<?php
namespace SpectraPro\Includes\Extensions\DynamicContent\Source;

use SpectraPro\Includes\Extensions\DynamicContent\Helper;

/**
 * Site
 *
 * @package spectra-pro
 * @since 1.0.0
 */
class Site {
	/**
	 * Get Site data based on source_field type
	 *
	 * @param array $fields required.
	 * @param array $advanced_settings optional.
	 * @return string
	 * @since 1.0.0
	 */
	public static function get_data( $fields, $advanced_settings = [] ) {
		$source_field = ( isset( $fields[1] ) ? $fields[1] : '' );
		switch ( $source_field ) {
			case 'archive_description':
				$value = wp_kses_post( get_the_archive_description() );
				return $value;
			case 'archive_title':
				$include_context = ( isset( $fields[2] ) ? boolval( $fields[2] ) : false );
				$value           = Helper::get_page_title( $include_context );
				return $value;
			case 'archive_meta':
				$image_settings = ( isset( $advanced_settings['image'] ) ? $advanced_settings['image'] : [] );
				$meta_key       = isset( $fields[3] ) ? $fields[3] : ''; 
				return CustomFields::get_archive_meta_value( $meta_key, $image_settings );
			case 'site_tagline':
				return get_bloginfo( 'description' );
			case 'site_title':
				return get_bloginfo();
			case 'site_logo':
				$image       = ( isset( $advanced_settings['image'] ) ? $advanced_settings['image'] : [] );
				$fallback    = ( isset( $image[1] ) && ! empty( $image[1] ) ? esc_url( $image[1] ) : '' );
				$custom_logo = get_theme_mod( 'custom_logo' );
				if ( $custom_logo ) {
					return esc_url( wp_get_attachment_url( get_theme_mod( 'custom_logo' ) ) );
				} elseif ( $fallback ) {
					return esc_url( $fallback );
				}
				return '';
			case 'request_parameter':
				$type       = ( isset( $fields[2] ) ? strtoupper( $fields[2] ) : false );
				$param_name = ( isset( $fields[3] ) ? $fields[3] : '' );
				return Helper::get_data_by_request_param( $type, $param_name );
			case 'shortcode':
				return do_shortcode( html_entity_decode( $fields[2] ) );
			case 'user_info':
				$image_settings = ( isset( $advanced_settings['image'] ) ? $advanced_settings['image'] : [] );
				return CustomFields::get_current_user_meta_value( $fields[2], $image_settings );
		}//end switch
		return '';
	}
}
