<?php

$custom_header_args = array(
			'default-text-color' => 'B00',
			'flex-width' => true,
			'width' => easel_themeinfo('custom_image_header_width'),
			'flex-height' => true,
			'height' => easel_themeinfo('custom_image_header_height'),
			'header-text' => false,
			'wp-head-callback' => 'easel_header_style',
			'admin-head-callback' => 'easel_admin_header_style',
			'admin-preview-callback' => 'easel_admin_header_style'	
	);

add_theme_support( 'custom-header', $custom_header_args );

function easel_admin_header_style() { ?>
<style type="text/css">
	#headimg { width: <?php echo get_custom_header()->width; ?>px; height: <?php echo get_custom_header()->height; ?>px; background: url(<?php header_image(); ?>) top center no-repeat; }
<?php if (!display_header_text()) { ?>
	#headimg h1, #headimg .description { display: none; }
<?php } ?>
</style>
	<?php
}

function easel_header_style() { 
	if (get_header_image()) {
		$textcolor = get_header_textcolor()
?>
<style type="text/css">
	#header { width: <?php echo get_custom_header()->width; ?>px; height: <?php echo get_custom_header()->height; ?>px; background: url('<?php header_image(); ?>') top center no-repeat; overflow: hidden; }
<?php if (get_theme_mod('easel-customize-checkbox-header-hotspot', false)) { ?>
	#header h1 { padding: 0; }
	#header h1 a { display: block; width: <?php echo get_custom_header()->width; ?>px; height: <?php echo get_custom_header()->height; ?>px; text-indent: -9999px; }
	.header-info, .header-info h1 a { padding: 0; }
<?php } elseif (!display_header_text()) { ?>
	#header h1, #header .description { display: none; }
<?php } elseif (!empty($textcolor)) { ?>
	#header h1, #header h1 a { color: #<?php echo $textcolor; ?>; }
<?php } ?>
</style>
	<?php }
}
