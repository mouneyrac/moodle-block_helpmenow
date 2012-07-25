$(document).ready(function () {
    $('#inputTextarea').keypress(function (e) {  // textarea keypress handler
            if (e.keyCode === 13 && !e.ctrlKey) {
                var message = $(this).val();
                if (message.length == 0) {
                    return false;
                }
                helpmenow_submit_message(message);
                $(this).val('');
                return false;
            }
            return true;
        })
        .focus(function () {    // on (first) focus clear text area and replace focus function
            $(this).text('')
                .focus(function () {
                    return;
                });
        });

    // set the chatDiv class depending on if the plugin div exists
    $('#chatDiv').addClass(($('#pluginDiv').length > 0) ? 'plugins' : 'noPlugins');

    // loads history and starts refresh cycle
    helpmenow_load_history();
});
