<?php
namespace SpectraPro\BlocksConfig\InstagramFeed;

use SpectraPro\Core\Helper;

/**
 * Spectra Pro Instagram.
 *
 * @package spectra-pro
 *
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Block.
 *
 * @since 1.0.0
 */
class Block {

	/**
	 * Initiator.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		$self = new self();
		add_action( 'init', array( $self, 'register_instagram_feed' ) );
		add_action( 'wp_ajax_spectra_pro_load_instagram_masonry', array( $self, 'render_masonry_pagination' ) );
		add_action( 'wp_ajax_nopriv_spectra_pro_load_instagram_masonry', array( $self, 'render_masonry_pagination' ) );
		add_action( 'wp_ajax_spectra_pro_load_instagram_grid_pagination', array( $self, 'render_grid_pagination' ) );
		add_action( 'wp_ajax_nopriv_spectra_pro_load_instagram_grid_pagination', array( $self, 'render_grid_pagination' ) );
		// Admin Filters for Instagram Transients.
		add_filter( 'uag_instagram_transients', array( $self, 'get_insta_media_transients' ), 10, 1 );
	}

	/**
	 * Registers the `instagram-feed` block on server.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function register_instagram_feed() {
		// Check if the register function exists.
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$arrow_border_attributes      = array();
		$btn_border_attributes        = array();
		$image_border_attributes      = array();
		$main_title_border_attributes = array();

		if ( method_exists( 'UAGB_Block_Helper', 'uag_generate_php_border_attribute' ) ) {
			$arrow_border_attributes      = \UAGB_Block_Helper::uag_generate_php_border_attribute(
				'arrow',
				array(
					'borderStyle'             => 'none',
					'borderTopWidth'          => 1,
					'borderRightWidth'        => 1,
					'borderLeftWidth'         => 1,
					'borderBottomWidth'       => 1,
					'borderTopLeftRadius'     => 50,
					'borderTopRightRadius'    => 50,
					'borderBottomLeftRadius'  => 50,
					'borderBottomRightRadius' => 50,
				)
			);
			$btn_border_attributes        = \UAGB_Block_Helper::uag_generate_php_border_attribute( 'btn' );
			$image_border_attributes      = \UAGB_Block_Helper::uag_generate_php_border_attribute( 'image' );
			$main_title_border_attributes = \UAGB_Block_Helper::uag_generate_php_border_attribute(
				'mainTitle',
				array(
					'borderTopWidth'    => 2,
					'borderRightWidth'  => 0,
					'borderBottomWidth' => 2,
					'borderLeftWidth'   => 0,
				)
			);
		}//end if

		register_block_type(
			'uagb/instagram-feed',
			array(
				'attributes'      => array_merge(
					// Block Requirements.
					array(
						'block_id'     => array(
							'type' => 'string',
						),
						'classMigrate' => array(
							'type'    => 'boolean',
							'default' => false,
						),
					),
					// Editor Requirements.
					array(
						'readyToRender' => array(
							'type'    => 'boolean',
							'default' => false,
						),
						'igUserName'    => array(
							'type'    => 'string',
							'default' => 'Not Defined',
						),
						'igUserID'      => array(
							'type' => 'number',
						),
					),
					// Post Settings.
					array(
						'feedLayout'         => array(
							'type'    => 'string',
							'default' => 'grid',
						),
						'postsTotal'         => array(
							'type'    => 'number',
							'default' => 0,
						),
						'postsMax'           => array(
							'type'    => 'number',
							'default' => 15,
						),
						'postsOffset'        => array(
							'type'    => 'number',
							'default' => 0,
						),
						'postOpenIG'         => array(
							'type'    => 'boolean',
							'default' => true,
						),
						'postDisplayCaption' => array(
							'type'    => 'boolean',
							'default' => true,
						),
					),
					// Caption Settings.
					array(
						'captionVisibility'       => array(
							'type'    => 'string',
							'default' => 'hover',
						),
						'postCaptionLength'       => array(
							'type'    => 'number',
							'default' => 30,
						),
						'captionDisplayType'      => array(
							'type'    => 'string',
							'default' => 'overlay',
						),
						'postCaptionAlignment'    => array(
							'type'    => 'string',
							'default' => 'center center',
						),
						'postCaptionAlignment01'  => array(
							'type'    => 'string',
							'default' => 'center',
						),
						'postCaptionAlignment02'  => array(
							'type'    => 'string',
							'default' => 'center',
						),
						'postDefaultCaption'      => array(
							'type'    => 'string',
							'default' => __( 'No Caption', 'spectra-pro' ),
						),
						'captionPaddingTop'       => array(
							'type'    => 'number',
							'default' => 8,
						),
						'captionPaddingRight'     => array(
							'type'    => 'number',
							'default' => 8,
						),
						'captionPaddingBottom'    => array(
							'type'    => 'number',
							'default' => 8,
						),
						'captionPaddingLeft'      => array(
							'type'    => 'number',
							'default' => 8,
						),
						'captionPaddingTopTab'    => array(
							'type' => 'number',
						),
						'captionPaddingRightTab'  => array(
							'type' => 'number',
						),
						'captionPaddingBottomTab' => array(
							'type' => 'number',
						),
						'captionPaddingLeftTab'   => array(
							'type' => 'number',
						),
						'captionPaddingTopMob'    => array(
							'type' => 'number',
						),
						'captionPaddingRightMob'  => array(
							'type' => 'number',
						),
						'captionPaddingBottomMob' => array(
							'type' => 'number',
						),
						'captionPaddingLeftMob'   => array(
							'type' => 'number',
						),
						'captionPaddingUnit'      => array(
							'type'    => 'string',
							'default' => 'px',
						),
						'captionPaddingUnitTab'   => array(
							'type'    => 'string',
							'default' => 'px',
						),
						'captionPaddingUnitMob'   => array(
							'type'    => 'string',
							'default' => 'px',
						),
						'captionPaddingUnitLink'  => array(
							'type'    => 'boolean',
							'default' => true,
						),
						'captionGap'              => array(
							'type'    => 'number',
							'default' => 0,
						),
						'captionGapUnit'          => array(
							'type'    => 'string',
							'default' => 'px',
						),
					),
					// Layout Settings.
					array(
						'columnsDesk'         => array(
							'type'    => 'number',
							'default' => 3,
						),
						'columnsTab'          => array(
							'type'    => 'number',
							'default' => 3,
						),
						'columnsMob'          => array(
							'type'    => 'number',
							'default' => 3,
						),
						'gridPostGap'         => array(
							'type'    => 'number',
							'default' => 8,
						),
						'gridPostGapTab'      => array(
							'type' => 'number',
						),
						'gridPostGapMob'      => array(
							'type' => 'number',
						),
						'gridPostGapUnit'     => array(
							'type'    => 'string',
							'default' => 'px',
						),
						'gridPostGapUnitTab'  => array(
							'type'    => 'string',
							'default' => 'px',
						),
						'gridPostGapUnitMob'  => array(
							'type'    => 'string',
							'default' => 'px',
						),
						'feedMarginTop'       => array(
							'type' => 'number',
						),
						'feedMarginRight'     => array(
							'type' => 'number',
						),
						'feedMarginBottom'    => array(
							'type' => 'number',
						),
						'feedMarginLeft'      => array(
							'type' => 'number',
						),
						'feedMarginTopTab'    => array(
							'type' => 'number',
						),
						'feedMarginRightTab'  => array(
							'type' => 'number',
						),
						'feedMarginBottomTab' => array(
							'type' => 'number',
						),
						'feedMarginLeftTab'   => array(
							'type' => 'number',
						),
						'feedMarginTopMob'    => array(
							'type' => 'number',
						),
						'feedMarginRightMob'  => array(
							'type' => 'number',
						),
						'feedMarginBottomMob' => array(
							'type' => 'number',
						),
						'feedMarginLeftMob'   => array(
							'type' => 'number',
						),
						'feedMarginUnit'      => array(
							'type'    => 'string',
							'default' => 'px',
						),
						'feedMarginUnitTab'   => array(
							'type'    => 'string',
							'default' => 'px',
						),
						'feedMarginUnitMob'   => array(
							'type'    => 'string',
							'default' => 'px',
						),
						'feedMarginUnitLink'  => array(
							'type'    => 'boolean',
							'default' => true,
						),
					),
					// Layout Specific Settings.
					array(
						'carouselStartAt'         => array(
							'type'    => 'number',
							'default' => 0,
						),
						'carouselSquares'         => array(
							'type'    => 'boolean',
							'default' => false,
						),
						'carouselLoop'            => array(
							'type'    => 'boolean',
							'default' => true,
						),
						'carouselAutoplay'        => array(
							'type'    => 'boolean',
							'default' => true,
						),
						'carouselAutoplaySpeed'   => array(
							'type'    => 'number',
							'default' => 5000,
						),
						'carouselPauseOnHover'    => array(
							'type'    => 'boolean',
							'default' => true,
						),
						'carouselTransitionSpeed' => array(
							'type'    => 'number',
							'default' => 1000,
						),
						'gridPages'               => array(
							'type'    => 'number',
							'default' => 1,
						),
						'gridPageNumber'          => array(
							'type'    => 'number',
							'default' => 1,
						),
					),
					// Pagination Settings.
					array(
						'feedPagination'                 => array(
							'type'    => 'boolean',
							'default' => false,
						),
						'paginateUseArrows'              => array(
							'type'    => 'boolean',
							'default' => true,
						),
						'paginateUseDots'                => array(
							'type'    => 'boolean',
							'default' => true,
						),
						'paginateUseLoader'              => array(
							'type'    => 'boolean',
							'default' => true,
						),
						'paginateLimit'                  => array(
							'type'    => 'number',
							'default' => 9,
						),
						'paginateButtonAlign'            => array(
							'type'    => 'string',
							'default' => 'center',
						),
						'paginateButtonText'             => array(
							'type'    => 'string',
							'default' => __( 'Load More', 'spectra-pro' ),
						),
						'paginateButtonPaddingTop'       => array(
							'type' => 'number',
						),
						'paginateButtonPaddingRight'     => array(
							'type' => 'number',
						),
						'paginateButtonPaddingBottom'    => array(
							'type' => 'number',
						),
						'paginateButtonPaddingLeft'      => array(
							'type' => 'number',
						),
						'paginateButtonPaddingTopTab'    => array(
							'type' => 'number',
						),
						'paginateButtonPaddingRightTab'  => array(
							'type' => 'number',
						),
						'paginateButtonPaddingBottomTab' => array(
							'type' => 'number',
						),
						'paginateButtonPaddingLeftTab'   => array(
							'type' => 'number',
						),
						'paginateButtonPaddingTopMob'    => array(
							'type' => 'number',
						),
						'paginateButtonPaddingRightMob'  => array(
							'type' => 'number',
						),
						'paginateButtonPaddingBottomMob' => array(
							'type' => 'number',
						),
						'paginateButtonPaddingLeftMob'   => array(
							'type' => 'number',
						),
						'paginateButtonPaddingUnit'      => array(
							'type'    => 'string',
							'default' => 'px',
						),
						'paginateButtonPaddingUnitTab'   => array(
							'type'    => 'string',
							'default' => 'px',
						),
						'paginateButtonPaddingUnitMob'   => array(
							'type'    => 'string',
							'default' => 'px',
						),
						'paginateButtonPaddingUnitLink'  => array(
							'type'    => 'boolean',
							'default' => true,
						),
					),
					// Post Styling.
					array(
						'postEnableZoom'                   => array(
							'type'    => 'boolean',
							'default' => true,
						),
						'postZoomType'                     => array(
							'type'    => 'string',
							'default' => 'zoom-in',
						),
						'captionBackgroundEnableBlur'      => array(
							'type'    => 'boolean',
							'default' => false,
						),
						'captionBackgroundBlurAmount'      => array(
							'type'    => 'number',
							'default' => 0,
						),
						'captionBackgroundBlurAmountHover' => array(
							'type'    => 'number',
							'default' => 5,
						),
					),
					// Caption Typography Styling.
					array(
						'captionLoadGoogleFonts' => array(
							'type'    => 'boolean',
							'default' => false,
						),
						'captionFontFamily'      => array(
							'type'    => 'string',
							'default' => 'Default',
						),
						'captionFontWeight'      => array(
							'type' => 'string',
						),
						'captionFontStyle'       => array(
							'type'    => 'string',
							'default' => 'normal',
						),
						'captionTransform'       => array(
							'type' => 'string',
						),
						'captionDecoration'      => array(
							'type'    => 'string',
							'default' => 'none',
						),
						'captionFontSizeType'    => array(
							'type'    => 'string',
							'default' => 'px',
						),
						'captionFontSize'        => array(
							'type' => 'number',
						),
						'captionFontSizeTab'     => array(
							'type' => 'number',
						),
						'captionFontSizeMob'     => array(
							'type' => 'number',
						),
						'captionLineHeightType'  => array(
							'type'    => 'string',
							'default' => 'em',
						),
						'captionLineHeight'      => array(
							'type' => 'number',
						),
						'captionLineHeightTab'   => array(
							'type' => 'number',
						),
						'captionLineHeightMob'   => array(
							'type' => 'number',
						),
					),
					// Pagination Button Typography Styling.
					array(
						'loadMoreLoadGoogleFonts' => array(
							'type'    => 'boolean',
							'default' => false,
						),
						'loadMoreFontFamily'      => array(
							'type'    => 'string',
							'default' => 'Default',
						),
						'loadMoreFontWeight'      => array(
							'type' => 'string',
						),
						'loadMoreFontStyle'       => array(
							'type'    => 'string',
							'default' => 'normal',
						),
						'loadMoreTransform'       => array(
							'type' => 'string',
						),
						'loadMoreDecoration'      => array(
							'type'    => 'string',
							'default' => 'none',
						),
						'loadMoreFontSizeType'    => array(
							'type'    => 'string',
							'default' => 'px',
						),
						'loadMoreFontSize'        => array(
							'type' => 'number',
						),
						'loadMoreFontSizeTab'     => array(
							'type' => 'number',
						),
						'loadMoreFontSizeMob'     => array(
							'type' => 'number',
						),
						'loadMoreLineHeightType'  => array(
							'type'    => 'string',
							'default' => 'em',
						),
						'loadMoreLineHeight'      => array(
							'type' => 'number',
						),
						'loadMoreLineHeightTab'   => array(
							'type' => 'number',
						),
						'loadMoreLineHeightMob'   => array(
							'type' => 'number',
						),
					),
					// Hoverable Styling.
					array(
						'captionBackgroundEffect'       => array(
							'type'    => 'string',
							'default' => 'none',
						),
						'captionBackgroundEffectHover'  => array(
							'type'    => 'string',
							'default' => 'none',
						),
						'captionBackgroundEffectAmount' => array(
							'type'    => 'number',
							'default' => 100,
						),
						'captionBackgroundEffectAmountHover' => array(
							'type'    => 'number',
							'default' => 0,
						),
						'captionColor'                  => array(
							'type'    => 'string',
							'default' => 'rgba(255,255,255,1)',
						),
						'captionColorHover'             => array(
							'type'    => 'string',
							'default' => 'rgba(255,255,255,1)',
						),
						'captionBackgroundColor'        => array(
							'type'    => 'string',
							'default' => 'rgba(0,0,0,0.75)',
						),
						'captionBackgroundColorHover'   => array(
							'type'    => 'string',
							'default' => 'rgba(0,0,0,0.75)',
						),
						'overlayColor'                  => array(
							'type'    => 'string',
							'default' => 'rgba(0,0,0,0)',
						),
						'overlayColorHover'             => array(
							'type'    => 'string',
							'default' => 'rgba(0,0,0,0)',
						),
						'captionSeparateColors'         => array(
							'type'    => 'boolean',
							'default' => false,
						),
					),
					// Pagination Styling.
					array(
						'paginateArrowDistance'        => array(
							'type'    => 'number',
							'default' => -24,
						),
						'paginateArrowDistanceUnit'    => array(
							'type'    => 'string',
							'default' => 'px',
						),
						'paginateArrowSize'            => array(
							'type'    => 'number',
							'default' => 24,
						),
						'paginateDotDistance'          => array(
							'type' => 'number',
						),
						'paginateDotDistanceUnit'      => array(
							'type'    => 'string',
							'default' => 'px',
						),
						'paginateLoaderSize'           => array(
							'type'    => 'number',
							'default' => 18,
						),
						'paginateButtonTextColor'      => array(
							'type' => 'string',
						),
						'paginateButtonTextColorHover' => array(
							'type' => 'string',
						),
						'paginateColor'                => array(
							'type' => 'string',
						),
						'paginateColorHover'           => array(
							'type' => 'string',
						),
					),
					// Box Shadow Styling.
					array(
						'postBoxShadowColor'         => array(
							'type' => 'string',
						),
						'postBoxShadowHOffset'       => array(
							'type'    => 'number',
							'default' => 0,
						),
						'postBoxShadowVOffset'       => array(
							'type'    => 'number',
							'default' => 0,
						),
						'postBoxShadowBlur'          => array(
							'type' => 'number',
						),
						'postBoxShadowSpread'        => array(
							'type' => 'number',
						),
						'postBoxShadowPosition'      => array(
							'type'    => 'string',
							'default' => 'outset',
						),
						'postBoxShadowColorHover'    => array(
							'type' => 'string',
						),
						'postBoxShadowHOffsetHover'  => array(
							'type'    => 'number',
							'default' => 0,
						),
						'postBoxShadowVOffsetHover'  => array(
							'type'    => 'number',
							'default' => 0,
						),
						'postBoxShadowBlurHover'     => array(
							'type' => 'number',
						),
						'postBoxShadowSpreadHover'   => array(
							'type' => 'number',
						),
						'postBoxShadowPositionHover' => array(
							'type'    => 'string',
							'default' => 'outset',
						),
					),
					// Responsive Borders.
					$arrow_border_attributes,
					$btn_border_attributes,
					$image_border_attributes,
					$main_title_border_attributes
				),
				'render_callback' => array( $this, 'render_initial_grid' ),
			)
		);
	}

	/**
	 * Renders All Instagram Posts.
	 *
	 * @param array $attributes  Array of attributes.
	 * @return string|false      The output buffer or false.
	 *
	 * @since 1.0.0
	 */
	public function render_initial_grid( $attributes ) {
		if ( ! $attributes['readyToRender'] ) {
			return false;
		}
		$all_media = '';
		$media     = ( ( 'carousel' !== $attributes['feedLayout'] ) && $attributes['feedPagination'] )
			? $this->fetch_instagram_media( $attributes, 'paginated' )
			: $this->fetch_instagram_media( $attributes, 'full' );

		if ( ! is_array( $media ) ) {
			ob_start();
			?>
			<!-- Spectra - Instagram Account @<?php echo esc_attr( $attributes['igUserName'] ); ?> has been unlinked! -->
			<?php
			return ob_get_clean();
		}

		foreach ( $attributes as $key => $attribute ) {
			$attributes[ $key ] = ( 'false' === $attribute ) ? false : ( ( 'true' === $attribute ) ? true : $attribute );
		}

		$desktop_class = '';
		$tab_class     = '';
		$mob_class     = '';

		$uag_common_selector_class = ''; // Required for z-index.

		if ( array_key_exists( 'UAGHideDesktop', $attributes ) || array_key_exists( 'UAGHideTab', $attributes ) || array_key_exists( 'UAGHideMob', $attributes ) ) {

			$desktop_class = ( isset( $attributes['UAGHideDesktop'] ) ) ? 'uag-hide-desktop' : '';

			$tab_class = ( isset( $attributes['UAGHideTab'] ) ) ? 'uag-hide-tab' : '';

			$mob_class = ( isset( $attributes['UAGHideMob'] ) ) ? 'uag-hide-mob' : '';
		}

		$zindex_desktop = '';
		$zindex_tablet  = '';
		$zindex_mobile  = '';
		$zindex_wrap    = array();

		if ( array_key_exists( 'zIndex', $attributes ) || array_key_exists( 'zIndexTablet', $attributes ) || array_key_exists( 'zIndexMobile', $attributes ) ) {
			$uag_common_selector_class = 'uag-blocks-common-selector';
			$zindex_desktop            = array_key_exists( 'zIndex', $attributes ) && ( '' !== $attributes['zIndex'] ) ? '--z-index-desktop:' . $attributes['zIndex'] . ';' : false;
			$zindex_tablet             = array_key_exists( 'zIndexTablet', $attributes ) && ( '' !== $attributes['zIndexTablet'] ) ? '--z-index-tablet:' . $attributes['zIndexTablet'] . ';' : false;
			$zindex_mobile             = array_key_exists( 'zIndexMobile', $attributes ) && ( '' !== $attributes['zIndexMobile'] ) ? '--z-index-mobile:' . $attributes['zIndexMobile'] . ';' : false;

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

		$wrap = array(
			'wp-block-uagb-instagram-feed',
			'uagb-block-' . $attributes['block_id'],
			( isset( $attributes['className'] ) ) ? $attributes['className'] : '',
			$desktop_class,
			$tab_class,
			$mob_class,
			$uag_common_selector_class,
		);

		$all_media = $this->render_media_markup( $media, $attributes );

		if ( ! $all_media ) {
			return false;
		}

		$grid_page_kses         = wp_kses_allowed_html( 'post' );
		$grid_page_args         = array(
			'div'    => array( 'class' => true ),
			'button' => array(
				'data-role'      => true,
				'class'          => true,
				'aria-label'     => true,
				'tabindex'       => true,
				'data-direction' => true,
				'disabled'       => true,
			),
			'svg'    => array(
				'width'       => true,
				'height'      => true,
				'viewbox'     => true,
				'aria-hidden' => true,
			),
			'path'   => array( 'd' => true ),
			'ul'     => array( 'class' => true ),
			'li'     => array(
				'class'      => true,
				'data-go-to' => true,
			),
		);
		$grid_page_allowed_tags = array_merge( $grid_page_kses, $grid_page_args );

		ob_start();
		?>
			<div
				class="<?php echo esc_attr( implode( ' ', $wrap ) ); ?>"
				style="<?php echo esc_attr( implode( '', $zindex_wrap ) ); ?>"
			>
		<?php
		switch ( $attributes['feedLayout'] ) {
			case 'grid':
				$grid_layout = ( $attributes['feedPagination'] ) ? 'isogrid' : 'grid';
				?>
					<div class="spectra-ig-feed spectra-ig-feed__layout--<?php echo esc_html( $grid_layout ); ?> spectra-ig-feed__layout--<?php echo esc_html( $grid_layout ); ?>-col-<?php echo esc_html( $attributes['columnsDesk'] ); ?> spectra-ig-feed__layout--<?php echo esc_html( $grid_layout ); ?>-col-tab-<?php echo esc_html( $attributes['columnsTab'] ); ?> spectra-ig-feed__layout--<?php echo esc_html( $grid_layout ); ?>-col-mob-<?php echo esc_html( $attributes['columnsMob'] ); ?>">
						<?php echo wp_kses_post( $all_media ); ?>
					</div>
					<?php echo $attributes['feedPagination'] ? wp_kses( $this->render_grid_pagination_controls( $attributes ), $grid_page_allowed_tags ) : ''; ?>
				<?php
				break;
			case 'masonry':
				?>
					<div class="spectra-ig-feed spectra-ig-feed__layout--<?php echo esc_html( $attributes['feedLayout'] ); ?> spectra-ig-feed__layout--<?php echo esc_html( $attributes['feedLayout'] ); ?>-col-<?php echo esc_html( $attributes['columnsDesk'] ); ?> spectra-ig-feed__layout--<?php echo esc_html( $attributes['feedLayout'] ); ?>-col-tab-<?php echo esc_html( $attributes['columnsTab'] ); ?> spectra-ig-feed__layout--<?php echo esc_html( $attributes['feedLayout'] ); ?>-col-mob-<?php echo esc_html( $attributes['columnsMob'] ); ?>">
						<?php echo wp_kses_post( $all_media ); ?>
					</div>
					<?php echo $attributes['feedPagination'] ? wp_kses( $this->render_masonry_pagination_controls( $attributes ), $grid_page_allowed_tags ) : ''; ?>
				<?php
				break;
			case 'carousel':
				?>
					<div class="spectra-ig-feed spectra-ig-feed__layout--<?php echo esc_html( $attributes['feedLayout'] ); ?>">
						<div class="uagb-slick-carousel">
							<?php echo wp_kses_post( $all_media ); ?>
						</div>
					</div>
				<?php
				break;
		}//end switch
		?>
			</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Renders Grid Pagination Controls.
	 *
	 * @param array $attributes  Array of block attributes.
	 * @return string            The output buffer or false.
	 *
	 * @since 1.0.0
	 */
	private function render_grid_pagination_controls( $attributes ) {
		ob_start();
		?>
			<div class="spectra-ig-feed__control-wrapper">
				<button type="button" data-role="none" class="spectra-ig-feed__control-arrows spectra-ig-feed__control-arrows--<?php echo esc_html( $attributes['feedLayout'] ); ?>" aria-label="Prev" tabIndex="0" data-direction="Prev"<?php echo ( 'grid' === $attributes['feedLayout'] && 1 === $attributes['gridPageNumber'] ) ? ' disabled' : ''; ?>>
					<svg width=20 height=20 viewBox="0 0 256 512">
						<path d="M31.7 239l136-136c9.4-9.4 24.6-9.4 33.9 0l22.6 22.6c9.4 9.4 9.4 24.6 0 33.9L127.9 256l96.4 96.4c9.4 9.4 9.4 24.6 0 33.9L201.7 409c-9.4 9.4-24.6 9.4-33.9 0l-136-136c-9.5-9.4-9.5-24.6-.1-34z" />
					</svg>
				</button>
				<ul class="spectra-ig-feed__control-dots">
					<?php
					for ( $i = 0; $i < $attributes['gridPages']; $i++ ) {
						?>
								<li class="spectra-ig-feed__control-dot<?php echo ( ( $attributes['gridPageNumber'] - 1 === $i ) ) ? ' spectra-ig-feed__control-dot--active' : ''; ?>" data-go-to=<?php echo intval( $i + 1 ); ?>>
									<button/>
								</li>
							<?php
					}
					?>
				</ul>
				<button type="button" data-role="none" class="spectra-ig-feed__control-arrows spectra-ig-feed__control-arrows--<?php echo esc_html( $attributes['feedLayout'] ); ?>" aria-label="Next" tabIndex="0" data-direction="Next"<?php echo ( 'grid' === $attributes['feedLayout'] && $attributes['gridPageNumber'] === $attributes['gridPages'] ) ? ' disabled' : ''; ?>>
					<svg width=20 height=20 viewBox="0 0 256 512">
						<path d="M224.3 273l-136 136c-9.4 9.4-24.6 9.4-33.9 0l-22.6-22.6c-9.4-9.4-9.4-24.6 0-33.9l96.4-96.4-96.4-96.4c-9.4-9.4-9.4-24.6 0-33.9L54.3 103c9.4-9.4 24.6-9.4 33.9 0l136 136c9.5 9.4 9.5 24.6.1 34z" />
					</svg>
				</button>
			</div>
		<?php
		$output = ob_get_clean();
		return is_string( $output ) ? $output : '';
	}

	/**
	 * Renders Masonry Pagination Controls.
	 *
	 * @param array $attributes  Array of block attributes.
	 * @return string            The output buffer or false.
	 *
	 * @since 1.0.0
	 */
	private function render_masonry_pagination_controls( $attributes ) {
		ob_start();
		if ( $attributes['paginateUseLoader'] ) {
			?>
				<div class="spectra-ig-feed__control-loader wp-block-button">
					<div class="wp-block-button__link spectra-ig-feed__control-loader--1"></div>
					<div class="wp-block-button__link spectra-ig-feed__control-loader--2"></div>
					<div class="wp-block-button__link spectra-ig-feed__control-loader--3"></div>
				</div>
			<?php
		} else {
			?>
				<div class="spectra-ig-feed__control-wrapper wp-block-button">
					<div class="spectra-ig-feed__control-button wp-block-button__link" aria-label="<?php echo esc_attr( $attributes['paginateButtonText'] ); ?>" tabIndex=0>
						<?php echo esc_html( $attributes['paginateButtonText'] ); ?>
					</div>
				</div>
			<?php
		}
		$output = ob_get_clean();
		return is_string( $output ) ? $output : '';
	}

	/**
	 * Get required attributes for query.
	 *
	 * @param array $attributes  Array of attributes.
	 * @return array             Required query attributes.
	 *
	 * @since 1.0.0
	 */
	public function required_atts( $attributes ) {
		return array(
			'igUserName'     => ( isset( $attributes['igUserName'] ) ) ? sanitize_text_field( $attributes['igUserName'] ) : 'Not Defined',
			'postsTotal'     => ( isset( $attributes['postsTotal'] ) ) ? sanitize_text_field( $attributes['postsTotal'] ) : 0,
			'postsMax'       => ( isset( $attributes['postsMax'] ) ) ? sanitize_text_field( $attributes['postsMax'] ) : 15,
			'postsOffset'    => ( isset( $attributes['postsOffset'] ) ) ? sanitize_text_field( $attributes['postsOffset'] ) : 0,
			'feedPagination' => ( isset( $attributes['feedPagination'] ) ) ? sanitize_text_field( $attributes['feedPagination'] ) : false,
			'gridPages'      => ( isset( $attributes['gridPages'] ) ) ? sanitize_text_field( $attributes['gridPages'] ) : 1,
			'gridPageNumber' => ( isset( $attributes['gridPageNumber'] ) ) ? sanitize_text_field( $attributes['gridPageNumber'] ) : 1,
			'paginateLimit'  => ( isset( $attributes['paginateLimit'] ) ) ? sanitize_text_field( $attributes['paginateLimit'] ) : 9,
		);
	}

	/**
	 * Sends the Posts to Masonry AJAX.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function render_masonry_pagination() {
		check_ajax_referer( 'spectra_pro_instagram_masonry_ajax_nonce', 'nonce' );
		$media_atts = array();
		$attr       = isset( $_POST['attr'] ) ? json_decode( stripslashes( sanitize_text_field( $_POST['attr'] ) ), true ) : false;
		if ( ! is_array( $attr ) ) {
			wp_send_json_error();
		} else {
			$attr['gridPageNumber'] = isset( $_POST['page_number'] ) ? absint( $_POST['page_number'] ) : '';
			$media_atts             = $this->required_atts( $attr );
			$media                  = $this->fetch_instagram_media( $media_atts, 'paginated' );
			if ( ! is_array( $media ) ) {
				wp_send_json_error();
			}

			foreach ( $attr as $key => $attribute ) {
				$attr[ $key ] = ( 'false' === $attribute ) ? false : ( ( 'true' === $attribute ) ? true : $attribute );
			}

			$html_array = $this->render_media_markup( $media, $attr );

			if ( ! $html_array ) {
				wp_send_json_error();
			}

			wp_send_json_success( $html_array );
		}//end if
	}

	/**
	 * Sends the Posts to Grid AJAX.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function render_grid_pagination() {
		check_ajax_referer( 'spectra_pro_instagram_grid_pagination_ajax_nonce', 'nonce' );
		$media_atts = array();
		$attr       = isset( $_POST['attr'] ) ? json_decode( stripslashes( sanitize_text_field( $_POST['attr'] ) ), true ) : false;
		if ( ! is_array( $attr ) ) {
			wp_send_json_error();
		} else {
			$attr['gridPageNumber'] = isset( $_POST['page_number'] ) ? absint( $_POST['page_number'] ) : '';
			$media_atts             = $this->required_atts( $attr );
			$media                  = $this->fetch_instagram_media( $media_atts, 'paginated' );
			if ( ! is_array( $media ) ) {
				wp_send_json_error();
			}
			foreach ( $attr as $key => $attribute ) {
				$attr[ $key ] = ( 'false' === $attribute ) ? false : ( ( 'true' === $attribute ) ? true : $attribute );
			}

			$html_array = $this->render_media_markup( $media, $attr );

			if ( ! $html_array ) {
				wp_send_json_error();
			}

			wp_send_json_success( $html_array );
		}//end if
	}

	/**
	 * Render Media HTML for All Instagram Posts.
	 *
	 * @param array|false $media        Part of User's Media Transient, or false if unable to fetch media.
	 * @param array       $attributes   Array of block attributes.
	 * @return string|false             The output buffer or false.
	 *
	 * @since 1.0.0
	 */
	private function render_media_markup( $media, $attributes ) {
		if ( ! $media ) {
			return false;
		}
		$total_media = count( $media );
		ob_start();
		for ( $i = 0; $i < $total_media; $i++ ) {
			$decoded_media = json_decode( $media[ $i ] );
			if ( is_object( $decoded_media ) ) {
				$this->render_single_media( $decoded_media, $attributes );
			}
		}
		return ob_get_clean();
	}

	/**
	 * Renders an Individual Instagram Post with All Wrappers.
	 *
	 * @param object $media_obj  A Single Instagram Post.
	 * @param array  $atts       Array of block attributes.
	 * @return void
	 *
	 * @since 1.0.0
	 */
	private function render_single_media( $media_obj, $atts ) {
		?>
		<div class='spectra-ig-feed__media-wrapper' >
			<?php
				$atts['postOpenIG']
					? $this->render_media_link( $media_obj, $atts )
					: $this->render_media_thumbnail( $media_obj, $atts );
			?>
		</div>
		<?php
	}

	/**
	 * Render a Link Wrapper for the Instagram Post.
	 *
	 * @param object $media_obj  A Single Instagram Post.
	 * @param array  $atts       Array of block attributes.
	 * @return void
	 *
	 * @since 1.0.0
	 */
	private function render_media_link( $media_obj, $atts ) {
		?>
		<a
		<?php
		echo property_exists( $media_obj, 'permalink' )
			? 'href="' . esc_url( $media_obj->permalink ) . '"'
			: 'class="spectra-ig-feed__media--flagged"';
		?>
			target="_blank" rel="noopener noreferrer" >
			<?php $this->render_media_thumbnail( $media_obj, $atts ); ?>
		</a>
		<?php
	}

	/**
	 * Render the Thumbnail of the Instagram Post.
	 *
	 * @param object $media_obj  A Single Instagram Post.
	 * @param array  $atts       Array of block attributes.
	 * @return void
	 *
	 * @since 1.0.0
	 */
	private function render_media_thumbnail( $media_obj, $atts ) {
		$ig_post_id            = property_exists( $media_obj, 'id' ) ? $media_obj->id : wp_rand( 10000000, 99999999 );
		$ig_post_is_video      = ( property_exists( $media_obj, 'media_type' ) && ( 'VIDEO' === $media_obj->media_type ) ) ? true : false;
		$ig_post_url           = property_exists( $media_obj, 'media_url' ) ? $media_obj->media_url : '';
		$ig_post_thumbnail_url = property_exists( $media_obj, 'thumbnail_url' ) ? $media_obj->thumbnail_url : '';
		if ( 'bar-outside' === $atts['captionDisplayType'] && ( ( 'top' === \UAGB_Block_Helper::get_matrix_alignment( $atts['postCaptionAlignment'], 1 ) ) && $atts['postDisplayCaption'] ) ) {
			?>
			<div class="spectra-ig-feed__media-thumbnail-caption-wrapper spectra-ig-feed__media-thumbnail-caption-wrapper--<?php echo esc_html( $atts['captionDisplayType'] ); ?>">
				<?php $this->render_media_caption( $media_obj, $atts ); ?>
			</div>
			<?php
		}
		?>
		<div class="spectra-ig-feed__media spectra-ig-feed__media--<?php echo esc_html( $atts['feedLayout'] ); ?>">
			<img class="spectra-ig-feed__media-thumbnail spectra-ig-feed__media-thumbnail--<?php echo esc_html( $atts['feedLayout'] ); ?>" src="
					<?php
						echo $ig_post_is_video ? esc_url( $ig_post_thumbnail_url ) : esc_url( $ig_post_url );
					?>
				" alt="<?php echo esc_attr( $atts['igUserName'] . '_post_' . $ig_post_id ); ?>"/>
			<div class="spectra-ig-feed__media-thumbnail-blurrer"></div>
			<?php
			if ( $atts['postDisplayCaption'] ) {
				if ( 'bar-outside' !== $atts['captionDisplayType'] ) {
					?>
						<div class="spectra-ig-feed__media-thumbnail-caption-wrapper spectra-ig-feed__media-thumbnail-caption-wrapper--<?php echo esc_html( $atts['captionDisplayType'] ); ?>">
						<?php $this->render_media_caption( $media_obj, $atts ); ?>
						</div>
						<?php
				}
			} else {
				?>
					<div class="spectra-ig-feed__media-thumbnail-caption-wrapper spectra-ig-feed__media-thumbnail-caption-wrapper--overlay"></div>
				<?php
			}
			?>
		</div>
		<?php
		if ( 'bar-outside' === $atts['captionDisplayType'] && ( ( 'top' !== \UAGB_Block_Helper::get_matrix_alignment( $atts['postCaptionAlignment'], 1 ) ) && $atts['postDisplayCaption'] ) ) {
			?>
			<div class="spectra-ig-feed__media-thumbnail-caption-wrapper spectra-ig-feed__media-thumbnail-caption-wrapper--<?php echo esc_html( $atts['captionDisplayType'] ); ?>">
				<?php $this->render_media_caption( $media_obj, $atts ); ?>
			</div>
			<?php
		}
	}

	/**
	 * Set the Instagram Post Caption.
	 *
	 * @param object $media_obj  A Single Instagram Post.
	 * @param array  $atts       Array of block attributes.
	 * @since 1.0.1
	 * @return string
	 */
	private function assign_insta_caption( $media_obj, $atts ) {
		
		if ( property_exists( $media_obj, 'permalink' ) ) {
			return $atts['postOpenIG'] ? (
				__( 'Click to view post', 'spectra-pro' )
			) : (
				$atts['postDefaultCaption']
			);
		}

		return (
			__( 'Contains Copyrighted Material', 'spectra-pro' )
		);
	}

	/**
	 * Render the Instagram Post Caption.
	 *
	 * @param object $media_obj  A Single Instagram Post.
	 * @param array  $atts       Array of block attributes.
	 * @since 1.0.0
	 * @return void
	 */
	private function render_media_caption( $media_obj, $atts ) {
		?>
			<div class="spectra-ig-feed__media-thumbnail-caption spectra-ig-feed__media-thumbnail-caption--<?php echo esc_html( $atts['captionDisplayType'] ); ?>">
				<?php
				if ( isset( $media_obj->caption ) && is_string( $media_obj->caption ) ) {
					$cropped_caption = Helper::trim_text_to_fully_visible_word( $media_obj->caption, $atts['postCaptionLength'] );
					echo wp_kses_post( $cropped_caption );
				} else {
					echo wp_kses_post( $this->assign_insta_caption( $media_obj, $atts ) );
				}
				?>
			</div>
		<?php
	}

	/**
	 * Render Instagram Media.
	 *
	 * @param array  $attributes  Array of block attributes.
	 * @param string $fetch_type  The generalized number images to fetch.
	 * @return array|false        The required media, or false if unable to fetch media.
	 *
	 * @since 1.0.0
	 */
	private static function fetch_instagram_media( $attributes, $fetch_type ) {
		// fetch type could be - paginated | full.
		$media_required = array();
		switch ( $fetch_type ) {
			case 'paginated':
				if ( isset( $attributes['igUserName'] ) && isset( $attributes['postsMax'] ) && isset( $attributes['feedPagination'] ) && isset( $attributes['gridPages'] ) && isset( $attributes['gridPageNumber'] ) && isset( $attributes['paginateLimit'] ) && $attributes['feedPagination'] && 'Not Defined' !== $attributes['igUserName'] ) {
					$user_media = self::get_insta_media_transients( $attributes['igUserName'] );
					if ( ! is_array( $user_media ) ) {
						return false;
					}
					if ( ! isset( $attributes['postsTotal'] ) || 0 === $attributes['postsTotal'] ) {
						$attributes['postsTotal'] = count( $user_media );
					}
					// Limit the media based on Post Max.
					$post_offset = isset( $attributes['postsOffset'] ) ? $attributes['postsOffset'] : 0;
					$max_posts   = ( is_numeric( $attributes['postsMax'] ) && $attributes['postsMax'] > 0 ) ? min( $attributes['postsMax'], $attributes['postsTotal'] ) : $attributes['postsTotal'];
					$user_media  = array_slice( $user_media, 0, ( $post_offset + $max_posts ), true );
					$media_index = $attributes['postsOffset'] + ( ( $attributes['gridPageNumber'] - 1 ) * $attributes['paginateLimit'] );
					for ( $i = 0; $i < $attributes['paginateLimit']; $i++ ) {
						if ( array_key_exists( $media_index + $i, $user_media ) ) {
							array_push( $media_required, $user_media[ $media_index + $i ] );
						}
					}
				}
				break;
			case 'full':
				if ( isset( $attributes['igUserName'] ) && isset( $attributes['postsMax'] ) && isset( $attributes['postsOffset'] ) && 'Not Defined' !== $attributes['igUserName'] ) {
					$user_media = self::get_insta_media_transients( $attributes['igUserName'] );
					if ( ! is_array( $user_media ) ) {
						return false;
					}
					if ( ! isset( $attributes['postsTotal'] ) || 0 === $attributes['postsTotal'] ) {
						$attributes['postsTotal'] = count( $user_media );
					}
					$media_index = $attributes['postsOffset'] ? $attributes['postsOffset'] : 0;
					for ( $i = 0; $i < $attributes['postsMax']; $i++ ) {
						if ( array_key_exists( $media_index + $i, $user_media ) ) {
							array_push( $media_required, $user_media[ $media_index + $i ] );
						}
					}
				}
				break;
		}//end switch
		return $media_required;
	}

	/**
	 * Render Front-end Masonry Layout.
	 *
	 * @param string $id        The ID of the current block.
	 * @param array  $attr      Array of block attributes.
	 * @param string $selector  The selector for the current block.
	 * @return string|false     The output buffer of the JS Script or false.
	 *
	 * @since 1.0.0
	 */
	public static function render_frontend_masonry_layout( $id, $attr, $selector ) {
		ob_start();
		?>
			window.addEventListener( 'DOMContentLoaded', function() {
				const scope = document.querySelector( '.uagb-block-<?php echo esc_html( $id ); ?>' );
				if ( scope ){
					if ( scope.children[0].classList.contains( 'spectra-ig-feed__layout--masonry' ) ) {
						const element = scope.querySelector( '.spectra-ig-feed__layout--masonry' );
						const isotope = new Isotope( element, {
							itemSelector: '.spectra-ig-feed__media-wrapper',
						} );
						imagesLoaded( element ).on( 'progress', function() {
							isotope.layout();
						});
						imagesLoaded( element ).on( 'always', function() {
							element.parentNode.style.visibility = 'visible';
						});
					}
					window.SpectraInstagramMasonry.init( <?php echo wp_json_encode( $attr ); ?>, '<?php echo esc_attr( $selector ); ?>' );
				}
			});
		<?php
		return ob_get_clean();
	}

	/**
	 * Render Front-end Grid Pagination Layout.
	 *
	 * @param string $id        The ID of the current block.
	 * @param array  $attr      Array of block attributes.
	 * @param string $selector  The selector for the current block.
	 * @return string|false     The output buffer of the JS Script or false.
	 *
	 * @since 1.0.0
	 */
	public static function render_frontend_grid_pagination( $id, $attr, $selector ) {
		ob_start();
		?>
			window.addEventListener( 'DOMContentLoaded', function() {
				const scope = document.querySelector( '.uagb-block-<?php echo esc_html( $id ); ?>' );
				if ( scope ){
					if ( scope.children[0].classList.contains( 'spectra-ig-feed__layout--isogrid' ) ) {
						const element = scope.querySelector( '.spectra-ig-feed__layout--isogrid' );
						const isotope = new Isotope( element, {
							itemSelector: '.spectra-ig-feed__media-wrapper',
							layoutMode: 'fitRows',
						} );
						imagesLoaded( element ).on( 'progress', function() {
							isotope.layout();
						});
					}
					window.SpectraInstagramPagedGrid.init( <?php echo wp_json_encode( $attr ); ?>, '<?php echo esc_attr( $selector ); ?>' );
				}
			});
		<?php
		return ob_get_clean();
	}

	/**
	 * Render Front-end Carousel Layout.
	 *
	 * @param string       $id        The ID of the current block.
	 * @param string|false $settings  The JSON encoded settings or false.
	 * @param string       $selector  The selector for the current block.
	 * @return string                 The jQuery string needed for Slick Slider configuration.
	 *
	 * @since 1.0.0
	 */
	public static function render_frontend_carousel_layout( $id, $settings, $selector ) {
		ob_start();
		?>
			jQuery( document ).ready( function() {
				const scope = document.querySelector( '.uagb-block-<?php echo esc_html( $id ); ?>' );
				if ( scope ){
					if ( scope.children[0].classList.contains( 'spectra-ig-feed__layout--carousel' ) ) {
						const carousel = scope.children[0];
						const dots = carousel.children[0].querySelector( '.slick-dots' );
						if( dots ){
							carousel.style.marginBottom = jQuery( ".slick-dots" ).height() + "px";
						}
					}
				}
			} );
		<?php
		$buffer = ob_get_clean();
		return 'jQuery( document ).ready( function() { if( jQuery( "' . $selector . '" ).length > 0 ){ jQuery( ".wp-block-uagb-instagram-feed' . $selector . '" ).css( "visibility", "visible" ).find( ".uagb-slick-carousel" ).slick( ' . $settings . ' ); } } );' . $buffer;
	}

	/**
	 * Set Instagram Transient.
	 *
	 * @param string|null $specific_user  The Specific User to Refresh, else Refresh All.
	 * @return array                      The Array Transient, or an empty array on failure.
	 *
	 * @since 1.0.0
	 */
	public static function get_insta_media_transients( $specific_user ) {
		if ( $specific_user ) {
			$linked_users = \UAGB_Admin_Helper::get_admin_settings_option( 'uag_insta_linked_accounts', array() );
			if ( ! is_array( $linked_users ) || empty( $linked_users ) ) {
				return array();
			}

			$cur_user = null;
			foreach ( $linked_users as $user ) {
				if ( $user['userName'] === $specific_user ) {
					$cur_user = $user;
					break;
				}
			}
			if ( ! $cur_user ) {
				return array();
			}
			// If the user is newly linked, delete their previous transient if any.
			if ( 'new' === $cur_user['isCurrentlyActive'] ) {
				delete_transient( 'spectra_ig_posts_of_' . $cur_user['userName'] );
			}
			self::refresh_user_token( $cur_user );
			$cur_user_media = array();
			$transient_name = 'spectra_ig_posts_of_' . $cur_user['userName'];
			$media_fetched  = get_transient( $transient_name );
			if ( false === $media_fetched ) {
				$media_fetched = wp_remote_get( 'https://graph.instagram.com/' . $cur_user['userID'] . '/media?fields=caption,id,media_type,media_url,permalink,thumbnail_url,timestamp&access_token=' . $cur_user['token'] );
				if ( ! is_wp_error( $media_fetched ) && is_array( $media_fetched ) ) {
					$cur_user_media = self::get_parsed_insta_media( $media_fetched, $cur_user['token'] );
					// The Next Line is Ignored in PHPStan, since this WP Constant is defined.
					$transient_expiry = HOUR_IN_SECONDS; // @phpstan-ignore-line
					set_transient( $transient_name, $cur_user_media, $transient_expiry );
				} else {
					return array();
				}
			}
			$insta_user_transients = get_transient( $transient_name );
			return ( is_array( $insta_user_transients ) ) ? $insta_user_transients : array();
		} else {
			$linked_users = \UAGB_Admin_Helper::get_admin_settings_option( 'uag_insta_linked_accounts', array() );
			if ( ! is_array( $linked_users ) || empty( $linked_users ) ) {
				return array();
			}

			$insta_user_transients = array();

			// Set all transients for new users ( if any ) and refresh expired transients.
			foreach ( $linked_users as $user ) {
				if ( ! $user['isCurrentlyActive'] ) {
					continue;
				}
				// If the user is newly linked, delete their previous transient if any.
				if ( 'new' === $user['isCurrentlyActive'] ) {
					delete_transient( 'spectra_ig_posts_of_' . $user['userName'] );
				}
				self::refresh_user_token( $user );

				$cur_user_media = array();
				$transient_name = 'spectra_ig_posts_of_' . $user['userName'];
				$media_fetched  = get_transient( $transient_name );
				if ( false === $media_fetched ) {
					$media_fetched = wp_remote_get( 'https://graph.instagram.com/' . $user['userID'] . '/media?fields=caption,id,media_type,media_url,permalink,thumbnail_url,timestamp&access_token=' . $user['token'] );
					if ( ! is_wp_error( $media_fetched ) && is_array( $media_fetched ) ) {
						$cur_user_media = self::get_parsed_insta_media( $media_fetched, $user['token'] );
						// The Next Line is Ignored in PHPStan, since this WP Constant is defined.
						$transient_expiry = HOUR_IN_SECONDS; // @phpstan-ignore-line
						set_transient( $transient_name, $cur_user_media, $transient_expiry );
					} else {
						return array();
					}
				}
				$insta_user_transients[ $user['userName'] ] = get_transient( $transient_name );
			}//end foreach
			\UAGB_Admin_Helper::update_admin_settings_option( 'uag_insta_all_users_media', $insta_user_transients );
			return $insta_user_transients;
		}//end if
	}

	/**
	 * Get the Parsed Instagram Media.
	 *
	 * @param array  $fetched_media   The Fetched Media.
	 * @param string $the_user_token  The User Token.
	 * @return array                  The Built Media Array.
	 *
	 * @since 1.0.0
	 */
	private static function get_parsed_insta_media( $fetched_media, $the_user_token ) {
		$built_media_objects = array();
		do {
			$there_is_more = false;
			if ( ! is_array( $fetched_media ) ) {
				break;
			}
			$fetched_media = json_decode( $fetched_media['body'], true );
			if ( ! is_array( $fetched_media ) ) {
				break;
			}
			if ( isset( $fetched_media['data'] ) ) {
				foreach ( $fetched_media['data'] as $mediaObject ) {
					if ( 'CAROUSEL_ALBUM' === $mediaObject['media_type'] ) {
						$fetchedChildren = wp_remote_get( 'https://graph.instagram.com/' . $mediaObject['id'] . '/children?fields=id,media_type,media_url,permalink,thumbnail_url&access_token=' . $the_user_token );
						if ( ! is_wp_error( $fetchedChildren ) && is_array( $fetchedChildren ) ) {
							$mediaObject['collection'] = self::get_parsed_insta_media( $fetchedChildren, $the_user_token );
						} else {
							$mediaObject['collection'] = $fetchedChildren;
						}
					}
					array_push( $built_media_objects, wp_json_encode( $mediaObject ) );
				}
			}
			if ( isset( $fetched_media['paging']['next'] ) && is_string( $fetched_media['paging']['next'] ) ) {
				$there_is_more = true;
				$fetched_media = wp_remote_get( $fetched_media['paging']['next'] );
			}
		} while ( $there_is_more );
		return $built_media_objects;
	}

	/**
	 * Refresh the Tokens of all Linked Instagram Accounts.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public static function refresh_all_instagram_users() {
		$all_users = \UAGB_Admin_Helper::get_admin_settings_option( 'uag_insta_linked_accounts', array() );
		if ( ! is_array( $all_users ) || empty( $all_users ) ) {
			return;
		}

		foreach ( $all_users as $user ) {
			self::refresh_user_token( $user );
		}
	}

	/**
	 * Refresh the Current Instagram User's Token.
	 *
	 * @param array $the_user  The User Object.
	 * @return void
	 *
	 * @since 1.0.0
	 */
	private static function refresh_user_token( $the_user ) {
		if ( ! is_array( $the_user ) || empty( $the_user ) ) {
			return;
		}

		$refresh_link = wp_remote_get( 'https://graph.instagram.com/refresh_access_token?grant_type=ig_refresh_token&access_token=' . $the_user['token'] );
		if ( ! is_array( $refresh_link ) ) {
			return;
		}

		$all_users = \UAGB_Admin_Helper::get_admin_settings_option( 'uag_insta_linked_accounts', array() );
		if ( ! is_array( $all_users ) || empty( $all_users ) ) {
			return;
		}

		$total_users          = count( $all_users );
		$user_details_updated = false;

		for ( $i = 0; $i < $total_users; $i++ ) {
			if ( $all_users[ $i ]['userName'] !== $the_user['userName'] ) {
				continue;
			}
			if ( 'new' === $the_user['isCurrentlyActive'] ) {
				$all_users[ $i ]['isCurrentlyActive'] = true;
				$user_details_updated                 = true;
			}
			$data = json_decode( $refresh_link['body'], true );
			if ( ! is_array( $data ) ) {
				return;
			}
			if ( isset( $data['error'] ) ) {
				$all_users[ $i ]['isCurrentlyActive'] = false;
				$user_details_updated                 = true;
				break;
			} elseif ( isset( $data['expires_in'] ) ) {
				$cur_date        = date_create( gmdate( 'Y-m-d' ) );
				$expiry_interval = date_interval_create_from_date_string( $data['expires_in'] . ' seconds' );
				if ( ! $cur_date || ! $expiry_interval ) {
					break;
				}
				date_add( $cur_date, $expiry_interval );
				$all_users[ $i ]['expiryDate'] = date_format( $cur_date, 'Y-m-d' );
				$user_details_updated          = true;
				break;
			}
		}//end for
		if ( $user_details_updated ) {
			\UAGB_Admin_Helper::update_admin_settings_option( 'uag_insta_linked_accounts', $all_users );
		}
	}
}//end class
