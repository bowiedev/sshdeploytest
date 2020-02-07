<table class="form-table">
    <tbody>
    <tr id="autoupdater_enable">
        <th scope="row">
            <label>
                <?php _e('Enable automatic updates', 'autoupdater') ?>
            </label>
        </th>
        <td>
            <label>
                <input type="radio" name="autoupdater_enabled" value="0" <?php if (!$autoupdater_enabled) echo 'checked="checked"'; ?>>
                <?php _e('No') ?>
            </label>
            <label>
                <input type="radio" name="autoupdater_enabled" value="1" <?php if ($autoupdater_enabled) echo 'checked="checked"'; ?>>
                <?php _e('Yes') ?>
            </label>
            <p class="description">
                <?php _e('This website will be automatically updated', 'autoupdater') ?>
            </p>
        </td>
    </tr>
    <?php /*<tr id="autoupdater_update_core">
        <th scope="row">
            <label>
                <?php _e('Update WordPress core', 'autoupdater') ?>
            </label>
        </th>
        <td>
            <label>
                <input type="radio" name="update_core" value="0" <?php if (!$update_core) echo 'checked="checked"'; ?>>
                <?php _e('No') ?>
            </label>
            <label>
                <input type="radio" name="update_core" value="1" <?php if ($update_core) echo 'checked="checked"'; ?>>
                <?php _e('Yes') ?>
            </label>
            <p class="description">
                <?php _e('Enable the automatic updates of the WordPress core', 'autoupdater') ?>
                <?php switch ($update_core_minor_policy) :
                    case 'disallow':
                        _e('with security and bug fixes only', 'autoupdater');
                        break;
                    case 'instant':
                        _e('to the latest version', 'autoupdater');
                        break;
                    case 'stable':
                        _e('to the latest version, excluding the first release (X.Y.0)', 'autoupdater');
                        break;
                    case 'delay_week':
                        _e('to the latest version, excluding the first release (X.Y.0), but with a weekly delay to wait for plugins\' compatibility updates', 'autoupdater');
                        break;
                    case 'delay_two_weeks':
                        _e('to the latest version, excluding the first release (X.Y.0), but with two weeks delay to wait for plugins\' compatibility updates', 'autoupdater');
                        break;
                endswitch; ?>
            </p>
        </td>
    </tr>
    <tr id="autoupdater_update_plugins">
        <th scope="row">
            <label>
                <?php _e('Update plugins', 'autoupdater') ?>
            </label>
        </th>
        <td class="autoupdater-toggle-input" data-toggle-target="#autoupdater_excluded_plugins">
            <label>
                <input type="radio" name="update_plugins" value="0" <?php if (!$update_plugins) echo 'checked="checked"'; ?>>
                <?php _e('No') ?>
            </label>
            <label>
                <input type="radio" name="update_plugins" value="1" <?php if ($update_plugins) echo 'checked="checked"'; ?>>
                <?php _e('Yes') ?>
            </label>
            <p class="description">
                <?php _e('Enable automatic updates of plugins', 'autoupdater') ?>
            </p>
        </td>
    </tr>*/ ?>
    <tr id="autoupdater_excluded_plugins" <?php if (!$update_plugins) echo 'style="display: none;"'; ?>>
        <th scope="row">
            <label>
                <?php _e('Exclude selected plugins', 'autoupdater') ?>
            </label>
        </th>
        <td>
            <div class="autoupdater-toggle">
                <?php if ($plugins_list_count > 10) : ?>
                    <a href="#" class="autoupdater-toggle-button">
                        <span class="autoupdater-toggle-indicator"><?php _e('Show', 'autoupdater'); ?></span>
                        <span class="autoupdater-toggle-indicator" style="display: none;"><?php _e('Hide', 'autoupdater'); ?></span>
                    </a>
                <?php endif; ?>
                <div class="autoupdater-toggle-content" <?php if ($plugins_list_count > 10) echo 'style="display: none;"'; ?>>
                    <?php foreach ($plugins_list as $slug => $item) : ?>
                        <br>
                        <label for="excluded_plugins_<?php echo $slug; ?>" class="checkbox">
                            <input type="checkbox" id="excluded_plugins_<?php echo $slug; ?>"
                                   name="excluded_plugins[]" value="<?php echo $slug; ?>"
                                <?php if (!empty($item['excluded'])) echo 'checked="checked"'; ?>
                            >
                            <?php echo $item['name'] . ' (' . $slug . ')'; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </td>
    </tr>
    <?php /*<tr id="autoupdater_update_themes">
        <th scope="row">
            <label>
                <?php _e('Update themes', 'autoupdater') ?>
            </label>
        </th>
        <td class="autoupdater-toggle-input" data-toggle-target="#autoupdater_excluded_themes">
            <label>
                <input type="radio" name="update_themes" value="0" <?php if (!$update_themes) echo 'checked="checked"'; ?>>
                <?php _e('No') ?>
            </label>
            <label>
                <input type="radio" name="update_themes" value="1" <?php if ($update_themes) echo 'checked="checked"'; ?>>
                <?php _e('Yes') ?>
            </label>
            <p class="description">
                <?php _e('Enable automatic updates of themes', 'autoupdater') ?>
            </p>
        </td>
    </tr>
    <tr id="autoupdater_excluded_themes" <?php if (!$update_themes) echo 'style="display: none;"'; ?>>
        <th scope="row">
            <label>
                <?php _e('Exclude selected themes', 'autoupdater') ?>
            </label>
        </th>
        <td>
            <div class="autoupdater-toggle">
                <?php if ($themes_list_count > 10) : ?>
                    <a href="#" class="autoupdater-toggle-button">
                        <span class="autoupdater-toggle-indicator"><?php _e('Show', 'autoupdater'); ?></span>
                        <span class="autoupdater-toggle-indicator" style="display: none;"><?php _e('Hide', 'autoupdater'); ?></span>
                    </a>
                <?php endif; ?>
                <div class="autoupdater-toggle-content" <?php if ($themes_list_count > 10) echo 'style="display: none;"'; ?>>
                    <?php foreach ($themes_list as $slug => $item) : ?>
                        <br>
                        <label for="excluded_themes_<?php echo $slug; ?>" class="checkbox">
                            <input type="checkbox" id="excluded_themes_<?php echo $slug; ?>"
                                   name="excluded_themes[]" value="<?php echo $slug; ?>"
                                <?php if (!empty($item['excluded'])) echo 'checked="checked"'; ?>
                            >
                            <?php echo $item['name'] . ' (' . $slug . ')'; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </td>
    </tr>*/ ?>
    <tr id="autoupdater_autoupdate_at">
        <th scope="row">
            <label>
                <?php _e('Time of day', 'autoupdater') ?>
            </label>
        </th>
        <td>
            <select name="autoupdate_at">
                <?php
                foreach ($autoupdate_at_options as $key => $item) :
                    echo '<option value="' . $key . '" ' . ($key === $autoupdate_at ? 'selected="selected"' : null) . '>' . $item . '</option>';
                endforeach;
                ?>
            </select>
            <p class="description">
                <?php _e('The time of the day when the automatic update is being performed', 'autoupdater') ?>
            </p>
        </td>
    </tr>
    <tr id="autoupdater_sitemap_url">
        <th scope="row">
            <label><?php _e('Sitemap URL', 'autoupdater') ?></label>
        </th>
        <td>
            <label>
                <input type="url" name="sitemap_url" maxlength="255" value="<?php echo esc_attr($sitemap_url); ?>" placeholder="<?php echo esc_attr(site_url('/sitemap.xml')) ?>" class="regular-text">
            </label>
            <p class="description">
                <?php printf(__('Provide URL to this website\'s sitemap to test up to 20 random URLs during an update. Accepted formats: XML %s and %s or a plain text sitemap with an absolute URL on each line. Put a new line break after the last URL.', 'autoupdater'), 
                    '<code><urlset></code>', '<code><sitemapindex></code>') ?>
            </p>
        </td>
    </tr>
    <tr id="autoupdater_maintenance_mode">
        <th scope="row">
            <label><?php _e('Maintenance mode', 'autoupdater') ?></label>
        </th>
        <td>
            <label>
                <input type="radio" name="maintenance_mode" value="0" <?php if (!$maintenance_mode) echo 'checked="checked"'; ?>>
                <?php _e('No') ?>
            </label>
            <label>
                <input type="radio" name="maintenance_mode" value="1" <?php if ($maintenance_mode) echo 'checked="checked"'; ?>>
                <?php _e('Yes') ?>
            </label>
            <p class="description">
                <?php _e('Enables maintenance mode of the website during an update in order to prevent possible data loss.', 'autoupdater') ?>
            </p>
        </td>
    </tr>
    <tr id="autoupdater_notification_emails">
        <th scope="row">
            <label><?php _e('Notification e-mails', 'autoupdater') ?></label>
        </th>
        <td>
            <label>
                <input type="text" name="notification_emails" value="<?php echo esc_attr($notification_emails); ?>" class="regular-text">
            </label>
            <p class="description">
                <?php printf(__('Provide e-mail addresses separated with a comma (,) to receive a notification after the automatic update of the site, in accordance with %s Privacy Policy, you have agreed on.', 'autoupdater'), 
                    $author) ?>
            </p>
        </td>
    </tr>
    <tr id="autoupdater_notification_success">
        <th scope="row">
            <label><?php _e('Notification on successful update', 'autoupdater') ?></label>
        </th>
        <td>
            <label>
                <input type="radio" name="notification_on_success" value="0" <?php if (!$notification_on_success) echo 'checked="checked"'; ?>>
                <?php _e('No') ?>
            </label>
            <label>
                <input type="radio" name="notification_on_success" value="1" <?php if ($notification_on_success) echo 'checked="checked"'; ?>>
                <?php _e('Yes') ?>
            </label>
            <p class="description">
                <?php _e('Receive a notification after a successful update', 'autoupdater') ?>
            </p>
        </td>
    </tr>
    <tr id="autoupdater_notification_fail">
        <th scope="row">
            <label><?php _e('Notification on failed update', 'autoupdater') ?></label>
        </th>
        <td>
            <label>
                <input type="radio" name="notification_on_failure" value="0" <?php if (!$notification_on_failure) echo 'checked="checked"'; ?>>
                <?php _e('No') ?>
            </label>
            <label>
                <input type="radio" name="notification_on_failure" value="1" <?php if ($notification_on_failure) echo 'checked="checked"'; ?>>
                <?php _e('Yes') ?>
            </label>
            <p class="description">
                <?php _e('Receive a notification after a failed update', 'autoupdater') ?>
            </p>
        </td>
    </tr>
    <tr id="autoupdater_auto_rollback">
        <th scope="row">
            <label><?php _e('Auto-rollback', 'autoupdater') ?></label>
        </th>
        <td>
            <label>
                <input type="radio" name="auto_rollback" value="0" <?php if (!$auto_rollback) echo 'checked="checked"'; ?>>
                <?php _e('No') ?>
            </label>
            <label>
                <input type="radio" name="auto_rollback" value="1" <?php if ($auto_rollback) echo 'checked="checked"'; ?>>
                <?php _e('Yes') ?>
            </label>
            <p class="description">
                <?php _e('Enables the automatic rollback after a failed update.', 'autoupdater') ?>
            </p>
        </td>
    </tr>
    </tbody>
    <tr id="autoupdater_vrt_css_exclusions">
        <th scope="row">
            <label><?php _e('CSS Exclusions', 'autoupdater') ?></label>
        </th>
        <td>
            <label>
                <textarea name="vrt_css_exclusions" placeholder="#footer .map&#10;.rev_slider&#10;.q_slider" class="regular-text" rows="5"><?php 
                    echo function_exists('esc_textarea') ? esc_textarea($vrt_css_exclusions) : htmlspecialchars($vrt_css_exclusions); ?></textarea>
            </label>
            <p class="description">
                <?php _e('Elements matching above CSS selectors will be hidden during visual regression testing. It’s recommended to exclude dynamic content which might lead to false negatives, such as a slider, ads or testimonials. Enter each CSS selector on a new line.', 'autoupdater') ?>
            </p>
        </td>
    </tr>
</table>
