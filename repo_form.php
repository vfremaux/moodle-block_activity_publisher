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
 * Import backup file form
 * @package   block_activity_publisher
 * @category  blocks
 * @copyright 2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

class activity_restore_form extends moodleform {

    function definition() {
        global $COURSE;

        $mform =& $this->_form;
        $contextid = $this->_customdata['contextid'];

        $mform->addElement('hidden', 'contextid', $contextid);
        $mform->setType('contextid', PARAM_INT);

        $mform->addElement('filepicker', 'backupfile', get_string('files'),null, array('maxfiles' => 1, 'maxbytes' => $COURSE->maxbytes, 'accepted_types' => array('.mbz')));

        $submit_string = get_string('restore');
        $this->add_action_buttons(false, $submit_string);
    }

}
