<?php

	
  function ds_npr_get_stories() {
        global $is_IE;
        $api_key =  get_option('ds_npr_api_key');
?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>Get NPR Stories</h2>
            <?php if ( ! $api_key ) : ?>
                <div class="error">
                    <p>You don't currently have an API key set.  <a href="<?php menu_page_url( 'npr_api' ); ?>">Set your API key here.</a></p>
                </div>
            <?php endif; 
            if ( ( isset( $_POST ) and isset( $_POST[ 'story_id' ] ) ) || ( isset( $_GET['create_draft'] ) && isset( $_GET['story_id'] ) ) ): ?>
                <div class="updated">
                    <p><?php //echo "getting " . $_POST['story_id']; ?></p>
                </div>
            <?php endif; ?>

            <div style="float: left;">
                <form action="" method="POST">
                    Enter an NPR Story ID or URL: <input type="text" name="story_id" value="" />
                    <input type="submit" name='createDaft' value="Create Draft" />
                    <input type="submit" name='publishNow' value="Publish Now" />
                </form>
            </div>

       </div>
        <?php
    }
