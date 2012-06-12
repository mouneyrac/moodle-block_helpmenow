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

require_once(dirname(__FILE__) . '/meeting.php');
require_once(dirname(__FILE__) . '/plugin.php');
require_once(dirname(__FILE__) . '/request.php');
require_once(dirname(__FILE__) . '/queue.php');
require_once(dirname(__FILE__) . '/helper.php');
require_once(dirname(__FILE__) . '/db/access.php');

/**
 * Some defines for queue privileges. This is disjoint from capabilities, as
 * a user can have the helper cap but still needs to be added as a helper to
 * the appropriate queue.
 */
define('HELPMENOW_QUEUE_HELPER', 'helper');
define('HELPMENOW_QUEUE_HELPEE', 'helpee');
define('HELPMENOW_NOT_PRIVILEGED', 'notprivileged');

# Default queue weight value, used to determine queue display order
define('HELPMENOW_DEFAULT_WEIGHT', 50);

# queue types
define('HELPMENOW_QUEUE_TYPE_INSTRUCTOR', 'instructor');
define('HELPMENOW_QUEUE_TYPE_HELPDESK', 'helpdesk');

# ajax stuff
define('HELPMENOW_AJAX_REFRESH', 5000);

/**
 * Checks if we want to auto create instructor queues. If we do, check if we
 * need to create a queue for this user and do so if necessary.
 */
function helpmenow_ensure_queue_exists($contextid = null) {
    global $CFG, $USER;

    # bail if we're not autocreating instructor queues
    if (!$CFG->helpmenow_autocreate_instructor_queue) { return; }

    # check if user is an instructor
    if (!record_exists('sis_user', 'sis_user_idstr', $USER->idnumber, 'privilege', 'TEACHER')) { return; }

    # check if we already have a queue
    if (record_exists('block_helpmenow_queue', 'userid', $USER->id)) { return; }

    $sitecontext = get_context_instance(CONTEXT_SYSTEM, SITEID);

    # make a queue
    $queue = helpmenow_queue::new_instance($CFG->helpmenow_default_plugin);
    $queue->contextid = $sitecontext->id;
    $queue->name = fullname($USER);
    $queue->description = '';
    $queue->userid = $USER->id;
    $queue->type = HELPMENOW_QUEUE_TYPE_INSTRUCTOR;
    $queue->insert();

    $helper = helpmenow_helper::new_instance($CFG->helpmenow_default_plugin);
    $helper->queueid = $queue->id;
    $helper->userid = $USER->id;
    $helper->isloggedin = 0;
    $helper->insert();
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

function helpmenow_get_students() {
    global $CFG, $USER;

    $cutoff = time() - 300;     # go with the same cutoff as Moodle
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

?>
