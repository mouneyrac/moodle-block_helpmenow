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
define('HELPMENOW_ERROR_REQUEST', 'error: bad_request');
define('HELPMENOW_ERROR_SERVER', 'error: server');
define('HELPMENOW_AJAX_REFRESH', 15000);

/**
 * Checks if we want to auto create course level queues. If we do, check if we
 * need to create a queue for this course and do so if necessary. Also adds
 * helpers if configured to do so.
 * todo: fix this to work with our polymorphism
 * @param int $contextid contextid; if none specified, gets the current course
 *      context
 */
function helpmenow_ensure_queue_exists($contextid = null) {
    global $CFG;

    # bail if we're not autocreating course queues
    if (!$CFG->helpmenow_autocreate_course_queue) { return; }

    # get the current contextid if we were'nt given one
    if (!isset($contextid)) {
        global $COURSE;
        # bail if we're one the front page
        if ($COURSE->id == SITEID) { return; }

        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
    } else {
        $context = get_context_instance_by_id($contextid);
        # bail if the passed contextid isn't course level
        if ($context->contextlevel !== CONTEXT_COURSE) { return; }

        $COURSE = get_record('course', 'id', $context->instanceid);
    }

    # check if we need to make a queue
    if (record_exists('block_helpmenow_queue', 'contextid', $context->id)) { return; }

    # make a queue
    $queue = new helpmenow_queue();
    $queue->contextid = $context->id;
    $queue->name = $COURSE->shortname;      # todo: maybe this should be configurable?
    $queue->description = get_string('auto_queue_desc', 'block_helpmenow'); # todo: this too
    $queue->plugin = $CFG->helpmenow_default_plugin;
    $queue->insert();

    # bail if we're not auto creating helpers
    if (!$CFG->helpmenow_autoadd_course_helpers) { return; }

    $users = get_users_by_capability($context, HELPMENOW_CAP_COURSE_QUEUE_ANSWER, 'u.id', '', '', '', '', '', false);

    foreach ($users as $u) {
        # we currently don't need to check if we already have a helper, as
        # we're only doing this on new queues
        $helper = new helpmenow_helper();
        $helper->queueid = $queue->id;
        $helper->userid = $u->id;
        $helper->isloggedin = 0;
        $helper->insert();
    }
}

/**
 * prints an error and ends execution
 * @param string $message message to be printed
 * @param bool $print_header print generic helpmenow header or not
 */
function helpmenow_fatal_error($message, $print_header = true) {
    if ($print_header) {
        $title = get_string('helpmenow', 'block_helpmenow');
        $nav = array(array('name' => $title));
        print_header($title, $title, build_navigation($nav));
        print_box($message);
        print_footer();
    } else {
        echo $message;
    }
    die;
}

# todo: faking this for now...
function helpmenow_get_students() {
    global $CFG;
    $sql = "
        SELECT u.*
        FROM {$CFG->prefix}classroom_enrolment ce
        JOIN {$CFG->prefix}user u ON u.idnumber = ce.sis_user_idstr
        WHERE ce.classroom_idstr = '289'
        LIMIT 10
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
