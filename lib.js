var xmlhttp;

function helpmenow_call(params, callbackFunction) {
    params = JSON.stringify(params);

    if (window.XMLHttpRequest) {    // code for IE7+, Firefox, Chrome, Opera, Safari
        xmlhttp = new XMLHttpRequest();
    }
    else {  // code for IE6, IE5... we're requiring IE8, so... yah
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = callbackFunction;
    xmlhttp.open("POST", helpmenow_url, true);
    xmlhttp.setRequestHeader("Accept", "application/json");
    xmlhttp.setRequestHeader("Content-type", "application/json");
    xmlhttp.send(params);
}

// edit: true toggles to edit mode
function helpmenow_toggle_motd(edit) {
    motd_element = document.getElementById("helpmenow_motd");
    edit_element = document.getElementById("helpmenow_motd_edit");
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

function helpmenow_enter_motd(e) {
    e = e || event;     // IE
    edit_element = document.getElementById("helpmenow_motd_edit");

    // enter key
    if (e.keyCode === 13 && !e.ctrlKey) {
        motd_element = document.getElementById("helpmenow_motd");
        var params = {
            "function" : "motd",
            "motd" : edit_element.value
        };
        helpmenow_call(params, function() {
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
