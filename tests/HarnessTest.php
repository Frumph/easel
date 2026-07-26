<?php

/**
 * Proves the harness itself works: that theme files can be loaded against the stubs, and
 * that the stubs the other tests depend on behave the way WordPress does.
 *
 * If this file fails, treat every other assertion in the suite as unreliable.
 */
class HarnessTest extends Easel_TestCase {

	public function testThemeFilesLoadAgainstStubs() {
		self::loadThemeFile( 'widgets/calendar.php' );
		self::loadThemeFile( 'functions/moods.php' );
		self::loadThemeFile( 'functions/page-options.php' );
		self::loadThemeFile( 'functions/copyright.php' );

		$this->assertTrue( function_exists( 'easel_get_calendar' ) );
		$this->assertTrue( class_exists( 'easel_calendar_widget' ) );
		$this->assertTrue( function_exists( 'easel_show_mood_in_post' ) );
		$this->assertTrue( function_exists( 'easel_handle_edit_post_mood_save' ) );
		$this->assertTrue( function_exists( 'easel_save_page_editor_options' ) );
		$this->assertTrue( function_exists( 'easel_copyright_text' ) );
	}

	/**
	 * The stubs answer with the checkout itself, so file_exists() and glob() inside the
	 * theme (moods, avatars) see the directories that actually ship.
	 */
	public function testThemeLocationStubsPointAtTheRepo() {
		$this->assertSame( EASEL_THEME_DIR, get_template_directory() );
		$this->assertSame( EASEL_THEME_DIR, get_stylesheet_directory() );
		$this->assertFileExists( get_template_directory() . '/style.css' );

		$this->assertSame( EASEL_TEST_THEME_URI, get_template_directory_uri() );
		$this->assertSame( EASEL_TEST_THEME_URI, get_stylesheet_directory_uri() );
	}

	/**
	 * esc_html()/esc_attr() must NOT re-encode existing entities; esc_textarea() must.
	 * Several tests distinguish correct from incorrect behaviour purely on this, so if the
	 * stubs get it wrong those tests silently stop meaning anything.
	 */
	public function testEscapingStubsMirrorWordPressDoubleEncodeSemantics() {
		$this->assertSame( '&lt;b&gt;', esc_html( '&lt;b&gt;' ), 'esc_html must not double-encode' );
		$this->assertSame( '&lt;b&gt;', esc_attr( '&lt;b&gt;' ), 'esc_attr must not double-encode' );
		$this->assertSame( '&amp;lt;b&amp;gt;', esc_textarea( '&lt;b&gt;' ), 'esc_textarea must double-encode' );

		$this->assertSame( '&lt;b&gt;', esc_html( '<b>' ) );
		$this->assertSame( '&quot;x&quot;', esc_attr( '"x"' ) );
	}

	/**
	 * The distinction a number of real findings turn on: esc_url() refuses a javascript:
	 * URL, esc_attr() is perfectly happy to hand one back. Escaping an href with esc_attr()
	 * therefore produces valid HTML and a working XSS, and only esc_url() closes it.
	 */
	public function testEscUrlRejectsJavascriptSchemeAndEscAttrDoesNot() {
		$this->assertSame( '', esc_url( 'javascript:alert(1)' ) );
		$this->assertSame( '', esc_url_raw( 'javascript:alert(1)' ) );
		$this->assertSame( 'https://example.test/x', esc_url_raw( 'https://example.test/x' ) );

		$this->assertSame( 'javascript:alert(1)', esc_attr( 'javascript:alert(1)' ) );
		$this->assertStringContainsString( 'javascript:', esc_attr( 'javascript:alert(1)' ) );
	}

	/**
	 * wp_filter_nohtml_kses() is the theme's only sanitiser on the options screen. It
	 * removes every tag and leaves quotes alone -- so its output is safe to print as text
	 * and still needs esc_attr() before it goes near an attribute.
	 */
	public function testNohtmlKsesStripsTagsButNotQuotes() {
		$this->assertSame( 'alert(1)', wp_filter_nohtml_kses( '<script>alert(1)</script>' ) );
		$this->assertSame( 'bold', wp_filter_nohtml_kses( '<b>bold</b>' ) );

		// Quotes survive: addslashes() on the way out is undone by wp_unslash(), the same
		// round trip a $_REQUEST value makes in WordPress.
		$this->assertSame( '\"x\"', wp_filter_nohtml_kses( '"x"' ) );
		$this->assertSame(
			'" onerror=alert(1)',
			wp_unslash( wp_filter_nohtml_kses( addslashes( '<img>" onerror=alert(1)' ) ) ),
			'a quote must survive sanitising, or attribute-injection tests would prove nothing'
		);
	}

