helpmenowChat.addPluginRefresh(function (response) {
    if (typeof response.wiziq !== "undefined") {
        $('#helpmenow_wiziq').html(response.wiziq);
    }
});

/**
 * Function to invite user to wiziq
 */
/*
helpmenowChat.wiziqInvite = function () {
    var params = {
        "function" : "plugin",
        "plugin" : "wiziq",
        "plugin_function" : "helpmenow_wiziq_ajax_invite",
        "session" : chat_session,
    };
    helpmenow.addRequest(params, function (response) {
        return;
    });
}
*/
