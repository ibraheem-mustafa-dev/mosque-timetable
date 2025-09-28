<?php
/**
 * Frontend CSS & Google Fonts loading File.
 *
 * @since 1.0.0
 *
 * @package spectra-pro
 */

/**
 * Adding this comment to avoid PHPStan errors of undefined variable as these variables are defined else where.
 *
 * @var mixed[] $attr
 * @var int $id
 */

SpectraPro\Core\Utils::blocks_instagram_feed_gfont( $attr );


// Arrow & Dots Default Color Fallback ( Not from Theme ).
$arrow_dot_color = $attr['paginateColor'] ? $attr['paginateColor'] : '#007cba';

// Block Visibility Based on Layout Type.
$hide_this_block = in_array( $attr['feedLayout'], array( 'carousel', 'masonry' ), true );

// Range Fallback.
$paginate_dot_distance_fallback = is_numeric( $attr['paginateDotDistance'] ) ? $attr['paginateDotDistance'] : 0;

// Responsive Slider Fallback.
$grid_post_gap_tablet_fallback = is_numeric( $attr['gridPostGapTab'] ) ? $attr['gridPostGapTab'] : $attr['gridPostGap'];
$grid_post_gap_mobile_fallback = is_numeric( $attr['gridPostGapMob'] ) ? $attr['gridPostGapMob'] : $grid_post_gap_tablet_fallback;

// Border Attributes.
$arrow_border_css             = UAGB_Block_Helper::uag_generate_border_css( $attr, 'arrow' );
$arrow_border_css_tablet      = UAGB_Block_Helper::uag_generate_border_css( $attr, 'arrow', 'tablet' );
$arrow_border_css_mobile      = UAGB_Block_Helper::uag_generate_border_css( $attr, 'arrow', 'mobile' );
$btn_border_css               = UAGB_Block_Helper::uag_generate_border_css( $attr, 'btn' );
$btn_border_css_tablet        = UAGB_Block_Helper::uag_generate_border_css( $attr, 'btn', 'tablet' );
$btn_border_css_mobile        = UAGB_Block_Helper::uag_generate_border_css( $attr, 'btn', 'mobile' );
$image_border_css             = UAGB_Block_Helper::uag_generate_border_css( $attr, 'image' );
$image_border_css_tablet      = UAGB_Block_Helper::uag_generate_border_css( $attr, 'image', 'tablet' );
$image_border_css_mobile      = UAGB_Block_Helper::uag_generate_border_css( $attr, 'image', 'mobile' );
$main_title_border_css        = UAGB_Block_Helper::uag_generate_border_css( $attr, 'mainTitle' );
$main_title_border_css_tablet = UAGB_Block_Helper::uag_generate_border_css( $attr, 'mainTitle', 'tablet' );
$main_title_border_css_mobile = UAGB_Block_Helper::uag_generate_border_css( $attr, 'mainTitle', 'mobile' );


// Box Shadow CSS.
$post_box_shadow_css       = (
	UAGB_Helper::get_css_value( $attr['postBoxShadowHOffset'], 'px' )
) . ' ' . (
	UAGB_Helper::get_css_value( $attr['postBoxShadowVOffset'], 'px' )
) . ' ' . (
	UAGB_Helper::get_css_value( $attr['postBoxShadowBlur'], 'px' )
) . ' ' . (
	UAGB_Helper::get_css_value( $attr['postBoxShadowSpread'], 'px' )
) . (
	$attr['postBoxShadowColor'] ? ( ' ' . $attr['postBoxShadowColor'] ) : ''
) . ' ' . (
	( 'inset' === $attr['postBoxShadowPosition'] ) ? ( ' ' . $attr['postBoxShadowPosition'] ) : ''
);
$post_box_shadow_hover_css = (
	UAGB_Helper::get_css_value( $attr['postBoxShadowHOffsetHover'], 'px' )
) . ' ' . (
	UAGB_Helper::get_css_value( $attr['postBoxShadowVOffsetHover'], 'px' )
) . ' ' . (
	UAGB_Helper::get_css_value( $attr['postBoxShadowBlurHover'], 'px' )
) . ' ' . (
	UAGB_Helper::get_css_value( $attr['postBoxShadowSpreadHover'], 'px' )
) . (
	$attr['postBoxShadowColorHover'] ? ( ' ' . $attr['postBoxShadowColorHover'] ) : ''
) . ' ' . (
	( 'inset' === $attr['postBoxShadowPositionHover'] ) ? ( ' ' . $attr['postBoxShadowPositionHover'] ) : ''
);

