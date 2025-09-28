<?php
namespace SpectraPro\Includes\Extensions\DynamicContent\Source;

use SpectraPro\Includes\Extensions\DynamicContent\Helper;

/**
 * Posts
 *
 * @package spectra-pro
 * @since 1.0.0
 */
class CustomFields {
	/**
	 * Get Meta Value
	 *
	 * @param int    $post_id required.
	 * @param string $meta_key required.
	 * @param array  $image_settings required.
	 *
	 * @return string|array
	 * @since 1.0.0
	 */
	public static function get_value( $post_id, $meta_key, $image_settings ) {
		if ( empty( $post_id ) || empty( $meta_key ) ) {
			return '';
		}

		// Check if the Advanced Custom Fields (ACF) plugin is active and available.
		if ( class_exists( 'ACF' ) ) {
			// Get all the field objects for the current post.
			$fields = get_field_objects( $post_id );
			// Check if the desired meta key exists in the retrieved fields.
			if ( isset( $fields[ $meta_key ] ) ) {
				// Retrieve the value associated with the specified meta key for the given post.
				$value = get_field( $meta_key, $post_id );
				// Prepare and return the ACF value using a custom function.
				return self::prepare_acf_value( $value, $image_settings );
			}
		}

		// Check if the RW Meta Box (RWMB) plugin is active and if the required function 'rwmb_meta' exists.
		if ( class_exists( 'RW_Meta_Box' ) && function_exists( 'rwmb_meta' ) ) {
			// Check if the provided meta key is associated with RWMB.
			if ( self::is_metabox_meta_key( $meta_key ) ) {
				// Retrieve and return the values for the meta box field using a custom function.
				return self::get_meta_box_field_values( $post_id, $meta_key, $image_settings ); 
			}
		}

		// Check if the Pods plugin is active and available.
		if ( function_exists( 'pods' ) ) {
			// Get the Pods object for the current post type and post ID.
			$pod_object = pods( get_post_type( $post_id ), $post_id );
			// Verify if the $pod_object is an object, and check for specific methods to ensure compatibility with the Pods plugin.
			if ( is_object( $pod_object ) && method_exists( $pod_object, 'exists' ) && $pod_object->exists() && method_exists( $pod_object, 'field' ) ) {
				// Retrieve the field value based on the provided meta key.
				$output = $pod_object->field( $meta_key );
				// Return the image URL from the 'guid' field.
				if ( ! empty( $output['guid'] ) ) {
					return $output['guid'];
				}
			}
		}
		return get_post_meta( $post_id, $meta_key, true );
	}

