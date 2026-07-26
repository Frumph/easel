<?php

/**
 * Pins two places where the theme reads $_SERVER without checking the key is there.
 *
 * PHP only populates $_SERVER with the keys the environment actually supplied. SCRIPT_NAME
 * is missing under some SAPIs and HTTP_USER_AGENT is missing whenever the request carried
 * no User-Agent header, so both reads can raise a warning on a perfectly ordinary request.
 *
 * easel_is_signup() also has a second problem worth writing down: it treats the result of
 * strpos() as a boolean, and strpos() answers 0 -- which is falsy -- when the needle is at
 * the start of the haystack. So the check misses exactly the case where SCRIPT_NAME is the
 * bare filename.
 *
 * After the fix both reads are guarded, so the warning assertions become assertSame(
 * array(), ... ), and the strpos() comparison becomes an explicit !== false, which makes
 * testSignupCheckMissesABareScriptName() assert true instead of false.
 */
class SuperglobalAccessTest extends Easel_TestCase {

	/** @var array */
	private $server_backup;

	protected function setUp(): void {
		parent::setUp();
		$this->server_backup = $_SERVER;
		self::loadThemeFunction( 'easel_is_signup' );
		self::loadThemeFile( 'widgets/calendar.php' );
	}

	protected function tearDown(): void {
		$_SERVER = $this->server_backup;
		unset( $GLOBALS['posts'] );
		parent::tearDown();
	}

	/**
	 * Current behaviour: one warning per read, and the line reads SCRIPT_NAME twice.
	 */
	public function testSignupCheckWarnsWhenScriptNameIsAbsent() {
		unset( $_SERVER['SCRIPT_NAME'] );

		$run = $this->runCapturing(
			function () {
				$this->assertFalse( easel_is_signup() );
			}
		);

		$warnings = self::diagnosticsMatching( $run['diagnostics'], 'Undefined array key "SCRIPT_NAME"' );
		$this->assertCount( 2, $warnings, 'the condition reads SCRIPT_NAME once per branch' );
		$this->assertSame( E_WARNING, $warnings[0]['errno'] );
	}

	/**
	 * With the key present the function answers, and the answer is right for the usual case
	 * where SCRIPT_NAME carries a leading path.
	 */
	public function testSignupCheckMatchesAScriptNameWithALeadingPath() {
		$_SERVER['SCRIPT_NAME'] = '/wp-signup.php';
		$this->assertTrue( easel_is_signup() );

		$_SERVER['SCRIPT_NAME'] = '/wp-activate.php';
		$this->assertTrue( easel_is_signup() );

		$_SERVER['SCRIPT_NAME'] = '/index.php';
		$this->assertFalse( easel_is_signup() );
	}

	/**
	 * Current behaviour: a bare filename is missed, because strpos() returns position 0 and
	 * the function tests it for truth rather than for false.
	 *
	 * After the fix both of these assert true.
	 */
	public function testSignupCheckMissesABareScriptName() {
		// The reason, stated on its own so a failure here is unambiguous.
		$this->assertSame( 0, strpos( 'wp-signup.php', 'wp-signup.php' ) );

		$_SERVER['SCRIPT_NAME'] = 'wp-signup.php';
		$this->assertFalse( easel_is_signup() );

		$_SERVER['SCRIPT_NAME'] = 'wp-activate.php';
		$this->assertFalse( easel_is_signup() );
	}

	/**
	 * The calendar widget reads HTTP_USER_AGENT three times on one line while picking a
	 * title separator, so a request with no User-Agent header raises three warnings.
	 *
	 * $posts is set so the widget gets past its "no posts at all, abort" shortcut; the $wpdb
	 * spy answers everything after that with empty results.
	 */
	public function testCalendarWarnsWhenUserAgentIsAbsent() {
		$this->useWpdbSpy();
		$GLOBALS['posts'] = array( 1 );
		unset( $_SERVER['HTTP_USER_AGENT'] );

		$run = $this->runCapturing(
			function () {
				easel_get_calendar( true, false );
			}
		);

		$warnings = self::diagnosticsMatching( $run['diagnostics'], 'Undefined array key "HTTP_USER_AGENT"' );
		$this->assertCount( 3, $warnings, 'three reads on one line' );
		$this->assertSame( E_WARNING, $warnings[0]['errno'] );
	}

	/**
	 * The control: with the header present the same call raises no HTTP_USER_AGENT warning,
	 * so the test above is about the missing key rather than about the widget in general.
	 */
	public function testCalendarIsQuietWhenUserAgentIsPresent() {
		$this->useWpdbSpy();
		$GLOBALS['posts']           = array( 1 );
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';

		$run = $this->runCapturing(
			function () {
				easel_get_calendar( true, false );
			}
		);

		$this->assertSame(
			array(),
			self::diagnosticsMatching( $run['diagnostics'], 'HTTP_USER_AGENT' )
		);
	}
}
