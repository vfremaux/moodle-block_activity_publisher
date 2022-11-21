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
* import summary page, first step page in the activity import process.
* @author Wafa Adham, Adham Inc.
* @version 1.0
*/

require('../../config.php');

$courseid = required_param('course', PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('coursemisconf');
}

$context = context_course::instance($courseid);

$url = new moodle_url('/blocks/activity_publisher/summary.php', array('id' => $course->id));
$PAGE->set_context($context);
$PAGE->set_url($url);

$renderer = $PAGE->get_renderer('block_activity_publisher');

require_login($courseid);
require_capablity('block/activity_publisher:publish', $context);

echo $OUTPUT->header();
echo $renderer->importsummary($course);
echo $OUTPUT->print_footer();