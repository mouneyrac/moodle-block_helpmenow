<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Core help me now library.
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/queue.php');
require_once(dirname(__FILE__) . '/db/access.php');

/**
 * Some defines for queue privileges. This is disjoint from capabilities, as
 * a user can have the helper cap but still needs to be added as a helper to
 * the appropriate queue.
 */
define('HELPMENOW_QUEUE_HELPER', 'helper');
define('HELPMENOW_QUEUE_HELPEE', 'helpee');
define('HELPMENOW_NOT_PRIVILEGED', 'notprivileged');

function helpmenow_verify_session($session) {
    global $CFG, $USER;
    $sql = "
        SELECT 1
        FROM {$CFG->prefix}block_helpmenow_session s
        JOIN {$CFG->prefix}block_helpmenow_session2user s2u ON s2u.sessionid = s.id
        WHERE s2u.userid = $USER->id
        AND s.id = $session
    ";
    return record_exists_sql($sql);
}

function helpmenow_check_privileged($session) {
    global $USER, $CFG;

    if (isset($session->queueid)) {
        $sql = "
            SELECT 1
            FROM {$CFG->prefix}block_helpmenow_queue q
            JOIN {$CFG->prefix}block_helpmenow_helper h ON h.queueid = q.id
            WHERE q.id = $session->queueid
            AND h.userid = $USER->id
        ";
        if (record_exists_sql($sql)) {
            return true;
        }
    } else if (get_field('sis_user', 'privilege', 'sis_user_idstr', $USER->idnumber) == 'TEACHER') {
        return true;
    }
    return false;
}

function helpmenow_get_students() {
    global $CFG, $USER;
    $cutoff = helpmenow_cutoff();
    $sql = "
        SELECT u.*
        FROM {$CFG->prefix}classroom c
        JOIN {$CFG->prefix}classroom_enrolment ce ON ce.classroom_idstr = c.classroom_idstr
        JOIN {$CFG->prefix}user u ON u.idnumber = ce.sis_user_idstr
        WHERE c.sis_user_idstr = '$USER->idnumber'
        AND ce.status_idstr = 'ACTIVE'
        AND ce.activation_status_idstr IN ('ENABLED', 'CONTACT_INSTRUCTOR')
        AND ce.iscurrent = 1
        AND u.lastaccess > $cutoff
    ";
    return get_records_sql($sql);
}

function helpmenow_get_instructors() {
    global $CFG, $USER;
    $cutoff = helpmenow_cutoff();
    $sql = "
        SELECT u.*, hu.motd
        FROM {$CFG->prefix}classroom_enrolment ce
        JOIN {$CFG->prefix}classroom c ON c.classroom_idstr = ce.classroom_idstr
        JOIN {$CFG->prefix}user u ON c.sis_user_idstr = u.idnumber
        JOIN {$CFG->prefix}block_helpmenow_user hu ON hu.userid = u.id
        WHERE ce.sis_user_idstr = '$USER->idnumber'
        AND ce.status_idstr = 'ACTIVE'
        AND ce.activation_status_idstr IN ('ENABLED', 'CONTACT_INSTRUCTOR')
        AND ce.iscurrent = 1
        AND hu.isloggedin <> 0
        AND u.lastaccess > $cutoff
    ";
    return get_records_sql($sql);
}

function helpmenow_cutoff() {
    global $CFG;
    if ($CFG->helpmenow_no_cutoff) {    # set this to true to see everyone
        return 0;
    }
    return time() - 300;
}

function helpmenow_add_user($userid, $sessionid, $last_refresh = 0) {
    $session2user_rec = (object) array(
        'sessionid' => $sessionid,
        'userid' => $userid,
        'last_refresh' => $last_refresh,
    );
    return insert_record('block_helpmenow_session2user', $session2user_rec);
}

/**
 * prints an error and ends execution
 * @param string $message message to be printed
 * @param bool $print_header print generic helpmenow header or not
 */
function helpmenow_fatal_error($message, $print_header = true, $close = false) {
    if ($print_header) {
        $title = get_string('helpmenow', 'block_helpmenow');
        $nav = array(array('name' => $title));
        print_header($title, $title, build_navigation($nav));
        print_box($message);
        print_footer();
    } else {
        echo $message;
    }
    if ($close) {
        echo "<script type=\"text/javascript\">close();</script>";
    }
    die;
}

/**
 * ensures our instructors have a helpmenow_user record
 */
function helpmenow_ensure_user_exists() {
    global $USER;
    if (record_exists('block_helpmenow_user', 'userid', $USER->id)) {
        return;
    }

    $helpmenow_user = (object) array(
        'userid' => $USER->id,
        'isloggedin' => 0,
        'motd' => '',
    );

    insert_record('block_helpmenow_user', $helpmenow_user);
}

/**
 * inserts a message into block_helpmenow_log
 * @param int $userid user performing action
 * @param string $action action user is performing
 * @param string $details details of the action
 */
function helpmenow_log($userid, $action, $details) {
    $new_record = (object) array(
        'userid' => $userid,
        'action' => $action,
        'details' => $details,
        'timecreated' => time(),
    );
    insert_record('block_helpmenow_log', $new_record);
}

