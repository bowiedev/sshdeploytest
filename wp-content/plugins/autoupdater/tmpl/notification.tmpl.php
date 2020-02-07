<?php function_exists('add_action') or die; ?>
<script type="text/javascript">
    jQuery(window).load(function () {
        jQuery('#autoupdater_notification button.notice-dismiss').remove();
        <?php  if ($notification['closable']) : ?>
        jQuery('<button type="button" class="notice-dismiss"></button>').click(function () {
            jQuery.ajax({
                url: "<?php echo $url; ?>",
                method: "post",
                data: {
                    action: 'autoupdater_notification_close'
                }
            }).done(function () {
                jQuery("#autoupdater_notification").hide();
            }).fail(function (xhr) {
                console.error(xhr.status + ' ' + xhr.statusText);
                console.error(xhr.responseText);
                alert("<?php _e('An error has occured.', 'autoupdater') ?>");
            });
        }).appendTo("#autoupdater_notification");
        <?php  endif; ?>
    });
</script>
<div id="autoupdater_notification" class="updated notice is-dismissible">
    <?php echo $notification['content']; ?>
</div>
