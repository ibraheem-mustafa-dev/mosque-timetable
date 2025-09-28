<?php
namespace SpectraPro\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Base
 *
 * @package spectra-pro
 * @since 1.0.0
 */
class Base {

	/**
	 * Micro Constructor
	 */
	public static function init() {
		$self = new self();
		add_action( 'uag_register_block', [ $self, 'register_blocks' ] );
	}

	/**
	 * Register all blocks
	 *
	 * @param object $that UAGB_Block object.
	 * @return void
	 * @since 1.0.0
	 */
	public function register_blocks( $that ) {
		$block_files = glob( SPECTRA_PRO_DIR . 'includes/blocks/*/block.php' );

		foreach ( $block_files as $block_file ) {
			$that->register( $block_file );
		}

	}
}
