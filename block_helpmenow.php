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
 * block_helpmenow class definition, which extends Moodle's block_base.
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die("Direct access to this location is not allowed.");

# our library
require_once(dirname(__FILE__) . '/lib.php');

class block_helpmenow extends block_base {

    function init() {
        global $CFG;
        $this->title = get_string('helpmenow', 'block_helpmenow'); 

        $plugin = new object;
        require(dirname(__FILE__) . "/version.php");
        $this->version = $plugin->version;
        $this->cron = $plugin->cron;
    }

    function get_content() {
        if ($this->content !== NULL) {
            return $this->content;
        }

        global $CFG, $USER;

        $this->content = (object) array(
            'text' => '',
            'footer' => '',
        );

        $this->content->text .= '<div id="helpmenow_queue_div"></div><hr />';

        $privilege = get_field('sis_user', 'privilege', 'sis_user_idstr', $USER->idnumber);
        switch ($privilege) {
        case 'TEACHER':
            helpmenow_ensure_user_exists();
            $helpmenow_user = get_record('block_helpmenow_user', 'userid', $USER->id);
            $instyle = $outstyle = '';
            if ($helpmenow_user->isloggedin) {
                $outstyle = 'style="display: none;"';
            } else {
                $instyle = 'style="display: none;"';
            }
            $this->content->text .= <<<EOF
<div id="helpmenow_office">
    <div><b>My Office</b></div>
    <div id="helpmenow_motd" onclick="helpmenow_toggle_motd(true);" style="border:1px dotted black;width:12em;min-height:1em;">$helpmenow_user->motd</div>
    <textarea id="helpmenow_motd_edit" onkeypress="return helpmenow_motd_textarea(event);" onblur="helpmenow_toggle_motd(false)" style="display:none;" rows="4" cols="22"></textarea>
    <div style="text-align: center; font-size:small;">
        <div id="helpmenow_logged_in_div_0" $instyle><a href="javascript:void();" onclick="helpmenow_login(false, 0);">Leave Office</a></div>
        <div id="helpmenow_logged_out_div_0" $outstyle>Out of Office | <a href="javascript:void();" onclick="helpmenow_login(true, 0);">Enter Office</a></div>
    </div>
    <div>Online Students:</div>
    <div id="helpmenow_users_div"></div>
</div>
EOF;
            break;
        case 'STUDENT':
            $this->content->text .= '
                <div>Online Instructors:</div>
                <div id="helpmenow_users_div"></div>
            ';
            break;
        }
        $this->content->text .= <<<EOF
<script type="text/javascript" src="$CFG->wwwroot/blocks/helpmenow/javascript/lib.js"></script>
<script type="text/javascript" src="$CFG->wwwroot/blocks/helpmenow/javascript/block.js"></script>
<script type="text/javascript">
    var helpmenow_url = "$CFG->wwwroot/blocks/helpmenow/ajax.php";
    helpmenow_block_refresh();
    var chat_t = setInterval(helpmenow_block_refresh, 10000);
</script>
EOF;

        return $this->content;
    }
}

?>
