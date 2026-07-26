<?php
/**
 * WordPress stubs for the Easel unit tests.
 *
 * These tests deliberately do NOT boot WordPress. The theme's functions depend only on
 * their arguments plus a small set of WordPress helpers, so stubbing those helpers gives
 * fast, dependency-free tests of the logic that matters.
 *
 * A handful of these stubs have to be faithful or the tests they support are worthless:
 *
 *  - esc_html()/esc_attr() call _wp_specialchars() with $double_encode = FALSE, so they
 *    leave text that already looks like an entity alone. esc_textarea() double-encodes.
 *    Several escaping tests probe exactly that difference, so a naive htmlspecialchars()
 *    stub would give the wrong answer and the tests would lie.
 *  - esc_url() drops a disallowed scheme; esc_attr() happily passes "javascript:" through.
 *    That asymmetry is the whole point of a number of findings in this theme, so the two
 *    stubs must not be quietly interchangeable.
 *  - wp_filter_nohtml_kses() is the theme's one and only sanitiser on the options screen.
 *    WordPress defines it as addslashes( wp_kses( stripslashes( $data ), array() ) ):
 *    it removes every tag and leaves quotes ALONE. Tests turn on that second half -- a
 *    stub that also escaped quotes would make unescaped attribute output look safe.
 *  - The nonce functions are a test-driven seam rather than a reimplementation, because
 *    the CSRF tests need to make verification both pass and fail, and need to prove it
 *    was attempted at all. See the block below for the contract.
 *  - current_user_can() defaults to FALSE, so a missing capability check shows up as a
 *    failing test rather than as a silent pass.
 *  - apply_filters() passes through by default but is overridable, because the filters
 *    are the seams the output tests hang on.
 *
 * State is held in Easel_Test_State and must be reset between tests (the base test case
 * in tests/Easel_TestCase.php does that automatically).
 */

/** Where the stubs pretend the theme is served from. */
define( 'EASEL_TEST_THEME_URI', 'https://example.test/wp-content/themes/easel' );

// wp-includes/wp-db.php return-format constants. widgets/calendar.php passes ARRAY_N to
// $wpdb->get_results(), so they have to exist even though the spy ignores them.
if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
	define( 'ARRAY_A', 'ARRAY_A' );
	define( 'ARRAY_N', 'ARRAY_N' );
}

class Easel_Test_State {
	/** @var array<string,mixed> option name => value */
	public static $options = array();
	/** @var array<string,mixed> "postid:metakey" => value */
	public static $post_meta = array();
	/** @var array<string,mixed> theme mod name => value */
	public static $theme_mods = array();
	/** @var array<string,bool> capability => bool, consulted by current_user_can() */
	public static $user_caps = array();
	/** @var array<string,mixed> filter tag => value to return */
	public static $filters = array();
	/** @var array<string,string> msgid => translation */
	public static $translations = array();
	/** @var array<string,mixed> easel_themeinfo() key => value */
	public static $themeinfo = array();
	/** @var bool route easel_themeinfo()/easel_load_options() to the theme's real code */
	public static $use_real_themeinfo = false;
	/** @var array<string,bool> "action|nonce" => true; anything else fails verification */
	public static $valid_nonces = array();
	/** @var array every wp_verify_nonce()/check_admin_referer() call, in order */
	public static $nonce_checks = array();
	/** @var string[] names passed to delete_option() */
	public static $deleted_options = array();
	/** @var array [post_id, key] pairs passed to delete_post_meta() */
	public static $deleted_meta = array();
	/** @var array recorded update_post_meta() calls */
	public static $meta_writes = array();

	public static function reset() {
		self::$options            = array(
			'blog_charset'  => 'UTF-8',
			'start_of_week' => 1,
		);
		self::$post_meta          = array();
		self::$theme_mods         = array();
		self::$user_caps          = array();
		self::$filters            = array();
		self::$translations       = array();
		self::$themeinfo          = array();
		self::$use_real_themeinfo = false;
		self::$valid_nonces       = array();
		self::$nonce_checks       = array();
		self::$deleted_options    = array();
		self::$deleted_meta       = array();
		self::$meta_writes        = array();
	}
}

