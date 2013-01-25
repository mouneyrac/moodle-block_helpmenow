var helpmenow = (function (my) {
    "use strict";

    var pluginRefresh = [];     // plugin refresh callbacks

    /**
     * extend init
     */
    var oldInit = my.init;
    my.init = function () {
        $('#inputTextarea').keypress(function (e) {  // textarea keypress handler
                if (e.keyCode === 13 && !e.ctrlKey) {
                    var message = $(this).val();
                    if (message.length === 0) {
                        return false;
                    }
                    helpmenow.chat.submitMessage(message);
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

        // setTimeout(function () { refresh(); }, 1000);
        oldInit();
    };

    my.processUpdates = function (response) {
        if (typeof response === "undefined" || response === null) {
            return;
        }
        if (typeof response.error === 'undefined') {
            if (response.last_message > helpmenow.sharedData.lastMessage) {
                helpmenow.sharedData.lastMessage = response.last_message;
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
    };

    my.chat = {
        setLastMessage: function (newLastMessage) {
            helpmenow.sharedData.lastMessage = newLastMessage;
        },
        setSessionId: function (newSessionId) {
            helpmenow.sharedData.session = newSessionId;
        },
        submitMessage: function (message) {
            var params = {
                'requests': {
                    'message': {
                        'id': 'message',
                        'function': 'message',
                        'message': message,
                        'session': helpmenow.sharedData.session 
                    }
                }
            };
            helpmenow.ajax(params, function (xmlhttp) {
                if (xmlhttp.readyState !== 4) { return; }
                try {
                    if (xmlhttp.status !== 200) { throw "status: " + xmlhttp.status; }
                    var response = JSON.parse(xmlhttp.responseText);
                    if (typeof response.error !== 'undefined') { throw "error: " + response.error; }
                    $("#chatDiv").append("<div><b>Me:</b> " + message + "</div>")                           // todo: use language string here
                        .scrollTop($('#chatDiv')[0].scrollHeight);
                } catch (e) {
                    $("#chatDiv").append("<div><i>An error occured submitting your message.</i></div>")     // todo: and here
                        .scrollTop($('#chatDiv')[0].scrollHeight);
                }
            });
        },
        addPluginRefresh: function (callback) {
            pluginRefresh.push(callback);
        }
    };

    return my;
}) (helpmenow);
