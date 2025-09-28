<?php

namespace SpectraPro\BlocksConfig\FreemiumBlocks\ImageGallery;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class for Image Gallery Pro enhancements.
 *
 * @since 1.0.0
 */
class Block {

	/**
	 * Initialize class.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		$self = new self();
		add_filter( 'uagb_image_gallery_dynamic_attributes', array( $self, 'extend_dynamic_attributes' ), 10, 1 );
		add_filter( 'uagb_image_gallery_pro_custom_url_js', array( $self, 'render_custom_url_frontend_js' ), 10, 3 );
		add_filter( 'uagb_image_gallery_pro_lightbox_js', array( $self, 'render_lightbox_url_frontend_js' ), 10, 3 );
		add_filter( 'spectra_image-gallery_styling', array( $self, 'render_frontend_css' ), 10, 2 );
	}

	/**
	 * Extend the attributes of the dynamic Image Gallery block.
	 *
	 * @param array $pro_attributes  Array of block attributes.
	 * @return array                 The updated Array.
	 *
	 * @since 1.0.0
	 */
	public function extend_dynamic_attributes( $pro_attributes ) {
		return array_merge(
			$pro_attributes,
			array(
				'customLinks'                     => array(
					'type'    => 'object',
					'default' => '',
				),
				'lightboxLinkedCaptionColor'      => array(
					'type' => 'string',
				),
				'lightboxLinkedCaptionHoverColor' => array(
					'type' => 'string',
				),
				'customLinksBehaviour'            => array(
					'type'    => 'object',
					'default' => '',
				),
			)
		);
	}

	/**
	 * Add the JS Script to handle custom URL on click in the frontend.
	 *
	 * @param string $js          The Current Image Gallery Block JS for Frontend.
	 * @param int    $id          The Block ID.
	 * @param array  $attributes  Array of attributes.
	 * @return string             The updated JS script.
	 *
	 * @since 1.0.0
	 */
	public function render_custom_url_frontend_js( $js, $id, $attributes ) {
		ob_start();
		?>
			window.addEventListener( 'DOMContentLoaded', () => {
				const blockScope = document.querySelector( '.uagb-block-<?php echo esc_html( (string) $id ); ?>' );
				if ( ! blockScope ) {
					return;
				}

				const regexCustomURL = new RegExp( '^((http|https):[\/]{2})(www.)?((?!www)[a-zA-Z0-9@:%._\\+~#?&//=\\-]{2,256})\\.[a-zA-Z]{2,256}\\b([-a-zA-Z0-9@:%._\\+~#?&//=]*)$');
				const customLinks = <?php echo isset( $attributes['customLinks'] ) ? wp_json_encode( $attributes['customLinks'] ) : '{}'; ?>;
				const customLinksBehaviour = <?php echo isset( $attributes['customLinksBehaviour'] ) ? wp_json_encode( $attributes['customLinksBehaviour'] ) : '{}'; ?>;

				const getCustomURL = ( image ) => {
					const imageID = parseInt( image.getAttribute( 'data-spectra-gallery-image-id' ) );
					return ( regexCustomURL.test( customLinks[ imageID ] ) ? customLinks[ imageID ] : undefined );
				}

				const openCustomURL = ( customURL, shouldOpenInNewTab ) => {
					if ( shouldOpenInNewTab ) { 
						window.open(customURL, "_blank");
					} else {
						window.location.assign( customURL );
					}
				}

				const getCustomLinkBehaviour = (caption) => {
					if (!customLinksBehaviour) {
						return true;
					}
					const imageId = parseInt(caption.getAttribute('data-spectra-gallery-image-id'));
					return !customLinksBehaviour[imageId] === true;
				};

				const images = blockScope.querySelectorAll( '.spectra-image-gallery__media-wrapper' );
				for ( let i = 0; i < images.length; i++ ) {
					const customURL = getCustomURL( images[ i ] );
					const shouldOpenInNewTab = getCustomLinkBehaviour( images[ i ] );
					if ( customURL ) {
						images[ i ].style.cursor = 'pointer';
						images[ i ].addEventListener( 'click', () => openCustomURL( customURL, shouldOpenInNewTab) );

						// Add event listener for Enter and Space key presses
						images[ i ].addEventListener('keydown', (event) => {
						if ( 13 === event.keyCode || 32 === event.keyCode ) {
							openCustomURL(customURL, shouldOpenInNewTab);
						}
					});
					}
				}
			} );
		<?php
		$js .= ob_get_clean();
		return $js;
	}

