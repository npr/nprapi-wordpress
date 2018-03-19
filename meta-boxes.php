<?php
/**
 * File containing meta box callback functions
 */

/**
 * Output the NPR Story API publishing options metabox for the edit page admin interface
 *
 * @param WP_Post $post the WordPress post object.
 */
function nprstory_publish_meta_box( $post ) {
	$helper_text = __( 'Push this story to NPR:', 'nprapi' );
	$is_disabled = ( 'post' !== $post->post_status );
	if ( $is_disabled ) {
		$helper_text = __( 'Publish this story in order to push it to NPR.', 'nprapi' );
	}
	$attrs = array( 'id' => 'ds-npr-update-push' );
	if (  $is_disabled ) {
		$attrs['disabled'] = 'disabled';
	}

	?>
	<div id="ds-npr-publish-actions">
		<p class="helper-text"><?php echo wp_kses_post( $helper_text ); ?></p>
		<?php
			submit_button(
				__( 'Push to NPR', 'nprapi' ),
				'large',
				'ds_npr_update_push',
				false,
				$attrs
			);
		?>
	</div>
<?php
}
