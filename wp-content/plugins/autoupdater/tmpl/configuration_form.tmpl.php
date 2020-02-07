<?php function_exists('add_action') or die; ?>
<?php /*if (!$autoupdater_enabled) : ?>
    <button type="button" id="autoupdater_show_settings" class="autoupdater-hide-after-enable button button-primary">
        <?php _e('Settings') ?>
    </button>
<?php endif;*/ ?>
<form action="<?php echo admin_url('admin-ajax.php?action=autoupdater_save'); ?>"
      id="autoupdater_configuration_form" method="post" target="_blank"
    <?php if (!$autoupdater_enabled) echo 'style="display: none; "'; ?>
      class="autoupdater-show-after-enable">
    <?php if ($autoupdater_enabled) : ?>
        <div>
            <h2><?php _e('Automatic updates settings', 'autoupdater') ?></h2>
            <?php include dirname(__FILE__) . '/configuration_form_autoupdater_fields.tmpl.php'; ?>
        </div>
        <div class="autoupdater-toggle">
            <div class="autoupdater-toggle-indicator-wrapper">
                <a href="#" class="autoupdater-toggle-button">
                    <h2><?php _e('Advanced settings', 'autoupdater') ?></h2>
                    <span class="autoupdater-toggle-indicator autoupdater-toggle-indicator-arrow autoupdate-open" style="display: none;"></span>
                    <span class="autoupdater-toggle-indicator autoupdater-toggle-indicator-arrow autoupdate-close"></span>
                </a>
                <div class="autoupdater-clearfix"></div>
            </div>
            <div class="autoupdater-toggle-content" <?php if ($autoupdater_enabled) echo 'style="display: none; "'; ?>>
                <?php include dirname(__FILE__) . '/configuration_form_advanced_fields.tmpl.php'; ?>
            </div>
        </div>
    <?php elseif (!$autoupdater_enabled) : ?>
        <div class="autoupdater-show-after-enable" style="display: none;">
            <div class="update-nag notice" id="autoupdater_enable_attempt_warning">
                <?php _e('Save settings to finish enabling of automatic updates!', 'autoupdater'); ?>
            </div>
            <h2><?php _e('Automatic updates settings', 'autoupdater') ?></h2>
            <?php include dirname(__FILE__) . '/configuration_form_autoupdater_fields.tmpl.php'; ?>
        </div>
        <?php /*<div>
            <h2><?php _e('Advanced settings', 'autoupdater') ?></h2>
            <?php include dirname(__FILE__) . '/configuration_form_advanced_fields.tmpl.php'; ?>
        </div>*/ ?>
    <?php else : ?>
        <?php /*<h2><?php _e('Advanced settings', 'autoupdater') ?></h2>
        <?php include dirname(__FILE__)
            . '/configuration_form_advanced_fields.tmpl.php'; ?>*/ ?>
    <?php endif; ?>


    <div>
        <button type="button" id="autoupdater_save_config" class="button button-primary">
            <?php _e('Save') ?>
        </button>

        <div id="autoupdater_messages" style="display: inline-block; margin-top: -10px;">
            <div class="updated" style="display: none">
                <p><?php _e('Settings have been saved', 'autoupdater'); ?></p>
            </div>
            <div class="error error_other" style="display: none">
                <p><?php _e('Failed to save settings', 'autoupdater'); ?></p>
            </div>
            <div class="error error_email" style="display: none">
                <p><?php _e('The email addresses entered did not appear to be valid or it has exceded 500 bytes. Please enter a valid email address.', 'autoupdater'); ?></p>
            </div>
            <div class="error error_sitemap" style="display: none">
                <p><?php _e('The sitemap URL entered did not appear to be valid or it has exceded 255 characters. Please enter a valid sitemap URL.', 'autoupdater'); ?></p>
            </div>
        </div>
    </div>

    <?php wp_nonce_field('save-configuration'); ?>
</form>