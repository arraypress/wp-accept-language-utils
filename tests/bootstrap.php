<?php
/**
 * PHPUnit bootstrap.
 *
 * @package ArrayPress\AcceptLanguageUtils
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Translation stub.
	 *
	 * @param string $text   Text.
	 * @param string $domain Text domain.
	 *
	 * @return string
	 */
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	/**
	 * Core strips the slashes it added to the superglobals on load.
	 *
	 * @param mixed $value Value.
	 *
	 * @return mixed
	 */
	function wp_unslash( $value ) {
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Core's single-line text sanitizer, near enough for a header.
	 *
	 * @param string $value Value.
	 *
	 * @return string
	 */
	function sanitize_text_field( string $value ): string {
		$value = wp_strip_all_tags( $value );
		$value = preg_replace( '/[\r\n\t ]+/', ' ', $value );

		return trim( (string) $value );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	/**
	 * Core's tag stripper.
	 *
	 * @param string $value Value.
	 *
	 * @return string
	 */
	function wp_strip_all_tags( string $value ): string {
		return (string) preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', strip_tags( $value ) );
	}
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

/*
 * And now src/Functions.php a second time, deliberately.
 *
 * It is a Composer `files` entry, so it has already run once -- but it ran
 * when PHPUnit loaded the autoloader, which is *before* this bootstrap, so
 * ABSPATH was not defined yet and the file returned without declaring
 * anything. Defining ABSPATH above cannot fix that; nothing a bootstrap does
 * happens early enough.
 *
 * Running it again with ABSPATH set declares the helpers. Everything in there
 * is guarded by function_exists(), so the second pass is safe.
 *
 * `require`, not `require_once`: Composer already included this path, so the
 * _once form matches it and does nothing at all.
 */
require dirname( __DIR__ ) . '/src/Functions.php';
