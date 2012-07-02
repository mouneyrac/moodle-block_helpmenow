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
function helpmenow_toggle_login_display(loggedin) {
    var logged_in_div = document.getElementById("helpmenow_logged_in_div_0");
    var logged_out_div = document.getElementById("helpmenow_logged_out_div_0");
    if (loggedin) {
        logged_in_div.style.display = "block";
        logged_out_div.style.display = "none";
    } else {
        logged_out_div.style.display = "block";
        logged_in_div.style.display = "none";
    }
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
            if (xmlhttp.status != 200) {
                return;
            }
            var response = JSON.parse(xmlhttp.responseText);
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
            if (xmlhttp.status==200) {
                var response = JSON.parse(xmlhttp.responseText);
                // console.debug(response);

                var queue_div = document.getElementById("helpmenow_queue_div");
                queue_div.innerHTML = response.queues_html;

                var last_refresh_div = document.getElementById("helpmenow_last_refresh_div");
                last_refresh_div.innerHTML = response.last_refresh;

                if (response.pending) {
                    var helpmenow_chime = document.getElementById("helpmenow_chime");
                    helpmenow_chime.Play();
                }

                if (typeof response.isloggedin !== "undefined") {
                    helpmenow_toggle_login_display(response.isloggedin);
                }

                if (typeof response.meetingid !== "undefined") {
                    var meetingid_div = document.getElementById("helpmenow_meetingid_div");
                    if (meetingid_div) {
                        meetingid_div.innerHTML = "Meeting ID #" + response.meetingid;
                    }
                }

                if (typeof response.users_html !== "undefined") {
                    var users_div = document.getElementById("helpmenow_users_div");
                    users_div.innerHTML = response.users_html;
                }
            }
        }
    });
}
