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
?>
