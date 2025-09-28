<?php
namespace SpectraPro\Includes\Extensions;

use SpectraPro\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * QueryLoop
 *
 * @package spectra-pro
 * @since 1.0.0
 */
class QueryLoop {
	/**
	 * Initialization
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public static function init() {
		$self = new self();
		add_action( 'init', [ $self, 'register_block_loop_builder' ] );
		add_action( 'wp_enqueue_scripts', [ $self, 'enqueue_scripts' ] );
		add_action( 'wp_ajax_uagb_update_loop_builder_content', [ $self, 'update_loop_builder_content' ] );
		add_action( 'wp_ajax_nopriv_uagb_update_loop_builder_content', [ $self, 'update_loop_builder_content' ] );
	}

	/**
	 * Enqueue Scripts.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts() {         
		// Check if assets should be excluded for the current post type.
		if ( \UAGB_Admin_Helper::should_exclude_assets_for_cpt() ) {
			return; // Early return to prevent loading assets.
		}
		
		wp_enqueue_script( 'uagb-loop-builder', SPECTRA_PRO_URL . 'assets/js/loop-builder.js', array(), SPECTRA_PRO_VER, true );
		$current_object_id = get_the_ID();
		/* Archive & 404 page compatibility */
		if ( is_archive() || is_home() || is_search() || is_404() ) {
			global $wp_query;
			$current_object_id = $wp_query->get_queried_object_id();
		}
		// Localize the script with new data.
		$translation_array = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'post_id'  => $current_object_id,
			'nonce'    => wp_create_nonce( 'uagb-loop-builder' ),
		);
		if ( class_exists( 'UAGB_Post_Assets' ) && function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
			// Create Instance. Pass the Post ID.
			$post_assets_instance = new \UAGB_Post_Assets( $current_object_id );
			// Get all block templates.
			$template_types = get_block_templates();
			// Extract the 'slug' column from the block templates array.
			$template_type_slug = array_column( $template_types, 'slug' );
			// Check if the current page is a product category page.
			if ( is_tax( 'product_cat' ) && in_array( 'taxonomy-product_cat', $template_type_slug ) ) {
				// If the current page is a product category page and the template type slug includes 'taxonomy-product_cat'.
				// set the 'what_post_type' key in the translation array to 'taxonomy-product_cat'.
				$translation_array['what_post_type'] = 'taxonomy-product_cat';
			} elseif ( is_tax( 'product_tag' ) && in_array( 'taxonomy-product_tag', $template_type_slug ) ) { // Check if the current page is a product tag page.
				// If the current page is a product tag page and the template type slug includes 'taxonomy-product_tag'.
				// set the 'what_post_type' key in the translation array to 'taxonomy-product_tag'.
				$translation_array['what_post_type'] = 'taxonomy-product_tag';
			} else { // If the above conditions are not met.
				// Set the 'what_post_type' key in the translation array to the determined post type.
				// The 'determine_template_post_type' method is called on the $post_assets_instance object.
				// The current object ID is passed as an argument to the method.
				$translation_array['what_post_type'] = $post_assets_instance->determine_template_post_type( $current_object_id ); // phpcs:ignore
			}
		}//end if

		wp_localize_script( 'uagb-loop-builder', 'uagb_loop_builder', $translation_array );
	}

	/**
	 * Register loop builder blocks
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function register_block_loop_builder() {
		$self = new self();

		register_block_type(
			'uagb/loop-builder',
			array(
				'provides_context' => array(
					'queryId'       => 'queryId',
					'query'         => 'query',
					'displayLayout' => 'displayLayout',
				),
				'render_callback'  => [ $self, 'render_loop_builder' ],
			)
		);

		register_block_type(
			'uagb/loop-wrapper',
			array(
				'title'             => __( 'Wrapper', 'spectra-pro' ),
				'render_callback'   => [ $self, 'render_loop_wrapper' ],
				'uses_context'      => array(
					'queryId',
					'query',
					'displayLayout',
				),
				'skip_inner_blocks' => true,
			)
		);

		register_block_type(
			'uagb/loop-search',
			array(
				'title'             => __( 'Search - Filter', 'spectra-pro' ),
				'render_callback'   => [ $self, 'render_loop_search' ],
				'uses_context'      => array(
					'queryId',
					'query',
					'displayLayout',
				),
				'skip_inner_blocks' => true,
			)
		);

		register_block_type(
			'uagb/loop-sort',
			array(
				'title'             => __( 'Sort - Filter', 'spectra-pro' ),
				'render_callback'   => [ $self, 'render_loop_sort' ],
				'uses_context'      => array(
					'queryId',
					'query',
					'displayLayout',
				),
				'skip_inner_blocks' => true,
			)
		);

		register_block_type(
			'uagb/loop-reset',
			array(
				'title'             => __( 'Reset - Filter', 'spectra-pro' ),
				'render_callback'   => [ $self, 'render_loop_reset' ],
				'uses_context'      => array(
					'queryId',
					'query',
					'attrs',
				),
				'skip_inner_blocks' => true,
			)
		);

		register_block_type(
			'uagb/loop-pagination',
			array(
				'title'             => __( 'Loop Builder - Pagination', 'spectra-pro' ),
				'render_callback'   => [ $self, 'render_loop_pagination' ],
				'uses_context'      => array(
					'queryId',
					'query',
				),
				'skip_inner_blocks' => true,
			)
		);

		register_block_type(
			'uagb/loop-category',
			array(
				'title'             => __( 'Loop Builder - Category', 'spectra-pro' ),
				'render_callback'   => [ $self, 'render_loop_category' ],
				'uses_context'      => array(
					'queryId',
					'query',
					'attrs',
				),
				'skip_inner_blocks' => true,
			)
		);
	}

	/**
	 * Render block content.
	 *
	 * @param array $block_instance Block instance details.
	 * @param array $default_array of query attributes.
	 * @since 1.2.0
	 * @return string Rendered block content.
	 */
	public function render_block_content( $block_instance, $default_array ) {
		return (
			new \WP_Block(
				$block_instance,
				$default_array
			)
		)->render( array( 'dynamic' => false ) );
	}

	/**
	 * Callback function for loop-builder block
	 *
	 * @param array     $attributes block attributes.
	 * @param string    $content wrapper block content.
	 * @param \WP_Block $block wrapper block object.
	 * @return string
	 * @since 1.2.0
	 */
	public function render_loop_builder( $attributes, $content, $block ) {
		// Collect data from inner blocks for the schema.
		$inner_blocks_html = '';
		foreach ( $block->inner_blocks as $inner_block ) {
			if ( is_object( $inner_block ) && method_exists( $inner_block, 'render' ) ) {
				$inner_blocks_html .= $inner_block->render();
			}
		}
		$desktop_class = '';
		$tab_class     = '';
		$mob_class     = '';
		if ( array_key_exists( 'UAGHideDesktop', $attributes ) || array_key_exists( 'UAGHideTab', $attributes ) || array_key_exists( 'UAGHideMob', $attributes ) ) {

			$desktop_class = ( isset( $attributes['UAGHideDesktop'] ) ) ? 'uag-hide-desktop' : '';

			$tab_class = ( isset( $attributes['UAGHideTab'] ) ) ? 'uag-hide-tab' : '';

			$mob_class = ( isset( $attributes['UAGHideMob'] ) ) ? 'uag-hide-mob' : '';
		}

		$zindex_desktop           = '';
		$zindex_tablet            = '';
		$zindex_mobile            = '';
		$zindex_wrap              = array();
		$zindex_extension_enabled = ( isset( $attributes['zIndex'] ) || isset( $attributes['zIndexTablet'] ) || isset( $attributes['zIndexMobile'] ) );

		if ( $zindex_extension_enabled ) {
			$zindex_desktop = ( isset( $attributes['zIndex'] ) ) ? '--z-index-desktop:' . $attributes['zIndex'] . ';' : false;
			$zindex_tablet  = ( isset( $attributes['zIndexTablet'] ) ) ? '--z-index-tablet:' . $attributes['zIndexTablet'] . ';' : false;
			$zindex_mobile  = ( isset( $attributes['zIndexMobile'] ) ) ? '--z-index-mobile:' . $attributes['zIndexMobile'] . ';' : false;

			if ( $zindex_desktop ) {
				array_push( $zindex_wrap, $zindex_desktop );
			}

			if ( $zindex_tablet ) {
				array_push( $zindex_wrap, $zindex_tablet );
			}

			if ( $zindex_mobile ) {
				array_push( $zindex_wrap, $zindex_mobile );
			}
		}
		$zindex           = $zindex_extension_enabled ? 'uag-blocks-common-selector' : '';
		$class_name       = ( isset( $attributes['className'] ) ) ? $attributes['className'] : '';
		$align_class_name = ( isset( $attributes['align'] ) ) ? $attributes['align'] : 'wide';
		// Build the block's HTML.
		$output  = '<div class="' . esc_attr( "wp-block-uagb-loop-builder uagb-block-{$attributes['block_id']} {$desktop_class} {$tab_class} {$mob_class} {$zindex} {$class_name} align{$align_class_name}" ) . '" data-block_id="' . esc_attr( $attributes['block_id'] ) . '"  style="' . esc_attr( implode( '', $zindex_wrap ) ) . '">';
		$output .= $inner_blocks_html;
		$output .= '</div>';

		return $output;
	}

	/**
	 * Callback function for wrapper block
	 *
	 * @param array     $attributes block attributes.
	 * @param string    $content wrapper block content.
	 * @param \WP_Block $block wrapper block object.
	 * @return string
	 * @since 1.0.0
	 */
	public function render_loop_wrapper( $attributes, $content, $block ) {

		$content = $this->get_loop_wrapper_content( $block );

		$desktop_class = '';
		$tab_class     = '';
		$mob_class     = '';
		if ( array_key_exists( 'UAGHideDesktop', $attributes ) || array_key_exists( 'UAGHideTab', $attributes ) || array_key_exists( 'UAGHideMob', $attributes ) ) {

			$desktop_class = ( isset( $attributes['UAGHideDesktop'] ) ) ? 'uag-hide-desktop' : '';

			$tab_class = ( isset( $attributes['UAGHideTab'] ) ) ? 'uag-hide-tab' : '';

			$mob_class = ( isset( $attributes['UAGHideMob'] ) ) ? 'uag-hide-mob' : '';
		}

		$zindex_desktop           = '';
		$zindex_tablet            = '';
		$zindex_mobile            = '';
		$zindex_wrap              = array();
		$zindex_extension_enabled = ( isset( $attributes['zIndex'] ) || isset( $attributes['zIndexTablet'] ) || isset( $attributes['zIndexMobile'] ) );

		if ( $zindex_extension_enabled ) {
			$zindex_desktop = ( isset( $attributes['zIndex'] ) ) ? '--z-index-desktop:' . $attributes['zIndex'] . ';' : false;
			$zindex_tablet  = ( isset( $attributes['zIndexTablet'] ) ) ? '--z-index-tablet:' . $attributes['zIndexTablet'] . ';' : false;
			$zindex_mobile  = ( isset( $attributes['zIndexMobile'] ) ) ? '--z-index-mobile:' . $attributes['zIndexMobile'] . ';' : false;

			if ( $zindex_desktop ) {
				array_push( $zindex_wrap, $zindex_desktop );
			}

			if ( $zindex_tablet ) {
				array_push( $zindex_wrap, $zindex_tablet );
			}

			if ( $zindex_mobile ) {
				array_push( $zindex_wrap, $zindex_mobile );
			}
		}
		$zindex     = $zindex_extension_enabled ? 'uag-blocks-common-selector' : '';
		$class_name = ( isset( $attributes['className'] ) ) ? $attributes['className'] : '';

		return sprintf(
			'<div id="%1$s" data-query-id="%10$s" class="uagb-loop-container %2$s %3$s %4$s %5$s %6$s %7$s" style="%8$s">%9$s</div>',
			esc_attr( "uagb-block-queryid-{$block->context['queryId']}" ),
			esc_attr( "uagb-block-{$attributes['block_id']}" ),
			esc_attr( $desktop_class ), 
			esc_attr( $tab_class ), 
			esc_attr( $mob_class ), 
			esc_attr( $zindex ), 
			esc_attr( $class_name ),
			esc_attr( implode( '', $zindex_wrap ) ),
			$content,
			esc_attr( $block->context['queryId'] ) // better to get the queryid in data attribute.
		);
	}

	/**
	 * Callback function for reset block
	 * This function generates content for reset block.
	 *
	 * @param array     $attributes block attributes.
	 * @param string    $content reset block content.
	 * @param \WP_Block $block reset block object.
	 * @since 1.2.0
	 * @return string
	 */
	public function render_loop_reset( $attributes, $content, $block ) {
		// Get an instance of the current Post Template block.
		$block_instance = $block->parsed_block;

		$default_array = array(
			'queryId' => $block->context['queryId'],
			'query'   => $block->context['query'],
		);
		// Inside the render_loop_reset function.
		$block_content = $this->render_block_content( $block_instance, $default_array );
		return sprintf(
			'<div id="%1$s" class="uagb-loop-reset %2$s">%3$s</div>',
			esc_attr( "uagb-block-loop-reset-queryid-{$block->context['queryId']}" ),
			esc_attr( "uagb-block-{$attributes['block_id']}" ),
			$block_content
		);
	}

	/**
	 * Callback function for search block
	 * This function generates content for search block.
	 *
	 * @param array     $attributes block attributes.
	 * @param string    $content search block content.
	 * @param \WP_Block $block search block object.
	 * @since 1.2.0
	 * @return string
	 */
	public function render_loop_search( $attributes, $content, $block ) {
		$placeholder                 = isset( $attributes['placeholder'] ) ? esc_attr( $attributes['placeholder'] ) : esc_attr( __( 'Search', 'spectra-pro' ) );
		$margin_search_top           = isset( $attributes['marginSearchTop'] ) ? $attributes['marginSearchTop'] : '';
		$margin_search_right         = isset( $attributes['marginSearchRight'] ) ? $attributes['marginSearchRight'] : '';
		$margin_search_bottom        = isset( $attributes['marginSearchBottom'] ) ? $attributes['marginSearchBottom'] : '';
		$margin_search_left          = isset( $attributes['marginSearchLeft'] ) ? $attributes['marginSearchLeft'] : '';
		$margin_search_top_tablet    = isset( $attributes['marginSearchTopTablet'] ) ? $attributes['marginSearchTopTablet'] : '';
		$margin_search_right_tablet  = isset( $attributes['marginSearchRightTablet'] ) ? $attributes['marginSearchRightTablet'] : '';
		$margin_search_bottom_tablet = isset( $attributes['marginSearchBottomTablet'] ) ? $attributes['marginSearchBottomTablet'] : '';
		$margin_search_left_tablet   = isset( $attributes['marginSearchLeftTablet'] ) ? $attributes['marginSearchLeftTablet'] : '';
		$margin_search_top_mobile    = isset( $attributes['marginSearchTopMobile'] ) ? $attributes['marginSearchTopMobile'] : '';
		$margin_search_right_mobile  = isset( $attributes['marginSearchRightMobile'] ) ? $attributes['marginSearchRightMobile'] : '';
		$margin_search_bottom_mobile = isset( $attributes['marginSearchBottomMobile'] ) ? $attributes['marginSearchBottomMobile'] : '';
		$margin_search_left_mobile   = isset( $attributes['marginSearchLeftMobile'] ) ? $attributes['marginSearchLeftMobile'] : '';
		// Define an array containing your variables.
		$margin_variables = array(
			$margin_search_top,
			$margin_search_right,
			$margin_search_bottom,
			$margin_search_left,
			$margin_search_top_tablet,
			$margin_search_right_tablet,
			$margin_search_bottom_tablet,
			$margin_search_left_tablet,
			$margin_search_top_mobile,
			$margin_search_right_mobile,
			$margin_search_bottom_mobile,
			$margin_search_left_mobile,
		);
		// Use array_filter() with the callback function.
		$has_margin    = count( array_filter( $margin_variables, 'is_numeric' ) ) > 0;
		$margin_class  = $has_margin ? 'wp-block-uagb-search--has-margin' : '';
		$html          = $has_margin ? '<div class="uagb-search-margin-wrapper"><input type="text" class="uagb-loop-search" placeholder="' . $placeholder . '" data-uagb-block-query-id="' . esc_attr( $block->context['queryId'] ) . '"></div>' : '<input type="text" class="uagb-loop-search" placeholder="' . $placeholder . '" data-uagb-block-query-id="' . esc_attr( $block->context['queryId'] ) . '">';
		$desktop_class = '';
		$tab_class     = '';
		$mob_class     = '';
		if ( array_key_exists( 'UAGHideDesktop', $attributes ) || array_key_exists( 'UAGHideTab', $attributes ) || array_key_exists( 'UAGHideMob', $attributes ) ) {

			$desktop_class = ( isset( $attributes['UAGHideDesktop'] ) ) ? 'uag-hide-desktop' : '';

			$tab_class = ( isset( $attributes['UAGHideTab'] ) ) ? 'uag-hide-tab' : '';

			$mob_class = ( isset( $attributes['UAGHideMob'] ) ) ? 'uag-hide-mob' : '';
		}

		$zindex_desktop           = '';
		$zindex_tablet            = '';
		$zindex_mobile            = '';
		$zindex_wrap              = array();
		$zindex_extension_enabled = ( isset( $attributes['zIndex'] ) || isset( $attributes['zIndexTablet'] ) || isset( $attributes['zIndexMobile'] ) );

		if ( $zindex_extension_enabled ) {
			$zindex_desktop = ( isset( $attributes['zIndex'] ) ) ? '--z-index-desktop:' . $attributes['zIndex'] . ';' : false;
			$zindex_tablet  = ( isset( $attributes['zIndexTablet'] ) ) ? '--z-index-tablet:' . $attributes['zIndexTablet'] . ';' : false;
			$zindex_mobile  = ( isset( $attributes['zIndexMobile'] ) ) ? '--z-index-mobile:' . $attributes['zIndexMobile'] . ';' : false;

			if ( $zindex_desktop ) {
				array_push( $zindex_wrap, $zindex_desktop );
			}

			if ( $zindex_tablet ) {
				array_push( $zindex_wrap, $zindex_tablet );
			}

			if ( $zindex_mobile ) {
				array_push( $zindex_wrap, $zindex_mobile );
			}
		}
		$zindex     = $zindex_extension_enabled ? 'uag-blocks-common-selector' : '';
		$class_name = ( isset( $attributes['className'] ) ) ? $attributes['className'] : '';
		// Need to be updated as per settings options.
		return '<div class="wp-block-uagb-loop-search uagb-search-container uagb-block-' . esc_attr( $attributes['block_id'] ) . ' ' . esc_attr( $margin_class ) . ' ' . esc_attr( $desktop_class ) . ' ' . esc_attr( $tab_class ) . ' ' . esc_attr( $mob_class ) . ' ' . esc_attr( $zindex ) . ' ' . esc_attr( $class_name ) . '" style="' . esc_attr( implode( '', $zindex_wrap ) ) . '">' . $html . '</div>';
	}

	/**
	 * Callback function for sort block
	 * This function generates content for sort block.
	 *
	 * @param array     $attributes block attributes.
	 * @param string    $content sort block content.
	 * @param \WP_Block $block sort block object.
	 * @since 1.2.0
	 * @return string
	 */
	public function render_loop_sort( $attributes, $content, $block ) {
		ob_start();
		// default sort option array.
		$default_sort_options      = array(
			'post_title|desc'    => __( 'Sort by title (Z-A)', 'spectra-pro' ),
			'post_title|asc'     => __( 'Sort by title (A-Z)', 'spectra-pro' ),
			'post_date|desc'     => __( 'Sort by newest', 'spectra-pro' ),
			'post_date|asc'      => __( 'Sort by oldest', 'spectra-pro' ),
			'post_id|desc'       => __( 'Post ID descending', 'spectra-pro' ),
			'post_id|asc'        => __( 'Post ID ascending', 'spectra-pro' ),
			'post_modified|desc' => __( 'Modified last', 'spectra-pro' ),
			'post_modified|asc'  => __( 'Modified recently', 'spectra-pro' ),
			'post_author|desc'   => __( 'Sort by author (Z-A)', 'spectra-pro' ),
			'post_author|asc'    => __( 'Sort by author (A-Z)', 'spectra-pro' ),
		);
		$margin_sort_top           = isset( $attributes['marginSortTop'] ) ? $attributes['marginSortTop'] : '';
		$margin_sort_right         = isset( $attributes['marginSortRight'] ) ? $attributes['marginSortRight'] : '';
		$margin_sort_bottom        = isset( $attributes['marginSortBottom'] ) ? $attributes['marginSortBottom'] : '';
		$margin_sort_left          = isset( $attributes['marginSortLeft'] ) ? $attributes['marginSortLeft'] : '';
		$margin_sort_top_tablet    = isset( $attributes['marginSortTopTablet'] ) ? $attributes['marginSortTopTablet'] : '';
		$margin_sort_right_tablet  = isset( $attributes['marginSortRightTablet'] ) ? $attributes['marginSortRightTablet'] : '';
		$margin_sort_bottom_tablet = isset( $attributes['marginSortBottomTablet'] ) ? $attributes['marginSortBottomTablet'] : '';
		$margin_sort_left_tablet   = isset( $attributes['marginSortLeftTablet'] ) ? $attributes['marginSortLeftTablet'] : '';
		$margin_sort_top_mobile    = isset( $attributes['marginSortTopMobile'] ) ? $attributes['marginSortTopMobile'] : '';
		$margin_sort_right_mobile  = isset( $attributes['marginSortRightMobile'] ) ? $attributes['marginSortRightMobile'] : '';
		$margin_sort_bottom_mobile = isset( $attributes['marginSortBottomMobile'] ) ? $attributes['marginSortBottomMobile'] : '';
		$margin_sort_left_mobile   = isset( $attributes['marginSortLeftMobile'] ) ? $attributes['marginSortLeftMobile'] : '';
		// Define an array containing your variables.
		$margin_variables = array(
			$margin_sort_top,
			$margin_sort_right,
			$margin_sort_bottom,
			$margin_sort_left,
			$margin_sort_top_tablet,
			$margin_sort_right_tablet,
			$margin_sort_bottom_tablet,
			$margin_sort_left_tablet,
			$margin_sort_top_mobile,
			$margin_sort_right_mobile,
			$margin_sort_bottom_mobile,
			$margin_sort_left_mobile,
		);
		// Use array_filter() with the callback function.
		$has_margin   = count( array_filter( $margin_variables, 'is_numeric' ) ) > 0;
		$margin_class = $has_margin ? 'wp-block-uagb-sort--has-margin' : '';
		// shortList is not empty and is array then show user selected setting from backend.
		if ( ! empty( $attributes['shortList'] ) && is_array( $attributes['shortList'] ) ) {
			// Initialize an empty array to store the matching key-value pairs.
			$matched_options = array();
			$sort_options    = $attributes['shortList'];
			// Loop through the selected options array.
			foreach ( $sort_options as $selected_option ) {
				// Check if the selected option is a key in the default_sort_options array.
				if ( array_key_exists( $selected_option, $default_sort_options ) ) {
					// If it matches, add the key-value pair to the new array.
					$matched_options[ $selected_option ] = $default_sort_options[ $selected_option ];
				}
			}
		} else { // If shortList is empty and not array and user did not selected anything in backend.
			$matched_options = array(
				'post_title|desc'    => __( 'Sort by title (Z-A)', 'spectra-pro' ),
				'post_title|asc'     => __( 'Sort by title (A-Z)', 'spectra-pro' ),
				'post_date|desc'     => __( 'Sort by newest', 'spectra-pro' ),
				'post_date|asc'      => __( 'Sort by oldest', 'spectra-pro' ),
				'post_id|desc'       => __( 'Post ID descending', 'spectra-pro' ),
				'post_id|asc'        => __( 'Post ID ascending', 'spectra-pro' ),
				'post_modified|desc' => __( 'Modified last', 'spectra-pro' ),
				'post_modified|asc'  => __( 'Modified recently', 'spectra-pro' ),
				'post_author|desc'   => __( 'Sort by author (Z-A)', 'spectra-pro' ),
				'post_author|asc'    => __( 'Sort by author (A-Z)', 'spectra-pro' ),
			);
		}//end if
		$desktop_class = '';
		$tab_class     = '';
		$mob_class     = '';
		if ( array_key_exists( 'UAGHideDesktop', $attributes ) || array_key_exists( 'UAGHideTab', $attributes ) || array_key_exists( 'UAGHideMob', $attributes ) ) {

			$desktop_class = ( isset( $attributes['UAGHideDesktop'] ) ) ? 'uag-hide-desktop' : '';

			$tab_class = ( isset( $attributes['UAGHideTab'] ) ) ? 'uag-hide-tab' : '';

			$mob_class = ( isset( $attributes['UAGHideMob'] ) ) ? 'uag-hide-mob' : '';
		}

		$zindex_desktop           = '';
		$zindex_tablet            = '';
		$zindex_mobile            = '';
		$zindex_wrap              = array();
		$zindex_extension_enabled = ( isset( $attributes['zIndex'] ) || isset( $attributes['zIndexTablet'] ) || isset( $attributes['zIndexMobile'] ) );

		if ( $zindex_extension_enabled ) {
			$zindex_desktop = ( isset( $attributes['zIndex'] ) ) ? '--z-index-desktop:' . $attributes['zIndex'] . ';' : false;
			$zindex_tablet  = ( isset( $attributes['zIndexTablet'] ) ) ? '--z-index-tablet:' . $attributes['zIndexTablet'] . ';' : false;
			$zindex_mobile  = ( isset( $attributes['zIndexMobile'] ) ) ? '--z-index-mobile:' . $attributes['zIndexMobile'] . ';' : false;

			if ( $zindex_desktop ) {
				array_push( $zindex_wrap, $zindex_desktop );
			}

			if ( $zindex_tablet ) {
				array_push( $zindex_wrap, $zindex_tablet );
			}

			if ( $zindex_mobile ) {
				array_push( $zindex_wrap, $zindex_mobile );
			}
		}
		$zindex     = $zindex_extension_enabled ? 'uag-blocks-common-selector' : '';
		$class_name = ( isset( $attributes['className'] ) ) ? $attributes['className'] : '';
		?>
		<div class="wp-block-uagb-loop-sort uagb-sort-container uagb-block-<?php echo esc_attr( $attributes['block_id'] ); ?> <?php echo esc_attr( $margin_class ); ?> <?php echo esc_attr( $desktop_class ); ?> <?php echo esc_attr( $tab_class ); ?> <?php echo esc_attr( $mob_class ); ?> <?php echo esc_attr( $zindex ); ?> <?php echo esc_attr( $class_name ); ?>" style="<?php echo esc_attr( implode( '', $zindex_wrap ) ); ?>" >
		<?php if ( $has_margin ) { ?>
			<div class="uagb-sort-margin-wrapper">
		<?php } ?>	
			<select class="uagb-loop-sort" data-uagb-block-query-id="<?php echo esc_attr( $block->context['queryId'] ); ?>">
				<option selected value=""><?php esc_html_e( 'Select', 'spectra-pro' ); ?></option>	
				<?php foreach ( $matched_options as $key => $value ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></option>
				<?php endforeach; ?>
			</select>
		<?php if ( $has_margin ) { ?>
			</div>
		<?php } ?>
		</div>
		<?php
		$sort_markup = ob_get_clean();

		return ! empty( $sort_markup ) ? $sort_markup : '';
	}

	/**
	 * Callback function for pagination block
	 * This function generates content for pagination block.
	 *
	 * @param array     $attributes block attributes.
	 * @param string    $content pagination block content.
	 * @param \WP_Block $block pagination block object.
	 * @since 1.2.0
	 * @return string
	 */
	public function render_loop_pagination( $attributes, $content, $block ) {
		$content = $this->get_loop_pagination_content( $block );

		$desktop_class = '';
		$tab_class     = '';
		$mob_class     = '';
		if ( array_key_exists( 'UAGHideDesktop', $attributes ) || array_key_exists( 'UAGHideTab', $attributes ) || array_key_exists( 'UAGHideMob', $attributes ) ) {

			$desktop_class = ( isset( $attributes['UAGHideDesktop'] ) ) ? 'uag-hide-desktop' : '';

			$tab_class = ( isset( $attributes['UAGHideTab'] ) ) ? 'uag-hide-tab' : '';

			$mob_class = ( isset( $attributes['UAGHideMob'] ) ) ? 'uag-hide-mob' : '';
		}

		$zindex_desktop           = '';
		$zindex_tablet            = '';
		$zindex_mobile            = '';
		$zindex_wrap              = array();
		$zindex_extension_enabled = ( isset( $attributes['zIndex'] ) || isset( $attributes['zIndexTablet'] ) || isset( $attributes['zIndexMobile'] ) );

		if ( $zindex_extension_enabled ) {
			$zindex_desktop = ( isset( $attributes['zIndex'] ) ) ? '--z-index-desktop:' . $attributes['zIndex'] . ';' : false;
			$zindex_tablet  = ( isset( $attributes['zIndexTablet'] ) ) ? '--z-index-tablet:' . $attributes['zIndexTablet'] . ';' : false;
			$zindex_mobile  = ( isset( $attributes['zIndexMobile'] ) ) ? '--z-index-mobile:' . $attributes['zIndexMobile'] . ';' : false;

			if ( $zindex_desktop ) {
				array_push( $zindex_wrap, $zindex_desktop );
			}

			if ( $zindex_tablet ) {
				array_push( $zindex_wrap, $zindex_tablet );
			}

			if ( $zindex_mobile ) {
				array_push( $zindex_wrap, $zindex_mobile );
			}
		}
		$zindex     = $zindex_extension_enabled ? 'uag-blocks-common-selector' : '';
		$class_name = ( isset( $attributes['className'] ) ) ? $attributes['className'] : '';

		return sprintf(
			'<div id="%1$s" class="uagb-loop-pagination %2$s %3$s %4$s %5$s %6$s %7$s" style="%8$s">%9$s</div>',
			esc_attr( "uagb-block-pagination-queryid-{$block->context['queryId']}" ),
			esc_attr( "uagb-block-{$attributes['block_id']}" ),
			esc_attr( $desktop_class ), 
			esc_attr( $tab_class ), 
			esc_attr( $mob_class ), 
			esc_attr( $zindex ), 
			esc_attr( $class_name ),
			esc_attr( implode( '', $zindex_wrap ) ),
			$content
		);
	}

	/**
	 * This function generates content for loop-pagination block as per query parameters provided.
	 *
	 * @param \WP_Block $block block object.
	 * @since 1.2.0
	 * @return string
	 */
	public function get_loop_category_buttons_content( $block ) {
		$block_instance = $block->parsed_block;

		$default_array = array(
			'queryId' => $block->context['queryId'],
			'query'   => $block->context['query'],
			'attrs'   => $block->context['attr'],
		);
		// Inside the get_loop_category_buttons_content function.
		$block_content = $this->render_block_content( $block_instance, $default_array );

		return $block_content;
	}

	/**
	 * Callback function for sort block
	 * This function generates content for sort block.
	 *
	 * @param array     $attributes block attributes.
	 * @param string    $content sort block content.
	 * @param \WP_Block $block sort block object.
	 * @since 1.2.0
	 * @return string
	 */
	public function render_loop_category( $attributes, $content, $block ) {
		// Assuming $block->context is the array you provided.
		$block->context['attr']   = $block->parsed_block['attrs'];
		$block_id                 = $attributes['block_id'];
		$layout                   = ! empty( $attributes['layout'] ) ? $attributes['layout'] : 'stack';
		$layout_tablet            = ! empty( $attributes['layoutTablet'] ) ? $attributes['layoutTablet'] : 'stack';
		$layout_mobile            = ! empty( $attributes['layoutMobile'] ) ? $attributes['layoutMobile'] : 'stack';
		$cat_type                 = ! empty( $attributes['catType'] ) ? $attributes['catType'] : 'select';
		$post_type                = ! empty( $block->context['attr'] ) ? $block->context['attr']['query']['postType'] : 'post';
		$taxonomy_type            = ! empty( $attributes['taxonomyType'] ) ? $attributes['taxonomyType'] : 'category';
		$tax_type                 = ! empty( $attributes['taxType'] ) ? $attributes['taxType'] : 'include';
		$show_empty_taxonomy      = ! empty( $attributes['showEmptyTaxonomy'] ) ? $attributes['showEmptyTaxonomy'] : false;
		$show_count               = ! empty( $attributes['showCount'] ) ? $attributes['showCount'] : false;
		$show_children            = ! empty( $attributes['showChildren'] ) ? $attributes['showChildren'] : false;
		$margin_cat_top           = isset( $attributes['marginCatTop'] ) ? $attributes['marginCatTop'] : '';
		$margin_cat_right         = isset( $attributes['marginCatRight'] ) ? $attributes['marginCatRight'] : '';
		$margin_cat_bottom        = isset( $attributes['marginCatBottom'] ) ? $attributes['marginCatBottom'] : '';
		$margin_cat_left          = isset( $attributes['marginCatLeft'] ) ? $attributes['marginCatLeft'] : '';
		$margin_cat_top_tablet    = isset( $attributes['marginCatTopTablet'] ) ? $attributes['marginCatTopTablet'] : '';
		$margin_cat_right_tablet  = isset( $attributes['marginCatRightTablet'] ) ? $attributes['marginCatRightTablet'] : '';
		$margin_cat_bottom_tablet = isset( $attributes['marginCatBottomTablet'] ) ? $attributes['marginCatBottomTablet'] : '';
		$margin_cat_left_tablet   = isset( $attributes['marginCatLeftTablet'] ) ? $attributes['marginCatLeftTablet'] : '';
		$margin_cat_top_mobile    = isset( $attributes['marginCatTopMobile'] ) ? $attributes['marginCatTopMobile'] : '';
		$margin_cat_right_mobile  = isset( $attributes['marginCatRightMobile'] ) ? $attributes['marginCatRightMobile'] : '';
		$margin_cat_bottom_mobile = isset( $attributes['marginCatBottomMobile'] ) ? $attributes['marginCatBottomMobile'] : '';
		$margin_cat_left_mobile   = isset( $attributes['marginCatLeftMobile'] ) ? $attributes['marginCatLeftMobile'] : '';
		// Define an array containing your variables.
		$margin_variables = array(
			$margin_cat_top,
			$margin_cat_right,
			$margin_cat_bottom,
			$margin_cat_left,
			$margin_cat_top_tablet,
			$margin_cat_right_tablet,
			$margin_cat_bottom_tablet,
			$margin_cat_left_tablet,
			$margin_cat_top_mobile,
			$margin_cat_right_mobile,
			$margin_cat_bottom_mobile,
			$margin_cat_left_mobile,
		);
		// Use array_filter() with the callback function.
		$has_margin   = count( array_filter( $margin_variables, 'is_numeric' ) ) > 0;
		$margin_class = $has_margin ? 'wp-block-uagb-cat--has-margin' : '';
		// include/ exclude.
		$include_value = ! empty( $attributes['taxonomy'] ) ? $attributes['taxonomy'] : array();
		$exclude_value = ! empty( $attributes['taxonomyExclude'] ) ? $attributes['taxonomyExclude'] : array();
		// Initialize an empty array to store the new format.
		$categories_list = array();
		// Your original array of WP_Term objects.
		$categories = get_categories(
			array(
				'orderby'    => 'name',
				'parent'     => 0,
				'hide_empty' => ! $show_empty_taxonomy,
				'taxonomy'   => $taxonomy_type,
			) 
		);
		// Check if the array is empty.
		if ( empty( $categories ) ) {
			return '';
		}
		// Iterate through the WP_Term objects.
		foreach ( $categories as $term_object ) {
			if ( ! empty( $term_object->taxonomy ) && ! empty( $term_object->term_id ) && ! empty( $term_object->name ) && isset( $term_object->count ) ) {
				if ( $show_children ) {
					// Get the children categories.
					$child_category  = Utils::get_child_categories( $term_object->term_id, $show_empty_taxonomy, $taxonomy_type );
					$categories_list = array_merge( $categories_list, $child_category );
				}
				// Build the desired format and add it to the new array.
				$categories_list[] = $term_object->taxonomy . ':' . $term_object->term_id . ':' . $term_object->name . ':' . $term_object->count;
			}
		}
		// condition check.
		if ( 'exclude' === $tax_type && ! empty( $exclude_value ) ) {
			// Use array_filter with an anonymous function to exclude elements in $exclude_value.
			// Loop through each value to remove.
			foreach ( $exclude_value as $value ) {
				// Loop through the original array and remove the value.
				foreach ( $categories_list as $key => $element ) {
					if ( strpos( $element, $value ) !== false ) {
						unset( $categories_list[ $key ] );
					}
				}
			}
			// Reindex the array.
			$new_categories_list = array_values( $categories_list );
		}

		// condition check.
		if ( 'include' === $tax_type && ! empty( $include_value ) ) {
			$matching_values = array();
			// Use array_filter with an anonymous function to include elements in $include_value.
			// Loop through each value to add.
			foreach ( $include_value as $value ) {
				// Loop through the original array and add the value.
				foreach ( $categories_list as $key => $element ) {
					if ( strpos( $element, $value ) !== false ) {
						$matching_values[] = $element;
						break; // Break inner loop if match found.
					}
				}
			}
			// Reindex the array.
			$new_categories_list = array_values( $matching_values );
		}

		if ( empty( $new_categories_list ) ) {
			$new_categories_list = $categories_list;
		}
		$content = $this->get_loop_category_buttons_content( $block );

		$button_class  = 'checkbox' !== $cat_type && 'select' !== $cat_type ? 'wp-block-button' : '';
		$desktop_class = '';
		$tab_class     = '';
		$mob_class     = '';
		if ( array_key_exists( 'UAGHideDesktop', $attributes ) || array_key_exists( 'UAGHideTab', $attributes ) || array_key_exists( 'UAGHideMob', $attributes ) ) {

			$desktop_class = ( isset( $attributes['UAGHideDesktop'] ) ) ? 'uag-hide-desktop' : '';

			$tab_class = ( isset( $attributes['UAGHideTab'] ) ) ? 'uag-hide-tab' : '';

			$mob_class = ( isset( $attributes['UAGHideMob'] ) ) ? 'uag-hide-mob' : '';
		}

		$zindex_desktop           = '';
		$zindex_tablet            = '';
		$zindex_mobile            = '';
		$zindex_wrap              = array();
		$zindex_extension_enabled = ( isset( $attributes['zIndex'] ) || isset( $attributes['zIndexTablet'] ) || isset( $attributes['zIndexMobile'] ) );

		if ( $zindex_extension_enabled ) {
			$zindex_desktop = ( isset( $attributes['zIndex'] ) ) ? '--z-index-desktop:' . $attributes['zIndex'] . ';' : false;
			$zindex_tablet  = ( isset( $attributes['zIndexTablet'] ) ) ? '--z-index-tablet:' . $attributes['zIndexTablet'] . ';' : false;
			$zindex_mobile  = ( isset( $attributes['zIndexMobile'] ) ) ? '--z-index-mobile:' . $attributes['zIndexMobile'] . ';' : false;

			if ( $zindex_desktop ) {
				array_push( $zindex_wrap, $zindex_desktop );
			}

			if ( $zindex_tablet ) {
				array_push( $zindex_wrap, $zindex_tablet );
			}

			if ( $zindex_mobile ) {
				array_push( $zindex_wrap, $zindex_mobile );
			}
		}
		$zindex     = $zindex_extension_enabled ? 'uag-blocks-common-selector' : '';
		$class_name = ( isset( $attributes['className'] ) ) ? $attributes['className'] : '';
		ob_start();
		?>
		<div class="wp-block-uagb-loop-category uagb-loop-category-layout-<?php echo esc_attr( $layout ); ?> uagb-loop-category-layout-<?php echo esc_attr( $layout_tablet ); ?>-tablet uagb-loop-category-layout-<?php echo esc_attr( $layout_mobile ); ?>-mobile uagb-loop-category-type-<?php echo esc_attr( $cat_type ); ?> uagb-category-container uagb-block-<?php echo esc_attr( $attributes['block_id'] ); ?> <?php echo esc_attr( $button_class ); ?> <?php echo esc_attr( $margin_class ); ?> <?php echo esc_attr( $margin_class ); ?> <?php echo esc_attr( $desktop_class ); ?> <?php echo esc_attr( $tab_class ); ?> <?php echo esc_attr( $mob_class ); ?> <?php echo esc_attr( $zindex ); ?> <?php echo esc_attr( $class_name ); ?>" style="<?php echo esc_attr( implode( '', $zindex_wrap ) ); ?>">
		<?php if ( 'select' === $cat_type ) { ?>	
			<?php if ( $has_margin ) { ?>
				<div class="uagb-cat-margin-wrapper">
			<?php } ?>
		<select class="uagb-loop-category" data-uagb-block-query-id="<?php echo esc_attr( $block->context['queryId'] ); ?>">
				<option selected value=""><?php esc_html_e( 'Select', 'spectra-pro' ); ?></option>
				<?php
				if ( is_array( $new_categories_list ) ) {
					foreach ( $new_categories_list as $key => $value ) {
						$parts         = explode( ':', $value );
						$taxonomy_name = $parts[0];
						$number        = (int) $parts[1];
						// Check if the category ID is valid.
						$name_of_tax = $parts[2];
						$count       = $parts[3];
						?>
					<option value="<?php echo esc_attr( $tax_type ); ?> | <?php echo esc_attr( $taxonomy_name ); ?> | <?php echo intval( $number ); ?>" >
						<?php echo esc_attr( $name_of_tax ); ?>
						<?php if ( $show_count ) { ?>
							<?php echo ' (' . intval( $count ) . ')'; ?>
						<?php } ?>
					</option>
						<?php
					}//end foreach
				}//end if
				?>
			</select>
			<?php if ( $has_margin ) { ?>
				</div>
			<?php } ?>
			<?php
		} elseif ( 'checkbox' === $cat_type ) {
			if ( is_array( $new_categories_list ) ) {
				foreach ( $new_categories_list as $key => $value ) {
					$parts         = explode( ':', $value );
					$taxonomy_name = $parts[0];
					$number        = (int) $parts[1];
					// Check if the category ID is valid.
					$name_of_tax = $parts[2];
					$count       = $parts[3];
				
					?>
					<div class="uagb-cat-checkbox-item">	
					<input
						type="checkbox"
						class="uagb-cat-checkbox"
						data-uagb-block-query-id="<?php echo esc_attr( $block->context['queryId'] ); ?>"
						id="checkbox-<?php echo esc_attr( $key ); ?>-<?php echo esc_attr( $attributes['block_id'] ); ?>-<?php echo esc_attr( $attributes['block_id'] ); ?>"
						name="<?php echo esc_attr( $attributes['block_id'] ); ?>"
						value="<?php echo esc_attr( $tax_type ); ?> | <?php echo esc_attr( $taxonomy_name ); ?> | <?php echo intval( $number ); ?>"
					/>
					<label for="checkbox-<?php echo esc_attr( $key ); ?>-<?php echo esc_attr( $attributes['block_id'] ); ?>-<?php echo esc_attr( $attributes['block_id'] ); ?>">
							<?php echo esc_attr( $name_of_tax ); ?>
							<?php if ( $show_count ) { ?>
								<?php echo ' (' . intval( $count ) . ')'; ?>
						<?php } ?>
					</label>
					</div>
					<?php	
				}//end foreach
				?>
				<?php
			}//end if
		} else {
			?>
			<div data-uagb-block-query-id="<?php echo esc_attr( $block->context['queryId'] ); ?>" class="uagb-loop-category-inner uagb-block-<?php echo esc_attr( $attributes['block_id'] ); ?>">
				<?php
				$allowed_html = array(
					'div'  => array(
						'class'     => array(),
						'id'        => array(),
						'data-type' => array(),
					),
					'a'    => array(
						'id'                       => array(),
						'data-uagb-block-query-id' => array(),
						'class'                    => array(),
						'aria-label'               => array(),
						'href'                     => array(),
						'rel'                      => array(),
						'target'                   => array(),
					),
					'span' => array(
						'class' => array(),
					),
					'svg'  => array(
						'xmlns'       => array(),
						'fill'        => array(),
						'viewbox'     => array(),
						'role'        => array(),
						'aria-hidden' => array(),
						'focusable'   => array(),
						'height'      => array(),
						'width'       => array(),
					),
					'path' => array(
						'd' => array(),
					),
				);
				echo wp_kses( $content, $allowed_html );
				?>
			</div>
			<?php
		}//end if
		?>
		</div>
		<?php
		$sort_markup = ob_get_clean();

		return ! empty( $sort_markup ) ? $sort_markup : '';
	}

	/**
	 * This function generates content for loop-wrapper block as per query parameters provided.
	 *
	 * @param \WP_Block $block block object.
	 * @since 1.2.0
	 * @return string
	 */
	public function get_loop_wrapper_content( $block ) {
		// callback function for wrapper block nonce verification not required.
		$page = empty( $block->context['query']['paged'] ) ? 1 : intval( $block->context['query']['paged'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is from WordPress block context No nonce verification needed.

		// Use global query if needed.
		$use_global_query = ( isset( $block->context['query']['inherit'] ) && $block->context['query']['inherit'] );
		if ( $use_global_query ) {
			global $wp_query;
			$query = clone $wp_query;
		} else {
			$query_args = (array) apply_filters(
				'spectra_loop_builder_main_query_args',
				build_query_vars_from_query_block( $block, $page )
			);
			$query_args = array_merge( $query_args, Utils::customize_block_query( $block ) );
			$query      = new \WP_Query( $query_args );
		}

		$content = '';
		while ( $query->have_posts() ) {
			$query->the_post();
			// Get an instance of the current Post Template block.
			$block_instance = $block->parsed_block;

			$default_array = array(
				'postType' => get_post_type(),
				'postId'   => get_the_ID(),
			);
			// Inside the get_loop_wrapper_content function.
			$block_content = $this->render_block_content( $block_instance, $default_array );

			$content .= '<div class="uagb-loop-post"><div class="uagb-loop-post-inner">' . $block_content . '</div></div>';
		}//end while

		/*
		* Use this function to restore the context of the template tags
		* from a secondary query loop back to the main query loop.
		* Since we use two custom loops, it's safest to always restore.
		*/
		wp_reset_postdata();

		return $content;
	}

	/**
	 * This function generates content for loop-pagination block as per query parameters provided.
	 *
	 * @param \WP_Block $block block object.
	 * @since 1.2.0
	 * @return string
	 */
	public function get_loop_pagination_content( $block ) {
		$block_instance = $block->parsed_block;

		$default_array = array(
			'postType' => get_post_type(),
			'postId'   => get_the_ID(),
			'queryId'  => $block->context['queryId'],
			'query'    => $block->context['query'],
		);
		// Inside the get_loop_pagination_content function.
		$block_content = $this->render_block_content( $block_instance, $default_array );

		return $block_content;
	}

	/**
	 * Generates parse content for all blocks.
	 *
	 * @param int $id of blocks.
	 * @since 1.2.0
	 * @return array $loop_builder_blocks array of blocks.
	 */
	public function get_loop_builder_using_post_content( $id ) {

		$content = get_post_field( 'post_content', $id );

		$loop_builder_blocks = parse_blocks( $content );

		$loop_builder = $this->get_loop_builder_recursive( $loop_builder_blocks );

		return $loop_builder;
	}

	/**
	 * Retrieves the ID of a template part based on its slug.
	 *
	 * @param array $block The block attributes.
	 * @since 1.2.0
	 * @return int|null The ID of the template part, or null if not found.
	 */
	public function get_fse_template_part( $block ) {
		if ( empty( $block['attrs']['slug'] ) ) {
			return null;
		}

		$slug            = $block['attrs']['slug'];
		$templates_parts = get_block_templates( array( 'slugs__in' => $slug ), 'wp_template_part' );
		foreach ( $templates_parts as $templates_part ) {
			if ( $slug === $templates_part->slug ) {
				$id = $templates_part->wp_id;
				return $id;
			}
		}
		return null;
	}

	/**
	 * Get the loop builder array.
	 *
	 * @param  array $blocks_array Block array.
	 *
	 * @since 1.2.0
	 * @return array $final_loop_builder_array array.
	 */
	public function get_loop_builder_recursive( $blocks_array ) {
		$final_loop_builder_array = array();

		foreach ( $blocks_array as $key => $block ) {
				
			if ( 'uagb/loop-builder' === $block['blockName'] ) {
				$final_loop_builder_array[] = $block;
			} elseif ( 'core/block' === $block['blockName'] ) {
				$id = ( isset( $block['attrs']['ref'] ) ) ? $block['attrs']['ref'] : 0;
				if ( $id ) {
					$template_part_loop_builder_array = $this->get_loop_builder_using_post_content( $id );
					$final_loop_builder_array         = array_merge( $final_loop_builder_array, $template_part_loop_builder_array );
				}   
			} elseif ( 'core/template-part' === $block['blockName'] ) {
				$id = $this->get_fse_template_part( $block );
				if ( $id ) {
					$template_part_loop_builder_array = $this->get_loop_builder_using_post_content( $id );
					$final_loop_builder_array         = array_merge( $final_loop_builder_array, $template_part_loop_builder_array );
				}
			} else {
				$inner_block_loop_builder_array = $this->get_loop_builder_recursive( $block['innerBlocks'] );
				$final_loop_builder_array       = array_merge( $final_loop_builder_array, $inner_block_loop_builder_array );
			}
		}//end foreach

		return $final_loop_builder_array;
	}

	/**
	 * Get post content with blocks for a given post ID.
	 *
	 * @param int $post_id Post ID.
	 * @since 1.2.0
	 * @return string|\WP_Error Post content if valid, otherwise error.
	 */
	public function get_post_content_by_id( $post_id ) {
		// Get the post object for the given post ID.
		$post = get_post( $post_id );

		// Check if the post is a valid WP_Post instance.
		if ( ! $post instanceof \WP_Post ) {
			return '';
		}

		// Check if the post has blocks and return content if it does.
		if ( $post instanceof \WP_Post && has_blocks( $post->post_content ) ) {
			return $post->post_content;
		}

		// Return empty string if no blocks are present in the post content.
		return '';
	}

	/**
	 * This is a callback function for ajax call to update the loop-wrapper block content.
	 * As per the filter selected by user in front end.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function update_loop_builder_content() {
		if ( ! check_ajax_referer( 'uagb-loop-builder', 'security', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce', 'spectra-pro' ) ) );
		}

		$post_id = ! empty( $_POST['postId'] ) ? (int) sanitize_text_field( $_POST['postId'] ) : 0;     
		if ( $post_id < 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post id', 'spectra-pro' ) ) );
		}
		$current_content = '';

		/*
		 * Check if the current theme is using the block editor.
		 * If it is, then we need to get the content from the block templates.
		 */
		if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) { 
			
			// Get the post type from the request.
			$post_type = isset( $_POST['postType'] ) ? sanitize_text_field( $_POST['postType'] ) : '';

			// If a post type is specified, then we need to get the block templates for that post type.
			if ( $post_type ) {
				
				// Get the block templates for the specified post type.
				$current_template = get_block_templates( array( 'slug__in' => array( $post_type ) ) );

				// If block templates were found and contain content, then set the current content.
				if ( ! empty( $current_template ) && is_array( $current_template ) && isset( $current_template[0]->content ) && has_blocks( $current_template[0]->content ) ) {
					$current_content = $current_template[0]->content;
				}
			} 

			// Parse the content to get the blocks.
			$blocks = parse_blocks( $current_content );
			$blocks = $this->get_loop_builder_recursive( $blocks );
			// Get the loop-builder block from the parsed blocks. matching the queryId.
			$loop_builder_block = array_values(
				array_filter(
					$blocks,
					function( $block ) {
						if ( isset( $_POST['security'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'uagb-loop-builder' ) ) {
							return 'uagb/loop-builder' === $block['blockName'] && isset( $block['attrs']['queryId'] ) && isset( $_POST['queryId'] ) && (int) sanitize_text_field( $_POST['queryId'] ) === $block['attrs']['queryId'];
						}
					}
				)
			);
			// If the loop-builder block is found, then set the current content.
			if ( ! $loop_builder_block ) {
				$post_id = (int) ( isset( $_POST['postId'] ) ? sanitize_text_field( $_POST['postId'] ) : '' );
				$post    = get_post( $post_id );
				if ( $post instanceof \WP_Post && ! empty( $post->post_content ) ) {
					$current_content = $post->post_content;
				} else {
					wp_send_json_error( array( 'message' => __( 'Invalid post id', 'spectra-pro' ) ) );
				}
			}
		}//end if 

		// Check if the current content is empty. If it is, this means we couldn't find the loop-builder block
		// in the parsed blocks from the current content. In this case, we need to check if there are any custom
		// layouts created using the Astra theme that contain a loop-builder block.
		if ( empty( $current_content ) && defined( 'ASTRA_ADVANCED_HOOKS_POST_TYPE' ) ) {
			// Query for custom layouts.
			$custom_layouts = new \WP_Query(
				array(
					// Specify the post type of custom layouts.
					'post_type'   => 'astra-advanced-hook',
					// Order by ID for consistency.
					'orderby'     => 'ID',
					// Only get published custom layouts.
					'post_status' => array( 'publish' ),
				)
			);
			// WP_Query object.
			$posts = $custom_layouts->posts; // Access the posts array directly.

			foreach ( $posts as $each_post ) {
				// Check if the current post is a WP_Post and has a post_content.
				if ( $each_post instanceof \WP_Post && ! empty( $each_post->post_content ) ) {
					// Check if the post_content contains a loop-builder block.
					if ( has_block( 'uagb/loop-builder', $each_post->post_content ) ) {
						// Parse the content to get the blocks.
						$blocks                    = parse_blocks( $each_post->post_content );
						$blocks                    = $this->get_loop_builder_recursive( $blocks );
						$is_same_loop_used_on_page = array_filter(
							$blocks,
							function( $block ) {
								// Check if blockName and necessary attributes are set.
								if (
									isset( $block['blockName'], $block['attrs']['queryId'], $_POST['queryId'], $_POST['block_id'] ) &&
									'uagb/loop-builder' === $block['blockName'] &&
									isset( $_POST['security'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'uagb-loop-builder' ) &&
									(int) sanitize_text_field( $_POST['queryId'] ) === $block['attrs']['queryId'] &&
									sanitize_text_field( $_POST['block_id'] ) === $block['attrs']['block_id']
								) {
									return true;
								}

								// if the block does not meet conditions.
								return false;
							}
						);
						if ( ! empty( $is_same_loop_used_on_page ) ) {
							// If the loop-builder block is found, set the current content and exit the loop.
							$current_content = $each_post->post_content;
							break;
						}
					}//end if
				}//end if
			}//end foreach

			// Get current post content in case loop used on it.
			$post_content = $this->get_post_content_by_id( $post_id );

			// Append the post content only if it's not a WP_Error.
			if ( ! is_wp_error( $post_content ) ) {
				$current_content .= $post_content;
			}
		}//end if

		// Check if the current content is empty. If it is, this means we couldn't find the loop-builder block
		// in the parsed blocks from the current content. In this case, we need to check if anywhere in widget area contain a loop-builder block.
		$widget_content = get_option( 'widget_block' );
		if ( ! empty( $widget_content ) && is_array( $widget_content ) && empty( $current_content ) ) {
			foreach ( $widget_content as $key => $value ) {
				// Check if the value is an array and has the "content" key.
				if ( is_array( $value ) && array_key_exists( 'content', $value ) ) {
					$content = $value['content'];

					// Ensure $content is a string and not a WP_Error instance.
					if ( ! is_wp_error( $content ) && is_string( $content ) ) {
						if ( has_block( 'uagb/loop-builder', $content ) ) {
							$current_content .= $content; // Add to the results array.
						}
					}
				}
			}    
		}

		/**
		 * If the current content is empty, we need to check if there is any content
		 * in the post that matches the postId passed in the AJAX request.
		 * If there is no content, we log an error and return an error message.
		 */
		if ( empty( $current_content ) ) {

			// Get the postId from the AJAX request.
			$post_id = (int) ( isset( $_POST['postId'] ) ? sanitize_text_field( $_POST['postId'] ) : '' );

			$current_content = $this->get_post_content_by_id( $post_id );
		} else { // current content is not empty and loop-builder block found on other places as well. e.g loop used in multiple places like widget area and page in that case we need to combine the content.
			// Get the postId from the AJAX request.
			$post_id      = (int) ( isset( $_POST['postId'] ) ? sanitize_text_field( $_POST['postId'] ) : '' );
			$post_content = $this->get_post_content_by_id( $post_id );
			
			if ( ! is_wp_error( $post_content ) && is_string( $post_content ) ) {
				$current_content .= $post_content;
			}
		}
		
		if ( ! is_wp_error( $current_content ) && is_string( $current_content ) ) {
			// Parse the content to get the blocks.
			$blocks = parse_blocks( $current_content );
			$blocks = $this->get_loop_builder_recursive( $blocks );
			
			// Get the loop-builder block from the parsed blocks. matching the queryId.
			$loop_builder_block = array_values(
				array_filter(
					$blocks,
					function( $block ) {
						if ( isset( $_POST['security'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'uagb-loop-builder' ) ) {
							return 'uagb/loop-builder' === $block['blockName'] && isset( $block['attrs']['queryId'] ) && isset( $_POST['queryId'] ) && (int) sanitize_text_field( $_POST['queryId'] ) === $block['attrs']['queryId'] && isset( $_POST['block_id'] ) && sanitize_text_field( $_POST['block_id'] ) === $block['attrs']['block_id'];
						}
					}
				)
			);

			if ( empty( $loop_builder_block ) ) {
				wp_send_json_error( array( 'message' => __( 'Loop builder block not found.', 'spectra-pro' ) ) );
			}

			$attrs = $loop_builder_block[0]['attrs'];
			if ( ! empty( $_POST['buttonFilter'] ) ) { 
				$string = sanitize_text_field( $_POST['buttonFilter'] );
				// Split the string based on the "|" character.
				$parts = explode( '|', stripslashes( $string ) );
				// Trim whitespace from the parts.
				$include_or_exclude = trim( $parts[0] );
				$taxonomy           = trim( $parts[1] );
				$term_id            = trim( $parts[2] );
				$attrs['query']['taxQuery']['include'][ $taxonomy ] = array( sanitize_text_field( $term_id ) );
			}
			if ( ! empty( $_POST['category'] ) ) { 
				$tax = sanitize_text_field( $_POST['category'] );
				// Split the string based on the "|" character.
				$parts = explode( '|', $tax );

				// Trim whitespace from the parts.
				$include_or_exclude = trim( $parts[0] );
				$taxonomy           = trim( $parts[1] );
				$term_id            = trim( $parts[2] );

				$attrs['query']['taxQuery']['include'][ $taxonomy ] = array( sanitize_text_field( $term_id ) );
			} 
			if ( ! empty( $_POST['checkbox'] ) ) {
				$tax = sanitize_text_field( $_POST['checkbox'] );

				// Split the string by the comma (,).
				$array_of_selected_checkbox = explode( ',', $tax );

				// Trim each element to remove extra whitespace.
				$trimmed_array = array_map( 'trim', $array_of_selected_checkbox );

				// Explode each element by the pipe (|) and trim each part.
				$array_of_individual_checkbox = array_map(
					function ( $element ) {
						return array_map( 'trim', explode( '|', $element ) );
					},
					$trimmed_array
				);

				// Initialize an array to store values at index 2.
				$array_of_selected_category = [];
				$taxonomy                   = '';

				// Loop through the final array and collect values at index 2.
				foreach ( $array_of_individual_checkbox as $sub_array ) {
					// Check if index 2 exists in the sub-array.
					$taxonomy = $sub_array[1];
					if ( isset( $sub_array[2] ) ) {
						$array_of_selected_category[] = $sub_array[2];
					}
				}

				$attrs['query']['taxQuery']['include'][ $taxonomy ] = $array_of_selected_category;
			}//end if

			if ( ! empty( $_POST['search'] ) ) {
				$attrs['query']['search'] = sanitize_text_field( $_POST['search'] );
			}

			if ( ! empty( $_POST['sorting'] ) ) {
				$order_array = explode( '|', sanitize_text_field( $_POST['sorting'] ) );
				if ( ! empty( $order_array[0] ) ) {
					$attrs['query']['orderBy'] = $this->get_order_by( $order_array[0] );
				}
				if ( ! empty( $order_array[1] ) ) {
					$attrs['query']['order'] = $this->get_order( $order_array[1] );
				}
			}

			if ( ! empty( $_POST['paged'] ) ) {
				$attrs['query']['paged'] = (int) sanitize_text_field( $_POST['paged'] );
			}
 
			// Get the loop-wrapper block in the loop-builder block.
			$loop_wrapper    = $this->get_nested_block( $loop_builder_block, 'uagb/loop-wrapper' );
			$loop_pagination = $this->get_nested_block( $loop_builder_block, 'uagb/loop-pagination' );
			
			if ( ! empty( $loop_wrapper ) ) {
				$block = new \WP_Block(
					$loop_wrapper,
					$attrs
				);

				$pagination_content = '';
				if ( ! empty( $loop_pagination ) ) {
					$pagination_block   = new \WP_Block(
						$loop_pagination,
						$attrs
					);
					$pagination_content = $this->get_loop_pagination_content( $pagination_block );
				} else {
					$loop_pagination_old_block = $this->get_nested_block( $loop_builder_block, 'uagb/buttons' );
					if ( ! empty( $loop_pagination_old_block ) ) {
						$pagination_block   = new \WP_Block(
							$loop_pagination_old_block,
							$attrs
						);
						$pagination_content = $pagination_block->render();
					}
				}
				$content = []; // Ensure $content starts as an array.
				// Gets the updated loop-wrapper block content as per the filter selected by user.
				$content['wrapper'] = $this->get_loop_wrapper_content( $block );
				if ( empty( $content['wrapper'] ) ) {
					$content['wrapper'] = ! empty( $attrs['noPostFoundMsg'] ) ? $attrs['noPostFoundMsg'] : __( 'No results found!', 'spectra-pro' );
				}
				if ( ! empty( $pagination_content ) ) {
					$content['pagination'] = $pagination_content;
				}

				wp_send_json_success( array( 'content' => $content ) );
			}//end if
		}//end if

		wp_send_json_error( array( 'message' => __( 'Loop wrapper block not found.', 'spectra-pro' ) ) );

	}

	/**
	 * This function search for the loop-wrapper block data inside the loop-builder block and return the object to create WP_Block object.
	 *
	 * @param array  $blocks array of block to check if it contains loop-wrapper blocks.
	 * @param string $block_name name of the block to search.
	 * @since 1.2.0
	 * @return array|null
	 */
	public function get_nested_block( $blocks, $block_name ) {
		foreach ( $blocks as $block ) {

			// If the current block is the loop-wrapper block return the block object.
			if ( $block_name === $block['blockName'] ) {

				// Compatibility for old pagination markup.
				if ( 'uagb/buttons' !== $block_name ) {
					return $block;
				}

				/**
				 * To check if old pagination block has pagination buttons.
				 * We need to check for the innerBlocks ( button-child )
				 * if they have dynamicContent and link field. required for pagination.
				 */
				if ( $this->has_old_pagination_child( $block ) ) {
					return $block;
				}
			}

			// If the current block is not loop-wrapper block and has innerBlocks, search for the loop-wrapper block inside the innerBlocks.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$result = $this->get_nested_block( $block['innerBlocks'], $block_name );
				if ( $result ) {
					return $result;
				}
			}
		}//end foreach

		// If no match is found in the current level or its descendants.
		return null;
	}

	/**
	 * This function gets string value from ajax call and return the order by parameter
	 *
	 * @param string $order_by order by parameter received from ajax query.
	 * @since 1.2.0
	 * @return string
	 */
	public function get_order_by( $order_by ) {
		if ( empty( $order_by ) || ! is_string( $order_by ) ) {
			return 'none';
		}

		switch ( $order_by ) {
			case 'post_title':
				return 'title';
			case 'post_date':
				return 'date';
			case 'post_id':
				return 'ID';
			case 'post_modified':
				return 'modified';
			case 'post_author':
				return 'author';
			default:
				return 'none';
		}
	}

	/**
	 * This function return how to sort query data ASC | DESC
	 * based on the parameter received in the aax call.
	 *
	 * @param string $order order parameter received from ajax query.
	 * @since 1.2.0
	 * @return string
	 */
	public function get_order( $order ) {
		if ( empty( $order ) || ! is_string( $order ) ) {
			return '';
		}

		switch ( $order ) {
			case 'desc':
				return 'DESC';
			case 'asc':
				return 'ASC';
			default:
				return '';
		}
	}

	/**
	 * This function checks if the loop builder has pagination buttons.
	 *
	 * @param array $block button block to be tested if it have dynamic content to be tested for loop builder.
	 * @since 1.2.0
	 * @return bool
	 */
	public function has_old_pagination_child( $block ) {
		if ( ! empty( $block['innerBlocks'] && is_array( $block['innerBlocks'] ) ) ) {
			foreach ( $block['innerBlocks'] as $button_child ) {
				if ( ! empty( $button_child['attrs']['dynamicContent']['link']['field'] ) && is_string( $button_child['attrs']['dynamicContent']['link']['field'] ) ) {
					$field                     = trim( $button_child['attrs']['dynamicContent']['link']['field'] );
					list( $scope, $link_type ) = array_pad( explode( '|', $field ), 2, '' );
					if ( in_array( $link_type, array( 'pagination-prev', 'pagination-numbers', 'pagination-next' ) ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}
}
