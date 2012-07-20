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
 * Help me now wiziq user2plugin class.
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/user2plugin.php');
require_once(dirname(__FILE__) . '/plugin.php');

define('HELPMENOW_WIZIQ_DURATION', 60);     # this is minutes as that's what the api takes

class helpmenow_user2plugin_wiziq extends helpmenow_user2plugin {
    /**
     * Extra fields
     * @var array $extra_fields
     */
    protected $extra_fields = array(
        'class_id',
        'presenter_url',
        'duration',
        'timecreated',
    );

    /**
     * wiziq class_id
     * @var int $class_id
     */
    public $class_id;

    /**
     * wiziq presenter_url
     * @var string $presenter_url
     */
    public $presenter_url;

    /**
     * duration in seconds of meeting
     * @var integer $duration
     */
    public $duration;

    /**
     * timestamp of creation
     * @var integer $timecreated
     */
    public $timecreated;

    /**
     * plugin
     * @var string $plugin
     */
    public $plugin = 'wiziq';

    /**
     * Create the meeting. Caller will insert record.
     */
    public function create_meeting() {
        global $USER;

        $params = array(
            'title' => fullname($USER),
            'start_time' => date('m/d/Y G:i:s'),
            'time_zone' => date('e'),
            'presenter_id' => $USER->id,
            'presenter_name' => fullname($USER),
            'duration' => HELPMENOW_WIZIQ_DURATION,
        );

        $response = helpmenow_plugin_wiziq::api('create', $params);
        $this->class_id = (integer) $response->create->class_details->class_id;
        $this->presenter_url = (string) $response->create->class_details->presenter_list->presenter[0]->presenter_url;
        $this->duration = HELPMENOW_WIZIQ_DURATION * 60;    # we're saving in seconds instead of minutes
        $this->timecreated = time();

        return true;
    }
}

?>