/* -------------------------------------------------------------------------- *
 * Escaping — must mirror WordPress's double_encode semantics. See header.
 * -------------------------------------------------------------------------- */

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false );
	}
}

if ( ! function_exists( 'esc_textarea' ) ) {
	function esc_textarea( $text ) {
		// Note the missing 4th argument: htmlspecialchars() defaults to double_encode = true,
		// which is what WordPress's esc_textarea() does too.
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_js' ) ) {
	function esc_js( $text ) {
		return addslashes( (string) $text );
	}
}

/**
 * Approximation of esc_url(). The behaviour the tests rely on is that a disallowed scheme
 * yields an empty string and that ampersands are entity-encoded.
 */
if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url, $protocols = null ) {
		$url = (string) $url;
		if ( '' === $url ) {
			return '';
		}
		$allowed = null === $protocols
			? array( 'http', 'https', 'mailto', 'ftp', 'ftps', 'news', 'irc', 'tel' )
			: $protocols;
		if ( preg_match( '#^([a-z0-9+.-]+):#i', $url, $m ) && ! in_array( strtolower( $m[1] ), $allowed, true ) ) {
			return '';
		}
		$url = str_replace( array( '"', "'", '<', '>' ), '', $url );
		return str_replace( '&amp;', '&#038;', htmlspecialchars( $url, ENT_NOQUOTES, 'UTF-8', false ) );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url, $protocols = null ) {
		$url = (string) $url;
		if ( '' === $url ) {
			return '';
		}
		$allowed = null === $protocols
			? array( 'http', 'https', 'mailto', 'ftp', 'ftps', 'news', 'irc', 'tel' )
			: $protocols;
		if ( preg_match( '#^([a-z0-9+.-]+):#i', $url, $m ) && ! in_array( strtolower( $m[1] ), $allowed, true ) ) {
			return '';
		}
		return $url;
	}
}

/**
 * Only the empty-allowlist case is modelled, which is the only way the theme calls kses.
 * WordPress additionally normalises bare ampersands to &amp;; nothing here depends on
 * that, and strip_tags() matches on everything that does.
 */
if ( ! function_exists( 'wp_kses' ) ) {
	function wp_kses( $content, $allowed_html = array(), $allowed_protocols = array() ) {
		return strip_tags( (string) $content );
	}
}

/**
 * Written out the long way on purpose, because the shape is the point.
 *
 * This is the theme's only sanitiser on the options screen, and what it does NOT do is
 * as load-bearing as what it does: tags go, quotes stay. Anything that lands in an HTML
 * attribute still needs esc_attr() on the way out, and several tests exist to say so.
 */
if ( ! function_exists( 'wp_filter_nohtml_kses' ) ) {
	function wp_filter_nohtml_kses( $data ) {
		return addslashes( wp_kses( stripslashes( (string) $data ), array() ) );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		$str = strip_tags( (string) $str );
		$str = preg_replace( '/[\r\n\t ]+/', ' ', $str );
		return trim( str_replace( "\0", '', $str ) );
	}
}

if ( ! function_exists( 'sanitize_html_class' ) ) {
	function sanitize_html_class( $class, $fallback = '' ) {
		$sanitized = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $class );
		return '' === $sanitized ? $fallback : $sanitized;
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
	}
}

if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( $title, $fallback_title = '', $context = 'save' ) {
		$title = strtolower( strip_tags( (string) $title ) );
		$title = preg_replace( '/[^a-z0-9_\-\s]/', '', $title );
		$title = preg_replace( '/[\s_]+/', '-', trim( $title ) );
		return '' === $title ? $fallback_title : $title;
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return is_array( $value ) ? array_map( 'wp_unslash', $value ) : stripslashes( (string) $value );
	}
}

/* -------------------------------------------------------------------------- *
 * Nonces — the seam the CSRF tests hang on.
 *
 * Not a reimplementation: a real nonce depends on the user, the session token and the
 * clock, none of which exist here, and reproducing the hash would prove nothing anyway.
 * What the tests need is control and visibility.
 *
 *   Control    Easel_Test_State::$valid_nonces holds "action|nonce" keys. A test that
 *              wants verification to succeed calls Easel_TestCase::allowNonce(); a test
 *              that wants to model an attacker simply does not, and wp_verify_nonce()
 *              returns false the way it does for a forged request.
 *   Visibility every call is appended to Easel_Test_State::$nonce_checks, so a test can
 *              assert that a save handler CHECKED at all. That matters more than the
 *              verdict: a handler with no nonce check passes any test that only looks at
 *              the happy path, and only an empty $nonce_checks reveals it.
 * -------------------------------------------------------------------------- */

