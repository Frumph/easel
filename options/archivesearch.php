<div id="easel-archivesearch">
	<form method="post" id="myForm-general" enctype="multipart/form-data" action="?page=easel-options">
	<?php wp_nonce_field('update-options') ?>

		<div class="easel-options">
		
			<table class="widefat">
				<thead>
					<tr>
						<th colspan="3"><?php _e( 'Archive & Search', 'easel' ); ?></th>
					</tr>
				</thead>
				<tr class="alternate">
					<th scope="row"><label for="display_archive_as_links"><?php _e( 'Display archive results as a list of links?', 'easel' ); ?></label></th>
					<td>
						<input id="display_archive_as_links" name="display_archive_as_links" type="checkbox" value="1" <?php checked(true, $easel_options['display_archive_as_links']); ?> />
					</td>
					<td>
						<?php _e('Enabling this will make the archive pages by date/category/term display as a list of links instead of full posts.', 'easel' ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row" colspan="2">
						<label for="excerpt_or_content_in_archive"><?php _e( 'Excerpt or Full Content in archive and search?', 'easel' ); ?></label>
						<select name="excerpt_or_content_in_archive" id="excerpt_or_content_in_archive">
							<option class="level-0" value="excerpt" <?php selected($easel_options['excerpt_or_content_in_archive'], 'excerpt'); ?>><?php _e( 'Excerpt', 'easel' ); ?></option>
							<option class="level-0" value="content" <?php selected($easel_options['excerpt_or_content_in_archive'], 'content'); ?>><?php _e( 'Full Content', 'easel' ); ?></option>
						</select>
					</th>
					<td>
						<?php _e( 'If Display archives results as list is disabled, decide how much is seen in the archive display.', 'easel' ); ?>
					</td>
				</tr>
				<tr class="alternate">
					<th scope="row" colspan="2">
						<label for="archive_display_order"><?php _e( 'Archive Display Order', 'easel' ); ?></label>
						<select name="archive_display_order" id="archive_display_order">
							<option class="level-0" value="asc" <?php if ($easel_options['archive_display_order'] == "asc") { ?>selected="selected"<?php } ?>><?php _e( 'Oldest to Newest &mdash; Ascending', 'easel' ); ?></option>
							<option class="level-0" value="desc" <?php if ($easel_options['archive_display_order'] == "desc") { ?>selected="selected"<?php } ?>><?php _e( 'Newest to Oldest &mdash; Descending', 'easel' ); ?></option>
						</select>
					</th>
					<td>
						<?php _e( 'Sets the display order of your archives. Newest to Oldest will display your posts starting with the most recent. Oldest to Newest will start with the first entry in the category, tag, or date range (e.g. Selecting May 20XX will start with May 1, not May 31st.)', 'easel' ); ?>
					</td>
				</tr>
			</table>
			<br />
				<table class="widefat">
				<thead>
					<tr>
						<th colspan="3"><?php _e( 'Navigation', 'easel' ); ?></th>
					</tr>
				</thead>
				<tr class="alternate">
					<th scope="row"><label for="enable_numbered_pagination"><?php _e( 'Enable numbered pagination?', 'easel' ); ?></label></th>
					<td>
						<input id="enable_numbered_pagination" name="enable_numbered_pagination" type="checkbox" value="1" <?php checked(true, $easel_options['enable_numbered_pagination']); ?> />
					</td>
					<td>
						<?php _e( 'Previous Entries and Next Entries buttons are replaced by a bar of numbered pages. Numbered pagination appears on the Home page, the author(s) page, the blog template, and comments/single when there are more then the set number of comments per page. Uses the same styling as the Menubar.', 'easel' ); ?>
					</td>
				</tr>
			</table>
			<br />
		</div>

		<div class="easel-options-save">
			<div class="easel-major-publishing-actions">
				<div class="easel-publishing-action">
					<input name="easel_save_general" type="submit" class="button-primary" value="<?php _e( 'Save Settings', 'easel' ); ?>" />
					<input type="hidden" name="action" value="easel_save_archivesearch" />
				</div>
				<div class="clear"></div>
			</div>
		</div>

	</form>

</div>
