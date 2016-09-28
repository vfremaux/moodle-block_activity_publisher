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

$modid = required_param('mod',PARAM_INT);
$courseid = required_param('course', PARAM_INT); 
$contextid = required_param('contextid', PARAM_INT); 
$blockid = required_param('bid', PARAM_INT); 

$action = required_param('what', PARAM_TEXT);
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

/// header and page start
$url = new moodle_url('/blocks/activity_publisher/summary.php', array('course' => $courseid));
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_context($block_context);

$PAGE->navigation->add($course->fullname, $CFG->wwwroot.'/course/view.php?id='.$courseid);

echo $OUTPUT->header();

echo $OUTPUT->heading("Backup Activities");    

print('<div id="content-cont">');    
// print_heading(get_string('exporting', 'block_activity_publisher'));

$activity = $DB->get_record('modules', array('id'=> $modid));

//get all activity instances 
$activity_instances = get_coursemodules_in_course($activity->name, $course->id);

$act_name = $activity->name;
$query = "
	SELECT 
		* 
	FROM 
		{$CFG->prefix}{$act_name} a ,
		{$CFG->prefix}course_modules cm 
	WHERE 
		cm.module = {$modid} AND 
		cm.instance = a.id AND 
		cm.course = {$courseid}
";

echo '<form name="exportactivityform" method="post" action="export.php">';
echo '<div id="summary-cont">';
echo '<div id="title">'.get_string('summary', 'block_activity_publisher').'</div>';

echo '<table id="summary-table" cellpadding="5" cellspacing="5">';

echo '<tr>';
echo '<td class="title">'.get_string('course_name', 'block_activity_publisher').'</td>';
echo '<td>'.$course->fullname.'</td>';
echo '</tr>';

echo '<tr>';
echo '<td class="title">'.get_string('activity_type','block_activity_publisher').'</td>';
echo '<td>'.$activity->name.'</td>';
echo '</tr>';

echo '<tr>';
echo '<td class="title" valign="top">'.get_string('activity_instances','block_activity_publisher').'</td>';
echo '<td>';

if($activity_instances){
    echo '<table width="100%">';
    //print all instances 
    $i = 0;
    foreach($activity_instances as $ai){            
    	if (!preg_match('/label$/', $activity->name)){
        	$name = $ai->name;
        } else {
        	$name = '<b>'.get_string('modulename', $activity->name).' '.$ai->id.' :</b> '. shorten_text(clean_param($ai->name, PARAM_NOTAGS), 50);
        }
    	if ($action == 'publish'){
            echo '<tr><td><input type="checkbox" id="'.$ai->id.'" name="instance[]"  value="'.$ai->id.'" onchange="check_submit_activity(this)" /> </td><td>'.$name.'</td></tr>';    
        } else {
            echo '<tr><td><input type="radio" name="instance[]"  value="'.$ai->id.'" onchange="check_submit_activity(this)" /> </td><td>'.$name.'</td></tr>';
        }
        $i++;
    }
	echo '</table>';
} else {
	echo get_string('activity_no_instances', 'block_activity_publisher');
}

echo '</td>';
echo '</tr>';
echo '</table>';

echo '<div id="download-btn">
<input type="hidden" name="course" value="'.$courseid.'" />
<input type="hidden" name="mod" value="'.$modid.'" />
<input type="hidden" name="what" value="'.$action.'" />
<input type="hidden" name="bid" value="'.$blockid.'" />
';

if($activity_instances){
	$modnamestr = (count($activity_instances) > 1) ?  get_string('modulenameplural', $activity->name) : get_string('modulename', $activity->name) ;
	$the = (count($activity_instances) > 1) ?  get_string('theplural', 'block_activity_publisher') : get_string('the', 'block_activity_publisher') ;
	$actionactivitystr = get_string($action.'_activity','block_activity_publisher', $the.ucfirst($modnamestr));
	echo '<input type="submit" name="submitpublish" disabled="disabled" class="submit-disabled" value="'.$actionactivitystr.'" />';
}

echo '</div>';
echo '</div>';
echo '</form>';
echo '</div>';    

echo '<p><hr><center>';
$options['id'] = $courseid;
echo $OUTPUT->single_button($CFG->wwwroot.'/course/view.php', "Back to Course", get_string('backtocourse', 'block_activity_publisher'));
echo '</center></p>';     

echo $OUTPUT->footer(); 