if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( $action = -1 ) {
		$nonce = 'nonce-' . md5( (string) $action );
		Easel_Test_State::$valid_nonces[ $action . '|' . $nonce ] = true;
		return $nonce;
	}
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
	function wp_verify_nonce( $nonce, $action = -1 ) {
		$nonce = (string) $nonce;
		$valid = ! empty( Easel_Test_State::$valid_nonces[ $action . '|' . $nonce ] );
		Easel_Test_State::$nonce_checks[] = array(
			'nonce'  => $nonce,
			'action' => $action,
			'valid'  => $valid,
		);
		return $valid ? 1 : false;
	}
}

if ( ! function_exists( 'wp_nonce_field' ) ) {
	function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $display = true ) {
		$field = '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( wp_create_nonce( $action ) ) . '" />';
		if ( $display ) {
			echo $field;
		}
		return $field;
	}
}

/**
 * Records like wp_verify_nonce() does, and returns rather than dying so a test can assert
 * on the false branch instead of watching the process exit.
 */
if ( ! function_exists( 'check_admin_referer' ) ) {
	function check_admin_referer( $action = -1, $query_arg = '_wpnonce' ) {
		$nonce = isset( $_REQUEST[ $query_arg ] ) ? $_REQUEST[ $query_arg ] : '';
		return wp_verify_nonce( $nonce, $action );
	}
}

/* -------------------------------------------------------------------------- *
 * Options, meta, theme mods, capabilities, filters — all test-driven.
 * -------------------------------------------------------------------------- */

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default = false ) {
		return array_key_exists( $name, Easel_Test_State::$options ) ? Easel_Test_State::$options[ $name ] : $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $name, $value, $autoload = null ) {
		Easel_Test_State::$options[ $name ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( $name ) {
		Easel_Test_State::$deleted_options[] = $name;
		unset( Easel_Test_State::$options[ $name ] );
		return true;
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( $post_id, $key = '', $single = false ) {
		$k = $post_id . ':' . $key;
		if ( ! array_key_exists( $k, Easel_Test_State::$post_meta ) ) {
			return $single ? '' : array();
		}
		return Easel_Test_State::$post_meta[ $k ];
	}
}

if ( ! function_exists( 'update_post_meta' ) ) {
	function update_post_meta( $post_id, $key, $value, $prev = '' ) {
		Easel_Test_State::$post_meta[ $post_id . ':' . $key ] = $value;
		Easel_Test_State::$meta_writes[]                      = array( $post_id, $key, $value );
		return true;
	}
}

if ( ! function_exists( 'delete_post_meta' ) ) {
	function delete_post_meta( $post_id, $key, $value = '' ) {
		Easel_Test_State::$deleted_meta[] = array( $post_id, $key );
		unset( Easel_Test_State::$post_meta[ $post_id . ':' . $key ] );
		return true;
	}
}

if ( ! function_exists( 'get_theme_mod' ) ) {
	function get_theme_mod( $name, $default = false ) {
		return array_key_exists( $name, Easel_Test_State::$theme_mods ) ? Easel_Test_State::$theme_mods[ $name ] : $default;
	}
}

if ( ! function_exists( 'set_theme_mod' ) ) {
	function set_theme_mod( $name, $value ) {
		Easel_Test_State::$theme_mods[ $name ] = $value;
		return true;
	}
}

if ( ! function_exists( 'remove_theme_mod' ) ) {
	function remove_theme_mod( $name ) {
		unset( Easel_Test_State::$theme_mods[ $name ] );
	}
}

/**
 * Defaults to false on purpose. A save handler that forgets its capability check should
 * fail a test, not sail through one; if every capability were granted by default the
 * missing check would be invisible.
 */
if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability, ...$args ) {
		return ! empty( Easel_Test_State::$user_caps[ $capability ] );
	}
}

