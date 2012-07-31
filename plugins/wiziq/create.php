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
 * This script creates wiziq sessions
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

require_login(0, false);

$session_id = required_param('sessionid', PARAM_INT);

# verify sesion
if (!helpmenow_verify_session($session_id)) {
    helpmenow_fatal_error('You do not have permission to view this page.');
}

# make sure user is instructor or helper
$user = get_record('block_helpmenow_user', 'userid', $USER->id);
$helper = get_records('block_helpmenow_helper', 'userid', $USER->id);
if (!$user and !$helper) {
    helpmenow_fatal_error('You do not have permission to view this page.');
}

if (!$user2plugin = helpmenow_user2plugin_wiziq::get_user2plugin()) {
    helpmenow_fatal_error('No user2plugin');
}

if (!$user2plugin->verify_active_meeting()) {
    $user2plugin->create_meeting();     # create meeting only if we don't have one
}

if ($s2p_rec = get_record('block_helpmenow_s2p', 'sessionid', $session_id, 'plugin', 'wiziq')) {
    $s2p = new helpmenow_session2plugin_wiziq(null, $s2p_rec);
    $method = 'update';
} else {
    $s2p = new helpmenow_session2plugin_wiziq(null, (object) array('sessionid' => $session_id));
    $method = 'insert';
}
if (!in_array($user2plugin->class_id, $s2p->classes)) {
    $s2p->classes[] = $user2plugin->class_id;
    $s2p->$method();
}

$join_url = new moodle_url("$CFG->wwwroot/blocks/helpmenow/plugins/wiziq/join.php");
$join_url->param('classid', $user2plugin->class_id);
$join_url->param('sessionid', $session_id);
$join_url = $join_url->out();

$message = fullname($USER) . ' has invited you to WizIQ, <a target="_blank" href="'.$join_url.'">click here</a> to join.';
$message_rec = (object) array(
    'userid' => get_admin()->id,
    'sessionid' => $session_id,
    'time' => time(),
    'message' => addslashes($message),
);
if (!insert_record('block_helpmenow_message', $message_rec)) {
    helpmenow_fatal_error('Could not insert message record');
}

echo <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
    <head>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js" type="text/javascript"></script>
        <script type="text/javascript">
            $(document).ready(function () {
                
            });
        </script>
EOF;

redirect($user2plugin->presenter_url); 
?>
