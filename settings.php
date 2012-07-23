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

$plugins = get_list_of_plugins('plugins', '', dirname(__FILE__));
foreach ($plugins as $pluginname) {
    $path = "$CFG->dirroot/blocks/helpmenow/plugins/$pluginname/settings.php";
    if (file_exists($path)) {
        # plugin heading
        $settings->add(new admin_setting_heading("helpmenow_{$pluginname}_heading",
            get_string("{$pluginname}_settings_heading", 'block_helpmenow'),
            get_string("{$pluginname}_settings_heading_desc", 'block_helpmenow')
        ));
        # setting to enable/disable plugin
        $settings->add(new admin_setting_configcheckbox("helpmenow_{$pluginname}_enabled",
            get_string("{$pluginname}_settings_enabled", 'block_helpmenow'),
            get_string("{$pluginname}_settings_enabled_desc", 'block_helpmenow')
        ));
        require($path);
    }
}

?>
