<?php
 /**
* activity publisher class, encapsulate the entry points for the activity backup
* process.
* @author Wafa Adham, Adham Inc.
* @version 1.0
*/

//check if this function already loaded , this means the backup lib already included.

require_once ("$CFG->dirroot/backup/lib.php");
require_once ("$CFG->libdir/blocklib.php");
require_once ("$CFG->libdir/adminlib.php");
require_once ("$CFG->dirroot/blocks/activity_publisher/lib/backup_result.class.php");


class activity_publisher
{
    /**
    * @param object $course the surrounding course
    * @param object $module instance of “module” record
    * @param array $instance_id the module instance ids, the function normally accepts an array , and 
    * return the link for the generated export file ,if the function is givven a comma seperated instances 
    * ids then all are packed in the same file, in all cases the function generate 1 export package file.
    */
    public static function backup_single_module($course, $module, $instance_id) { 
	    global $CFG, $preferences, $SESSION;

	    if(!function_exists('user_check_backup')){
	    	require_once ("$CFG->dirroot/blocks/activity_publisher/backup/backuplib.php");
	    }

	    //we should always prepare an array with 1 element as our backup function used to take 
	    //an array of ids .
	    if(is_array($instance_id)){
	       	//take the first element only .
	       	$ids_arr[0] = $instance_id[0]; 
	    } else {
	      	$ids_arr[0] = $instance_id;  
	    }
	    
	    //check the instance_id is valid 
	    $result = get_record('course_modules', 'id', $ids_arr[0]);
	        
	    if(!$result){
	        if(debugging()){
	        	print("Invalid course module id.");
	        }
	        return false;
	    }
	   
	    $course_mod = $result;
	   
	    unset($SESSION->backupprefs);
	
	    //lets prepare the backup object parameters.           
	    //as this is the first version , i decided to keep some of the original 
	    //backup preferences as we are going to use them in the coming version.
	    
	    $preferences = new StdClass;
	    $preferences->backup_metacourse = 1;
	    $preferences->backup_users = 0;
	    $preferences->backup_logs = 0;
	    $preferences->backup_user_files = 0;
	    $preferences->backup_course_files = 0;
	    $preferences->backup_gradebook_history = 0;
	    $preferences->backup_site_files = 0;
	    $preferences->backup_messages = 0;
	    $preferences->backup_blogs = 0;
	    $preferences->backup_course = $course->id;
	    $preferences->activity_instances = $ids_arr; //
	    
	    //build the activity filename
	    $backup_name = backup_get_zipfile_name($module, $course_mod);   
	    
	    $preferences->backup_name = $backup_name;
	    $preferences->backup_unique_code = rand(1000, 100000);
	
	    backup_fetch_prefs_from_request($preferences, $count, $course, $module->id);
	    //store it in the session ..
	    //$SESSION->backupprefs[$course->id] = $preferences;
	    //Another Info
	    backup_add_static_preferences($preferences);
	
	    if ($count == 0) {
	        notice("No backupable modules are installed!");
	    }
	 
	    $errorstr = '';
	    
	    # Doing the Backup Check before Execution.
	       //This is the alignment of every row in the table
	    $table->align = array ('left', 'right'); 
	    if ($allmods = get_records('modules')) {
			foreach ($allmods as $mod) {
	            $modname = $mod->name;
	            $modfile = $CFG->dirroot.'/mod/'.$modname.'/backuplib.php';
	            if (!file_exists($modfile)) {
	                continue;
	            }
	            require_once($modfile);
	            $modbackup = $modname.'_backup_mods';
	            //If exists the lib & function
	            $var = 'exists_'.$modname;
	            if (isset($preferences->$var) && $preferences->$var) {
	                $var = 'backup_'.$modname;
	                //Only if selected
	                if (!empty($preferences->$var) and ($preferences->$var == 1)) {
	                    //Print the full tr
	                    //echo "<tr>";
	                    //echo "<td colspan=\"2\">";
	                    //Print the mod name
	                    //echo "<b>".get_string("include")." ".get_string("modulenameplural",$modname)." ";
	                    //Now look for user-data status
	                    $backup_user_options[0] = get_string('withoutuserdata');
	                    $backup_user_options[1] = get_string('withuserdata');
	                    $var = 'backup_user_info_'.$modname;
	                    //Print the user info
	                    echo $backup_user_options[$preferences->$var].'</b>';
	                    //Call the check function to show more info
	                    $modcheckbackup = $modname.'_check_backup_mods';
	                    $var = $modname.'_instances';
	                    $instancestopass = array();
	                    if (!empty($preferences->$var) && is_array($preferences->$var) && count($preferences->$var)) {
	                        $table->data = array();
	                        $countinstances = 0;
	                        foreach ($preferences->$var as $instance) {
	                            $var1 = 'backup_'.$modname.'_instance_'.$instance->id;
	                            $var2 = 'backup_user_info_'.$modname.'_instance_'.$instance->id;
	                            if (!empty($preferences->$var1)) {
	                                $obj = new StdClass;
	                                $obj->name = $instance->name;
	                                $obj->userdata = $preferences->$var2;
	                                $obj->id = $instance->id;
	                                $instancestopass[$instance->id] = $obj;
	                                $countinstances++;	
	                            }
	                        }
	                    }
	                    $data = $modcheckbackup($course->id, $preferences->$var, $preferences->backup_unique_code, $instancestopass);
	                }
	            }
	        }
	    }
    
	    #start the execution process.
	    $status = backup_execute($preferences, $errorstr);
    
	    $br = new backup_result();
	    $br->op_result  = $status;
    
    	if($status == true){
        	//put back the download link 
        	$br->download_link = $CFG->wwwroot.'/file.php/'.$course->id.'/backupdata/'.$preferences->backup_name;
        	$br->storage_path = $CFG->dataroot.'/'.$course->id.'/backupdata/'.$preferences->backup_name;
    	}
    
    	return $br;
    }