$selectors = array(

	// Feed Selectors.
	'.wp-block-uagb-instagram-feed'                       => array(
		'padding'    => UAGB_Block_Helper::generate_spacing(
			$attr['feedMarginUnit'],
			$attr['feedMarginTop'],
			$attr['feedMarginRight'],
			$attr['feedMarginBottom'],
			$attr['feedMarginLeft']
		),
		'visibility' => $hide_this_block ? 'hidden' : '',
	),

	// Control Settings.

	' .spectra-ig-feed__control-arrows svg'               => array(
		'fill' => $arrow_dot_color,
	),
	' .spectra-ig-feed__control-arrows svg:hover'         => array(
		'fill' => $attr['paginateColorHover'],
	),
	// If the default border setting is selected, replace this with Border None to overwrite the UAG Slick Arrow Border Styling.
	' .spectra-ig-feed__control-arrows--carousel'         => $arrow_border_css ? $arrow_border_css : array(
		'border' => 'none',
	),
	' .spectra-ig-feed__control-arrows--carousel:hover'   => array(
		'border-color' => $attr['arrowBorderHColor'],
	),
	' .spectra-ig-feed__control-arrows--carousel.slick-prev' => array(
		'left' => UAGB_Helper::get_css_value(
			$attr['paginateArrowDistance'],
			$attr['paginateArrowDistanceUnit']
		),
	),
	' .spectra-ig-feed__control-arrows--carousel.slick-next' => array(
		'right' => UAGB_Helper::get_css_value(
			$attr['paginateArrowDistance'],
			$attr['paginateArrowDistanceUnit']
		),
	),
	' .spectra-ig-feed__layout--carousel ul.slick-dots'   => array(
		'top' => 'calc(-10px + ' . UAGB_Helper::get_css_value( $paginate_dot_distance_fallback, 'px' ) . ')',
	),
	' .spectra-ig-feed__layout--carousel ul.slick-dots li button:before' => array(
		'color' => $arrow_dot_color,
	),
	' .spectra-ig-feed__layout--carousel ul.slick-dots li button:hover:before' => array(
		'color' => $attr['paginateColorHover'],
	),
	' .spectra-ig-feed__control-dots li button::before'   => array(
		'color' => $arrow_dot_color,
	),
	' .spectra-ig-feed__control-dots li button:hover::before' => array(
		'color' => $attr['paginateColorHover'],
	),
	' .spectra-ig-feed__control-loader'                   => array(
		'margin-top' => UAGB_Helper::get_css_value( $paginate_dot_distance_fallback, $attr['paginateDotDistanceUnit'] ),
	),
	' .spectra-ig-feed__control-loader div'               => array(
		'background-color' => $attr['paginateColor'],
		'width'            => UAGB_Helper::get_css_value( $attr['paginateLoaderSize'], 'px' ),
		'height'           => UAGB_Helper::get_css_value( $attr['paginateLoaderSize'], 'px' ),
		'border-radius'    => '100%',
		'padding'          => 0,
	),
	' .spectra-ig-feed__control-button'                   => array_merge(
		array(
			'margin-top'       => UAGB_Helper::get_css_value( $paginate_dot_distance_fallback, $attr['paginateDotDistanceUnit'] ),
			'padding'          => UAGB_Block_Helper::generate_spacing(
				$attr['paginateButtonPaddingUnit'],
				$attr['paginateButtonPaddingTop'],
				$attr['paginateButtonPaddingRight'],
				$attr['paginateButtonPaddingBottom'],
				$attr['paginateButtonPaddingLeft']
			),
			'color'            => $attr['paginateButtonTextColor'],
			'background-color' => $attr['paginateColor'],
			'font-family'      => 'Default' === $attr['loadMoreFontFamily'] ? '' : $attr['loadMoreFontFamily'],
			'font-weight'      => $attr['loadMoreFontWeight'],
			'font-style'       => $attr['loadMoreFontStyle'],
			'text-decoration'  => $attr['loadMoreDecoration'],
			'text-transform'   => $attr['loadMoreTransform'],
			'font-size'        => UAGB_Helper::get_css_value( $attr['loadMoreFontSize'], $attr['loadMoreFontSizeType'] ),
			'line-height'      => UAGB_Helper::get_css_value( $attr['loadMoreLineHeight'], $attr['loadMoreLineHeightType'] ),
		),
		$btn_border_css
	),
	' .spectra-ig-feed__control-button:hover'             => array(
		'color'            => $attr['paginateButtonTextColorHover'],
		'background-color' => $attr['paginateColorHover'],
		'border-color'     => $attr['btnBorderHColor'],
	),

	// Media Wrapper Selectors.

	' .spectra-ig-feed__layout--grid'                     => array(
		'grid-gap' => UAGB_Helper::get_css_value(
			$attr['gridPostGap'],
			$attr['gridPostGapUnit']
		),
	),
	' .spectra-ig-feed__layout--isogrid'                  => array(
		'margin' => UAGB_Helper::get_css_value(
			-abs( $attr['gridPostGap'] / 2 ),
			$attr['gridPostGapUnit']
		),
	),
	' .spectra-ig-feed__layout--isogrid .spectra-ig-feed__media-wrapper' => array(
		'padding' => UAGB_Helper::get_css_value(
			$attr['gridPostGap'] / 2,
			$attr['gridPostGapUnit']
		),
	),
	' .spectra-ig-feed__layout--masonry'                  => array(
		'margin' => UAGB_Helper::get_css_value(
			-abs( $attr['gridPostGap'] / 2 ),
			$attr['gridPostGapUnit']
		),
	),
	' .spectra-ig-feed__layout--masonry .spectra-ig-feed__media-wrapper' => array(
		'padding' => UAGB_Helper::get_css_value(
			$attr['gridPostGap'] / 2,
			$attr['gridPostGapUnit']
		),
	),
	' .spectra-ig-feed__layout--carousel'                 => array(
		// Override Slick Slider Margin.
		'margin-bottom' => UAGB_Helper::get_css_value(
			$paginate_dot_distance_fallback,
			'px'
		) . ' !important',
	),
	' .spectra-ig-feed__layout--carousel .spectra-ig-feed__media-wrapper' => array(
		'padding' => UAGB_Block_Helper::generate_spacing(
			$attr['gridPostGapUnit'],
			0,
			( $attr['gridPostGap'] / 2 )
		),
	),
	' .spectra-ig-feed__layout--carousel .slick-list'     => array(
		'margin' => UAGB_Block_Helper::generate_spacing(
			$attr['gridPostGapUnit'],
			0,
			-( $attr['gridPostGap'] / 2 )
		),
	),
	' .spectra-ig-feed__media'                            => array_merge(
		$image_border_css,
		array(
			'box-shadow' => $post_box_shadow_css,
		)
	),
	' .spectra-ig-feed__media:hover'                      => array(
		'border-color' => $attr['imageBorderHColor'],
	),
	' .spectra-ig-feed__media-wrapper:hover .spectra-ig-feed__media' => array(
		'box-shadow' => $post_box_shadow_hover_css,
	),

	// Thumbnail Selectors.

	' .spectra-ig-feed__media-thumbnail-blurrer'          => array(
		'-webkit-backdrop-filter' => 'blur(' . UAGB_Helper::get_css_value(
			$attr['captionBackgroundBlurAmount'],
			'px'
		) . ')',
		'backdrop-filter'         => 'blur(' . UAGB_Helper::get_css_value(
			$attr['captionBackgroundBlurAmount'],
			'px'
		) . ')',
	),
	' .spectra-ig-feed__media-wrapper:hover .spectra-ig-feed__media-thumbnail-blurrer' => array(
		'-webkit-backdrop-filter' => 'blur(' . UAGB_Helper::get_css_value(
			$attr['captionBackgroundBlurAmountHover'],
			'px'
		) . ')',
		'backdrop-filter'         => 'blur(' . UAGB_Helper::get_css_value(
			$attr['captionBackgroundBlurAmountHover'],
			'px'
		) . ')',
	),

	// Caption Wrapper Selectors.

	' .spectra-ig-feed__media-thumbnail-caption-wrapper--overlay' => array(
		'background-color' => $attr['postDisplayCaption'] ? ( ( 'hover' === $attr['captionVisibility'] ) ? 'rgba(0,0,0,0)' : $attr['captionBackgroundColor'] ) : $attr['overlayColor'],
	),
	' .spectra-ig-feed__media-wrapper:hover .spectra-ig-feed__media-thumbnail-caption-wrapper--overlay' => array(
		'background-color' => $attr['postDisplayCaption'] ? ( ( 'antiHover' === $attr['captionVisibility'] ) ? 'rgba(0,0,0,0)' : ( ( 'always' === $attr['captionVisibility'] && $attr['captionSeparateColors'] ) ? $attr['captionBackgroundColorHover'] : $attr['captionBackgroundColor'] ) ) : $attr['overlayColorHover'],
	),
	' .spectra-ig-feed__media-thumbnail-caption-wrapper--bar-inside' => array(
		'-webkit-align-items'     => UAGB_Block_Helper::get_matrix_alignment( $attr['postCaptionAlignment'], 1, 'flex' ),
		'align-items'             => UAGB_Block_Helper::get_matrix_alignment( $attr['postCaptionAlignment'], 1, 'flex' ),
		'-webkit-justify-content' => UAGB_Block_Helper::get_matrix_alignment( $attr['postCaptionAlignment'], 2, 'flex' ),
		'justify-content'         => UAGB_Block_Helper::get_matrix_alignment( $attr['postCaptionAlignment'], 2, 'flex' ),
	),

	// Caption Selectors.

	' .spectra-ig-feed__media-thumbnail-caption'          => array(
		'color'           => ( 'hover' === $attr['captionVisibility'] ) ? 'rgba(0,0,0,0)' : $attr['captionColor'],
		'text-align'      => UAGB_Block_Helper::get_matrix_alignment( $attr['postCaptionAlignment'], 2 ),
		'font-family'     => 'Default' === $attr['captionFontFamily'] ? '' : $attr['captionFontFamily'],
		'font-weight'     => $attr['captionFontWeight'],
		'font-style'      => $attr['captionFontStyle'],
		'text-decoration' => $attr['captionDecoration'],
		'text-transform'  => $attr['captionTransform'],
		'font-size'       => UAGB_Helper::get_css_value( $attr['captionFontSize'], $attr['captionFontSizeType'] ),
		'line-height'     => UAGB_Helper::get_css_value( $attr['captionLineHeight'], $attr['captionLineHeightType'] ),
		'padding'         => UAGB_Block_Helper::generate_spacing(
			$attr['captionPaddingUnit'],
			$attr['captionPaddingTop'],
			$attr['captionPaddingRight'],
			$attr['captionPaddingBottom'],
			$attr['captionPaddingLeft']
		),
	),
	' .spectra-ig-feed__media-thumbnail-caption--overlay' => array(
		'-webkit-align-items'     => UAGB_Block_Helper::get_matrix_alignment( $attr['postCaptionAlignment'], 1, 'flex' ),
		'align-items'             => UAGB_Block_Helper::get_matrix_alignment( $attr['postCaptionAlignment'], 1, 'flex' ),
		'-webkit-justify-content' => UAGB_Block_Helper::get_matrix_alignment( $attr['postCaptionAlignment'], 2, 'flex' ),
		'justify-content'         => UAGB_Block_Helper::get_matrix_alignment( $attr['postCaptionAlignment'], 2, 'flex' ),
	),
	' .spectra-ig-feed__media-thumbnail-caption--bar-inside' => array_merge(
		array(
			'background-color' => ( 'hover' === $attr['captionVisibility'] ) ? 'rgba(0,0,0,0)' : $attr['captionBackgroundColor'],
		),
		$main_title_border_css,
		array(
			'border-color' => ( 'hover' === $attr['captionVisibility'] ) ? 'rgba(0,0,0,0)' : $attr['mainTitleBorderColor'],
		)
	),
	' .spectra-ig-feed__media-wrapper:hover .spectra-ig-feed__media-thumbnail-caption--bar-inside' => array(
		'background-color' => ( 'antiHover' === $attr['captionVisibility'] ) ? 'rgba(0,0,0,0)' : ( ( 'always' === $attr['captionVisibility'] && $attr['captionSeparateColors'] ) ? $attr['captionBackgroundColorHover'] : $attr['captionBackgroundColor'] ),
		'border-color'     => ( 'antiHover' === $attr['captionVisibility'] ) ? 'rgba(0,0,0,0)' : $attr['mainTitleBorderHColor'],
	),
	' .spectra-ig-feed__media-thumbnail-caption--bar-outside' => array_merge(
		array(
			'background-color' => $attr['captionBackgroundColor'],
		),
		$main_title_border_css
	),
	' .spectra-ig-feed__media-wrapper:hover .spectra-ig-feed__media-thumbnail-caption--bar-outside' => array(
		'background-color' => $attr['captionSeparateColors'] ? $attr['captionBackgroundColorHover'] : $attr['captionBackgroundColor'],
		'border-color'     => $attr['mainTitleBorderHColor'],
	),
	' .spectra-ig-feed__media-wrapper:hover .spectra-ig-feed__media-thumbnail-caption' => array(
		'color' => ( 'antiHover' === $attr['captionVisibility'] ) ? 'rgba(0,0,0,0)' : ( ( 'always' === $attr['captionVisibility'] && $attr['captionSeparateColors'] ) ? $attr['captionColorHover'] : $attr['captionColor'] ),
	),
);