	/**
	 * Add the JS Script to handle custom URL on click of the Lightbox Caption in the frontend.
	 *
	 * @param string $js          The Current Image Gallery Block JS for Frontend.
	 * @param int    $id          The Block ID.
	 * @param array  $attributes  Array of attributes.
	 * @return string             The updated JS script.
	 *
	 * @since 1.0.0
	 */
	public function render_lightbox_url_frontend_js( $js, $id, $attributes ) {
		ob_start();
		?>
			const regexCustomURL = new RegExp( '^((http|https)://)(www.)?[a-zA-Z0-9@:%._\\+~#?&//=\\-]{2,256}\\.[a-z]{2,6}\\b([-a-zA-Z0-9@:%._\\+~#?&//=]*)$' );
			const customLinks = <?php echo isset( $attributes['customLinks'] ) ? wp_json_encode( $attributes['customLinks'] ) : '{}'; ?>;
			const customLinksBehaviour = <?php echo isset( $attributes['customLinksBehaviour'] ) ? wp_json_encode( $attributes['customLinksBehaviour'] ) : '{}'; ?>;

			const getCustomURL = ( caption ) => {
				if ( ! customLinks ) {
					return undefined;
				}
				const imageID = parseInt( caption.getAttribute( 'data-spectra-gallery-image-id' ) );
				return ( regexCustomURL.test( customLinks[ imageID ] ) ? customLinks[ imageID ] : undefined );
			}

			const getCustomLinkBehaviour = (caption) => {
				if (!customLinksBehaviour) {
					return true;
				}
				const imageId = parseInt(caption.getAttribute('data-spectra-gallery-image-id'));
				return !(customLinksBehaviour[imageId] === true);
			};

			const captions = lightboxSwiper.el.querySelectorAll( '.spectra-image-gallery__control-lightbox--caption' );
			for ( let i = 0; i < captions.length; i++ ) {
				const customURL = getCustomURL( captions[ i ] );
				if ( customURL ) {
					const anchor = document.createElement( 'a' );
					anchor.target = getCustomLinkBehaviour( captions[ i ] ) ? '_blank' : '_self';
					anchor.rel = 'noopener noreferrer';
					anchor.href = customURL;
					anchor.innerHTML = captions[ i ].innerHTML;
					captions[ i ].textContent = '';
					captions[ i ].appendChild( anchor );
				}
			}
		<?php
		$js .= ob_get_clean();
		return $js;
	}

	/**
	 * Add Frontend CSS for the Desktop View.
	 *
	 * @param array $selectors   Array of CSS Selectors.
	 * @param array $attributes  Array of attributes.
	 * @return array             The updated CSS Selector Array.
	 *
	 * @since 1.0.0
	 */
	public function render_frontend_css( $selectors, $attributes ) {
		if ( empty( $attributes['lightboxLinkedCaptionColor'] ) ) {
			$attributes['lightboxLinkedCaptionColor'] = '';
		}
		if ( empty( $attributes['lightboxLinkedCaptionHoverColor'] ) ) {
			$attributes['lightboxLinkedCaptionHoverColor'] = '';
		}
		return array_merge(
			$selectors,
			array(
				'+.spectra-image-gallery__control-lightbox .spectra-image-gallery__control-lightbox--caption a'       => array(
					'color' => $attributes['lightboxLinkedCaptionColor'],
				),
				'+.spectra-image-gallery__control-lightbox .spectra-image-gallery__control-lightbox--caption a:hover' => array(
					'color' => $attributes['lightboxLinkedCaptionHoverColor'],
				),
			)
		);
	}
}
