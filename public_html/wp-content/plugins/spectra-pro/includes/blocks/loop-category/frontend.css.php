<?php
/**
 * Frontend CSS.
 *
 * @since 1.2.0
 *
 * @package spectra-pro
 */

/**
 * Adding this comment to avoid PHPStan errors of undefined variable as these variables are defined else where.
 *
 * @var mixed[] $attr
 * @var int $id
 */

// Add fonts.
SpectraPro\Core\Utils::blocks_loop_category_gfont( $attr );

$cat_border        = UAGB_Block_Helper::uag_generate_border_css( $attr, 'cat' );
$cat_border_tablet = UAGB_Block_Helper::uag_generate_border_css( $attr, 'cat', 'tablet' );
$cat_border_mobile = UAGB_Block_Helper::uag_generate_border_css( $attr, 'cat', 'mobile' );

$selectors['.wp-block-uagb-loop-category'] = array(
	'text-align' => $attr['catAlign'],
);

$selectors['.wp-block-uagb-loop-category select'] = array_merge(
	array(
		'width'          => UAGB_Helper::get_css_value( $attr['catWidth'], $attr['catWidthUnit'] ),
		'color'          => $attr['catTextColor'],
		'background'     => $attr['catFieldColor'],
		'padding-top'    => UAGB_Helper::get_css_value( $attr['paddingCatTop'], $attr['paddingCatUnit'] ),
		'padding-bottom' => UAGB_Helper::get_css_value( $attr['paddingCatBottom'], $attr['paddingCatUnit'] ),
		'padding-left'   => UAGB_Helper::get_css_value( $attr['paddingCatLeft'], $attr['paddingCatUnit'] ),
		'padding-right'  => UAGB_Helper::get_css_value( $attr['paddingCatRight'], $attr['paddingCatUnit'] ),
		'text-transform' => $attr['catTransform'],
	),
	$cat_border
);

$selectors['.wp-block-uagb-loop-category.wp-block-uagb-cat--has-margin .uagb-cat-margin-wrapper'] = array(
	'margin-top'    => UAGB_Helper::get_css_value( $attr['marginCatTop'], $attr['marginCatUnit'] ),
	'margin-bottom' => UAGB_Helper::get_css_value( $attr['marginCatBottom'], $attr['marginCatUnit'] ),
	'margin-left'   => UAGB_Helper::get_css_value( $attr['marginCatLeft'], $attr['marginCatUnit'] ),
	'margin-right'  => UAGB_Helper::get_css_value( $attr['marginCatRight'], $attr['marginCatUnit'] ),
);

$selectors['.wp-block-uagb-loop-category .uagb-cat-checkbox-item']          = array(
	'column-gap' => UAGB_Helper::get_css_value( $attr['betweenGapDesktop'], $attr['betweenGapType'] ),
);
$selectors['.wp-block-uagb-loop-category .uagb-cat-checkbox-item label']    = array(
	'color' => $attr['catTextColor'],
);
$selectors['.wp-block-uagb-loop-category.uagb-loop-category-type-checkbox'] = array(
	'padding-top'    => UAGB_Helper::get_css_value( $attr['paddingCatTop'], $attr['paddingCatUnit'] ),
	'padding-bottom' => UAGB_Helper::get_css_value( $attr['paddingCatBottom'], $attr['paddingCatUnit'] ),
	'padding-left'   => UAGB_Helper::get_css_value( $attr['paddingCatLeft'], $attr['paddingCatUnit'] ),
	'padding-right'  => UAGB_Helper::get_css_value( $attr['paddingCatRight'], $attr['paddingCatUnit'] ),
	'margin-top'     => UAGB_Helper::get_css_value( $attr['marginCatTop'], $attr['marginCatUnit'] ),
	'margin-bottom'  => UAGB_Helper::get_css_value( $attr['marginCatBottom'], $attr['marginCatUnit'] ),
	'margin-left'    => UAGB_Helper::get_css_value( $attr['marginCatLeft'], $attr['marginCatUnit'] ),
	'margin-right'   => UAGB_Helper::get_css_value( $attr['marginCatRight'], $attr['marginCatUnit'] ),
	'row-gap'        => UAGB_Helper::get_css_value( $attr['rowGapDesktop'], $attr['rowGapType'] ),
	'column-gap'     => UAGB_Helper::get_css_value( $attr['columnGapDesktop'], $attr['columnGapType'] ),
);

$selectors['.wp-block-uagb-loop-category select:hover'] = array(
	'border-color' => $attr['catBorderHColor'],
);

