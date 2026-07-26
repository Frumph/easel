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
				$code .= preg_replace( '/^function\s+' . $name . '\s*\(/', 'function easel_test_real_' . $name . '(', $body ) . "\n";
			}
			eval( $code );
		}
		Easel_Test_State::$use_real_themeinfo = true;
		// The real accessor memoises into a global, so clear it or the previous test's
		// values leak into this one.
		$GLOBALS['easel_themeinfo'] = array();
	}

	/**
	 * Return the source text of a top-level function declaration, braces matched.
	 */
	private static function extractFunction( $source, $name ) {
		if ( ! preg_match( '/^function\s+' . preg_quote( $name, '/' ) . '\s*\(/m', $source, $m, PREG_OFFSET_CAPTURE ) ) {
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
