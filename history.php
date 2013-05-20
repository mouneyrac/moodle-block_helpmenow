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
 * This script generates the history window
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once((dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

require_login(0, false);

# verify session, also verify it is not from a queue, they do not have history
$sessionid = required_param('session', PARAM_INT);
if (!helpmenow_verify_session($sessionid) or isset($session->queueid)) {
    helpmenow_fatal_error(get_string('permission_error', 'block_helpmenow'));
}

$session = get_record('block_helpmenow_session', 'id',  $sessionid);

# title
$sql = "
    SELECT u.*
    FROM {$CFG->prefix}block_helpmenow_session2user s2u
    JOIN {$CFG->prefix}user u ON u.id = s2u.userid
    WHERE s2u.sessionid = $sessionid
    AND s2u.userid <> $USER->id
    ";
$other_user_recs = get_records_sql($sql);
$other_users = array();
foreach ($other_user_recs as $r) {
    $other_users[] = fullname($r);
    $otheruserid = $r->id;
}
$title = get_string('chat_history', 'block_helpmenow') . ': ' . implode(', ', $other_users);

if (count($other_user_recs)>1) {
    helpmenow_fatal_error(get_string('history_not_available', 'block_helpmenow'));
}

$sql = "
    SELECT s.id
    FROM {$CFG->prefix}block_helpmenow_session s
    JOIN {$CFG->prefix}block_helpmenow_session2user s2u ON s.id = s2u.sessionid AND s2u.userid=$USER->id
    JOIN {$CFG->prefix}block_helpmenow_session2user s2u2 ON s.id = s2u2.sessionid AND s2u2.userid = $otheruserid
    ";
// TODO: Add WHERE that limits messages to recent times...

$messages = '';
$sessionids = array();
if ($sessions = get_records_sql($sql)) {
    foreach ($sessions as $s) {
        $sessionids[] = $s->id;
    }
    $sessionids = implode(', ', $sessionids);
    if ($history = helpmenow_get_history_list($sessionids)) {
        $messages = helpmenow_format_messages($history, true);
    }
}


echo <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
    <head>
        <title>$title</title>
        <link rel="stylesheet" type="text/css" href="$CFG->wwwroot/blocks/helpmenow/style/history.css" />
    </head>
    <body>
        <div id="titleDiv"><b>$title</b></div>
        <div id="chatDiv">$messages</div>
    </body>
</html>
EOF;

?>
