<?php
namespace SpectraPro\Includes\Extensions\PopupBuilder;

/**
 * Pro Popup Builder Class.
 *
 * @package SpectraPro
 *
 * @since 1.0.0
 */
class Spectra_Pro_Popup_Builder {

	/**
	 * Initializator.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		$self = new self();
		add_action( 'register_spectra_pro_popup_meta', array( $self, 'register_pro_meta' ), 10, 0 );
		add_action( 'spectra_pro_popup_dashboard', array( $self, 'add_pro_admin_scripts_and_ajax' ), 10, 0 );
		add_filter( 'spectra_pro_admin_popup_list_titles', array( $self, 'add_pro_admin_list_titles' ), 10, 1 );
		add_action( 'spectra_pro_admin_popup_list_content', array( $self, 'add_pro_admin_list_content' ), 10, 2 );
		add_filter( 'spectra_pro_popup_frontend_js', array( $self, 'upgrade_frontend_js' ), 10, 5 );
		add_filter( 'spectra_pro_popup_display_filters', array( $self, 'render_shortcode_conditionally' ), 10, 2 );
		add_action( 'wp_ajax_spectra_popup_builder_get_posts_by_query', array( $self, 'spectra_popup_builder_get_posts_by_query' ) );
	}

	/**
	 * Get location selection options.
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public static function get_location_selections() {

		$args = array(
			'public'   => true,
			'_builtin' => true,
		);

		/**
		 * Post Types will always be of type WP_Post_Type[] instead of string[].
		 * Since we're passing param 2 as 'objects' instead of 'names'.
		 *
		 * @var \WP_Post_Type[] $post_types  An array of all Inbuilt Post Types.
		 */
		$post_types = get_post_types( $args, 'objects' );
		unset( $post_types['attachment'] );

		$args['_builtin'] = false;

		/**
		 * Post Types will always be of type WP_Post_Type[] instead of string[].
		 * Since we're passing param 2 as 'objects' instead of 'names'.
		 *
		 * @var \WP_Post_Type[] $custom_post_type  An array of all Custom Post Types.
		 */
		$custom_post_type = get_post_types( $args, 'objects' );

		$post_types = array_merge( $post_types, $custom_post_type );

		$special_pages = array(
			'special-404'    => __( '404 Page', 'spectra-pro' ),
			'special-search' => __( 'Search Page', 'spectra-pro' ),
			'special-blog'   => __( 'Blog / Posts Page', 'spectra-pro' ),
			'special-front'  => __( 'Front Page', 'spectra-pro' ),
			'special-date'   => __( 'Date Archive', 'spectra-pro' ),
			'special-author' => __( 'Author Archive', 'spectra-pro' ),
		);

		if ( class_exists( 'WooCommerce' ) ) {
			$special_pages['special-woo-shop'] = __( 'WooCommerce Shop Page', 'spectra-pro' );
		}

		$selection_options = array(
			'basic'         => array(
				'label' => __( 'Basic', 'spectra-pro' ),
				'value' => array(
					'basic-singulars' => __( 'All Singulars', 'spectra-pro' ),
					'basic-archives'  => __( 'All Archives', 'spectra-pro' ),
				),
			),

			'special-pages' => array(
				'label' => __( 'Special Pages', 'spectra-pro' ),
				'value' => $special_pages,
			),
		);

		$args = array(
			'public' => true,
		);

		/**
		 * Taxonomies will always be of type WP_Taxonomy[] instead of string[].
		 * Since we're passing param 2 as 'objects' instead of 'names'.
		 *
		 * @var \WP_Taxonomy[] $taxonomies  An array of all taxonomies.
		 */
		$taxonomies = get_taxonomies( $args, 'objects' );

		if ( ! empty( $taxonomies ) ) {
			foreach ( $taxonomies as $taxonomy ) {
				// skip post format taxonomy.
				if ( 'post_format' === $taxonomy->name ) {
					continue;
				}

				foreach ( $post_types as $post_type ) {
					$post_opt = self::get_post_target_rule_options( $post_type, $taxonomy );

					if ( isset( $selection_options[ $post_opt['post_key'] ] ) ) {

						if ( ! empty( $post_opt['value'] ) && is_array( $post_opt['value'] ) ) {

							foreach ( $post_opt['value'] as $key => $value ) {

								if ( ! in_array( $value, $selection_options[ $post_opt['post_key'] ]['value'], true ) ) {
									$selection_options[ $post_opt['post_key'] ]['value'][ $key ] = $value;
								}
							}
						}
					} else {
						$selection_options[ $post_opt['post_key'] ] = array(
							'label' => $post_opt['label'],
							'value' => $post_opt['value'],
						);
					}
				}//end foreach
			}//end foreach
		}//end if

		$selection_options['specific-target'] = array(
			'label' => __( 'Specific Target', 'spectra-pro' ),
			'value' => array(
				'specifics' => __( 'Specific Pages / Posts / CPTs', 'spectra-pro' ),
			),
		);

