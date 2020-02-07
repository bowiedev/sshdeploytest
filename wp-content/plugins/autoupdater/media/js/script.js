/*!
 * @license GNU/GPL http://www.gnu.org/licenses/gpl-3.0.html
 */

jQuery(document).ready(function ($) {

    if ($("#autoupdater_configuration_form").length) {
        $('#autoupdater_show_settings').click(function (e) {
            e.preventDefault();
            $('#autoupdater_configuration_form').toggle();
        });

        $('.autoupdater-toggle-button').click(function (e) {
            e.preventDefault();
            $(this).find('.autoupdater-toggle-indicator').toggle();
            $(this).closest('.autoupdater-toggle').find('.autoupdater-toggle-content').toggle();
        });

        $('.autoupdater-toggle-input input').change(function() {
            var selector = $(this).closest('.autoupdater-toggle-input').data('toggle-target');
            $(selector).toggle();
        });

        $('.autoupdater-enable').click(function (e) {
            e.preventDefault();
            $('.autoupdater-template-wrapper').toggle();
            $('.autoupdater-hide-after-enable').hide();
            $('.autoupdater-show-after-enable').show();
            $('input[name="autoupdater_enabled"][value="1"]').prop('checked', true);
            $(this).data('enable-attempt', 1);
        });

        if ($('.autoupdater-enable').length) {
            window.onbeforeunload = function () {
                if ($('.autoupdater-enable').data('enable-attempt')) {
                    return 'Changes you made may not be saved.';// it's default text for this alert box
                } else {
                    return;
                }
            };
        }

        $('#autoupdater_save_config').click(function (e) {
            e.preventDefault();

            var $button = $(this);
            var $config_form = $("#autoupdater_configuration_form");
            var $messages = $("#autoupdater_messages");
            var $autoupdater_enable_button = $('.autoupdater-enable');
            var timer = 0;

            $.ajax({
                url: $config_form.attr('action'),
                type: "POST",
                data: $config_form.serialize(),
                beforeSend: function () {
                    $button.attr("disabled", true);
                    clearInterval(timer);
                    $messages.find(".updated").hide();
                    $messages.find(".error").hide();
                }
            }).done(function () {
                $messages.find(".error").hide();
                $messages.find(".updated").show();
                timer = setTimeout(function () {
                    $messages.find(".updated").hide();
                }, 2000);
            }).fail(function (xhr) {
                if (typeof xhr.responseText !== 'undefined' && (xhr.responseText === 'error_email' || xhr.responseText === 'error_sitemap')) {
                    $messages.find("." + xhr.responseText).show();
                } else {
                    $messages.find(".error_other").show();
                }
                timer = setTimeout(function () {
                    $messages.find(".error").hide();
                }, 2000);
            }).always(function () {
                $button.removeAttr("disabled");

                if ($autoupdater_enable_button.length) {
                    $autoupdater_enable_button.data('enable-attempt', 0)
                }
            });
        });
    }
});
