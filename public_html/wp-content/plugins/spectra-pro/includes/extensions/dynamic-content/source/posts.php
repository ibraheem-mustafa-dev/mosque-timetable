<?php
namespace SpectraPro\Includes\Extensions\DynamicContent\Source;

use SpectraPro\Includes\Extensions\DynamicContent\Helper;

/**
 * Posts
 *
 * @package spectra-pro
 * @since 1.0.0
 */
class Posts {
	/**
	 * Get Post data based on source_field type
	 *
	 * @param array      $fields fields required.
	 * @param int|string $post_id Post ID.
	 * @param array      $advanced_settings optional.
	 * @param bool       $backend_request Is request for fetching backend data.
	 * @param string     $char_length The character length assigned by the user.
	 * @return string
	 * @since 1.0.0
	 */
	public static function get_data( $fields, $post_id = 'null', $advanced_settings = [], $backend_request = false, $char_length = '' ) {
		$source_field = $fields[1];
		global $post;
		// Fix for dynamic content not working at frontend: FSE.
		if ( 'null' === $post_id ) {
			if ( isset( $post->ID ) ) {
				$post_id = $post->ID;
			}
		}
		switch ( $source_field ) {
			case 'custom_field':
				$image_settings = ( isset( $advanced_settings['image'] ) ? $advanced_settings['image'] : [] );
				$meta_key       = 'custom_key' === $fields[2] && ! empty( $fields[3] ) ? $fields[3] : $fields[2];
				return CustomFields::get_value( $post_id, $meta_key, $image_settings );
			case 'post_date':
				$format        = ( isset( $fields[3] ) ? $fields[3] : get_option( 'date_format' ) );
				$custom_format = ( isset( $fields[4] ) ? $fields[4] : '' );
				$type          = $fields[2];
				return Helper::get_post_date( $post_id, $type, $format, $custom_format );
			case 'post_excerpt':
				$excerpt = '';
				if ( ! empty( $char_length ) ) {
					$field_data = get_post_field( 'post_excerpt', $post_id );
					$word_count = Helper::get_word_count_from_char_length( $field_data, $char_length );

					add_filter(
						'uagb_loop_excerpt_length',
						function () use ( $word_count ) {
							return $word_count;
						}
					);
				}
				// TODO:Prevent infinite loop when called upon inside render_block. We can Check if there's a better way to prevent infinite loop later.
				if ( ! doing_filter( 'get_the_excerpt' ) ) {
					$length_fallback = apply_filters( 'uagb_loop_excerpt_length', 20 );

					// Ensure length_fallback is an integer.
					$length_fallback = is_int( $length_fallback ) ? $length_fallback : ( is_numeric( $length_fallback ) ? intval( $length_fallback ) : 20 );
					if ( $length_fallback <= 0 ) {
						$length_fallback = 20;
					}

					$excerpt = Helper::get_excerpt( $post_id, strip_shortcodes( $post->post_content ), $length_fallback, $char_length );
				}
				return $excerpt;
			case 'post_ID':
				return $post_id;
			case 'author_archive':
				$author_id = get_post_field( 'post_author', $post_id );
				return get_author_posts_url( absint( $author_id ) );
			case 'post_title':
				return get_the_title( $post_id );
			case 'post_permalink':
				return get_the_permalink( $post_id );
			case 'post_terms':
				$term_name  = ( isset( $fields[2] ) ? $fields[2] : '' );
				$seperator  = ( isset( $fields[3] ) ? $fields[3] : '' );
				$allow_link = filter_var( ( isset( $fields[4] ) ? $fields[4] : false ), FILTER_VALIDATE_BOOLEAN );
				return Helper::get_terms_by_post_id( $post_id, $term_name, $seperator, $allow_link );
			case 'post_time':
				$format        = ( isset( $fields[3] ) ? $fields[3] : get_option( 'time_format' ) );
				$custom_format = ( isset( $fields[4] ) ? $fields[4] : '' );
				$type          = $fields[2];
				return Helper::get_post_time( $post_id, $type, $format, $custom_format );
			case 'featured_image_data':
				$type           = $fields[2];
				$image_settings = ( isset( $advanced_settings['image'] ) ? $advanced_settings['image'] : [] );
				return Helper::get_post_featured_image_data( $post_id, $type, $image_settings );
			case 'nicename':
			case 'description':
			case 'email':
			case 'url':
			case 'avatar':
			case 'name':
			case 'first_name':
			case 'last_name':
				$image_settings = ( isset( $advanced_settings['image'] ) ? $advanced_settings['image'] : [] );
				return CustomFields::get_author_meta_value( $post_id, $fields[1], $image_settings );
			case 'author_info':
				if ( 'avatar' === $fields[2] ) {
					return '';
				}
				$image_settings = ( isset( $advanced_settings['image'] ) ? $advanced_settings['image'] : [] );
				return CustomFields::get_author_meta_value( $post_id, $fields[2], $image_settings );
			case 'comments_area':
				return get_comments_link( $post_id );
			case 'comments_number':
				$type               = $fields[3];
				$no_comments        = ( isset( $fields[2] ) ? $fields[2] : '' );
				$one_comments       = ( isset( $fields[3] ) ? $fields[3] : '' );
				$many_comments      = ( isset( $fields[4] ) ? $fields[4] : '' );
				$allow_comemnt_link = filter_var( ( isset( $fields[5] ) ? $fields[5] : false ), FILTER_VALIDATE_BOOLEAN );
				if ( $backend_request ) {
					return strval( get_comments_number( $post_id ) );
				}
				return Helper::get_post_comment_count_number( $post_id, $no_comments, $one_comments, $many_comments, $allow_comemnt_link );
		}//end switch
		return '';
	}
}
