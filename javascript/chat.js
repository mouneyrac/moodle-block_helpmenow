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
            //var response = JSON.parse(xmlhttp.responseText);
            //console.debug(response);
            if (xmlhttp.status != 200) {
                helpmenow_message(message);
                return;
            }
            helpmenow_chat_refresh();
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
    };
    helpmenow_call(params, function(xmlhttp) {
        if (xmlhttp.readyState==4) {
            var response = JSON.parse(xmlhttp.responseText);
            //console.debug(response);
            if (xmlhttp.status==200) {
                var chatDiv = document.getElementById("chatDiv");
                if (chatDiv.innerHTML != response.html) {
                    chatDiv.innerHTML = response.html;
                    chatDiv.scrollTop = chatDiv.scrollHeight;
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
            //var response = JSON.parse(xmlhttp.responseText);
            //console.debug(response);
            if (xmlhttp.status != 200) {
                helpmenow_invite();
                return;
            }
        }
    });
}