$t_selectors = array(
	'.wp-block-uagb-instagram-feed'                   => array(
		'padding' => UAGB_Block_Helper::generate_spacing(
			$attr['feedMarginUnitTab'],
			$attr['feedMarginTopTab'],
			$attr['feedMarginRightTab'],
			$attr['feedMarginBottomTab'],
			$attr['feedMarginLeftTab']
		),
	),
	' .spectra-ig-feed__control-arrows--carousel'     => $arrow_border_css_tablet,
	' .spectra-ig-feed__control-button'               => array_merge(
		array(
			'padding'     => UAGB_Block_Helper::generate_spacing(
				$attr['paginateButtonPaddingUnitTab'],
				$attr['paginateButtonPaddingTopTab'],
				$attr['paginateButtonPaddingRightTab'],
				$attr['paginateButtonPaddingBottomTab'],
				$attr['paginateButtonPaddingLeftTab']
			),
			'font-size'   => UAGB_Helper::get_css_value( $attr['loadMoreFontSizeTab'], $attr['loadMoreFontSizeType'] ),
			'line-height' => UAGB_Helper::get_css_value( $attr['loadMoreLineHeightTab'], $attr['loadMoreLineHeightType'] ),
		),
		$btn_border_css_tablet
	),
	' .spectra-ig-feed__layout--grid'                 => array(
		'grid-gap' => UAGB_Helper::get_css_value(
			$grid_post_gap_tablet_fallback,
			$attr['gridPostGapUnitTab']
		),
	),
	' .spectra-ig-feed__layout--isogrid'              => array(
		'margin' => UAGB_Helper::get_css_value(
			-abs( $grid_post_gap_tablet_fallback / 2 ),
			$attr['gridPostGapUnitTab']
		),
	),
	' .spectra-ig-feed__layout--isogrid .spectra-ig-feed__media-wrapper' => array(
		'padding' => UAGB_Helper::get_css_value(
			$grid_post_gap_tablet_fallback / 2,
			$attr['gridPostGapUnitTab']
		),
	),
	' .spectra-ig-feed__layout--masonry'              => array(
		'margin' => UAGB_Helper::get_css_value(
			-abs( $grid_post_gap_tablet_fallback / 2 ),
			$attr['gridPostGapUnitTab']
		),
	),
	' .spectra-ig-feed__layout--masonry .spectra-ig-feed__media-wrapper' => array(
		'padding' => UAGB_Helper::get_css_value(
			$grid_post_gap_tablet_fallback / 2,
			$attr['gridPostGapUnitTab']
		),
	),
	' .spectra-ig-feed__layout--carousel .spectra-ig-feed__media-wrapper' => array(
		'padding' => UAGB_Block_Helper::generate_spacing(
			$attr['gridPostGapUnitTab'],
			0,
			( $grid_post_gap_tablet_fallback / 2 )
		),
	),
	' .spectra-ig-feed__layout--carousel .slick-list' => array(
		'margin' => UAGB_Block_Helper::generate_spacing(
			$attr['gridPostGapUnitTab'],
			0,
			-( $grid_post_gap_tablet_fallback / 2 )
		),
	),
	' .spectra-ig-feed__layout--tiled'                => array(
		'grid-gap' => UAGB_Helper::get_css_value(
			$grid_post_gap_tablet_fallback,
			$attr['gridPostGapUnitTab']
		),
	),
	' .spectra-ig-feed__media'                        => $image_border_css_tablet,
	' .spectra-ig-feed__media-thumbnail-caption'      => array(
		'font-size'   => UAGB_Helper::get_css_value( $attr['captionFontSizeTab'], $attr['captionFontSizeType'] ),
		'line-height' => UAGB_Helper::get_css_value( $attr['captionLineHeightTab'], $attr['captionLineHeightType'] ),
		'padding'     => UAGB_Block_Helper::generate_spacing(
			$attr['captionPaddingUnitTab'],
			$attr['captionPaddingTopTab'],
			$attr['captionPaddingRightTab'],
			$attr['captionPaddingBottomTab'],
			$attr['captionPaddingLeftTab']
		),
	),
	' .spectra-ig-feed__media-thumbnail-caption--bar-inside' => $main_title_border_css_tablet,
	' .spectra-ig-feed__media-thumbnail-caption--bar-outside' => $main_title_border_css_tablet,
);