	/**
	 * The CSRF seam: verification can be made to succeed, made to fail, and -- most
	 * importantly -- observed, so a test can tell "checked and passed" from "never checked".
	 */
	public function testNonceStubCanPassCanFailAndRecordsTheAttempt() {
		$this->assertFalse( wp_verify_nonce( 'forged', 'easel_post_options-7' ) );
		$this->assertCount( 1, Easel_Test_State::$nonce_checks );
		$this->assertFalse( Easel_Test_State::$nonce_checks[0]['valid'] );
		$this->assertSame( 'easel_post_options-7', Easel_Test_State::$nonce_checks[0]['action'] );

		$this->allowNonce( 'good', 'easel_post_options-7' );
		$this->assertSame( 1, wp_verify_nonce( 'good', 'easel_post_options-7' ) );
		$this->assertCount( 2, Easel_Test_State::$nonce_checks );
		$this->assertTrue( Easel_Test_State::$nonce_checks[1]['valid'] );

		// A nonce is scoped to its action, so the same string must not open another door.
		$this->assertFalse( wp_verify_nonce( 'good', 'some_other_action' ) );
	}

	public function testNonceFieldEmitsAVerifiableNonce() {
		$field = wp_nonce_field( 'update-options', '_wpnonce', true, false );
		$this->assertMatchesRegularExpression( '/name="_wpnonce" value="([^"]+)"/', $field );
		preg_match( '/value="([^"]+)"/', $field, $m );
		$this->assertSame( 1, wp_verify_nonce( $m[1], 'update-options' ) );
	}

	public function testCurrentUserCanDefaultsToFalse() {
		$this->assertFalse( current_user_can( 'edit_page' ) );
		$this->assertFalse( current_user_can( 'manage_options' ) );

		$this->grantCap( 'edit_page' );
		$this->assertTrue( current_user_can( 'edit_page' ) );
		$this->assertFalse( current_user_can( 'manage_options' ), 'granting one capability must not grant the rest' );
	}

	public function testFilterStubIsOverridable() {
		$this->assertSame( 'default', apply_filters( 'easel_copyright_text', 'default' ) );
		Easel_Test_State::$filters['easel_copyright_text'] = 'overridden';
		$this->assertSame( 'overridden', apply_filters( 'easel_copyright_text', 'default' ) );
	}

	public function testTranslationStubIsOverridable() {
		$this->assertSame( 'Original', __( 'Original', 'easel' ) );
		Easel_Test_State::$translations['Original'] = 'Origineel';
		$this->assertSame( 'Origineel', __( 'Original', 'easel' ) );
	}

	public function testThemeModsAndOptionsAreTestDriven() {
		$this->assertFalse( get_theme_mod( 'easel-customize-select-layout', false ) );
		set_theme_mod( 'easel-customize-select-layout', '2cl' );
		$this->assertSame( '2cl', get_theme_mod( 'easel-customize-select-layout', false ) );
		remove_theme_mod( 'easel-customize-select-layout' );
		$this->assertFalse( get_theme_mod( 'easel-customize-select-layout', false ) );

		update_option( 'easel-options', array( 'home_post_count' => 3 ) );
		delete_option( 'easel-options' );
		$this->assertSame( array( 'easel-options' ), Easel_Test_State::$deleted_options );
	}

	public function testPostMetaDeletionIsRecorded() {
		$this->setPostMeta( 7, 'disable-sidebars', '1' );
		$this->assertSame( '1', get_post_meta( 7, 'disable-sidebars', true ) );
		delete_post_meta( 7, 'disable-sidebars' );
		$this->assertSame( '', get_post_meta( 7, 'disable-sidebars', true ) );
		$this->assertSame( array( array( 7, 'disable-sidebars' ) ), Easel_Test_State::$deleted_meta );
	}

