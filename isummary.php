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
* import summery page, first step page in the activity import process.
* @author Wafa Adham, Adham Inc.
* @version 1.0
*/

require('../../config.php');

$course_id = required_param('course',PARAM_INT); 

if (!$course = $DB->get_record('course', array('id' => $course_id))) {
    print_error('coursemisconf');
}

require_login($course_id);

if (! $site = get_site()) {
    redirect(new moodle_url('/admin/index.php'));
}

echo $OUTPUT->header();
echo '<div id="content-cont">';

echo $OUTPUT->heading(get_string('pluginname', 'block_activity_publisher'));
echo '<div id="summary-cont">';
echo '<div id="title">'.get_string('import', 'block_activity_publisher').'</div>';

echo '<form enctype="multipart/form-data" method="post" action="import.php">
<table id="summary-table" cellpadding="5" cellspacing="5">';

echo '<tr>';
echo '<td class="title">'.get_string('coursename', 'block_activity_publisher').'</td>';
echo '<td>'.$course->fullname.'</td>';
echo '</tr>';

echo '<tr>';
echo '<td class="title">Select Activity :</td>';
echo '<td><input type="file" name="upfile" id="upfile" /></td>';
echo '</tr>';
echo '</table>';

echo '<div id="download-btn">';

echo '<input type="hidden" value="'.$course_id.'" name="cid"/><input type="submit" value="Import" />';

echo '</form></div>';

echo '</div>';

print('</div>');

$OUTPUT->print_footer();