	/**
	 * Get ACF Fields Based on post_id
	 *
	 * @param int    $post_id required.
	 * @param string $type optional.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public static function get_acf_fields( $post_id, $type = 'text' ) {
		if ( empty( $post_id ) ) {
			return [];
		}
		if ( ! class_exists( 'ACF' ) ) {
			return [];
		}
		$type   = self::get_acf_field_type_by_group( $type );
		$fields = get_field_objects( $post_id );
		$data   = [];
		if ( is_array( $fields ) && count( $fields ) > 0 ) {
			foreach ( $fields as $field ) {
				if ( count( $type ) > 0 && in_array( $field['type'], $type, true ) ) {
					$data[ $field['name'] ] = [
						'label' => $field['label'],
						'value' => $field['name'],
					];
				}
			}
		}
		return $data;
	}

	/**
	 * Get ACF group field array based on request type
	 *
	 * @param string $type optional.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public static function get_acf_field_type_by_group( $type = 'text' ) {

		if ( 'image' === $type ) {
			return array(
				'image',
			);
		} elseif ( 'url' === $type ) {
			return array(
				'text',
				'email',
				'image',
				'file',
				'page_link',
				'url',
				'link',
			);
		}

		return array(
			'text',
			'textarea',
			'number',
			'range',
			'email',
			'url',
			'password',
			'wysiwyg',
			'select',
			'checkbox',
			'radio',
			'true_false',
			'date_picker',
			'time_picker',
			'date_time_picker',
			'color_picker',
		);
	}

	/**
	 * ACF array value to string prepare
	 *
	 * @param array|string $value required.
	 * @param array        $image_settings required.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public static function prepare_acf_value( $value, $image_settings ) {
		if ( is_array( $value ) ) {
			// Check if it's an image array with ID.
			if ( isset( $value['id'] ) ) {
				$image_id     = absint( $value['id'] );
				$img_settings = isset( $image_settings[0] ) ? $image_settings[0] : '';
				$value        = self::get_attachment_image_src( $image_id, $img_settings );
			} else {
				// Convert array to comma-separated string.
				$value = implode( ', ', $value );
			}//end if
		} elseif ( is_numeric( $value ) ) {
			return $value;
		} elseif ( is_string( $value ) && false !== strpos( $value, 'a:' ) && false !== strpos( $value, ';' ) ) {
			// Check for PHP serialized data format - 'a:' indicates an array start and ';' is a serialized data delimiter.
			// This pattern matching helps identify serialized arrays without the overhead of attempting unserialization on non-serialized strings as ACF fields are serialized.
			// This might be a serialized string (common for ACF checkbox fields).
			$unserialized = maybe_unserialize( $value );
			if ( is_array( $unserialized ) ) {
				$value = implode( ', ', $unserialized );
			}//end if
		}//end if
		
		// Apply fallback if value is empty.
		if ( empty( $value ) && isset( $image_settings[1] ) ) {
			return $image_settings[1];
		}
		return $value;
	}

	/**
	 * Check if the meta key is from Meta Box Plugin.
	 *
	 * @param string $meta_key Key to check.
	 * @param string $object_type Key to check.
	 * @since 1.0.1
	 * @return boolean
	 */
	public static function is_metabox_meta_key( $meta_key, $object_type = 'post' ) {
		$meta_box_registry = rwmb_get_registry( 'meta_box' );
		// Get all registered meta boxes.
		$args       = [
			'object_type' => 'post',
		];
		$meta_boxes = $meta_box_registry->get_by( $args );
	
		// Loop through each meta box.
		foreach ( $meta_boxes as $key => $mb_object ) {
			$meta_box = $mb_object->meta_box;
			// Get the fields within the meta box.
			$fields = $meta_box['fields'];
	
			// Loop through each field.
			foreach ( $fields as $field ) {
				// Check if the field has the provided meta key.
				if ( $field['id'] === $meta_key ) {
					return true;
				}
			}
		}
	
		return false;
	}

	/**
	 * Get Meta Box field values
	 *
	 * @param int    $object_id Object ID.
	 * @param string $meta_key Meta key.
	 * @param array  $image_settings Image Settings.
	 * @param array  $args Arguments Array.
	 * @since 1.0.1
	 * @return string Meta Value.
	 */
	public static function get_meta_box_field_values( $object_id, $meta_key, $image_settings, $args = array() ) {
		$field_value = '';
		// Retrieve the custom field values using the Meta Box Plugin's helper function.
		$custom_fields = rwmb_get_value( $meta_key, $args, $object_id );

		if ( ! is_array( $custom_fields ) ) {
			return strval( $custom_fields );
		}
		$field_data = rwmb_get_field_settings( $meta_key, array(), $object_id );
		// If Custom Fields value is in Array, Loop through each custom field.
		foreach ( $custom_fields as $field ) {
			$field_type  = $field_data['type'];
			$field_value = '';
			$image_size  = ! empty( $image_settings[0] ) ? $image_settings[0] : 'full';
	
			switch ( $field_type ) {
				case 'checkbox':
				case 'radio':
					$field_value = isset( $field['checked'] ) ? 'checked' : '';
					break;
				case 'select':
				case 'radio_list':
				case 'checkbox_list':
					$field_value = isset( $field['selected'] ) ? $field['selected'] : '';
					break;
				case 'file':
				case 'file_input':
				case 'file_advanced':
				case 'file_upload':
					// Get the file URL or attachment ID.
					$field_value = isset( $field['url'] ) ? $field['url'] : $field['id'];
					break;
				case 'single_image':
					$field_value = wp_get_attachment_image_url( $custom_fields['ID'], $image_size );
					break;
				case 'image':
				case 'image_advanced':
				case 'image_upload':
				case 'plupload_image':
				case 'thickbox_image':
					// Get the image URL or attachment ID.
					$field_value = wp_get_attachment_image_url( $field['ID'], $image_size );
					break;
				case 'date':
				case 'datetime':
				case 'time':
					$field_value = $field['date'];
					break;
				case 'taxonomy':
				case 'taxonomy_advanced':
					// Get the selected terms.
					$field_value = isset( $field['selected'] ) ? $field['selected'] : '';
					break;
				case 'post':
				case 'post_advanced':
				case 'post_checkbox_list':
				case 'post_select':
					// Get the selected posts.
					$field_value = isset( $field['selected'] ) ? $field['selected'] : '';
					break;
				default:
					$field_value = $field['value'];
					break;
			}//end switch
	
			return $field_value;
		}//end foreach
		return $field_value;
	}

