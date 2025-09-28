<?php
namespace SpectraPro\Includes\Extensions\DynamicContent;

use SpectraPro\Includes\Extensions\DynamicContent\Source\Posts;
use SpectraPro\Includes\Extensions\DynamicContent\Source\Site;
use SpectraPro\Includes\Extensions\DynamicContent\Source\CustomFields;
use SpectraPro\Includes\Extensions\DynamicContent\Helper;
use SpectraPro\Core\Utils;
use SpectraPro\Core\Helper as PRO_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * DynamicContent
 *
 * @package spectra-pro
 * @since 1.0.0
 */
class DynamicContent {
	/**
	 * Rest API Base namespace.
	 *
	 * @var string $namespace
	 * @since 1.0.0
	 */
	private $namespace = 'uagpro_dc/v1';

	/**
	 * Micro Constructor
	 */
	public static function init() {
		$self = new self();
		add_filter( 'render_block', array( $self, 'render_dynamic_content' ), 10, 2 );
		add_filter( 'spectra_buttons_child_content', array( $self, 'buttons_child_content' ), 10, 3 );
		add_filter( 'uagb_google_map_block_attributes', array( $self, 'google_map_block_attributes' ) );
		add_action( 'rest_api_init', [ $self, 'register_route' ] );
	}

	/**
	 * Get Dynamic Content Status
	 *
	 * @return boolean
	 * @since 1.0.0
	 */
	public function is_enabled_dynamic_content() {
		return 'enabled' === apply_filters( 'enable_dynamic_content', \UAGB_Admin_Helper::get_admin_settings_option( 'uag_enable_dynamic_content', 'enabled' ) );
	}

