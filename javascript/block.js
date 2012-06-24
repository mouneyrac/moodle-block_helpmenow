/**
 * toggles motd editing
 *
 * @param bool edit  true indicates edit mode, false is display mode
 */
function helpmenow_toggle_motd(edit) {
    var motd_element = document.getElementById("helpmenow_motd");
    var edit_element = document.getElementById("helpmenow_motd_edit");
    if (edit) {
        motd_element.style.display = "none";
        edit_element.style.display = "block";
        edit_element.focus();
        edit_element.value = "";
        edit_element.value = motd_element.innerHTML;
    } else {
        motd_element.style.display = "block";
        edit_element.style.display = "none";
    }
}

/**
 * toggles logged in status display
 */
function helpmenow_toggle_login_display(loggedin, queue) {
    var logged_in_div = document.getElementById("helpmenow_logged_in_div_" + queue);
    var logged_out_div = document.getElementById("helpmenow_logged_out_div_" + queue);
    if (loggedin) {
        logged_in_div.style.display = "block";
        logged_out_div.style.display = "none";
    } else {
        logged_out_div.style.display = "block";
        logged_in_div.style.display = "none";
    }
}

/**
 * logs instructor in
 */
function helpmenow_login(login, queue) {
    var params = {
        "function" : "login",
        "login" : login,
        "queue" : queue,
    };
    helpmenow_call(params, function(xmlhttp) {
        if (xmlhttp.readyState == 4) {
            var response = JSON.parse(xmlhttp.responseText);
            console.debug(response);
            if (xmlhttp.status == 200) {
                helpmenow_toggle_login_display(response.login, queue);
            }
        }
    });
}

/**
 * Handles typing in the motd textarea. Limits the length to 140 characters.
 * When the enter key is pressed, we submit.
 *
 * @param event e keypress event
 * @return bool true indicates to the browser to treat the event as a normal
 *      keystroke
 */
function helpmenow_motd_textarea(e) {
    e = e || event;     // IE
    var edit_element = document.getElementById("helpmenow_motd_edit");

    // enter key
    if (e.keyCode === 13 && !e.ctrlKey) {
        helpmenow_motd(edit_element.value);
        return false;
    }

    // limit the length to 140
    if (edit_element.value.length >= 140) {
        return false;
    }

    return true;
}

/**
 * Handles submitting the motd
 */
function helpmenow_motd(motd) {
    var params = {
        "function" : "motd",
        "motd" : motd,
    };
    helpmenow_call(params, function(xmlhttp) {
        if (xmlhttp.readyState == 4) {
            var response = JSON.parse(xmlhttp.responseText);
            if (xmlhttp.status != 200) {
                helpmenow_motd(motd);
                return;
            }
            var edit_element = document.getElementById("helpmenow_motd_edit");
            var motd_element = document.getElementById("helpmenow_motd");
            edit_element.value = response.motd;
            motd_element.innerHTML = response.motd;
            helpmenow_toggle_motd(false);
        }
    });
}

/**
 * Function that handles refreshing the block
 */
function helpmenow_block_refresh() {
    var params = {
        "function" : "block",
    };
    helpmenow_call(params, function(xmlhttp) {
        if (xmlhttp.readyState==4) {
            var response = JSON.parse(xmlhttp.responseText);
            console.debug(response);
            if (xmlhttp.status==200) {

                var queue_div = document.getElementById("helpmenow_queue_div");
                queue_div.innerHTML = response.queues_html;

                if (typeof response.users_html === "undefined") {
                    return;
                }
                var users_div = document.getElementById("helpmenow_users_div");
                users_div.innerHTML = response.users_html;
            }
        }
    });
}
