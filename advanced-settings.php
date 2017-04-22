<!-- Advanced CSS Settings Section -->
<div class="postbox">
    <a name="step1"></a>
    <h2 class="hndle ui-sortable-handle" style="cursor:default;"><span><?php _e('Advanced CSS Settings', 'font-organizer'); ?></span></h2>
    <div class="inside">
        <form method="post" action="options.php#1">
        <?php
            // This prints out all hidden setting fields
            settings_fields( 'fo_advanced_options' );
            fo_do_settings_section( 'font-setting-admin', 'advanced_css_options' );
            submit_button();
        ?>
        </form>
    </div>
</div>

<!-- General Settings Section -->
<div class="postbox">
    <a name="step1"></a>
    <h2 class="hndle ui-sortable-handle" style="cursor:default;"><span><?php _e('System Settings', 'font-organizer'); ?></span></h2>
    <div class="inside">
        <form method="post" action="options.php#1">
        <?php
            // This prints out all hidden setting fields
            settings_fields( 'fo_general_options' );
            fo_do_settings_section( 'font-setting-admin', 'setting_general' );
            submit_button();
        ?>
        </form>
    </div>
</div>
