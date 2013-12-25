<?php

$custom_header_args = array(
			'flex-width' => true,
			'width' => 980,
			'flex-height' => true,
			'height' => 120,
			'wp-head-callback' => 'easel_header_style',
			'admin-head-callback' => 'easel_admin_header_style',
			'admin-preview-callback' => 'easel_admin_header_style'	
	);

add_theme_support( 'custom-header', $custom_header_args );

function easel_admin_header_style() { ?>
<style type="text/css">
	#headimg { width: <?php echo get_custom_header()->width; ?>px; height: <?php echo get_custom_header()->height; ?>px; background: url(<?php header_image(); ?>) top center no-repeat; }
	#headimg h1, #headimg .description { display: none; }
</style>
	<?php
}

function easel_header_style() { 
	if (get_header_image()) { ?>
<style type="text/css">
	#header { width: <?php echo get_custom_header()->width; ?>px; height: <?php echo get_custom_header()->height; ?>px; background: url('<?php header_image(); ?>') top center no-repeat; overflow: hidden; }
<?php if (get_theme_mod('easel-customize-checkbox-header-hotspot', false)) { ?>
	#header h1 { padding: 0; }
	#header h1 a { display: block; width: <?php echo get_custom_header()->width; ?>px; height: <?php echo get_custom_header()->height; ?>px; text-indent: -9999px; }
	#header .description { display: none; }	
	.header-info, .header-info h1 a { padding: 0; }
<?php } ?>
</style>
	<?php }
}
