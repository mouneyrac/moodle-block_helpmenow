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
 * Help me now user2plugin abstract class. Plugins can extend this class
 * to store any necessary per user data by defining $extra_fields.
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/db_object.php');

abstract class helpmenow_user2plugin extends helpmenow_db_object {
    const table = 'user2plugin';

    /**
     * Array of required db fields.
     * @var array $required_fields
     */
    protected $required_fields = array(
        'id',
        'timecreated',
        'timemodified',
        'modifiedby',
        'userid',
        'plugin',
    );

    /**
     * user.id
     * @var int $userid
     */
    public $userid;

    /**
     * plugin.name
     * @var strnng $plugin
     */
    public $plugin;
}

?>
