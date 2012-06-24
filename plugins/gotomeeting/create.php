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
 * This script creates g2m sessions
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/lib.php');
require_once(dirname(__FILE__) . '/user2plugin.php');
require_once(dirname(__FILE__) . '/session2plugin.php');

require_login(0, false);

$sessionid = required_param('sessionid', PARAM_INT);

# make sure user is in this session
if (!helpmenow_verify_session($sessionid)) {
    helpmenow_fatal_error('You do not have permission to view this page.');
}

# make sure user is privileged
$session = get_record('block_helpmenow_session', 'id',  $sessionid);
if (!$privileged = helpmenow_check_privileged($session)) {
    helpmenow_fatal_error('You do not have permission to view this page.');
}

# if we've already made a g2m session, redirect
if ($session2user = get_record('block_helpmenow_s2p', 'sessionid', $session->id, 'plugin', 'gotomeeting')) {
    $session2user = new helpmenow_session2plugin_gotomeeting(null, $session2user);
    redirect($session2user->join_url);
}

# make sure we've got a gotomeeting user2plugin record with a token
$token_url = new moodle_url("$CFG->wwwroot/blocks/helpmenow/plugins/gotomeeting/token.php");
$token_url->param('redirect', qualified_me());
$token_url = $token_url->out();
if ($record = get_record('block_helpmenow_user2plugin', 'userid', $USER->id, 'plugin', 'gotomeeting')) {
    $user2plugin = new helpmenow_user2plugin_gotomeeting(null, $record);
} else {
    $user2plugin = new helpmenow_user2plugin_gotomeeting();
    $user2plugin->userid = $USER->id;
    $user2plugin->insert();
    redirect($token_url);
}
# check to see if the oauth token has expired
if ($user2plugin->token_expiration < time()) {
    redirect($token_url);
}

$session2user = new helpmenow_session2plugin_gotomeeting();
$session2user->create();
$session2user->sessionid = $session->id;
$session2user->insert();

$message = fullname($USER) . ' has started GoToMeeting, <a target="_blank" href="'.$session2user->join_url.'">click here</a> to join.';

$message_rec = (object) array(
    'userid' => get_admin()->id,
    'sessionid' => $session->id,
    'time' => time(),
    'message' => $message,
);
insert_record('block_helpmenow_message', $message_rec);

redirect($session2user->join_url);

?>
