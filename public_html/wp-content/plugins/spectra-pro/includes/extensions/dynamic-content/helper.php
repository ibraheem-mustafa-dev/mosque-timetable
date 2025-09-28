<?php
namespace SpectraPro\Includes\Extensions\DynamicContent;

use SpectraPro\Includes\Extensions\DynamicContent\Source\Posts;
use SpectraPro\Includes\Extensions\DynamicContent\Source\Site;
/**
 * Helper
 *
 * @package spectra-pro
 * @since 1.0.0
 */
class Helper {
	/**
	 * Get Taxonomary Data Based On Post Type
	 *
	 * @param array  $args post_type and other information.
	 * @param string $output Optional.taxonomies output data field name.
	 * @param string $operator Optional. The logical operation to perform. 'AND' means
	 *                              all elements from the array must match. 'OR' means only
	 *                              one element needs to match. 'NOT' means no elements may
	 *                              match. Default 'AND'.
	 * @return array
	 * @since 1.0.0
	 */
	public static function get_taxonomies( $args = [], $output = 'names', $operator = 'and' ) {
		global $wp_taxonomies;

		$field = ( 'names' === $output ) ? 'name' : false;

		// Handle 'object_type' separately.
		if ( isset( $args['object_type'] ) ) {
			$object_type = (array) $args['object_type'];
			unset( $args['object_type'] );
		}

		$taxonomies = wp_filter_object_list( $wp_taxonomies, $args, $operator );

		if ( isset( $object_type ) ) {
			foreach ( $taxonomies as $tax => $tax_data ) {
				if ( ! array_intersect( $object_type, $tax_data->object_type ) ) {
					unset( $taxonomies[ $tax ] );
				}
			}
		}

		$output = [];
		if ( $field ) {
			foreach ( $taxonomies as $taxonomie ) {
				$output[] = array(
					'label' => $taxonomie->label,
					'value' => $taxonomie->$field,
				);
			}
		}
		return $output;
	}

