<?php

/**
 * Pins the order in which easel_admin_options() reads the request.
 *
 * The options screen handles two reset actions at the top of the function and the six save
 * actions further down. The saves sit inside an `isset($_POST['_wpnonce']) &&
 * wp_verify_nonce(...)` block; the two resets do not -- they run before the function gets
 * as far as looking at the nonce. So today a request that names a reset action takes effect
 * whether or not the form token that the screen's own reset buttons submit is present.
 *
 * The tests below record that: the option is gone, the theme mods are gone, and
 * Easel_Test_State::$nonce_checks -- which every call to wp_verify_nonce() appends to -- is
 * still empty when the call returns. After the fix the same three assertions become
 * "option survives", "theme mods survive", and "the nonce was checked".
 *
 * The last test covers a smaller thing in the same function: inside the nonce block the
 * action is read as $_REQUEST['action'] with no isset(), so a request that carries a valid
 * token and no action raises a warning per comparison.
 *
 * easel_admin_options() prints the whole screen, including the partials under options/, so
 * every call goes through runCapturing(): the markup is buffered and the diagnostics the
 * partials raise are collected rather than escaping into the test run.
 */
class OptionsRequestHandlingTest extends Easel_TestCase {

	protected function setUp(): void {
		parent::setUp();
		self::loadThemeFile( 'options.php' );
		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
	}

	protected function tearDown(): void {
		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
		unset( $GLOBALS['easel_themeinfo'] );
		parent::tearDown();
	}

	/**
	 * Current behaviour: 'easel_reset' deletes the settings option, and no nonce is ever
	 * verified during the call.
	 *
	 * After the fix: 'easel-options' is NOT in $deleted_options, get_option() still returns
	 * the seeded array, and $nonce_checks holds one entry for 'update-options'.
	 */
	public function testResetActionDeletesTheOptionAndChecksNoNonce() {
		update_option( 'easel-options', array( 'home_post_count' => '5' ) );
		$_REQUEST['action'] = 'easel_reset';

		$run = $this->runCapturing( 'easel_admin_options' );

		$this->assertContains( 'easel-options', Easel_Test_State::$deleted_options );
		$this->assertFalse( get_option( 'easel-options' ) );
		$this->assertStringContainsString( 'Easel Settings RESET!', $run['output'] );
		$this->assertSame(
			array(),
			Easel_Test_State::$nonce_checks,
			'the reset ran without wp_verify_nonce() being called at any point'
		);
	}

	/**
	 * Same shape for the customizer reset, which drops the theme mod and the theme_mods
	 * option.
	 *
	 * After the fix: 'easel-customize' is still in $theme_mods, 'theme_mods_easel' is not in
	 * $deleted_options, and $nonce_checks is not empty.
	 */
	public function testResetCustomizeActionClearsThemeModsAndChecksNoNonce() {
		set_theme_mod( 'easel-customize', array( 'page_background' => '#ffffff' ) );
		update_option( 'theme_mods_easel', array( 'easel-customize' => array() ) );
		$_REQUEST['action'] = 'easel_reset_customize';

		$run = $this->runCapturing( 'easel_admin_options' );

		$this->assertArrayNotHasKey( 'easel-customize', Easel_Test_State::$theme_mods );
		$this->assertContains( 'theme_mods_easel', Easel_Test_State::$deleted_options );
		$this->assertStringContainsString( 'Easel Customizer Colors RESET!', $run['output'] );
		$this->assertSame(
			array(),
			Easel_Test_State::$nonce_checks,
			'the customizer reset ran without wp_verify_nonce() being called at any point'
		);
	}

	/**
	 * The control. Without an action nothing is deleted, which is what makes the two tests
	 * above about the action rather than about merely rendering the screen.
	 */
	public function testRenderingTheScreenWithNoActionDeletesNothing() {
		update_option( 'easel-options', array( 'home_post_count' => '5' ) );

		$this->runCapturing( 'easel_admin_options' );

		$this->assertSame( array(), Easel_Test_State::$deleted_options );
		$this->assertSame( array( 'home_post_count' => '5' ), get_option( 'easel-options' ) );
	}

	/**
	 * Inside the nonce block the action is read as $_REQUEST['action'] rather than
	 * isset($_REQUEST['action']), once per save handler.
	 *
	 * After the fix the same call raises no 'action' warnings at all, so this becomes
	 * assertCount( 0, ... ) -- or assertSame( array(), ... ).
	 */
	public function testActionIsReadWithoutIssetOnceTheNonceIsAccepted() {
		$_POST['_wpnonce'] = wp_create_nonce( 'update-options' );

		$run = $this->runCapturing( 'easel_admin_options' );

		$this->assertNotEmpty(
			Easel_Test_State::$nonce_checks,
			'this test only means something if the nonce block was actually entered'
		);

		$warnings = self::diagnosticsMatching( $run['diagnostics'], 'Undefined array key "action"' );
		$this->assertCount( 6, $warnings, 'one warning per save handler that compares the action' );
		$this->assertSame( E_WARNING, $warnings[0]['errno'] );
	}
}