	/**
	 * $wp_locale is a global that widgets/calendar.php reaches for directly.
	 */
	public function testLocaleStubAnswersWhatTheCalendarAsksFor() {
		$locale = $GLOBALS['wp_locale'];
		$this->assertSame( 'January', $locale->get_month( '01' ) );
		$this->assertSame( 'Jan', $locale->get_month_abbrev( $locale->get_month( '01' ) ) );
		$this->assertSame( 'Monday', $locale->get_weekday( 1 ) );
		$this->assertSame( 'M', $locale->get_weekday_initial( 'Monday' ) );
		$this->assertSame( 'Mon', $locale->get_weekday_abbrev( 'Monday' ) );
	}

	public function testWpdbSpyRecordsPreparedSql() {
		$wpdb = $this->useWpdbSpy();
		$sql  = $wpdb->prepare( 'SELECT * FROM t WHERE id = %d AND name = %s', '7 OR 1=1', "o'brien" );
		$this->assertSame( "SELECT * FROM t WHERE id = 7 AND name = 'o\\'brien'", $sql );
		$this->assertTrue( $wpdb->sawSql( 'id = 7' ) );
	}

	/**
	 * widgets/calendar.php still calls $wpdb->escape(), removed from WordPress in 5.3. The
	 * spy answers so the widget can be exercised at all -- and answers with addslashes(),
	 * which is what it always was and why it never made anything safe.
	 */
	public function testWpdbSpyStillAnswersDeprecatedEscape() {
		$wpdb = $this->useWpdbSpy();
		$this->assertSame( "o\\'brien", $wpdb->escape( "o'brien" ) );
	}

	/**
	 * The dispatcher in tests/bootstrap.php can hand easel_themeinfo() over to the theme's
	 * real implementation, extracted from functions.php without editing it.
	 *
	 * Note the seeded option. With 'easel-options' unset, the real easel_load_options()
	 * assigns into the `false` that get_option() returns, which PHP 8.1 deprecates -- and
	 * this suite runs with failOnDeprecation. That default-install path deserves a test
	 * that asserts the deprecation deliberately (#[IgnoreDeprecations]), not one that
	 * trips over it here.
	 */
	public function testRealThemeInfoCanBeLoadedFromFunctionsPhp() {
		update_option( 'easel-options', array( 'home_post_count' => '9' ) );
		self::loadRealThemeInfo();

		$this->assertSame( array( 'home_post_count' => '9' ), easel_load_options() );
		$this->assertSame( '9', easel_themeinfo( 'home_post_count' ) );
		// From $easel_addinfo inside the real easel_themeinfo(), not from the option.
		$this->assertSame( '4.4.0 Beta', easel_themeinfo( 'version' ) );
		// The real accessor forces a layout when the option does not carry one.
		$this->assertSame( '3c', easel_themeinfo( 'layout' ) );
		$this->assertFalse( easel_themeinfo( 'no_such_setting' ) );
	}

	/**
	 * And back again: without loadRealThemeInfo(), easel_themeinfo() is the cheap stub the
	 * rest of the suite drives. Runs after the test above, which proves the switch is per
	 * test rather than sticky for the process.
	 */
	public function testThemeInfoStubIsTheDefault() {
		$this->assertFalse( Easel_Test_State::$use_real_themeinfo );
		$this->assertFalse( easel_themeinfo( 'moods_directory' ) );
		$this->setThemeInfo( array( 'moods_directory' => 'kelly' ) );
		$this->assertSame( 'kelly', easel_themeinfo( 'moods_directory' ) );
		$this->assertFalse( easel_themeinfo( 'still_missing' ) );
	}

	/**
	 * Every test above dirtied something. None of it should be visible here.
	 */
	public function testStateResetsBetweenTests() {
		$this->assertSame( array(), Easel_Test_State::$nonce_checks );
		$this->assertSame( array(), Easel_Test_State::$theme_mods );
		$this->assertSame( array(), Easel_Test_State::$user_caps );
		$this->assertSame( array(), Easel_Test_State::$deleted_options );
		$this->assertSame( array(), Easel_Test_State::$deleted_meta );
		$this->assertSame( array(), Easel_Test_State::$themeinfo );
		$this->assertFalse( get_option( 'easel-options' ) );
		$this->assertSame( 'UTF-8', get_option( 'blog_charset' ) );
	}
}
