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
        if (!empty($CFG->helpmenow_title)) {
            $this->title = $CFG->helpmenow_title;
        } else {
            $this->title = get_string('helpmenow', 'block_helpmenow'); 
        }

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

        $this->content->text .= helpmenow_block_interface();

        $privilege = get_field('sis_user', 'privilege', 'sis_user_idstr', $USER->idnumber);
        if ($privilege == 'TEACHER' or record_exists('block_helpmenow_helper', 'userid', $USER->id)) {
            $this->content->text .= '<div id="helpmenow_meetingid_div"></div><hr />';
            $token_url = new moodle_url("$CFG->wwwroot/blocks/helpmenow/plugins/gotomeeting/token.php");
            $token_url->param('redirect', qualified_me());
            $token_url = $token_url->out();
            $this->content->text .= "<div><a href='$token_url'>Allow GoToMeeting Access</a></div>";
        }

        $this->content->text .= '<div><a target="_blank" href="https://webdes2.vlacs.org//~eohare1/help_me_now/gtm_install.html">Set Up GoToMeeting</a></div>';

        # admin link
        $sitecontext = get_context_instance(CONTEXT_SYSTEM, SITEID);
        if (has_capability(HELPMENOW_CAP_MANAGE, $sitecontext)) {
            $admin = "$CFG->wwwroot/blocks/helpmenow/admin.php";
            $admin_text = get_string('admin_link', 'block_helpmenow');
            $this->content->footer .= "<a href='$admin'>$admin_text</a><br />";
        }
        if (has_capability(HELPMENOW_CAP_MANAGE, $sitecontext) or record_exists('block_helpmenow_helper', 'userid', $USER->id)) {
            $who = get_string('who', 'block_helpmenow');
            $this->content->footer .= "<a href='$CFG->wwwroot/blocks/helpmenow/hallway.php'>$who</a>";
        }

        $this->content->footer .= <<<EOF
<div id="helpmenow_last_refresh_div"></div>
<div style="text-align:right;">
    <div style="float:left;">
        <a href="javascript:void(0)" onclick="document.getElementById('helpmenow_chime').Play();">
            <img src="$CFG->wwwroot/blocks/helpmenow/media/Bell.png" />
        </a>
    </div>
EOF;
        $popout = get_string('popout', 'block_helpmenow');
        $this->content->footer .= link_to_popup_window("$CFG->wwwroot/blocks/helpmenow/popout.php", 'popout', $popout, 400, 250, null, null, true) . '</div>';

        return $this->content;
    }

    function cron() {
        $success = true;
        $success = $success and helpmenow_autologout_helpers();
        $success = $success and helpmenow_autologout_users();
        return $success;
    }
}

?>
