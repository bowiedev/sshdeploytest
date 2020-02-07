<div id="autoupdater-page">
    <div class="autoupdater-template-wrapper"<?php if (!$autoupdater_enabled) echo ' style="display: none;"'; ?>>
        <?php if ($template_enabled) : ?>
            <?php echo $template_enabled; ?>
        <?php else : ?>
            <?php printf(__('Welcome to %s', 'autoupdater'), $plugin_name); ?>
            <br><br>
            <?php printf(__('According to research 86%% of websites get hacked because of outdated WordPress core, themes & plugins. %s truly cares about website security and your piece of mind. That is why we have provided this plugin for you to ease the pain for keeping websites up-to-date.'
                , 'autoupdater'), $author); ?>
            <br><br>
            <a target="_blank" href="https://wpengine.com/support/smart-plugin-manager-faq/">
                <?php _e('Learn more here', 'autoupdater'); ?>
            </a>
        <?php endif; ?>
    </div>
    <div class="autoupdater-template-wrapper"<?php if ($autoupdater_enabled) echo ' style="display: none;"'; ?>>
        <?php if ($template_disabled) : ?>
            <?php echo $template_disabled; ?>
        <?php else : ?>
            <?php printf(__('Welcome to %s', 'autoupdater'), $plugin_name); ?>
            <br><br>
            <?php printf(__('According to research 86%% of websites get hacked because of outdated WordPress core, themes & plugins. %s truly cares about website security and your piece of mind. That is why we have provided this plugin for you to ease the pain for keeping websites up-to-date.'
                , 'autoupdater'), $author); ?>
            <br><br>
            <button type="button" class="autoupdater-enable button button-primary">
                <?php _e('Enable automatic updates', 'autoupdater'); ?>
            </button>
        <?php endif; ?>
    </div>
    <div id="autoupdater-form-wrapper">
        <?php include dirname(__FILE__) . '/configuration_form.tmpl.php'; ?>
    </div>
</div>