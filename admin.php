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
 * This script handles administration of queues.
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

# moodle stuff
require_once((dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->libdir . '/weblib.php');

# helpmenow library
require_once(dirname(__FILE__) . '/lib.php');

# require login
require_login(0, false);

# get our parameters

# title and navbar
$title = get_string('admin_heading', 'block_helpmenow');
$nav = array(array('name' => $title));
print_header($title, $title, build_navigation($nav));

# footer
print_footer();

?>
