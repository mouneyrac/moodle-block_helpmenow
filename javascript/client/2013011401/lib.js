var helpmenow = (function () {
    "use strict";

    /**
     * the first half here are "defines", the second half are regular variables
     */
    var NAME = 'helpmenow',                     // client name for storage
        STORAGE_VERSION = '2013011400',         // versioning storage
        PREFIX = NAME + '_' + STORAGE_VERSION + '_',
        TAKEOVER_DELAY = 5000,                  // takeOver timeout in milliseconds
        REQUEST_FREQ = 2000,                    // period at which we send all requests to server
        CHECKIN_FREQ = 500,                     // period at which we checkIn
        BLOCK_UPDATE_FREQ = 10000,              // period at which we get block stuff
        CLEANUP_FREQ = 60000,                   // period for cleaning up
        SOME_LARGE_NUMBER = Math.pow(2, 40),    // max number for the random portion of the id

        id,                                     // unique id
        serverURL,                              // server url
        requestCount = 0,                       // count of requests, used to id requests
        requestCallbacks = {},                  // holds our requests
        isMaster = false,                       // whether or not we are in charge of updates
        doBlockUpdates = true,                  // whether or not to get block updates
        isBlock = false,                        // if we're a block
        takeOverTimer,                          // setTimeout for seeing if we need to takeover
        requests = {};                          // array of requests to send to the server

    /**
     * updates time on our cookie so other instances know we're still here
     */
    function checkIn() {
        storage.set(PREFIX + id, {
            'id': id,
            time: new Date().getTime(),
            'type': 'instance',
            'isBlock': isBlock
        });

        /**
         * clients without events will need to periodically check for responses
         * so this seems like as good a time as any
         */
        if (!storage.haveEvents()) {
            getResponses();
        }

        setTimeout(function () { checkIn(); }, CHECKIN_FREQ);
    }

    /**
     * get responses from localStorage
     */
    function getResponses() {
        var responses = storage.getType('response');
        for (var key in responses) {
            if (responses.hasOwnProperty(key)) {
                if (responses[key].instanceId !== id) { continue; }
                processResponse(responses[key]);
                storage.remove(PREFIX + responses[key].id + '_response');
            }
        }
    }

    /**
     * get requests in localStorage and put them in requests
     */
    function getRequests() {
        var records = storage.getType('request');
        for (var key in records) {  // filter out requests that have responses
            if (records.hasOwnProperty(key)) {
                var item = storage.get(PREFIX + records[key].id + '_response');
                if (item === null) {
                    if (records[key].instanceId === id) {
                        storage.remove(PREFIX + records[key].id);
                    }
                    requests[records[key].id] = records[key];
                }
            }
        }
    }

    /**
     * makes ajax call to fill requests
     */
    function getUpdates() {
        if (!storage.haveEvents()) { getRequests(); }   // if we don't have events we need to get all the requests now

        var params = {
            'requests': {}
        };

        var haveRequests = false;
        for (var key in requests) {
            if (requests.hasOwnProperty(key)) {
                params.requests[key] = requests[key];
                haveRequests = true;
            }
        }
        if (!haveRequests) {
            setTimeout(function () { getUpdates(); }, REQUEST_FREQ);
            return;
        }

        params = JSON.stringify(params);

        var xmlhttp;
        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState !== 4) {
                return;
            }
            if (xmlhttp.status !== 200) {
                setTimeout(function () { getUpdates(); }, REQUEST_FREQ);
                return;
            }
            try {
                var responses = JSON.parse(xmlhttp.responseText);
                if (typeof responses !== "undefined") {
                    var now = new Date().getTime();
                    for (var i = 0; i < responses.length; i++) {
                        if (responses[i].instanceId === id) {
                            processResponse(responses[i]);
                        } else {
                            responses[i].type = 'response';
                            responses[i].time = now;
                            storage.set(PREFIX + responses[i].id + '_response', responses[i]);
                            storage.remove(PREFIX + responses[i].id);
                            delete requests[responses[i].id];
                        }
                    }
                }
                setTimeout(function () { getUpdates(); }, REQUEST_FREQ);
            } catch (e) {
                sendError(e.message, xmlhttp.responseText);
            }
        };
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
            'details': errorDetails
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
    function checkTakeOver() {
        var cutoff = new Date().getTime() - TAKEOVER_DELAY;
        var instances = storage.getType('instance');
        var doTakeOver = true;
        for (var key in instances) {
            if (instances.hasOwnProperty(key)) {
                if (instances[key].time < cutoff) {
                    storage.remove(PREFIX + instances[key].id);
                    continue;
                }
                if (instances[key].id < id) {
                    doTakeOver = false;
                    break;
                }
            }
        }
        if (doTakeOver) {
            takeOver();
        } else {
            takeOverTimer = setTimeout(function () { checkTakeOver(); }, TAKEOVER_DELAY);
        }
    }

    /**
     * perform takeover
     */
    function takeOver() {
        isMaster = true;
        if (doBlockUpdates) {
            setTimeout(function () { blockUpdate(); }, 0);
        }
        getRequests();  // there might be some requests in localStorage
        setTimeout(function () { getUpdates(); }, 0);
    }

    function blockUpdate() {
        var params = {
            "function" : "block"
        };
        helpmenow.addRequest(params, function(response) {
            if (typeof response.error === 'undefined') {
                response.time = new Date().getTime();
                response.type = 'block';
                storage.set(PREFIX + 'block', response);
            }
            setTimeout(function () { blockUpdate(); }, BLOCK_UPDATE_FREQ);
        });
    }

    /**
     * process responses to our requests
     */
    function processResponse(response) {
        try {
            requestCallbacks[response.id](response);
        } catch (e) {
            // sendError(e.message, JSON.stringify(response));
        }
        delete requests[response.id];
        delete requestCallbacks[response.id];
    }

    /**
     * storage object
     */
    var storage = (function () {
        /**
         * do we have events?
         */
        var events = true;

        /**
         * are we faking localstorage?
         */
        var fakingStorage = false;

        /**
         * storage event handler
         */
        function handleStorageEvent(eventObject) {
            if (!eventObject) { eventObject = window.event; }
            if (eventObject.key.indexOf(PREFIX) !== 0) { return; }  // not our stuff
            if (!eventObject.newValue) { return; }  // no need to handle deletes right now
            var storageItem = JSON.parse(eventObject.newValue);
            if (storageItem.type === 'instance') {
                if (storageItem.id < id) {
                    clearTimeout(takeOverTimer);
                    takeOverTimer = setTimeout(function () { checkTakeOver(); }, TAKEOVER_DELAY);
                }
            } else if (storageItem.type === 'response') {
                if (storageItem.instanceId !== id) { return; }      // not a response for us
                processResponse(storageItem);
                storage.remove(eventObject.key);
            } else if (isMaster && storageItem.type === 'request') {
                requests[storageItem.id] = storageItem;
            }
        }

        function fakeStorage() {
            fakingStorage = true;

            window.localStorage = {     // emulate localStorage api
                _data       : {},
                setItem     : function(id, val) { return this._data[id] = String(val); },
                getItem     : function(id) { return this._data.hasOwnProperty(id) ? this._data[id] : undefined; },
                removeItem  : function(id) { return delete this._data[id]; },
                clear       : function() { return this._data = {}; }
            };

            /**
             * because clients won't be sharing data, lets only update the
             * block if we have to, and with a longer period
             */
            BLOCK_UPDATE_FREQ = 30000;
            doBlockUpdates = false;

            if (typeof id === 'undefined') {
                takeOver();
            }
        }

        /**
         * Handle non-modern browsers (fake localstorage)
         */
        if (!('localStorage' in window)) { fakeStorage(); }

        /**
         * use events where (well) supported
         */
        if (window.addEventListener) {
            window.addEventListener("storage", handleStorageEvent, false);
        } else {
            events = false;
        }

        /**
         * storage interface
         */
        return {
            set: function (id, obj) {
                try {
                    if (!fakingStorage) { localStorage.removeItem(id); }
                    localStorage.setItem(id, JSON.stringify(obj));
                } catch (e) {
                    if (!fakingStorage) {
                        fakeStorage();
                        localStorage.setItem(id, JSON.stringify(obj));
                    }
                }
            },
            get: function (id) {
                var item;
                try {
                    item = localStorage.getItem(id);
                    if (item !== null) { item = JSON.parse(item); }
                } catch (e) {
                    if (!fakingStorage) { fakeStorage(); }
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
                    if (!fakingStorage) { fakeStorage(); }
                    records = {};
                }
                return records;
            },
            remove: function (id) {
                try {
                    localStorage.removeItem(id);
                } catch (e) {
                    if (!fakingStorage) { fakeStorage(); }
                }
            },
            haveEvents: function () {
                return events;
            },
            cleanUp: function () {
                if (fakingStorage) { return; }  // if we're faking we don't need to clean up now or ever
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

    /**
     * public interface
     */
    return {
        init: function () {
            // cleanup immediately so we don't hit the quota
            storage.cleanUp();

            // start by generating an id that is a combination of the current time and some large random number
            id = new Date().getTime().toString() + (Math.floor(Math.random() * SOME_LARGE_NUMBER)).toString();

            // start checkin, and takeover timers
            setTimeout(function () { checkIn(); }, 0);
            if (!isMaster) {
                takeOverTimer = setTimeout(function () { checkTakeOver(); }, 100);   // wait a short time here
            }
        },
        setServerURL: function (newServerURL) {
            serverURL = newServerURL;
        },
        addRequest: function (requestBody, callback) {
            var requestId = id + '_' + (requestCount++).toString();
            requestCallbacks[requestId] = callback;

            requestBody.id = requestId;
            requestBody.instanceId = id;

            requests[requestBody.id] = requestBody;
            if (!isMaster) {
                requestBody.time = new Date().getTime();
                requestBody.type = 'request';
                storage.set(PREFIX + requestId, requestBody);
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

            var block = storage.get(PREFIX + 'block');
            if ((typeof block === 'undefined') || block === null) { return; }

            if (block.time < ((new Date().getTime()) - CLEANUP_FREQ)) {
                return;
            }

            if (block.alert) {
                var instances = storage.getType('instance');
                for (var key in instances) {
                    if (instances.hasOwnProperty(key)) {
                        if (instances[key].isBlock && instances[key].id < id) {
                            block.alert = false;
                            break;
                        }
                    }
                }
            }
            return block;
        }
    };
}) ();
