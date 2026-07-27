<?php

use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Pins how easel_load_options() builds its defaults.
 *
 * The function reads get_option('easel-options'), which answers false until the options
 * screen has been saved at least once, and then assigns into that false one key at a time.
 * PHP treats writing an array key into false as an automatic conversion: deprecated since
 * 8.1 and an Error in 9. The update_option() that would have persisted the result is
 * commented out, so this happens on every request of a site that has never pressed Save
 * rather than only once.
 *
 * Two tests: one that the returned array is correct today -- because it is, and the fix
 * must not change it -- and one that pins the deprecation itself.
 *
 * After the fix the array is seeded before the loop, both tests keep the same expectations
 * for the returned values, and testBuildingDefaultsRaisesTheFalseToArrayDeprecation()
 * becomes assertSame( array(), $matches ). The #[IgnoreDeprecations] attribute below can
 * come off at the same time.
 */
class LoadOptionsDefaultsTest extends Easel_TestCase {

	protected function setUp(): void {
		parent::setUp();
		self::loadRealThemeInfo();
		// The fresh-install state: the option has never been written.
		$this->assertFalse( get_option( 'easel-options' ) );
	}

	/**
	 * The defaults come back correctly populated, which is the behaviour the fix has to
	 * preserve. The suite runs with failOnDeprecation, so the attribute is what lets this
	 * test exercise the real path rather than tiptoe around it.
	 */
	#[IgnoreDeprecations]
	public function testDefaultsAreReturnedForASiteThatHasNeverSaved() {
		$options = easel_load_options();

		$this->assertIsArray( $options );
		$this->assertSame( '5', $options['home_post_count'] );
		$this->assertSame( 500, $options['content_width'] );
		$this->assertSame( 700, $options['content_width_disabled_sidebars'] );
		$this->assertSame( 'none', $options['moods_directory'] );
		$this->assertSame( 'none', $options['avatar_directory'] );
		$this->assertSame( 'excerpt', $options['excerpt_or_content_in_archive'] );
		$this->assertSame( 'DESC', $options['archive_display_order'] );
		$this->assertTrue( $options['enable_avatar_trick'] );
		$this->assertFalse( $options['disable_post_titles'] );
		$this->assertArrayHasKey( 'enable_jetpack_infinite_scrolling', $options );
		$this->assertSame( '', $options['menubar_social_twitter'] );
	}

	/**
	 * And it is not written back, so the array is rebuilt on every call.
	 */
	#[IgnoreDeprecations]
	public function testDefaultsAreNotPersisted() {
		easel_load_options();

		$this->assertFalse( get_option( 'easel-options' ) );
		$this->assertArrayNotHasKey( 'easel-options', Easel_Test_State::$options );
	}

	/**
	 * The deprecation itself, pinned by message. One notice per call: the first assignment
	 * converts the false, and the remaining keys go into an array.
	 */
	public function testBuildingDefaultsRaisesTheFalseToArrayDeprecation() {
		$run = $this->runCapturing(
			function () {
				easel_load_options();
			}
		);

		$matches = self::diagnosticsMatching( $run['diagnostics'], 'Automatic conversion of false to array' );

		$this->assertCount( 1, $matches );
		$this->assertSame( E_DEPRECATED, $matches[0]['errno'] );
		$this->assertSame( '', $run['output'], 'the function must not print anything' );
	}

	/**
	 * Nothing happens when the option does exist, which is what the rest of the suite has
	 * been relying on by seeding it.
	 */
	public function testASavedOptionIsReturnedUntouched() {
		update_option( 'easel-options', array( 'home_post_count' => '9' ) );

		$run = $this->runCapturing(
			function () {
				$this->assertSame( array( 'home_post_count' => '9' ), easel_load_options() );
			}
		);

		$this->assertSame( array(), $run['diagnostics'] );
	}
}
