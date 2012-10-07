/**
 * Function to invite user to gotomeeting
 */
helpmenowChat.gotomeetingInvite = function () {
    var params = {
        "function": "plugin",
        "plugin": "gotomeeting",
        "plugin_function": "helpmenow_gotomeeting_invite",
        session: sessionId,
    };
    helpmenow.addRequest(params, function (response) {
        return;
    });
}