if ( 'inline' === $attr['layout'] ) {
	$selectors['.wp-block-uagb-loop-category.uagb-loop-category-type-checkbox.uagb-loop-category-layout-inline'] = array(
		'flex-direction' => 'row',
		'display'        => 'flex',
		'flex-wrap'      => 'wrap',
	);
} elseif ( 'stack' === $attr['layout'] ) { 
	$selectors['.wp-block-uagb-loop-category.uagb-loop-category-type-checkbox.uagb-loop-category-layout-stack'] = array(
		'flex-direction' => 'column',
		'display'        => 'flex',
		'flex-wrap'      => 'wrap',
	);
}

$t_selectors = array(
	'.wp-block-uagb-loop-category'                         => array(
		'text-align' => $attr['catAlignTablet'],
	),
	'.wp-block-uagb-loop-category select'                  => array_merge(
		array(
			'width'          => UAGB_Helper::get_css_value( $attr['catWidthTablet'], $attr['catWidthUnitTablet'] ),
			'padding-top'    => UAGB_Helper::get_css_value( $attr['paddingCatTopTablet'], $attr['paddingCatUnitTablet'] ),
			'padding-bottom' => UAGB_Helper::get_css_value( $attr['paddingCatBottomTablet'], $attr['paddingCatUnitTablet'] ),
			'padding-left'   => UAGB_Helper::get_css_value( $attr['paddingCatLeftTablet'], $attr['paddingCatUnitTablet'] ),
			'padding-right'  => UAGB_Helper::get_css_value( $attr['paddingCatRightTablet'], $attr['paddingCatUnitTablet'] ),
		),
		$cat_border_tablet
	),
	'.wp-block-uagb-loop-category.wp-block-uagb-cat--has-margin .uagb-cat-margin-wrapper' => array(
		'margin-top'    => UAGB_Helper::get_css_value( $attr['marginCatTopTablet'], $attr['marginCatUnitTablet'] ),
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['marginCatBottomTablet'], $attr['marginCatUnitTablet'] ),
		'margin-left'   => UAGB_Helper::get_css_value( $attr['marginCatLeftTablet'], $attr['marginCatUnitTablet'] ),
		'margin-right'  => UAGB_Helper::get_css_value( $attr['marginCatRightTablet'], $attr['marginCatUnitTablet'] ),
	),
	'.wp-block-uagb-loop-category.uagb-loop-category-type-checkbox' => array(
		'padding-top'    => UAGB_Helper::get_css_value( $attr['paddingCatTopTablet'], $attr['paddingCatUnitTablet'] ),
		'padding-bottom' => UAGB_Helper::get_css_value( $attr['paddingCatBottomTablet'], $attr['paddingCatUnitTablet'] ),
		'padding-left'   => UAGB_Helper::get_css_value( $attr['paddingCatLeftTablet'], $attr['paddingCatUnitTablet'] ),
		'padding-right'  => UAGB_Helper::get_css_value( $attr['paddingCatRightTablet'], $attr['paddingCatUnitTablet'] ),
		'margin-top'     => UAGB_Helper::get_css_value( $attr['marginCatTopTablet'], $attr['marginCatUnitTablet'] ),
		'margin-bottom'  => UAGB_Helper::get_css_value( $attr['marginCatBottomTablet'], $attr['marginCatUnitTablet'] ),
		'margin-left'    => UAGB_Helper::get_css_value( $attr['marginCatLeftTablet'], $attr['marginCatUnitTablet'] ),
		'margin-right'   => UAGB_Helper::get_css_value( $attr['marginCatRightTablet'], $attr['marginCatUnitTablet'] ),
		'row-gap'        => UAGB_Helper::get_css_value( $attr['rowGapTablet'], $attr['rowGapTypeTablet'] ),
		'column-gap'     => UAGB_Helper::get_css_value( $attr['columnGapTablet'], $attr['columnGapTypeTablet'] ),
	),
	'.wp-block-uagb-loop-category .uagb-cat-checkbox-item' => array(
		'column-gap' => UAGB_Helper::get_css_value( $attr['betweenGapTablet'], $attr['betweenGapTypeTablet'] ),
	),
);

if ( 'inline' === $attr['layoutTablet'] ) {
	$t_selectors['.wp-block-uagb-loop-category.uagb-loop-category-type-checkbox.uagb-loop-category-layout-inline-tablet'] = array(
		'flex-direction' => 'row',
		'display'        => 'flex',
		'flex-wrap'      => 'wrap',
	);
} elseif ( 'stack' === $attr['layoutTablet'] ) { 
	$t_selectors['.wp-block-uagb-loop-category.uagb-loop-category-type-checkbox.uagb-loop-category-layout-stack-tablet'] = array(
		'flex-direction' => 'column',
		'display'        => 'flex',
		'flex-wrap'      => 'wrap',
	);
}

