<?php
/**
 * Spectra Pro Utilities.
 *
 * @since 1.0.0
 *
 * @package spectra-pro
 */
namespace SpectraPro\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Utils
 *
 * @since 1.0.0
 */
class Utils {

	/**
	 * Get Block CSS url from to assets.
	 *
	 * @param string $file_name  The file name.
	 * @return string            The CSS url.
	 *
	 * @since 1.0.0
	 */
	public static function get_block_css_url( $file_name ) {
		return SPECTRA_PRO_URL . 'assets/css/blocks/' . $file_name . '.css';
	}

	/**
	 * Get CSS url from to assets.
	 *
	 * @param string $file_name  The file name.
	 * @return string            The CSS url.
	 *
	 * @since 1.0.0
	 */
	public static function get_css_url( $file_name ) {
		return SPECTRA_PRO_URL . 'assets/css/' . $file_name . UAGB_CSS_EXT;
	}

	/**
	 * Get JS url from to assets.
	 *
	 * @param string $file_name  The file name.
	 * @return string            The CSS url.
	 *
	 * @since 1.0.0
	 */
	public static function get_js_url( $file_name ) {
		return SPECTRA_PRO_URL . 'assets/js/' . $file_name . UAGB_JS_EXT;
	}

	/**
	 * Adds Google fonts for Search block.
	 *
	 * @param array $attr  The block's attributes.
	 * @return void
	 *
	 * @since 1.2.0
	 */
	public static function blocks_search_gfont( $attr ) {

		$search_load_google_font = isset( $attr['searchLoadGoogleFonts'] ) ? $attr['searchLoadGoogleFonts'] : '';
		$search_font_family      = isset( $attr['searchFontFamily'] ) ? $attr['searchFontFamily'] : '';
		$search_font_weight      = isset( $attr['searchFontWeight'] ) ? $attr['searchFontWeight'] : '';

		\UAGB_Helper::blocks_google_font( $search_load_google_font, $search_font_family, $search_font_weight );
	}

	/**
	 * Adds Google fonts for Sort block.
	 *
	 * @param array $attr  The block's attributes.
	 * @return void
	 *
	 * @since 1.2.0
	 */
	public static function blocks_sort_gfont( $attr ) {

		$sort_load_google_font = isset( $attr['sortLoadGoogleFonts'] ) ? $attr['sortLoadGoogleFonts'] : '';
		$sort_font_family      = isset( $attr['sortFontFamily'] ) ? $attr['sortFontFamily'] : '';
		$sort_font_weight      = isset( $attr['sortFontWeight'] ) ? $attr['sortFontWeight'] : '';

		\UAGB_Helper::blocks_google_font( $sort_load_google_font, $sort_font_family, $sort_font_weight );
	}

	/**
	 * Adds Google fonts for Category block.
	 *
	 * @param array $attr  The block's attributes.
	 * @return void
	 *
	 * @since 1.2.0
	 */
	public static function blocks_loop_category_gfont( $attr ) {

		$cat_load_google_font = isset( $attr['catLoadGoogleFonts'] ) ? $attr['catLoadGoogleFonts'] : '';
		$cat_font_family      = isset( $attr['catFontFamily'] ) ? $attr['catFontFamily'] : '';
		$cat_font_weight      = isset( $attr['catFontWeight'] ) ? $attr['catFontWeight'] : '';

		\UAGB_Helper::blocks_google_font( $cat_load_google_font, $cat_font_family, $cat_font_weight );
	}

