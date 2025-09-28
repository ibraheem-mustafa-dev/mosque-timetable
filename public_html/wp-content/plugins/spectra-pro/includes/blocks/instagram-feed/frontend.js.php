<?php
/**
 * Frontend JS File.
 *
 * @since 1.0.0
 *
 * @package spectra-pro
 */

use SpectraPro\BlocksConfig\InstagramFeed\Block;

/**
 * Adding this comment to avoid PHPStan errors of undefined variable as these variables are defined else where.
 *
 * @var mixed[] $attr
 * @var string $id
 */

$columns_tab_fallback = is_numeric( $attr['columnsTab'] ) ? $attr['columnsTab'] : $attr['columnsDesk'];
$columns_mob_fallback = is_numeric( $attr['columnsMob'] ) ? $attr['columnsMob'] : $columns_tab_fallback;

$is_rtl = is_rtl();

$slick_options = apply_filters(
	'uagb_instagram_feed_slick_options',
	array(
		'arrows'        => $attr['paginateUseArrows'],
		'dots'          => $attr['paginateUseDots'],
		'initialSlide'  => $attr['carouselStartAt'],
		'infinite'      => $attr['carouselLoop'],
		'autoplay'      => $attr['carouselAutoplay'],
		'autoplaySpeed' => $attr['carouselAutoplaySpeed'],
		'pauseOnHover'  => $attr['carouselPauseOnHover'],
		'speed'         => $attr['carouselTransitionSpeed'],
		'slidesToShow'  => $attr['columnsDesk'],
		'prevArrow'     => "<button type='button' data-role='none' class='spectra-ig-feed__control-arrows spectra-ig-feed__control-arrows--carousel slick-prev slick-arrow' aria-label='Previous' tabindex='0' role='button'><svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 256 512' width='{$attr['paginateArrowSize']}' height='{$attr['paginateArrowSize']}'><path d='M31.7 239l136-136c9.4-9.4 24.6-9.4 33.9 0l22.6 22.6c9.4 9.4 9.4 24.6 0 33.9L127.9 256l96.4 96.4c9.4 9.4 9.4 24.6 0 33.9L201.7 409c-9.4 9.4-24.6 9.4-33.9 0l-136-136c-9.5-9.4-9.5-24.6-.1-34z'></path></svg></button>",
		'nextArrow'     => "<button type='button' data-role='none' class='spectra-ig-feed__control-arrows spectra-ig-feed__control-arrows--carousel slick-next slick-arrow' aria-label='Previous' tabindex='0' role='button'><svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 256 512' width='{$attr['paginateArrowSize']}' height='{$attr['paginateArrowSize']}'><path d='M224.3 273l-136 136c-9.4 9.4-24.6 9.4-33.9 0l-22.6-22.6c-9.4-9.4-9.4-24.6 0-33.9l96.4-96.4-96.4-96.4c-9.4-9.4-9.4-24.6 0-33.9L54.3 103c9.4-9.4 24.6-9.4 33.9 0l136 136c9.5 9.4 9.5 24.6.1 34z'></path></svg></button>",
		'rtl'           => $is_rtl,
		'responsive'    => array(
			array(
				'breakpoint' => 1024,
				'settings'   => array(
					'slidesToShow' => $columns_tab_fallback,
				),
			),
			array(
				'breakpoint' => 767,
				'settings'   => array(
					'slidesToShow' => $columns_mob_fallback,
				),
			),
		),
	),
	$id
);

$settings = wp_json_encode( $slick_options );
$selector = '.uagb-block-' . $id;
$js       = '';

if ( Block::get_insta_media_transients( $attr['igUserName'] ) ) {
	switch ( $attr['feedLayout'] ) {
		case 'grid':
			$js = $attr['feedPagination']
				? Block::render_frontend_grid_pagination( $id, $attr, $selector )
				: '';
			break;
		case 'masonry':
			$js = Block::render_frontend_masonry_layout( $id, $attr, $selector );
			break;
		case 'carousel':
			$js = Block::render_frontend_carousel_layout( $id, $settings, $selector );
			break;
	}
}

return $js;

