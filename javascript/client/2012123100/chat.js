var helpmenowChat = (function () {
    /**
     * session.id
     */
    var sessionId;

    /**
     * last received message
     */
    var lastMessage;

    /**
     * plugin refresh callbacks
     */
    var pluginRefresh = [];

    /**
     * get new messages
     */
    function refresh() {
        var params = {
            "function": "refresh",
            session: sessionId,
            "last_message": lastMessage,
        };
        helpmenow.addRequest(params, function (response) {
            if (typeof response.error === 'undefined') {
                if (response.last_message > lastMessage) {
                    lastMessage = response.last_message;
                    $("#chatDiv").append(response.html)
                        .scrollTop($('#chatDiv')[0].scrollHeight);
                    if (response.beep && !$(document)[0].hasFocus()) {
                        helpmenow.chime();
                        if (typeof response.title_flash !== "undefined") {
                            $.titleAlert('"' + response.title_flash + '"', {
                                interval:1000
                            });
                        }
                    }
                }

                $.each(pluginRefresh, function (k, v) {
                    v(response);
                });
            }
            setTimeout(function () { refresh(); }, 1000);
        });
    }

    /**
     * public interface
     */
    return {
        init: function () {
            helpmenow.init();
            setTimeout(function () { refresh(); }, 1000);
        },
        setLastMessage: function (newLastMessage) {
            lastMessage = newLastMessage;
        },
        setSessionId: function (newSessionId) {
            sessionId = newSessionId;
        },
        submitMessage: function (message) {
            var params = {
                "function": "message",
                message: message,
                session: sessionId,
            };
            helpmenow.addRequest(params, function (response) {
                if (typeof response.error === 'undefined') {
                    $("#chatDiv").append("<div><b>Me:</b> " + message + "</div>")   // todo: use language string here
                        .scrollTop($('#chatDiv')[0].scrollHeight);
                } else {
                    $("#chatDiv").append("<div><i>An error occured submitting your message.</i></div>")   // todo: and here
                        .scrollTop($('#chatDiv')[0].scrollHeight);
                }
            });
        },
        addPluginRefresh: function (callback) {
            pluginRefresh.push(callback);
        }
    };
}) ();


$(document).ready(function () {
    $('#inputTextarea').keypress(function (e) {  // textarea keypress handler
            if (e.keyCode === 13 && !e.ctrlKey) {
                var message = $(this).val();
                if (message.length == 0) {
                    return false;
                }
                helpmenowChat.submitMessage(message);
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

    helpmenowChat.init();
});