$m_selectors = array(
	'.wp-block-uagb-instagram-feed'                   => array(
		'padding' => UAGB_Block_Helper::generate_spacing(
			$attr['feedMarginUnitMob'],
			$attr['feedMarginTopMob'],
			$attr['feedMarginRightMob'],
			$attr['feedMarginBottomMob'],
			$attr['feedMarginLeftMob']
		),
	),
	' .spectra-ig-feed__control-arrows--carousel'     => $arrow_border_css_mobile,
	' .spectra-ig-feed__control-button'               => array_merge(
		array(
			'padding'     => UAGB_Block_Helper::generate_spacing(
				$attr['paginateButtonPaddingUnitMob'],
				$attr['paginateButtonPaddingTopMob'],
				$attr['paginateButtonPaddingRightMob'],
				$attr['paginateButtonPaddingBottomMob'],
				$attr['paginateButtonPaddingLeftMob']
			),
			'font-size'   => UAGB_Helper::get_css_value( $attr['loadMoreFontSizeMob'], $attr['loadMoreFontSizeType'] ),
			'line-height' => UAGB_Helper::get_css_value( $attr['loadMoreLineHeightMob'], $attr['loadMoreLineHeightType'] ),
		),
		$btn_border_css_mobile
	),
	' .spectra-ig-feed__layout--grid'                 => array(
		'grid-gap' => UAGB_Helper::get_css_value(
			$grid_post_gap_mobile_fallback,
			$attr['gridPostGapUnitMob']
		),
	),
	' .spectra-ig-feed__layout--isogrid'              => array(
		'margin' => UAGB_Helper::get_css_value(
			-abs( $grid_post_gap_mobile_fallback / 2 ),
			$attr['gridPostGapUnitMob']
		),
	),
	' .spectra-ig-feed__layout--isogrid .spectra-ig-feed__media-wrapper' => array(
		'padding' => UAGB_Helper::get_css_value(
			$grid_post_gap_mobile_fallback / 2,
			$attr['gridPostGapUnitMob']
		),
	),
	' .spectra-ig-feed__layout--masonry'              => array(
		'margin' => UAGB_Helper::get_css_value(
			-abs( $grid_post_gap_mobile_fallback / 2 ),
			$attr['gridPostGapUnitMob']
		),
	),
	' .spectra-ig-feed__layout--masonry .spectra-ig-feed__media-wrapper' => array(
		'padding' => UAGB_Helper::get_css_value(
			$grid_post_gap_mobile_fallback / 2,
			$attr['gridPostGapUnitMob']
		),
	),
	' .spectra-ig-feed__layout--carousel .spectra-ig-feed__media-wrapper' => array(
		'padding' => UAGB_Block_Helper::generate_spacing(
			$attr['gridPostGapUnitMob'],
			0,
			( $grid_post_gap_mobile_fallback / 2 )
		),
	),
	' .spectra-ig-feed__layout--carousel .slick-list' => array(
		'margin' => UAGB_Block_Helper::generate_spacing(
			$attr['gridPostGapUnitMob'],
			0,
			-( $grid_post_gap_mobile_fallback / 2 )
		),
	),
	' .spectra-ig-feed__layout--tiled .spectra-ig-feed__media-wrapper' => array(
		'grid-gap' => UAGB_Helper::get_css_value(
			$grid_post_gap_mobile_fallback,
			$attr['gridPostGapUnitMob']
		),
	),
	' .spectra-ig-feed__media'                        => $image_border_css_mobile,
	' .spectra-ig-feed__media-thumbnail-caption'      => array(
		'font-size'   => UAGB_Helper::get_css_value( $attr['captionFontSizeMob'], $attr['captionFontSizeType'] ),
		'line-height' => UAGB_Helper::get_css_value( $attr['captionLineHeightMob'], $attr['captionLineHeightType'] ),
		'padding'     => UAGB_Block_Helper::generate_spacing(
			$attr['captionPaddingUnitMob'],
			$attr['captionPaddingTopMob'],
			$attr['captionPaddingRightMob'],
			$attr['captionPaddingBottomMob'],
			$attr['captionPaddingLeftMob']
		),
	),
	' .spectra-ig-feed__media-thumbnail-caption--bar-inside' => $main_title_border_css_mobile,
	' .spectra-ig-feed__media-thumbnail-caption--bar-outside' => $main_title_border_css_mobile,
);