    /**
    * @param object $course the surrounding course
    * @param object $module
    * @param array $activities_arr gives the configuration list of activities to export
    */
	public static function course_backup_activities($course, $module, $activities_arr) { 
     
       	if(count($activities_arr) <= 0 || $activities_arr == null){
           	//invalid activities 
           	return false;
       	}
       
       	if($course == null){
           	return false;
       	}
     
       	foreach($activities_arr as $instance){
          	$results[$instance] =  self::backup_single_module($course,$module,$instance);
       	}

       	return $results ; 
	}

    /**
    * load the activities select box for the given course id
    * 
    * @param mixed $course_id
    * @return mixed select box with available activities to export.
    */
    public static function load_course_activities_select($course_id){
        global $CFG;

        $allowed_mod = array('quiz', 'assignment', 'data', 'glossary', 'userquiz', 'forum', 'customlabel', 'choice', 'survey', 'feedback');
        if (!empty($CFG->activitypublisherallowmods)){
        	$allowed_mod = explode(',', $CFG->activitypublisherallowmods);
        }
        $modules = self::get_course_mods($course_id);

        $select = '<select name="mod" >';
         
        if ( $modules &&  (count($modules) > 0)){
            foreach ($modules as $mod){
                if(in_array($mod->name,$allowed_mod)){
	                $select .= "<option value='" . $mod->id . "'>" . get_string('modulename', $mod->name) . "</options>";
	            }
			}
        } else {
        	$nomodulesstr = get_string('nomodulestoexport', 'block_activity_publisher');
			$select .= "<option>$nomodulesstr</options>";
        }

        $select .= "</select>";

		return $select;
	}
        
        
    public static function get_course_mods($course_id){
		global $CFG;
            
		$query = "
			SELECT DISTINCT 
				cm.module,
				m.name,
				m.id as id 
			FROM 
				{$CFG->prefix}course_modules cm,
				{$CFG->prefix}modules m 
			WHERE 
				cm.module = m.id AND 
				cm.course = {$course_id}
		";
		
		$modules = get_records_sql($query);
		
		return $modules;
	}
        
