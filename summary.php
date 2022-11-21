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
 *
 * Export summary
 */

require('../../config.php');

$courseid = required_param('course', PARAM_INT);
$contextid = required_param('contextid', PARAM_INT);
$modid = required_param('mod',PARAM_INT);
$blockid = required_param('bid', PARAM_INT);
$action = optional_param('what', '', PARAM_TEXT);

$PAGE->requires->js('/blocks/activity_publisher/js/block_js.js');

if (!$course = $DB->get_record('course', array('id' => $courseid))){
    print_error('coursemisconf');
}

// Security.

require_login($courseid);
$coursecontext = context_course::instance($courseid);
require_capability('moodle/course:manageactivities', $coursecontext);

if (!$site = get_site()) {
    redirect(new moodle_url('/'. $CFG->admin .'/index.php'));
}

$block_context = context::instance_by_id($contextid);

// Header and page start.
$params = array('course' => $courseid, 'mod' => $modid, 'contextid' => $contextid, 'bid' => $blockid);
$url = new moodle_url('/blocks/activity_publisher/summary.php', $params);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_context($block_context);
$PAGE->navigation->add($course->fullname, new moodle_url('/course/view.php', array('id' => $courseid)));

$renderer = $PAGE->get_renderer('block_activity_publisher');

$activity = $DB->get_record('modules', array('id' => $modid));
$activityinstances = get_coursemodules_in_course($activity->name, $course->id);

echo $OUTPUT->header();
echo $renderer->summary($course, $activity, $activityinstances);
echo $OUTPUT->footer();
