<?php

// This file is part of Help Me Now block - http://moodle.org/
//
// Help Me Now block is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Help Me Now block is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Help Me Now block.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Post installation and migration code.
 *
 * @package    block_helpmenow
 * @copyright  2014 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function xmldb_block_helpmenow_install() {
    global $DB, $USER;

    // Reminder: do not use core/lib function in upgrade script!
    $contextid = $DB->get_field('context', 'id', array('contextlevel' => CONTEXT_SYSTEM));

    // Hardcode the capability as they must match the value at this upgrade time.
    $HELPMENOW_CAP_QUEUE_ANSWER = 'block/helpmenow:global_queue_answer';
    $HELPMENOW_CAP_QUEUE_ASK = 'block/helpmenow:queue_ask';
    $HELPMENOW_CAP_MANAGE = 'block/helpmenow:manage_queues';

    // Add Help Me Now block manager system role.
    $role = new stdClass();
    $role->name        = 'Help Me Now Manager';
    $role->shortname   = 'helpmenowmanager';
    $role->description = 'can assign a queue helper - can do anything on helpmenow.';
    // Find free sortorder number.
    $role->sortorder = $DB->get_field('role', 'MAX(sortorder) + 1', array());
    if (empty($role->sortorder)) {
        $role->sortorder = 1;
    }
    $roleid = $DB->insert_record('role', $role);
    // Set the role as system role.
    $rcl = new stdClass();
    $rcl->roleid = $roleid;
    $rcl->contextlevel = CONTEXT_SYSTEM;
    $DB->insert_record('role_context_levels', $rcl, false, true);
    // Assign correct permission to Help Me Now block manager role.
    $cap = new stdClass();
    $cap->contextid    = $contextid;
    $cap->roleid       = $roleid;
    $cap->capability   = $HELPMENOW_CAP_MANAGE;
    $cap->permission   = 1;
    $cap->timemodified = time();
    $cap->modifierid   = empty($USER->id) ? 0 : $USER->id;
    $DB->insert_record('role_capabilities', $cap);
    $cap->capability   = $HELPMENOW_CAP_QUEUE_ANSWER;
    $DB->insert_record('role_capabilities', $cap);
    $cap->capability   = $HELPMENOW_CAP_QUEUE_ASK;
    $DB->insert_record('role_capabilities', $cap);

    // Add Help Me Now block instructor system role.
    $role = new stdClass();
    $role->name        = 'Help Me Now instructor';
    $role->shortname   = 'helpmenowinstructor';
    $role->description = 'can login in an office and answer questions.';
    $role->sortorder = $DB->get_field('role', 'MAX(sortorder) + 1', array());
    $roleid = $DB->insert_record('role', $role);
    $rcl = new stdClass();
    $rcl->roleid = $roleid;
    $rcl->contextlevel = CONTEXT_SYSTEM;
    $DB->insert_record('role_context_levels', $rcl, false, true);
    $cap->roleid       = $roleid;
    $cap->capability   = $HELPMENOW_CAP_QUEUE_ASK;
    $DB->insert_record('role_capabilities', $cap);
    $cap->capability   = $HELPMENOW_CAP_QUEUE_ANSWER;
    $DB->insert_record('role_capabilities', $cap);

    // Add Help Me Now block student system role.
    $role = new stdClass();
    $role->name        = 'Help Me Now student';
    $role->shortname   = 'helpmenowstudent';
    $role->description = 'can ask questions to instructors and helpers.';
    $role->sortorder = $DB->get_field('role', 'MAX(sortorder) + 1', array());
    $roleid = $DB->insert_record('role', $role);
    $rcl = new stdClass();
    $rcl->roleid = $roleid;
    $rcl->contextlevel = CONTEXT_SYSTEM;
    $DB->insert_record('role_context_levels', $rcl, false, true);
    $cap->roleid       = $roleid;
    $cap->capability   = $HELPMENOW_CAP_QUEUE_ASK;
    $DB->insert_record('role_capabilities', $cap);
}