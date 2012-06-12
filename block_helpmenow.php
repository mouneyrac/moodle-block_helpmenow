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

require_once(dirname(__FILE__) . '/lib.php');

class block_helpmenow extends block_base {
    /**
     * Overridden block_base method that sets block title and version.
     *
     * @return null
     */
    function init() {
        global $CFG;
        $this->title = get_string('helpmenow', 'block_helpmenow'); 

        $plugin = new object;
        require(dirname(__FILE__) . "/version.php");
        $this->version = $plugin->version;
        $this->cron = $plugin->cron;
    }

    /**
     * Overridden block_base method that generates the content diplayed in the
     * block and returns it.
     *
     * @return stdObject
     */
    function get_content() {
        if (isset($this->content)) { return $this->content; }

        global $CFG, $COURSE, $USER;

        $this->content = (object) array(
            'text' => '',
            'footer' => '',
        );

        # For now, restrict to tech dept for testing.
        /*
        switch ($USER->id) {
            # test accounts:
        case 57219:
        case 56956:
            # tech staff
        case 8712:
        case 58470:
        case 930:
        case 919:
        case 57885:
        case 52650:
        case 37479:
        case 56385:
        case 56528:
        case 5:
            break;
        default:
            if ($USER->id % 2) {
                return $this->content;
            }
        }
         */

        helpmenow_ensure_queue_exists(); # autocreates a course queue if necessary

        # contexts
        $sitecontext = get_context_instance(CONTEXT_SYSTEM, SITEID);
        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);

        # queues
        $url = $CFG->wwwroot . "/blocks/helpmenow/";
        $this->content->text .= "
            <script type=\"text/javascript\">
                var helpmenow_url = \"$url\";
                var helpmenow_interval = ".HELPMENOW_AJAX_REFRESH.";
            </script>
            <script type=\"text/javascript\" src=\"{$CFG->wwwroot}/blocks/helpmenow/lib.js\"></script>
            <div id=\"helpmenow_queue\"></div>
            <script type=\"text/javascript\">
                // call helpmenow_refresh() immediately and periodically
                helpmenow_queue_refresh();
                var helpmenow_tq = setInterval(helpmenow_queue_refresh, helpmenow_interval);
            </script>
        ";

        # instructor
        $sql = "
            SELECT q.*
            FROM {$CFG->prefix}block_helpmenow_queue q
            WHERE q.userid = $USER->id
        ";
        if ($instructor_queue = get_record_sql($sql)) {
            $instructor_queue = helpmenow_queue::get_instance(null, $instructor_queue);

            $this->content->text .= "
                <hr />
                <div id=\"helpmenow_instructor\">
                <b>My Office</b>
                <div id=\"helpmenow_motd\" onclick=\"helpmenow_toggle_motd(true);\" style=\"border:1px dotted black;width:12em;min-height:1em;\">$instructor_queue->description</div>
                <textarea id=\"helpmenow_motd_edit\" onkeypress=\"return helpmenow_enter_motd(event);\" onblur=\"helpmenow_toggle_motd(false)\" style=\"display:none;\" rows=\"4\" cols=\"22\"></textarea>
            ";
            $this->content->text .= "<div id=\"helpmenow_login\" style='text-align:center;font-size:small;'></div>";
            $this->content->text .= "Online students:<br />";

            $this->content->text .= "
                <div id=\"helpmenow_students\"></div>
                <script type=\"text/javascript\">
                    // call helpmenow_refresh() immediately and periodically
                    helpmenow_instructor_refresh();
                    var helpmenow_t = setInterval(helpmenow_instructor_refresh, helpmenow_interval);
                </script>
                </div>
            ";
        }

        # helper link
        $sql = "
            SELECT *
            FROM {$CFG->prefix}block_helpmenow_helper h
            JOIN {$CFG->prefix}block_helpmenow_queue q ON h.queueid = q.id
            WHERE q.type = '".HELPMENOW_QUEUE_TYPE_HELPDESK."'
            AND h.userid = $USER->id
        ";
        if (record_exists_sql($sql)) {
            $helper = new moodle_url("$CFG->wwwroot/blocks/helpmenow/helpmenow.php");
            $helper_text = get_string('helper_link', 'block_helpmenow');
            $this->content->text .= '<hr />' . link_to_popup_window($helper->out(), 'helper', $helper_text, 400, 700, null, null, true) . "<br />";
        }

        # block message
        if (strlen($CFG->helpmenow_block_message)) {
            $this->content->text .= '<hr />' . $CFG->helpmenow_block_message;
        }

        # admin link
        if (has_capability(HELPMENOW_CAP_MANAGE, $sitecontext)) {
            $admin = new moodle_url("$CFG->wwwroot/blocks/helpmenow/admin.php");
            $admin->param('courseid', $COURSE->id);
            $admin = $admin->out();
            $admin_text = get_string('admin_link', 'block_helpmenow');
            $this->content->footer .= "<a href='$admin'>$admin_text</a><br />";
            $this->content->footer .= "<a href='$CFG->wwwroot/blocks/helpmenow/hallway.php'>Administrators' Hallway</a>";
        }

        return $this->content;
    }

    /**
     * Overriden block_base method that is called when Moodle's cron runs.
     *
     * @return boolean
     */
    function cron() {
        $success = true;

        # clean up helpers
        $success = $success and helpmenow_helper::auto_logout();

        # clean up old meetings
        $success = $success and helpmenow_meeting::clean_meetings();

        # clean up abandoned requests
        $success = $success and helpmenow_request::clean_requests();

        # call plugin crons
        $success = $success and helpmenow_plugin::cron_all();

        return $success;
    }

    /**
     * Overriden block_base method that is called when block is installed
     */
    function after_install() {
        helpmenow_plugin::install_all();
    }
}

?>
