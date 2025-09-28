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
SpectraPro\Core\Utils::blocks_sort_gfont( $attr );

$sort_border        = UAGB_Block_Helper::uag_generate_border_css( $attr, 'sort' );
$sort_border_tablet = UAGB_Block_Helper::uag_generate_border_css( $attr, 'sort', 'tablet' );
$sort_border_mobile = UAGB_Block_Helper::uag_generate_border_css( $attr, 'sort', 'mobile' );

$selectors['.wp-block-uagb-loop-sort'] = array(
	'text-align' => $attr['sortAlign'],
);

$selectors['.wp-block-uagb-loop-sort select'] = array_merge(
	array(
		'width'          => UAGB_Helper::get_css_value( $attr['sortWidth'], $attr['sortWidthUnit'] ),
		'color'          => $attr['sortTextColor'],
		'background'     => $attr['sortFieldColor'],
		'padding-top'    => UAGB_Helper::get_css_value( $attr['paddingSortTop'], $attr['paddingSortUnit'] ),
		'padding-bottom' => UAGB_Helper::get_css_value( $attr['paddingSortBottom'], $attr['paddingSortUnit'] ),
		'padding-left'   => UAGB_Helper::get_css_value( $attr['paddingSortLeft'], $attr['paddingSortUnit'] ),
		'padding-right'  => UAGB_Helper::get_css_value( $attr['paddingSortRight'], $attr['paddingSortUnit'] ),
		'text-transform' => $attr['sortTransform'],
	),
	$sort_border
);

$selectors['.wp-block-uagb-loop-sort.wp-block-uagb-sort--has-margin .uagb-sort-margin-wrapper'] = array(
	'margin-top'    => UAGB_Helper::get_css_value( $attr['marginSortTop'], $attr['marginSortUnit'] ),
	'margin-bottom' => UAGB_Helper::get_css_value( $attr['marginSortBottom'], $attr['marginSortUnit'] ),
	'margin-left'   => UAGB_Helper::get_css_value( $attr['marginSortLeft'], $attr['marginSortUnit'] ),
	'margin-right'  => UAGB_Helper::get_css_value( $attr['marginSortRight'], $attr['marginSortUnit'] ),
);

$selectors['.wp-block-uagb-loop-sort select:hover'] = array(
	'border-color' => $attr['sortBorderHColor'],
);

$t_selectors = array(
	'.wp-block-uagb-loop-sort'        => array(
		'text-align' => $attr['sortAlignTablet'],
	),
	'.wp-block-uagb-loop-sort select' => array_merge(
		array(
			'width'          => UAGB_Helper::get_css_value( $attr['sortWidthTablet'], $attr['sortWidthUnitTablet'] ),
			'padding-top'    => UAGB_Helper::get_css_value( $attr['paddingSortTopTablet'], $attr['paddingSortUnitTablet'] ),
			'padding-bottom' => UAGB_Helper::get_css_value( $attr['paddingSortBottomTablet'], $attr['paddingSortUnitTablet'] ),
			'padding-left'   => UAGB_Helper::get_css_value( $attr['paddingSortLeftTablet'], $attr['paddingSortUnitTablet'] ),
			'padding-right'  => UAGB_Helper::get_css_value( $attr['paddingSortRightTablet'], $attr['paddingSortUnitTablet'] ),
		),
		$sort_border_tablet
	),
	'.wp-block-uagb-loop-sort.wp-block-uagb-sort--has-margin .uagb-sort-margin-wrapper' => array(
		'margin-top'    => UAGB_Helper::get_css_value( $attr['marginSortTopTablet'], $attr['marginSortUnitTablet'] ),
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['marginSortBottomTablet'], $attr['marginSortUnitTablet'] ),
		'margin-left'   => UAGB_Helper::get_css_value( $attr['marginSortLeftTablet'], $attr['marginSortUnitTablet'] ),
		'margin-right'  => UAGB_Helper::get_css_value( $attr['marginSortRightTablet'], $attr['marginSortUnitTablet'] ),
	),
);

$m_selectors = array(
	'.wp-block-uagb-loop-sort'        => array(
		'text-align' => $attr['sortAlignMobile'],
	),
	'.wp-block-uagb-loop-sort select' => array_merge(
		array(
			'width'          => UAGB_Helper::get_css_value( $attr['sortWidthMobile'], $attr['sortWidthUnitMobile'] ),
			'padding-top'    => UAGB_Helper::get_css_value( $attr['paddingSortTopMobile'], $attr['paddingSortUnitMobile'] ),
			'padding-bottom' => UAGB_Helper::get_css_value( $attr['paddingSortBottomMobile'], $attr['paddingSortUnitMobile'] ),
			'padding-left'   => UAGB_Helper::get_css_value( $attr['paddingSortLeftMobile'], $attr['paddingSortUnitMobile'] ),
			'padding-right'  => UAGB_Helper::get_css_value( $attr['paddingSortRightMobile'], $attr['paddingSortUnitMobile'] ),
		),
		$sort_border_mobile
	),
	'.wp-block-uagb-loop-sort.wp-block-uagb-sort--has-margin .uagb-sort-margin-wrapper' => array(
		'margin-top'    => UAGB_Helper::get_css_value( $attr['marginSortTopMobile'], $attr['marginSortUnitMobile'] ),
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['marginSortBottomMobile'], $attr['marginSortUnitMobile'] ),
		'margin-left'   => UAGB_Helper::get_css_value( $attr['marginSortLeftMobile'], $attr['marginSortUnitMobile'] ),
		'margin-right'  => UAGB_Helper::get_css_value( $attr['marginSortRightMobile'], $attr['marginSortUnitMobile'] ),
	),
);

$combined_selectors = array(
	'desktop' => $selectors,
	'tablet'  => $t_selectors,
	'mobile'  => $m_selectors,
);

$combined_selectors = UAGB_Helper::get_typography_css( $attr, 'sort', '.wp-block-uagb-loop-sort select', $combined_selectors );

return UAGB_Helper::generate_all_css( $combined_selectors, '.uagb-block-' . $id );
