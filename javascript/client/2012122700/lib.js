var helpmenow = (function () {
    /**
     * unique id
     */
    var id;

    /**
     * prefix
     */
    var PREFIX = 'helpmenow_2012122700_';

    /**
     * takeOver timeout in milliseconds
     */
    var TAKEOVER_DELAY = 5000;

    /**
     * period at which we send all requests to server
     */
    var REQUEST_FREQ = 2000;

    /**
     * period at which we checkIn
     */
    var CHECKIN_FREQ = 500;

    /**
     * period at which we get block stuff
     */
    var BLOCK_UPDATE_FREQ = 10000;

    /**
     * period for cleaning up
     */
    var CLEANUP_FREQ = 60000;

    /**
     * max number for the random portion of the id
     */
    var SOME_LARGE_NUMBER = Math.pow(2,40);

    /**
     * server url
     */
    var serverURL;

    /**
     * local copy of our shared data
     */
    var sharedData = {};

    /**
     * count of requests
     */
    var requestCount = 0;

    /**
     * holds our requests
     */
    var requestCallbacks = {};

    /**
     * whether or not we are in charge of updates
     */
    var isMaster = false;

    /**
     * whether or not to get block updates, this really only matters for when
     * we don't have localStorage support
     */
    var doBlockUpdates = true;

    /**
     * if we're a block
     */
    var isBlock = false;

    /**
     * setTimeout for seeing if we need to takeover
     */
    var takeOverTimer;

    /**
     * array of requests to send to the server
     */
    var requests = {};

    /**
     * do we have events?
     */
    var noEvents = false;

    /**
     * updates time on our cookie so other instances know we're still here
     */
    function checkIn() {
        localStorage.setItem(PREFIX + id, JSON.stringify({
            'id': id,
            time: new Date().getTime(),
            'type': 'instance',
            'isBlock': isBlock
        }));

        if (noEvents) {
            getResponses();
        }

        setTimeout(function () { checkIn(); }, CHECKIN_FREQ);
    }

    /**
     * get responses from localStorage
     */
    function getResponses() {
        var responses = getStorage('response');
        for (var key in responses) {
            if (responses[key].instanceId !== id) continue;
            processResponse(responses[key]);
            localStorage.removeItem(PREFIX + responses[key].id + '_response');
        }
    }

    /**
     * get requests in localStorage and put them in requests
     */
    function getRequests() {
        var records = getStorage('request');

        // filter out requests that have responses
        for (var key in records) {
            var item = localStorage.getItem(PREFIX + records[key].id + '_response');
            if (item === null) {
                requests[records[key].id] = records[key];
            }
        }
    }

    /**
     * makes ajax call to fill requests
     */
    function getUpdates() {
        if (noEvents) {
            getRequests();
        }

        var params = {
            'requests': {}
        };

        var haveRequests = false;
        for (var key in requests) {
            params.requests[key] = requests[key];
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
            try {
                var responses = JSON.parse(xmlhttp.responseText);
                if (typeof responses !== "undefined") {
                    var now = new Date().getTime();
                    for (var i = 0; i < responses.length; i++) {
                        if (responses[i].instanceId === id) {
                            processResponse(responses[i]);
                            if (noEvents) {
                                // if we don't do this we have request records that
                                // don't get deleted when the master changes
                                localStorage.removeItem(PREFIX + responses[i].id);
                            }
                            delete requests[responses[i].id];
                        } else {
                            responses[i].type = 'response';
                            responses[i].time = now;
                            localStorage.setItem(PREFIX + responses[i].id + '_response', JSON.stringify(responses[i]));
                            localStorage.removeItem(PREFIX + responses[i].id);
                            delete requests[responses[i].id];
                        }
                    }
                }
                setTimeout(function () { getUpdates(); }, REQUEST_FREQ);
            } catch (e) {
                sendError(e.message, xmlhttp.responseText);
            }
        }
        xmlhttp.open("POST", serverURL, true);
        xmlhttp.setRequestHeader("Accept", "application/json");
        xmlhttp.setRequestHeader("Content-type", "application/json");
        xmlhttp.send(params);
    }

    /**
     * send error to the server so we can debug those pesky intermittent client issues
     */
    function sendError(errorMessage, errorDetails) {
        var params = {
            'error': errorMessage,
            'details': errorDetails,
        };
        params = JSON.stringify(params);

        var xmlhttp;
        xmlhttp = new XMLHttpRequest();
        xmlhttp.open("POST", serverURL, true);
        xmlhttp.setRequestHeader("Accept", "application/json");
        xmlhttp.setRequestHeader("Content-type", "application/json");
        xmlhttp.send(params);
    }

    /**
     * see if we need to take over
     */
    function takeOver() {
        var cutoff = new Date().getTime() - TAKEOVER_DELAY;
        var instances = getInstances();
        var doIt = true;
        for (var key in instances) {
            if (instances[key].time < cutoff) {
                localStorage.removeItem(PREFIX + instances[key].id);
                continue;
            }
            if (instances[key].id < id) {
                doIt = false;
                break;
            }
        }
        if (doIt) {
            isMaster = true;
            if (doBlockUpdates) {
                setTimeout(function () { blockUpdate(); }, 0);
            }
            if (!noEvents) {
                getRequests();  // there might be some requests in localStorage
            }
            setTimeout(function () { getUpdates(); }, 0);
        } else {
            takeOverTimer = setTimeout(function () { takeOver(); }, TAKEOVER_DELAY);
        }
    }

    function blockUpdate() {
        var params = {
            "function" : "block"
        };
        helpmenow.addRequest(params, function(response) {
            response.time = new Date().getTime();
            response.type = 'block';
            localStorage.setItem(PREFIX + 'block', JSON.stringify(response));
            setTimeout(function () { blockUpdate(); }, BLOCK_UPDATE_FREQ);
        });
    }

    /**
     * get instance objects from storage
     */
    function getInstances() {
        return getStorage('instance');
    }

    function getStorage(type) {
        if (localStorage.length === 0) return;
        var records = {};
        for (var i = 0; i < localStorage.length; i++) {
            var key = localStorage.key(i);
            if (key.indexOf(PREFIX) !== 0) continue;

            var item = localStorage.getItem(key);
            if (item === null) continue;

            item = JSON.parse(item);
            if (item.type === type) records[item.id] = item;
        }
        return records;
    }

    /**
     * storage event handler
     */
    function handleStorageEvent(e) {
        if (!e) e = window.event;
        if (e.key.indexOf(PREFIX) !== 0) return;
        if (e.newValue === null) return;
        var item = JSON.parse(e.newValue);
        if (item.type === 'instance') {
            if (item.id < id) {
                clearTimeout(takeOverTimer);
                takeOverTimer = setTimeout(function () { takeOver(); }, TAKEOVER_DELAY);
            }
        } else if (item.type === 'response') {
            if (item.instanceId !== id) return;
            processResponse(item);
            localStorage.removeItem(e.key);
        } else if (isMaster && item.type === 'request') {
            requests[item.id] = item;
        }
    }

    /**
     * process responses to our requests
     */
    function processResponse(response) {
        requestCallbacks[response.id](response);
        delete requestCallbacks[response.id];
    }

    /**
     * clean up old records
     */
    function cleanUp() {
        if (localStorage.length === 0) return;
        var records = {};
        var cutoff = new Date().getTime() - CLEANUP_FREQ;
        for (var i = 0; i < localStorage.length; i++) {
            var key = localStorage.key(i);
            if (key.indexOf(PREFIX) !== 0) continue;

            var item = localStorage.getItem(key);
            if (item === null) localStorage.removeItem(key);

            item = JSON.parse(item);
            if (item.time < cutoff) localStorage.removeItem(key);
        }

        setTimeout(function () { cleanUp(); }, CLEANUP_FREQ);
    }

    /**
     * Handle non-modern browsers
     */
    if (!('localStorage' in window)) {
        window.localStorage = {     // emulate localStorage api
            _data       : {},
            setItem     : function(id, val) { return this._data[id] = String(val); },
            getItem     : function(id) { return this._data.hasOwnProperty(id) ? this._data[id] : undefined; },
            removeItem  : function(id) { return delete this._data[id]; },
            clear       : function() { return this._data = {}; }
        };

        // do some things a little differently...
        BLOCK_UPDATE_FREQ = 30000;  // longer delay, as multiple clients will not be sharing blockupdates
        doBlockUpdates = false;
        // todo: there might be more here depending on what else we change...
    }

    if (window.addEventListener) {
        window.addEventListener("storage", handleStorageEvent, false);
    } else {
        noEvents = true;    // ie8 storage events plain don't work
    };

    /**
     * public interface
     */
    return {
        init: function () {
            // start by generating an id that is a combination of the current time and some large random number
            id = new Date().getTime().toString() + (Math.floor(Math.random() * SOME_LARGE_NUMBER)).toString();

            // start cleanup, checkin, and takeover timers
            setTimeout(function () { cleanUp(); }, 0);
            setTimeout(function () { checkIn(); }, 0);
            takeOverTimer = setTimeout(function () { takeOver(); }, 100);   // wait a short time here
        },
        setServerURL: function (newServerURL) {
            serverURL = newServerURL;
        },
        addRequest: function (requestBody, callback) {
            requestId = id + (requestCount++).toString();
            requestCallbacks[requestId] = callback;

            requestBody.id = requestId;
            requestBody.instanceId = id;

            if (isMaster) {
                requests[requestBody.id] = requestBody;
            } else {
                requestBody.time = new Date().getTime();
                requestBody.type = 'request';
                localStorage.setItem(PREFIX + requestId, JSON.stringify(requestBody));
            }
        },
        chime: function () {
            $("#helpmenow_chime").jPlayer("play");
            return;
        },
        getBlockData: function () {
            isBlock = true;
            if (doBlockUpdates === false) {
                doBlockUpdates = true;
                setTimeout(function () { blockUpdate(); }, 0);
            }

            var block = localStorage.getItem(PREFIX + 'block');
            if ((typeof block === 'undefined') || block === null) return;

            block = JSON.parse(block);

            if (block.time < ((new Date().getTime()) - CLEANUP_FREQ)) {
                return;
            }

            if (block.alert) {
                var instances = getInstances();
                for (var key in instances) {
                    if (instances[key].isBlock && instances[key].id < id) {
                        block.alert = false;
                        break;
                    }
                }
            }
            return block;
        }
    };
}) ();
