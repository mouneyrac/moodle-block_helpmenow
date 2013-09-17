/**
 * Function to invite user to gotomeeting
 */
helpmenow.chat.gotomeetingInvite = function () {
    var params = {
        'requests': {
            'message': {
                'id': 'gotomeeting',
                "function": "plugin",
                "plugin": "gotomeeting",
                "plugin_function": "helpmenow_gotomeeting_invite",
                'session': helpmenow.sharedData.session 
            }
        }
    };
    helpmenow.ajax(params, function (response) {
        return;
    });
}
