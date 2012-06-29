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
 * block_helpmenow form definitions, which extend moodleform.
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die("Direct access to this location is not allowed.");

require_once($CFG->libdir.'/formslib.php');

class helpmenow_queue_form extends moodleform {
    function definition() {
        global $CFG;

        $mform =& $this->_form;

        $hiddenfields = array(
            'queueid',
        );

        # name
        $mform->addElement('text', 'name', get_string('name'), array('size' => 50));
        $mform->addRule('name', null, 'required', null, 'client');

        # description textarea with max length of 140
        $mform->addElement('textarea', 'description', get_string('description', 'block_helpmenow'), "wrap='virtual' rows='6' cols='40'");
        $mform->addRule('description', null, 'required', null, 'client');
        $mform->addRule('description', get_string('max_length', 'block_helpmenow'), 'maxlength', 140, 'client');

        # todo: verify number
        $mform->addElement('text', 'weight', get_string('weight', 'block_helpmenow'), array('size' => 4));
        $mform->addRule('weight', null, 'required', null, 'client');

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        $buttonarray[] = &$mform->createElement('reset', 'resetbutton', get_string('revert'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        foreach ($hiddenfields as $hf) {
            $mform->addElement('hidden', $hf, '');
        }
    }
}

?>
