/**
 * generic call function for our ajax server
 *
 * @param object params
 * @param function callbackFunction
 */
function helpmenow_call(params, callbackFunction) {
    var xmlhttp;
    params = JSON.stringify(params);

    if (window.XMLHttpRequest) {    // code for IE7+, Firefox, Chrome, Opera, Safari
        xmlhttp = new XMLHttpRequest();
    }
    else {  // code for IE6, IE5... we're requiring IE8, so... yah
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function() {
        callbackFunction(xmlhttp);
    }
    xmlhttp.open("POST", helpmenow_url + "ajax.php", true);
    xmlhttp.setRequestHeader("Accept", "application/json");
    xmlhttp.setRequestHeader("Content-type", "application/json");
    xmlhttp.send(params);
}

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
 * Handles typing in the motd textarea. Limits the length to 140 characters.
 * When the enter key is pressed, we submit.
 *
 * @param event e keypress event
 * @return bool true indicates to the browser to treat the event as a normal
 *      keystroke
 */
function helpmenow_enter_motd(e) {
    e = e || event;     // IE
    var edit_element = document.getElementById("helpmenow_motd_edit");

    // enter key
    if (e.keyCode === 13 && !e.ctrlKey) {
        var motd_element = document.getElementById("helpmenow_motd");
        var params = {
            "function" : "motd",
            "motd" : edit_element.value
        };
        helpmenow_call(params, function(xmlhttp) {
            if (xmlhttp.readyState==4 && xmlhttp.status==200) {
                var response = JSON.parse(xmlhttp.responseText);
                edit_element.value = response.motd;
                motd_element.innerHTML = response.motd;
                helpmenow_toggle_motd(false);
            }
        });
        return false;
    }

    // limit the length to 140
    if (edit_element.value.length >= 140) {
        return false;
    }

    return true;
}

/**
 * Function that is called periodically to update the list of students
 */
function helpmenow_instructor_refresh() {
    var params = {
        "function" : "students",
    };
    helpmenow_call(params, function(xmlhttp) {
        if (xmlhttp.readyState==4 && xmlhttp.status==200) {
            var response = JSON.parse(xmlhttp.responseText);
            var student_list = document.getElementById("helpmenow_students");
            student_list.innerHTML = "";
            for (var i = 0; i < response.students.length; i++) {
                student_list.innerHTML += response.students[i].html;
            }
        }
    });
}

/**
 * Function that is called periodically to update the list of queues
 */
function helpmenow_queue_refresh() {
    var params = {
        "function" : "queues",
    };
    helpmenow_call(params, function(xmlhttp) {
        if (xmlhttp.readyState==4 && xmlhttp.status==200) {
            var response = JSON.parse(xmlhttp.responseText);
            var queue_list = document.getElementById("helpmenow_queue");
            queue_list.innerHTML = "";
            for (var i = 0; i < response.queues.length; i++) {
                queue_list.innerHTML += response.queues[i].html;
            }
        }
        if (xmlhttp.readyState==4 && xmlhttp.status==400) {
            console.log(xmlhttp);
        }
    });
}
