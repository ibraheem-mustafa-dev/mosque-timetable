<?php
namespace SpectraPro\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extensions_Manager
 *
 * @package spectra-pro
 * @since 1.0.0
 */
class Extensions_Manager {

	/**
	 * Micro Constructor
	 */
	public static function init() {
		$self = new self();
		$self->load_dynamic_content();
		$self->load_loop_data();
	}
	/**
	 * Init Dynamic Content Extenstions
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function load_dynamic_content() {
		\SpectraPro\Includes\Extensions\DynamicContent\DynamicContent::init();
		\SpectraPro\Includes\Extensions\PopupBuilder\Spectra_Pro_Popup_Builder::init();
	}

	/**
	 * Init loop data.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function load_loop_data() {
		\SpectraPro\Includes\Extensions\QueryLoop::init();
	}
}
