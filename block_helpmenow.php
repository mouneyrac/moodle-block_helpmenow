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
        $this->title = helpmenow_title();

        $plugin = new object;
        require(dirname(__FILE__) . "/version.php");
        $this->version = $plugin->version;
        $this->cron = $plugin->cron;
    }

    function get_content() {
        if ($this->content !== NULL) {
            return $this->content;
        }

        global $CFG, $SESSION;

        $this->content = (object) array(
            'text' => '',
            'footer' => '',
        );

        # noscript block - if js is not there
        $this->content->text .= '<noscript>'.get_string('noscript', 'block_helpmenow').'</noscript>';

        # the first time a user loads the block this session try to popout
        $popout_url = "$CFG->wwwroot/blocks/helpmenow/popout.php";
        # do stuff that stuff that should be done when a user first logs in
        if (!isset($SESSION->helpmenow_first_load)) {
            $SESSION->helpmenow_first_load = true;
            helpmenow_clean_sessions();     # clean up users sessions

            # try to popout the interface
            $this->content->text .= <<<EOF
<script>
    try {
        var popout = window.open('', 'hmn_popout', 'menubar=0,location=0,scrollbars,resizable,width=250,height=400');
        if (popout.location.href == "about:blank" || typeof popout.location === 'undefined') {
            popout.location = "$popout_url";
        }
    } catch (error) {
    }
</script>
EOF;
        }

        $this->content->text .= helpmenow_block_interface();

        $this->content->footer .= <<<EOF
<div style="text-align:right;">
    <div style="float:left;">
        <a href="javascript:void(0)" onclick="helpmenow.chime();">
            <img src="$CFG->wwwroot/blocks/helpmenow/media/Bell.png" />
        </a>
    </div>
EOF;
        $popout = get_string('popout', 'block_helpmenow');
        $this->content->footer .= link_to_popup_window($popout_url, 'hmn_popout', $popout, 400, 250, null, null, true) . '</div>';

        return $this->content;
    }

    function cron() {
        $success = true;
        $success = $success and helpmenow_autologout_helpers();
        $success = $success and helpmenow_autologout_users();
        $success = $success and helpmenow_email_messages();
        $success = $success and helpmenow_clean_all_sessions();
        return $success;
    }
}

?>
