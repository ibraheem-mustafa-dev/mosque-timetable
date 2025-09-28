<?php
/**
 * Spectra Pro Helper.
 *
 * @package spectra-pro
 * @since 1.0.1
 */
namespace SpectraPro\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Helper' ) ) {

	/**
	 * Class Helper.
	 *
	 * @since 1.0.1
	 */
	final class Helper {

		/**
		 * Function to check if a given string is a single word.
		 *
		 * @param string $str The string to check if it's singular word.
		 * @since 1.0.1
		 * @return boolean
		 */
		public static function is_single_word( $str ) {
			return strpos( $str, ' ' ) === false;
		}

		/**
		 * Function to check if the given string is the first word in the original string.
		 *
		 * @param string $str         The word.
		 * @param string $originalStr The original string.
		 * @since 1.0.1
		 * @return boolean
		 */
		public static function is_first_word( $str, $originalStr ) {
			return html_entity_decode( $str ) === explode( ' ', $originalStr )[0];
		}

		/**
		 * Trim text but show only fully visible words.
		 *
		 * @param string  $txt The text to be trimmed.
		 * @param integer $len Max trim length.
		 * @param bool    $isDC conditional variable for logic.
		 * @since 1.0.1
		 * @return string
		 */
		public static function trim_text_to_fully_visible_word( $txt, $len, $isDC = false ) {

			// Necessary for frontend trimming calculations.
			if ( ! $isDC ) {
				$len++;
			}

			$disallowed_last_characters = array( ',', '.', ' ', "'" );
			$txt                        = html_entity_decode( $txt ); // Decode HTML entities in text, if any.

			// If the input text is already within the maxLength, return the original text.
			if ( strlen( $txt ) <= $len ) {
				return htmlentities( $txt );
			}

			$limited_caption = htmlentities( mb_substr( $txt, 0, $len ) );

			// Check if the limited caption is a single word.
			if ( self::is_single_word( $limited_caption ) && ! $isDC ) {
				// If the original text is a single word and the limited caption is the first word, return the original text.
				if ( self::is_single_word( $txt ) && self::is_first_word( $limited_caption, $txt ) ) {
					return htmlentities( $txt );
				}
				$limited_caption = '';
			} elseif ( strlen( html_entity_decode( $limited_caption ) ) !== strlen( $txt ) && substr( $limited_caption, -1 ) !== ' ' && ! $isDC ) {
				// If the limited caption is not the same as the original text and the end of the limited text is not a word,
				// trim the limited caption to the last complete word.
				$last_space_pos  = strrpos( $limited_caption, ' ' );
				$limited_caption = substr( $limited_caption, 0, false === $last_space_pos ? strlen( $limited_caption ) : min( strlen( $limited_caption ), $last_space_pos ) );
			}

			// Remove any disallowed characters from the end of the limited caption.
			while ( in_array( substr( $limited_caption, -1 ), $disallowed_last_characters ) ) {
				$limited_caption = substr( $limited_caption, 0, -1 );
			}

			// Determine if an ellipsis is needed based on the length of the input text.
			$needs_ellipsis = $len < strlen( $txt );
			return $limited_caption . ( $needs_ellipsis ? '&#8230;' : '' );
		}

	}

}//end if