if ( ! function_exists( 'user_can' ) ) {
	function user_can( $user, $capability, ...$args ) {
		return ! empty( Easel_Test_State::$user_caps[ $capability ] );
	}
}

if ( ! function_exists( 'is_user_logged_in' ) ) {
	function is_user_logged_in() {
		return ! empty( Easel_Test_State::$user_caps );
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value = null, ...$args ) {
		return array_key_exists( $tag, Easel_Test_State::$filters ) ? Easel_Test_State::$filters[ $tag ] : $value;
	}
}

/* -------------------------------------------------------------------------- *
 * Translation and number formatting
 * -------------------------------------------------------------------------- */

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return array_key_exists( $text, Easel_Test_State::$translations ) ? Easel_Test_State::$translations[ $text ] : $text;
	}
}

if ( ! function_exists( '_e' ) ) {
	function _e( $text, $domain = 'default' ) {
		echo __( $text, $domain );
	}
}

if ( ! function_exists( '_x' ) ) {
	function _x( $text, $context, $domain = 'default' ) {
		return __( $text, $domain );
	}
}

if ( ! function_exists( '_n' ) ) {
	function _n( $single, $plural, $number, $domain = 'default' ) {
		return __( 1 === (int) $number ? $single : $plural, $domain );
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) {
		return esc_html( __( $text, $domain ) );
	}
}

if ( ! function_exists( 'esc_attr__' ) ) {
	function esc_attr__( $text, $domain = 'default' ) {
		return esc_attr( __( $text, $domain ) );
	}
}

if ( ! function_exists( 'esc_html_e' ) ) {
	function esc_html_e( $text, $domain = 'default' ) {
		echo esc_html( __( $text, $domain ) );
	}
}

if ( ! function_exists( 'esc_attr_e' ) ) {
	function esc_attr_e( $text, $domain = 'default' ) {
		echo esc_attr( __( $text, $domain ) );
	}
}

if ( ! function_exists( 'number_format_i18n' ) ) {
	function number_format_i18n( $number, $decimals = 0 ) {
		return number_format( (float) $number, (int) $decimals );
	}
}

if ( ! function_exists( 'load_theme_textdomain' ) ) {
	function load_theme_textdomain( $domain, $path = false ) {
		return true;
	}
}

/* -------------------------------------------------------------------------- *
 * Theme locations — every stub answers with the repo itself, so file_exists() and
 * glob() calls inside the theme (moods, avatars) see the real shipped directories.
 * -------------------------------------------------------------------------- */

if ( ! function_exists( 'get_template_directory' ) ) {
	function get_template_directory() {
		return EASEL_THEME_DIR;
	}
}

if ( ! function_exists( 'get_stylesheet_directory' ) ) {
	function get_stylesheet_directory() {
		return EASEL_THEME_DIR;
	}
}

if ( ! function_exists( 'get_template_directory_uri' ) ) {
	function get_template_directory_uri() {
		return EASEL_TEST_THEME_URI;
	}
}

if ( ! function_exists( 'get_stylesheet_directory_uri' ) ) {
	function get_stylesheet_directory_uri() {
		return EASEL_TEST_THEME_URI;
	}
}

if ( ! function_exists( 'get_stylesheet_uri' ) ) {
	function get_stylesheet_uri() {
		return EASEL_TEST_THEME_URI . '/style.css';
	}
}

if ( ! function_exists( 'home_url' ) ) {
	function home_url( $path = '' ) {
		return 'https://example.test' . $path;
	}
}

if ( ! function_exists( 'site_url' ) ) {
	function site_url( $path = '' ) {
		return 'https://example.test' . $path;
	}
}

if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo( $show = '', $filter = 'raw' ) {
		$defaults = array(
			'name'        => 'Example Comic',
			'description' => 'Just another WordPress site',
			'url'         => home_url(),
			'wpurl'       => home_url(),
			'rss2_url'    => home_url( '/feed/' ),
			'charset'     => get_option( 'blog_charset' ),
			'version'     => '6.4',
			'language'    => 'en-US',
		);
		return array_key_exists( $show, $defaults ) ? $defaults[ $show ] : '';
	}
}

