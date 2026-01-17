<?php
/**
 * Global Accept-Language Helper Functions
 *
 * Provides convenient global functions for common Accept-Language operations.
 * These functions are wrappers around the ArrayPress\AcceptLanguage\AcceptLanguage class.
 *
 * @package ArrayPress\AcceptLanguageUtils
 * @since   1.0.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

use ArrayPress\AcceptLanguageUtils\AcceptLanguage;

if ( ! function_exists( 'get_accept_language' ) ) {
	/**
	 * Get the primary/preferred language code from Accept-Language header.
	 *
	 * @since 1.0.0
	 * @return string|null The primary language code (e.g., 'en-US', 'de') or null.
	 */
	function get_accept_language(): ?string {
		return AcceptLanguage::get_primary();
	}
}

if ( ! function_exists( 'get_preferred_language' ) ) {
	/**
	 * Find the best match from available languages.
	 *
	 * @since 1.0.0
	 *
	 * @param array       $available Array of available language codes.
	 * @param string|null $default   Default language if no match found.
	 *
	 * @return string|null The best matching language or default.
	 */
	function get_preferred_language( array $available, ?string $default = null ): ?string {
		return AcceptLanguage::get_best_match( $available, $default );
	}
}

if ( ! function_exists( 'accepts_language' ) ) {
	/**
	 * Check if a specific language is accepted.
	 *
	 * @since 1.0.0
	 *
	 * @param string $language The language code to check (e.g., 'en', 'en-US').
	 * @param bool   $exact    If true, requires exact match.
	 *
	 * @return bool True if the language is accepted.
	 */
	function accepts_language( string $language, bool $exact = false ): bool {
		return AcceptLanguage::accepts( $language, $exact );
	}
}

if ( ! function_exists( 'is_rtl_language' ) ) {
	/**
	 * Check if the user prefers a right-to-left language.
	 *
	 * @since 1.0.0
	 * @return bool True if the primary language is RTL.
	 */
	function is_rtl_language(): bool {
		return AcceptLanguage::is_rtl();
	}
}