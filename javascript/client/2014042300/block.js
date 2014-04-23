var helpmenow = (function (my) {
    "use strict";

    var lastUpdate = 0;     // last time we got block data

    my.sharedData.isBlock = true;

    /**
     * toggles logged in status display
     */
    function toggleLoginDisplay(loggedin) {
        var logged_in_div = document.getElementById("helpmenow_logged_in_div_0");
        var logged_out_div = document.getElementById("helpmenow_logged_out_div_0");
        if (logged_in_div === null || logged_out_div === null) {
            return;
        }
        if (loggedin) {
            logged_in_div.style.display = "block";
            logged_out_div.style.display = "none";
        } else {
            logged_out_div.style.display = "block";
            logged_in_div.style.display = "none";
        }
    }

    /**
     * refresh block
     */
    my.processUpdates = function (response) {
        if (typeof response === "undefined" || response === null) {               // don't have block data yet
            return;
        }
        if (response.time > lastUpdate) {
            lastUpdate = response.time;
            var queue_div = document.getElementById("helpmenow_queue_div");
            queue_div.innerHTML = response.queues_html;

            var last_refresh_div = document.getElementById("helpmenow_last_refresh_div");
            last_refresh_div.innerHTML = response.last_refresh;

            if (response.alert) {
                if (response.pending && !$(document)[0].hasFocus()) {
                    //$.titleAlert('"' + response.title_flash + '"', {
                    var titleName = helpmenow.getTitleName();
                    $.titleAlert('(' + response.pending + ') ' + titleName, {
                        interval:1000
                    });
                }
                helpmenow.chime();
            }

            // if we don't try-catch here we break when coming back
            // from being logged in as an instructor
            try {
                if (typeof response.isloggedin !== "undefined") {
                    toggleLoginDisplay(response.isloggedin);
                }

                if (typeof response.users_html !== "undefined" && response.users_html.length > 0) {
                    var users_div = document.getElementById("helpmenow_users_div");
                    users_div.innerHTML = response.users_html;
                }
            } catch (e) {
                console.log('block processUpdates exception:' + e.message);
            }
        }
    };

    /**
     * Handles submitting the motd
     */
    function submitMOTD(motd) {
        var params = {
            'requests': {
                'motd': {
                    'id': 'motd',
                    'function': 'motd',
                    'motd': motd
                }
            }
        };
        helpmenow.ajax(params, function(xmlhttp) {
            if (xmlhttp.readyState !== 4) { return; }
            if (xmlhttp.status !== 200) { return; }      // todo: maybe have a spot on the block that displays errors if they occur
            var response = JSON.parse(xmlhttp.responseText);
            if (typeof response.error === 'undefined') {
                var edit_element = document.getElementById("helpmenow_motd_edit");
                var motd_element = document.getElementById("helpmenow_motd");
                edit_element.value = response[0].motd;
                motd_element.innerHTML = response[0].motd;
                helpmenow.block.toggleMOTD(false);
            }
        });
    }

    my.block = {
        toggleMOTD: function (edit) {
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
        },

        /**
         * Handles typing in the motd textarea. Limits the length to 140 characters.
         * When the enter key is pressed, we submit.
         *
         * @param event e keypress event
         * @return bool true indicates to the browser to treat the event as a normal
         *      keystroke
         */
        keypressMOTD: function (e) {
            e = e || event;     // IE
            var edit_element = document.getElementById("helpmenow_motd_edit");

            // enter key
            if (e.keyCode === 13 && !e.ctrlKey) {
                submitMOTD(edit_element.value);
                return false;
            }

            // limit the length to 140
            var char = helpmenow.getChar(e);
            if (char && edit_element.value.length >= 140) {
                return false;
            }

            return true;
        }
    };
    return my;
}) (helpmenow);
