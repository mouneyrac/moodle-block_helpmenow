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

print_header($title, '', '', 'inputTextarea');

$output = <<<EOF
<div style="position: absolute; top: 0px; left: 0px; right: 0px; bottom: 0px; background-color: white;">
EOF;

$plugins = '';
$top = '1em';
if ($privileged) {
    $plugins = <<<EOF
<div id="pluginDiv" style="position: absolute; top: 1em; left: 1em; right: 1em; height: 2em; padding-left: .5em; border: 1px solid black;">
    <div style="margin-top: .5em; display: inline-block;"><a href="javascript:void(0)" onclick="helpmenow_invite();">Invite To My GoToMeeting</a></div> |
    <div style="margin-top: .5em; display: inline-block;"><a href="javascript:void(0)" onclick="helpmenow_wiziq_invite();">Invite To My WizIQ</a></div>
</div>
EOF;
    $top = '4em';
}

$output .= <<<EOF
<embed id="helpmenow_chime" src="$CFG->wwwroot/blocks/helpmenow/cowbell.wav" autostart="false" width="0" height="1" enablejavascript="true" style="position:absolute; left:0px; right:0px; z-index:-1;" />
$plugins
<div id="chatDiv" style="position: absolute; top: $top; left: 1em; right: 1em; bottom: 6em; padding: .5em; overflow: auto; border: 1px solid black; min-height: 5em;"> </div>
<div style="position: absolute; left: 0px; right: 0px; bottom: 0px; height: 4em; padding: 1em;">
    <textarea id="inputTextarea" cols="30" rows="3" style="height: 100%; width: 100%; resize: none;" onkeypress="return helpmenow_chat_textarea(event);"></textarea>
</div>
<script type="text/javascript" src="$CFG->wwwroot/blocks/helpmenow/javascript/lib.js"></script>
<script type="text/javascript" src="$CFG->wwwroot/blocks/helpmenow/javascript/chat.js"></script>
<script type="text/javascript">
    var helpmenow_url = "$CFG->wwwroot/blocks/helpmenow/ajax.php";
    var helpmenow_session = $sessionid;
    var helpmenow_last_message = -1;
    helpmenow_chat_refresh();
    var chat_t = setInterval(helpmenow_chat_refresh, 2000);
</script>
</div>
</div>
</body>
</html>
EOF;

echo $output;

?>
