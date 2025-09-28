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
SpectraPro\Core\Utils::blocks_search_gfont( $attr );

$search_border        = UAGB_Block_Helper::uag_generate_border_css( $attr, 'search' );
$search_border_tablet = UAGB_Block_Helper::uag_generate_border_css( $attr, 'search', 'tablet' );
$search_border_mobile = UAGB_Block_Helper::uag_generate_border_css( $attr, 'search', 'mobile' );

$selectors['.wp-block-uagb-loop-search'] = array(
	'text-align' => $attr['searchAlign'],
);

$selectors['.wp-block-uagb-loop-search.wp-block-uagb-search--has-margin .uagb-search-margin-wrapper'] = array(
	'margin-top'    => UAGB_Helper::get_css_value( $attr['marginSearchTop'], $attr['marginSearchUnit'] ),
	'margin-bottom' => UAGB_Helper::get_css_value( $attr['marginSearchBottom'], $attr['marginSearchUnit'] ),
	'margin-left'   => UAGB_Helper::get_css_value( $attr['marginSearchLeft'], $attr['marginSearchUnit'] ),
	'margin-right'  => UAGB_Helper::get_css_value( $attr['marginSearchRight'], $attr['marginSearchUnit'] ),
);


$selectors['.wp-block-uagb-loop-search input[type=text]'] = array_merge(
	array(
		'width'          => UAGB_Helper::get_css_value( $attr['searchWidth'], $attr['searchWidthUnit'] ),
		'color'          => $attr['searchTextColor'],
		'background'     => $attr['searchFieldColor'],
		'padding-top'    => UAGB_Helper::get_css_value( $attr['paddingSearchTop'], $attr['paddingSearchUnit'] ),
		'padding-bottom' => UAGB_Helper::get_css_value( $attr['paddingSearchBottom'], $attr['paddingSearchUnit'] ),
		'padding-left'   => UAGB_Helper::get_css_value( $attr['paddingSearchLeft'], $attr['paddingSearchUnit'] ),
		'padding-right'  => UAGB_Helper::get_css_value( $attr['paddingSearchRight'], $attr['paddingSearchUnit'] ),
		'text-transform' => $attr['searchTransform'],
	),
	$search_border
);

$selectors['.wp-block-uagb-loop-search input[type="text"]:focus'] = array(
	'border-color' => $attr['searchBorderColor'],
);

$selectors['.wp-block-uagb-loop-search input[type=text]:hover'] = array(
	'border-color' => $attr['searchBorderHColor'],
);

$selectors['.wp-block-uagb-loop-search input[type=text]::placeholder'] = array(
	'color'           => $attr['placeholderColor'],
	'text-decoration' => $attr['searchDecoration'],
);

$t_selectors = array(
	'.wp-block-uagb-loop-search'                  => array(
		'text-align' => $attr['searchAlignTablet'],
	),
	'.wp-block-uagb-loop-search input[type=text]' => array_merge(
		array(
			'width'          => UAGB_Helper::get_css_value( $attr['searchWidthTablet'], $attr['searchWidthUnitTablet'] ),
			'padding-top'    => UAGB_Helper::get_css_value( $attr['paddingSearchTopTablet'], $attr['paddingSearchUnitTablet'] ),
			'padding-bottom' => UAGB_Helper::get_css_value( $attr['paddingSearchBottomTablet'], $attr['paddingSearchUnitTablet'] ),
			'padding-left'   => UAGB_Helper::get_css_value( $attr['paddingSearchLeftTablet'], $attr['paddingSearchUnitTablet'] ),
			'padding-right'  => UAGB_Helper::get_css_value( $attr['paddingSearchRightTablet'], $attr['paddingSearchUnitTablet'] ),
		),
		$search_border_tablet
	),
	'.wp-block-uagb-loop-search.wp-block-uagb-search--has-margin .uagb-search-margin-wrapper' => array(
		'margin-top'    => UAGB_Helper::get_css_value( $attr['marginSearchTopTablet'], $attr['marginSearchUnitTablet'] ),
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['marginSearchBottomTablet'], $attr['marginSearchUnitTablet'] ),
		'margin-left'   => UAGB_Helper::get_css_value( $attr['marginSearchLeftTablet'], $attr['marginSearchUnitTablet'] ),
		'margin-right'  => UAGB_Helper::get_css_value( $attr['marginSearchRightTablet'], $attr['marginSearchUnitTablet'] ),
	),
);

$m_selectors = array(
	'.wp-block-uagb-loop-search'                  => array(
		'text-align' => $attr['searchAlignMobile'],
	),
	'.wp-block-uagb-loop-search input[type=text]' => array_merge(
		array(
			'width'          => UAGB_Helper::get_css_value( $attr['searchWidthMobile'], $attr['searchWidthUnitMobile'] ),
			'padding-top'    => UAGB_Helper::get_css_value( $attr['paddingSearchTopMobile'], $attr['paddingSearchUnitMobile'] ),
			'padding-bottom' => UAGB_Helper::get_css_value( $attr['paddingSearchBottomMobile'], $attr['paddingSearchUnitMobile'] ),
			'padding-left'   => UAGB_Helper::get_css_value( $attr['paddingSearchLeftMobile'], $attr['paddingSearchUnitMobile'] ),
			'padding-right'  => UAGB_Helper::get_css_value( $attr['paddingSearchRightMobile'], $attr['paddingSearchUnitMobile'] ),
		),
		$search_border_mobile
	),
	'.wp-block-uagb-loop-search.wp-block-uagb-search--has-margin .uagb-search-margin-wrapper' => array(
		'margin-top'    => UAGB_Helper::get_css_value( $attr['marginSearchTopMobile'], $attr['marginSearchUnitMobile'] ),
		'margin-bottom' => UAGB_Helper::get_css_value( $attr['marginSearchBottomMobile'], $attr['marginSearchUnitMobile'] ),
		'margin-left'   => UAGB_Helper::get_css_value( $attr['marginSearchLeftMobile'], $attr['marginSearchUnitMobile'] ),
		'margin-right'  => UAGB_Helper::get_css_value( $attr['marginSearchRightMobile'], $attr['marginSearchUnitMobile'] ),
	),
);

$combined_selectors = array(
	'desktop' => $selectors,
	'tablet'  => $t_selectors,
	'mobile'  => $m_selectors,
);

$combined_selectors = UAGB_Helper::get_typography_css( $attr, 'search', '.wp-block-uagb-loop-search input', $combined_selectors );

return UAGB_Helper::generate_all_css( $combined_selectors, '.uagb-block-' . $id );
