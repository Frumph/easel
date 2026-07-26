<?php

/**
 * Pins the type of the three width settings and what the header CSS does with them.
 *
 * functions/customize.php registers the site width and the two sidebar widths as ranges,
 * but sanitises them with wp_filter_nohtml_kses(), which is a text sanitiser: it strips
 * tags and returns a string, including an empty one. The header CSS then adds 4 to the two
 * sidebar values directly. On PHP 8 adding to a string that is not numeric is a TypeError
 * rather than a coercion, so a stored value that the sanitiser lets through stops the page.
 *
 * Two halves are recorded: which sanitiser the settings are registered with (via the
 * recorder in tests/stubs.php), and what the arithmetic does with the values that sanitiser
 * permits.
 *
 * After the fix the settings are registered with an integer sanitiser, so the stored value
 * is an int, the addition is int + int, and testAddingFourToANonNumericWidthIsATypeError()
 * becomes an assertion that the sum is 4 rather than that it throws.
 */
class CustomizerRangeValueTest extends Easel_TestCase {

	const RANGE_SETTINGS = array(
		'easel-customize-range-site-width',
		'easel-customize-range-left-sidebar-width',
		'easel-customize-range-right-sidebar-width',
	);

	/** @var Easel_Customize_Recorder */
	private $customize;

	protected function setUp(): void {
		parent::setUp();
		self::loadThemeFile( 'functions/customize.php' );
		$this->customize = new Easel_Customize_Recorder();
		easel_Customize::register( $this->customize );
	}

	/**
	 * After the fix the expected value here is the theme's integer sanitiser rather than
	 * 'wp_filter_nohtml_kses'.
	 */
	public function testWidthSettingsAreSanitizedAsTextRatherThanAsNumbers() {
		foreach ( self::RANGE_SETTINGS as $id ) {
			$this->assertArrayHasKey( $id, $this->customize->settings );
			$this->assertSame(
				'wp_filter_nohtml_kses',
				$this->customize->settings[ $id ]['sanitize_callback'],
				"$id is registered with a text sanitiser"
			);
			$this->assertSame(
				'theme_mod',
				$this->customize->settings[ $id ]['type'],
				"$id is stored as a theme mod, so get_theme_mod() is what reads it back"
			);
		}
	}

	/**
	 * The text sanitiser hands back both an empty string and a non-numeric one unchanged,
	 * so either can end up stored against a width setting.
	 */
	public function testTextSanitizerPassesThroughValuesThatAreNotNumbers() {
		$this->assertSame( '', wp_filter_nohtml_kses( '' ) );
		$this->assertSame( 'abc', wp_filter_nohtml_kses( 'abc' ) );
		$this->assertSame( '200', wp_filter_nohtml_kses( '200' ) );
	}

	/**
	 * The addition the header CSS performs on the two sidebar widths.
	 *
	 * After the fix the stored value is an integer and this test asserts a sum instead:
	 * an unset or empty setting falls back to the default and 200 + 4 is 204.
	 */
	public function testAddingFourToANonNumericWidthIsATypeError() {
		if ( PHP_VERSION_ID < 80000 ) {
			$this->markTestSkipped( 'PHP 7 coerces a non-numeric string instead of throwing' );
		}

		foreach ( array( '', 'abc' ) as $stored ) {
			set_theme_mod( 'easel-customize-range-left-sidebar-width', $stored );
			try {
				$width = get_theme_mod( 'easel-customize-range-left-sidebar-width', 200 ) + 4;
				$this->fail( 'expected a TypeError, got ' . var_export( $width, true ) );
			} catch ( TypeError $e ) {
				$this->assertStringContainsString( 'Unsupported operand types', $e->getMessage() );
			}
		}
	}

	/**
	 * And the happy path, which is what makes the test above about the stored value rather
	 * than about the arithmetic being wrong in general.
	 */
	public function testAddingFourToANumericWidthWorks() {
		set_theme_mod( 'easel-customize-range-left-sidebar-width', '200' );
		$this->assertSame( 204, get_theme_mod( 'easel-customize-range-left-sidebar-width', 200 ) + 4 );

		// Unset, the default answers and the addition is int + int.
		remove_theme_mod( 'easel-customize-range-left-sidebar-width' );
		$this->assertSame( 204, get_theme_mod( 'easel-customize-range-left-sidebar-width', 200 ) + 4 );
	}
}
