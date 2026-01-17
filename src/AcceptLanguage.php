<?php
/**
 * AcceptLanguage Utility Class
 *
 * Provides utility functions for parsing and working with
 * the HTTP Accept-Language header.
 *
 * @package ArrayPress\AcceptLanguageUtils
 * @since   1.0.0
 * @author  ArrayPress
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace ArrayPress\AcceptLanguageUtils;

/**
 * AcceptLanguage Class
 *
 * Core operations for working with Accept-Language headers.
 */
class AcceptLanguage {

	/**
	 * Get the raw Accept-Language header value.
	 *
	 * @return string|null The header value or null if not set.
	 */
	public static function get_header(): ?string {
		if ( empty( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
			return null;
		}

		return $_SERVER['HTTP_ACCEPT_LANGUAGE'];
	}

	/**
	 * Get the primary/preferred language code.
	 *
	 * @return string|null The primary language code (e.g., 'en-US', 'de') or null.
	 */
	public static function get_primary(): ?string {
		$languages = self::parse();

		if ( empty( $languages ) ) {
			return null;
		}

		// Return the first (highest priority) language
		return array_key_first( $languages );
	}

	/**
	 * Get the primary language code without region.
	 *
	 * @return string|null The language code (e.g., 'en', 'de') or null.
	 */
	public static function get_primary_language(): ?string {
		$primary = self::get_primary();

		if ( ! $primary ) {
			return null;
		}

		return self::extract_language( $primary );
	}

	/**
	 * Get the primary region/country code.
	 *
	 * @return string|null The region code (e.g., 'US', 'GB') or null.
	 */
	public static function get_primary_region(): ?string {
		$primary = self::get_primary();

		if ( ! $primary ) {
			return null;
		}

		return self::extract_region( $primary );
	}

	/**
	 * Parse the Accept-Language header into an array of languages with quality values.
	 *
	 * @param string|null $header Optional header string to parse. Uses $_SERVER if null.
	 *
	 * @return array<string, float> Associative array of language => quality, sorted by quality descending.
	 */
	public static function parse( ?string $header = null ): array {
		$header = $header ?? self::get_header();

		if ( empty( $header ) ) {
			return [];
		}

		$languages = [];
		$parts     = explode( ',', $header );

		foreach ( $parts as $part ) {
			$part = trim( $part );

			if ( empty( $part ) ) {
				continue;
			}

			// Check for quality value (e.g., "en-US;q=0.8")
			if ( str_contains( $part, ';' ) ) {
				[ $lang, $quality ] = explode( ';', $part, 2 );
				$lang = trim( $lang );

				// Parse quality value
				if ( preg_match( '/q=([0-9.]+)/', $quality, $matches ) ) {
					$q = (float) $matches[1];
				} else {
					$q = 1.0;
				}
			} else {
				$lang = $part;
				$q    = 1.0;
			}

			// Normalize the language code
			$lang = self::normalize( $lang );

			if ( ! empty( $lang ) ) {
				$languages[ $lang ] = $q;
			}
		}

		// Sort by quality descending
		arsort( $languages );

		return $languages;
	}

	/**
	 * Get all accepted languages as a simple array (without quality values).
	 *
	 * @return array<string> Array of language codes sorted by preference.
	 */
	public static function get_all(): array {
		return array_keys( self::parse() );
	}

	/**
	 * Get all unique language codes (without regions).
	 *
	 * @return array<string> Array of language codes (e.g., ['en', 'de', 'fr']).
	 */
	public static function get_languages(): array {
		$all       = self::get_all();
		$languages = [];

		foreach ( $all as $locale ) {
			$lang = self::extract_language( $locale );
			if ( $lang && ! in_array( $lang, $languages, true ) ) {
				$languages[] = $lang;
			}
		}

		return $languages;
	}

	/**
	 * Check if a specific language is accepted.
	 *
	 * @param string $language The language code to check (e.g., 'en', 'en-US').
	 * @param bool   $exact    If true, requires exact match. If false, matches base language.
	 *
	 * @return bool True if the language is accepted.
	 */
	public static function accepts( string $language, bool $exact = false ): bool {
		$languages = self::parse();
		$language  = self::normalize( $language );

		if ( empty( $language ) ) {
			return false;
		}

		// Exact match
		if ( isset( $languages[ $language ] ) ) {
			return true;
		}

		if ( $exact ) {
			return false;
		}

		// Check base language match
		$base_language = self::extract_language( $language );

		foreach ( array_keys( $languages ) as $accepted ) {
			if ( self::extract_language( $accepted ) === $base_language ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the quality value for a specific language.
	 *
	 * @param string $language The language code to check.
	 *
	 * @return float|null The quality value (0.0-1.0) or null if not accepted.
	 */
	public static function get_quality( string $language ): ?float {
		$languages = self::parse();
		$language  = self::normalize( $language );

		return $languages[ $language ] ?? null;
	}

	/**
	 * Find the best match from a list of available languages.
	 *
	 * @param array       $available Array of available language codes.
	 * @param string|null $default   Default language if no match found.
	 *
	 * @return string|null The best matching language or default.
	 */
	public static function get_best_match( array $available, ?string $default = null ): ?string {
		$accepted = self::parse();

		if ( empty( $accepted ) || empty( $available ) ) {
			return $default;
		}

		// Normalize available languages
		$available_normalized = [];
		foreach ( $available as $lang ) {
			$normalized                          = self::normalize( $lang );
			$available_normalized[ $normalized ] = $lang;
		}

		// First pass: exact matches
		foreach ( array_keys( $accepted ) as $lang ) {
			if ( isset( $available_normalized[ $lang ] ) ) {
				return $available_normalized[ $lang ];
			}
		}

		// Second pass: base language matches
		foreach ( array_keys( $accepted ) as $lang ) {
			$base = self::extract_language( $lang );

			foreach ( $available_normalized as $normalized => $original ) {
				if ( self::extract_language( $normalized ) === $base ) {
					return $original;
				}
			}
		}

		return $default;
	}

	/**
	 * Check if the user prefers a right-to-left language.
	 *
	 * @return bool True if the primary language is RTL.
	 */
	public static function is_rtl(): bool {
		$primary = self::get_primary_language();

		if ( ! $primary ) {
			return false;
		}

		$rtl_languages = [
			'ar', // Arabic
			'he', // Hebrew
			'fa', // Persian/Farsi
			'ur', // Urdu
			'yi', // Yiddish
			'ps', // Pashto
			'sd', // Sindhi
			'ug', // Uyghur
			'ku', // Kurdish (Sorani)
			'dv', // Divehi
		];

		return in_array( $primary, $rtl_languages, true );
	}

	/**
	 * Normalize a language code.
	 *
	 * Converts to lowercase language with uppercase region (e.g., 'EN-us' => 'en-US').
	 *
	 * @param string $code The language code to normalize.
	 *
	 * @return string The normalized language code.
	 */
	public static function normalize( string $code ): string {
		$code = trim( $code );

		if ( empty( $code ) ) {
			return '';
		}

		// Handle both hyphen and underscore separators
		$code = str_replace( '_', '-', $code );

		if ( str_contains( $code, '-' ) ) {
			[ $lang, $region ] = explode( '-', $code, 2 );

			return strtolower( $lang ) . '-' . strtoupper( $region );
		}

		return strtolower( $code );
	}

	/**
	 * Extract the language code from a locale string.
	 *
	 * @param string $locale The locale string (e.g., 'en-US', 'de').
	 *
	 * @return string The language code (e.g., 'en', 'de').
	 */
	public static function extract_language( string $locale ): string {
		$locale = str_replace( '_', '-', $locale );

		if ( str_contains( $locale, '-' ) ) {
			return strtolower( explode( '-', $locale )[0] );
		}

		return strtolower( $locale );
	}

	/**
	 * Extract the region code from a locale string.
	 *
	 * @param string $locale The locale string (e.g., 'en-US', 'de-AT').
	 *
	 * @return string|null The region code (e.g., 'US', 'AT') or null if not present.
	 */
	public static function extract_region( string $locale ): ?string {
		$locale = str_replace( '_', '-', $locale );

		if ( str_contains( $locale, '-' ) ) {
			$parts = explode( '-', $locale );

			return isset( $parts[1] ) ? strtoupper( $parts[1] ) : null;
		}

		return null;
	}

	/**
	 * Get common language options for select dropdowns.
	 *
	 * @param bool $as_options If true, returns array of ['value' => '', 'label' => ''] format.
	 *
	 * @return array
	 */
	public static function get_common_languages( bool $as_options = false ): array {
		$languages = [
			'en'    => __( 'English', 'arraypress' ),
			'en-US' => __( 'English (US)', 'arraypress' ),
			'en-GB' => __( 'English (UK)', 'arraypress' ),
			'es'    => __( 'Spanish', 'arraypress' ),
			'es-ES' => __( 'Spanish (Spain)', 'arraypress' ),
			'es-MX' => __( 'Spanish (Mexico)', 'arraypress' ),
			'fr'    => __( 'French', 'arraypress' ),
			'fr-FR' => __( 'French (France)', 'arraypress' ),
			'fr-CA' => __( 'French (Canada)', 'arraypress' ),
			'de'    => __( 'German', 'arraypress' ),
			'de-DE' => __( 'German (Germany)', 'arraypress' ),
			'de-AT' => __( 'German (Austria)', 'arraypress' ),
			'it'    => __( 'Italian', 'arraypress' ),
			'pt'    => __( 'Portuguese', 'arraypress' ),
			'pt-BR' => __( 'Portuguese (Brazil)', 'arraypress' ),
			'pt-PT' => __( 'Portuguese (Portugal)', 'arraypress' ),
			'nl'    => __( 'Dutch', 'arraypress' ),
			'ru'    => __( 'Russian', 'arraypress' ),
			'ja'    => __( 'Japanese', 'arraypress' ),
			'zh'    => __( 'Chinese', 'arraypress' ),
			'zh-CN' => __( 'Chinese (Simplified)', 'arraypress' ),
			'zh-TW' => __( 'Chinese (Traditional)', 'arraypress' ),
			'ko'    => __( 'Korean', 'arraypress' ),
			'ar'    => __( 'Arabic', 'arraypress' ),
			'hi'    => __( 'Hindi', 'arraypress' ),
			'tr'    => __( 'Turkish', 'arraypress' ),
			'pl'    => __( 'Polish', 'arraypress' ),
			'sv'    => __( 'Swedish', 'arraypress' ),
			'da'    => __( 'Danish', 'arraypress' ),
			'no'    => __( 'Norwegian', 'arraypress' ),
			'fi'    => __( 'Finnish', 'arraypress' ),
			'el'    => __( 'Greek', 'arraypress' ),
			'he'    => __( 'Hebrew', 'arraypress' ),
			'th'    => __( 'Thai', 'arraypress' ),
			'vi'    => __( 'Vietnamese', 'arraypress' ),
			'id'    => __( 'Indonesian', 'arraypress' ),
			'ms'    => __( 'Malay', 'arraypress' ),
			'cs'    => __( 'Czech', 'arraypress' ),
			'hu'    => __( 'Hungarian', 'arraypress' ),
			'ro'    => __( 'Romanian', 'arraypress' ),
			'uk'    => __( 'Ukrainian', 'arraypress' ),
		];

		if ( ! $as_options ) {
			return $languages;
		}

		return self::to_options( $languages );
	}

	/**
	 * Convert key/value array to options format.
	 *
	 * @param array $items Key/value array.
	 *
	 * @return array<array{value: string, label: string}>
	 */
	protected static function to_options( array $items ): array {
		$options = [];

		foreach ( $items as $value => $label ) {
			$options[] = [
				'value' => $value,
				'label' => $label,
			];
		}

		return $options;
	}

}