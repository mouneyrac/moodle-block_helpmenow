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

        $this->content->text .= <<<EOF
<div id="helpmenow_queue_div"></div>
EOF;

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
            $login_url = new moodle_url("$CFG->wwwroot/blocks/helpmenow/login.php");
            $login_url->param('login', 0);
            $logout = link_to_popup_window($login_url->out(), "login", 'Leave Office', 400, 500, null, null, true);
            $login_url->param('login', 1);
            $login = link_to_popup_window($login_url->out(), "login", 'Enter Office', 400, 500, null, null, true);
            $this->content->text .= <<<EOF
<div id="helpmenow_office">
    <div><b>My Office</b></div>
    <div id="helpmenow_motd" onclick="helpmenow_toggle_motd(true);" style="border:1px dotted black;width:12em;min-height:1em;">$helpmenow_user->motd</div>
    <textarea id="helpmenow_motd_edit" onkeypress="return helpmenow_motd_textarea(event);" onblur="helpmenow_toggle_motd(false)" style="display:none;" rows="4" cols="22"></textarea>
    <div style="text-align: center; font-size:small;">
        <div id="helpmenow_logged_in_div_0" $instyle>$logout</div>
        <div id="helpmenow_logged_out_div_0" $outstyle>Out of Office | $login</div>
    </div>
    <div>Online Students:</div>
    <div id="helpmenow_users_div"></div>
</div><hr />
EOF;
            break;
        case 'STUDENT':
            $this->content->text .= '
                <div>Online Instructors:</div>
                <div id="helpmenow_users_div"></div><hr />
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
<embed id="helpmenow_chime" src="$CFG->wwwroot/blocks/helpmenow/cowbell.wav" autostart="false" width="0" height="0" enablejavascript="true" style="position:absolute; left:0px; right:0px; z-index:-1;" />
EOF;

        if ($privilege == 'TEACHER' or record_exists('block_helpmenow_helper', 'userid', $USER->id)) {
            $this->content->text .= '<div id="helpmenow_meetingid_div"></div><hr />';
            $token_url = new moodle_url("$CFG->wwwroot/blocks/helpmenow/plugins/gotomeeting/token.php");
            $token_url->param('redirect', qualified_me());
            $token_url = $token_url->out();
            $this->content->text .= "<div><a href='$token_url'>Allow GoToMeeting Access</a></div>";
        }

        $this->content->text .= '<div><a target="_blank" href="http://vlacs.org/~dzaharee/gotomeeting-setup.html">Set Up GoToMeeting</a></div>';

        return $this->content;
    }
}

?>
