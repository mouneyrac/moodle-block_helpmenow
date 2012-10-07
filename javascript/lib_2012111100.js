var helpmenow = (function () {
    /**
     * unique id
     */
    var id;

    /**
     * server url
     */
    var serverURL;

    /**
     * instance timeout in milliseconds; used to delete old cookies
     */
    var INSTANCE_TIMEOUT = 5000;

    /**
     * period at which we send all requests to server
     */
    var REQUEST_FREQ = 1000;

    /**
     * period at which we refresh to see if we have updates/need to take over
     */
    var REFRESH_FREQ = 500;

    /**
     * period at which we get block stuff
     */
    var BLOCK_UPDATE_FREQ = 5000;

    /**
     * local copy of our shared data
     */
    var sharedData = {};

    /**
     * holds our requests
     */
    var requestCallbacks = {};

    /**
     * whether or not we are in charge of updates
     */
    var isMaster = false;

    /**
     * loads storage
     */
    function loadStorage() {
        sharedData = JSON.parse($.cookie('helpmenow'));
        if (sharedData === null) {
            sharedData = {
                instances: {},
                requests: {},
                requestCount: 0
            };
            saveStorage();
        }
    }

    /**
     * saves storage
     */
    function saveStorage() {
        $.cookie('helpmenow', JSON.stringify(sharedData), { path: "/" });
    }

    /**
     * updates time on our cookie so other instances know we're still here
     */
    function checkIn() {
        sharedData.instances[id.toString()].lastUpdate = new Date().getTime();
        saveStorage();
    }

    /**
     * makes ajax call to fill requests
     */
    function getUpdates() {
        loadStorage();

        // put together our request
        var params = {
            requests: {}
        };
        var haveRequests = false;
        for (var key in sharedData.requests) {
            if (typeof sharedData.requests[key].response !== 'undefined') { continue; }
            params.requests[key] = sharedData.requests[key];
            haveRequests = true;
        }

        if (!haveRequests) {
            setTimeout(function () { getUpdates(); }, REQUEST_FREQ);
            return;
        }

        params = JSON.stringify(params);

        var xmlhttp;
        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState != 4) {
                return;
            }
            if (xmlhttp.status != 200) {
                setTimeout(function () { getUpdates(); }, REQUEST_FREQ);
            }
            var responses = JSON.parse(xmlhttp.responseText);
            if (typeof responses !== "undefined") {
                loadStorage();

                for (var i = 0; i < responses.length; i++) {
                    if (responses[i].id.toString() in sharedData.requests) {    // false if request was cancelled
                        sharedData.requests[responses[i].id.toString()].response = responses[i];
                        saveStorage();
                    }
                }
            }
            setTimeout(function () { getUpdates(); }, REQUEST_FREQ);
        }

        xmlhttp.open("POST", serverURL, true);
        xmlhttp.setRequestHeader("Accept", "application/json");
        xmlhttp.setRequestHeader("Content-type", "application/json");
        xmlhttp.send(params);
    }

    /**
     * checks for updates/see if we need to take over getting updates
     */
    function checkUpdates() {
        loadStorage();
        checkIn();

        if (!isMaster) {
            var takeOver = true;
            var cutoff = new Date().getTime() - INSTANCE_TIMEOUT;
            for (var key in sharedData.instances) {
                if (sharedData.instances[key].lastUpdate < cutoff) {
                    delete sharedData.instances[key];
                    saveStorage();
                    continue;
                }
                if (sharedData.instances[key].id < id) takeOver = false;
            }
            if (takeOver) {
                isMaster = true;
                setTimeout(function () { checkUpdates(); }, REFRESH_FREQ);
                blockUpdate();
                getUpdates();
                return;
            }
        }

        for (var key in sharedData.requests) {
            if (sharedData.requests[key].instanceId !== id) continue;
            if (typeof sharedData.requests[key].response === 'undefined') continue;

            requestCallbacks[sharedData.requests[key].id.toString()](sharedData.requests[key].response);
            delete requestCallbacks[sharedData.requests[key].id];
            delete sharedData.requests[key];
            saveStorage();
        }
        setTimeout(function () { checkUpdates(); }, REFRESH_FREQ);
    }

    function blockUpdate() {
        var params = {
            "function" : "block",
        };
        helpmenow.addRequest(params, function(response) {
            loadStorage();
            sharedData.block = response;
            sharedData.block.lastUpdate = new Date().getTime();
            saveStorage();
            setTimeout(function () { blockUpdate(); }, 5000);
        });
    }

    /**
     * public interface
     */
    return {
        init: function () {
            id = new Date().getTime();          // generate an id for ourself
            loadStorage();
            for (var key in sharedData.instances) {
                if (key == id) {
                    setTimeout(function () { helpmenow.init(); }, 1);
                    return;
                }
            }
            sharedData.instances[id.toString()] = {
                id: id,
                lastUpdate: new Date().getTime()
            };
            saveStorage();
            checkUpdates();
        },
        setServerURL: function (newServerURL) {
            serverURL = newServerURL;
        },
        addRequest: function (requestBody, callback) {
            loadStorage();
            var requestId = sharedData.requestCount++;
            requestCallbacks[requestId.toString()] = callback;
            sharedData.requests[requestId.toString()] = requestBody;
            sharedData.requests[requestId.toString()].id = requestId;
            sharedData.requests[requestId.toString()].instanceId = id;
            saveStorage();
            return requestId;
        },
        removeRequest: function (requestId) {
            loadStorage();
            delete requestCallbacks[requestId.toString()];
            delete sharedData.requests[requestId.toString()];
            saveStorage();
        },
        chime: function () {
            $("#helpmenow_chime").jPlayer("play");
            return;
        },
        getBlockData: function () {
            loadStorage();
            if (typeof sharedData.block === 'undefined') return;
            var block = {};
            for (var key in sharedData.block) {
                block[key] = sharedData.block[key];
            }
            if (sharedData.block.alert) {
                sharedData.block.alert = false;
                saveStorage();
            }
            return block;
        }
    };
}) ();
