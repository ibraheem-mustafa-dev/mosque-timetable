<?php

namespace SpectraPro\BlocksConfig\FreemiumBlocks\Slider;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Block to handle filter to extend slider.
 */
class Block {

	/**
	 * Init
	 */
	public static function init() {

		$self = new self();
		add_filter( 'spectra_pro_render_block', array( $self, 'render_slider_child_hash_nav' ), 10, 2 );
		add_filter( 'uagb_slider_options', array( $self, 'extend_slider_options' ), 10, 2 );
		add_action( 'spectra_after_slider_options_loaded', array( $self, 'register_nav_events' ) );
		add_filter( 'spectra_frontend_static_style', array( $self, 'load_slider_pro_styles' ), 10, 2 );
		add_filter( 'spectra_slider_frontend_attributes', array( $self, 'extend_frontend_attributes' ) );
	}

	/**
	 * Add the Hash Navigation to Slider Children if needed.
	 *
	 * @param string $block_content  The block content.
	 * @param array  $block          The block data.
	 * @since 1.0.0
	 * @return string                The block content after updation.
	 */
	public function render_slider_child_hash_nav( $block_content, $block ) {
		// Return early if this isn't a slider, or if it is a slider that either is without hash navigation enabled or is empty.
		if (
			! is_string( $block_content )
			|| 'uagb/slider' !== $block['blockName']
			|| ! is_array( $block['innerBlocks'] )
			|| empty( $block['innerBlocks'] )
		) {
			return $block_content;
		}

		// Return if Hash Nav is not needed.
		if ( empty( $block['attrs']['enableHashNavigation'] ) ) {
			return $block_content;
		}

		foreach ( $block['innerBlocks'] as $slider_child ) {
			// First set the nav link, or the default if it doesn't exist.
			// We're checking specifically for '' instead of empty, since the user can enter a falsely identifier if they desire.
			$data_hash = ( isset( $slider_child['attrs']['navigationLink'] ) && '' !== $slider_child['attrs']['navigationLink'] ) ? $slider_child['attrs']['navigationLink'] : 'slide-' . $slider_child['attrs']['block_id'];

			// Next set the string to replace the opening div tag of this slider child.
			// NOTE: This check adds a data-hash tag attribute before the class attribute of the first non-updated slider child in this slider.
			$new_tag_opening = '<div data-hash="' . esc_attr( $data_hash ) . '" class="wp-block-uagb-slider-child';

			// Replace the closest opening div tag in this slider with its updated hash nav opening.
			$updated_content = preg_replace( '/<div class="wp-block-uagb-slider-child/', $new_tag_opening, $block_content, 1 );

			// If an error was encountered, null would have been passed. Keep the content as it is when this happens.
			if ( $updated_content ) {
				$block_content = $updated_content;
			}
		}

		return $block_content;
	}

	/**
	 * Extend slider options.
	 *
	 * @param array $options slider options.
	 * @param array $attributes block attributes.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function extend_slider_options( $options, $attributes ) {

		if ( isset( $attributes['enableHashNavigation'] ) && $attributes['enableHashNavigation'] ) {
			$options['hashNavigation'] = array(
				'replaceState' => true,
				'watchState'   => true,
			);
		}

		// Set the Responsive Attributes if they're empty.
		$attributes['gapBetweenSlidesTablet'] = ! empty( $attributes['gapBetweenSlidesTablet'] ) ? $attributes['gapBetweenSlidesTablet'] : $attributes['gapBetweenSlides'];
		$attributes['gapBetweenSlidesMobile'] = ! empty( $attributes['gapBetweenSlidesMobile'] ) ? $attributes['gapBetweenSlidesMobile'] : $attributes['gapBetweenSlidesTablet'];

		$attributes['slidesPerViewTablet'] = ! empty( $attributes['slidesPerViewTablet'] ) ? $attributes['slidesPerViewTablet'] : $attributes['slidesPerView'];
		$attributes['slidesPerViewMobile'] = ! empty( $attributes['slidesPerViewMobile'] ) ? $attributes['slidesPerViewMobile'] : $attributes['slidesPerViewTablet'];

		// Revert Focus Mode.
		if ( ! empty( $options['centeredSlides'] ) ) {
			$options['centeredSlides'] = false;
		}
		// Update the Swiper based on the breakpoints.
		$options['breakpoints'] = array(
			UAGB_TABLET_BREAKPOINT => array(
				'slidesPerView' => $attributes['slidesPerView'],
				'spaceBetween'  => $attributes['gapBetweenSlides'],
			),
			// when window width is >= 767px.
			UAGB_MOBILE_BREAKPOINT => array(
				'slidesPerView' => $attributes['slidesPerViewTablet'],
				'spaceBetween'  => $attributes['gapBetweenSlidesTablet'],
			),
			// when window width is >= 320px.
			320                    => array(
				'slidesPerView' => $attributes['slidesPerViewMobile'],
				'spaceBetween'  => $attributes['gapBetweenSlidesMobile'],
			),
		);

		return $options;

	}

	/**
	 * Load slider pro static style on front end.
	 *
	 * @param string $style slider style.
	 * @param string $block_name block name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function load_slider_pro_styles( $style, $block_name ) {

		if ( 'slider' === $block_name ) {

			$block_static_css_path = SPECTRA_PRO_DIR . 'assets/css/blocks/slider-pro.css';

			if ( file_exists( $block_static_css_path ) ) {

				$file_system = uagb_filesystem();

				$style .= $file_system->get_contents( $block_static_css_path );
			}
		}

		return $style;
	}

	/**
	 * Extend slider front end attributes to work default values.
	 *
	 * @param array $slider_attributes slider attributes.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function extend_frontend_attributes( $slider_attributes ) {

		return array_merge(
			array(
				'enableHashNavigation'   => false,
				'navigationLink'         => '',
				'gapBetweenSlides'       => 10,
				'gapBetweenSlidesTablet' => '',
				'gapBetweenSlidesMobile' => '',
				'sliderStyle'            => 'normal',
				'slideOverlayColor'      => 'rgba(0,0,0,.5)',
				'slidesPerView'          => 1,
				'slidesPerViewTablet'    => '',
				'slidesPerViewMobile'    => '',
			),
			$slider_attributes
		);

	}

	/**
	 * Register click events for custom navigation.
	 *
	 * @param array $attributes Array of block attributes.
	 * @since 1.0.0
	 */
	public function register_nav_events( $attributes ) {

		if ( empty( $attributes['enableCustomNavigation'] ) ) {
			return;
		}

		$selector  = '.uagb-block-' . $attributes['block_id'] . ' .uagb-swiper';
		$slider_id = isset( $attributes['sliderID'] ) ? $attributes['sliderID'] : '';
		?>

		window.addEventListener("DOMContentLoaded", function(){

			const swiper = document.querySelector("<?php echo esc_attr( $selector ); ?>").swiper;

			const slideNextElements = document.querySelectorAll('.slider-<?php echo esc_attr( $slider_id ); ?>-next');

			slideNextElements.forEach(element => {
				element.addEventListener('click', event => {
					swiper?.slideNext();
				});
			});

			const slidePrevElements = document.querySelectorAll('.slider-<?php echo esc_attr( $slider_id ); ?>-prev');

			slidePrevElements.forEach(element => {
				element.addEventListener('click', event => {
					swiper?.slidePrev();
				});
			});
		});
		<?php
	}
}