// Background Effect based styling.
switch ( $attr['captionBackgroundEffect'] ) {
	case 'none':
		$selectors[' .spectra-ig-feed__media-thumbnail']['-webkit-filter'] = 'none';
		$selectors[' .spectra-ig-feed__media-thumbnail']['filter']         = 'none';
		break;
	case 'grayscale':
	case 'sepia':
		$selectors[' .spectra-ig-feed__media-thumbnail']['-webkit-filter'] = $attr['captionBackgroundEffect'] . '(' . UAGB_Helper::get_css_value(
			$attr['captionBackgroundEffectAmount'],
			'%'
		) . ')';
		$selectors[' .spectra-ig-feed__media-thumbnail']['filter']         = $attr['captionBackgroundEffect'] . '(' . UAGB_Helper::get_css_value(
			$attr['captionBackgroundEffectAmount'],
			'%'
		) . ')';
		break;
};
switch ( $attr['captionBackgroundEffectHover'] ) {
	case 'none':
		$selectors[' .spectra-ig-feed__media-wrapper:hover .spectra-ig-feed__media-thumbnail']['-webkit-filter'] = 'none';
		$selectors[' .spectra-ig-feed__media-wrapper:hover .spectra-ig-feed__media-thumbnail']['filter']         = 'none';
		break;
	case 'grayscale':
	case 'sepia':
		$selectors[' .spectra-ig-feed__media-wrapper:hover .spectra-ig-feed__media-thumbnail']['-webkit-filter'] = $attr['captionBackgroundEffectHover'] . '(' . UAGB_Helper::get_css_value(
			$attr['captionBackgroundEffectAmountHover'],
			'%'
		) . ')';
		$selectors[' .spectra-ig-feed__media-wrapper:hover .spectra-ig-feed__media-thumbnail']['filter']         = $attr['captionBackgroundEffectHover'] . '(' . UAGB_Helper::get_css_value(
			$attr['captionBackgroundEffectAmountHover'],
			'%'
		) . ')';
		break;
};
if ( ! $attr['captionBackgroundEnableBlur'] ) {
	$selectors[' .spectra-ig-feed__media-thumbnail-blurrer']['-webkit-backdrop-filter'] = 'none';
	$selectors[' .spectra-ig-feed__media-thumbnail-blurrer']['backdrop-filter']         = 'none';
	$selectors[' .spectra-ig-feed__media-wrapper:hover .spectra-ig-feed__media-thumbnail-blurrer']['-webkit-backdrop-filter'] = 'none';
	$selectors[' .spectra-ig-feed__media-wrapper:hover .spectra-ig-feed__media-thumbnail-blurrer']['backdrop-filter']         = 'none';
}

