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
 * This script displays the block interface as it's own page
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once((dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

require_login(0, false);

$meetingid_div = '';
$bottom = '1em';
$privilege = get_field('sis_user', 'privilege', 'sis_user_idstr', $USER->idnumber);
if ($privilege == 'TEACHER' or record_exists('block_helpmenow_helper', 'userid', $USER->id)) {
    $bottom = '4em';
    $meetingid_div = <<<EOF
<div style="position: absolute; bottom: 1em; left: 1em; right: 1em; height: 2em; padding-left: .5em; border: 1px solid black;">
    <div id="helpmenow_meetingid_div" style="margin-top: .5em;"></div>
</div>
EOF;
}

//print_header('Help Me Now', '', '', 'inputTextarea');
print_header('VLACS Communicator', '', '', 'inputTextarea');

$output = <<<EOF
<div style="position: absolute; top: 0px; left: 0px; right: 0px; bottom: 0px; background-color: white;">
<div id="chatDiv" style="position: absolute; top: 1em; left: 1em; right: 1em; bottom: $bottom; padding: .5em; overflow: auto; border: 1px solid black;">
EOF;

$output .= helpmenow_block_interface();
$output .= '<div id="helpmenow_last_refresh_div" style="font-size:small;"></div>';

$output .= "</div>$meetingid_div</div></div></body></html>";

echo $output;

?>
