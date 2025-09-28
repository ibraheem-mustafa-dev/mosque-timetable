<?php
/**
 * Countdown Pro Block config file.
 */
namespace SpectraPro\BlocksConfig\FreemiumBlocks\Countdown;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Countdown.
 *
 * @since 1.0.0
 */
class Block {

	/**
	 * Static variable to keep track of processed blocks (as the function runs twice).
	 *
	 * @var array
	 * @since 1.0.0
	 */
	public static $processed_blocks = array();

	/**
	 * Micro Constructor.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function init() {
		$self = new self();
		// Priority less than 11 gives conflicts with Woocommerce Blocks' render_block filter, leading to fatal error.
		if ( ! is_admin() ) {
			add_filter( 'render_block', array( $self, 'countdown_render_block' ), 11, 2 );
		}
		add_filter( 'uagb_countdown_options', [ $self, 'add_countdown_options' ], 10, 3 );
		add_filter( 'spectra_countdown_attributes', [ $self, 'add_attributes_defaults' ] );
		add_filter( 'spectra_countdown_frontend_dynamic_js', [ $self, 'override_dynamic_js' ], 10, 3 );
		add_filter( 'spectra_uagb/countdown_blockdata', [ $self, 'filter_countdown_block_data' ] );
	}

	/**
	 * Filters the Modal block data.
	 *
	 * @param array $block_data The block data to filter.
	 * @return array The filtered block data.
	 */
	public static function filter_countdown_block_data( $block_data ) {
		if ( isset( $block_data['static_dependencies']['uagb-countdown-js'] ) && ! empty( $block_data['static_dependencies']['uagb-countdown-js'] ) ) {
			$block_data['static_dependencies']['uagb-countdown-js']['src'] = \SpectraPro\Core\Utils::get_js_url( 'uagb-countdown' );
		}
		return $block_data;
	}

	/**
	 * Add additional countdown options to pass in JS.
	 *
	 * @param array  $data data to filter.
	 * @param string $id Block ID.
	 * @param array  $atts Block Attributes.
	 * @return array $args JS arguments.
	 * @since 1.0.0
	 */
	public function add_countdown_options( $data, $id, $atts ) {
		$data['timerType']        = $atts['timerType'];
		$data['evergreenDays']    = $atts['evergreenDays'];
		$data['evergreenHrs']     = $atts['evergreenHrs'];
		$data['evergreenMinutes'] = $atts['evergreenMinutes'];
		$data['campaignID']       = $atts['campaignID'];
		$data['resetDays']        = $atts['resetDays'];
		$data['reloadOnExpire']   = $atts['reloadOnExpire'];
		$data['autoReload']       = $atts['autoReload'];
		$data['redirectURL']      = $atts['redirectURL'];
		return $data;
	}

		/**
		 * Override Block dynamic JS.
		 *
		 * @param string $output JS output.
		 * @param string $selector Selector.
		 * @param array  $args Attributes.
		 * @return string Modified Output.
		 */
	public function override_dynamic_js( $output, $selector, $args ) {
		
		\ob_start();
		?>
			window.addEventListener( 'load', function() {
				UAGBCountdown.init( '<?php echo esc_attr( $selector ); ?>', <?php echo wp_json_encode( $args ); ?> );
			});
		<?php

		$output = \ob_get_clean();

		return false !== $output ? $output : '';
	}

	/**
	 * Add attributes to the countdown block.
	 *
	 * @param array $attributes The block attributes.
	 * @return array The block attributes.
	 * @since 1.0.0
	 */
	public function add_attributes_defaults( $attributes ) {
		return array_merge(
			$attributes,
			array(
				'evergreenDays'    => 0,
				'evergreenHrs'     => 0,
				'evergreenMinutes' => 0,
				'campaignID'       => '',
				'resetDays'        => 30,
				'reloadOnExpire'   => true,
				'autoReload'       => false,
				'redirectURL'      => '',
			)
		);
	}