if ( ! function_exists( 'bloginfo' ) ) {
	function bloginfo( $show = '' ) {
		echo get_bloginfo( $show );
	}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	function add_query_arg( ...$args ) {
		if ( is_array( $args[0] ) ) {
			$params = $args[0];
			$url    = isset( $args[1] ) ? $args[1] : '';
		} else {
			$params = array( $args[0] => $args[1] );
			$url    = isset( $args[2] ) ? $args[2] : '';
		}
		$sep = false === strpos( $url, '?' ) ? '?' : '&';
		return $url . $sep . http_build_query( $params );
	}
}

/* -------------------------------------------------------------------------- *
 * Posts, users, dates and archive links
 * -------------------------------------------------------------------------- */

if ( ! function_exists( 'get_post' ) ) {
	function get_post( $post = null, $output = OBJECT ) {
		if ( is_object( $post ) ) {
			return $post;
		}
		if ( null === $post || 0 === (int) $post ) {
			return isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
		}
		$stub              = new stdClass();
		$stub->ID          = (int) $post;
		$stub->post_author = 0;
		$stub->post_type   = 'post';
		$stub->post_status = 'publish';
		$stub->post_title  = 'Title';
		return $stub;
	}
}

if ( ! function_exists( 'get_the_ID' ) ) {
	function get_the_ID() {
		return isset( $GLOBALS['post'] ) ? $GLOBALS['post']->ID : 0;
	}
}

if ( ! function_exists( 'get_userdata' ) ) {
	function get_userdata( $user_id ) {
		$user               = new stdClass();
		$user->ID           = (int) $user_id;
		$user->display_name = 'User ' . (int) $user_id;
		$user->user_login   = 'user' . (int) $user_id;
		$user->user_url     = '';
		return $user;
	}
}

if ( ! function_exists( 'get_permalink' ) ) {
	function get_permalink( $post = 0 ) {
		return 'https://example.test/?p=' . ( is_object( $post ) ? $post->ID : (int) $post );
	}
}

if ( ! function_exists( 'get_the_title' ) ) {
	function get_the_title( $post = 0 ) {
		return 'Title';
	}
}

if ( ! function_exists( 'get_the_time' ) ) {
	function get_the_time( $format = '', $post = null ) {
		return '2020-01-01';
	}
}

/**
 * Fixed clock. Dates in the calendar widget are compared against this, so a test asserting
 * on "today" stays green in December.
 */
if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type, $gmt = 0 ) {
		$ts = 1577880000; // 2020-01-01 12:00:00 UTC
		if ( 'timestamp' === $type || 'U' === $type ) {
			return $ts;
		}
		if ( 'mysql' === $type ) {
			return gmdate( 'Y-m-d H:i:s', $ts );
		}
		return gmdate( $type, $ts );
	}
}

if ( ! function_exists( 'get_month_link' ) ) {
	function get_month_link( $year, $month ) {
		return sprintf( 'https://example.test/%04d/%02d/', (int) $year, (int) $month );
	}
}

if ( ! function_exists( 'get_day_link' ) ) {
	function get_day_link( $year, $month, $day ) {
		return sprintf( 'https://example.test/%04d/%02d/%02d/', (int) $year, (int) $month, (int) $day );
	}
}

if ( ! function_exists( 'zeroise' ) ) {
	function zeroise( $number, $threshold ) {
		return sprintf( '%0' . (int) $threshold . 's', $number );
	}
}

if ( ! function_exists( 'calendar_week_mod' ) ) {
	function calendar_week_mod( $num ) {
		$base = 7;
		return $num - $base * floor( $num / $base );
	}
}

/* -------------------------------------------------------------------------- *
 * $wp_locale stand-in. widgets/calendar.php reaches for the global directly, so the base
 * test case installs one of these before every test.
 * -------------------------------------------------------------------------- */

