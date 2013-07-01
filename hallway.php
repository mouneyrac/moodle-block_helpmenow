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
 * This script is a quick 'n' dirty list of Instructor queues, who's online,
 * and meeting links.
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once((dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
helpmenow_plugin::get_plugins();

# require login
require_login(0, false);

# contexts and cap check
$admin = has_capability(HELPMENOW_CAP_MANAGE, get_context_instance(CONTEXT_SYSTEM, SITEID));
if (!($admin or record_exists('block_helpmenow_helper', 'userid', $USER->id))) {
    redirect();
}

# title, navbar, and a nice box
$blockname = helpmenow_title();
$title = get_string('who', 'block_helpmenow');
$nav = array(
    array('name' => $blockname),
    array('name' => $title)
);
print_header($title, $title, build_navigation($nav));
print_box_start('generalbox centerpara');

$where = $admin ? '' : "WHERE h.userid = {$USER->id}";
$sql = "
    SELECT q.*
    FROM {$CFG->prefix}block_helpmenow_queue q
    JOIN {$CFG->prefix}block_helpmenow_helper h ON h.queueid = q.id
    $where
";
# helpers see other helpers in the same queue
if ($queues = get_records_sql($sql)) {
    foreach ($queues as $q) {
        print_heading($q->name, '', 3);
        $helpers = get_records_sql("
            SELECT *
            FROM {$CFG->prefix}block_helpmenow_helper h
            JOIN {$CFG->prefix}user u ON u.id = h.userid
            WHERE h.queueid = {$q->id}
        ");
        helpmenow_print_hallway($helpers);
    }
}

# admins see all instructors
if ($admin) {
    print_heading("Instructors", '', 3);
    $instructors = get_records_sql("
        SELECT *
        FROM {$CFG->prefix}block_helpmenow_user hu
        JOIN {$CFG->prefix}user u ON u.id = hu.userid
    ");
    helpmenow_print_hallway($instructors);
}

print_box_end();

# footer
print_footer();

?>
