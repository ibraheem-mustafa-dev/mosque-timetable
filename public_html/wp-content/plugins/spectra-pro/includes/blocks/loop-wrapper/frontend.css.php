<?php
/**
 * Frontend CSS.
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

$selectors = array(
	'.uagb-loop-container' => array(
		'flex-direction' => $attr['numberOfColumn'] > 1 ? 'row' : 'column',
		'gap'            => UAGB_Helper::get_css_value( $attr['columnGap'], $attr['columnGapType'] ),
	),
);

$loop_post_width = '100%';

if ( $attr['numberOfColumn'] > 1 ) {
	$number_of_column = absint( $attr['numberOfColumn'] );
	$number_of_gaps   = $number_of_column - 1;
	$loop_post_width  = "calc( calc(100% - {$number_of_gaps} * {$attr['columnGap']}{$attr['columnGapType']} ) / {$number_of_column} )";
}

$selectors['.uagb-loop-container > .uagb-loop-post'] = [
	'width' => $loop_post_width,
];

$t_selectors = array(
	'.uagb-loop-container' => array(
		'flex-direction' => $attr['numberOfColumnTablet'] > 1 ? 'row' : 'column',
		'gap'            => UAGB_Helper::get_css_value( $attr['columnGapTablet'], $attr['columnGapType'] ),
	),
);

$loop_post_width_tablet = '100%';

if ( $attr['numberOfColumnTablet'] > 1 ) {
	$number_of_gaps         = $attr['numberOfColumnTablet'] - 1;
	$loop_post_width_tablet = "calc( calc(100% - {$number_of_gaps} * {$attr['columnGapTablet']}{$attr['columnGapType']} ) / {$attr['numberOfColumnTablet']} )";
}

$t_selectors['.uagb-loop-container > .uagb-loop-post'] = [
	'width' => $loop_post_width_tablet,
];

$m_selectors = array(
	'.uagb-loop-container' => array(
		'flex-direction' => $attr['numberOfColumnMobile'] > 1 ? 'row' : 'column',
		'gap'            => UAGB_Helper::get_css_value( $attr['columnGapMobile'], $attr['columnGapType'] ),
	),
);

$loop_post_width_mobile = '100%';

if ( $attr['numberOfColumnMobile'] > 1 ) {
	$number_of_gaps         = $attr['numberOfColumnMobile'] - 1;
	$loop_post_width_mobile = "calc( calc(100% - {$number_of_gaps} * {$attr['columnGapMobile']}{$attr['columnGapType']} ) / {$attr['numberOfColumnMobile']} )";
}

$m_selectors['.uagb-loop-container > .uagb-loop-post'] = [
	'width' => $loop_post_width_mobile,
];

$combined_selectors = array(
	'desktop' => $selectors,
	'tablet'  => $t_selectors,
	'mobile'  => $m_selectors,
);

return UAGB_Helper::generate_all_css( $combined_selectors, '.uagb-block-' . $id );
