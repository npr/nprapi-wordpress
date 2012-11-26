<?php
function ds_npr_api_options_page() {
?>
    <div>
        <h2>NPR API settings</h2>
        <form action="options.php" method="post">
            <?php settings_fields( 'ds_npr_api' ); ?>
            <?php do_settings_sections( 'ds_npr_api' ); ?>
            <input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
        </form>
    </div>
<?php
}


function ds_npr_api_get_multi_options_page() {
?>
	<div>
       <div><p>Create an NPR API query (see the <a target="_" href="http://www.npr.org/api/queryGenerator.php">NPR API query generator</a>). Enter your queries into one of the rows below to have stories on that query automatically publish to your site. Please note, you do not need to include your API key to the query.  </div>
        <form action="options.php" method="post">
            <?php settings_fields( 'ds_npr_api_get_multi_settings' ); ?>
            <?php do_settings_sections( 'ds_npr_api_get_multi_settings' ); ?>
            <input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
        </form>
    </div>
<?php
}
?>