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
 * This script handles GoToMeeting Help Me Now settings.
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die("Direct access to this location is not allowed.");

/*
$token_url = new moodle_url('/blocks/helpmenow/plugins/gotomeeting/token.php');
$token_url->param('admin', 1);
$token_url->param('redirect', qualified_me());
$token_url = $token_url->out();
 */

$settings->add(new admin_setting_heading('helpmenow_gotomeeting_heading',
                                         get_string('gotomeeting_settings_heading', 'block_helpmenow'),
                                         get_string('gotomeeting_settings_heading_desc', 'block_helpmenow') //.
                                         //"<a href='$token_url'>" .
                                         //get_string('gotomeeting_settings_admin_key', 'block_helpmenow') .
                                         //"</a>"
                                     ));

$settings->add(new admin_setting_configtext('helpmenow_gotomeeting_key',
                                            get_string('gotomeeting_settings_key', 'block_helpmenow'),
                                            get_string('gotomeeting_settings_key_desc', 'block_helpmenow'),
                                            '',
                                            PARAM_TEXT,
                                            50));
?>
