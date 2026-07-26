<?php
/**
 * PHPUnit bootstrap for the Easel theme.
 *
 * WordPress is not loaded. See tests/stubs.php for the rationale and for the stubs the
 * tests depend on.
 */

// No theme file guards on ABSPATH today, but WordPress-adjacent code checks for it out of
// habit, and a guard added later should not silently exit the test run.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

define( 'EASEL_THEME_DIR', dirname( __DIR__ ) );

require_once __DIR__ . '/stubs.php';

/**
 * easel_load_options() and easel_themeinfo() are the theme's own settings accessors, and
 * practically every function in the theme calls one of them.
 *
 * They live in functions.php, which cannot be require'd from a unit test. functions.php
 * calls easel_themeinfo() on line 6 -- before it is defined -- glob-autoloads every file
 * in functions/ and widgets/ through get_template_part(), and pulls in options.php when
 * is_admin(). Requiring it would drag in the entire theme to test one function.
 *
 * So they are stubbed here and driven from Easel_Test_State::$themeinfo, the same way the
 * other tests drive options and post meta.
 *
 * But the two functions are worth testing in their own right -- easel_load_options() is
 * where every default lives -- so these are DISPATCHERS rather than plain stubs.
 * Easel_TestCase::loadRealThemeInfo() lifts the real bodies out of functions.php verbatim
 * (only the declaration line is renamed) and flips Easel_Test_State::$use_real_themeinfo.
 * Because the real easel_themeinfo() reaches easel_load_options() through this same
 * dispatcher, both halves switch over together and the real code runs unmodified.
 *
 * Nothing in the theme's source is changed to make any of this work, which is the point:
 * the harness has to be able to test the theme as shipped.
 */
if ( ! function_exists( 'easel_load_options' ) ) {
	function easel_load_options() {
		if ( Easel_Test_State::$use_real_themeinfo ) {
			return easel_test_real_easel_load_options();
		}
		return Easel_Test_State::$themeinfo;
	}
}

if ( ! function_exists( 'easel_themeinfo' ) ) {
	function easel_themeinfo( $whichinfo = null ) {
		if ( Easel_Test_State::$use_real_themeinfo ) {
			return easel_test_real_easel_themeinfo( $whichinfo );
		}
		if ( null === $whichinfo || 'reset' === $whichinfo ) {
			return Easel_Test_State::$themeinfo;
		}
		// The real accessor answers false, not '', for a key it does not hold, and the
		// theme leans on that in dozens of `if (easel_themeinfo('...'))` checks.
		return array_key_exists( $whichinfo, Easel_Test_State::$themeinfo )
			? Easel_Test_State::$themeinfo[ $whichinfo ]
			: false;
	}
}

require_once __DIR__ . '/Easel_TestCase.php';
