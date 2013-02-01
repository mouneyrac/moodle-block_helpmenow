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
 * This script invites users to adobe connect sessions and then redirects the
 * inviter as well.
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

require_login(0, false);

# limit to testers for now
if (!helpmenow_adobeconnect_tester()) {
    helpmenow_fatal_error('You do not have permission to view this page.');
}

# make sure user is instructor or helper
$user = get_record('block_helpmenow_user', 'userid', $USER->id);
$helper = get_records('block_helpmenow_helper', 'userid', $USER->id);
if (!$user and !$helper) {
    helpmenow_fatal_error('You do not have permission to view this page.');
}

$sessionid = required_param('sessionid', PARAM_INT);

# verify sesion
if (!helpmenow_verify_session($sessionid)) {
    helpmenow_fatal_error('You do not have permission to view this page.');
}

$username = preg_replace('/admin$/', '', $USER->username);

if (!empty($CFG->helpmenow_adobeconnect_url)) {
    $url = $CFG->helpmenow_adobeconnect_url."/$username";
} else {
    helpmenow_fatal_error('This page has not been fully configured.');
}
$message = fullname($USER) . ' has invited you to use voice, video, and whiteboarding, <a target="adobe_connect" href="'.$url.'">click here</a> to join.';
helpmenow_message($sessionid, null, $message);

redirect($url);

?>
