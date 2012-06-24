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
 * Help me now plugin abstract class.
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/plugin_object.php');

abstract class helpmenow_plugin extends helpmenow_plugin_object {
    const table = 'plugin';

    /**
     * Cron delay in seconds; 0 represents no cron
     * @var int $cron_interval
     */
    public $cron_interval = 0;

    /**
     * Last cron timestamp
     * @var int $last_cron
     */
    public $last_cron;

    /**
     * "Installs" the plugin
     * @return boolean success
     */
    public static function install() {
        $plugin = new static();
        $plugin->last_cron = 0;
        $plugin->insert();
    }

    /**
     * Cron that will run everytime block cron is run.
     * @return boolean
     */
    public static function cron() {
        return true;
    }

    /**
     * Calls install for all plugins
     * @return boolean success
     */
    public final static function install_all() {
        $success = true;
        foreach (get_list_of_plugins('plugins', '', dirname(__FILE__)) as $pluginname) {
            require_once("$CFG->dirroot/blocks/helpmenow/plugins/$pluginname/plugin.php");
            $class = "helpmenow_plugin_$pluginname";
            $success = $success and $class::install();
        }
        return $success;
    }

    /**
     * Calls any existing cron functions of plugins
     * @return boolean
     */
    public final static function cron_all() {
        $success = true;
        foreach (get_list_of_plugins('plugins', '', dirname(__FILE__)) as $pluginname) {
            $record = get_record('block_helpmenow_plugin', 'plugin', $pluginname);
            $class = "helpmenow_plugin_$pluginname";
            $plugin = new $class(null, $record);
            if (($plugin->cron_interval != 0) and (time() >= $plugin->last_cron + $plugin->cron_interval)) {
                $class = "helpmenow_plugin_$pluginname";
                $success = $success and $class::cron();
                $plugin->last_cron = time();
                $plugin->update();
            }
        }
        return $success;
    }
}

?>
