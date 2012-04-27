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
 * This script handles global help me now settings.
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die("Direct access to this location is not allowed.");

# $settings->add(new admin_setting_heading('heading',
#                                          get_string('settings_heading', 'block_helpmenow'),
#                                          get_string('settings_heading_desc', 'block_helpmenow')));

$choices = array();
foreach (get_list_of_plugins('plugins', '', dirname(__FILE__)) as $plugin) {
    $choices[$plugin] = $plugin;
}
$settings->add(new admin_setting_configselect('helpmenow_default_plugin',
                                              get_string('settings_plugin', 'block_helpmenow'),
                                              get_string('settings_plugin_desc', 'block_helpmenow'),
                                              'native',
                                              $choices));

$settings->add(new admin_setting_configtime('helpmenow_request_timeout',
                                            'helpmenow_request_timeout2',
                                            get_string('settings_request_timeout', 'block_helpmenow'),
                                            get_string('settings_request_timeout_desc', 'block_helpmenow'),
                                            array('h' => 0, 'm' => 5)));

$settings->add(new admin_setting_configtext('helpmenow_refresh_rate',
                                            get_string('settings_refresh_rate', 'block_helpmenow'),
                                            get_string('settings_refresh_rate_desc', 'block_helpmenow'),
                                            15,
                                            PARAM_INT,
                                            4));

$settings->add(new admin_setting_configcheckbox('helpmenow_autocreate_course_queue',
                                                get_string('settings_autocreate_queue', 'block_helpmenow'),
                                                get_string('settings_autocreate_queue_desc', 'block_helpmenow'),
                                                0));

$settings->add(new admin_setting_configcheckbox('helpmenow_autoadd_queue_helpers',
                                                get_string('settings_autoadd_helpers', 'block_helpmenow'),
                                                get_string('settings_autoadd_helpers_desc', 'block_helpmenow'),
                                                0));

$settings->add(new admin_setting_configtime('helpmenow_meeting_timeout',
                                            'helpmenow_meeting_timeout2',
                                            get_string('settings_meeting_timeout', 'block_helpmenow'),
                                            get_string('settings_meeting_timeout_desc', 'block_helpmenow'),
                                            array('h' => 2, 'm' => 0)));
?>
