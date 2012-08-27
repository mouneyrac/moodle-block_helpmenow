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
 * This script generates the chat window
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once((dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

require_login(0, false);

# verify session
$sessionid = required_param('session', PARAM_INT);
if (!helpmenow_verify_session($sessionid)) {
    helpmenow_fatal_error(get_string('permission_error', 'block_helpmenow'));
}

# figure out if the user should see plugins
$session = get_record('block_helpmenow_session', 'id',  $sessionid);
$privileged = helpmenow_check_privileged($session);

# title
if (!$privileged and isset($session->queueid)) {
    $title = get_field('block_helpmenow_queue', 'name', 'id', $session->queueid);
} else {
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
    }
    $title = implode(', ', $other_users);
}

# get plugin links and load necessary javascript
$plugins_display = $plugins_js = array();
foreach (helpmenow_plugin::get_plugins() as $pluginname) {
    # display
    $class = "helpmenow_plugin_$pluginname";
    $plugin_text = $class::display($sessionid, $privileged);
    if (!strlen($plugin_text)) {
        continue;
    }
    $plugins_display[] = "<div id='helpmenow_$pluginname'>$plugin_text</div>";

    # js
    $plugin_libs = $class::get_js_libs();
    if (count($plugin_libs)) {
        foreach ($plugin_libs as $lib) {
            $plugins_js[] = "<script src=\"$lib\" type=\"text/javascript\"></script>";
        }
    }
}
if (count($plugins_display)) {
    $plugins_display = '<div id="pluginDiv">'.implode(' | ', $plugins_display).'</div>';
} else {
    $plugins_display = '';
}
if (count($plugins_js)) {
    $plugins_js = implode("\n", $plugins_js);
} else {
    $plugins_js = '';
}

$textarea_message = get_string('textarea_message', 'block_helpmenow');
$jplayer = helpmenow_jplayer();

if ($history = helpmenow_get_history($sessionid)) {
    $messages = helpmenow_format_messages($history);
    foreach ($history as $m) {
        $last_message = $m->id;
    }
} else {
    $messages = '';
    $last_message = 0;
}

echo <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
    <head>
        <title>$title</title>
        <link rel="stylesheet" type="text/css" href="$CFG->wwwroot/blocks/helpmenow/style/chat.css" />
        <script type="text/javascript">
            var helpmenow_url = "$CFG->wwwroot/blocks/helpmenow/ajax.php";
            var chat_session = $sessionid;
            var last_message = $last_message;
            var refresh;
            var plugin_refresh = new Array();
        </script>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js" type="text/javascript"></script>
        $jplayer
        <script src="$CFG->wwwroot/blocks/helpmenow/javascript/lib_2012083000.js" type="text/javascript"></script>
        <script src="$CFG->wwwroot/blocks/helpmenow/javascript/chat_2012082700.js" type="text/javascript"></script>
        $plugins_js
    </head>
    <body>
        <div id="helpmenow_chime"></div>
        $plugins_display
        <div id="chatDiv">$messages</div>
        <div id="inputDiv">
            <textarea id="inputTextarea" cols="30" rows="3">$textarea_message</textarea>
        </div>
    </body>
</html>
EOF;

?>
