<?php
/**
 * Global helper tests.
 *
 * @package ArrayPress\AcceptLanguageUtils
 */

declare( strict_types=1 );

namespace ArrayPress\AcceptLanguageUtils\Tests;

use PHPUnit\Framework\TestCase;

/**
 * The four global functions are the surface most consuming code touches, and
 * they are declared in a file Composer autoloads rather than a class anything
 * imports. That has two consequences worth a test.
 *
 * They only exist when ABSPATH is defined, because the file returns early
 * otherwise. And a wrapper that forwards to the wrong method, or drops an
 * argument on the way, is invisible: the call still works and the answer is
 * merely wrong.
 */
final class FunctionsTest extends TestCase {

	/**
	 * The live header, put back after each test.
	 *
	 * @var string|null
	 */
	private $original;

	/**
	 * Remember the real header.
	 */
	protected function setUp(): void {
		$this->original = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;
	}

	/**
	 * Put it back.
	 */
	protected function tearDown(): void {
		if ( null === $this->original ) {
			unset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] );
		} else {
			$_SERVER['HTTP_ACCEPT_LANGUAGE'] = $this->original;
		}
	}

	/**
	 * The helpers are declared.
	 *
	 * If ABSPATH were missing, src/Functions.php would return before
	 * declaring any of them and every test below would fail on an undefined
	 * function -- which reads as the library being broken rather than the
	 * bootstrap being wrong.
	 */
	public function test_the_helpers_are_declared(): void {
		foreach (
			[
				'get_accept_language',
				'get_preferred_language',
				'accepts_language',
				'is_rtl_language',
			] as $function
		) {
			$this->assertTrue( function_exists( $function ), sprintf( '%s() was never declared.', $function ) );
		}
	}

	/**
	 * Each helper forwards to the method it claims to.
	 */
	public function test_each_helper_forwards_correctly(): void {
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ar-EG,en;q=0.4';

		$this->assertSame( 'ar-EG', get_accept_language() );
		$this->assertTrue( is_rtl_language() );
		$this->assertTrue( accepts_language( 'en' ) );
		$this->assertSame( 'en-GB', get_preferred_language( [ 'en-GB', 'de' ] ) );
	}

	/**
	 * The arguments survive the forwarding.
	 *
	 * Both of these take a second argument that changes the answer, and a
	 * wrapper that forgets to pass it on still returns something plausible.
	 */
	public function test_the_second_argument_is_passed_on(): void {
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-GB';

		$this->assertTrue( accepts_language( 'en-US' ) );
		$this->assertFalse( accepts_language( 'en-US', true ) );

		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ja';

		$this->assertNull( get_preferred_language( [ 'en' ] ) );
		$this->assertSame( 'en', get_preferred_language( [ 'en' ], 'en' ) );
	}
}
