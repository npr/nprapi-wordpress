<?php
/**
 * Build the options page for the bass NPR API settings
 */
function ds_npr_api_options_page() {
?>
    <div>
        <h2>NPR API settings</h2>
        <form action="options.php" method="post">
            <?php settings_fields( 'ds_npr_api' ); ?>
            <?php do_settings_sections( 'ds_npr_api' ); ?>
            <input name="Submit" type="submit" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
        </form>
    </div>
<?php
}

/**
 * Build the options page for multiple automatic retrieval
 */
function ds_npr_api_get_multi_options_page() {
?>
	<div>
       <div><p>Create an NPR API query (see the <a target="_" href="http://www.npr.org/api/queryGenerator.php">NPR API query generator</a>). Enter your queries into one of the rows below to have stories on that query automatically publish to your site. Please note, you do not need to include your API key to the query.  </div>
        <form action="options.php" method="post">
            <?php settings_fields( 'ds_npr_api_get_multi_settings' ); ?>
            <?php do_settings_sections( 'ds_npr_api_get_multi_settings' ); ?>
            <input name="Submit" type="submit" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
        </form>
    </div>
<?php
}

/**
 * Build the options page for mapping fields for the NPRML fields pushed with a post to local custom meta fields.
 */
function ds_npr_add_field_mapping_page() {
	?>
	<form action="options.php" method="post">
	<div>Use this page to map your custom WordPress Meta fields to fields sent the NPR API.  <P>Clicking the <strong>Use Custom Settings</strong> checkbox will enable these mappings.  If you wish to use the default mapping for a field, select &mdash; default &mdash; and we will use the obvious WordPress field. </div>
	<p>
	<div>Select for the Meta fields for the <strong> <?php echo nprstory_get_push_post_type(); ?></strong> post type</div>
	<?php

	settings_fields( 'ds_npr_api_push_mapping' );
  //do_settings_section('ds_npr_api_push_mapping');
  do_settings_sections( 'ds_npr_api_push_mapping' );
	?>
  <input name="Submit" type="submit" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
  </form>
  <?php
}
