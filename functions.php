<?php
add_action('after_setup_theme', 'easel_setup');
add_action('wp_enqueue_scripts', 'easel_enqueue_theme_scripts');
add_action('widgets_init', 'easel_register_sidebars');
add_filter('excerpt_length', 'easel_excerpt_length');
if (easel_themeinfo('enable_debug_footer_code'))
	add_action('easel-page-foot', 'easel_debug_page_foot_code');
add_filter('excerpt_more', 'easel_auto_excerpt_more');
if (easel_themeinfo('force_active_connection_close')) 
	add_action('shutdown_action_hook','easel_close_up_shop');
if (easel_themeinfo('menubar_social_icons')) 
	add_action('easel-menubar-menunav', 'easel_display_social_icons');

if (!is_admin())
	add_action('init', 'easel_init');

if (class_exists('MultiPostThumbnails')) {
	new MultiPostThumbnails(
		array(
			'label' => 'Secondary Image',
			'id' => 'secondary-image',
			'post_type' => 'comic'
			));
	add_image_size('secondary-image');
}

// These autoload
foreach (glob(get_template_directory() . '/functions/*.php') as $funcfile) {
	get_template_part('functions/'.basename($funcfile,'.php'));
}

// Load all the widgets.
foreach (glob(get_template_directory()  . '/widgets/*.php') as $widgefile) {
	get_template_part('widgets/'.basename($widgefile,'.php'));
}

function easel_setup() {
	load_theme_textdomain('easel', get_template_directory().'/lang');
// 	add_editor_style();
	add_theme_support('automatic-feed-links');
	add_theme_support(
		'post-formats', 
		array(
//			'image',
//			'video',
//			'quote',
//			'status',
			'link',
			'aside'
			)
		);
	register_nav_menus(array(
		'Primary' => __( 'Primary', 'easel' ),
		'Footer' => __( 'Footer', 'easel' )
	));
	add_theme_support('custom-background');
	add_theme_support('post-thumbnails');
	add_theme_support('title-tag');
	add_theme_support('woocommerce'); // PMH
}

function easel_enqueue_theme_scripts() {
	global $is_IE, $wp_styles;
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) && !easel_themeinfo('disable_comment_javascript')) wp_enqueue_script('comment-reply');
	if (!is_admin()) {
		wp_enqueue_script('jquery');
		if (!easel_themeinfo('disable_jquery_menu_code')) {
			wp_enqueue_script('ddsmoothmenu_js', get_template_directory_uri().'/js/ddsmoothmenu.js');
			wp_enqueue_script('menubar_js', get_template_directory_uri().'/js/menubar.js');
		}
		if (!easel_themeinfo('disable_scroll_to_top')) {
			wp_enqueue_script('easel_scroll', get_template_directory_uri().'/js/scroll.js', null, null, true);
		}
		if (easel_themeinfo('enable_avatar_trick') && !$is_IE) {
			wp_enqueue_script('themetricks_historic1', get_template_directory_uri().'/js/cvi_text_lib.js', null, null, true);
			wp_enqueue_script('themetricks_historic2', get_template_directory_uri().'/js/instant.js', null, null, true);
		}
	}
}

function easel_init() {
	add_action('pre_get_posts', 'easel_pre_parser', 1, 1);
}

function easel_pre_parser($query) {
	if ( $query->is_home() && $query->is_main_query()) {
//		$query->set('category__in', '8');
		$query->set('posts_per_page', easel_themeinfo('home_post_count'));
	}
	if (($query->is_archive() || $query->is_search() || is_post_type_archive())  && !$query->is_feed() && $query->is_main_query()) {
		$archive_display_order = easel_themeinfo('archive_display_order');
		if (empty($archive_display_order)) $archive_display_order = 'desc';
		$query->set('order', $archive_display_order);
	}
	if ($query->is_author() && $query->is_main_query()) {
		$query->set('nopaging', true);
	}
}

