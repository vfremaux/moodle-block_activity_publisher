<?php
/**
* export summary 
* @author Wafa Adham, Adham Inc.
* @version 1.0
*/

require_once('../../config.php');  
require_once('lib/activity_publisher.class.php');  
    
    $mod_id = required_param('mod', PARAM_INT);
    $course_id = required_param('course', PARAM_INT);
    $action = optional_param('what', PARAM_TEXT);
    $instances = required_param('instance', PARAM_INT);
    $id = $course_id;
  
    define('BACKUP_SILENTLY', 1);
    
    $course = get_record('course', 'id', $id);
    
    if(!$course){
        error("invalid course.");
    }

    require_login($id);
    
    //get course context
    $context = get_context_instance(CONTEXT_COURSE, $id);
    
    if (!empty($course->id)) {
        if (!has_capability('moodle/site:backup', $context)) {
            if (empty($to)) {
                error("You need to be a teacher or admin user to use this page.", "$CFG->wwwroot/login/index.php");
            } else {
                if (!has_capability('moodle/site:backup', get_context_instance(CONTEXT_COURSE, $to))) {
                    error("You need to be a teacher or admin user to use this page.", "$CFG->wwwroot/login/index.php");
                }
            }
        }
    } else {
        if (!has_capability('moodle/site:backup', get_context_instance(CONTEXT_SYSTEM))) {
            error("You need to be an admin user to use this page.", "$CFG->wwwroot/login/index.php");
        }
    }
    
    //initialize the activity publisher object.
    $ap = new activity_publisher();  
    
    print('<ul>');
    if(!defined('BACKUP_SILENTLY')){
    	print("<li>Checking configurations.....</li>");
    }
 
    if(!defined('BACKUP_SILENTLY')) {  
	    print("<li>Starting the backup process</li>") ;
    }
     
    //clean up backup pref 
    unset ($SESSION->backupprefs);
    
    // get the module object;
    $module = get_record('modules', 'id', $mod_id);
        
    //we have all the infos , now we start
    $filelink = $ap::course_backup_activities($course, $module, $instances);

    if (!$filelink) {
        error ("The backup did not complete successfully.", "$CFG->wwwroot/course/view.php?id=$course->id");
    }
  
    print('</ul>');

	if ($action == 'publish'){
	    redirect("$CFG->wwwroot/files/index.php?id=".$course->id."&amp;wdir=/backupdata");    
	} elseif ($action == 'share'){

		// when sharing, only one activity can be selected		
		$singleinstance = array_pop($filelink);
		$singlecoursemod = array_pop($instances);
		$cm = get_record('course_modules', 'id', $singlecoursemod);
		$instance = get_record($module->name, 'id', $cm->instance);
		
		// we make a shared resource entry, put it in session and invoke metadataform to finish indexation
		require_once($CFG->dirroot.'/mod/sharedresource/sharedresource_entry.class.php');
		require_once($CFG->dirroot.'/mod/sharedresource/sharedresource_metadata.class.php');
		require_once($CFG->dirroot.'/mod/sharedresource/lib.php');


		$mtdstandard = sharedresource_plugin_base::load_mtdstandard($CFG->pluginchoice);
	    
	    $sharedresource_entry = new sharedresource_entry(false); 
	    $sharedresource_entry->title = $instance->name;
	    $titleelm = $mtdstandard->getTitleElement();
	    $sharedresource_entry->add_element($titleelm->name, $instance->name, $CFG->pluginchoice);
	    $description = '';
	    if (!empty($instance->description)){ 
	    	$description = addslashes($instance->description);
	    } elseif(!empty($instance->summary)){
	    	$description = addslashes($instance->summary);
	    } elseif(!empty($instance->intro)){
	    	$description = addslashes($instance->intro);
	    }
	    
	    $sharedresource_entry->description = addslashes($description);
	    $descriptionelm = $mtdstandard->getDescriptionElement();
	    $sharedresource_entry->add_element($descriptionelm->name, $description, $CFG->pluginchoice);
		$sharedresource_entry->keywords = '';
	    $sharedresource_entry->type = 'file';
        $hash = sharedresource_sha1file($singleinstance->storage_path);
        $sharedresource_entry->identifier = $hash;
        $sharedresource_entry->file = $hash.'-'.basename($singleinstance->storage_path);
        $sharedresource_entry->tempfilename = $singleinstance->storage_path;
        if (function_exists('mime_content_type')){
            $sharedresource_entry->mimetype = mime_content_type($sharedresource_entry->tempfilename);
        }
        $sharedresource_entry->url = '';
        // do not record instance yet, rely on metadataform output to do it properly
	    // if (!record_exists('sharedresource_entry', 'identifier', $sharedresource_entry->identifier)){
	        // $sharedresource_entry->add_instance();
	    // }
	    
	    $SESSION->sr_entry = serialize($sharedresource_entry);
	    $paramstr = "course={$id}&type=file&add=sharedresource&mode=add&pluginchoice={$CFG->pluginchoice}";
	    redirect($CFG->wwwroot.'/mod/sharedresource/metadataform.php?'.$paramstr);
}
  
?>