function helpmenow_clean_sessions() {
    global $CFG, $USER;

    $sql = "
        SELECT s.*
        FROM {$CFG->prefix}block_helpmenow_session s
        JOIN {$CFG->prefix}block_helpmenow_session2user s2u ON s2u.sessionid = s.id AND s2u.userid = $USER->id
        WHERE s.iscurrent = 1
        ";
    if ($sessions = get_records_sql($sql)) {
        foreach ($sessions as $s) {
            $session_users = get_records('block_helpmenow_session2user', 'sessionid', $s->id);
            foreach ($session_users as $su) {
                if (($su->last_refresh + 60) > time()) {
                    continue 2;
                }
            }
            set_field('block_helpmenow_session', 'iscurrent', 0, 'id', $s->id);
        }
    }
}

function helpmenow_autologout_helpers() {
    global $CFG;

    $cutoff = helpmenow_cutoff();
    $sql = "
        SELECT h.*
        FROM {$CFG->prefix}block_helpmenow_helper h
        JOIN {$CFG->prefix}user u ON u.id = h.userid
        WHERE h.isloggedin <> 0
        AND u.lastaccess < $cutoff
        ";
    if (!$helpers = get_records_sql($sql)) {
        return true;
    }

    $success = true;
    foreach ($helpers as $h) {
        $h->isloggedin = 0;
        $success = $success and update_record('block_helpmenow_helper', $h);
    }

    return $success;
}

function helpmenow_autologout_users() {
    global $CFG;

    $cutoff = helpmenow_cutoff();
    $sql = "
        SELECT hu.*
        FROM {$CFG->prefix}block_helpmenow_user hu
        JOIN {$CFG->prefix}user u ON u.id = hu.userid
        WHERE hu.isloggedin <> 0
        AND u.lastaccess < $cutoff
        ";
    if (!$users = get_records_sql($sql)) {
        return true;
    }

    $success = true;
    foreach ($users as $u) {
        $u->isloggedin = 0;
        $success = $success and update_record('block_helpmenow_user', $u);
    }

    return $success;
}

function helpmenow_block_interface() {
    global $CFG, $USER;

    $output = '';

    $output .= <<<EOF
<div id="helpmenow_queue_div"></div>
EOF;

    $privilege = get_field('sis_user', 'privilege', 'sis_user_idstr', $USER->idnumber);
    switch ($privilege) {
    case 'TEACHER':
        helpmenow_ensure_user_exists();
        $helpmenow_user = get_record('block_helpmenow_user', 'userid', $USER->id);
        $instyle = $outstyle = '';
        if ($helpmenow_user->isloggedin) {
            $outstyle = 'style="display: none;"';
        } else {
            $instyle = 'style="display: none;"';
        }
        $login_url = new moodle_url("$CFG->wwwroot/blocks/helpmenow/login.php");
        $login_url->param('login', 0);
        $logout = link_to_popup_window($login_url->out(), "login", 'Leave Office', 400, 500, null, null, true);
        $login_url->param('login', 1);
        $login = link_to_popup_window($login_url->out(), "login", 'Enter Office', 400, 500, null, null, true);
        $output .= <<<EOF
<div id="helpmenow_office">
    <div><b>My Office</b></div>
    <div id="helpmenow_motd" onclick="helpmenow_toggle_motd(true);" style="border:1px dotted black; width:12em; min-height:1em; padding:.2em; margin-top:.5em;">$helpmenow_user->motd</div>
    <textarea id="helpmenow_motd_edit" onkeypress="return helpmenow_motd_textarea(event);" onblur="helpmenow_toggle_motd(false)" style="display:none; margin-top:.5em;" rows="4" cols="22"></textarea>
    <div style="text-align: center; font-size:small; margin-top:.5em;">
        <div id="helpmenow_logged_in_div_0" $instyle>$logout</div>
        <div id="helpmenow_logged_out_div_0" $outstyle>Out of Office | $login</div>
    </div>
    <div style="margin-top:.5em;">Online Students:</div>
    <div id="helpmenow_users_div"></div>
</div>
EOF;
        break;
    case 'STUDENT':
        $output .= '
            <div>Online Instructors:</div>
            <div id="helpmenow_users_div"></div>
            ';
        break;
    }
    $output .= <<<EOF
<hr />
<script type="text/javascript" src="$CFG->wwwroot/blocks/helpmenow/javascript/lib.js"></script>
<script type="text/javascript" src="$CFG->wwwroot/blocks/helpmenow/javascript/block.js"></script>
<script type="text/javascript">
    var helpmenow_url = "$CFG->wwwroot/blocks/helpmenow/ajax.php";
    helpmenow_block_refresh();
    var chat_t = setInterval(helpmenow_block_refresh, 10000);
</script>
<embed id="helpmenow_chime" src="$CFG->wwwroot/blocks/helpmenow/cowbell.wav" autostart="false" width="0" height="1" enablejavascript="true" style="position:absolute; left:0px; right:0px; z-index:-1;" />
EOF;

    return $output;
}

?>
