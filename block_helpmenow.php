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

        global $COURSE;

        $this->content = (object) array(
            'text' => '',
            'footer' => '',
        );

        helpmenow_ensure_queue_exists(); # autocreate a course queue if necessary

        $queues = helpmenow_queue::get_queues();

        foreach ($queues as $q) {
            $this->content->text .= $q->name . "<br />";
            switch $q->get_privilege() {
            case HELPMENOW_QUEUE_HELPER:
                # TODO: login/out link & indicator
                foreach ($q->request as $r) {
                    # todo: get the name of submitting user
                    # todo: popuplink
                    # description
                    $this->content->text .= $r->description . "<br />";
                }
                break;
            case HELPMENOW_QUEUE_HELPEE:
                break;
        }

        return $this->content;
    }

    /**
     * Overriden block_base method that is ccalled when Moodle's cron runs.
     *
     * @return boolean
     */
    function cron() {
        return true;
    }
}

?>
