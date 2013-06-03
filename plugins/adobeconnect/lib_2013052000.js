
var helpmenow_plugin_adobeconnect = (function () {
    "use strict";

    var inviteMessage = "";

    var invite = function () {
        helpmenow.chat.submitSystemMessage(inviteMessage);
    }

    $(document).ready(function(){
        $('a#adobeconnect_invite').click(function(){
            invite();
            return false;
        });
    });

    return {
        init: function (message) {
            inviteMessage = message;
        }
    };
}) ();