class Easel_Locale {
	public $weekday         = array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );
	public $weekday_initial = array(
		'Sunday'    => 'S',
		'Monday'    => 'M',
		'Tuesday'   => 'T',
		'Wednesday' => 'W',
		'Thursday'  => 'T',
		'Friday'    => 'F',
		'Saturday'  => 'S',
	);
	public $weekday_abbrev  = array(
		'Sunday'    => 'Sun',
		'Monday'    => 'Mon',
		'Tuesday'   => 'Tue',
		'Wednesday' => 'Wed',
		'Thursday'  => 'Thu',
		'Friday'    => 'Fri',
		'Saturday'  => 'Sat',
	);
	public $month           = array(
		'01' => 'January',
		'02' => 'February',
		'03' => 'March',
		'04' => 'April',
		'05' => 'May',
		'06' => 'June',
		'07' => 'July',
		'08' => 'August',
		'09' => 'September',
		'10' => 'October',
		'11' => 'November',
		'12' => 'December',
	);

	public function get_month( $month_number ) {
		$key = zeroise( (int) $month_number, 2 );
		return isset( $this->month[ $key ] ) ? $this->month[ $key ] : '';
	}

	public function get_month_abbrev( $month_name ) {
		return substr( (string) $month_name, 0, 3 );
	}

	public function get_weekday( $weekday_number ) {
		return $this->weekday[ (int) $weekday_number ];
	}

	public function get_weekday_initial( $weekday_name ) {
		return isset( $this->weekday_initial[ $weekday_name ] ) ? $this->weekday_initial[ $weekday_name ] : '';
	}

	public function get_weekday_abbrev( $weekday_name ) {
		return isset( $this->weekday_abbrev[ $weekday_name ] ) ? $this->weekday_abbrev[ $weekday_name ] : '';
	}
}

/* -------------------------------------------------------------------------- *
 * Hook, widget and asset registration — no-ops, so theme files can simply be require'd.
 * -------------------------------------------------------------------------- */

if ( ! function_exists( 'add_action' ) ) {
	function add_action( ...$args ) {
		return true;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( ...$args ) {
		return true;
	}
}

if ( ! function_exists( 'remove_action' ) ) {
	function remove_action( ...$args ) {
		return true;
	}
}

if ( ! function_exists( 'remove_filter' ) ) {
	function remove_filter( ...$args ) {
		return true;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( ...$args ) {
		return true;
	}
}

if ( ! function_exists( 'register_widget' ) ) {
	function register_widget( $widget ) {
		return true;
	}
}

if ( ! function_exists( 'register_sidebar' ) ) {
	function register_sidebar( $args = array() ) {
		return isset( $args['id'] ) ? $args['id'] : 'sidebar-1';
	}
}

if ( ! function_exists( 'register_nav_menus' ) ) {
	function register_nav_menus( $locations = array() ) {
		return true;
	}
}

if ( ! function_exists( 'add_theme_support' ) ) {
	function add_theme_support( $feature, ...$args ) {
		return true;
	}
}

if ( ! function_exists( 'add_image_size' ) ) {
	function add_image_size( $name, $width = 0, $height = 0, $crop = false ) {
		return true;
	}
}

if ( ! function_exists( 'add_meta_box' ) ) {
	function add_meta_box( $id, $title, $callback, $screen = null, $context = 'advanced', $priority = 'default', $callback_args = null ) {
		return true;
	}
}

if ( ! function_exists( 'add_theme_page' ) ) {
	function add_theme_page( $page_title, $menu_title, $capability, $menu_slug, $callback = '' ) {
		return 'appearance_page_' . $menu_slug;
	}
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
		return true;
	}
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
	function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
		return true;
	}
}

if ( ! function_exists( 'wp_admin_css' ) ) {
	function wp_admin_css( $file = 'wp-admin', $force_echo = false ) {
		return true;
	}
}

if ( ! function_exists( 'get_template_part' ) ) {
	function get_template_part( $slug, $name = null ) {
		return false;
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = array() ) {
		return array_merge( $defaults, (array) $args );
	}
}

if ( ! function_exists( 'checked' ) ) {
	function checked( $checked, $current = true, $display = true ) {
		$out = ( (string) $checked === (string) $current ) ? ' checked="checked"' : '';
		if ( $display ) {
			echo $out;
		}
		return $out;
	}
}

if ( ! function_exists( 'selected' ) ) {
	function selected( $selected, $current = true, $display = true ) {
		$out = ( (string) $selected === (string) $current ) ? ' selected="selected"' : '';
		if ( $display ) {
			echo $out;
		}
		return $out;
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		return false;
	}
}

if ( ! function_exists( 'is_multisite' ) ) {
	function is_multisite() {
		return false;
	}
}