	/**
	 * Render block function for Countdown.
	 *
	 * @param string $block_content The block content.
	 * @param array  $block The block data.
	 * @since 1.0.0
	 * @return string|null|boolean Returns the new block content.
	 */
	public function countdown_render_block( $block_content, $block ) {

		// Check if it's NOT the countdown block and ensure the current page is the Gutenberg editor.
		// The second check ensures that HTTP redirects do not occur in the editor, else the user may never be able to edit the post once the countdown expires.
		// Third check: If the block attributes are empty, return the block content.
		if ( empty( $block['attrs'] ) || ( 'uagb/countdown' !== $block['blockName'] ) ) {
			return $block_content;
		}

		$block_attributes = $block['attrs'];

		// If the block has already been processed, return the block content.
		if ( in_array( $block['attrs']['block_id'], self::$processed_blocks, true ) ) {
			return $block_content;
		}

		// Add the current block to the list of processed blocks.
		array_push( self::$processed_blocks, $block['attrs']['block_id'] );

		$js_time      = strtotime( $block_attributes['endDateTime'] );
		$current_time = time();
		$timer_type   = isset( $block_attributes['timerType'] ) ? $block_attributes['timerType'] : 'date';

		if ( 'evergreen' === $timer_type ) {
			$campaign_id = ! empty( $block['attrs']['campaignID'] ) ? $block['attrs']['campaignID'] : $block['attrs']['block_id'];
			$site_slug   = sanitize_title( get_bloginfo( 'name' ) );
			$cookie_name = $site_slug . '-' . $campaign_id;

			if ( isset( $_COOKIE[ $cookie_name ] ) ) {
				// Converting PHP timestamp to JS timestamp.
				$current_time = $current_time * 1000;
				$diff         = absint( $_COOKIE[ $cookie_name ] ) - $current_time;
				$js_time      = $current_time + $diff;
			} else {
				$evergreen_days = isset( $block['attrs']['evergreenDays'] ) ? $block['attrs']['evergreenDays'] : 0;
				$evergreen_hrs  = isset( $block['attrs']['evergreenHrs'] ) ? $block['attrs']['evergreenHrs'] : 0;
				$evergreen_mins = isset( $block['attrs']['evergreenMinutes'] ) ? $block['attrs']['evergreenMinutes'] : 0;
				$js_time        = $current_time + ( ( $evergreen_days * 24 * 60 ) + ( $evergreen_hrs * 60 ) + $evergreen_mins ) * 60;
				$current_time   = $current_time + ( $evergreen_days * 24 * 60 * 60 ) + ( $evergreen_hrs * 60 * 60 ) + ( $evergreen_mins * 60 );
			}
		}

		$is_overtime = $current_time > $js_time;

		$timer_end_action = isset( $block_attributes['timerEndAction'] ) ? $block_attributes['timerEndAction'] : 'zero';

		// If countdown isn't overtime or the end action is set to stay at zero, show it.
		// Also in case it's set to replace with content, we need to process it further to remove the innerblocks in case it's not overtime.
		// Moreover, for 'redirect' case, we need further JS.
		if ( 'zero' === $timer_end_action ) {
			return $block_content;
		}

		// If the timer is overtime AND end action is not 'keep the timer at zero'.
		if ( ( $is_overtime ) && ( 'zero' !== $timer_end_action ) ) {

			if ( 'hide' === $timer_end_action ) {
				return null;
			}       
		}//end if
		
		$reloadOnExpireValue = isset( $block_attributes['reloadOnExpire'] ) ? $block_attributes['reloadOnExpire'] : true;
		// If 'Replace with Content' is enabled.
		if ( ( 'content' === $timer_end_action ) && $reloadOnExpireValue ) {

			// If countdown is overtime.
			if ( $is_overtime ) {
				return $block_content;
			}//end if

			// If countdown is NOT overtime, then remove the innerblocks.
			// Go through each innerblock, save it's html structure (in string format) and remove the same from block content.
			// Dynamic blocks aren't removed via this so we also use JS later.
			// But removing most info on server side minimizes the chance 'surprise data' being revealed before the countdown ends.
			$block_content = $this->blocks_remover( $block['innerBlocks'], $block_content );

		}//end if

		return $block_content;
	}

	/**
	 * Recursively removes innerblocks.
	 * Limitations: Cannot remove dynamic blocks content.
	 *
	 * @param array  $blocks The innerblock content.
	 * @param string $block_content The block content.
	 * @since 1.0.0
	 * @return string Returns the new block content with the innerblocks removed.
	 */
	public function blocks_remover( $blocks, $block_content ) {

		if ( empty( $blocks ) || ! is_array( $blocks ) ) {
			return $block_content;
		}

		foreach ( $blocks as $inner_block ) {
			// Remove the current instance of innerblock from block content.
			$block_content = str_replace( $inner_block['innerHTML'], '', $block_content );
	
			// Check if the inner block has inner blocks.
			if ( isset( $inner_block['innerBlocks'] ) && count( $inner_block['innerBlocks'] ) > 0 ) {
				// Recursively remove inner blocks within inner blocks.
				$block_content = $this->blocks_remover( $inner_block['innerBlocks'], $block_content );
			}
		}
	
		return $block_content;
	}

}
