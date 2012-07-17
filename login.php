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
 * This script logs helpers and instructors in and out
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once((dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/plugins/gotomeeting/user2plugin.php');

# require login
require_login(0, false);

# get our parameters
$login = required_param('login', PARAM_INT);
$queueid = optional_param('queueid', 0, PARAM_INT);

# todo: log this

$message = '';
if ($queueid) {     # helper
    if (!$record = get_record('block_helpmenow_helper', 'queueid', $queueid, 'userid', $USER->id)) {
        helpmenow_fatal_error('You do not have permission to view this page.');
    }
    $message = "queueid: $queueid, ";
} else {    # instructor
    if (!$record = get_record('block_helpmenow_user', 'userid', $USER->id)) {
        helpmenow_fatal_error('You do not have permission to view this page.');
    }
}

if ($login) {
    helpmenow_log($USER->id, 'logged_in', $message); 
    $record->isloggedin = time();
} else {
    $duration = time() - $record->isloggedin;
    helpmenow_log($USER->id, 'logged_out', "{$message}duration: $duration seconds");
    $record->isloggedin = 0;
}

if ($queueid) {     # helper
    update_record('block_helpmenow_helper', $record);
} else {    # instructor
    update_record('block_helpmenow_user', addslashes_recursive($record));
}

/**
 * handle plugins' on_login/on_logout
 *
 * plugins that return true don't need anymore
 * plugins that return a string are giving us a url to redirect too
 *
 * if multiple plugins give us a url to redirect to, we're going to have have 
 * to handle that by presenting links to the user instead of auto redirecting
 */
$redirects = array();
foreach (helpmenow_plugin::get_plugins() as $pluginname => $class) {
    if ($login) {
        $returned = $class::on_login();
    } else {
        $returned = $class::on_logout();
    }
    if (!is_bool($returned)) {
        $redirects[$pluginname] = $returned;
    }
}

if (count($redirects) == 0) {
    helpmenow_fatal_error('You may now close this window', true, true);
}
if (count($redirects) == 1) {
    redirect(reset($redirects));
}

$output = <<<EOF
<p>Multiple plugins require further action. Please follow the links below to finish logging in.</p>
EOF;
foreach ($redirects as $pluginname => $redirect) {
    $output .= link_to_popup_window($redirect, $pluginname, $pluginname, 400, 500, null, null, true) . "<br />";
}
$title = get_string('helpmenow', 'block_helpmenow');
$nav = array(array('name' => $title));
print_header($title, $title, build_navigation($nav));
print_box($output);
print_footer();

?>
