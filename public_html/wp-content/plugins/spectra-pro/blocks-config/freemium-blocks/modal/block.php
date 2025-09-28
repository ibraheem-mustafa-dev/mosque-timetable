<?php
/**
 * Modal Pro Block config file.
 */
namespace SpectraPro\BlocksConfig\FreemiumBlocks\Modal;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Modal Pro Block config Class.
 */
class Block {

	/**
	 * Init class.
	 *
	 * @return void
	 */
	public static function init() {
		$self = new self();
		add_filter( 'spectra_uagb/modal_blockdata', [ $self, 'filter_modal_block_data' ] );
		add_filter( 'spectra_modal_frontend_dynamic_js', [ $self, 'override_dynamic_js' ], 10, 3 );
		add_filter( 'spectra_modal_attributes', [ $self, 'add_attributes_defaults' ] );
		add_filter( 'spectra_modal_styling', [ $self, 'add_dynamic_styling' ], 10, 2 );
	}

	/**
	 * Filters the Modal block data.
	 *
	 * @param array $block_data The block data to filter.
	 * @return array The filtered block data.
	 */
	public static function filter_modal_block_data( $block_data ) {
		if ( isset( $block_data['static_dependencies']['uagb-modal-js'] ) && ! empty( $block_data['static_dependencies']['uagb-modal-js'] ) ) {
			$block_data['static_dependencies']['uagb-modal-js']['src'] = \SpectraPro\Core\Utils::get_js_url( 'modal' );
		}
		return $block_data;
	}

	/**
	 * Add attributes to the modal block.
	 *
	 * @param array $attributes The block attributes.
	 * @return array The block attributes.
	 */
	public function add_attributes_defaults( $attributes ) {
		return array_merge(
			$attributes,
			array(
				'openModalAs'          => 'popup',
				'appearEffect'         => 'uagb-effect-default',
				'modalPosition'        => 'centered',
				'hPos'                 => '',
				'vPos'                 => '',
				'cssClass'             => '',
				'cssID'                => '',
				'exitIntent'           => false,
				'showAfterSeconds'     => false,
				'noOfSecondsToShow'    => 5,
				'enableCookies'        => false,
				'setCookiesOn'         => 'close-action',
				'hideForDays'          => 2,
				'iconBGColor'          => '#aaa',
				'closeIconType'        => 'none',
				'closeIconShape'       => 'Circle',
				'closeIconBorderWidth' => 3,
			)
		);
	}

	/**
	 * Override Block dynamic JS.
	 *
	 * @param string $output JS output.
	 * @param string $selector Selector.
	 * @param array  $attr Attributes.
	 * @return string Modified Output.
	 */
	public function override_dynamic_js( $output, $selector, $attr ) {
		$args = array(
			'modalTrigger'      => $attr['modalTrigger'],
			'cssClass'          => $attr['cssClass'],
			'cssID'             => $attr['cssID'],
			'exitIntent'        => $attr['exitIntent'],
			'showAfterSeconds'  => $attr['showAfterSeconds'],
			'noOfSecondsToShow' => $attr['noOfSecondsToShow'],
			'enableCookies'     => $attr['enableCookies'],
			'setCookiesOn'      => $attr['setCookiesOn'],
			'hideForDays'       => $attr['hideForDays'],
		);

		\ob_start();
		?>
			window.addEventListener( 'DOMContentLoaded', function() {
				UAGBModal.init( '<?php echo esc_attr( $selector ); ?>', false, '<?php echo wp_json_encode( $args ); ?>' );
			});
		<?php

		$output = \ob_get_clean();

		return $output;
	}

	/**
	 * Add Dynamic CSS to block.
	 *
	 * @param array $selectors  The selectors.
	 * @param array $atts       The Block Attributes.
	 * @return array            The Modified Selectors.
	 */
	public function add_dynamic_styling( $selectors, $atts ) {
		if ( 'custom' === $atts['modalPosition'] && ! empty( $atts['hPos'] ) && ! empty( [ 'vPos' ] ) ) {
			$selectors[' .uagb-modal-popup-wrap']['position'] = 'absolute';
			$selectors[' .uagb-modal-popup-wrap']['left']     = \UAGB_Helper::get_css_value( $atts['hPos'], 'px' );
			$selectors[' .uagb-modal-popup-wrap']['top']      = \UAGB_Helper::get_css_value( $atts['vPos'], 'px' );
		}
		if ( 'Stacked' === $atts['closeIconType'] ) {
			$selectors[' .uagb-modal-popup-close']['background-color'] = $atts['iconBGColor'];
			if ( 'Circle' === $atts['closeIconShape'] ) {
				$selectors[' .uagb-modal-popup-close']['border-radius'] = '50%';
			}
		} elseif ( 'Framed' === $atts['closeIconType'] ) {
			$selectors[' .uagb-modal-popup-close']['border'] = $atts['closeIconBorderWidth'] . 'px solid ' . $atts['iconBGColor'];
			if ( 'Circle' === $atts['closeIconShape'] ) {
				$selectors[' .uagb-modal-popup-close']['border-radius'] = '50%';
			}
		}//end if
		return $selectors;
	}
}