if (!function_exists('easel_register_sidebars')) {
	function easel_register_sidebars() {
		$widgets_list = array(
			array('id' => 'left-sidebar', 'name' => __( 'Left Sidebar', 'easel' ), 'description' => __( 'The sidebar that appears to the left of the content.', 'easel' )),
			array('id' => 'right-sidebar', 'name' => __( 'Right Sidebar', 'easel' ), 'description' => __( 'The sidebar that appears to the right of the content.', 'easel' )),
			array('id' => 'above-header', 'name' => __( 'Above Header', 'easel' ), 'description' => __( 'This sidebar appears to above all of the site information.  This sidebar is not encased in CSS, you will need to create CSS for it.', 'easel' )),
			array('id' => 'header', 'name' => __( 'Header', 'easel' ), 'description' => __( 'This sidebar appears inside the #header block.', 'easel' )),
			array('id' => 'menubar', 'name' => __( 'Menubar', 'easel' ), 'description' => __( 'This sidebar is under the header and above the content-wrapper block', 'easel' )),
			array('id' => 'over-blog', 'name' => __( 'Over Blog', 'easel' ), 'description' => __( 'This sidebar appears over the blog within the #column .narrowcolumn', 'easel')),
			array('id' => 'under-blog', 'name' => __( 'Under Blog', 'easel' ), 'description' => __( 'This sidebar appears under the blog within the #column .narrowocolumn', 'easel' )),
			array('id' => 'footer', 'name' => __( 'Footer', 'easel' ), 'description' => __( 'This sidebar is at the bottom of the page and is the center of the 3 footer sidebars.', 'easel' )),
			array('id' => 'footer-left', 'name' => __( 'Footer Left', 'easel' ), 'description' => __( 'This sidebar is at the bottom of the page, the left one.', 'easel' )),
			array('id' => 'footer-right', 'name' => __( 'Footer Right', 'easel' ), 'description' => __( 'This sidebar is at the bottom of the page, the right one.', 'easel' )),
		);
		if (class_exists('Jetpack') && Jetpack::init()->is_module_active('minileven')) { 
			$widgets_list[] = array('id' => '1', 'name' => __( 'Jetpack Mobile Sidebar', 'easel' ), 'description' => __( 'Jetpack Mobile Sidebar', 'easel' ));
		}
		foreach ($widgets_list as $widget_info) {
			register_sidebar(array(
						'name'=> $widget_info['name'],
						'id' => 'sidebar-'.sanitize_title($widget_info['id']),
						'description' => $widget_info['description'],
						'before_widget' => "<div id=\"".'%1$s'."\" class=\"widget ".'%2$s'."\">\r\n<div class=\"widget-content\">\r\n",
						'after_widget'  => "</div>\r\n<div class=\"clear\"></div>\r\n</div>\r\n",
						'before_title'  => "<h2 class=\"widget-title\">",
						'after_title'   => "</h2>\r\n"
						));
		}
	}
}

function easel_get_sidebar($location = '') {
	if (empty($location)) return;
	if (file_exists(get_template_directory().'/sidebar-'.$location.'.php') || file_exists(get_stylesheet_directory().'/sidebar-'.$location.'.php')) {
		get_sidebar($location);
	} elseif (is_active_sidebar('sidebar-'.$location)) { ?>
		<div id="sidebar-<?php echo $location; ?>" class="sidebar">
			<?php dynamic_sidebar('sidebar-'.$location); ?>
			<div class="clear"></div>
		</div>
	<?php }
}

function easel_is_signup() {
	global $wp_query;
	if (strpos( $_SERVER['SCRIPT_NAME'], 'wp-signup.php' ) || strpos( $_SERVER['SCRIPT_NAME'], 'wp-activate.php' )) return true;
	return false;
}

function easel_debug_page_foot_code() { ?>
	<p><?php echo get_num_queries() ?> queries. <?php if (function_exists('memory_get_usage')) { $unit=array('b','kb','mb','gb','tb','pb'); echo @round(memory_get_usage(true)/pow(1024,($i=floor(log(memory_get_usage(true),1024)))),2).' '.$unit[$i]; ?> Memory usage. <?php } timer_stop(1) ?> seconds.</p>
<?php }

function easel_excerpt_length($length) {
	return easel_themeinfo('excerpt_length');
}

if (!function_exists('easel_auto_excerpt_more')) {
	function easel_auto_excerpt_more( $more ) {
		return __( '[&hellip;]', 'easel' ) . ' <a class="more-link" href="'. get_permalink() . '">' . __( '&darr; Read the rest of this entry...', 'easel' ) . '</a>';
	}
}

function easel_close_up_shop() {
	@mysql_close();
}

if (!function_exists('easel_is_layout')) {
	function easel_is_layout($choices) {
		$choices = explode(",", $choices);
		$easel_options = easel_load_options();
		if (isset($easel_options['layout']) && !get_theme_mod('easel-customize-select-layout', false)) {
			if (in_array($easel_options['layout'], $choices)) return true;
		} elseif (in_array(get_theme_mod('easel-customize-select-layout', '3c'), $choices)) return true;
		return false;
	}
}

function easel_is_bbpress() {
	if (function_exists('bbp_is_single_forum') &&
			(bbp_is_forum()
				|| bbp_is_forum_archive()
				|| bbp_is_topic_archive()
				|| bbp_is_single_forum() 
				|| bbp_is_single_topic()
				|| bbp_is_topic()
				|| bbp_is_topic_edit()
				|| bbp_is_topic_merge()
				|| bbp_is_topic_split()
				|| bbp_is_single_reply()
				|| bbp_is_reply_edit()
				|| bbp_is_reply_edit()
				|| bbp_is_single_view()
				|| bbp_is_single_user_edit()
				|| bbp_is_single_user()
				|| bbp_is_user_home()
				|| bbp_is_subscriptions()
				|| bbp_is_favorites()
				|| bbp_is_topics_created()))
		return true;
	return false;
}

