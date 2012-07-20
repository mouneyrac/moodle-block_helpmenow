/**
 * Handles keypresses in chat textarea
 *
 * @param event e keypress event
 * @return bool true indicates to the browser to treat the event as a normal
 *      keystroke
 */
function helpmenow_chat_textarea(e) {
    e = e || event;     // IE

    // enter key
    if (e.keyCode === 13 && !e.ctrlKey) {
        var inputTextarea = document.getElementById("inputTextarea");
        if (inputTextarea.value.length == 0) {
            return false;
        }
        helpmenow_message(inputTextarea.value);
        inputTextarea.value = '';
        return false;
    }
    return true;
}

/**
 * Handles the message submission
 */
function helpmenow_message(message) {
    var params = {
        "function" : "message",
        "message" : message,
        "session" : helpmenow_session,
    };
    helpmenow_call(params, function(xmlhttp) {
        if (xmlhttp.readyState==4) {
            if (xmlhttp.status != 200) {
                helpmenow_message(message);
                return;
            }
            var chatDiv = document.getElementById("chatDiv");
            chatDiv.innerHTML += "<div><b>Me:</b> " + message + "</div>";
            chatDiv.scrollTop = chatDiv.scrollHeight;
        }
    });
}

/**
 * Function that is called periodically to get new messages
 */
function helpmenow_chat_refresh() {
    var params = {
        "function" : "refresh",
        "session" : helpmenow_session,
        "last_message" : helpmenow_last_message,
    };
    helpmenow_call(params, function(xmlhttp) {
        if (xmlhttp.readyState==4) {
            if (xmlhttp.status==200) {
                var response = JSON.parse(xmlhttp.responseText);
                if (response.last_message > helpmenow_last_message) {
                    helpmenow_last_message = response.last_message;
                    var chatDiv = document.getElementById("chatDiv");
                    chatDiv.innerHTML += response.html;
                    chatDiv.scrollTop = chatDiv.scrollHeight;
                    if (!document.hasFocus()) {
                        var helpmenow_chime = document.getElementById("helpmenow_chime");
                        helpmenow_chime.Play();
                    }
                }
            }
        }
    });
}

/**
 * Function to invite user to gotomeeting
 */
function helpmenow_invite() {
    var params = {
        "function" : "invite",
        "session" : helpmenow_session,
    };
    helpmenow_call(params, function(xmlhttp) {
        if (xmlhttp.readyState==4) {
            if (xmlhttp.status != 200) {
                helpmenow_invite();
                return;
            }
        }
    });
}

/**
 * Function to invite user to wiziq
 */
function helpmenow_wiziq_invite() {
    var params = {
        "function" : "wiziq_invite",
        "session" : helpmenow_session,
    };
    helpmenow_call(params, function(xmlhttp) {
        if (xmlhttp.readyState==4) {
            if (xmlhttp.status != 200) {
                helpmenow_wiziq_invite();
                return;
            }
        }
    });
}
