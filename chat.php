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
require_once(dirname(__FILE__) . '/plugin.php');

require_login(0, false);

# verify session
$sessionid = required_param('session', PARAM_INT);
if (!helpmenow_verify_session($sessionid)) {
    helpmenow_fatal_error('You do not have permission to view this page.');
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

$plugins = array();
foreach (helpmenow_plugin::get_plugins() as $pluginname => $class) {
    $plugin_text = $class::display($privileged);
    if (!strlen($plugin_text)) {
        continue;
    }
    $plugins[] = '<div>'.$plugin_text.'</div>';
}
if (count($plugins)) {
    $plugins = '<div id="pluginDiv">'.implode(' | ', $plugins).'</div>';
} else {
    $plugins = '';
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
            var last_message = 0;
            var refresh;
        </script>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js" type="text/javascript"></script>
        <script src="$CFG->wwwroot/blocks/helpmenow/javascript/lib.js" type="text/javascript"></script>
        <script src="$CFG->wwwroot/blocks/helpmenow/javascript/chat.js" type="text/javascript"></script>
    </head>
    <body>
        <div>
            <object id="helpmenow_chime" type="audio/x-wav" data="$CFG->wwwroot/blocks/helpmenow/cowbell.wav" width="0" height="1">
              <param name="src" value="$CFG->wwwroot/blocks/helpmenow/cowbell.wav" />
              <param name="autoplay" value="false" />
              <param name="autoStart" value="0" />
            </object>
        </div>
        $plugins
        <div id="chatDiv"></div>
        <div id="inputDiv">
            <textarea id="inputTextarea" cols="30" rows="3">Type your message here and press the "enter" or "return" key.</textarea>
        </div>
    </body>
</html>
EOF;

?>
