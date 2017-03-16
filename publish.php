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
 * @package   block_activity_publisher
 * @category  blocks
 * @copyright 2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/blocks/activity_publisher/lib/activity_publisher.class.php');

$contextid = required_param('contextid', PARAM_INT);
$courseid = required_param('course', PARAM_INT);
$fileid = optional_param('fileid', null, PARAM_INT);
$sharingcontext = optional_param('sharingcontext', null, PARAM_INT);

$block_context = context::instance_by_id($contextid);

// Security.

if (!$course = $DB->get_record('course', array('id' => $courseid))){
    print_error('coursemisconf');
}

require_course_login($course);

// Header and page start.

$url = new moodle_url('/blocks/activity_publisher/publish.php');
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_context($block_context);

$system_context = context_system::instance();

$fs = get_file_storage();
$out = '';

if ($fileinfo = $fs->get_file_by_id($fileid)) {
    $result = activity_publisher::publish_file($fileid);
    if ($result == -1) {
        $out .= $OUTPUT->box_start();
        $out .= $OUTPUT->notification(get_string('alreadypublished', 'block_activity_publisher'));
        $out .= $OUTPUT->box_end();
    }
}

echo $OUTPUT->header();
echo $out;
echo $OUTPUT->continue_button(new moodle_url('/blocks/activity_publisher/repo.php', array('contextid' => $contextid)));
echo $OUTPUT->footer();
