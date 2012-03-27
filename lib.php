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

require_once(dirname(__FILE__) . '/helpmenow.php');
require_once(dirname(__FILE__) . '/meeting.php');
require_once(dirname(__FILE__) . '/queue.php');

define('QUEUE_HELPER', 'helper');
define('QUEUE_HELPEE', 'helpee');
define('NOT_PRIVILEGED', 'notprivileged');

/**
 * Get the current course context
 * @return object context
 */
function helpmenow_get_context() {
    global $COURSE;

    return get_context_instance(CONTEXT_COURSE, $COURSE->id);
}

/**
 * Checks if we want to auto create course level queues. If we do, check if we
 * need to create a queue for this course and do so if necessary.
 * @parm int $contextid contextid; if none specified, gets the current course
 *      context
 */
function helpmenow_ensure_queue_exists($contextid = null) {
    global $CFG, $COURSE;

    # bail if we're not autocreating course queues
    if (!$CFG->helpmenow_autocreate_course_queue) { return; }

    # get the current contextid if we were'nt given one
    if (!isset($contextid)) {
        $context = helpmenow_get_context();
        $contextid = $context->id;
    }

    # check if we need to make a queue
    if (record_exists('block_helpmenow_queue', 'contextid', $contextid)) { return; }

    # make a queue
    $queue = new helpmenow_queue();
    $queue->contextid = $contextid;
    $queue->name = $COURSE->shortname;
    $queue->plugin = $CFG->helpmenow_default_plugin;
    $queue->insert();
}

?>