    /**
    * Restore module
    * 
    * @param mixed $course
    * @param mixed $module
    * @param mixed $instance_id
    */
	public static function restore_single_module($to_course_id,$file) { 
        
        global $CFG, $preferences, $SESSION, $USER;

        require_once($CFG->dirroot."/blocks/activity_publisher/backup/restorelib.php");

        //Optional
        //check destination course 
        if(!$destination_course = get_record('course','id',$to_course_id)){
	        error("invalid destination course");
        }

        $current_course_id = $to_course_id ;

        $course = get_record('course', 'id', $current_course_id);

        $to = $to_course_id;
        $method = 'manual';
        $backup_unique_code = rand(1000,27654567);

        //resetting params
        if (isset($SESSION->course_header)) {
	        unset ($SESSION->course_header);
        }
        if (isset($SESSION->info)) {
	        unset ($SESSION->info);
        }
        if (isset($SESSION->backupprefs)) {
	        unset ($SESSION->backupprefs);
        }
        if (isset($SESSION->restore)) {
	        unset ($SESSION->restore);
        }
        if (isset($SESSION->import_preferences)) {
	        unset ($SESSION->import_preferences);
        }

        if (!$to && isset($SESSION->restore->restoreto) && isset($SESSION->restore->importing) && isset($SESSION->restore->course_id)) {
	        $to = $SESSION->restore->course_id;
        }

        //Check site
        if (!$site = get_site()) {
        	error('Site not found!');
        }

        backup_required_functions();

        //Check backup_version
        if ($file) {
	        $linkto = 'restore.php?id='.$current_course_id.'&amp;file='.$file;
        } else {
	        $linkto = 'restore.php';
        }
        upgrade_backup_db($linkto);

        //Get strings
        if (empty($to)) {
	        $strcourserestore = get_string('courserestore');
        } else {
	        $strcourserestore = get_string('importdata');
        }
        $stradministration = get_string('administration');

        if (!$file) {
	        print_header("$site->shortname: $strcourserestore", $site->fullname, $navigation);
	        print_heading(get_string('nofilesselected'));
	        print_continue("$CFG->wwwroot/$CFG->admin/index.php");
	        print_footer();
	        exit;
        }

        //Adjust some php variables to the execution of this script
        @ini_set('max_execution_time', '3000');
        if (empty($CFG->extramemorylimit)) {
          	raise_memory_limit('128M');
        } else {
            raise_memory_limit($CFG->extramemorylimit);
        }

        //**restore_precheck
        $errorstr = '';
        if (!empty($SESSION->restore->importing)) {
                define('RESTORE_SILENTLY', true);
        }

        $status = restore_precheck($current_course_id, $file, $errorstr, false, $backup_unique_code);
        
        //**restore properties (restore_forms)

        if (!($info = $SESSION->info)) {
        	error('info object missing from session');
        }
        if (!($course_header = $SESSION->course_header)) {
            error('course_header object missing from session');
        }

        //load all mods 
        $allmods = get_records('modules');

        foreach ($allmods as $mod) {
	        $modname = $mod->name;
	        $modrestore = $modname.'_restore_mods';
	        //If exists the lib & function
	        $exist = 'exists_'.$modname;
	        $restore_var = 'restore_'.$modname;
	        $user_info_var = 'restore_user_info_'.$modname;
	        
	        if (isset($$exist)) {
	            if ($$exist) {
	                //Now check that we have that module info in the backup file
	                if (isset($info->mods[$modname]) && $info->mods[$modname]->backup == 'true') {
	                   
	                   //load instances  
	                    if (isset($info->mods[$modname]->instances)) {
	                        $instances = $info->mods[$modname]->instances;
	                    }
	                   
	                    if (!empty($instances) && is_array($instances)) {
	     
	                        //loop all the instances and make sure they are all restorable.
	                        foreach ($instances as $instance) {
	                            
	                            $var = 'restore_'.$modname.'_instance_'.$instance->id;
	                            $$var = 1; //we restore all instances.
	                            $var = 'restore_user_info_'.$modname.'_instance_'.$instance->id;
	                            $$var = 0; //we dont restore user info
	                        }
	                    }
	                } else {
	                    //Module isn't restorable
	                }
	            } else {
	                //Module isn't restorable
	            }
	        } else {
	            //Module isn't restorable
	        }
		}// all mods loop

        //********************************************************************
        //** restore_check
        global $restore;

        $coursestartdatedateoffset = 0;
        $restore->course_startdateoffset = 0;    

        if ($SESSION) {
        $info = $SESSION->info;
        $course_header = $SESSION->course_header;
            if (isset($SESSION->restore)) {
                $restore = $SESSION->restore;
            }
        } 

        //Checks for the required restoremod parameters
        if ($allmods = get_records('modules')) {
            foreach ($allmods as $mod) {
                $modname = $mod->name;
                $var = 'restore_'.$modname;
                $$var = 1; //optional_param( $var,0);
                $var = 'restore_user_info_'.$modname;
                $$var = 0; //optional_param( $var,0);
                $instances = !empty($info->mods[$mod->name]->instances) ? $info->mods[$mod->name]->instances : NULL;
                if ($instances === NULL) {
                    continue;
                }
                foreach ($instances as $instance) {
                    $var = 'restore_'.$modname.'_instance_'.$instance->id;
                    $$var = 1;
                    $var = 'restore_user_info_'.$modname.'_instance_'.$instance->id;
                    $$var = 0;
                }
            }
        }
        //restoreto
        //        DebugBreak();
        $restore_restoreto = $to;
        //restore_course_files
        $restore_course_files = true;

        //We are here, having all we need !!
        //Create the restore object and put it in the session
        $restore->backup_unique_code = $backup_unique_code;
        $restore->file = $file;
        if ($allmods = get_records('modules')) {
	        foreach ($allmods as $mod) {
	            $modname = $mod->name;
	            $var = 'restore_'.$modname;
	            $restore->mods[$modname]->restore = $$var;
	            $var = 'restore_user_info_'.$modname;
	            $restore->mods[$modname]->userinfo = $$var;
	            $instances = !empty($info->mods[$mod->name]->instances) ? $info->mods[$mod->name]->instances : NULL;
	            if ($instances === NULL) {
	                continue;
	            }
	            foreach ($instances as $instance) {
	                $var = 'restore_'.$modname.'_instance_'.$instance->id;
	                $restore->mods[$modname]->instances[$instance->id]->restore = $$var;
	                $var = 'restore_user_info_'.$modname.'_instance_'.$instance->id;
	                $restore->mods[$modname]->instances[$instance->id]->userinfo = $$var;
	            }
	        }
        }
        $restore->restoreto = $restore_restoreto;
        $restore->course_files = $restore_course_files;
        $restore->users = 0;
        /*
        $restore->metacourse=$restore_metacourse;
        $restore->users=$restore_users;
        $restore->groups=$restore_groups;
        $restore->logs=$restore_logs;
        $restore->user_files=$restore_user_files;
        $restore->site_files=$restore_site_files;
        $restore->messages=$restore_messages;
        $restore->blogs=$restore_blogs;
        $restore->restore_gradebook_history=$restore_gradebook_history;
        */
        $restore->course_id = $to;
        //add new vars to restore object
        $restore->course_startdateoffset = 0;
        $restore->course_shortname = $course->fullname;

        // Non-cached - get accessinfo
        if (isset($USER->access)) {
	        $accessinfo = $USER->access;
        } else {
	        $accessinfo = get_user_access_sitewide($USER->id);
        }

        // create role mappings, not sure all should be here
        if ($data2 = data_submitted()) {
	        foreach ($data2 as $tempname => $tempdata) {
	            if (strstr($tempname, 'roles_')) {
	                $temprole = explode('_', $tempname);
	                $oldroleid = $temprole[1];
	                $newroleid = $tempdata;
	                $restore->rolesmapping[$oldroleid] = $newroleid;
	            }
	        }
        }

        // default role mapping for moodle < 1.7
        if ($defaultteacheredit = optional_param('defaultteacheredit', 0, PARAM_INT)) {
	        $restore->rolesmapping['defaultteacheredit'] = $defaultteacheredit;
        }
        if ($defaultteacher = optional_param('defaultteacher', 0, PARAM_INT)) {
	        $restore->rolesmapping['defaultteacher'] = $defaultteacher;
        }
        if ($defaultstudent = optional_param('defaultstudent', 0, PARAM_INT)) {
	        $restore->rolesmapping['defaultstudent'] = $defaultstudent;
        }


        // Get all the courses the user is able to restore to
        $mycourses = get_user_courses_bycap($USER->id, 'moodle/site:restore', $accessinfo, true, 'c.sortorder ASC',  array('id', 'fullname', 'shortname', 'visible'));

        // Calculate if the user can create courses
        $cancreatecourses = user_can_create_courses();

        if (empty($restore->course_id) && ($restore->restoreto == RESTORETO_CURRENT_DELETING || $restore->restoreto == RESTORETO_CURRENT_ADDING)) {
	        $restore->course_id = $id; /// Force restore to current course, disabling pick course from list
        }

        $restore->deleting = false; 

        $SESSION->restore = $restore;
        /// Printout messages

        if(!empty($messages)){
	        foreach ($messages as $message) {
	            echo '<p>' . $message . '</p>';
	        }
        }
        //*****************************************************************************************************
        //** restore_execute.

        $errorstr = '';
        $info = $SESSION->info;
        $course_header = $SESSION->course_header;
        $restore = $SESSION->restore;

        //Add info->original_wwwroot to $restore to be able to use it in all the restore process
        //(mainly when decoding internal links)
        $restore->original_wwwroot = $info->original_wwwroot;
        // Copy $info->original_siteidentifier, is present, so backup_is_same_site can work.
        if (isset($info->original_siteidentifier)) {
	        $restore->original_siteidentifier = $info->original_siteidentifier;
        }
        //Add info->backup_version to $restore to be able to detect versions in the restore process
        //(to decide when to convert wiki texts to markdown...)
        $restore->backup_version = $info->backup_backup_version;

        //Check admin
        if (!empty($id)) {
	        if (!has_capability('moodle/site:restore', get_context_instance(CONTEXT_COURSE, $id))) {
		        if (empty($to)) {
		            error("You need to be a teacher or admin user to use this page.", "$CFG->wwwroot/login/index.php");
		        } else {
		            if (!has_capability('moodle/site:restore', get_context_instance(CONTEXT_COURSE, $to)) 
		                && !has_capability('moodle/site:import',  get_context_instance(CONTEXT_COURSE, $to))) {
		                error("You need to be a teacher or admin user to use this page.", "$CFG->wwwroot/login/index.php");
		            }
		        }
	        }
        } else {
	        if (!has_capability('moodle/site:restore', get_context_instance(CONTEXT_SYSTEM))) {
		        error("You need to be an admin user to use this page.", "$CFG->wwwroot/login/index.php");
	        }
        }

        $status = restore_execute($restore,$info,$course_header,$errorstr);

        if (!$status) {
	        error ("An error has occurred and the restore could not be completed!");
        }

        if (empty($restore->importing)) {
	        //Print final message
	        print_simple_box(get_string('restorefinished'), 'center');
        } else {
	        print_simple_box(get_string('importdatafinished'), 'center');
	        $file = $CFG->dataroot.'/'.$SESSION->import_preferences->backup_course.'/backupdata/'.$SESSION->import_preferences->backup_name;
	        if (is_readable($file)) {
		        unlink($file);
	        } else {
		        error_log("import course data: couldn't unlink $file");
	        }
	        unset($SESSION->restore);
		}
	}

	/**
	* checks if the file name matches commonpatterns for activity backups
	*
	*/
	public static function is_activity_backup($archivename){
		
		return preg_match('/[a-fA-F0-9]{32}-activity-.*\.zip$/', $archivename);
		
	}
}
    
    
    
    
?>