// Caption Type based styling.
if ( $attr['postDisplayCaption'] && ( 'bar-outside' === $attr['captionDisplayType'] ) ) {
	if ( 'top' === $attr['postCaptionAlignment01'] ) {
		$selectors[' .spectra-ig-feed__media-thumbnail-caption-wrapper']['margin-bottom'] = UAGB_Helper::get_css_value(
			$attr['captionGap'],
			$attr['captionGapUnit']
		);
	} else {
		$selectors[' .spectra-ig-feed__media-thumbnail-caption-wrapper']['margin-top'] = UAGB_Helper::get_css_value(
			$attr['captionGap'],
			$attr['captionGapUnit']
		);
	}
}

// Grid based styling.
if ( 'grid' === $attr['feedLayout'] && $attr['feedPagination'] ) {
	$selectors[' .spectra-ig-feed__control-wrapper']['margin-top'] = UAGB_Helper::get_css_value(
		$paginate_dot_distance_fallback,
		$attr['paginateDotDistanceUnit']
	);
}

// Carousel based styling.
if ( 'carousel' === $attr['feedLayout'] ) {
	if ( $attr['carouselSquares'] ) {
		$selectors[' .spectra-ig-feed__layout--carousel .slick-slide']['align-self'] = 'flex-start';
		$selectors[' .spectra-ig-feed__media--carousel']['aspect-ratio']             = 1;
		$selectors[' .spectra-ig-feed__media-thumbnail--carousel']['height']         = '100%';
		$selectors[' .spectra-ig-feed__media-thumbnail--carousel']['width']          = '100%';
		$selectors[' .spectra-ig-feed__media-thumbnail--carousel']['-o-object-fit']  = 'cover';
		$selectors[' .spectra-ig-feed__media-thumbnail--carousel']['object-fit']     = 'cover';
	}
} else {
	$selectors[' .spectra-ig-feed__iso-ref-wrapper']['overflow'] = 'auto';
}