	/**
	 * Register All Routes
	 *
	 * @hooked - rest_api_init
	 * @return void
	 * @since 1.0.0
	 */
	public function register_route() {
		if ( ! $this->is_enabled_dynamic_content() ) {
			return;
		}
		/**
		 * Get Post By Search Keyword
		 */
		register_rest_route(
			$this->namespace,
			'/search_posts',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_search_items' ),
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
					'args'                => array(
						'post_type' => array(
							'sanitize_callback' => 'sanitize_text_field',
						),
						'keyword'   => array(
							'sanitize_callback' => 'sanitize_text_field',
						),
						'limit'     => array(
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
		/**
		 * Get Post By ID
		 */
		register_rest_route(
			$this->namespace,
			'/post',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_post_item' ),
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
					'args'                => array(
						'ID' => array(
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
		/**
		 * Get Custom Fields by post id
		 */
		register_rest_route(
			$this->namespace,
			'/custom_fields',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_custom_fields' ),
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
					'args'                => array(
						'post_id' => array(
							'sanitize_callback' => 'sanitize_text_field',
						),
						'type'    => array(
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
		/**
		 * Get Custom User Meta Fields
		 */
		register_rest_route(
			$this->namespace,
			'/user_custom_fields',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_user_custom_fields' ),
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
					'args'                => array(
						'post_id' => array(
							'sanitize_callback' => 'sanitize_text_field',
						),
						'type'    => array(
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		/**
		 * Get terms by post id
		 */
		register_rest_route(
			$this->namespace,
			'/terms',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_terms' ),
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
					'args'                => array(
						'post_id' => array(
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		/**
		 * Get term Meta by get_queried_object
		 */
		register_rest_route(
			$this->namespace,
			'/term_meta',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_term_meta' ),
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
					'args'                => array(
						'post_id' => array(
							'sanitize_callback' => 'sanitize_text_field',
						),
						'type'    => array(
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		/**
		 * Get dynamic content based on post id and fields
		 */
		register_rest_route(
			$this->namespace,
			'/dynamic_content',
			array(
				array(
					'methods'             => 'GET,POST',
					'callback'            => array( $this, 'get_dynamic_content' ),
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
					'args'                => array(
						'field' => array(
							'sanitize_callback' => 'sanitize_text_field',
						),
						'image' => array(
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		$post_type = \UAGB_Helper::get_post_types();
		foreach ( $post_type as $key => $value ) {
			/**
			 * Add metas to the rest API Inputs.
			 */
			register_rest_field(
				$value['value'],
				'spectra_custom_meta',
				array(
					'get_callback' => array( $this, 'get_post_meta_values' ),
					'schema'       => null,
				)
			);
		}

		if ( class_exists( 'RW_Meta_Box' ) && ! class_exists( 'MB_Rest_API' ) ) {
			// Add compatibility for Meta Box Plugin.
			register_rest_field(
				'user',
				'meta_box',
				array(
					'get_callback' => array( $this, 'get_user_meta' ),
					'schema'       => null,
				)
			);

			// Add compatibility for Meta Box Plugin.
			register_rest_field(
				'post',
				'meta_box',
				array(
					'get_callback' => array( $this, 'metabox_get_post_meta' ),
					'schema'       => null,
				)
			);
		}//end if
	}

	/**
	 * Get user meta for the rest API.
	 *
	 * @param array $object User object.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_user_meta( $object ) {
		$meta_boxes = rwmb_get_registry( 'meta_box' )->get_by( [ 'object_type' => 'user' ] );

		// Ignore MB User Profile meta boxes.
		$meta_boxes = array_filter(
			$meta_boxes,
			function( $meta_box ) {
				return ! in_array(
					$meta_box->id,
					[
						'rwmb-user-register',
						'rwmb-user-login',
						'rwmb-user-lost-password',
						'rwmb-user-reset-password',
						'rwmb-user-info',
					],
					true
				);
			}
		);

		return $this->get_values( $meta_boxes, $object['id'], [ 'object_type' => 'user' ] );
	}

	/**
	 * Get user meta for the rest API.
	 *
	 * @param array $object User object.
	 *
	 * @since 1.0.1
	 * @return array
	 */
	public function metabox_get_post_meta( $object ) {
		$post_id   = $object['id'];
		$post_type = get_post_type( $post_id );
		if ( ! $post_type ) {
			return [];
		}

		$meta_boxes = rwmb_get_registry( 'meta_box' )->get_by( [ 'object_type' => 'post' ] );
		$meta_boxes = array_filter(
			$meta_boxes,
			function( $meta_box ) use ( $post_type ) {
				return in_array( $post_type, $meta_box->post_types, true );
			}
		);

		return $this->get_values( $meta_boxes, $post_id );
	}

	/**
	 * Get all fields' values from list of meta boxes.
	 *
	 * @param array $meta_boxes Array of meta box object.
	 *
	 * @param int   $object_id  Object ID.
	 * @param array $args       Additional params for helper function.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function get_values( $meta_boxes, $object_id, $args = [] ) {
		$fields = [];
		foreach ( $meta_boxes as $meta_box ) {
			$fields = array_merge( $fields, $meta_box->fields );
		}

		// Remove fields with hide_from_rest = true.
		$fields = array_filter(
			$fields,
			function( $field ) {
				return empty( $field['hide_from_rest'] );
			}
		);

		$values = [];
		foreach ( $fields as $field ) {
			$value                  = CustomFields::get_meta_box_field_values( $object_id, $field['id'], array(), $args );
			$values[ $field['id'] ] = $value;
		}

		return $values;
	}

	/**
	 * Get Post Meta Values.
	 *
	 * @param array            $object Post Array.
	 * @param string           $field_name Meta Field Name.
	 * @param \WP_REST_Request $request Request Object.
	 *
	 * @since 1.0.0
	 * @return mixed post_meta.
	 */
	public function get_post_meta_values( $object, $field_name, $request ) {
		$post_id = $object['id'];

		// Check if post meta cache is already set for this post.
		if ( ! isset( $GLOBALS['post_meta_cache'][ $post_id ] ) ) {
			$post_ids = array( $post_id );
			$post_ids = array_merge( $post_ids, get_post_ancestors( $post_id ) );

			update_meta_cache( 'post', $post_ids );
		}

		// Retrieve the post meta values from the cache.
		$meta = get_post_meta( $post_id );

		return $meta;
	}

	/**
	 * Get Post by ID
	 *
	 * @param object $request Rest API request param.
	 * @return array|null
	 * @since 1.0.0
	 */
	public function get_post_item( $request ) {
		$post_id = (int) $request->get_param( 'ID' );
		$post    = get_post( $post_id );
		return new \WP_REST_Response( $post, 200 );
	}

	/**
	 * Get Posts by keywords
	 *
	 * @param object $request Rest API request param.
	 * @return array|null
	 * @since 1.0.0
	 */
	public function get_search_items( $request ) {
		$params    = $request->get_params();
		$post_type = ( isset( $params['post_type'] ) ? $params['post_type'] : 'any' );
		$search    = ( isset( $params['keyword'] ) ? $params['keyword'] : '' );
		$limit     = ( isset( $params['limit'] ) ? intval( $params['limit'] ) : 5 );
		$args      = array(
			'posts_per_page' => $limit,
			'post_type'      => $post_type,
		);
		if ( $search ) {
			$args['s'] = $search;
		}
		$response = get_posts( $args );

		return new \WP_REST_Response( $response, 200 );
	}

	/**
	 * Get field type by keys.
	 *
	 * @param string $type_key Meta Box type key.
	 * @since 1.0.1
	 * @return string Type Key.
	 */
	public function meta_box_get_field_type( $type_key ) {
		$field_types = array(
			'image' => array(
				'image',
				'image_advanced',
				'image_upload',
				'single_image',
				'url',
				'file',
				'file_advanced',
				'file_input',
				'file_upload',
			),
			'text'  => array(
				'text',
				'email',
				'number',
				'textaraa',
				'select',
				'radio',
				'checkbox',
				'checkbox_list',
			),
			'url'   => array(
				'url',
				'file',
				'file_advanced',
				'file_input',
				'file_upload',
			),
		);

		foreach ( $field_types as $field_type => $type_values ) {
			if ( in_array( $type_key, $type_values, true ) ) {
				return $field_type;
			}
		}

		return $type_key;
	}

	/**
	 * Get Custom Field data by post id
	 *
	 * @param object $request Rest API request param.
	 * @return array
	 * @since 1.0.0
	 */
	public function get_custom_fields( $request ) {
		$params    = $request->get_params();
		$post_id   = ( isset( $params['post_id'] ) ? intval( $params['post_id'] ) : 0 );
		$type      = ( isset( $params['type'] ) ? sanitize_text_field( $params['type'] ) : 'text' );
		$template  = ( isset( $params['template'] ) ? sanitize_text_field( $params['template'] ) : '' );
		$post_type = $this->get_post_type_by_template( $template );

		if ( ! empty( $post_type ) ) {
			$custom_keys = $this->get_custom_keys_by_post_type( $post_type );
		} else {
			$custom_keys = get_post_custom_keys( $post_id );
		}
		// acf.
		$options = CustomFields::get_acf_fields( $post_id, $type );
		if ( ! empty( $custom_keys ) && is_array( $custom_keys ) ) {
			foreach ( $custom_keys as $custom_key ) {
				$field_label = $custom_key;
				if ( ! isset( $options[ $custom_key ] ) && '_' !== substr( $custom_key, 0, 1 ) ) {
					// Adding support for metabox plugin.
					if ( function_exists( 'rwmb_get_field_settings' ) ) {
						$field_data = rwmb_get_field_settings( $custom_key, array(), $post_id );
						if ( ! empty( $field_data ) ) {
							if ( $type !== $this->meta_box_get_field_type( $field_data['type'] ) ) {
								continue;
							}
							$field_label = isset( $field_data['name'] ) ? $field_data['name'] : $custom_key;
						}
					}
					$options[ $custom_key ] = [
						'label' => $field_label,
						'value' => $custom_key,
					];
				}
			}
		}//end if

		return new \WP_REST_Response( array_values( $options ), 200 );
	}

	/**
	 * Get Post Type by Template.
	 *
	 * @param string $template_name Template Name.
	 * @since 1.0.1
	 * @return string
	 */
	public function get_post_type_by_template( $template_name ) {
		global $wp_post_types;

		$post_type = '';

		// Check if the slug is for a single or archive post type.
		if ( strpos( $template_name, 'single-' ) === 0 || strpos( $template_name, 'archive-' ) === 0 ) {
			$post_type = str_replace( array( 'single-', 'archive-' ), '', $template_name );
		} else {
			$post_type = $template_name;
		}

		// Check if the post type is valid.
		if ( isset( $wp_post_types[ $post_type ] ) ) {
			return $post_type;
		}

		return $post_type;
	}

	/**
	 * Get post custom keys by post type.
	 *
	 * @param string $post_type Post Type.
	 * @since 1.0.1
	 * @return array Array of post metas.
	 */
	public function get_custom_keys_by_post_type( $post_type ) {
		// Retrieve all posts of the specified post type.
		$posts = get_posts(
			array(
				'post_type'      => $post_type,
				'posts_per_page' => -1, // Retrieve all posts.
			)
		);

		// Initialize an empty array to store the meta keys.
		$meta_keys = array();

		if ( empty( $posts ) ) {
			return $meta_keys;
		}

		// Loop through each post and retrieve its meta keys.
		foreach ( $posts as $post ) {
			$post_id = isset( $post->ID ) ? $post->ID : 0;
			$keys    = get_post_custom_keys( $post_id );

			if ( $keys && is_array( $keys ) ) {
				foreach ( $keys as $key ) {
					// Add the meta key to the array if it doesn't already exist.
					if ( ! in_array( $key, $meta_keys ) ) {
						$meta_keys[] = $key;
					}
				}
			}
		}

		return $meta_keys;

	}

	/**
	 * Get User Custom Field data by post id
	 *
	 * @param object $request Rest API request param.
	 * @return array
	 * @since 1.0.0
	 */
	public function get_user_custom_fields( $request ) {
		$params  = $request->get_params();
		$post_id = ( isset( $params['post_id'] ) ? intval( $params['post_id'] ) : 0 );
		$type    = ( isset( $params['type'] ) ? sanitize_text_field( $params['type'] ) : 'text' );
		if ( $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post instanceof \WP_Post ) {
				$user_id = get_current_user_id();
			} else {
				$user_id = $post->post_author;
			}
		} else {
			$user_id = get_current_user_id();
		}

		// acf.
		$acf_fields = CustomFields::get_acf_fields( 'user_' . $user_id, $type );
		$options    = $acf_fields;
		// custom meta keys.
		if ( 'text' === $type ) {
			$custom_keys = CustomFields::get_user_meta_fields( $user_id );
			$options     = array_merge( $custom_keys, $options );
		}
		return new \WP_REST_Response( array_values( $options ), 200 );
	}

	/**
	 * Get Terms data by post id
	 *
	 * @param object $request Rest API request param.
	 * @return array
	 * @since 1.0.0
	 */
	public function get_terms( $request ) {
		$post_id       = $request->get_param( 'post_id' );
		$taxonomy_args = [
			'show_in_nav_menus' => true,
		];
		$taxonomy      = Helper::get_taxonomies( $taxonomy_args );
		return new \WP_REST_Response( $taxonomy, 200 );
	}

	/**
	 * Get Term Meta by get_queried_objects
	 *
	 * @param object $request Rest API request param.
	 * @return array
	 * @since 1.0.0
	 */
	public function get_term_meta( $request ) {
		$post_id = $request->get_param( 'post_id' );
		$type    = $request->get_param( 'type' );
		$options = [];

		if ( class_exists( 'ACF' ) ) {
			$taxonomy_args = [
				'show_in_nav_menus' => true,
				'object_type'       => [ get_post_type( $post_id ) ],
			];
			$taxonomy      = Helper::get_taxonomies( $taxonomy_args );
			$taxonomy      = wp_list_pluck( $taxonomy, 'value' );

			$field_groups = acf_get_field_groups(
				array(
					'taxonomy' => $taxonomy,
				)
			);
			$type         = CustomFields::get_acf_field_type_by_group( $type );
			foreach ( $field_groups as $field_group ) {
				$fields = acf_get_fields( $field_group );
				foreach ( $fields as $field ) {
					if ( count( $type ) > 0 && in_array( $field['type'], $type, true ) ) {
						$options[] = [
							'label' => $field['label'],
							'value' => $field['name'],
						];
					}
				}
			}
		}//end if

		return new \WP_REST_Response( $options, 200 );
	}

	/**
	 * Get Dynamic Content based on saved string
	 *
	 * @param \WP_REST_Request $request Rest API request param.
	 * @return array
	 * @since 1.0.0
	 */
	public function get_dynamic_content( $request ) {
		$field           = $this->get_string_value( $request->get_param( 'field' ) );
		$post_id         = $this->get_string_value( $request->get_param( 'postID' ) );
		$image           = $this->get_string_value( $request->get_param( 'image' ) );
		$backend_request = boolval( $request->get_param( 'backendReq' ) );
		$fields          = ( ! empty( $field ) ? explode( '|', $field ) : [] );
		$image           = ( ! empty( $image ) ? explode( '|', $image ) : [] );
		$source          = $fields[0];

		if ( count( $fields ) === 0 ) {
			return new \WP_REST_Response( esc_html__( 'Empty Fields', 'spectra-pro' ), 200 );
		}

		if ( ( 'current_post' === $source || 'other_posts' === $source ) ) {
			$response = Posts::get_data( $fields, $post_id, [ 'image' => $image ], $backend_request );
		} else {
			$response = Site::get_data( $fields, [ 'image' => $image ] );
		}
		return new \WP_REST_Response( $response, 200 );
	}

	/**
	 * Static Dynamic Content replace with real dynamic content
	 *
	 * @param string $block_content all block content string.
	 * @param array  $block block details.
	 * @return string
	 * @since 1.0.0
	 */
	public function render_dynamic_content( $block_content, $block ) {
		$args = [];
		// adding wp_doing_ajax condition for ajax calls in frontend. for Loop builder filters ( search, sort ) etc.
		if ( ! $this->is_enabled_dynamic_content() 
			|| ! wp_doing_ajax() && ( 
				is_admin() 
				|| ( 
					defined( 'REST_REQUEST' ) && REST_REQUEST 
					) 
			) 
		) {
			return $block_content;
		}
		global $wp_query;
		$is_not_posts_or_home_page = ! ( $wp_query->is_posts_page() || $wp_query->is_home() || $wp_query->is_category() || $wp_query->is_singular() );
		if ( $is_not_posts_or_home_page ) {
			// Added filter to remove excerpt_more link.
			add_filter( 'excerpt_more', [ $this, 'excerpt_more_filter' ], 99 );
		}

		$block_attr           = isset( $block['attrs'] ) ? $block['attrs'] : '';
		$dynamic_content_attr = ( $block_attr && is_array( $block['attrs'] ) && isset( $block_attr['dynamicContent'] ) ? $block_attr['dynamicContent'] : '' );

		switch ( $block['blockName'] ) {
			case 'uagb/team':
				if ( true === Helper::has_enable_dynamic_content( $block_attr ) ) {
					$search_string  = ( isset( $block_attr['image']['url'] ) && 'image' === $block_attr['image']['type'] ? $block_attr['image']['url'] : '' );
					$replace_string = Helper::get_dynamic_content_from_attributes( $block_attr['dynamicContent'] );
					$block_content  = str_replace( $search_string, $replace_string, $block_content );
				}

				// Image link.
				if ( true === Helper::has_enable_dynamic_content( $block_attr, 'bgImageLink' ) ) {
					$replace_string = Helper::get_dynamic_content_from_dc_attributes( $dynamic_content_attr['bgImageLink'] );
					$block_content  = preg_replace_callback(
						"/<img\s[^>]*?src\s*=\s*['\"]([^'\"]*?)['\"][^>]*?>/U",
						function ( $matches ) use ( $replace_string ) {
							$content = '<a href="' . $replace_string . '">' . $matches[0] . '</a>';
							return $content;
						},
						strval( $block_content )
					);
				}

				break;

			case 'uagb/blockquote':
				if ( true === Helper::has_enable_dynamic_content( $block_attr ) ) {
					$search_string  = ( isset( $block_attr['authorImage']['url'] ) && 'image' === $block_attr['authorImage']['type'] ? $block_attr['authorImage']['url'] : '' );
					$replace_string = Helper::get_dynamic_content_from_attributes( $block_attr['dynamicContent'] );
					$block_content  = str_replace( $search_string, $replace_string, $block_content );
				}

				// Image link.
				if ( true === Helper::has_enable_dynamic_content( $block_attr, 'bgImageLink' ) ) {
					$replace_string = Helper::get_dynamic_content_from_dc_attributes( $dynamic_content_attr['bgImageLink'] );
					$block_content  = preg_replace_callback(
						"/<img\s[^>]*?src\s*=\s*['\"]([^'\"]*?)['\"][^>]*?>/U",
						function ( $matches ) use ( $replace_string ) {
							$content = '<a href="' . $replace_string . '">' . $matches[0] . '</a>';
							return $content;
						},
						$block_content
					);
				}

				break;

			case 'uagb/restaurant-menu-child':
				if ( true === Helper::has_enable_dynamic_content( $block_attr ) ) {
					$search_string  = ( isset( $block_attr['image']['url'] ) && 'image' === $block_attr['image']['type'] ? $block_attr['image']['url'] : '' );
					$replace_string = Helper::get_dynamic_content_from_attributes( $block_attr['dynamicContent'] );
					$block_content  = str_replace( $search_string, $replace_string, $block_content );
				}

				// Image link.
				if ( true === Helper::has_enable_dynamic_content( $block_attr, 'bgImageLink' ) ) {
					$replace_string = Helper::get_dynamic_content_from_dc_attributes( $dynamic_content_attr['bgImageLink'] );
					$block_content  = preg_replace_callback(
						"/<img\s[^>]*?src\s*=\s*['\"]([^'\"]*?)['\"][^>]*?>/U",
						function ( $matches ) use ( $replace_string ) {
							$content = '<a href="' . $replace_string . '">' . $matches[0] . '</a>';
							return $content;
						},
						$block_content
					);
				}

				break;

			case 'uagb/review':
				if ( true === Helper::has_enable_dynamic_content( $block_attr ) ) {
					$search_string  = ( isset( $block_attr['mainimage']['url'] ) && 'image' === $block_attr['mainimage']['type'] ? $block_attr['mainimage']['url'] : '' );
					$replace_string = Helper::get_dynamic_content_from_attributes( $block_attr['dynamicContent'] );
					$block_content  = str_replace( $search_string, $replace_string, $block_content );
				}

				// Image link.
				if ( true === Helper::has_enable_dynamic_content( $block_attr, 'bgImageLink' ) ) {
					$replace_string = Helper::get_dynamic_content_from_dc_attributes( $dynamic_content_attr['bgImageLink'] );
					$block_content  = preg_replace_callback(
						"/<img\s[^>]*?src\s*=\s*['\"]([^'\"]*?)['\"][^>]*?>/U",
						function ( $matches ) use ( $replace_string ) {
							$content = '<a href="' . $replace_string . '">' . $matches[0] . '</a>';
							return $content;
						},
						$block_content
					);
				}

				break;

			case 'uagb/info-box':
					// Link.
				if ( isset( $dynamic_content_attr['ctaLink'] ) && $dynamic_content_attr['ctaLink']['enable'] ) {
					$replace_string = Helper::get_dynamic_content_from_dc_attributes( $dynamic_content_attr['ctaLink'] );
					$block_content  = preg_replace_callback(
						'/(href)=("[^"]*")/U',
						function ( $matches ) use ( $replace_string ) {

							$content = substr_replace( $matches[1], ' href="' . $replace_string . '"', 0 );

							return $content;
						},
						$block_content
					);
				}

				if ( true === Helper::has_enable_dynamic_content( $block_attr ) ) {
					$search_string  = ( isset( $block_attr['iconImage']['url'] ) && 'image' === $block_attr['iconImage']['type'] ? $block_attr['iconImage']['url'] : '' );
					$replace_string = Helper::get_dynamic_content_from_attributes( $block_attr['dynamicContent'] );
					$block_content  = str_replace( $search_string, $replace_string, $block_content );
				}

				// Image link.
				if ( true === Helper::has_enable_dynamic_content( $block_attr, 'bgImageLink' ) ) {
					$replace_string = Helper::get_dynamic_content_from_dc_attributes( $dynamic_content_attr['bgImageLink'] );
					$block_content  = preg_replace_callback(
						"/<img\s[^>]*?src\s*=\s*['\"]([^'\"]*?)['\"][^>]*?>/U",
						function ( $matches ) use ( $replace_string ) {
							$content = '<a href="' . $replace_string . '">' . $matches[0] . '</a>';
							return $content;
						},
						strval( $block_content )
					);
				}

				// Alt Text.
				if ( isset( $dynamic_content_attr['imageAlt'] ) && $dynamic_content_attr['imageAlt']['enable'] ) {
					$replace_string = Helper::get_dynamic_content_from_dc_attributes( $dynamic_content_attr['imageAlt'] );
					if ( is_string( $block_content ) ) {
						$block_content = preg_replace_callback(
							'/(alt)=("[^"]*")/U',
							function ( $matches ) use ( $replace_string ) {
								$content = substr_replace( $matches[1], ' alt="' . $replace_string . '"', 0 );
								return $content;
							},
							$block_content
						);
					}
				}

				break;

			case 'uagb/how-to':
				if ( true === Helper::has_enable_dynamic_content( $block_attr ) ) {
					$search_string  = ( isset( $block_attr['mainimage']['url'] ) && 'image' === $block_attr['mainimage']['type'] ? $block_attr['mainimage']['url'] : '' );
					$replace_string = Helper::get_dynamic_content_from_attributes( $block_attr['dynamicContent'] );
					$block_content  = str_replace( $search_string, $replace_string, $block_content );
				}

				// Image link.
				if ( true === Helper::has_enable_dynamic_content( $block_attr, 'bgImageLink' ) ) {
					$replace_string = Helper::get_dynamic_content_from_dc_attributes( $dynamic_content_attr['bgImageLink'] );
					$block_content  = preg_replace_callback(
						"/<img\s[^>]*?src\s*=\s*['\"]([^'\"]*?)['\"][^>]*?>/U",
						function ( $matches ) use ( $replace_string ) {
							$content = '<a href="' . $replace_string . '">' . $matches[0] . '</a>';
							return $content;
						},
						$block_content
					);
				}

				break;

			case 'uagb/how-to-step':
				if ( true === Helper::has_enable_dynamic_content( $block_attr ) ) {
					$search_string  = ( isset( $block_attr['image']['url'] ) && 'image' === $block_attr['image']['type'] ? $block_attr['image']['url'] : '' );
					$replace_string = Helper::get_dynamic_content_from_attributes( $block_attr['dynamicContent'] );
					$block_content  = str_replace( $search_string, $replace_string, $block_content );
				}

				// Image link.
				if ( true === Helper::has_enable_dynamic_content( $block_attr, 'bgImageLink' ) ) {
					$replace_string = Helper::get_dynamic_content_from_dc_attributes( $dynamic_content_attr['bgImageLink'] );
					$block_content  = preg_replace_callback(
						"/<img\s[^>]*?src\s*=\s*['\"]([^'\"]*?)['\"][^>]*?>/U",
						function ( $matches ) use ( $replace_string ) {
							$content = '<a href="' . $replace_string . '">' . $matches[0] . '</a>';
							return $content;
						},
						$block_content
					);
				}

				break;

			case 'uagb/columns':
				if ( true === Helper::has_enable_dynamic_content( $block_attr ) ) {
					$search_string  = ( isset( $block_attr['backgroundImage']['url'] ) && 'image' === $block_attr['backgroundImage']['type'] ? $block_attr['backgroundImage']['url'] : '' );
					$replace_string = Helper::get_dynamic_content_from_attributes( $block_attr['dynamicContent'] );
					$block_content  = preg_replace_callback(
						'/<.*?class=["\'].*wp-block-uagb-columns.*["\']>/U',
						function ( $matches ) use ( $replace_string ) {
							$content = '';
							if ( is_array( $matches ) ) {
								$content = substr_replace( $matches[0], ' style="background-image: url(' . $replace_string . ');"', strlen( $matches[0] ) - 1, 0 );
							}
							return $content;
						},
						$block_content
					);
				}

				break;

			case 'uagb/container':
				if ( true === Helper::has_enable_dynamic_content( $block_attr ) ) {
					$search_string = ( isset( $block_attr['backgroundImage']['url'] ) && 'image' === $block_attr['backgroundImage']['type'] ? $block_attr['backgroundImage']['url'] : '' );
					$attr_file     = UAGB_DIR . 'includes/blocks/container/attributes.php';

					if ( file_exists( $attr_file ) ) {

						$default_attr = include $attr_file;

						$block_attr = self::get_fallback_values( $default_attr, $block_attr );
					}
					$replace_string = Helper::get_dynamic_content_from_attributes( $block_attr['dynamicContent'] );
					$bg_obj_desktop = array(
						'backgroundType'           => $block_attr['backgroundType'],
						'backgroundImage'          => [
							'type' => 'image',
							'url'  => $replace_string,
						],
						'backgroundColor'          => $block_attr['backgroundColor'],
						'gradientValue'            => $block_attr['gradientValue'],
						'gradientColor1'           => $block_attr['gradientColor1'],
						'gradientColor2'           => $block_attr['gradientColor2'],
						'gradientType'             => $block_attr['gradientType'],
						'gradientLocation1'        => $block_attr['gradientLocation1'],
						'gradientLocation2'        => $block_attr['gradientLocation2'],
						'gradientAngle'            => $block_attr['gradientAngle'],
						'selectGradient'           => $block_attr['selectGradient'],
						'backgroundRepeat'         => $block_attr['backgroundRepeatDesktop'],
						'backgroundPosition'       => $block_attr['backgroundPositionDesktop'],
						'backgroundSize'           => $block_attr['backgroundSizeDesktop'],
						'backgroundAttachment'     => $block_attr['backgroundAttachmentDesktop'],
						'backgroundImageColor'     => $block_attr['backgroundImageColor'],
						'overlayType'              => $block_attr['overlayType'],
						'backgroundCustomSize'     => $block_attr['backgroundCustomSizeDesktop'],
						'backgroundCustomSizeType' => $block_attr['backgroundCustomSizeType'],
						'backgroundVideo'          => $block_attr['backgroundVideo'],
						'backgroundVideoColor'     => $block_attr['backgroundVideoColor'],
						'customPosition'           => $block_attr['customPosition'],
						'xPosition'                => $block_attr['xPositionDesktop'],
						'xPositionType'            => $block_attr['xPositionType'],
						'yPosition'                => $block_attr['yPositionDesktop'],
						'yPositionType'            => $block_attr['yPositionType'],
					);
					// Second parameter indicates that this element has a '::before' overlay, and the CSS generated should be for the element only instead of the overlay.
					$container_bg_css_desktop = \UAGB_Block_Helper::uag_get_background_obj( $bg_obj_desktop, 'no' );
					$cssString                = implode(
						'; ',
						array_map(
							function( $v, $k ) {
								return "{$k}: {$v}";
							},
							$container_bg_css_desktop,
							array_keys( $container_bg_css_desktop )
						)
					);
					$block_content            = preg_replace_callback(
						'/<.*?class=["\'].*wp-block-uagb-container.*["\']>/U',
						function ( $matches ) use ( $cssString ) {
							$content = '';
							if ( is_array( $matches ) ) {
								$content = substr_replace( $matches[0], ' style="' . $cssString . '"', strlen( $matches[0] ) - 1, 0 );
							}
							return $content;
						},
						$block_content,
						1
					);
				}//end if

				if ( ! is_string( $block_content ) ) {
					break;
				}
				if ( isset( $dynamic_content_attr['htmlTagLink'] ) && $dynamic_content_attr['htmlTagLink']['enable'] ) {
					$replace_string = Helper::get_dynamic_content_from_dc_attributes( $dynamic_content_attr['htmlTagLink'] );

					$block_content = preg_replace_callback(
						'/<a[^>]*class="spectra-container-link-overlay "[^>]*>.*?<\/a>/', // Define the regular expression pattern to match the anchor tag.
						function ( $match ) use ( $replace_string ) {
							// Define the callback function.
							// Replace the href attribute with the replace URL.
							$content = preg_replace( '/href="[^"]*"/', 'href="' . $replace_string . '"', $match[0] );
							return $content;
						},
						$block_content
					);

					$block_content = preg_replace( '/<a(.*?)>/', '<a href="' . $replace_string . '"$1>', strval( $block_content ) );            
				}//end if
				
				break;

			case 'uagb/image':
					// Alt Text.
				if ( isset( $dynamic_content_attr['alt'] ) && $dynamic_content_attr['alt']['enable'] ) {
					$replace_string = Helper::get_dynamic_content_from_dc_attributes( $dynamic_content_attr['alt'] );
					$block_content  = preg_replace_callback(
						'/(alt)=("[^"]*")/U',
						function ( $matches ) use ( $replace_string ) {

							$content = substr_replace( $matches[1], ' alt="' . $replace_string . '"', 0 );

							return $content;
						},
						$block_content
					);
				}
					// Title Text.
				if ( isset( $dynamic_content_attr['title'] ) && $dynamic_content_attr['title']['enable'] ) {
					if ( ! is_string( $block_content ) ) {
						break;
					}
					$replace_string = Helper::get_dynamic_content_from_dc_attributes( $dynamic_content_attr['title'] );
					$block_content  = preg_replace_callback(
						'/(title)=("[^"]*")/U',
						function ( $matches ) use ( $replace_string ) {
	
							$content = substr_replace( $matches[1], ' title="' . $replace_string . '"', 0 );
	
							return $content;
						},
						$block_content
					);
				}
				// Image.
				if ( true === Helper::has_enable_dynamic_content( $block_attr ) ) {
					$search_string  = ( isset( $block_attr['backgroundImage']['url'] ) && 'image' === $block_attr['backgroundImage']['type'] ? $block_attr['backgroundImage']['url'] : '' );
					$replace_string = Helper::get_dynamic_content_from_attributes( $block_attr['dynamicContent'] );

					if ( empty( $replace_string ) ) {
						$block_content = '';
						break;
					}

					$block_content = preg_replace_callback(
						'/[^"\'=\s]+\.(jpg|jpeg|png|gif|ico)/U',
						function ( $matches ) use ( $replace_string ) {
							$content = '';
							if ( is_array( $matches ) ) {
								$content = substr_replace( $matches[0], $replace_string, 0 );
							}
							return $content;
						},
						strval( $block_content )
					);

					// Added logic to modify <img> tags that include the loading="lazy" attribute, adjusting the sizes attribute to resolve an issue of when we search then image size increase in loop.
					$block_content = preg_replace_callback(
						'/<img([^>]+loading="lazy"[^>]*)>/',
						function ( $matches ) {
							// Check if the `sizes` attribute exists in the matched tag.
							if ( preg_match( '/sizes="([^"]*)"/', $matches[0] ) ) {
								// Modify the `sizes` attribute.
								$modifiedTag = preg_replace(
									'/sizes="([^"]*)"/',
									'sizes="auto, $1"',
									$matches[0]
								);
								return $modifiedTag;
							}
							// Return the original tag if `sizes` attribute is not present.
							return $matches[0];
						},
						strval( $block_content )
					);
				}//end if

				// Image link.
				if ( true === Helper::has_enable_dynamic_content( $block_attr, 'bgImageLink' ) ) {
					$replace_string = Helper::get_dynamic_content_from_dc_attributes( $dynamic_content_attr['bgImageLink'] );
					$block_content  = preg_replace_callback(
						"/<img\s[^>]*?src\s*=\s*['\"]([^'\"]*?)['\"][^>]*?>/U",
						function ( $matches ) use ( $replace_string ) {
							$content = '<a href="' . $replace_string . '">' . $matches[0] . '</a>';
							return $content;
						},
						$block_content
					);
					if ( ! empty( $block_attr['layout'] ) && 'overlay' === $block_attr['layout'] && ! empty( $block_content ) ) {
						// Use preg_replace_callback to find and replace the anchor tag when the image layout is set to overlay.
						$block_content = preg_replace_callback(
							'/<a[^>]*class="wp-block-uagb-image--layout-overlay-link "[^>]*>.*?<\/a>/', // Define the regular expression pattern to match the anchor tag.
							function ( $match ) use ( $replace_string ) {
								// Define the callback function.
								// Replace the href attribute with the replace URL.
								$content = preg_replace( '/href="[^"]*"/', 'href="' . $replace_string . '"', $match[0] );
								return $content;
							},
							$block_content
						);
					}
				}//end if
				break;

			case 'uagb/buttons-child':
				// Link.
				if ( isset( $dynamic_content_attr['link'] ) && $dynamic_content_attr['link']['enable'] ) {
					$linkField               = isset( $dynamic_content_attr['link']['field'] ) ? $dynamic_content_attr['link']['field'] : '';
					list($scope, $link_type) = array_pad( explode( '|', $linkField ), 2, '' );

					if ( 'pagination-prev' === $link_type || 'pagination-numbers' === $link_type || 'pagination-next' === $link_type ) {
						return $block_content;
					} elseif ( 'post_terms' === $link_type || 'reset' === $link_type ) {
						return $block_content;
					}

					$replace_string = Helper::get_dynamic_content_from_dc_attributes( $dynamic_content_attr['link'] );

					if ( empty( $replace_string ) ) {
						$block_content = '';
						break;
					}

					$block_content = preg_replace_callback(
						'/<a\s+(?=[^>]*?(?<!href)=(["\'])\S*\1)[^>]*?>/i',
						function ( $matches ) use ( $replace_string ) {

							$content = preg_replace( '/href=(["\'])(.*?)\1/i', 'href="' . $replace_string . '"', $matches[0] );

							return $content;
						},
						$block_content
					);

					if ( strpos( $block_content, 'href=' ) === false ) {
						$block_content = preg_replace( '/<a(.*?)>/', '<a href="' . $replace_string . '"$1>', strval( $block_content ) );
					}
				}//end if
				break;

			case 'uagb/call-to-action':
				// ctaLink.
				if ( isset( $dynamic_content_attr['ctaLink'] ) && $dynamic_content_attr['ctaLink']['enable'] ) {
					$replace_string = Helper::get_dynamic_content_from_dc_attributes( $dynamic_content_attr['ctaLink'] );
					$block_content  = preg_replace_callback(
						'/(href)=("[^"]*") class="uagb-cta__button-link-wrapper wp-block-button__link"/U',
						function ( $matches ) use ( $replace_string ) {
							$content = substr_replace( $matches[1], ' href="' . $replace_string . '" class="uagb-cta__button-link-wrapper wp-block-button__link"', 0 );
							return $content;
						},
						$block_content
					);
				}

				// secondCtaLink.
				if ( isset( $dynamic_content_attr['secondCtaLink'] ) && $dynamic_content_attr['secondCtaLink']['enable'] ) {
					$replace_string = Helper::get_dynamic_content_from_dc_attributes( $dynamic_content_attr['secondCtaLink'] );
					$block_content  = preg_replace_callback(
						'/(href)=("[^"]*") class="uagb-cta-second__button wp-block-button__link"/U',
						function ( $matches ) use ( $replace_string ) {
							$content = substr_replace( $matches[1], ' href="' . $replace_string . '" class="uagb-cta-second__button wp-block-button__link"', 0 );
							return $content;
						},
						strval( $block_content )
					);
				}
				break;
			case 'uagb/counter':
				if ( true === Helper::has_enable_dynamic_content( $block_attr, 'startNumber' ) || true === Helper::has_enable_dynamic_content( $block_attr, 'endNumber' ) || true === Helper::has_enable_dynamic_content( $block_attr, 'totalNumber' ) ) {
					$put_obj = [];
					// For start number.
					if ( true === Helper::has_enable_dynamic_content( $block_attr, 'startNumber' ) ) {
						$start_num              = Helper::get_dynamic_content_from_dc_attributes( $dynamic_content_attr['startNumber'] );
						$put_obj['startNumber'] = preg_replace( '/[^0-9]/', '', $start_num );
					}

					// For end number.
					if ( true === Helper::has_enable_dynamic_content( $block_attr, 'endNumber' ) ) {
						$end_num              = Helper::get_dynamic_content_from_dc_attributes( $dynamic_content_attr['endNumber'] );
						$put_obj['endNumber'] = preg_replace( '/[^0-9]/', '', $end_num );
					}

					// For total number.
					if ( true === Helper::has_enable_dynamic_content( $block_attr, 'totalNumber' ) ) {
						$put_obj['totalNumber'] = Helper::get_dynamic_content_from_dc_attributes( $dynamic_content_attr['totalNumber'] );
					}

					$put_obj       = wp_json_encode( $put_obj );
					$block_content = preg_replace( '/<div /', "<div data-counter='$put_obj' ", $block_content, 1 );
				}//end if
				break;
		}//end switch

		if ( strpos( strval( $block_content ), 'class="uag-pro-dynamic-content"' ) === false ) {
			return $block_content;
		}

		$replaced_block_content = preg_replace_callback(
			'/<span\s+((?:data-spectra-dc-[\w\-]+=["\']+.*["\']+[\s]+)+)class=["\'].*uag-pro-dynamic-content.*["\']\s*>(.*)<\/span>/U',
			function ( $matches ) {
				if ( empty( $matches[1] ) ) {
					return '';
				}
				$options = explode( '" ', str_replace( 'data-', '', $matches[1] ) );
				$args    = [];
				foreach ( $options as $key => $value ) {
					if ( empty( $value ) ) {
						continue;
					}
					$data_split = explode( '=', $value, 2 );
					if ( 'spectra-dc-source' === $data_split[0] ) {
						$field_split       = explode( '|', str_replace( '"', '', $data_split[1] ), 2 );
						$args['post_type'] = ( isset( $field_split[0] ) && ! empty( $field_split[0] ) ? $field_split[0] : 'post' );
						$args['post_id']   = ( isset( $field_split[1] ) && ! empty( $field_split[1] ) ? $field_split[1] : '' );
					} else {
						$args[ $data_split[0] ] = str_replace( '"', '', $data_split[1] );
					}
				}
				$dynamic_content = $this->generate_dynamic_content( $args );
				return $dynamic_content;
			},
			strval( $block_content )
		);
		// if the regex errored out, don't replace the $block_content.
		$block_content = is_null( $replaced_block_content ) ? $block_content : $replaced_block_content;

		if ( $is_not_posts_or_home_page ) {
			// Remove the filter that's added for dynamic content.
			remove_filter( 'excerpt_more', [ $this, 'excerpt_more_filter' ], 99 );
		}
		return $block_content;
	}

	/**
	 * Render Buttons Child Content.
	 *
	 * @param string    $content Button Content.
	 * @param array     $attributes Attributes Array.
	 * @param \WP_Block $block Block Object.
	 * @since 1.0.0
	 * @return string|null Modified Content.
	 */
	public function buttons_child_content( $content, $attributes, $block ) {
		if ( isset( $attributes['dynamicContent']['link'] ) && $attributes['dynamicContent']['link']['enable'] ) {
			$linkField               = isset( $attributes['dynamicContent']['link']['field'] ) ? $attributes['dynamicContent']['link']['field'] : '';
			list($scope, $link_type) = array_pad( explode( '|', $linkField ), 2, '' );

			if ( 'pagination-prev' === $link_type ) {
				$page_key = isset( $block->context['queryId'] ) ? 'query-' . $block->context['queryId'] . '-page' : 'query-page';
				$page     = ! empty( $block->context['query']['paged'] ) ? (int) $block->context['query']['paged'] : 1;

				if ( 1 !== $page ) {
					$prev_page = $page - 1;
					$content   = str_replace( '<a', '<a id="uagb-pagination-links" data-uagb-block-query-id="' . $block->context['queryId'] . '" data-uagb-block-query-paged="' . $prev_page . '"', $content );
				} else {
					$content = '';
				}
			}

			if ( 'reset' === $link_type ) {
				$content = str_replace( '<a', '<a onclick="return false;" data-uagb-block-query-id="' . $block->context['queryId'] . '"', $content );
			}

			if ( 'post_terms' === $link_type ) {
				$showEmptyTaxonomy = ! empty( $block->context['attrs']['showEmptyTaxonomy'] ) ? boolval( $block->context['attrs']['showEmptyTaxonomy'] ) : false;
				$showChildren      = ! empty( $block->context['attrs']['showChildren'] ) ? boolval( $block->context['attrs']['showChildren'] ) : false;
				$showCount         = ! empty( $block->context['attrs']['showCount'] ) ? $block->context['attrs']['showCount'] : false;
				$taxonomyType      = ! empty( $block->context['attrs']['taxonomyType'] ) ? $block->context['attrs']['taxonomyType'] : 'category';
				// include/ exclude.
				$include_value = ! empty( $block->context['attrs']['taxonomy'] ) ? $block->context['attrs']['taxonomy'] : array();
				$exclude_value = ! empty( $block->context['attrs']['taxonomyExclude'] ) ? $block->context['attrs']['taxonomyExclude'] : array();
				$taxType       = ! empty( $block->context['attrs']['taxType'] ) ? $block->context['attrs']['taxType'] : 'include';
				$categories    = get_categories(
					array(
						'orderby'    => 'name',
						'parent'     => 0,
						'hide_empty' => ! $showEmptyTaxonomy,
						'taxonomy'   => $taxonomyType,
					) 
				);
				// Condition check.
				if ( empty( $categories ) ) {
					return '';
				}
				// Initialize an empty array to store the new format.
				$categories_list = array();
				// Iterate through the WP_Term objects.
				foreach ( $categories as $termObject ) {
					if ( ! empty( $termObject->taxonomy ) && ! empty( $termObject->term_id ) && ! empty( $termObject->name ) && isset( $termObject->count ) ) {     
						if ( $showChildren ) {
							// Get the children categories.
							$child_category  = Utils::get_child_categories( $termObject->term_id, $showEmptyTaxonomy, $taxonomyType );
							$categories_list = array_merge( $categories_list, $child_category );
						}
						// Build the desired format and add it to the new array.
						$categories_list[] = $termObject->taxonomy . ':' . $termObject->term_id . ':' . $termObject->name . ':' . $termObject->count;
					}
				}
				// condition check.
				if ( 'exclude' === $taxType && ! empty( $exclude_value ) ) {
					// Use array_filter with an anonymous function to exclude elements in $exclude_value.
					// Loop through each value to remove.
					foreach ( $exclude_value as $value ) {
						// Loop through the original array and remove the value.
						foreach ( $categories_list as $key => $element ) {
							if ( strpos( $element, $value ) !== false ) {
								unset( $categories_list[ $key ] );
							}
						}
					}
					// Reindex the array.
					$new_categories_list = array_values( $categories_list );
				}
				// condition check.
				if ( 'include' === $taxType && ! empty( $include_value ) ) {
					$matching_values = array();
					// Use array_filter with an anonymous function to include elements in $include_value.
					// Loop through each value to remove.
					foreach ( $include_value as $value ) {
						// Loop through the original array and remove the value.
						foreach ( $categories_list as $key => $element ) {
							if ( strpos( $element, $value ) !== false ) {
								$matching_values[] = $element;
								break; // Break inner loop if match found.
							}
						}
					}
					// Reindex the array.
					$new_categories_list = array_values( $matching_values );
				}
				if ( empty( $new_categories_list ) ) {
					$new_categories_list = $categories_list;
				}

				if ( is_array( $new_categories_list ) ) {
					
					$updated_with_content = '';
					foreach ( $new_categories_list as $key => $value ) {
						$parts         = explode( ':', $value );
						$taxonomy_name = $parts[0];
						$number        = (int) $parts[1];
						// Check if the category ID is valid.
						$name_of_tax           = $parts[2];
						$count                 = $parts[3];
						$updated_link          = str_replace( '<a', '<a id="uagb-loop-category-button-links" data-uagb-block-query-id="' . $block->context['queryId'] . '"', $content );
						$taxType               = 'include';
						$updated_with_content .= preg_replace_callback(
							'/(<[^>]+class="[^"]*\buagb-button__link\b[^"]*"[^>]*>)([^<]+)(<\/[^>]+>)/',
							function ( $matches ) use ( $name_of_tax, $taxType, $taxonomy_name, $number, $showCount, $count ) {
								// Append data-type attribute to the div element.
								$matches[1] = preg_replace(
									'/<div\b/',
									'<div data-type="' . esc_attr( $taxType ) . '|' . esc_attr( $taxonomy_name ) . '|' . intval( $number ) . '"',
									$matches[1],
									1 // Limit to replace only the first occurrence.
								);
								return $matches[1] . $name_of_tax . ( $showCount ? ' ( ' . $count . ' ) ' : '' ) . $matches[3];
							},
							$updated_link
						);

					}//end foreach
					$content = $updated_with_content;
				}//end if
			}//end if

			if ( 'pagination-numbers' === $link_type ) {
				$page_key = isset( $block->context['queryId'] ) ? 'query-' . $block->context['queryId'] . '-page' : 'query-page';
				$page     = ! empty( $block->context['query']['paged'] ) ? (int) $block->context['query']['paged'] : 1;
				$max_page = isset( $block->context['query']['pages'] ) ? (int) $block->context['query']['pages'] : 0;

				global $wp_query;

				$query_args = (array) apply_filters(
					'spectra_loop_builder_query_args',
					build_query_vars_from_query_block( $block, $page ),
					$attributes,
					$block
				);

				$query_args = array_merge( $query_args, Utils::customize_block_query( $block ) );

				$block_query = new \WP_Query( $query_args );

				// Switching the original query as `paginate_links` works with the global $wp_query.
				$prev_wp_query = $wp_query;
				$wp_query      = $block_query; // phpcs:ignore -- No way around overwriting core global.
				$total         = ! $max_page || $max_page > $wp_query->max_num_pages ? $wp_query->max_num_pages : $max_page;

				$paginate_args = array(
					'base'      => '%_%',
					'format'    => "?$page_key=%#%",
					'current'   => max( 1, $page ),
					'total'     => $total,
					'prev_next' => false,
				);

				// We still need to preserve `paged` query param if it exists, as is used for Queries that inherit from global context.
				$paged = empty( $_GET['paged'] ) ? 1 : (int) $_GET['paged']; // phpcs:ignore -- No data processing happening.

				if ( $paged ) {
					$paginate_args['add_args'] = array( 'paged' => $paged );
				}

				$links    = paginate_links( $paginate_args );
				$wp_query = $prev_wp_query; // phpcs:ignore -- Restoring core global.
				wp_reset_postdata(); // Reset to original Post Data.

				$doc = self::load_html( $this->get_string_value( $links ) );

				if ( ! $doc ) {
					return $content;
				}

				$data       = array();
				$html_nodes = $doc->getElementsByTagName( '*' );

				foreach ( $html_nodes as $index => $node ) {
					$classes = $node->getAttribute( 'class' ) ? $node->getAttribute( 'class' ) : '';

					// phpcs:ignore -- DOMDocument doesn't use snake-case.
					if ( 'span' === $node->tagName || 'a' === $node->tagName ) {
						$data[ $index ]['href']         = $node->getAttribute( 'href' ) ? $node->getAttribute( 'href' ) : '';
						$data[ $index ]['aria-current'] = $node->getAttribute( 'aria-current' ) ? $node->getAttribute( 'aria-current' ) : '';
						$data[ $index ]['class']        = $classes;

						// phpcs:ignore -- DOMDocument doesn't use snake-case.
						foreach ( $node->childNodes as $childNode ) {
							if ( $childNode instanceof \DOMNode ) {
								$data[ $index ]['content'] = $doc->saveHTML( $childNode );
							}
						}
					}
				}

				$paginate_links = array_values( $data );
				$link_items     = array();

				foreach ( (array) $paginate_links as $index => $link ) {
					$link_items[ $index ] = array(
						'content'    => isset( $link['content'] ) ? $link['content'] : '',
						'attributes' => array(
							'aria-current'                => $link['aria-current'],
							'class'                       => $link['class'],
							'id'                          => 'uagb-pagination-links',
							'data-uagb-block-query-id'    => $block->context['queryId'],
							'data-uagb-block-query-paged' => '',
						),
					);
				}

				if ( empty( $link_items ) ) {
					$content = '';
				}
				$updated_with_content = '';
				foreach ( $link_items as $link_item ) {
					$up_content = $link_item['content'];

					$updated_link = str_replace( '<a', '<a id="uagb-pagination-links" data-uagb-block-query-id="' . $block->context['queryId'] . '" data-uagb-block-query-paged="' . $up_content . '"', $content );

					$updated_with_content .= preg_replace_callback(
						'/(<[^>]+class="[^"]*\buagb-button__link\b[^"]*"[^>]*>)([^<]+)(<\/[^>]+>)/',
						function ( $matches ) use ( $up_content ) {
							return $matches[1] . $up_content . $matches[3];
						},
						$updated_link
					);
				}
				$content = $updated_with_content;
			}//end if

			if ( 'pagination-next' === $link_type ) {
				$page_key = isset( $block->context['queryId'] ) ? 'query-' . $block->context['queryId'] . '-page' : 'query-page';
				$page     = ! empty( $block->context['query']['paged'] ) ? (int) $block->context['query']['paged'] : 1;
				$max_page = isset( $block->context['query']['pages'] ) ? (int) $block->context['query']['pages'] : 0;

				if ( ! $max_page || $max_page > $page ) {
					$query_args = (array) apply_filters(
						'spectra_loop_builder_query_args',
						build_query_vars_from_query_block( $block, $page ),
						$attributes,
						$block
					);

					$query_args = array_merge( $query_args, Utils::customize_block_query( $block ) );

					$custom_query           = new \WP_Query( $query_args );
					$custom_query_max_pages = (int) $custom_query->max_num_pages;

					if ( $custom_query_max_pages && $custom_query_max_pages !== $page ) {
						$url       = esc_url( add_query_arg( $page_key, $page + 1 ) );
						$next_page = $page + 1;
						$content   = str_replace( '<a', '<a id="uagb-pagination-links" data-uagb-block-query-id="' . $block->context['queryId'] . '" data-uagb-block-query-paged="' . $next_page . '"', $content );
					} else {
						$content = '';
					}

					wp_reset_postdata(); // Restore to the original Post Data.
				}//end if
			}//end if
		}//end if
		return $content;
	}

	/**
	 * Filter Google Map block attributes.
	 *
	 * @param array $attributes Block attributes.
	 * @since 1.0.1
	 * @return array Modified block attributes.
	 */
	public function google_map_block_attributes( $attributes ) {

		if ( ! empty( $attributes['dynamicContent']['address']['field'] ) && $attributes['dynamicContent']['address']['enable'] ) {
			$atts = array(
				'spectra-dc-field'    => $attributes['dynamicContent']['address']['field'],
				'spectra-dc-advanced' => $attributes['dynamicContent']['address']['advanced'],
				'spectra-dc-source'   => $attributes['dynamicContent']['address']['source'],
			);

			$attributes['address'] = $this->generate_dynamic_content( $atts );
		}

		return $attributes;
	}

	/**
	 * Run HTML through DOMDocument so we can use parts of it
	 * when needed.
	 *
	 * @param string $content The content to run through DOMDocument.
	 * @since 1.0.0
	 * @return \DOMDocument|null The parsed HTML document or null if DOMDocument class is not available.
	 */
	public static function load_html( $content ) {
		if ( ! class_exists( 'DOMDocument' ) ) {
			return null;
		}

		$doc = new \DOMDocument();

		// Enable user error handling for the HTML parsing. Ignore all errors.
		libxml_use_internal_errors( true );

		// Parse the post content into an HTML document.
		// Ensure UTF-8 encoding.
		$doc->loadHTML(
			sprintf(
				'<html><head><meta http-equiv="Content-Type" content="text/html; charset=%s"></head><body>%s</body></html>',
				esc_attr( get_bloginfo( 'charset' ) ),
				$content
			),
			LIBXML_NOWARNING | LIBXML_NOERROR
		);

		// Disable user error handling.
		libxml_use_internal_errors( false );

		return $doc;
	}

	/**
	 * Filter excerpt_more text.
	 *
	 * @param string $more_text default text.
	 * @since 1.0.0
	 * @return string empty string.
	 */
	public function excerpt_more_filter( $more_text ) {
		return '';
	}

	/**
	 * Build Dynamic Content based on data attributes value
	 *
	 * @param array $atts attribute dynamic data arguments.
	 * @return string
	 * @since 1.0.0
	 */
	public function generate_dynamic_content( $atts ) {
		$field_data    = '';
		$field         = isset( $atts['spectra-dc-field'] ) ? $atts['spectra-dc-field'] : '';
		$advance       = isset( $atts['spectra-dc-advanced'] ) ? $atts['spectra-dc-advanced'] : '';
		$link          = isset( $atts['spectra-dc-link'] ) ? $atts['spectra-dc-link'] : '';
		$post_id       = isset( $atts['post_id'] ) ? $atts['post_id'] : 'null';
		$field_array   = explode( '|', $field );
		$advance_array = explode( '|', $advance );
		$link_array    = explode( '|', $link );

		if ( 'current_post' === $field_array[0] || 'other_posts' === $field_array[0] ) {
			if ( isset( $advance_array[3] ) ) {
				$field_data = Posts::get_data( $field_array, $post_id, array(), false, $advance_array[3] );
			} else {
				$field_data = Posts::get_data( $field_array, $post_id );
			}
		} else {
			$field_data = Site::get_data( $field_array );
		}
		if ( empty( $field_data ) && isset( $advance_array[2] ) ) {
			return $advance_array[2];
		}

		// Ensure field_data is always a string before processing.
		$field_data = $this->sanitize_field_data_to_string( $field_data );

		// Trim field data here.
		// $advance_array[3] - holds character length.
		if ( ! empty( $advance_array[3] ) ) {
			$field_data = PRO_Helper::trim_text_to_fully_visible_word( $field_data, intval( $advance_array[3] ), true );
		}

		$link_url = $this->get_dynamic_link( $link_array[0], $link_array, $post_id );
		if ( ! empty( $link_url ) ) {
			$link_url   = is_email( $link_url ) ? 'mailto:' . $link_url : $link_url;
			$field_data = '<a href="' . esc_url( $link_url ) . '">' . wp_kses_post( $field_data ) . '</a>';
		}
		$before_string = ( isset( $advance_array[0] ) ? Helper::get_decoded_string( $advance_array[0] ) : '' );
		$after_string  = ( isset( $advance_array[1] ) ? Helper::get_decoded_string( $advance_array[1] ) : '' );

		return trim( $before_string . wp_kses_post( $field_data ) . $after_string );
	}

	/**
	 * Sanitizes field data to ensure it's always a string.
	 * Handles arrays and other data types that might be returned by custom fields.
	 *
	 * @param mixed $field_data The field data to sanitize.
	 * @return string The sanitized field data as a string.
	 * @since 1.2.5
	 */
	private function sanitize_field_data_to_string( $field_data ) {
		// If it's already a string, return as is.
		if ( is_string( $field_data ) ) {
			return $field_data;
		}

		// If it's an array, flatten and convert to comma-separated string.
		if ( is_array( $field_data ) ) {
			$flat_array = [];
			array_walk_recursive(
				$field_data,
				function ( $value ) use ( &$flat_array ) {
					if ( is_scalar( $value ) || ( is_object( $value ) && method_exists( $value, '__toString' ) ) ) {
						$flat_array[] = (string) $value;
					}
				}
			);
			return implode( ', ', $flat_array );
		}

		// If it's a WP_Post object, return post_title.
		if ( $field_data instanceof \WP_Post ) {
			return $field_data->post_title;
		}

		// If it's any object that can be cast to string.
		if ( is_object( $field_data ) && method_exists( $field_data, '__toString' ) ) {
			return (string) $field_data;
		}

		// If it's null, return empty string.
		if ( is_null( $field_data ) ) {
			return '';
		}

		// If it's a scalar (int, float, bool), cast safely.
		if ( is_scalar( $field_data ) ) {
			return (string) $field_data;
		}

		// For all else, return empty string to avoid type errors.
		return '';
	}

	/**
	 * Returns attributes array with default value wherever required.
	 *
	 * @param array $default_attr default attribute value array from attributes.php.
	 * @param array $attr saved attributes data from database.
	 * @return array
	 * @since 1.0.0
	 */
	public static function get_fallback_values( $default_attr, $attr ) {
		foreach ( $default_attr as $key => $value ) {
			// sets default value if key is not available in database.
			if ( ! isset( $attr[ $key ] ) ) {
				$attr[ $key ] = $value;
			}
		}

		return $attr;
	}

	/**
	 * Get Dynamic link data as per fields.
	 *
	 * @param string             $source Link Source.
	 * @param array<int, string> $fields Data fields.
	 * @param string|int         $post_id Post ID.
	 *
	 * @return string Link URL.
	 * @since 1.0.0
	 */
	public function get_dynamic_link( $source, $fields, $post_id ) {
		if ( 'null' === $post_id ) {
			global $post;
			if ( isset( $post->ID ) ) {
				$post_id = $post->ID;
			}
		}
		$link = '';
		switch ( $source ) {
			case 'post_permalink':
				$link = \get_permalink( $post_id );
				break;
			case 'comments_area':
				$link = \get_comments_link( $post_id );
				break;
			case 'featured_image_data':
				$link = \get_the_post_thumbnail_url( $post_id, 'full' );
				break;
			case 'avatar':
				$author_id = get_post_field( 'post_author', $post_id );
				$link      = \get_avatar_url( $author_id );
				break;
			case 'custom_field':
				$meta_key = 'custom_key' === $fields[2] ? $fields[3] : $fields[2];
				if ( class_exists( 'ACF' ) ) {
					$fields = get_field_objects( $post_id );
					if ( isset( $fields[ $meta_key ] ) ) {
						$value = get_field( $meta_key, $post_id );
						$link  = $value;
					}
				} else {
					$link = get_post_meta( $post_id, $meta_key, true );
				}
				break;
			case 'author_info':
				$author_id = get_post_field( 'post_author', $post_id );
				$meta_key  = 'custom_key' === $fields[2] ? $fields[3] : $fields[2];
				if ( class_exists( 'ACF' ) ) {
					$fields = get_field_objects( 'user_' . $author_id );
				}
				if ( isset( $fields[ $meta_key ] ) ) {
					$value = get_field( $meta_key, 'user_' . $author_id );
					$link  = $value;
				}
				$link = get_user_meta( absint( $author_id ), $meta_key, true );
				break;
			case 'author_archive':
					$author_id = get_post_field( 'post_author', $post_id );
					$link      = get_author_posts_url( absint( $author_id ) );
				break;
			case 'author_page':
					$author_id = get_post_field( 'post_author', $post_id );
					$link      = get_the_author_meta( 'url', absint( $author_id ) );
				break;
			default:
				$link = '';
				break;
		}//end switch
		return $this->get_string_value( $link );
	}

	/**
	 * Checks if current value is string or else returns default value
	 *
	 * @param mixed  $data data which need to be checked if is string.
	 * @param string $default value can be set is $data is not a string, defaults to empty string.
	 * @since 1.0.0
	 * @return string
	 */
	public function get_string_value( $data, $default = '' ) {
		return is_string( $data ) ? $data : $default;
	}

}
