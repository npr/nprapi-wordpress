<?php

function nprstory_publish_meta_box($post) {
	$helper_text = 'Push this story to NPR:';
	$is_disabled = ($post->post_status != 'publish');
	if ($is_disabled) {
		$helper_text = 'Publish this story in order to push it to NPR.';
	}
	$attrs = array('id' => 'ds-npr-update-push');
	if ($is_disabled)
		$attrs['disabled'] = 'disabled';
?>
	<div id="ds-npr-publish-actions">
		<p class="helper-text"><?php echo $helper_text; ?></p>
<?php
	submit_button( 'Push to NPR', 'large', 'ds_npr_update_push', false, $attrs );
?>
	</div>
<?php
}
