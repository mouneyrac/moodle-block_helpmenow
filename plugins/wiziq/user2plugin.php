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

class helpmenow_user2plugin_wiziq extends helpmenow_user2plugin {
    /**
     * Extra fields
     * @var array $extra_fields
     */
    protected $extra_fields = array(
        'classid',
        'presenter_url',
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
            'start_time' => gmdate('m/d/Y G:i:s', time() + (24*60*60)),  # let's try 5 minutes in the future
            'presenter_email' => $USER->email,
        );

        $response = helpmenow_plugin_wiziq::api('create', $params);
        $this->class_id = $response->create->class_details->class_id;
        $this->presenter_url = $response->create->class_details->presenter_list->presenter[0]->presenter_email;

        return true;
    }
}

?>
