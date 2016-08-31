<?php

add_action('admin_menu', 'easel_options_setup');

function easel_options_setup() {
	$options_title = __( 'Options', 'easel' );
	$admin_title = __( 'Easel Options', 'easel' );
	$pagehook = add_theme_page($admin_title, $admin_title, 'edit_theme_options', 'easel-options', 'easel_admin_options');
	add_action('admin_head-' . $pagehook, 'easel_admin_page_head');
	add_action('admin_print_scripts-' . $pagehook, 'easel_admin_print_scripts');
	add_action('admin_print_styles-' . $pagehook, 'easel_admin_print_styles');
}

function easel_admin_print_scripts() {
	wp_enqueue_script('utils');
	wp_enqueue_script('jquery');
}

function easel_admin_print_styles() {
	wp_admin_css('css/global');
	wp_admin_css('css/colors');
	wp_admin_css('css/ie');
	wp_enqueue_style('easel-options-style', get_template_directory_uri() . '/options/options.css');
}

function easel_admin_page_head() { ?>
	<!--[if lt ie 8]> <style> div.show { position: static; margin-top: 1px; } #eadmin div.off { height: 22px; } </style> <![endif]-->
<?php }

function easel_admin_options() { ?>
<div class="wrap">
	<div id="eadmin-headericon" style="background: url('<?php echo get_template_directory_uri() ?>/images/easel.png') no-repeat;"></div>
		<h2><?php _e( 'Easel Options', 'easel' ); ?></h2>
		<?php _e( 'Easel is a modular theme that has an abundance of hooks and actions placed in it for additional usability.', 'easel' ); ?><br />
		<?php _e( 'While Easel is an excellent stand-alone theme, it can be enhanced in usability with the associated plugins that have been built to utilize its functionality.', 'easel' ); ?><br />
	<div class="clear"></div>
	<?php
	if (isset($_GET['tab'])) { $tab = wp_filter_nohtml_kses($_GET['tab']); } else { $tab = ''; };

	if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'easel_reset') {
		delete_option('easel-options');
		global $easel_themeinfo; $easel_themeinfo = '';
	?>
		<div id="message" class="updated"><p><strong><?php _e( 'Easel Settings RESET!', 'easel' ); ?></strong></p></div>
	<?php } 
	if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'easel_reset_customize') {
		remove_theme_mod('easel-customize');
		delete_option('theme_mods_easel');
		global $easel_themeinfo; $easel_themeinfo = '';
	?>
		<div id="message" class="updated"><p><strong><?php _e( 'Easel Customizer Colors RESET!', 'easel' ); ?></strong></p></div>
	<?php }
	if (empty($easel_options)) { 
		easel_themeinfo('reset');
	}
	$easel_options = get_option('easel-options');
	if ( isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'update-options') ) {
		
		if ($_REQUEST['action'] == 'easel_save_general') {

			foreach (array(
			'disable_scroll_to_top',  // general
			'enable_post_thumbnail_rss',  // general
			'disable_footer_text', // general
			'disable_blog_on_homepage', // general
//			'add_pw_async_code_to_head',  // general
			'over-blog-sidebar-all-posts'  // general
				) as $key) {
					if (!isset($_REQUEST[$key])) $_REQUEST[$key] = 0;
					$easel_options[$key] = (bool)( $_REQUEST[$key] == 1 ? true : false );
			}

			foreach (array(
				'home_post_count',  // general
				'copyright_name',  // general
				'copyright_url'  // general
						) as $key) {
							if (isset($_REQUEST[$key])) 
								$easel_options[$key] = wp_filter_nohtml_kses($_REQUEST[$key]);
			}
			$tab = 'debug';
			update_option('easel-options', $easel_options);
		}

		if ($_REQUEST['action'] == 'easel_save_menubar') {

			foreach (array(
			'disable_jquery_menu_code',
			'disable_default_menubar',
			'enable_search_in_menubar',
			'enable_rss_in_menubar',
			'menubar_social_icons',
			'enable_breadcrumbs'			
				) as $key) {
					if (!isset($_REQUEST[$key])) $_REQUEST[$key] = 0;
					$easel_options[$key] = (bool)( $_REQUEST[$key] == 1 ? true : false );
			}

			foreach (array(
				'menubar_social_twitter',
				'menubar_social_facebook',
				'menubar_social_googleplus',
				'menubar_social_linkedin',
				'menubar_social_pinterest',
				'menubar_social_youtube',
				'menubar_social_flickr',
				'menubar_social_tumblr',
				'menubar_social_deviantart',
				'menubar_social_myspace',
				'menubar_social_email'
						) as $key) {
							if (isset($_REQUEST[$key]) && !empty($_REQUEST[$key])) {
								$easel_options[$key] = esc_url($_REQUEST[$key]);
							} else {
								// set to empty if it's not set
								$easel_options[$key] = '';
							}
			}
			$tab = 'menubar';
			update_option('easel-options', $easel_options);
		}
		
		if ($_REQUEST['action'] == 'easel_save_postspages') {

			foreach (array(
			'enable_avatar_trick', // postspages
			'disable_page_titles',  // postspages
			'disable_post_titles',  // postspages
			'enable_post_calendar',  // postspages
			'enable_post_author_gravatar',  // postspages
			'disable_categories_in_posts',  // postspages
			'disable_tags_in_posts',  // postspages
			'disable_author_info_in_posts',  // postspages
			'disable_date_info_in_posts',  // postspages
			'enable_last_modified_in_posts',  // postspages
			'disable_posted_at_time_in_posts', // postspages
				) as $key) {
					if (!isset($_REQUEST[$key])) $_REQUEST[$key] = 0;
					$easel_options[$key] = (bool)( $_REQUEST[$key] == 1 ? true : false );
			}

			foreach (array(
				'moods_directory',  // postspages
				'content_width',  // postspages
				'content_width_disabled_sidebars'  // postspages
						) as $key) {
							if (isset($_REQUEST[$key])) 
								$easel_options[$key] = wp_filter_nohtml_kses($_REQUEST[$key]);
			}
			$tab = 'postspages';
			update_option('easel-options', $easel_options);
		}
		
		if ($_REQUEST['action'] == 'easel_save_comments') {

			foreach (array(
			'disable_comment_note',  // comments
			'disable_comment_javascript',  // commments
			'enable_comments_on_homepage', // comments
				) as $key) {
					if (!isset($_REQUEST[$key])) $_REQUEST[$key] = 0;
					$easel_options[$key] = (bool)( $_REQUEST[$key] == 1 ? true : false );
			}

			foreach (array(
				'avatar_directory'  // comments 
						) as $key) {
							if (isset($_REQUEST[$key])) 
								$easel_options[$key] = wp_filter_nohtml_kses($_REQUEST[$key]);
			}
			$tab = 'comments';
			update_option('easel-options', $easel_options);
		}
		
		if ($_REQUEST['action'] == 'easel_save_archivesearch') {

			foreach (array(
			'display_archive_as_links',  // archivesearch
			'enable_numbered_pagination'  // postspages
				) as $key) {
					if (!isset($_REQUEST[$key])) $_REQUEST[$key] = 0;
					$easel_options[$key] = (bool)( $_REQUEST[$key] == 1 ? true : false );
			}

			foreach (array(
				'archive_display_order',  // archivesearch
				'excerpt_or_content_in_archive'  // archivesearch
						) as $key) {
							if (isset($_REQUEST[$key])) 
								$easel_options[$key] = wp_filter_nohtml_kses($_REQUEST[$key]);
			}
			$tab = 'archivesearch';
			update_option('easel-options', $easel_options);
		}
				
		if ($_REQUEST['action'] == 'easel_save_debug') {
			foreach (array(
				'enable_debug_footer_code',
				'force_active_connection_close'
			) as $key) {
				if (!isset($_REQUEST[$key])) $_REQUEST[$key] = 0;
				$easel_options[$key] = (bool)( $_REQUEST[$key] == 1 ? true : false );
			}
			$tab = 'debug';
			update_option('easel-options', $easel_options);
		}
		
		if ($tab) { ?>
			<div id="message" class="updated"><p><strong><?php _e( 'Easel Settings SAVED!', 'easel' ); ?></strong></p></div>
			<script>function hidemessage() { document.getElementById('message').style.display = 'none'; }</script>
		<?php }
	} 
	$version = easel_themeinfo('version');
	$easel_options = get_option('easel_load_options');
	?>
	<div id="poststuff" class="metabox-holder">
		<div id="eadmin">
		  <?php
		  	$tab_info = array(
				'splash' => __( 'Introduction', 'easel' ),
		  		'general' => __( 'General', 'easel' ),
		  		'menubar' => __( 'Menubar', 'easel' ),
		  		'postspages' => __( 'Posts & Pages', 'easel' ),
				'comments' => __( 'Comments', 'easel' ),
				'archivesearch' => __( 'Archive & Search', 'easel' ),
				'debug' => __( 'Debug', 'easel' )
		  	);

		  	if (empty($tab)) { $tab = 'splash'; }

		  	foreach($tab_info as $tab_id => $label) { ?>
		  		<div id="easel-tab-<?php echo $tab_id ?>" class="easel-tab <?php echo ($tab == $tab_id) ? 'on' : 'off'; ?>"><span><?php echo $label; ?></span></div>
		  	<?php }
		  ?>
		</div>

		<div id="easel-options-pages">
		  <?php	foreach (glob(get_template_directory() . '/options/*.php') as $file) { include($file); } ?>
		</div>
	</div>
	<script type="text/javascript">
		(function($) {
			var showPage = function(which) {
				$('#easel-options-pages > div').each(function(i) {
					$(this)[(this.id == 'easel-' + which) ? 'show' : 'hide']();
				});
			};

			$('.easel-tab').click(function() {
				$('#message').animate({height:"0", opacity:0, margin: 0}, 100, 'swing', function() { $(this).remove() });

				showPage(this.id.replace('easel-tab-', ''));
				var myThis = this;
				$('.easel-tab').each(function() {
					var isSame = (this == myThis);
					$(this).toggleClass('on', isSame).toggleClass('off', !isSame);
				});
				return false;
			});

			showPage('<?php echo esc_js($tab); ?>');
		}(jQuery));
	</script>
