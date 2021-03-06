<div id="easel-general">
	<form method="post" id="myForm-general" enctype="multipart/form-data" action="?page=easel-options">
		<?php wp_nonce_field('update-options') ?>

		<div class="easel-options">

			<table class="widefat">
				<thead>
					<tr>
						<th colspan="3">
							<?php _e( 'General', 'easel' ); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<th scope="row">
							<label for="home_post_count">
								<?php _e( 'How many blog posts would you like to display on the home page?', 'easel' ); ?>
							</label>
						</th>
						<td>
							<input type="text" size="2" name="home_post_count" id="home_post_count" value="<?php echo $easel_options['home_post_count']; ?>" />
						</td>
						<td>
							<?php _e( 'How many blog posts you would like displayed on the index page at one time. This is different then the one in the wp-admin &#10132; settings, the one in the settings will control how many show up on search and archive pages.', 'easel' ); ?>
						</td>
					</tr>
					<tr class="alternate">
						<th scope="row">
							<label for="disable_blog_on_homepage">
								<?php _e( 'Disable Blog Loop?', 'easel' ); ?>
							</label>
						</th>
						<td>
							<input id="disable_blog_on_homepage" name="disable_blog_on_homepage" type="checkbox" value="1" <?php checked(true, $easel_options['disable_blog_on_homepage']); ?> />
						</td>
						<td>
							<?php _e( 'Enabling this option, will DISABLE the blog from appearing on the home page AND any pages set as the blog posts page in the settings &#10132; reading.', 'easel' ); ?>
						</td>
					</tr>
					<tr class="alternate">
						<th scope="row">
							<label for="over-blog-sidebar-all-posts">
								<?php _e( 'Allow the sidebar over-blog to appear on all posts?', 'easel' ); ?>
							</label>
						</th>
						<td>
							<input id="over-blog-sidebar-all-posts" name="over-blog-sidebar-all-posts" type="checkbox" value="1" <?php checked(true, $easel_options['over-blog-sidebar-all-posts']); ?> />
						</td>
						<td>
							<?php _e( 'Sidebar Over-Blog appears on all posts not just the front page/blog loop when enabled.', 'easel' ); ?>
						</td>
					</tr>
				</tbody>
			</table>
			<br />
			
			<table class="widefat">
				<thead>
					<tr>
						<th colspan="3">
							<?php _e( 'Footer', 'easel' ); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<tr class="alternate">
						<th scope="row">
							<label for="disable_footer_text">
								<?php _e( 'Disable the default text in the footer?', 'easel' ); ?>
							</label>
						</th>
						<td>
							<input id="disable_footer_text" name="disable_footer_text" type="checkbox" value="1" <?php checked(true, $easel_options['disable_footer_text']); ?> />
						</td>
						<td>
							<?php _e( 'Default text in the footer will not display. Enable this if you do not want any text in the footer. If you wish to add you own custom content, you may do so via Appearance &#10132; Widgets &#10132; Footer.', 'easel' ); ?>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="disable_scroll_to_top">
								<?php _e( 'Disable the scroll to top link in the footer?', 'easel' ); ?>
							</label>
						</th>
						<td>
							<input id="disable_scroll_to_top" name="disable_scroll_to_top" type="checkbox" value="1" <?php checked(true, $easel_options['disable_scroll_to_top']); ?> />
						</td>
						<td>
							<?php _e( 'When this link is clicked on long pages it will scroll back to the top.', 'easel' ); ?>
						</td>
					</tr>
					<tr class="alternate">
						<th scope="row">
							<label for="copyright_name">
								<?php _e( '&copy; Copyright Name', 'easel' ); ?>
							</label>
							<input type="text" size="20" name="copyright_name" id="copyright_name" value="<?php echo stripcslashes($easel_options['copyright_name']); ?>" />
							<br />
						</th>
						<td colspan="2">
							<?php _e( 'Set the name to which this site is &copy; Copyright to. Leave blank to have the site name.', 'easel' ); ?>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="copyright_url">
								<?php _e( '&copy; Copyright Holder URL', 'easel' ); ?>
							</label>
							<input type="text" size="30" name="copyright_url" id="copyright_url" value="<?php echo stripcslashes($easel_options['copyright_url']); ?>" />
							<br />
						</th>
						<td colspan="2">
							<?php _e( 'Set the URL to the owner of the &copy; Copyright if different then this website. Leave blank for sites link.', 'easel' ); ?>
						</td>
					</tr>
				</tbody>
			</table>
			<br />
			
			<table class="widefat">
				<thead>
					<tr>
						<th colspan="3">
							<?php _e( 'RSS', 'easel' ); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<tr class="alternate">
						<th scope="row">
							<label for="enable_post_thumbnail_rss">
								<?php _e( 'Enable the post thumbnails in the RSS feed?', 'easel' ); ?>
							</label>
						</th>
						<td>
							<input id="enable_post_thumbnail_rss" name="enable_post_thumbnail_rss" type="checkbox" value="1" <?php checked(true, $easel_options['enable_post_thumbnail_rss']); ?> />
						</td>
						<td>
							<?php _e( 'If enabled will show the post thumbnail of the post in the RSS feed.', 'easel' ); ?>
						</td>
					</tr>
				</tbody>
			</table>
			<br />
			
		</div>

		<div class="easel-options-save">
			<div class="easel-major-publishing-actions">
				<div class="easel-publishing-action">
					<input name="easel_save_general" type="submit" class="button-primary" value="<?php _e( 'Save Settings', 'easel' ); ?>" />
					<input type="hidden" name="action" value="easel_save_general" />
				</div>
				<div class="clear"></div>
			</div>
		</div>

	</form>
</div>