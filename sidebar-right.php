<div id="sidebar-right">
	<div class="sidebar">
	<?php
		do_action('easel-sidebar-right');
		if ( !dynamic_sidebar('sidebar-right-sidebar') ) { ?>
		<div class="sidebar-no-widgets">
			<?php _e( 'There are currently no widgets assigned to the right-sidebar, place some!', 'easel' ); ?>
			<br />
			<br />
			<?php _e( 'Once you add widgets to this sidebar, this default information will go away.', 'easel' ); ?>
			<br />
			<br />
			<?php _e( 'Widgets can be added by going to your dashboard (wp-admin) &#10132; Appearance &#10132; Widgets, drag a widget you want to see into one of the appropriate sidebars.', 'easel' ); ?>
		    <br />
		 </div>
		<?php } ?>
	</div>
</div>