// Masonry based styling.
if ( 'masonry' === $attr['feedLayout'] && $attr['feedPagination'] && ! $attr['paginateUseLoader'] ) {
	$selectors[' .spectra-ig-feed__control-wrapper']['-webkit-justify-content'] = $attr['paginateButtonAlign'];
	$selectors[' .spectra-ig-feed__control-wrapper']['justify-content']         = $attr['paginateButtonAlign'];
	$selectors[' .spectra-ig-feed__control-wrapper']['-webkit-align-items']     = 'center';
	$selectors[' .spectra-ig-feed__control-wrapper']['align-items']             = 'center';
}

// New Zoom Effect on Hover.
switch ( $attr['postZoomType'] ) {
	case 'zoom-in':
		if ( $attr['postEnableZoom'] ) {
			$selectors[' .spectra-ig-feed__media-thumbnail']['transform']                                       = 'scale3d(1.005, 1.005, 1.005)';
			$selectors[' .spectra-ig-feed__media-wrapper:hover .spectra-ig-feed__media-thumbnail']['transform'] = 'scale3d(1.1, 1.1, 1.1)';
		}
		break;
	case 'zoom-out':
		if ( $attr['postEnableZoom'] ) {
			$selectors[' .spectra-ig-feed__media-thumbnail']['transform']                                       = 'scale3d(1.1, 1.1, 1.1)';
			$selectors[' .spectra-ig-feed__media-wrapper:hover .spectra-ig-feed__media-thumbnail']['transform'] = 'scale3d(1.005, 1.005, 1.005)';
		}
		break;
}

