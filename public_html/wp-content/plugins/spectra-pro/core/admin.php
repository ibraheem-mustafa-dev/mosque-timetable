<?php
namespace SpectraPro\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Admin
 *
 * @package spectra-pro
 * @since 1.0.0
 */
class Admin {

	/**
	 * Micro Constructor
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public static function init() {

		$self = new self();
		// Activation hook.
		add_action( 'admin_init', array( $self, 'activation_redirect' ) );

		// Needs this as we need to upload svg files for svg picker.
		add_filter( 'upload_mimes', array( $self, 'custom_upload_mimes' ) ); // phpcs:ignore WordPressVIPMinimum.Hooks.RestrictedHooks.upload_mimes

		add_filter( 'wp_prepare_attachment_for_js', array( $self, 'prepare_attachment_for_js' ), 10, 1 );
	}
	/**
	 * Localize inputs to the Spectra Admin Dashboard.
	 *
	 * @Hooked - uag_react_admin_localize
	 *
	 * @param array $localize Localized Filtered Variable.
	 * @return array $localize Updated Localize.
	 * @since 1.0.0
	 */
	public static function uag_localize_admin_dashboard( $localize ) {
		$localize['license_activation_nonce']   = wp_create_nonce( 'uag_license_activation' );
		$localize['license_deactivation_nonce'] = wp_create_nonce( 'uag_license_deactivation' );
		return $localize;
	}

	/**
	 * Activation Redirect
	 *
	 * @Hooked - admin_init
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function activation_redirect() {
		$do_redirect = apply_filters( 'spectra_pro_enable_redirect_activation', get_option( '__spectra_pro_do_redirect' ) );
		if ( $do_redirect ) {

			update_option( '__spectra_pro_do_redirect', false );

			if ( ! is_multisite() ) {
				wp_safe_redirect(
					add_query_arg(
						array(
							'page'                        => UAGB_SLUG,
							'path'                        => 'settings',
							'spectra-activation-redirect' => true,
						),
						admin_url( 'admin.php' )
					)
				);
				exit();
			}
		}
	}

	/**
	 * Permits upload of svg files
	 *
	 * @param array $mimes allow mimes.
	 * @return array
	 * @since 1.0.0
	 */
	public function custom_upload_mimes( $mimes ) {
		// Allows svg files.
		if ( current_user_can( 'administrator' ) ) {
			$mimes['svg'] = 'image/svg+xml';
		}

		return $mimes;
	}

	/**
	 * Adds svg content to attachment data for svg images
	 *
	 * @param array $attachment attachment data.
	 * @return array
	 * @since 1.0.0
	 */
	public function prepare_attachment_for_js( $attachment ) {
		if ( 'image/svg+xml' !== $attachment['mime'] ) {
			return $attachment;
		}

		$attachment['svg'] = self::get_attachment_svg( $attachment['id'] );
		return $attachment;
	}

	/**
	 * Return content from uploaded file.
	 *
	 * @param integer $id attachment id.
	 * @return string
	 * @since 1.0.0
	 */
	public static function get_attachment_svg( $id ) {
		$svg = get_post_meta( $id, '_uagb_svg' );
		if ( ! empty( $svg ) && is_string( $svg ) && '' !== trim( $svg ) ) {
			return $svg;
		}

		$svg_path = get_attached_file( $id );

		if ( false === $svg_path ) {
			return false;
		}

		$svg = file_get_contents( $svg_path ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents,WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
		if ( false === $svg || empty( trim( $svg ) ) ) {
			return false;
		}

		update_post_meta( $id, '_uagb_svg', $svg );
		return $svg;
	}
}
