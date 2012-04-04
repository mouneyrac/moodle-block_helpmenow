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

class helpmenow_request_form extends moodleform {

    function definition() {
        global $CFG;

        $mform =& $this->_form;

        $hiddenfields = array(
            'queueid',
            'requested_userid',
        );

        # required description textarea with max length
        $mform->addElement('textarea', 'description', get_string('description', 'block_helpmenow'), "wrap='virtual' rows='6' cols='40'");
        $mform->addRule('description', null, 'required', null, 'client');
        $mform->addRule('description', get_string('max_length', 'block_helpmenow'), 'maxlength', 140, 'client');

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

/**
class amgr_asmt_pod_form extends moodleform {

    function definition() {
        global $CFG;

        $mform =& $this->_form;

        $hiddenfields = array(
            'search',
            'id',
            'master_course_version_id',
            'master_course_idstr',
            'status',
            'page',
        );
        $pod_types = array();
        $tmp = get_records('asmt_pod_type');
        foreach ($tmp as $t) {
            $pod_types[$t->id] = $t->name;
        }
        $status_idstrs = array(
            AMGR_GENIUS_ACTIVE => AMGR_GENIUS_ACTIVE,
            AMGR_GENIUS_ARCHIVED => AMGR_GENIUS_ARCHIVED
        );

        $mform->addElement('text', 'master_coursename', get_string('master_coursename', 'block_assessment_manager'), array('readonly'));
        $mform->addElement('text', 'version', get_string('version', 'block_assessment_manager'), array('readonly'));

        $mform->addElement('text', 'name', get_string('name', 'block_assessment_manager'), array('size'=>50));

        $mform->addElement('select', 'asmt_pod_type_id', get_string('asmt_pod_type', 'block_assessment_manager'), $pod_types);

        $mform->addElement('textarea', 'description', get_string("description", "block_assessment_manager"), 'wrap="virtual" rows="10" cols="70"');
        $mform->addElement('textarea', 'keywords', get_string("keywords", "block_assessment_manager"), 'wrap="virtual" rows="10" cols="70"');

        $mform->addElement('text', 'num', get_string('num', 'block_assessment_manager'), array('size' => 3));
        $mform->addElement('text', 'percent_transcript_credit', get_string('percent_transcript_credit', 'block_assessment_manager'), array('size' => 3));
        $mform->setType('percent_transcript_credit', PARAM_NUMBER);

        $mform->addElement('select', 'status_idstr', get_string('status', 'block_assessment_manager'), $status_idstrs);

        if (has_capability('moodle/site:doanything', get_context_instance(CONTEXT_SYSTEM, SITEID))) {
            $mform->addElement('checkbox', 'delete', get_string('delete', 'block_assessment_manager'));
            $mform->addElement('checkbox', 'confirm_delete', get_string('really_delete', 'block_assessment_manager'));

            $mform->disabledIf('confirm_delete', 'delete');
        }

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'), array('style' => 'margin-top:2em;'));
        $buttonarray[] = &$mform->createElement('reset', 'resetbutton', get_string('revert'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        foreach ($hiddenfields as $hf) {
            $mform->addElement('hidden', $hf, '');
        }
    }
}

class amgr_mcv2ap_form extends moodleform {

    function definition() {
        global $CFG;

        $mform =& $this->_form;

        $hiddenfields = array(
            'search',
            'master_course_version_id',
            'page',
        );
        $pod_types = get_records('asmt_pod_type');

        foreach ($pod_types as $p) {
            $p->name = strtolower($p->name);
            $mform->addElement('static', $p->name, '', '<span style="font-weight:bold;">'.get_string($p->name, 'block_assessment_manager').'</span>');
            $mform->addElement('advcheckbox', 'isrequired_'.$p->name, get_string('isrequired', 'block_assessment_manager'), '', array('group' => 'foo'), array(0, 1));
            $mform->addElement('date_selector', 'starttimestamp_'.$p->name, get_string('starttimestamp', 'block_assessment_manager'), null, array('style' => 'margin-bottom:2em;'));
        }

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'), array('style' => 'margin-top:2em;'));
        $buttonarray[] = &$mform->createElement('reset', 'resetbutton', get_string('revert'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        foreach ($hiddenfields as $hf) {
            $mform->addElement('hidden', $hf, '');
        }
    }
}
 */
?>
