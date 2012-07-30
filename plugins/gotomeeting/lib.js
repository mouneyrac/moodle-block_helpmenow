/**
 * Function to invite user to gotomeeting
 */
function helpmenow_gotomeeting_invite() {
    var params = {
        "function" : "plugin",
        "plugin" : "gotomeeting",
        "plugin_function" : "helpmenow_gotomeeting_invite",
        "session" : chat_session,
    };
    helpmenow_call(params, function (xmlhttp) {
        if (xmlhttp.readyState != 4) {
            return;
        }
        if (xmlhttp.status != 200) {
            helpmenow_gotomeeting_invite();
            return;
        }
    });
}
