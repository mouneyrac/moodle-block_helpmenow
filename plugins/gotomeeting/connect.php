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
 * This script handles opening g2m's join page in an iframe
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

# moodle stuff
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');

# g2m meeting
require_once(dirname(__FILE__) . '/meeting.php');

# require login
require_login(0, false);

# get our parameters
$meetingid = required_param('meetingid', PARAM_INT);

# get the meeting
$meeting = new helpmenow_meeting_gotomeeting($meetingid);

# check to make sure this user belongs in this meeting
if (!isset($meeting->meeting2user[$USER->id])) {
    # todo: print a wrong user message and close
}

# title, navbar
$title = get_string('connect', 'block_helpmenow');
$nav = array(array('name' => $title));
print_header($title, $title, build_navigation($nav));

print_box_start();

echo "<p>" . get_string('g2m_connecting', 'block_helpmenow') . "</p>" .
    "<iframe width='100%' height='400px' src='$meeting->join_url'></iframe>";

print_box_end();

# footer
print_footer();

?>