function easel_sidebars_disabled() {
	global $wp_query, $post;
	if (!empty($post) && (is_single() || is_page()) && !is_404()) {
		$sidebars_disabled = get_post_meta($post->ID, 'disable-sidebars', true);
		if ($sidebars_disabled) return true;
	}
//		if (easel_is_bbpress()) return true;
	return false;
}

global $content_width;
if (!isset($content_width)) {
	$content_width = easel_themeinfo('content_width');
	if (!$content_width) $content_width = 500;
}

if (!function_exists('easel_display_social_icons')) {
	function easel_display_social_icons() {
		$twitter = easel_themeinfo('menubar_social_twitter');
		$facebook = easel_themeinfo('menubar_social_facebook');
		$googleplus = easel_themeinfo('menubar_social_googleplus');
		$linkedin = easel_themeinfo('menubar_social_linkedin');
		$pinterest = easel_themeinfo('menubar_social_pinterest');
		$youtube = easel_themeinfo('menubar_social_youtube');
		$flickr = easel_themeinfo('menubar_social_flickr');
		$tumblr = easel_themeinfo('menubar_social_tumblr');
		$deviantart = easel_themeinfo('menubar_social_deviantart');
		$myspace = easel_themeinfo('menubar_social_myspace');
		$email = easel_themeinfo('menubar_social_email');
		$output = '<div class="menunav-social-wrapper">';
		if (!empty($deviantart)) $output .= '<a href="'.$deviantart.'" target="_blank" title="'.__( 'my DeviantART', 'easel' ).'" class="menunav-social menunav-deviantart">'.__( 'DeviantART', 'easel' ).'</a>'."\r\n";
		if (!empty($tumblr)) $output .= '<a href="'.$tumblr.'" target="_blank" title="'.__( 'Examine my Tumblr', 'easel' ).'" class="menunav-social menunav-tumblr">'.__( 'Tumblr', 'easel' ).'</a>'."\r\n";
		if (!empty($facebook)) $output .= '<a href="'.$facebook.'" target="_blank" title="'.__( 'Friend on Facebook', 'easel' ).'" class="menunav-social menunav-facebook">'.__( 'Facebook', 'easel' ).'</a>'."\r\n";
		if (!empty($myspace)) $output .= '<a href="'.$myspace.'" target="_blank" title="'.__( 'Make use of MySpace', 'easel' ).'" class="menunav-social menunav-myspace">'.__( 'MySpace', 'easel' ).'</a>'."\r\n";
		if (!empty($linkedin)) $output .= '<a href="'.$linkedin.'" target="_blank" title="'.__( 'Look at my LinkedIn', 'easel' ).'" class="menunav-social menunav-linkedin">'.__( 'LinkedIn', 'easel' ).'</a>'."\r\n";
		if (!empty($twitter)) $output .= '<a href="'.$twitter.'" target="_blank" title="'.__( 'Follow me on Twitter', 'easel' ).'" class="menunav-social menunav-twitter">'.__( 'Twitter', 'easel' ).'</a>'."\r\n";
		if (!empty($flickr)) $output .= '<a href="'.$flickr.'" target="_blank" title="'.__( 'Gaze at my Flickr', 'easel' ).'" class="menunav-social menunav-flickr">'.__( 'Flickr', 'easel' ).'</a>'."\r\n";		
		if (!empty($email)) $output .= '<a href="'.$email.'" target="_blank" title="'.__( 'Email me', 'easel' ).'" class="menunav-social menunav-email">'.__( 'Email', 'easel' ).'</a>'."\r\n";
		if (!empty($googleplus)) $output .= '<a href="'.$googleplus.'" target="_blank" title="'.__( 'Circle me on Google+', 'easel' ).'" class="menunav-social menunav-googleplus">'.__( 'Google+', 'easel' ).'</a>'."\r\n";
		if (!empty($pinterest)) $output .= '<a href="'.$pinterest.'" target="_blank" title="'.__( 'Peruse my Pinterests', 'easel' ).'" class="menunav-social menunav-pinterest">'.__( 'pinterest', 'easel' ).'</a>'."\r\n";
		if (!empty($youtube)) $output .= '<a href="'.$youtube.'" target="_blank" title="'.__( 'My Channel on YouTube', 'easel' ).'" class="menunav-social menunav-youtube">'.__( 'YouTube', 'easel' ).'</a>'."\r\n";
		if (easel_themeinfo('enable_rss_in_menubar')) $output .= '<a href="'.get_bloginfo('rss2_url').'" target="_blank" title="'.__( 'RSS Feed', 'easel' ).'" class="menunav-social menunav-rss2">'.__( 'RSS', 'easel' ).'</a>'."\r\n";
		$output .= '<div class="clear"></div>';
		$output .= '</div>'."\r\n";
		echo $output;
	}
}

