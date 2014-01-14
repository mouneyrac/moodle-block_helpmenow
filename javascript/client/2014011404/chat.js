// This "inherits" from lib.js helpmenow. This chat.js file contains
// functionality specific to the popup chat window.

var helpmenow = (function (my) {
    "use strict";

    var pluginRefresh = [];     // plugin refresh callbacks

    /**
     * extend init
     */
    var oldInit = my.init;
    my.init = function () {
        $('#inputTextarea').keypress(function (e) {  // textarea keypress handler
                if (e.keyCode === 13 && !e.ctrlKey) { // Check for enter/return press without ctrl key
                    var message = $(this).val();
                    if (message.length === 0) { // Don't send blank messages
                        return false;
                    }
                    helpmenow.chat.submitMessage(message);
                    $(this).val(''); // clear text box after sending message
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
                if (response.beep && !$(document)[0].hasFocus()) { // Chime if window does not have focus
                    helpmenow.chime();
                    if (typeof response.title_flash !== "undefined") { // make title blink
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
        submitSystemMessage: function (message) {
            var params = {
                'requests': {
                    'sysmessage': {
                        'id': 'sysmessage',
                        'function': 'sysmessage',
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
                } catch (e) {
                    $("#chatDiv").append("<div><i>An error occured submitting your message.</i></div>")     // todo: and here
                        .scrollTop($('#chatDiv')[0].scrollHeight);
                }
            });
        },
        submitLastRead: function (messageid) {
            var params = {
                'requests': {
                    'lastRead': {
                        'id': 'lastRead',
                        'function': 'last_read',
                        'last_read': messageid,
                        'session': helpmenow.sharedData.session
                    }
                }
            };
            helpmenow.ajax(params, function (xmlhttp) {
                if (xmlhttp.readyState !== 4) { return; }
                try {
                    if (xmlhttp.status != 200) { throw "status: " + xmlhttp.status; }
                    var response = JSON.parse(xmlhttp.responseText);
                    if (typeof response.error !== 'undefined') {throw "error: " + response.error; }
                } catch (e) {
                    $("#chatDiv").append("<div><i>An error occured with your connection to the server, please check your internet connection or contact the help desk for more help.</i></div>")     // todo: language string
                        .scrollTop($('#chatDiv')[0].scrollHeight);
                }
            });
        },
        addPluginRefresh: function (callback) {
            pluginRefresh.push(callback);
        }
    };

    // When chat window is focused on mark messages as read, leave as
    // unfocused/unread otherwise to include in emails
    $(window).focus(function() {
        if(helpmenow.sharedData.lastReadMessage != helpmenow.sharedData.lastMessage &&
           helpmenow.sharedData.lastMessage != 0 ) {
            helpmenow.sharedData.lastReadMessage = helpmenow.sharedData.lastMessage;
            // Send last read message id back to server to record in db
            helpmenow.chat.submitLastRead(helpmenow.sharedData.lastReadMessage);
        }
    });


    return my;
}) (helpmenow);