if ( ! function_exists( 'wp_cache_get' ) ) {
	function wp_cache_get( $key, $group = '' ) {
		return false;
	}
}

if ( ! function_exists( 'wp_cache_set' ) ) {
	function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
		return true;
	}
}

if ( ! function_exists( 'wp_cache_delete' ) ) {
	function wp_cache_delete( $key, $group = '' ) {
		return true;
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public $message;
		public function __construct( $code = '', $message = '' ) {
			$this->message = $message;
		}
		public function get_error_message() {
			return $this->message;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

/* -------------------------------------------------------------------------- *
 * Widgets
 * -------------------------------------------------------------------------- */

if ( ! class_exists( 'WP_Widget' ) ) {
	class WP_Widget {
		public $id_base;
		public $name;
		public function __construct( $id_base = '', $name = '', $widget_options = array(), $control_options = array() ) {
			$this->id_base = $id_base;
			$this->name    = $name;
		}
		public function get_field_id( $field ) {
			return $this->id_base . '-' . $field;
		}
		public function get_field_name( $field ) {
			return $this->id_base . '[' . $field . ']';
		}
	}
}

/* -------------------------------------------------------------------------- *
 * $wpdb spy — records the SQL it is handed so tests can assert on the query text
 * without a database. This is what stands in for MySQL when checking that untrusted
 * input never reaches the SQL string.
 * -------------------------------------------------------------------------- */

class Easel_WPDB_Spy {
	public $posts              = 'wp_posts';
	public $postmeta           = 'wp_postmeta';
	public $terms              = 'wp_terms';
	public $term_relationships = 'wp_term_relationships';
	public $term_taxonomy      = 'wp_term_taxonomy';
	public $prefix             = 'wp_';

	/** @var string[] every SQL string handed to prepare() or a get_*() method */
	public $queries = array();
	/** @var mixed value the next get_col()/get_results()/get_var()/get_row() returns */
	public $result = array();

	public function prepare( $query, ...$args ) {
		// Close enough to $wpdb::prepare for assertion purposes: %d becomes an integer,
		// %s becomes a single-quoted escaped string.
		$i   = 0;
		$out = preg_replace_callback(
			'/%[dsf]/',
			function ( $m ) use ( &$i, $args ) {
				$arg = array_key_exists( $i, $args ) ? $args[ $i ] : '';
				$i++;
				if ( '%d' === $m[0] ) {
					return (string) (int) $arg;
				}
				if ( '%f' === $m[0] ) {
					return (string) (float) $arg;
				}
				return "'" . addslashes( (string) $arg ) . "'";
			},
			$query
		);
		$this->queries[] = $out;
		return $out;
	}

	/**
	 * $wpdb::escape() was deprecated in WordPress 3.6 and removed in 5.3, but
	 * widgets/calendar.php still calls it, so the spy has to answer. Behaves like the
	 * addslashes() it always was -- which is exactly why it is not a sanitiser.
	 */
	public function escape( $data ) {
		return is_array( $data ) ? array_map( array( $this, 'escape' ), $data ) : addslashes( (string) $data );
	}

	public function get_col( $query = null, $x = 0 ) {
		if ( null !== $query ) {
			$this->queries[] = $query;
		}
		return $this->result;
	}

	public function get_results( $query = null, $output = null ) {
		if ( null !== $query ) {
			$this->queries[] = $query;
		}
		return $this->result;
	}

	public function get_var( $query = null, $x = 0, $y = 0 ) {
		if ( null !== $query ) {
			$this->queries[] = $query;
		}
		return is_array( $this->result ) ? null : $this->result;
	}

	public function get_row( $query = null, $output = null, $y = 0 ) {
		if ( null !== $query ) {
			$this->queries[] = $query;
		}
		return null;
	}

	public function query( $query ) {
		$this->queries[] = $query;
		return true;
	}

	public function update( $table, $data, $where, ...$rest ) {
		return 1;
	}

	/** The SQL from the most recent call. */
	public function last() {
		return end( $this->queries );
	}

	/** True when any recorded query contains $needle. */
	public function sawSql( $needle ) {
		foreach ( $this->queries as $q ) {
			if ( false !== strpos( $q, $needle ) ) {
				return true;
			}
		}
		return false;
	}
}
