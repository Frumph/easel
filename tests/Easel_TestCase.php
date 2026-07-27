<?php

use PHPUnit\Framework\TestCase;

/**
 * Base case: resets the stub state between tests and offers a few helpers for loading
 * theme files once per process.
 */
abstract class Easel_TestCase extends TestCase {

	/** @var array<string,bool> theme files already required in this process */
	private static $loaded = array();

	protected function setUp(): void {
		parent::setUp();
		Easel_Test_State::reset();
		// widgets/calendar.php reads $wp_locale straight off the globals, so give every
		// test a fresh one rather than making each remember to.
		$GLOBALS['wp_locale'] = new Easel_Locale();
	}

	/**
	 * Require a theme file, relative to the theme root, at most once per process.
	 *
	 * One file at a time and never functions.php: that file autoloads all of functions/
	 * and widgets/ and calls easel_themeinfo() before defining it. See tests/bootstrap.php.
	 */
	protected static function loadThemeFile( $relative ) {
		if ( isset( self::$loaded[ $relative ] ) ) {
			return;
		}
		self::$loaded[ $relative ] = true;
		require_once EASEL_THEME_DIR . '/' . $relative;
	}

	/**
	 * Switch easel_themeinfo()/easel_load_options() over to the theme's real code.
	 *
	 * The two functions are extracted from functions.php by brace matching and eval'd
	 * under prefixed names; only the `function <name>(` line differs from what ships.
	 * Their bodies still call easel_load_options() unqualified, which lands back on the
	 * dispatcher in tests/bootstrap.php -- and since that is now in real mode, the real
	 * implementation answers. Ugly, but it beats either duplicating the defaults into the
	 * test suite (where they would drift) or editing functions.php to suit the tests.
	 */
	protected static function loadRealThemeInfo() {
		if ( ! function_exists( 'easel_test_real_easel_themeinfo' ) ) {
			$source = file_get_contents( EASEL_THEME_DIR . '/functions.php' );
			$code   = '';
			foreach ( array( 'easel_load_options', 'easel_themeinfo' ) as $name ) {
				$body = self::extractFunction( $source, $name );
				if ( null === $body ) {
					throw new RuntimeException( "Could not find function $name() in functions.php" );
				}
				$code .= preg_replace( '/^\s*function\s+' . $name . '\s*\(/', 'function easel_test_real_' . $name . '(', $body ) . "\n";
			}
			eval( $code );
		}
		Easel_Test_State::$use_real_themeinfo = true;
		// The real accessor memoises into a global, so clear it or the previous test's
		// values leak into this one.
		$GLOBALS['easel_themeinfo'] = array();
	}

	/**
	 * Lift one function out of a theme file and define it, under its own name, verbatim.
	 *
	 * Same trick as loadRealThemeInfo() and for the same reason: functions.php cannot be
	 * require'd, but several of the functions in it are worth exercising directly. Because
	 * the file is never loaded the real name is free, so nothing has to be renamed here.
	 */
	protected static function loadThemeFunction( $name, $relative = 'functions.php' ) {
		if ( function_exists( $name ) ) {
			return;
		}
		$source = file_get_contents( EASEL_THEME_DIR . '/' . $relative );
		$body   = self::extractFunction( $source, $name );
		if ( null === $body ) {
			throw new RuntimeException( "Could not find function $name() in $relative" );
		}
		eval( $body );
	}

	/**
	 * Return the source text of a function declaration, braces matched.
	 *
	 * Leading whitespace is allowed because some of the theme's functions sit inside an
	 * `if (!function_exists(...))` guard and are therefore indented.
	 */
	private static function extractFunction( $source, $name ) {
		if ( ! preg_match( '/^[ \t]*function\s+' . preg_quote( $name, '/' ) . '\s*\(/m', $source, $m, PREG_OFFSET_CAPTURE ) ) {
			return null;
		}
		$start = $m[0][1];
		$open  = strpos( $source, '{', $start );
		$depth = 0;
		for ( $i = $open, $len = strlen( $source ); $i < $len; $i++ ) {
			if ( '{' === $source[ $i ] ) {
				$depth++;
			} elseif ( '}' === $source[ $i ] ) {
				$depth--;
				if ( 0 === $depth ) {
					return substr( $source, $start, $i - $start + 1 );
				}
			}
		}
		return null;
	}

	/**
	 * Run $callable with its output buffered and PHP's diagnostics intercepted.
	 *
	 * Returns array( 'output' => string, 'diagnostics' => array of errno/message pairs ).
	 *
	 * Some of the code under test emits notices, warnings or deprecations as it runs, and
	 * that emission is exactly what a few of these tests are pinning. The suite runs with
	 * failOnWarning/failOnNotice/failOnDeprecation, so rather than loosening the config the
	 * handler is swapped for the duration of the call: the diagnostics are collected and
	 * returned as ordinary data, which a test can then assert on precisely. Returning true
	 * from the handler stops PHP passing the event on to PHPUnit's own handler.
	 */
	protected function runCapturing( callable $callable ) {
		$diagnostics = array();
		ob_start();
		set_error_handler(
			function ( $errno, $errstr ) use ( &$diagnostics ) {
				$diagnostics[] = array(
					'errno'   => $errno,
					'message' => $errstr,
				);
				return true;
			}
		);
		try {
			$callable();
		} finally {
			restore_error_handler();
			$output = ob_get_clean();
		}
		return array(
			'output'      => $output,
			'diagnostics' => $diagnostics,
		);
	}

	/**
	 * The subset of runCapturing()'s diagnostics whose message contains $needle.
	 */
	protected static function diagnosticsMatching( array $diagnostics, $needle ) {
		return array_values(
			array_filter(
				$diagnostics,
				function ( $entry ) use ( $needle ) {
					return false !== strpos( $entry['message'], $needle );
				}
			)
		);
	}

	/**
	 * Install a fresh $wpdb spy as the global and return it.
	 */
	protected function useWpdbSpy() {
		$spy             = new Easel_WPDB_Spy();
		$GLOBALS['wpdb'] = $spy;
		return $spy;
	}

	/**
	 * Set the global $post to a lightweight stand-in. The functions under test only ever
	 * read a couple of properties, so a real WP_Post is unnecessary.
	 */
	protected function setGlobalPost( $id = 1, $type = 'post' ) {
		$post              = new stdClass();
		$post->ID          = $id;
		$post->post_author = 0;
		$post->post_type   = $type;
		$post->post_status = 'publish';
		$post->post_title  = 'Title';
		$GLOBALS['post']   = $post;
		return $post;
	}

	protected function setThemeInfo( array $values ) {
		Easel_Test_State::$themeinfo = $values;
	}

	protected function setPostMeta( $post_id, $key, $value ) {
		Easel_Test_State::$post_meta[ $post_id . ':' . $key ] = $value;
	}

	protected function grantCap( $cap ) {
		Easel_Test_State::$user_caps[ $cap ] = true;
	}

	/**
	 * Make wp_verify_nonce() accept this exact (nonce, action) pair and nothing else.
	 */
	protected function allowNonce( $nonce, $action ) {
		Easel_Test_State::$valid_nonces[ $action . '|' . $nonce ] = true;
	}
}
