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

$userid = required_param('userid', PARAM_INT);
$recent = optional_param('recent', 0, PARAM_INT);

require_login(0, true);

# contexts and cap check
$admin = has_capability(HELPMENOW_CAP_MANAGE, get_context_instance(CONTEXT_SYSTEM, SITEID));
if (!($admin or $userid==$USER->id)) {
    redirect();
}

# title, navbar, and a nice box
if (!empty($CFG->helpmenow_title)) {
    $blockname = $CFG->helpmenow_title;
} else {
    $blockname = get_string('helpmenow', 'block_helpmenow'); 
}
$title = get_string('chathistories', 'block_helpmenow');
$nav = array(
    array('name' => $blockname),
    array('name' => $title)
);
print_header($title, $title, build_navigation($nav));
print_box_start();

$this_url = new moodle_url("$CFG->wwwroot/blocks/helpmenow/chathistorylist.php");
$this_url->param('userid', $userid);
$orderbyname_url = $this_url->out();

$this_url->param('recent', 1);
$orderbydate_url = $this_url->out();

# Get a list of all session2user sessions to query for users who have had 
# conversations with $userid
$sessions = get_records_list('block_helpmenow_session2user', 'userid', $userid, '', 'sessionid');
$sessionids = array();
foreach ($sessions as $s) {
    $sessionids[] = $s->sessionid;
}

if (count($sessions) < 1) {
    // No histories availible

} else {
    $sessions = implode(', ', $sessionids);

    $orderby = "u.lastname, u.firstname";
    if ($recent) {
        $orderby = "s2u.last_refresh";
    }

    $sql = "
        SELECT u.*, s2u.sessionid
        FROM {$CFG->prefix}block_helpmenow_session2user s2u
        JOIN {$CFG->prefix}user u ON u.id = s2u.userid
        WHERE s2u.userid <> $USER->id
        AND s2u.sessionid IN ($sessions)
        ORDER BY $orderby
        ";
    $other_user_recs = get_records_sql($sql);

    print_heading(get_string('viewconversation', 'block_helpmenow'), 'left');
    $orderbystring = get_string('orderby', 'block_helpmenow') . " ( <a href=$orderbyname_url>" . get_string('name', 'block_helpmenow') . "</a> | <a href=$orderbydate_url>" . get_string('mostrecentconversation', 'block_helpmenow') . '</a> )';
    print "<div>$orderbystring</div><br />";

    foreach ($other_user_recs as $u) {
        $history_url = new moodle_url("$CFG->wwwroot/blocks/helpmenow/history.php#recent");
        $history_url->param('session', $u->sessionid);
        $history_url->param('date', '-1 year');
        $name = fullname($u);
        $history_link = link_to_popup_window($history_url->out(), $u->sessionid, $name, 400, 500, null, null, true);
        $history_link = '<div>'.$history_link." ($u->username)</div>";
        print $history_link;
    }

}

print_box_end();

# footer
print_footer();

?>
