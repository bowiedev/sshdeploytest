    <table class="form-table">
    <tbody>
    <tr>
        <th scope="row">
            <label for="autoupdater_worker_token">
                <?php _e('Worker token', 'autoupdater'); ?>
            </label>
        </th>
        <td>
            <input id="autoupdater_worker_token" name="worker_token" type="text" class="regular-text"
                   value="<?php echo $worker_token; ?>"<?php if ($protect) echo ' disabled="disabled"'; ?>
            />
            <p class="description">
                <?php _e('The token to sign a request and authenticate it', 'autoupdater') ?>
            </p>
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label for="autoupdater_aes_key">
                <?php _e('AES key', 'autoupdater'); ?>
            </label>
        </th>
        <td>
            <input id="autoupdater_aes_key" name="aes_key" type="text" class="regular-text"
                   value="<?php echo $aes_key; ?>"<?php if ($protect) echo ' disabled="disabled"'; ?>
            />
            <p class="description">
                <?php _e('This key is used to encrypt the response if your website is not secured with the TLS', 'autoupdater') ?>
            </p>
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label>
                <?php _e('Response encryption', 'autoupdater'); ?>
            </label>
        </th>
        <td>
            <label>
                <input type="radio" name="encrypt_response"
                       value="0" <?php if (empty($encrypt_response)) echo 'checked="checked"'; ?>/>
                <?php _e('No'); ?>
            </label>
            <label>
                <input type="radio" name="encrypt_response"
                       value="1" <?php if (!empty($encrypt_response)) echo 'checked="checked"'; ?>/>
                <?php _e('Yes'); ?>
            </label>
            <p class="description">
                <?php _e('The response will be encrypted by the plugin if your website is not secured with the TLS', 'autoupdater') ?>
            </p>
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label>
                <?php _e('SSL verification', 'autoupdater'); ?>
            </label>
        </th>
        <td>
            <label>
                <input type="radio" name="ssl_verify"
                       value="0" <?php if (empty($ssl_verify)) echo 'checked="checked"'; ?>/>
                <?php _e('No'); ?>
            </label>
            <label>
                <input type="radio" name="ssl_verify"
                       value="1" <?php if (!empty($ssl_verify)) echo 'checked="checked"'; ?>/>
                <?php _e('Yes'); ?>
            </label>
            <p class="description">
                <?php _e('Enable the SSL verification for a download request', 'autoupdater') ?>
            </p>
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label>
                <?php _e('Debug', 'autoupdater'); ?>
            </label>
        </th>
        <td>
            <label>
                <input type="radio" name="debug"
                       value="0" <?php if (empty($debug)) echo 'checked="checked"'; ?>/>
                <?php _e('No'); ?>
            </label>
            <label>
                <input type="radio" name="debug"
                       value="1" <?php if (!empty($debug)) echo 'checked="checked"'; ?>/>
                <?php _e('Yes'); ?>
            </label>
            <p class="description">
                <?php _e('Save logs to a file', 'autoupdater') ?>
            </p>
        </td>
    </tr>
    </tbody>
</table>