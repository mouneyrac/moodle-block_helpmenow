/**
 * Function to invite user to wiziq
 */
function helpmenow_wiziq_invite() {
    var params = {
        "function" : "plugin",
        "plugin" : "wiziq",
        "plugin_function" : "helpmenow_wiziq_ajax_invite",
        "session" : chat_session,
    };
    helpmenow_call(params, function (xmlhttp) {
        if (xmlhttp.readyState != 4) {
            return;
        }
        if (xmlhttp.status != 200) {
            helpmenow_wiziq_invite();
            return;
        }
    });
}
