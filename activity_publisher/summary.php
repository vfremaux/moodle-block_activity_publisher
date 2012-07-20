<?php
    /**
    * Export summary
    */

    require_once('../../config.php');

    $mod_id = required_param('mod',PARAM_INT);
    $courseid = required_param('course', PARAM_INT); 
    $action = required_param('what', PARAM_TEXT);

    if (!$course = get_record('course', 'id', $courseid)){
        error("invalid course");
    }

    require_login($courseid);

    if (! $site = get_site()) {
        redirect($CFG->wwwroot .'/'. $CFG->admin .'/index.php');
    }

    print_header(strip_tags($site->fullname), $site->fullname, '', '', '',true, '', '');    

    print('<div id="content-cont">');    
    print_heading(get_string('exporting', 'block_activity_publisher'));

    $activity = get_record('modules', 'id', $mod_id);

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
			cm.module = {$mod_id} AND 
			cm.instance = a.id AND 
			cm.course = {$courseid}
	";

    echo '<form name="exportactivityform" method="post" action="export.php">';
    print('<div id="summary-cont">');
    print('<div id="title">'.get_string('summary', 'block_activity_publisher').'</div>');

    print('<table id="summary-table" cellpadding="5" cellspacing="5">');

    print('<tr>');
    print('<td class="title">'.get_string('course_name', 'block_activity_publisher').'</td>');
    print('<td>'.$course->fullname.'</td>');
    print('</tr>');

    print('<tr>');
    print('<td class="title">'.get_string('activity_type','block_activity_publisher').'</td>');
    print('<td>'.$activity->name.'</td>');
    print('</tr>');

    print('<tr>');
    print('<td class="title" valign="top">'.get_string('activity_instances','block_activity_publisher').'</td>');
    print('<td>');

    if($activity_instances){
        print('<table width="100%">');
        //print all instances 
        foreach($activity_instances as $ai){
        	if (!preg_match('/label$/', $activity->name)){
	        	$name = $ai->name;
	        } else {
	        	$name = '<b>'.get_string('modulename', $activity->name).' '.$ai->id.' :</b> '. shorten_text(clean_param($ai->name, PARAM_NOTAGS), 50);
	        }
        	if ($action == 'publish'){
	            print('<tr><td><input type="checkbox" name="instance[]"  value="'.$ai->id.'" /> </td><td>'.$name.'</td></tr>');    
	        } else {
	            print('<tr><td><input type="radio" name="instance[]"  value="'.$ai->id.'" /> </td><td>'.$name.'</td></tr>');    
	        }
        }
		print('</table>');
    } else {
		echo (get_string('activity_no_instances', 'block_activity_publisher'));
    }

    print('</td>');
    print('</tr>');
    print('</table>');

    print('<div id="download-btn">
    <input type="hidden" name="course" value="'.$courseid.'" />
    <input type="hidden" name="mod" value="'.$mod_id.'" />
    <input type="hidden" name="what" value="'.$action.'" />
    ');

    if($activity_instances){
    	print('<input type="submit" value="'.get_string('publish_activity','block_activity_publisher')." ".ucfirst(get_string('modulename', $activity->name)).'" />');
    }
    
    print('</div>');
    print('</div>');
    print('</form>');
    print('</div>');    
    
    echo '<p><hr><center>';
    $options['id'] = $courseid;
    print_single_button($CFG->wwwroot.'/course/view.php', $options, get_string('backtocourse', 'block_activity_publisher'));
	echo '</center></p>';     
        
    print_footer($course);    

?>