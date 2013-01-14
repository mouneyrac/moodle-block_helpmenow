var helpmenowBlock = (function () {
    "use strict";

    var lastUpdate = 0;     // last time we got block data

    /**
     * toggles logged in status display
     */
    function toggleLoginDisplay(loggedin) {
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
     * refresh block
     */
    function refresh() {
        var response = helpmenow.getBlockData();
        if (typeof response === "undefined") {               // don't have block data yet
            setTimeout(function () { refresh(); }, 500);
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
                    $.titleAlert('(' + response.pending + ') VLACS Communicator', {
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

                if (typeof response.users_html !== "undefined") {
                    var users_div = document.getElementById("helpmenow_users_div");
                    users_div.innerHTML = response.users_html;
                }
            } catch (e) {
            }
        }
        setTimeout(function () { refresh(); }, 500);
    }

    /**
     * Handles submitting the motd
     */
    function submitMOTD(motd) {
        var params = {
            "function" : "motd",
            "motd" : motd
        };
        helpmenow.addRequest(params, function(response) {
            if (typeof response.error === 'undefined') {
                var edit_element = document.getElementById("helpmenow_motd_edit");
                var motd_element = document.getElementById("helpmenow_motd");
                edit_element.value = response.motd;
                motd_element.innerHTML = response.motd;
                helpmenowBlock.toggleMOTD(false);
            }
        });
    }

    /**
     * public interface
     */
    return {
        init: function () {
            helpmenow.init();
            refresh();
        },

        /**
         * toggles motd editing
         *
         * @param bool edit  true indicates edit mode, false is display mode
         */
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
            if (edit_element.value.length >= 140) {
                return false;
            }

            return true;
        }
    };
}) ();

$(document).ready(function () {
    "use strict";
    helpmenowBlock.init();
});
