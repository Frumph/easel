<div id="content-wrapper">
	
	<?php do_action('easel-content-area'); ?>
	<?php if (!get_theme_mod('easel-customize-comic-in-column', false)) do_action('comic-area'); ?>
	
	<div id="subcontent-wrapper">
<?php
if (!easel_is_signup() && !easel_sidebars_disabled()) {
	if (easel_is_layout('2cl,3c,3cl,3clgn')) easel_get_sidebar('left');
	if (easel_is_layout('3cl')) easel_get_sidebar('right');
}
if (is_front_page() && !easel_sidebars_disabled()) {
	easel_get_sidebar('home-splash');
}
	?>
		<div id="content-column">
<?php 
if (get_theme_mod('easel-customize-comic-in-column', false)) do_action('comic-area');
if (!easel_is_signup() && !easel_sidebars_disabled()) {
	if (easel_is_layout('3crgn')) easel_get_sidebar('left');
}
			?>
			<div id="content" class="narrowcolumn">
				<?php do_action('easel-narrowcolumn-area'); ?>
<?php
if (!easel_sidebars_disabled()) {
	if (!is_front_page() && !easel_themeinfo('over-blog-sidebar-all-posts')) return;
	easel_get_sidebar('over-blog');
}
				?>
				<?php do_action('comic-blog-area'); ?>