		return $selection_options;
	}

	/**
	 * Get target rules for generating the markup for rule selector.
	 *
	 * @param \WP_Post_Type $post_type  Post type parameter.
	 * @param \WP_Taxonomy  $taxonomy   Taxonomy for creating the target rule markup.
	 * @return array                    The post output.
	 *
	 * @since 1.0.0
	 */
	public static function get_post_target_rule_options( $post_type, $taxonomy ) {

		$post_key    = str_replace( ' ', '-', strtolower( $post_type->label ) );
		$post_label  = ucwords( $post_type->label );
		$post_name   = $post_type->name;
		$post_option = array();

		/* translators: %s post label */
		$all_posts                          = sprintf( __( 'All %s', 'spectra-pro' ), $post_label );
		$post_option[ $post_name . '|all' ] = $all_posts;

		if ( 'pages' !== $post_key ) {
			/* translators: %s post label */
			$all_archive                                = sprintf( __( 'All %s Archive', 'spectra-pro' ), $post_label );
			$post_option[ $post_name . '|all|archive' ] = $all_archive;
		}

		if ( in_array( $post_type->name, $taxonomy->object_type, true ) ) {
			$tax_label = ucwords( $taxonomy->label );
			$tax_name  = $taxonomy->name;

			/* translators: %s taxonomy label */
			$tax_archive = sprintf( __( 'All %s Archive', 'spectra-pro' ), $tax_label );

			$post_option[ $post_name . '|all|taxarchive|' . $tax_name ] = $tax_archive;
		}

		$post_output['post_key'] = $post_key;
		$post_output['label']    = $post_label;
		$post_output['value']    = $post_option;

		return $post_output;
	}

	/**
	 * Register the Pro Meta Tags for the Spectra Popup Post Type.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function register_pro_meta() {

		$meta_args_popup_trigger = array(
			'single'        => true,
			'type'          => 'string',
			'default'       => 'load',
			'auth_callback' => '__return_true',
			'show_in_rest'  => true,
		);

		$meta_args_popup_trigger_delay = array(
			'single'        => true,
			'type'          => 'integer',
			'default'       => 0,
			'auth_callback' => '__return_true',
			'show_in_rest'  => true,
		);

		$meta_args_popup_display_inclusions = array(
			'single'        => true,
			'type'          => 'object',
			'default'       => array(
				'rule'         => array(),
				'specific'     => array(),
				'specificText' => array(),
			),
			'auth_callback' => '__return_true',
			'show_in_rest'  => array(
				'schema' => array(
					'type'       => 'object',
					'properties' => array(
						'rule'         => array(
							'type' => 'array',
						),
						'specific'     => array(
							'type' => 'array',
						),
						'specificText' => array(
							'type' => 'array',
						),
					),
				),
			),
		);

		$meta_args_popup_display_exclusions = array(
			'single'        => true,
			'type'          => 'object',
			'default'       => array(
				'rule'         => array(),
				'specific'     => array(),
				'specificText' => array(),
			),
			'auth_callback' => '__return_true',
			'show_in_rest'  => array(
				'schema' => array(
					'type'       => 'object',
					'properties' => array(
						'rule'         => array(
							'type' => 'array',
						),
						'specific'     => array(
							'type' => 'array',
						),
						'specificText' => array(
							'type' => 'array',
						),
					),
				),
			),
		);

		register_post_meta( 'spectra-popup', 'spectra-popup-trigger', $meta_args_popup_trigger );
		register_post_meta( 'spectra-popup', 'spectra-popup-trigger-delay', $meta_args_popup_trigger_delay );
		register_post_meta( 'spectra-popup', 'spectra-popup-display-inclusions', $meta_args_popup_display_inclusions );
		register_post_meta( 'spectra-popup', 'spectra-popup-display-exclusions', $meta_args_popup_display_exclusions );
	}

	/**
	 * Add the Pro Popup Admin Scripts and Ajax only if the current page is the Popup Admin Page.
	 *
	 * @since 1.0.2
	 * @return void
	 */
	public function add_pro_admin_scripts_and_ajax() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_pro_admin_scripts' ) );
		add_action( 'admin_footer', array( $this, 'add_pro_admin_footer_scripts' ) );
		add_action( 'wp_ajax_uag_trigger_popup_quickview', array( $this, 'get_quickview_details' ) );
	}

	/**
	 * Check if the current page is the Popup Admin Page.
	 *
	 * @since 1.0.2
	 * @return boolean
	 */
	private function is_this_the_popup_admin() {
		global $pagenow;
		$screen = get_current_screen();
		// If the current screen exists, return the result of the comparison, else return false.
		return $screen ? ( 'spectra-popup' === $screen->post_type && 'edit.php' === $pagenow ) : false;
	}

	/**
	 * Enqueue the Pro Popup Admin Scripts.
	 *
	 * @since 1.0.2
	 * @return void
	 */
	public function enqueue_pro_admin_scripts() {
		if ( ! $this->is_this_the_popup_admin() ) {
			return;
		}

		$extension = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		wp_register_script(
			'spectra-pro-popup-builder-admin-js',
			SPECTRA_PRO_URL . 'assets/js/spectra-pro-popup-builder-admin' . $extension . '.js',
			array(),
			SPECTRA_PRO_VER,
			false
		);
		wp_register_style(
			'spectra-pro-popup-builder-admin',
			SPECTRA_PRO_URL . 'assets/css/spectra-pro-popup-builder-admin' . $extension . '.css',
			array(),
			SPECTRA_PRO_VER
		);
		wp_localize_script(
			'spectra-pro-popup-builder-admin-js',
			'spectra_pro_popup_builder_admin',
			array(
				'ajax_url'                              => admin_url( 'admin-ajax.php' ),
				'spectra_pro_popup_builder_admin_nonce' => wp_create_nonce( 'spectra_pro_popup_builder_admin_nonce' ),
			)
		);
		wp_enqueue_script( 'spectra-pro-popup-builder-admin-js' );
		wp_enqueue_style( 'spectra-pro-popup-builder-admin' );
	}


	/**
	 * Get the current Popup's QuickView Details from the Admin Table.
	 *
	 * @since 1.0.2
	 * @return void
	 */
	public function get_quickview_details() {
		check_ajax_referer( 'spectra_pro_popup_builder_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		if ( ! isset( $_POST['popup_id'] ) ) {
			wp_send_json_error();
		}

		// Format the Popup's ID.
		$formatted_post_id = absint( $_POST['popup_id'] );

		// Get the Popup's Type and Display Rules.
		$popup_type              = get_post_meta( $formatted_post_id, 'spectra-popup-type', true );
		$popup_inclusion_ruleset = get_post_meta( $formatted_post_id, 'spectra-popup-display-inclusions', true );
		$popup_exclusion_ruleset = get_post_meta( $formatted_post_id, 'spectra-popup-display-exclusions', true );

		// If the inclusion or exclusion rules are not set, set them to an empty array.
		if ( ! is_array( $popup_inclusion_ruleset ) ) {
			$popup_inclusion_ruleset = array();
		}
		if ( ! is_array( $popup_exclusion_ruleset ) ) {
			$popup_exclusion_ruleset = array();
		}

		// Get the Popup's Edit Label.
		switch ( $popup_type ) {
			case 'banner':
				$edit_label = __( 'Edit Info Bar', 'spectra-pro' );
				break;
			case 'popup':
				$edit_label = __( 'Edit Popup', 'spectra-pro' );
				break;
			default:
				$edit_label = __( 'Edit', 'spectra-pro' );
		}

		// Set the required data.
		$required_data = array(
			'name'       => get_the_title( $formatted_post_id ),
			'type'       => $popup_type,
			'inclusions' => $this->get_display_condition_ruleset_labels( $popup_inclusion_ruleset ),
			'exclusions' => $this->get_display_condition_ruleset_labels( $popup_exclusion_ruleset ),
			'status'     => get_post_status( $formatted_post_id ),
			'date'       => get_the_date( 'F j, Y', $formatted_post_id ),
			'edit'       => get_edit_post_link( $formatted_post_id ),
			'editLabel'  => $edit_label,
		);

		wp_send_json_success( $required_data );
	}

	/**
	 * Enqueue the Pro Popup Admin Footer Scripts.
	 *
	 * @since 1.0.2
	 * @return void
	 */
	public function add_pro_admin_footer_scripts() {
		if ( ! $this->is_this_the_popup_admin() ) {
			return;
		}
		// Add the popup builder admin modal.
		?>
			<div class="spectra-popup-builder__modal--overlay" data-popup-id="0">
				<div class="spectra-popup-builder__modal">
					<div class="spectra-popup-builder__modal--header spectra-popup-builder__modal--padded">
						<h2 class="spectra-popup-builder__modal--title"></h2>
						<div class="spectra-popup-builder__modal--type"></div>
					</div>
					<div class="spectra-popup-builder__modal--body">
						<div class="spectra-popup-builder__modal--padded"><?php echo esc_html__( 'Display On:', 'spectra-pro' ); ?></div>
						<div class="spectra-popup-builder__modal--body-inclusions spectra-popup-builder__modal--padded"></div>
						<div class="spectra-popup-builder__modal--padded"><?php echo esc_html__( 'Do Not Display On:', 'spectra-pro' ); ?></div>
						<div class="spectra-popup-builder__modal--body-exclusions spectra-popup-builder__modal--padded"></div>
					</div>
					<div class="spectra-popup-builder__modal--footer spectra-popup-builder__modal--padded">
						<div class="spectra-popup-builder__modal--footer-details">
							<b><?php echo esc_html__( 'Status:', 'spectra-pro' ); ?></b>&nbsp;<span class="spectra-popup-builder__modal--status"></span><b>&nbsp;|&nbsp;<?php echo esc_html__( 'Created:', 'spectra-pro' ); ?></b>&nbsp;<span class="spectra-popup-builder__modal--date"></span>
						</div>
						<a href="javascript:void(0)" class="spectra-popup-builder__modal--edit button button-primary">
							<?php echo esc_html__( 'Edit', 'spectra-pro' ); ?>
						</a>
					</div>
					<button class="spectra-popup-builder__modal--close">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
				</div>
			</div>
		<?php
	}

	/**
	 * Get the Popup's Display Condition Labels.
	 *
	 * @param array $display_condition  The Current Display Condition.
	 * @since 1.0.2
	 * @return array                    The Display Condition Labels, if any.
	 */
	private function get_display_condition_ruleset_labels( $display_condition ) {
		// If the display condition is not an array, return an empty array.
		if ( ! is_array( $display_condition['rule'] ) ) {
			return array();
		}

		$display_condition_labels = array();

		// Loop through the display condition rules and add the labels to the array.
		foreach ( $display_condition['rule'] as $rule ) {
			if ( ! empty( $rule['label'] ) && 'specifics' !== $rule['value'] ) {
				array_push( $display_condition_labels, $rule['label'] );
			}
		}

		// If the display condition specifics is not an array, return the existing labels.
		if ( ! is_array( $display_condition['specificText'] ) ) {
			return $display_condition_labels;
		}

		// Loop through the display condition specifics and add the labels to the array.
		foreach ( $display_condition['specificText'] as $specific ) {
			if ( ! empty( $specific['label'] ) ) {
				array_push( $display_condition_labels, $specific['label'] );
			}
		}

		return $display_condition_labels;
	}

	/**
	 * Add Pro Columns to the Popup Builder Admin Post List.
	 *
	 * @param array $columns  The current popup list columns.
	 * @return array          The updated columns.
	 *
	 * @since 1.0.0
	 */
	public function add_pro_admin_list_titles( $columns ) {
		if ( is_array( $columns ) ) {
			$columns['spectra_popup_trigger'] = __( 'Trigger', 'spectra-pro' );
			unset( $columns['spectra_popup_type'] );
			$columns['spectra_popup_details'] = __( 'Quick View', 'spectra-pro' );
		}

		return $columns;
	}

	/**
	 * Add Pro Column Content to the Popup Builder Admin Post List.
	 *
	 * @param string $column   Name of the current column.
	 * @param int    $post_id  Current Post ID.
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function add_pro_admin_list_content( $column, $post_id ) {
		if ( ! is_int( $post_id ) ) {
			return;
		}
		switch ( $column ) {
			case 'spectra_popup_trigger':
				$trigger = get_post_meta( $post_id, 'spectra-popup-trigger', true );
				if ( ! is_string( $trigger ) ) {
					break;
				}
				switch ( $trigger ) {
					case 'load':
						$trigger_delay = get_post_meta( $post_id, 'spectra-popup-trigger-delay', true );
						if ( is_numeric( $trigger_delay ) && $trigger_delay > 0 ) {
							echo sprintf(
								/* translators: %1$s break tag and opening emphasis tag, %2$s number of seconds, %3$s singular or plural form of second, %4%s closing emphasis tag */
								esc_html__( 'On Load%1$sAfter %2$s %3$s%4$s', 'spectra-pro' ),
								'<br/><em>',
								esc_attr( strval( $trigger_delay ) ),
								esc_html( _n( 'Second', 'Seconds', intval( $trigger_delay ), 'spectra-pro' ) ),
								'</em>'
							);
						} else {
							echo esc_html__( 'On Load', 'spectra-pro' );
						}
						break;
					case 'exit':
						echo esc_html__( 'Exit Intent', 'spectra-pro' );
						break;
					case 'element':
						echo esc_html__( 'Custom Element', 'spectra-pro' );
						echo '<br/><em>spectra-popup-trigger-' . esc_attr( strval( $post_id ) ) . '</em>';
						break;
				}//end switch
				break;
			case 'spectra_popup_details':
				echo '<button class="spectra-popup-builder__button" data-popup_id="' . esc_attr( strval( $post_id ) ) . '"><span class="dashicons dashicons-visibility"></span></button>';
				break;
			default:
				break;
		}//end switch
	}

	/**
	 * Check whether to render this popup or not based on Pro Display Conditions.
	 *
	 * Note:
	 * Popups can be included in a general post type AND be excluded specifically.
	 *
	 * @param bool $render_status  The current render status of this popup based on whether it is enabled.
	 * @param int  $post_id        The current post ID to render this popup on.
	 * @return bool                Whether to render this popup on this post or not.
	 *
	 * @since 1.0.0
	 */
	public function render_shortcode_conditionally( $render_status, $post_id ) {
		// If this popup is not enabled, exit directly.
		if ( ! $render_status ) {
			return $render_status;
		}

		// Return early if unable to get the popup ID.
		$popup_id = get_the_ID();
		if ( false === $popup_id ) {
			return $render_status;
		}

		// Get the display inclusion meta, return if it's not defined.
		$include_on = get_post_meta( $popup_id, 'spectra-popup-display-inclusions', true );
		if ( ! is_array( $include_on ) ) {
			return $render_status;
		}

		// Get the display exclusion meta, return if it's not defined.
		$exclude_on = get_post_meta( $popup_id, 'spectra-popup-display-exclusions', true );
		if ( ! is_array( $exclude_on ) ) {
			return $render_status;
		}

		// Parse the exclusion rules to check if this popup is excluded from the current post (generally or specifically).
		$is_excluded = $this->parse_popup_display_condition( $post_id, $exclude_on );

		// Exit Early - Don't render this popup if it's excluded.
		if ( $is_excluded ) {
			return false;
		}

		// Parse the inclusion rules to check if this popup is included on the current post (generally or specifically).
		$is_included = $this->parse_popup_display_condition( $post_id, $include_on );
		if ( $is_included ) {
			return true;
		}

		// If this popup had implicit include rules, don't render it - else render it.
		return empty( $include_on['rule'] );
	}

	/**
	 * Parse the generic / specific rules for exclusion / inclusion of this popup.
	 *
	 * @param int   $post_id  The current post ID to render this popup on.
	 * @param array $rules    Array of rules for the inclusion / exclusion meta.
	 * @return boolean        Whether or not the current meta rules match for the current page and popup.
	 *
	 * @since 1.0.0
	 */
	public function parse_popup_display_condition( $post_id, $rules ) {

		if ( empty( $rules['rule'] ) ) {
			return false;
		}

		$is_in_rule        = false;
		$current_post_type = get_post_type( $post_id );

		foreach ( $rules['rule'] as $key => $rule ) {
			if ( empty( $rule ) ) {
				continue;
			}

			if ( is_array( $rule ) && isset( $rule['value'] ) ) {
				$rule = $rule['value'];
			}

			$rule_case = ( strrpos( $rule, 'all' ) !== false ) ? 'all' : $rule;

			switch ( $rule_case ) {

				// If 'Basic --> All Singulars' is selected.
				case 'basic-singulars':
					if ( is_singular() ) {
						$is_in_rule = true;
					}
					break;

				// If 'Basic --> All Archives' is selected.
				case 'basic-archives':
					if ( is_archive() ) {
						$is_in_rule = true;
					}
					break;

				// If 'Special Pages --> 404 Page' is selected.
				case 'special-404':
					if ( is_404() ) {
						$is_in_rule = true;
					}
					break;

				// If 'Special Pages --> Search Page' is selected.
				case 'special-search':
					if ( is_search() ) {
						$is_in_rule = true;
					}
					break;

				// If 'Special Pages --> Blog / Post Page' is selected.
				case 'special-blog':
					if ( is_home() ) {
						$is_in_rule = true;
					}
					break;

				// If 'Special Pages --> Front Page' is selected.
				case 'special-front':
					if ( is_front_page() ) {
						$is_in_rule = true;
					}
					break;

				// If 'Special Pages --> Date Archive' is selected.
				case 'special-date':
					if ( is_date() ) {
						$is_in_rule = true;
					}
					break;

				// If 'Special Pages --> Author Archive' is selected.
				case 'special-author':
					if ( is_author() ) {
						$is_in_rule = true;
					}
					break;

				// If 'Special Pages --> WooCommerce Shop Page' is selected.
				case 'special-woo-shop':
					if ( function_exists( 'is_shop' ) && is_shop() ) {
						$is_in_rule = true;
					}
					break;

				// If '[postTypes] --> All [postTypes|taxonomy|archive|etc]' is selected.
				case 'all':
					// First split apart the rule to determine the depth of this rule.
					$rule_data = explode( '|', $rule );

					// Then set the depth to check if this page needs this popup.
					// The depth is as follows: postType | 'all' | archiveType | taxonomy.
					$rule_post_type    = isset( $rule_data[0] ) ? $rule_data[0] : false;
					$rule_archive_type = isset( $rule_data[2] ) ? $rule_data[2] : false;
					$rule_taxonomy     = isset( $rule_data[3] ) ? $rule_data[3] : false;

					// Check if this rule was not for an archive type.
					if ( false === $rule_archive_type ) {

						// Since this is not an archive type, check if the post ID is valid and the rule matches the current post type.
						if ( $post_id && $current_post_type === $rule_post_type ) {
							$is_in_rule = true;
						}
						break;
					}

					// Check if the current page is not an archive.
					if ( is_archive() ) {
						break;
					}

					// Since this is an archive, get the post type without an ID.
					$current_post_type = get_post_type();

					// Check if the current post type is not the post type in the rule.
					if ( $current_post_type !== $rule_post_type ) {
						break;
					}

					// Check what kind of archive this is.
					switch ( $rule_archive_type ) {

						case 'archive':
							$is_in_rule = true;
							break;

						case 'taxarchive':
							$current_query_obj = get_queried_object();
							if ( null === $current_query_obj || ! isset( $current_query_obj->taxonomy ) ) {
								break;
							}

							$current_taxonomy = $current_query_obj->taxonomy;
							if ( $current_taxonomy === $rule_taxonomy ) {
								$is_in_rule = true;
							}
							break;
					}
					break;

				// If 'Specific Target --> Specific Pages / Posts / Taxonomies' is selected.
				case 'specifics':
					// Continue only if this rule has a list of speficic targets.
					if ( ! isset( $rules['specific'] ) || ! is_array( $rules['specific'] ) ) {
						break;
					}

					foreach ( $rules['specific'] as $specific_page ) {

						$specific_data = explode( '-', $specific_page );

						$specific_post_type = isset( $specific_data[0] ) ? $specific_data[0] : false;
						$specific_post_id   = isset( $specific_data[1] ) ? (int) $specific_data[1] : 0;
						$specific_single    = isset( $specific_data[2] ) ? $specific_data[2] : false;

						// Check what kind of post this is.
						switch ( $specific_post_type ) {

							case 'post':
								if ( $specific_post_id === $post_id ) {
									$is_in_rule = true;
								}
								break;

							case 'tax':
								if ( 'single' === $specific_single && is_singular() ) {
									$term_details = get_term( $specific_post_id );
									if ( isset( $term_details->taxonomy ) ) {
										$has_term = has_term( $specific_post_id, $term_details->taxonomy, $post_id );
										if ( $has_term ) {
											$is_in_rule = true;
										}
									}
								} else {
									$tax_id = get_queried_object_id();
									if ( $specific_post_id === $tax_id ) {
										$is_in_rule = true;
									}
								}
								break;
						}//end switch
					}//end foreach
					break;
			}//end switch

			if ( $is_in_rule ) {
				break;
			}
		}//end foreach

		return $is_in_rule;
	}

	/**
	 * Ajax handeler to return the posts based on the search query.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function spectra_popup_builder_get_posts_by_query() {

		check_ajax_referer( 'spectra_pro_ajax_nonce', 'nonce' );

		$search_string = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
		$data          = array();
		$result        = array();

		$args = array(
			'public'   => true,
			'_builtin' => false,
		);

		$post_types = get_post_types( $args );

		$post_types['Posts'] = 'post';
		$post_types['Pages'] = 'page';

		foreach ( $post_types as $key => $post_type ) {

			$data = array();

			add_filter( 'posts_search', array( $this, 'search_only_titles' ), 10, 2 );

			$query = new \WP_Query(
				array(
					's'              => $search_string,
					'post_type'      => $post_type,
					'posts_per_page' => -1,
				)
			);

			while ( $query->have_posts() ) :
				$query->the_post();

				$title  = get_the_title();
				$title .= ( $query->post && ( 0 !== $query->post->post_parent ) ) ? ' (' . get_the_title( $query->post->post_parent ) . ')' : '';
				$id     = get_the_id();
				array_push(
					$data,
					array(
						'id'    => 'post-' . $id,
						'title' => $title,
					)
				);
			endwhile;

			if ( ! empty( $data ) ) {
				array_push(
					$result,
					array(
						'title'    => $key,
						'children' => $data,
					)
				);
			}
		}//end foreach

		// return the result in json.
		wp_send_json_success( $result );
	}

	/**
	 * Return search results only by post title.
	 *
	 * @param string    $search       Search SQL for WHERE clause.
	 * @param \WP_Query $wp_query  The current WP_Query object.
	 * @return string              The Modified Search SQL for WHERE clause.
	 *
	 * @since 1.0.0
	 */
	public function search_only_titles( $search, $wp_query ) {
		if ( empty( $search ) || empty( $wp_query->query_vars['search_terms'] ) ) {
			return $search;
		}

		global $wpdb;

		$query_vars = $wp_query->query_vars;
		if ( ! is_array( $query_vars ) ) {
			return $search;
		}

		$match_helper = empty( $query_vars['exact'] ) ? '%' : '';
		$search       = array();

		foreach ( $query_vars['search_terms'] as $term ) {
			$search[] = $wpdb->prepare( "$wpdb->posts.post_title LIKE %s", $match_helper . $wpdb->esc_like( $term ) . $match_helper );
		}

		$search = ' AND ' . implode( ' AND ', $search );

		return $search;
	}


	/**
	 * Add Pro Meta Based Conditions to JS Popup Builder Block.
	 *
	 * @param string $js              The current block JS script.
	 * @param int    $id              The current block ID.
	 * @param array  $attr            The current block attributes.
	 * @param bool   $is_push_banner  A boolean stating if this is a push banner or not.
	 * @param int    $popup_timer     The timer of the current popup based on if it's a push banner.
	 * @return string                 The upgraded JS script or the Default JS Script.
	 *
	 * @since 1.0.0
	 */
	public function upgrade_frontend_js( $js, $id, $attr, $is_push_banner, $popup_timer ) {
		$popup_id = get_the_ID();
		if ( ! $popup_id ) {
			return $js;
		}

		$trigger = get_post_meta( $popup_id, 'spectra-popup-trigger', true );
		if ( ! is_string( $trigger ) ) {
			return $js;
		}

		$trigger_delay = get_post_meta( $popup_id, 'spectra-popup-trigger-delay', true );
		if ( ! $trigger_delay ) {
			$trigger_delay = 0;
		}

		// Convert the Seconds to Milliseconds.
		$trigger_delay *= 1000;

		ob_start();

		switch ( $trigger ) {
			case 'load':
				?>
					window.addEventListener( 'DOMContentLoaded', () => {
						const blockScope = document.querySelector( '.uagb-block-<?php echo esc_attr( strval( $id ) ); ?>' );
						if ( ! blockScope ) {
							return;
						}
						<?php
							// The front-end JS common responsive code snippet cannot be escaped.
							echo $this->frontend_js_responsive_snippet(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The output buffer is escaped.
						?>

						<?php
							// The front-end JS common repetition code snippet cannot be escaped.
							echo $this->frontend_js_repetition_snippet( $popup_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The args passed to the function is already escaped.
						?>

						const theBody = document.querySelector( 'body' );

						setTimeout( () => {
							blockScope.style.display = 'flex';
						}, <?php echo intval( $trigger_delay ); ?> );
						setTimeout( () => {
							<?php
								// The front-end JS common load code snippet cannot be escaped.
								echo $this->frontend_js_load_snippet( $attr, $is_push_banner ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The args passed to the function is already escaped.
							?>
						}, <?php echo intval( $trigger_delay ) + 100; ?> );

						<?php
							// The front-end JS common close code snippet cannot be escaped.
							echo $this->frontend_js_close_snippet( $attr, $popup_id, true, $is_push_banner, $popup_timer, $trigger_delay ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The args passed to the function is already escaped.
						?>
					} );
				<?php
				break;
			case 'exit':
				?>
					window.addEventListener( 'DOMContentLoaded', () => {
						const exitIntent = ( event ) => {
							if ( ! event.toElement && ! event.relatedTarget ) {
								document.removeEventListener( 'mouseout', exitIntent );
								const blockScope = document.querySelector( '.uagb-block-<?php echo esc_attr( strval( $id ) ); ?>' );
								if ( ! blockScope ) {
									return;
								}
								<?php
									// The front-end JS common responsive code snippet cannot be escaped.
									echo $this->frontend_js_responsive_snippet(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								?>

								<?php
									// The front-end JS common repetition code snippet cannot be escaped.
									echo $this->frontend_js_repetition_snippet( $popup_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The args passed to the function is already escaped.
								?>

								const theBody = document.querySelector( 'body' );

								blockScope.style.display = 'flex';
								setTimeout( () => {
									<?php
										// The front-end JS common load code snippet cannot be escaped.
										echo $this->frontend_js_load_snippet( $attr, $is_push_banner ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The args passed to the function is already escaped.
									?>
								}, 100 );

								<?php
									// The front-end JS common close code snippet cannot be escaped.
									echo $this->frontend_js_close_snippet( $attr, $popup_id, true, $is_push_banner, $popup_timer, 0 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The args passed to the function is already escaped.
								?>
							}
						}
						document.addEventListener( 'mouseout', exitIntent );
					} );
				<?php
				break;
			case 'element':
				?>
					window.addEventListener( 'DOMContentLoaded', () => {
						const popupTriggers = document.querySelectorAll( '.spectra-popup-trigger-<?php echo esc_attr( strval( $popup_id ) ); ?>' );
						for ( let i = 0; i < popupTriggers.length; i++ ) {
							popupTriggers[ i ].style.cursor = 'pointer';
							popupTriggers[ i ].addEventListener( 'click', () => {
								const blockScope = document.querySelector( '.uagb-block-<?php echo esc_attr( strval( $id ) ); ?>' );
								if ( ! blockScope ) {
									return;
								}
								<?php
									// The front-end JS common responsive code snippet cannot be escaped.
									echo $this->frontend_js_responsive_snippet(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								?>

								const theBody = document.querySelector( 'body' );

								blockScope.style.display = 'flex';
								setTimeout( () => {
									<?php
										// The front-end JS common load code snippet cannot be escaped.
										echo $this->frontend_js_load_snippet( $attr, $is_push_banner ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The args passed to the function is already escaped.
									?>
								}, 100 );

								<?php
									// The front-end JS common close code snippet cannot be escaped.
									echo $this->frontend_js_close_snippet( $attr, $popup_id, false, $is_push_banner, $popup_timer, 0 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The args passed to the function is already escaped.
								?>
							} );
						}
					} );
				<?php
				break;
			default:
				// The block of JS code sent to this acction cannot be escaped.
				echo $js; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				break;
		}//end switch

		$updated_js = ob_get_clean();

		return is_string( $updated_js ) ? $updated_js : $js;
	}

	/**
	 * Snippet of the responsive handling for all pro JS renders.
	 *
	 * @return string  The output buffer.
	 *
	 * @since 1.0.0
	 */
	private function frontend_js_responsive_snippet() {
		ob_start();
		?>
			const deviceWidth = ( window.innerWidth > 0 ) ? window.innerWidth : screen.width;
			if ( blockScope.classList.contains( 'uag-hide-desktop' ) && deviceWidth > 1024 ) {
				blockScope.remove();
				return;
			} else if ( blockScope.classList.contains( 'uag-hide-tab' ) && ( deviceWidth <= 1024 && deviceWidth > 768 ) ) {
				blockScope.remove();
				return;
			} else if ( blockScope.classList.contains( 'uag-hide-mob' ) && deviceWidth <= 768 ) {
				blockScope.remove();
				return;
			}
		<?php
		$output = ob_get_clean();
		return is_string( $output ) ? $output : '';
	}

	/**
	 * Snippet of common repetition JS code.
	 *
	 * @param int $popup_id  The popup ID.
	 * @return string        The output buffer.
	 *
	 * @since 1.0.0
	 */
	private function frontend_js_repetition_snippet( $popup_id ) {
		// Either check if the localStorage has been set before - If not, create it.
		// Or if this popup has an updated repetition number, reset the localStorage.
		$repetition = get_post_meta( $popup_id, 'spectra-popup-repetition', true );
		if ( ! is_numeric( $repetition ) ) {
			return '';
		}
		ob_start();
		?>
		let popupSesh = JSON.parse( localStorage.getItem( 'spectraPopup<?php echo esc_attr( strval( $popup_id ) ); ?>' ) );
		const repetition = <?php echo intval( $repetition ); ?>;
		if ( null === popupSesh || repetition !== popupSesh[1] ) {
			<?php // [0] is the updating repetition number, [1] is the original repetition number. ?>
			const repetitionArray = [
				repetition,
				repetition,
			];
			localStorage.setItem( 'spectraPopup<?php echo esc_attr( strval( $popup_id ) ); ?>', JSON.stringify( repetitionArray ) );
			popupSesh = JSON.parse( localStorage.getItem( 'spectraPopup<?php echo esc_attr( strval( $popup_id ) ); ?>' ) );
		}

		if ( 0 === popupSesh[0] ) {
			blockScope.remove();
			return;
		}
		<?php
		$output = ob_get_clean();
		return is_string( $output ) ? $output : '';
	}

	/**
	 * Snippet of common close JS function and calls required for all popups.
	 *
	 * @param array $attr            The array of block attributes.
	 * @param int   $popup_id        The popup ID.
	 * @param bool  $delete          Determines whether or not the popup should be deleted when closed.
	 * @param bool  $is_push_banner  A boolean stating if this is a push banner or not.
	 * @param int   $popup_timer     The timer of the current popup based on if it's a push banner.
	 * @param int   $trigger_delay   The delay for on load popups, or zero for other popups.
	 * @return string                The output buffer.
	 *
	 * @since 1.0.0
	 */
	private function frontend_js_close_snippet( $attr, $popup_id, $delete, $is_push_banner, $popup_timer, $trigger_delay ) {
		ob_start();
		// If this is a banner with push, Add the unset bezier curve after animating.
		if ( $is_push_banner ) :
			?>
			setTimeout( () => {
				blockScope.style.transition = 'max-height 0.5s cubic-bezier(0, 1, 0, 1)';
			}, <?php echo intval( $trigger_delay ) + 600; ?> );
		<?php endif; ?>
			const closePopup = ( event = null ) => {
				if ( event && blockScope !== event.target ) {
					return;
				}
				<?php
					// If this is a banner with push, render the required animation instead of opacity.
				if ( $is_push_banner ) :
					?>
					blockScope.style.maxHeight = '';
				<?php else : ?>
					blockScope.style.opacity = 0;
				<?php endif; ?>
				setTimeout( () => {
					<?php
						// If this is a banner with push, remove the unset bezier curve.
					if ( $is_push_banner ) :
						?>
						blockScope.style.transition = '';
					<?php endif; ?>
					<?php if ( $delete ) : ?>
						if ( popupSesh[0] > 0 ) {
							popupSesh[0] -= 1;
							localStorage.setItem( 'spectraPopup<?php echo esc_attr( strval( $popup_id ) ); ?>', JSON.stringify( popupSesh ) );
						}
						blockScope.remove();
					<?php else : ?>
						blockScope.style.display = 'none';
						blockScope.classList.remove( 'spectra-popup--open' );
					<?php endif; ?>
					const allActivePopups = document.querySelectorAll( '.uagb-popup-builder.spectra-popup--open' );
					if ( 0 === allActivePopups.length ) {
						theBody.classList.remove( 'uagb-popup-builder__body--overflow-hidden' );
					}
				}, <?php echo intval( $popup_timer ); ?> );
			};

			<?php
			if ( ! empty( $attr['isDismissable'] ) ) :
				if ( ! empty( $attr['hasOverlay'] ) && ! empty( $attr['closeOverlayClick'] ) ) :
					?>
					blockScope.addEventListener( 'click', ( event ) => closePopup( event ) );
					<?php
					endif;
				if ( ! empty( $attr['closeIcon'] ) ) :
					?>
					const closeButton = blockScope.querySelector( '.uagb-popup-builder__close' );
					closeButton.style.cursor = 'pointer';
					closeButton.addEventListener( 'click', () => closePopup() );
					<?php
					endif;
				if ( ! empty( $attr['closeEscapePress'] ) && ! empty( $attr['haltBackgroundInteraction'] ) && ! empty( $attr['variantType'] ) && 'popup' === $attr['variantType'] ) :
					?>
					document.addEventListener( 'keyup', ( event ) => {
						if ( 27 === event.keyCode && blockScope.classList.contains( 'spectra-popup--open' ) ) {
							return closePopup();
						}
					} );
					<?php
					endif;
				endif;
			?>

			const closingElements = blockScope.querySelectorAll( '.spectra-popup-close-<?php echo esc_attr( strval( $popup_id ) ); ?>' );
			for ( let i = 0; i < closingElements.length; i++ ) {
				closingElements[ i ].style.cursor = 'pointer';
				closingElements[ i ].addEventListener( 'click', () => closePopup() );
			}
		<?php
		$output = ob_get_clean();
		return is_string( $output ) ? $output : '';
	}

	/**
	 * Snippet of common scrollbar hide and push banner JS code on load.
	 *
	 * @param array $attr            The array of block attributes.
	 * @param bool  $is_push_banner  A boolean stating if this is a push banner or not.
	 * @since 1.0.1
	 * @return string                The output buffer.
	 */
	private function frontend_js_load_snippet( $attr, $is_push_banner ) {
		ob_start();
		// If this is a banner with push, render the max height instead of opacity on timeout.
		if ( $is_push_banner ) {
			?>
				blockScope.style.maxHeight = '100vh';
			<?php
		} else {
			// If this is a popup which prevent background interaction, hide the scrollbar.
			if ( 'popup' === $attr['variantType'] && $attr['haltBackgroundInteraction'] ) :
				?>
				theBody.classList.add( 'uagb-popup-builder__body--overflow-hidden' );
				blockScope.classList.add( 'spectra-popup--open' );
				<?php // Once this popup is active, create a focusable element to add focus onto the popup and then remove it. ?>
				blockScope.focus();
				const focusElement = document.createElement( 'button' );
				focusElement.style.position = 'absolute';
				focusElement.style.opacity = '0';
				const popupFocus = blockScope.insertBefore( focusElement, blockScope.firstChild );
				popupFocus.focus();
				popupFocus.remove();
			<?php endif; ?>
			blockScope.style.opacity = 1;
			<?php
		}//end if
		$output = ob_get_clean();

		return is_string( $output ) ? $output : '';
	}
}
