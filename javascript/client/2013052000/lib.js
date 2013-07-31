var helpmenow = (function () {
    "use strict";

    var NAME = 'helpmenow',                             // client name for storage
        STORAGE_VERSION     = '2013052000',             // versioning storage
        PREFIX              = NAME + '_' + STORAGE_VERSION + '_',
        CHECKIN_FREQ        = 500,                      // period at which we checkIn
        UPDATE_FREQ         = 2000,                     // period at which we set update requests to the server
        BLOCK_FREQ          = 10000,
        PROCESS_FREQ        = 500,                      // period at which we set update requests to the server
        CLEANUP_FREQ        = 2000,                     // period for cleaning up
        REQUEST_FREQ        = 2000,                     // period at which we send all requests to server
        SOME_LARGE_NUMBER   = Math.pow(2, 40),          // max number for the random portion of the id

        id,
        serverURL,
        titleName,
        userName            = '',                       // user name and idnumber to be set by the server the instance is being viewed on
        userIdNumber        = 0,
        token               = 0,
        latestUpdate,
        lastBlockUpdate     = 0,
        multiClient         = true;                     // are we using localStorage to minimize the number of connections we make to the server?

    var storage = (function () {
        // if we don't have localStorage we're not going to use multiClient
        if (!window.localStorage) {
            disableMultiClient();
        }

        // storage interface
        return {
            set: function (id, obj) {
                try {
                    localStorage.setItem(id, JSON.stringify(obj));
                } catch (e) {
                    disableMultiClient();
                }
            },
            get: function (id) {
                var item;
                try {
                    item = localStorage.getItem(id);
                    if (item !== null) { item = JSON.parse(item); }
                } catch (e) {
                    disableMultiClient();
                    item = null;
                }
                return item;
            },
            getType: function (type) {
                var records = {};
                try {
                    records = {};
                    for (var i = 0; i < localStorage.length; i++) {
                        var key = localStorage.key(i);
                        if (key.indexOf(PREFIX) !== 0) { continue; }

                        var item = storage.get(key);
                        if (item.type === type) { records[item.id] = item; }
                    }
                } catch (e) {
                    disableMultiClient();
                    records = {};
                }
                return records;
            },
            remove: function (id) {
                try {
                    localStorage.removeItem(id);
                } catch (e) {
                    disableMultiClient();
                }
            },
            cleanUp: function () {
                if (!multiClient) { return; }
                if (localStorage.length !== 0) {
                    var cutoff = new Date().getTime() - CLEANUP_FREQ;
                    for (var i = 0; i < localStorage.length; i++) {
                        var key = localStorage.key(i);
                        if (key.indexOf(NAME) !== 0) { continue; }

                        var item = storage.get(key);
                        if (item === null) { storage.remove(key); }

                        if (typeof item.time === 'undefined' || item.time === null || item.time < cutoff) {
                            storage.remove(key);
                        }
                    }
                }
                setTimeout(function () { storage.cleanUp(); }, CLEANUP_FREQ);
            }
        };
    }) ();

    function disableMultiClient() {
        multiClient = false;
        BLOCK_FREQ = 30000;
    }

    function checkIn() {
        if (!multiClient) { return; }
        helpmenow.sharedData.time = new Date().getTime();
        storage.set(PREFIX + id, helpmenow.sharedData);
        setTimeout(function () { checkIn(); }, CHECKIN_FREQ);
    }

    function getUpdates() {
        var haveRequests = false;
        var params = {
            'requests': {}
        };
        if (multiClient) {
            var lowestId;
            var records = storage.getType('instance');
            for (var key in records) {
                if (!records.hasOwnProperty(key)) { continue; }
                if (typeof lowestId === 'undefined' || records[key].id < lowestId) { lowestId = records[key].id; }
                if (records[key].isBlock) { continue; }
                haveRequests = true;
                params.requests[records[key].id] = {
                    'id': records[key].id,
                    'function': 'refresh',
                    session: records[key].session,
                    'useridnumber': userIdNumber,
                    'token': token,
                    'last_message': records[key].lastMessage
                };
            }
            if (lowestId !== id) {
                setTimeout(function () { getUpdates(); }, UPDATE_FREQ);
                return;
            }
        } else if (!helpmenow.sharedData.isBlock) {
            haveRequests = true;
            params.requests[id] = {
                'id': id,
                'function': 'refresh',
                session: helpmenow.sharedData.session,
                'useridnumber': userIdNumber,
                'token': token,
                'last_message': helpmenow.sharedData.lastMessage
            };
        }
        var now = new Date().getTime();
        if ((multiClient || helpmenow.sharedData.isBlock) && now > lastBlockUpdate + BLOCK_FREQ) {
            haveRequests = true;
            lastBlockUpdate = now;
            params.requests.block = {
                'id': 'block',
                'function': 'block',
                'useridnumber': userIdNumber,
                'token': token
            };
        }
        if (!haveRequests) {
            setTimeout(function () { getUpdates(); }, UPDATE_FREQ);
            return;
        }
        helpmenow.ajax(params, function (xmlhttp) {
            if (xmlhttp.readyState !== 4) { return; }
            if (xmlhttp.status !== 200) {
                setTimeout(function () { getUpdates(); }, REQUEST_FREQ);
                return;
            }
            try {
                var responses = JSON.parse(xmlhttp.responseText);
                if (typeof responses !== "undefined") {
                    var now = new Date().getTime();
                    for (var i = 0; i < responses.length; i++) {
                        responses[i].type = 'response';
                        responses[i].time = now;
                        if (multiClient) {
                            storage.set(PREFIX + responses[i].id + '_response', responses[i]);
                        } else if (responses[i].id === id) {
                            latestUpdate = responses[i];
                        } else if (responses[i].id === 'block' && helpmenow.sharedData.isBlock === true) {
                            latestUpdate = responses[i];
                        }
                    }
                }
                setTimeout(function () { getUpdates(); }, REQUEST_FREQ);
            } catch (e) {
                setTimeout(function () { getUpdates(); }, REQUEST_FREQ);
            }
        });
    }

    function checkUpdates() {
        if (multiClient) {
            if (helpmenow.sharedData.isBlock) {
                latestUpdate = storage.get(PREFIX + 'block_response');
            } else {
                latestUpdate = storage.get(PREFIX + id + '_response');
            }
        }
        helpmenow.processUpdates(latestUpdate);
        setTimeout(function () { checkUpdates(); }, PROCESS_FREQ);
    }

    /**
     * initialize block
     */
    function initBlock() {
        var params = {
            'requests': {
                'init': {
                    'id': 'init',
                    'function': 'init',
                    'useridnumber': userIdNumber,
                    'username': userName,
                    'token': token
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
                        if (response[i].office_loggedin) {
                            helpmenow.toggleLoginDisplay(response[i].office_loggedin);
                        }
                    }
                }

            } catch (e) {
                var links_div = document.getElementById("helpmenow_links_div");
                links_div.innerHTML += "An Error occurred while initializing the block " + e;
            }
        });
    }

    $(document).ready(function () {     // call our init function onLoad
        helpmenow.init();
    });

    /**
     * public interface
     */
    return {
        init: function () {
            id = new Date().getTime().toString() + (Math.floor(Math.random() * SOME_LARGE_NUMBER)).toString();
            helpmenow.sharedData.id = id;
            helpmenow.sharedData.type = 'instance';
            checkIn();
            storage.cleanUp();
            setTimeout(function () { getUpdates(); }, 0);
            setTimeout(function () { checkUpdates(); }, 0);
            initBlock();
        },
        setServerURL: function (newServerURL) {
            serverURL = newServerURL;
        },
        setTitleName: function (newTitleName) {
            titleName = newTitleName;
        },
        getTitleName: function () {
            return titleName;
        },
        setUser: function (newUserIdNumber, newUserName) {
            userIdNumber = newUserIdNumber;
            userName = newUserName;
        },
        setToken: function (newToken) {
            token = newToken;
        },
        getUserName: function () {
            return userName;
        },
        getUserIdNumber: function () {
            return userIdNumber;
        },
        chime: function () {
            $("#helpmenow_chime").jPlayer("play");
            return;
        },
        ajax: function (request, callback) {
            request = JSON.stringify(request);

            var xmlhttp;
            if (window.XMLHttpRequest) {    // IE7+, Firefox, Chrome, Opera, Safari
                xmlhttp = new XMLHttpRequest();
            } else {                        // IE6, IE5
                xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
            }
            xmlhttp.onreadystatechange = function() {
                callback(xmlhttp);
            };
            xmlhttp.open("POST", serverURL, true);
            xmlhttp.setRequestHeader("Accept", "application/json");
            xmlhttp.setRequestHeader("Content-type", "application/json");
            xmlhttp.send(request);
        },
        sharedData: {},
        /**
         * toggles logged in status display
         */
        toggleLoginDisplay: function (loggedin) {
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

   };
}) ();