	/**
	 * Adds Google fonts for Login block.
	 *
	 * @param array $attr  The block's attributes.
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public static function blocks_login_gfont( $attr ) {

		$label_load_google_font = isset( $attr['labelLoadGoogleFonts'] ) ? $attr['labelLoadGoogleFonts'] : '';
		$label_font_family      = isset( $attr['labelFontFamily'] ) ? $attr['labelFontFamily'] : '';
		$label_font_weight      = isset( $attr['labelFontWeight'] ) ? $attr['labelFontWeight'] : '';

		$fields_load_google_font = isset( $attr['fieldsLoadGoogleFonts'] ) ? $attr['fieldsLoadGoogleFonts'] : '';
		$fields_font_family      = isset( $attr['fieldsFontFamily'] ) ? $attr['fieldsFontFamily'] : '';
		$fields_font_weight      = isset( $attr['fieldsFontWeight'] ) ? $attr['fieldsFontWeight'] : '';

		$login_load_google_font = isset( $attr['loginLoadGoogleFonts'] ) ? $attr['loginLoadGoogleFonts'] : '';
		$login_font_family      = isset( $attr['loginFontFamily'] ) ? $attr['loginFontFamily'] : '';
		$login_font_weight      = isset( $attr['loginFontWeight'] ) ? $attr['loginFontWeight'] : '';

		$register_info_load_google_font = isset( $attr['registerInfoLoadGoogleFonts'] ) ? $attr['registerInfoLoadGoogleFonts'] : '';
		$register_info_font_family      = isset( $attr['registerInfoFontFamily'] ) ? $attr['registerInfoFontFamily'] : '';
		$register_info_font_weight      = isset( $attr['registerInfoFontWeight'] ) ? $attr['registerInfoFontWeight'] : '';

		\UAGB_Helper::blocks_google_font( $label_load_google_font, $label_font_family, $label_font_weight );
		\UAGB_Helper::blocks_google_font( $fields_load_google_font, $fields_font_family, $fields_font_weight );
		\UAGB_Helper::blocks_google_font( $login_load_google_font, $login_font_family, $login_font_weight );
		\UAGB_Helper::blocks_google_font( $register_info_load_google_font, $register_info_font_family, $register_info_font_weight );
	}

	/**
	 * Adds Google fonts for register block.
	 *
	 * @param array $attr  The block's attributes.
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public static function blocks_register_gfont( $attr ) {

		$label_load_google_font = isset( $attr['labelloadGoogleFonts'] ) ? $attr['labelloadGoogleFonts'] : '';
		$label_font_family      = isset( $attr['labelFontFamily'] ) ? $attr['labelFontFamily'] : '';
		$label_font_weight      = isset( $attr['labelFontWeight'] ) ? $attr['labelFontWeight'] : '';

		$input_load_google_font = isset( $attr['inputloadGoogleFonts'] ) ? $attr['inputloadGoogleFonts'] : '';
		$input_font_family      = isset( $attr['inputFontFamily'] ) ? $attr['inputFontFamily'] : '';
		$input_font_weight      = isset( $attr['inputFontWeight'] ) ? $attr['inputFontWeight'] : '';

		$register_button_google_font = isset( $attr['registerBtnloadGoogleFonts'] ) ? $attr['registerBtnloadGoogleFonts'] : '';
		$register_button_font_family = isset( $attr['registerBtnFontFamily'] ) ? $attr['registerBtnFontFamily'] : '';
		$register_button_font_weight = isset( $attr['registerBtnFontWeight'] ) ? $attr['registerBtnFontWeight'] : '';

		$login_info_google_font = isset( $attr['loginInfoLoadGoogleFonts'] ) ? $attr['loginInfoLoadGoogleFonts'] : '';
		$login_info_font_family = isset( $attr['loginInfoFontFamily'] ) ? $attr['loginInfoFontFamily'] : '';
		$login_info_font_weight = isset( $attr['loginInfoFontWeight'] ) ? $attr['loginInfoFontWeight'] : '';

		\UAGB_Helper::blocks_google_font( $label_load_google_font, $label_font_family, $label_font_weight );
		\UAGB_Helper::blocks_google_font( $input_load_google_font, $input_font_family, $input_font_weight );
		\UAGB_Helper::blocks_google_font( $register_button_google_font, $register_button_font_family, $register_button_font_weight );
		\UAGB_Helper::blocks_google_font( $login_info_google_font, $login_info_font_family, $login_info_font_weight );
	}

	/**
	 * Adds Google fonts for the Instagram Feed block.
	 *
	 * @param array $attr  The block's attributes.
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public static function blocks_instagram_feed_gfont( $attr ) {

		$caption_load_google_font = isset( $attr['captionLoadGoogleFonts'] ) ? $attr['captionLoadGoogleFonts'] : '';
		$caption_font_family      = isset( $attr['captionFontFamily'] ) ? $attr['captionFontFamily'] : '';
		$caption_font_weight      = isset( $attr['captionFontWeight'] ) ? $attr['captionFontWeight'] : '';

		$load_more_load_google_font = isset( $attr['loadMoreLoadGoogleFonts'] ) ? $attr['loadMoreLoadGoogleFonts'] : '';
		$load_more_font_family      = isset( $attr['loadMoreFontFamily'] ) ? $attr['loadMoreFontFamily'] : '';
		$load_more_font_weight      = isset( $attr['loadMoreFontWeight'] ) ? $attr['loadMoreFontWeight'] : '';

		\UAGB_Helper::blocks_google_font( $caption_load_google_font, $caption_font_family, $caption_font_weight );
		\UAGB_Helper::blocks_google_font( $load_more_load_google_font, $load_more_font_family, $load_more_font_weight );
	}

	/**
	 * Customize loop builder query for parameters not handled by in-built WordPress function.
	 *
	 * @param \WP_Block $block block object.
	 * @since 1.0.0
	 * @return array
	 */
	public static function customize_block_query( $block ) {
		$query = [];

		if ( isset( $block->context['query']['include'] ) && ! empty( $block->context['query']['include'] && is_array( $block->context['query']['include'] ) ) ) {
			$post_in = array_filter( array_map( 'intval', $block->context['query']['include'] ) );

			if ( ! empty( $block->context['query']['sticky'] ) && 'only' === $block->context['query']['sticky'] ) {
				$sticky            = get_option( 'sticky_posts' );
				$sticky            = is_array( $sticky ) ? $sticky : array();
				$query['post__in'] = ! empty( $sticky ) ? array_intersect( $sticky, $post_in ) : array( 0 );
			} else {
				$query['post__in'] = $post_in;
			}
		}

		if ( isset( $block->context['query']['author'] ) && ! empty( $block->context['query']['author'] && is_array( $block->context['query']['author'] ) ) ) {
			$query['author']     = null;
			$query['author__in'] = array_filter( array_map( 'intval', $block->context['query']['author'] ) );
		}

		if ( isset( $block->context['query']['authorExclude'] ) && ! empty( $block->context['query']['authorExclude'] && is_array( $block->context['query']['authorExclude'] ) ) ) {
			$query['author__not_in'] = array_filter( array_map( 'intval', $block->context['query']['authorExclude'] ) );
		}

		if ( ! isset( $block->context['query']['taxQuery'] ) || empty( $block->context['query']['taxQuery'] || ! is_array( $block->context['query']['taxQuery'] ) ) ) {
			return $query;
		}

		foreach ( $block->context['query']['taxQuery'] as $operator => $tax_query ) {
			if ( empty( $tax_query ) ) {
				continue;
			}

			switch ( $operator ) {
				case 'exclude':
					$tax_query_operator = 'NOT IN';
					break;
				case 'include':
					$tax_query_operator = 'IN';
					break;
				default:
					continue 2;
			}

			foreach ( $tax_query as $taxonomy => $tax_ids ) {
				$temp = [];
				if ( is_taxonomy_viewable( $taxonomy ) && ! empty( $tax_ids ) ) {
					$temp['taxonomy'] = $taxonomy;
					$temp['terms']    = array_filter( array_map( 'intval', $tax_ids ) );
					$temp['operator'] = $tax_query_operator;
				}
				if ( ! empty( $temp ) ) {
					$query['tax_query'][] = $temp;
				}
			}
		}//end foreach

		$query['post_status'] = array( 'publish' );

		// Add tax query to exclude hidden products based on the "catalog_visibility" taxonomy.
		if ( 'product' === $block->context['query']['postType'] ) {
			$query['tax_query'][] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => 'exclude-from-catalog', // Exclude products marked as hidden in catalog.
				'operator' => 'NOT IN',
			);
		}

		return $query;

	}

	/**
	 * Get child categories.
	 *
	 * @param int    $parent_category_id id of parent category.
	 * @param bool   $show_empty_taxonomy show empty taxonomy.
	 * @param string $taxonomyType show taxonomy type.
	 * @since 1.2.0
	 * @return array of child categories.
	 */
	public static function get_child_categories( $parent_category_id, $show_empty_taxonomy, $taxonomyType ) {
		$categories       = array();
		$child_categories = get_categories(
			array(
				'parent'     => $parent_category_id,
				'hide_empty' => ! $show_empty_taxonomy,
				'taxonomy'   => $taxonomyType,
			) 
		);
		
		if ( ! empty( $child_categories ) ) {
			foreach ( $child_categories as $child_category ) {
				$category   = array( $taxonomyType . ':' . $child_category->term_id . ':' . $child_category->name . ':' . $child_category->count );
				$category   = array_merge( $category, Utils::get_child_categories( $child_category->term_id, $show_empty_taxonomy, $taxonomyType ) );
				$categories = array_merge( $categories, $category );
			}
		}
		
		return $categories;
	}
}