// Box Shadow Application Based on Type.
if ( 'outset' === $attr['postBoxShadowPosition'] ) {
	$selectors[' .spectra-ig-feed__media']['box-shadow']                   = $post_box_shadow_css;
	$selectors[' .spectra-ig-feed__media-thumbnail-blurrer']['box-shadow'] = '0 0 transparent' . (
		( 'inset' === $attr['postBoxShadowPositionHover'] ) ? ( ' ' . $attr['postBoxShadowPositionHover'] ) : ''
	);
} else {
	$selectors[' .spectra-ig-feed__media-thumbnail-blurrer']['box-shadow'] = $post_box_shadow_css;
	$selectors[' .spectra-ig-feed__media']['box-shadow']                   = '0 0 transparent' . (
		( 'inset' === $attr['postBoxShadowPositionHover'] ) ? ( ' ' . $attr['postBoxShadowPositionHover'] ) : ''
	);
}

if ( 'outset' === $attr['postBoxShadowPositionHover'] ) {
	$selectors[' .spectra-ig-feed__media-wrapper:hover .spectra-ig-feed__media']['box-shadow']                   = $post_box_shadow_hover_css;
	$selectors[' .spectra-ig-feed__media-wrapper:hover .spectra-ig-feed__media-thumbnail-blurrer']['box-shadow'] = '0 0 transparent' . (
		( 'inset' === $attr['postBoxShadowPosition'] ) ? ( ' ' . $attr['postBoxShadowPosition'] ) : ''
	);
} else {
	$selectors[' .spectra-ig-feed__media-wrapper:hover .spectra-ig-feed__media-thumbnail-blurrer']['box-shadow'] = $post_box_shadow_hover_css;
	$selectors[' .spectra-ig-feed__media-wrapper:hover .spectra-ig-feed__media']['box-shadow']                   = '0 0 transparent' . (
		( 'inset' === $attr['postBoxShadowPosition'] ) ? ( ' ' . $attr['postBoxShadowPosition'] ) : ''
	);
}

// Slick Dot Positioning in the Front-end.
$selectors[' .spectra-ig-feed__layout--carousel .slick-dots']['margin-bottom'] = '20px !important';

// Hiding the Front-end Images that are somehow generated for Emojis when required.
if ( 'hover' === $attr['captionVisibility'] ) {
	$selectors[' .spectra-ig-feed__media-wrapper img.emoji']['opacity']       = 0;
	$selectors[' .spectra-ig-feed__media-wrapper:hover img.emoji']['opacity'] = 1;
} elseif ( 'antiHover' === $attr['captionVisibility'] ) {
	$selectors[' .spectra-ig-feed__media-wrapper img.emoji']['opacity']       = 1;
	$selectors[' .spectra-ig-feed__media-wrapper:hover img.emoji']['opacity'] = 0;
}

$combined_selectors = array(
	'desktop' => $selectors,
	'tablet'  => $t_selectors,
	'mobile'  => $m_selectors,
);

$base_selector = '.uagb-block-';

return UAGB_Helper::generate_all_css( $combined_selectors, $base_selector . $id );