$m_selectors = array(
	'.wp-block-uagb-loop-category'                         => array(
		'text-align' => $attr['catAlignMobile'],
	),
	'.wp-block-uagb-loop-category select'                  => array_merge( 
		array(
			'width'          => UAGB_Helper::get_css_value( $attr['catWidthMobile'], $attr['catWidthUnitMobile'] ),
			'padding-top'    => UAGB_Helper::get_css_value( $attr['paddingCatTopMobile'], $attr['paddingCatUnitMobile'] ),
			'padding-bottom' => UAGB_Helper::get_css_value( $attr['paddingCatBottomMobile'], $attr['paddingCatUnitMobile'] ),
			'padding-left'   => UAGB_Helper::get_css_value( $attr['paddingCatLeftMobile'], $attr['paddingCatUnitMobile'] ),
			'padding-right'  => UAGB_Helper::get_css_value( $attr['paddingCatRightMobile'], $attr['paddingCatUnitMobile'] ),
		),
		$cat_border_mobile
	),
	'.wp-block-uagb-loop-category.wp-block-uagb-cat--has-margin .uagb-cat-margin-wrapper' => array(
		'margin-top'    => UAGB_Helper::get_css_value( $attr['marginCatTopMobile'], $attr['marginCatUnitMobile'] ),
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['marginCatBottomMobile'], $attr['marginCatUnitMobile'] ),
		'margin-left'   => UAGB_Helper::get_css_value( $attr['marginCatLeftMobile'], $attr['marginCatUnitMobile'] ),
		'margin-right'  => UAGB_Helper::get_css_value( $attr['marginCatRightMobile'], $attr['marginCatUnitMobile'] ),
	),
	'.wp-block-uagb-loop-category.uagb-loop-category-type-checkbox' => array(
		'padding-top'    => UAGB_Helper::get_css_value( $attr['paddingCatTopMobile'], $attr['paddingCatUnitMobile'] ),
		'padding-bottom' => UAGB_Helper::get_css_value( $attr['paddingCatBottomMobile'], $attr['paddingCatUnitMobile'] ),
		'padding-left'   => UAGB_Helper::get_css_value( $attr['paddingCatLeftMobile'], $attr['paddingCatUnitMobile'] ),
		'padding-right'  => UAGB_Helper::get_css_value( $attr['paddingCatRightMobile'], $attr['paddingCatUnitMobile'] ),
		'margin-top'     => UAGB_Helper::get_css_value( $attr['marginCatTopMobile'], $attr['marginCatUnitMobile'] ),
		'margin-bottom'  => UAGB_Helper::get_css_value( $attr['marginCatBottomMobile'], $attr['marginCatUnitMobile'] ),
		'margin-left'    => UAGB_Helper::get_css_value( $attr['marginCatLeftMobile'], $attr['marginCatUnitMobile'] ),
		'margin-right'   => UAGB_Helper::get_css_value( $attr['marginCatRightMobile'], $attr['marginCatUnitMobile'] ),
		'row-gap'        => UAGB_Helper::get_css_value( $attr['rowGapMobile'], $attr['rowGapTypeMobile'] ),
		'column-gap'     => UAGB_Helper::get_css_value( $attr['columnGapMobile'], $attr['columnGapTypeMobile'] ),
	),
	'.wp-block-uagb-loop-category .uagb-cat-checkbox-item' => array(
		'column-gap' => UAGB_Helper::get_css_value( $attr['betweenGapMobile'], $attr['betweenGapTypeMobile'] ),
	),
);

if ( 'inline' === $attr['layoutMobile'] ) {
	$m_selectors['.wp-block-uagb-loop-category.uagb-loop-category-type-checkbox.uagb-loop-category-layout-inline-mobile'] = array(
		'flex-direction' => 'row',
		'display'        => 'flex',
		'flex-wrap'      => 'wrap',
	);
} elseif ( 'stack' === $attr['layoutMobile'] ) { 
	$m_selectors['.wp-block-uagb-loop-category.uagb-loop-category-type-checkbox.uagb-loop-category-layout-stack-mobile'] = array(
		'flex-direction' => 'column',
		'display'        => 'flex',
		'flex-wrap'      => 'wrap',
	);
}

$combined_selectors = array(
	'desktop' => $selectors,
	'tablet'  => $t_selectors,
	'mobile'  => $m_selectors,
);

$combined_selectors = UAGB_Helper::get_typography_css( $attr, 'cat', '.wp-block-uagb-loop-category select', $combined_selectors );
$combined_selectors = UAGB_Helper::get_typography_css( $attr, 'cat', '.wp-block-uagb-loop-category .uagb-cat-checkbox-item label', $combined_selectors );

return UAGB_Helper::generate_all_css( $combined_selectors, '.uagb-block-' . $id );
