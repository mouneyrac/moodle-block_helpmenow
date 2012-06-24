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
    global $USER;

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
        AND ce.iscurrent = 1
        AND hu.isloggedin <> 0
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

function helpmenow_add_user($userid, $sessionid) {
    $session2user_rec = (object) array(
        'sessionid' => $sessionid,
        'userid' => $userid,
        'last_refresh' => time(),
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

?>
