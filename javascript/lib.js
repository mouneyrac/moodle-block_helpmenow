/**
 * generic call function for our ajax server
 *
 * @param object params
 * @param function callbackFunction
 */
function helpmenow_call(params, callbackFunction) {
    var xmlhttp;
    params = JSON.stringify(params);

    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        callbackFunction(xmlhttp);
    }

    xmlhttp.open("POST", helpmenow_url, true);
    xmlhttp.setRequestHeader("Accept", "application/json");
    xmlhttp.setRequestHeader("Content-type", "application/json");
    xmlhttp.send(params);
}

/**
 * Handles the message submission
 */
function helpmenow_submit_message(message) {
    var params = {
        "function" : "message",
        "message" : message,
        "session" : chat_session,
    };
    helpmenow_call(params, function (xmlhttp) {
        if (xmlhttp.readyState != 4) {
            return;
        }
        if (xmlhttp.status != 200) {
            helpmenow_submit_message(message);
            return;
        }
        $("#chatDiv").append("<div><b>Me:</b> " + message + "</div>")
            .scrollTop($('#chatDiv')[0].scrollHeight);
    });
}

/**
 * Function that is called periodically to get new messages
 */
function helpmenow_chat_refresh() {
    var params = {
        "function" : "refresh",
        "session" : chat_session,
        "last_message" : last_message,
    };
    helpmenow_call(params, function (xmlhttp) {
        if (xmlhttp.readyState != 4 || xmlhttp.status != 200) {
            return;
        }
        var response = JSON.parse(xmlhttp.responseText);
        if (response.last_message > last_message) {
            last_message = response.last_message;
            $("#chatDiv").append(response.html)
                .scrollTop($('#chatDiv')[0].scrollHeight);
            if (!$(document)[0].hasFocus()) {
                $("#helpmenow_chime")[0].Play();
            }
        }
    });
}

/**
 * Loads chat history and starts refresh cycle
 */
function helpmenow_load_history() {
    var params = {
        "function" : "history",
        "session" : chat_session,
    };
    helpmenow_call(params, function (xmlhttp) {
        if (xmlhttp.readyState != 4) {
            return;
        }
        if (xmlhttp.status != 200) {
            helpmenow_load_history();
            return;
        }
        var response = JSON.parse(xmlhttp.responseText);
        last_message = response.last_message;
        $("#chatDiv").append(response.html)
            .scrollTop($('#chatDiv')[0].scrollHeight);
        refresh = setInterval(helpmenow_chat_refresh, 2000);
    });
}

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

/**
 * Function to invite user to wiziq
 */
function helpmenow_wiziq_invite() {
    var params = {
        "function" : "plugin",
        "plugin" : "wiziq",
        "plugin_function" : "helpmenow_wiziq_invite",
        "session" : chat_session,
    };
    helpmenow_call(params, function (xmlhttp) {
        if (xmlhttp.readyState != 4) {
            return;
        }
        if (xmlhttp.status != 200) {
            helpmenow_wiziq_invite();
            return;
        }
    });
}