	/**
	 * Get post date by post id
	 *
	 * @param int    $post_id required.
	 * @param string $date_type required.
	 * @param string $format required.
	 * @param string $custom_format optional.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public static function get_post_date( $post_id, $date_type, $format, $custom_format = '' ) {
		if ( 'human_readable' === $format ) {
			$post       = get_post( $post_id );
			$date_value = isset( $post->{$date_type} ) ? $post->{$date_type} : '';
			/* translators: %s: human readable time string. */
			$value = sprintf( esc_html__( '%s ago', 'spectra-pro' ), human_time_diff( strtotime( $date_value ) ) );
		} else {
			switch ( $format ) {
				case 'custom':
					$date_format = $custom_format;
					break;
				default:
					$date_format = $format;
					break;
			}
			if ( 'post_date_gmt' === $date_type ) {
				$value = get_the_date( $date_format, $post_id );
			} else {
				$value = get_the_modified_date( $date_format, $post_id );
			}
		}//end if
		return $value;
	}

	/**
	 * Get post time by post id
	 *
	 * @param int    $post_id required.
	 * @param string $time_type required.
	 * @param string $format required.
	 * @param string $custom_format optional.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public static function get_post_time( $post_id, $time_type, $format, $custom_format = '' ) {
		$value = '';
		switch ( $format ) {
			case 'custom':
				$date_format = $custom_format;
				break;
			default:
				$date_format = $format;
				break;
		}

		if ( 'post_date_gmt' === $time_type ) {
			$value = get_the_time( $date_format, $post_id );
		} else {
			$value = get_the_modified_time( $date_format, $post_id );
		}
		return $value;
	}

	/**
	 * Get data by request param
	 *
	 * @param string $request_type required.
	 * @param string $param_name required.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public static function get_data_by_request_param( $request_type = '', $param_name = '' ) {
		$value = '';
		if ( ! $param_name || ! $request_type ) {
			return '';
		}
		switch ( $request_type ) {
			case 'POST':
				// Phpcs ignore comment is required as nonce verification is exempted for this custom POST request.
				$value = isset( $_POST[ $param_name ] ) ? sanitize_text_field( $_POST[ $param_name ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification is intentionally skipped for this custom request.
				break;
			case 'GET':
				// Phpcs ignore comment is required as nonce verification is exempted for this custom GET request.
				$value = isset( $_GET[ $param_name ] ) ? sanitize_text_field( $_GET[ $param_name ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification is intentionally skipped for this custom request.
				break;
			case 'QUERY_VAR':
				$value = get_query_var( $param_name );
				break;
		}
		return $value;
	}

	/**
	 * Get current user meta data
	 *
	 * @param string $type required.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public static function get_current_user_info( $type ) {
		$user = wp_get_current_user();
		if ( empty( $type ) || 0 === $user->ID ) {
			return;
		}

		$value = '';
		switch ( $type ) {
			case 'login':
			case 'email':
			case 'url':
			case 'nicename':
				$field = 'user_' . $type;
				$value = isset( $user->$field ) ? $user->$field : '';
				break;
			case 'id':
				$value = $user->ID;
				break;
			case 'description':
			case 'first_name':
			case 'last_name':
			case 'display_name':
				$value = isset( $user->$type ) ? $user->$type : '';
				break;
			case 'meta':
				$key = $type;
				if ( ! empty( $key ) ) {
					$value = get_user_meta( $user->ID, $key, true );
				}
				break;
		}//end switch
		return $value;
	}

	/**
	 * Get terms by post id
	 *
	 * @param int    $post_id required.
	 * @param string $term_name required.
	 * @param string $seperator required.
	 * @param bool   $allow_link required.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public static function get_terms_by_post_id( $post_id, $term_name, $seperator, $allow_link = false ) {
		$value = '';
		if ( true === $allow_link ) {
			$value = get_the_term_list( $post_id, $term_name, '', $seperator . ' ' );
		} else {
			$terms = get_the_terms( $post_id, $term_name );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				$term_names = [];
				foreach ( $terms as $term ) {
					$term_names[] = $term->name;
				}
				$value = implode( $seperator . ' ', $term_names );
			}
		}
		return $value;
	}

	/**
	 * Get page title
	 *
	 * @param bool $include_context optional.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public static function get_page_title( $include_context = true ) {
		$title = '';

		if ( is_singular() ) {
			$title = get_the_title();
			if ( $include_context ) {
				$post_type_obj = get_post_type_object( get_post_type() );
				$title         = sprintf( '%s: %s', $post_type_obj->labels->singular_name, $title );
			}
		} elseif ( is_search() ) {
			/* translators: %s: Search term. */
			$title = sprintf( esc_html__( 'Search Results for: %s', 'spectra-pro' ), get_search_query() );

			if ( get_query_var( 'paged' ) ) {
				/* translators: %s is the page number. */
				$title .= sprintf( esc_html__( '&nbsp;&ndash; Page %s', 'spectra-pro' ), get_query_var( 'paged' ) );
			}
		} elseif ( is_category() ) {
			$title = single_cat_title( '', false );

			if ( $include_context ) {
				/* translators: Category archive title. 1: Category name */
				$title = sprintf( esc_html__( 'Category: %s', 'spectra-pro' ), $title );
			}
		} elseif ( is_tag() ) {
			$title = single_tag_title( '', false );
			if ( $include_context ) {
				/* translators: Tag archive title. 1: Tag name */
				$title = sprintf( esc_html__( 'Tag: %s', 'spectra-pro' ), $title );
			}
		} elseif ( is_author() ) {
			$title = '<span class="vcard">' . get_the_author() . '</span>';

			if ( $include_context ) {
				/* translators: Author archive title. 1: Author name */
				$title = sprintf( esc_html__( 'Author: %s', 'spectra-pro' ), $title );
			}
		} elseif ( is_year() ) {
			$title = get_the_date( _x( 'Y', 'yearly archives date format', 'spectra-pro' ) );

			if ( $include_context ) {
				/* translators: Yearly archive title. 1: Year */
				$title = sprintf( esc_html__( 'Year: %s', 'spectra-pro' ), $title );
			}
		} elseif ( is_month() ) {
			$title = get_the_date( _x( 'F Y', 'monthly archives date format', 'spectra-pro' ) );

			if ( $include_context ) {
				/* translators: Monthly archive title. 1: Month name and year */
				$title = sprintf( esc_html__( 'Month: %s', 'spectra-pro' ), $title );
			}
		} elseif ( is_day() ) {
			$title = get_the_date( _x( 'F j, Y', 'daily archives date format', 'spectra-pro' ) );

			if ( $include_context ) {
				/* translators: Daily archive title. 1: Date */
				$title = sprintf( esc_html__( 'Day: %s', 'spectra-pro' ), $title );
			}
		} elseif ( is_tax( 'post_format' ) ) {
			if ( is_tax( 'post_format', 'post-format-aside' ) ) {
				$title = _x( 'Asides', 'post format archive title', 'spectra-pro' );
			} elseif ( is_tax( 'post_format', 'post-format-gallery' ) ) {
				$title = _x( 'Galleries', 'post format archive title', 'spectra-pro' );
			} elseif ( is_tax( 'post_format', 'post-format-image' ) ) {
				$title = _x( 'Images', 'post format archive title', 'spectra-pro' );
			} elseif ( is_tax( 'post_format', 'post-format-video' ) ) {
				$title = _x( 'Videos', 'post format archive title', 'spectra-pro' );
			} elseif ( is_tax( 'post_format', 'post-format-quote' ) ) {
				$title = _x( 'Quotes', 'post format archive title', 'spectra-pro' );
			} elseif ( is_tax( 'post_format', 'post-format-link' ) ) {
				$title = _x( 'Links', 'post format archive title', 'spectra-pro' );
			} elseif ( is_tax( 'post_format', 'post-format-status' ) ) {
				$title = _x( 'Statuses', 'post format archive title', 'spectra-pro' );
			} elseif ( is_tax( 'post_format', 'post-format-audio' ) ) {
				$title = _x( 'Audio', 'post format archive title', 'spectra-pro' );
			} elseif ( is_tax( 'post_format', 'post-format-chat' ) ) {
				$title = _x( 'Chats', 'post format archive title', 'spectra-pro' );
			}
		} elseif ( is_post_type_archive() ) {
			$title = post_type_archive_title( '', false );

			if ( $include_context ) {
				/* translators: Post type archive title. 1: Post type name */
				$title = sprintf( esc_html__( 'Archives: %s', 'spectra-pro' ), $title );
			}
		} elseif ( is_tax() ) {
			$title = single_term_title( '', false );

			if ( $include_context ) {
				$tax = get_taxonomy( get_queried_object()->taxonomy );
				/* translators: Taxonomy term archive title. 1: Taxonomy singular name, 2: Current taxonomy term */
				$title = sprintf( esc_html__( '%1$s: %2$s', 'spectra-pro' ), $tax->labels->singular_name, $title );
			}
		} elseif ( is_archive() ) {
			$title = esc_html__( 'Archives', 'spectra-pro' );
		} elseif ( is_404() ) {
			$title = esc_html__( 'Page Not Found', 'spectra-pro' );
		}//end if

		return $title;
	}

	/**
	 * Get comment number by post id
	 *
	 * @param int    $post_id required.
	 * @param string $no_comments required.
	 * @param string $one_comments required.
	 * @param string $many_comments required.
	 * @param bool   $allow_comemnt_link optional.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public static function get_post_comment_count_number( $post_id, $no_comments, $one_comments, $many_comments, $allow_comemnt_link = false ) {
		$comments_number = get_comments_number( $post_id );
		if ( ! $comments_number ) {
			$count = $no_comments;
		} elseif ( 1 === $comments_number ) {
			$count = $one_comments;
		} else {
			$count = strtr(
				$many_comments,
				[
					'{number}' => number_format_i18n( $comments_number ),
				]
			);
		}

		if ( true === $allow_comemnt_link ) {
			$count = sprintf( '<a href="%s">%s</a>', get_comments_link(), $count );
		}
		return $count;
	}

	/**
	 * Get featured image info by post id
	 *
	 * @param int    $post_id required.
	 * @param string $type required.
	 * @param array  $settings optional.
	 * @return string
	 * @since 1.0.0
	 */
	public static function get_post_featured_image_data( $post_id, $type, $settings = [] ) {
		$attachment_id = get_post_thumbnail_id( $post_id );
		if ( ! $attachment_id ) {
			$fallback = ( isset( $settings[1] ) ? esc_url( $settings[1] ) : '' );
			return $fallback;
		}

		$attachment = get_post( $attachment_id );
		if ( ! $attachment instanceof \WP_Post ) {
			return '';
		}
		$value = '';
		switch ( $type ) {
			case 'alt':
				$value = get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true );
				break;
			case 'caption':
				$value = $attachment->post_excerpt;
				break;
			case 'description':
				$value = $attachment->post_content;
				break;
			case 'href':
				$value = get_permalink( $attachment->ID );
				break;
			case 'src':
				$size  = ( isset( $settings[0] ) ? $settings[0] : '' );
				$value = wp_get_attachment_image_src( $attachment_id, $size );
				if ( $value ) {
					$value = esc_url( $value[0] );
				}
				break;
			case 'title':
				$value = $attachment->post_title;
				break;
		}//end switch
		return $value;
	}

	/**
	 * Check Dynamic Content Enable or not.
	 *
	 * @param array  $block_attr required.
	 * @param string $type optional.
	 * @return bool
	 * @since 1.0.0
	 */
	public static function has_enable_dynamic_content( $block_attr, $type = 'bgImage' ) {
		if ( isset( $block_attr['dynamicContent'] ) && isset( $block_attr['dynamicContent'][ $type ]['enable'] ) && true === $block_attr['dynamicContent'][ $type ]['enable'] ) {
			return true;
		}
		return false;
	}

	/**
	 * Get Dynamic Content Based on block attributes.
	 *
	 * @param array $dynamic_attr required.
	 * @return string
	 * @since 1.0.0
	 */
	public static function get_dynamic_content_from_attributes( $dynamic_attr ) {
		$dynamic_content = '';
		$fields          = ( isset( $dynamic_attr['bgImage']['field'] ) ? explode( '|', $dynamic_attr['bgImage']['field'] ) : [] );
		$image           = ( isset( $dynamic_attr['bgImage']['advanced'] ) ? explode( '|', $dynamic_attr['bgImage']['advanced'] ) : [] );
		$post_id         = ( isset( $dynamic_attr['bgImage']['postId'] ) ? absint( $dynamic_attr['bgImage']['postId'] ) : 'null' );
		$source          = $fields[0];
		if ( ( 'current_post' === $source || 'other_posts' === $source ) ) {
			$post_id         = 'current_post' === $source ? 'null' : $post_id;
			$dynamic_content = Posts::get_data( $fields, $post_id, [ 'image' => $image ] );
		} else {
			$dynamic_content = Site::get_data( $fields, [ 'image' => $image ] );
		}
		return $dynamic_content;
	}

	/**
	 * Get Dynamic Content Based on block attributes.
	 *
	 * @param array $dynamic_attr required.
	 * @return string
	 * @since 1.0.0
	 */
	public static function get_dynamic_content_from_dc_attributes( $dynamic_attr ) {
		$dynamic_content = '';
		$fields          = ( isset( $dynamic_attr['field'] ) ? explode( '|', $dynamic_attr['field'] ) : [] );
		$advanced        = ( isset( $dynamic_attr['advanced'] ) ? explode( '|', $dynamic_attr['advanced'] ) : [] );
		$post_id         = ( isset( $dynamic_attr['postId'] ) && 'current_post' !== $fields[0] ? absint( $dynamic_attr['postId'] ) : 'null' );
		$source          = ( isset( $fields[0] ) ? $fields[0] : '' );
		if ( ( 'current_post' === $source || 'other_posts' === $source ) ) {
			$dynamic_content = Posts::get_data( $fields, $post_id, [ 'image' => $advanced ] );
		} else {
			$dynamic_content = Site::get_data( $fields, [ 'image' => $advanced ] );
		}
		if ( empty( $dynamic_content ) && isset( $advanced[1] ) ) {
			return $advanced[1];
		}
		return $dynamic_content;
	}

	/**
	 * Get decoded HTML entities and shortcodes in a string.
	 *
	 * @param string $string Input String.
	 * @since 1.0.0
	 * @return string Decoded String.
	 */
	public static function get_decoded_string( $string ) {
		// Decode HTML entities.
		$string = html_entity_decode( $string );

		// Decode shortcodes.
		$pattern = '/\[(.*?)\]/';
		preg_match_all( $pattern, $string, $matches );
		if ( ! empty( $matches[0] ) ) {
			$shortcodes = array_unique( $matches[0] );
			$decoded    = array_map( 'do_shortcode', $shortcodes );
			$string     = str_replace( $shortcodes, $decoded, $string );
		}

		return $string;
	}

	/**
	 * Get the excerpt.
	 *
	 * @param int    $post_id          for the block.
	 * @param string $content          for post content.
	 * @param int    $length_fallback  for excerpt, after fallback has been done.
	 * @param string $char_length      for the character length applied.
	 *
	 * @since 1.2.0
	 * @return string $excerpt for specific post.
	 */
	public static function get_excerpt( $post_id, $content, $length_fallback, $char_length ) {
		// If there's an excerpt provided from meta, use it.
		$excerpt = get_post_field( 'post_excerpt', $post_id );

		if ( empty( $excerpt ) ) { // If no excerpt provided from meta.
			$max_excerpt = 20;
			// If the content is present on the post, then trim it and use that.
			if ( ! empty( $content ) ) {
				/**
				 * Adding this comment to avoid mixed return type.
				 *
				 * @var string $excerpt The filtered excerpt.
				 */
				$excerpt = apply_filters( 'the_excerpt', $content );
				if ( ! is_string( $excerpt ) ) {
					$excerpt = ''; // Fallback to an empty string.
				}
			}

			$excerpt = preg_replace( '/<\/?p>/', '', $excerpt );
			
			if ( isset( $excerpt ) ) {
				$excerpt = wp_strip_all_tags( $excerpt );
			}
		}//end if

		// Trim the excerpt.
		if ( ! empty( $excerpt ) && is_string( $excerpt ) ) {
			// Store the original excerpt before splitting.
			$original_excerpt = $excerpt;
		
			// Split the excerpt into words.
			$words = explode( ' ', $excerpt );
		
			if ( count( $words ) >= $length_fallback ) {
				if ( '' === $char_length ) {
					$excerpt = implode( ' ', array_slice( $words, 0, $length_fallback ) );
				} else {
					$excerpt = implode( ' ', $words );
				}
			} else {
				// If the word count is within the limit, use the original text.
				$excerpt = implode( ' ', $words );
			}
		
			$excerpt = preg_replace( '/<\/?p>/', '', $excerpt );
		}//end if

		return empty( $excerpt ) ? '' : esc_html( $excerpt );

	}

	/**
	 * Get the word count based on character count.
	 *
	 * @param string     $txt The text to calculate words.
	 * @param int|string $char_length Character length provided by the user.
	 * @since            1.2.1
	 * @return int
	 */
	public static function get_word_count_from_char_length( $txt, $char_length ) {
		// Decode HTML entities.
		$txt = html_entity_decode( $txt );
	
		// Ensure $char_length is a number.
		if ( ! is_numeric( $char_length ) ) {
			return str_word_count( trim( $txt ) ); // Fallback to full word count if invalid input.
		}
	
		$char_length = (int) $char_length;
	
		// If the input text is within the character limit, return total word count.
		if ( strlen( $txt ) <= $char_length ) {
			return str_word_count( trim( $txt ) );
		}
	
		// Trim text to the given character length without breaking words.
		$limited_text = substr( $txt, 0, $char_length );
	
		$last_space_pos = strrpos( $limited_text, ' ' );
		if ( false !== $last_space_pos ) {
			$limited_text = substr( $limited_text, 0, $last_space_pos );
		}
	
		// Count words in the trimmed text.
		return str_word_count( trim( $limited_text ) );
	}   
}
