<?php
namespace SpectraPro\BlocksConfig;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Config
 *
 * @package spectra-pro
 * @since 1.0.0
 */
class Config {
	/**
	 * Micro Constructor
	 */
	public static function init() {
		$self = new self();
		$self->load_blocks();
	}

	/**
	 * Register All Blocks.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function load_blocks() {
		Login\Block::init();
		Register\Block::init();
		InstagramFeed\Block::init();
		FreemiumBlocks\Countdown\Block::init();
		FreemiumBlocks\Modal\Block::init();
		FreemiumBlocks\Countdown\Block::init();
		FreemiumBlocks\Slider\Block::init();
		FreemiumBlocks\ImageGallery\Block::init();
	}
}