	/**
	 * Attachment id to url convert
	 *
	 * @param int    $attachment_id required.
	 * @param string $size optional.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public static function get_attachment_image_src( $attachment_id, $size = 'full' ) {
		$image = wp_get_attachment_image_src( $attachment_id, $size );
		return ( isset( $image[0] ) ? $image[0] : '' );
	}

	/**
	 * Get author meta value based on post_id and meta_key
	 *
	 * @param int    $post_id required.
	 * @param string $meta_key required.
	 * @param array  $image_settings required.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public static function get_author_meta_value( $post_id, $meta_key, $image_settings ) {
		if ( empty( $post_id ) || empty( $meta_key ) ) {
			return '';
		}
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return '';
		}
		$user_id = intval( $post->post_author );
		$fields  = [];
		if ( class_exists( 'ACF' ) ) {
			$fields = get_field_objects( 'user_' . $user_id );
		}
		if ( isset( $fields[ $meta_key ] ) ) {
			$value = get_field( $meta_key, 'user_' . $user_id );
			return self::prepare_acf_value( $value, $image_settings );
		} elseif ( 'avatar' === $meta_key ) {
			$image = isset( $image_settings['image'] ) ? $image_settings['image'] : [];
			return get_avatar_url(
				$user_id,
				array(
					'size' => ( isset( $image[0] ) ? get_option( $image[0] . '_size_w' ) : '' ),
				)
			);
		} elseif ( 'name' === $meta_key ) {
			return get_the_author_meta( 'display_name', $user_id );
		} elseif ( 'nicename' === $meta_key ) {
			return get_the_author_meta( 'nickname', $user_id );
		}
		return get_the_author_meta( $meta_key, $user_id );
	}

	/**
	 * Get current user meta value based on user_id and meta_key
	 *
	 * @param string $meta_key required.
	 * @param array  $image_settings required.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public static function get_current_user_meta_value( $meta_key, $image_settings ) {
		if ( empty( $meta_key ) ) {
			return '';
		}
		$user_id = get_current_user_id();
		$fields  = [];
		if ( class_exists( 'ACF' ) ) {
			$fields = get_field_objects( 'user_' . $user_id );
		}
		if ( isset( $fields[ $meta_key ] ) ) {
			$value = get_field( $meta_key, 'user_' . $user_id );
			return self::prepare_acf_value( $value, $image_settings );
		}
		// Adding compatibility for Meta Box Plugin.
		if ( class_exists( 'RW_Meta_Box' ) && function_exists( 'rwmb_get_value' ) ) {
			$value = rwmb_get_value( $meta_key, [ 'object_type' => 'user' ], $user_id );
			return is_string( $value ) ? $value : '';
		}
		return Helper::get_current_user_info( $meta_key );
	}

	/**
	 * Get archive meta value based on meta_key
	 *
	 * @param string $meta_key required.
	 * @param array  $image_settings required.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public static function get_archive_meta_value( $meta_key, $image_settings ) {
		if ( empty( $meta_key ) ) {
			return '';
		}
		$post_id = get_queried_object_id();
		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}
		// if not found post id and it is image meta type then add placeholder image.
		if ( empty( $post_id ) && count( $image_settings ) > 0 ) {
			return SPECTRA_PRO_URL . 'assets/images/placeholder.png';
		}
		$acf_meta_field = false;
		if ( class_exists( 'ACF' ) ) {
			$acf_meta_field = get_field( $meta_key, 'term_' . $post_id );
		}
		if ( $acf_meta_field ) {
			return self::prepare_acf_value( $acf_meta_field, $image_settings );
		}
		$value = '';
		if ( ! empty( $meta_key ) ) {
			if ( is_category() || is_tax() ) {
				$value = get_term_meta( get_queried_object_id(), $meta_key, true );
			} elseif ( is_author() ) {
				$value = get_user_meta( get_queried_object_id(), $meta_key, true );
			}
			if ( ! $value ) {
				$image    = ( isset( $advanced_settings['image'] ) ? $advanced_settings['image'] : [] );
				$fallback = ( isset( $image[1] ) && ! empty( $image[1] ) ? esc_url( $image[1] ) : '' );
				if ( $fallback ) {
					return esc_url( $fallback );
				}
			}
		}
		return $value;
	}

	/**
	 * Get user meta fields based on user_id
	 *
	 * @param int $user_id required.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public static function get_user_meta_fields( $user_id ) {
		if ( empty( $user_id ) ) {
			return [];
		}
		$custom_keys       = get_user_meta( $user_id );
		$options           = [];
		$user_exclude_list = array(
			'nickname',
			'first_name',
			'last_name',
			'description',
			'rich_editing',
			'syntax_highlighting',
			'comment_shortcuts',
			'admin_color',
			'use_ssl',
			'show_admin_bar_front',
			'locale',
			'wp_capabilities',
			'wp_user_level',
			'dismissed_wp_pointers',
			'show_welcome_panel',
			'session_tokens',
			'wp_user-settings',
			'wp_user-settings-time',
			'wp_dashboard_quick_press_last_post_id',
			'community-events-location',
			'last_update',
			// Woocommerce.
			'wc_last_active',
			'woocommerce_admin_activity_panel_inbox_last_read',
			'wp_woocommerce_product_import_mapping',
			'wp_product_import_error_log',
			// Elementor.
			'elementor_introduction',
			// Others.
			'nav_menu_recently_edited',
			'managenav-menuscolumnshidden',
			'rtladminbar',
			'metaboxhidden_',
			'enable_custom_fields',
			'metaboxhidden_nav-menus',
		);
		foreach ( $custom_keys as $custom_user_key => $custom_user_data ) {
			$field_label = $custom_user_key;
			if ( '_' !== substr( $custom_user_key, 0, 1 ) &&
					'wp_' !== substr( $custom_user_key, 0, 3 ) &&
					'meta' !== substr( $custom_user_key, 0, 4 ) &&
					( strlen( $custom_user_key ) <= 10 || strlen( $custom_user_key ) > 10 && 'manageedit' !== substr( $custom_user_key, 0, 10 ) ) &&
					! in_array( $custom_user_key, $user_exclude_list, true )
				) {
				// Adding support for metabox plugin.
				if ( function_exists( 'rwmb_get_field_settings' ) ) {
					$field_data = rwmb_get_field_settings( $custom_user_key, array( 'object_type' => 'user' ), $user_id );
					if ( ! empty( $field_data ) ) {
						$field_label = isset( $field_data['name'] ) ? $field_data['name'] : $custom_user_key;
					}
				}
				$options[ $custom_user_key ] = array(
					'value' => $custom_user_key,
					'label' => $field_label,
				);
			}
		}//end foreach
		return $options;
	}
}
