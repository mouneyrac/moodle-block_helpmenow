var helpmenow = (function (my) {
    "use strict";

    var lastUpdate = 0;     // last time we got block data

    my.sharedData.isBlock = true;

    /**
     * initialize block
     */
    function initBlock() {
        var params = {
            'requests': {
                'init': {
                    'id': 'init',
                    'function': 'init',
                    'useridnumber': helpmenow.getUserIdNumber(),
                    'username': helpmenow.getUserName(),
                    'token': helpmenow.getToken()
                }
            }
        };
        helpmenow.ajax(params, function (xmlhttp) {
            if (xmlhttp.readyState !== 4) { return; }
            try {
                if (xmlhttp.status !== 200) { throw "status: " + xmlhttp.status; }
                var response = JSON.parse(xmlhttp.responseText);
                if (typeof response.error !== 'undefined') { throw "error: " + response.error; }

                for (var i = 0; i < response.length; i++) {
                    if (response[i].links_html) {
                        var links_div = document.getElementById("helpmenow_links_div");
                        links_div.innerHTML += response[i].links_html;
                    }
                    if (response[i].user_warning) {
                        var warning_div = document.getElementById("helpmenow_user_warning_div");
                        warning_div.innerHTML = response[i].user_warning;
                    }
                    if (response[i].user_heading) {
                        var user_heading_div = document.getElementById("helpmenow_users_heading_div");
                        user_heading_div.innerHTML = response[i].user_heading;
                    }
                    if (response[i].show_office) {
                        var office_div = document.getElementById("helpmenow_office");
                        office_div.style.display = "block";
                        if (response[i].office_motd) {
                            var office_motd_div = document.getElementById("helpmenow_motd");
                            office_motd_div.innerHTML = response[i].office_motd;
                        }
                        helpmenow.toggleLoginDisplay(response[i].office_loggedin);
                    }
                }

            } catch (e) {
                var links_div = document.getElementById("helpmenow_links_div");
                links_div.innerHTML += "An Error occurred while initializing the block " + e;
            }
        });
    }


    /**
     * extend init
     */
    var oldInit = my.init;
    my.init = function () {
        oldInit();
        initBlock();
    }

    /**
     * refresh block
     */
    my.processUpdates = function (response) {
        if (typeof response === "undefined" || response === null) {               // don't have block data yet
            return;
        }
        if (response.time > lastUpdate) {
            $('#loading').hide();
            lastUpdate = response.time;
            var queue_div = document.getElementById("helpmenow_queue_div");
            queue_div.innerHTML = response.queues_html;

            var last_refresh_div = document.getElementById("helpmenow_last_refresh_div");
            last_refresh_div.innerHTML = response.last_refresh;

            if (response.alert) {
                if (response.pending && !$(document)[0].hasFocus()) {
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
                    helpmenow.toggleLoginDisplay(response.isloggedin);
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
                    'useridnumber': helpmenow.getUserIdNumber(),
                    'token': helpmenow.getToken(),
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
            if (edit_element.value.length >= 140) {
                return false;
            }

            return true;
        }
    };
    return my;
}) (helpmenow);