</div>
	<div class="eadmin-footer">
		<div id="easel-version-title"><a href="http://frumph.net/">Easel <?php echo easel_themeinfo('version'); ?></a></div>
		<br />
		<?php _e( 'Created, Developed and maintained by', 'easel' ); ?> <a href="http://frumph.net/">Philip M. Hofer</a> <small>(<a href="http://frumph.net/">Frumph</a>)</small><br />
		<?php _e( 'If you like the Easel theme, please donate. It will help in developing new features and versions.', 'easel' ); ?>
		<table style="margin:0 auto;">
			<tr>
				<td style="width:200px;">
					<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
						<input type="hidden" name="cmd" value="_s-xclick" />
						<input type="hidden" name="hosted_button_id" value="46RNWXBE7467Q" />
						<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" name="submit" alt="PayPal - The safer, easier way to pay online!" />
						<img alt="" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" />
					</form>
				</td>
				<td style="width:200px;">
					<form method="post" id="myForm" name="template" enctype="multipart/form-data" action="">
						<?php wp_nonce_field('update-options') ?>
						<input name="easel_reset" type="submit" class="button" value="<?php _e( 'Reset All Settings', 'easel' ); ?>" />
						<input type="hidden" name="action" value="easel_reset" />
					</form>
				</td>
				<td style="width:200px;">
					<form method="post" id="myForm" name="template" enctype="multipart/form-data" action="">
						<?php wp_nonce_field('update-options') ?>
						<input name="easel_reset_customize" type="submit" class="button" value="<?php _e( 'Reset Customizer Colors', 'easel' ); ?>" />
						<input type="hidden" name="action" value="easel_reset_customize" />
					</form>
				</td>
			</tr>
		</table>
	</div>

<?php
}