/**
 * This is function ceo_clean_filename
 *
 * @param string $filename the BASE filename
 * @return string returns the rawurlencoded filename with the %2F put back to /
 */
function easel_clean_filename($filename) {
	return str_replace("%2F", "/", rawurlencode($filename));
}

function easel_infinite_scroll_loop() {
	while (have_posts()) : the_post();
		easel_display_post();
	endwhile;
}

/**
 * function load default settings
 */
function easel_load_options() {

	$easel_options = get_option('easel-options');
	if (empty($easel_options)) {
		
		foreach (array(
				// General
				'home_post_count' => '5',
				'disable_blog_on_homepage' => false,
//				'add_pw_async_code_to_head' => false,
				'over-blog-sidebar-all-posts' => false,
				// WordPress Content Width that sets video and image size within posts
				'content_width' => 500,
				'content_width_disabled_sidebars' => 700,
				// Pages
				'disable_page_titles' => false,
				// Posts
				'disable_post_titles' => false,
				'enable_post_calendar' => false,
				'enable_post_author_gravatar' => false,
				'enable_avatar_trick' => true,
				'disable_tags_in_posts' => false,
				'disable_categories_in_posts' => false,
				'disable_author_info_in_posts' => false,
				'disable_date_info_in_posts' => false,
				'disable_posted_at_time_in_posts' => false,
				'enable_last_modified_in_posts' => false,
				'moods_directory' => 'none',
				// Comments
				'disable_comment_note' => true,
				'disable_comment_javascript' => false,
				'enable_comments_on_homepage' => false,
				'avatar_directory' => 'none',
				// Pagination
				'enable_numbered_pagination' => true,
				// Footer
				'disable_footer_text' => false,
				'disable_scroll_to_top' => false,
				'copyright_name' => '',
				'copyright_url' => '',
				// RSS
				'enable_post_thumbnail_rss' => true,
				// Archive & Search
				'display_archive_as_links' => false,
				'excerpt_or_content_in_archive' => 'excerpt',
				'archive_display_order' => 'DESC',
				// Menubar
				'disable_default_menubar' => false,
				'enable_search_in_menubar' => false,
				'enable_rss_in_menubar' => true,
				'disable_jquery_menu_code' => false,
				'enable_breadcrumbs' => false,
				// Menubar - Social Icons
				'menubar_social_icons' => false,
				'menubar_social_twitter' => '',
				'menubar_social_facebook' => '',
				'menubar_social_googleplus' => '',
				'menubar_social_linkedin' => '',
				'menubar_social_pinterest' => '',
				'menubar_social_youtube' => '',
				'menubar_social_flickr' => '',
				'menubar_social_tumblr' => '',
				'menubar_social_deviantart' => '',
				'menubar_social_myspace' => '',
				'menubar_social_email' => '',
				// Debug
				'enable_debug_footer_code' => false,
				'force_active_connection_close' => false,
				// Jetpack
				'enable_jetpack_infinite_scrolling' => false
		) as $field => $value) {
			$easel_options[$field] = $value;
		}
//		update_option('easel-options', $easel_options);
//		Cannot save to the database unless you click the save button in options
	}
	return $easel_options;
}

function easel_themeinfo($whichinfo = null) {
	global $easel_themeinfo;
	if (empty($easel_themeinfo) || $whichinfo == 'reset') {
		$easel_themeinfo = array();
		$easel_options = easel_load_options();
		$easel_addinfo = array(
			'version' => '4.3 Beta',
			'excerpt_length' => '40'
		);
		$easel_themeinfo = array_merge($easel_themeinfo, $easel_addinfo);
		$easel_themeinfo = array_merge($easel_themeinfo, $easel_options);
		if (!isset($easel_themeinfo['layout']) || empty($easel_themeinfo['layout']) || ($easel_themeinfo['layout'] == 'standard')) $easel_themeinfo['layout'] = '3c';
	}
	if ($whichinfo && $whichinfo !== 'reset')
		if (isset($easel_themeinfo[$whichinfo])) 
			return $easel_themeinfo[$whichinfo];
		else
			return false;
	return $easel_themeinfo;
}

// Dashboard Menu Options - Only run in the wp-admin area
if (is_admin()) {
	@require_once(get_template_directory().'/options.php');
	/* translators: theme discription for wp-admin */
	$bogus_translation = __( 'Publish a WebComic with the Easel theme and the Comic Easel plugin.', 'easel' );
}
