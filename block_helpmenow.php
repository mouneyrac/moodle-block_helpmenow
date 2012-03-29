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

global $CFG;
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
        $this->version = 2012031400;
        # $this->cron = 60; # we're going to use cron, but not yet
    }

    /**
     * Overridden block_base method that generates the content diplayed in the
     * block and returns it.
     *
     * @return stdObject
     */
    function get_content() {
        if (isset($this->content)) { return $this->content; }

        global $COURSE, $USER;

        $this->content = (object) array(
            'text' => '',
            'footer' => '',
        );

        helpmenow_ensure_queue_exists(); # autocreates a course queue if necessary

        $queues = helpmenow_queue::get_queues();
        foreach ($queues as $q) {
            $this->content->text .= $q->name . "<br />";
            switch ($q->get_privilege()) {
            case HELPMENOW_QUEUE_HELPER:
                # login/out link
                $login = new moodle_url("$CFG->wwwroot/blocks/helpmenow/login.php");
                $login->params(array(
                    'courseid' => $COURSE->id,
                    'queueid' => $q->id,
                ));
                if ($q->helper[$USER->id]->isloggedin) {
                    $login->param('login', 0);
                    $login_text = get_string('login', 'block_helpmenow');
                } else {
                    $login->param('login', 1);
                    $login_text = get_string('logout', 'block_helpmenow');
                }
                $login = $login->out();
                $this->content->text .= "<a href='$login'>$login_text</a><br />";

                # requests
                foreach ($q->request as $r) {
                    $connect = new moodle_url("$CFG->wwwroot/blocks/helpmenow/connect.php");
                    $connect->param('requestid', $r->id);
                    $name = fullname(get_record('user', 'id', $r->userid));
                    $this->content->text .= link_to_popup_window($connect->out(), null, $name, 400, 500, null, null, true) . "<br />";
                    $this->content->text .= $r->description . "<br />";
                }
                break;
            case HELPMENOW_QUEUE_HELPEE:
                # if the user has a request, display it, otherwise give a link
                # to create one
                if (isset($q->request[$USER->id])) {
                    $this->content->text .= get_string('pending', 'block_helpmenow') . "<br />";
                    $this->content->text .= $q->request[$USER->id]->description;
                } else {
                    if ($q->check_available()) {
                        $request = new moodle_url("$CFG->wwwroot/blocks/helpmenow/new_request.php");
                        $request->param('queueid', $q->id);
                        $request_text = get_string('new_request', 'block_helpmenow');
                        $this->content->text .= link_to_popup_window($request->out(), null, $request_text, 400, 500, null, null, true) . "<br />";
                    } else {
                        # todo: make this smarter (helpers leave message or configurable)
                        $this->content->text .= get_string('queue_na', 'block_helpmenow');
                    }
                }
                break;
            }
        }

        return $this->content;
    }

    /**
     * Overriden block_base method that is called when Moodle's cron runs.
     *
     * @return boolean
     */
    function cron() {
        return true;
    }
}

?>
