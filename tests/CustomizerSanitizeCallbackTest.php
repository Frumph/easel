<?php

/**
 * Pins the sanitize_callback names that functions/customize.php registers.
 *
 * The checkbox sanitiser is defined as eaeel_sanitize_checkbox() and referenced as
 * easel_sanitize_checkbox(). The two names differ by a transposition, so the callback the
 * settings point at does not exist and WordPress falls back to storing the submitted value
 * for those settings.
 *
 * easel_Customize::register() takes $wp_customize as an argument, so the whole registration
 * can be replayed against Easel_Customize_Recorder (tests/stubs.php), which writes the
 * arguments down instead of building a Customizer. That makes the questions worth asking
 * about this file -- does every setting declare a sanitiser, and does the named sanitiser
 * exist -- answerable as plain array assertions.
 */
class CustomizerSanitizeCallbackTest extends Easel_TestCase {

	/**
	 * The setting ids whose sanitize_callback names a function that is not defined.
	 *
	 * There is a fourth checkbox setting, 'easel-customize-comic-in-column', that shares the
	 * same callback name but is only registered when a companion plugin is active, so it
	 * does not appear here.
	 */
	const SETTINGS_WITH_A_MISSING_SANITIZER = array(
		'easel-customize-detach-footer',
		'easel-customize-checkbox-rounded',
		'easel-customize-checkbox-header-hotspot',
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
	 * Proof that register() actually ran, so the assertions below are about the theme's
	 * registrations rather than about an empty recorder.
	 */
	public function testRecorderCapturedTheRegistration() {
		$this->assertContains( 'colors', $this->customize->removed_sections );
		$this->assertArrayHasKey( 'easel-scheme-options', $this->customize->sections );
		$this->assertArrayHasKey( 'easel-customize-select-layout', $this->customize->settings );
		$this->assertNotEmpty( $this->customize->controls );
	}

	/**
	 * The function exists under the transposed name and not under the name the settings use.
	 *
	 * After the fix both assertions swap: eaeel_sanitize_checkbox() is gone and
	 * easel_sanitize_checkbox() is defined.
	 */
	public function testCheckboxSanitizerIsDefinedUnderADifferentNameThanItIsReferencedBy() {
		$this->assertTrue(
			function_exists( 'eaeel_sanitize_checkbox' ),
			'the definition currently carries the transposed name'
		);
		$this->assertFalse(
			function_exists( 'easel_sanitize_checkbox' ),
			'the name the settings reference is currently undefined'
		);
	}

	/**
	 * Every setting does declare a sanitize_callback, which is what the theme directory
	 * review asks for. Recorded so that a setting added later without one shows up here.
	 */
	public function testEverySettingDeclaresASanitizeCallback() {
		$without = array_keys(
			array_filter(
				$this->customize->sanitizeCallbacks(),
				function ( $callback ) {
					return null === $callback || '' === $callback;
				}
			)
		);

		$this->assertSame( array(), $without );
	}

	/**
	 * Current behaviour: three settings name a callback that cannot be called, and every
	 * other setting names one that can.
	 *
	 * After the fix this becomes assertSame( array(), $missing ) -- the rename makes the
	 * whole list callable.
	 */
	public function testOnlyTheCheckboxSettingsNameASanitizerThatDoesNotExist() {
		$missing = array();
		foreach ( $this->customize->sanitizeCallbacks() as $id => $callback ) {
			if ( ! is_callable( $callback ) ) {
				$missing[] = $id;
			}
		}

		$this->assertSame( self::SETTINGS_WITH_A_MISSING_SANITIZER, $missing );

		foreach ( $missing as $id ) {
			$this->assertSame(
				'easel_sanitize_checkbox',
				$this->customize->settings[ $id ]['sanitize_callback'],
				"$id names the sanitiser that is not defined"
			);
		}
	}
}
