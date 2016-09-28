<?php //$Id: restorelib.php,v 1.5 2012-07-18 16:47:54 vf Exp $
    //Functions used in restore

    require_once($CFG->libdir.'/gradelib.php');
    require_once($CFG->dirroot.'/backup/restorelib.php');

    //This function makes all the necessary calls to xxxx_decode_content_links_caller()
    //function in each module/block/course format..., passing them the desired contents to be decoded
    //from backup format to destination site/course in order to mantain inter-activities
    //working in the backup/restore process
    function ap_restore_decode_content_links($restore) {
        global $CFG;

        $status = true;

        if (!defined('RESTORE_SILENTLY')) {
            echo "<ul>";
        }

        // Recode links in the course summary.

		// ... no content in course summary (no course level data) ...
        
        if (!defined('RESTORE_SILENTLY')) {
            echo '</li>';
        }

        // Recode links in section summaries.

		// ... no content in section summaries (no course level data) ...

        // Restore links in modules.
        foreach ($restore->mods as $name => $info) {
            //If the module is being restored
            if (isset($info->restore) && $info->restore == 1) {
                //Check if the xxxx_decode_content_links_caller exists
                include_once("$CFG->dirroot/mod/$name/restorelib.php");
                $function_name = $name."_decode_content_links_caller";
                if (function_exists($function_name)) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo "<li>".get_string ("from")." ".get_string("modulenameplural", $name);
                    }
                    $status = $function_name($restore) && $status;
                    if (!defined('RESTORE_SILENTLY')) {
                        echo '</li>';
                    }
                }
            }
        }

        // For the course format call its decode_content_links method (if it exists)

		// ... no course format content ...

        // Process all html text also in blocks too

		// ... no block content ...

        // Restore links in questions.
     
        require_once("$CFG->dirroot/blocks/activity_publisher/backup/question/restorelib.php");
        if (!defined('RESTORE_SILENTLY')) {
            echo '<li>' . get_string('from') . ' ' . get_string('questions', 'quiz');
        }
       
        $status = ap_question_decode_content_links_caller($restore) && $status;
        if (!defined('RESTORE_SILENTLY')) {
            echo '</li>';
        }

        if (!defined('RESTORE_SILENTLY')) {
            echo "</ul>";
        }

        return $status;
    }



    //This function is called from all xxxx_decode_content_links_caller(),
    //its task is to ask all modules (maybe other linkable objects) to restore
    //links to them.

	// Note : this function is a callack from all other parts 
    // of the code. We CANNOT overide it to ap scope.
    /* function restore_decode_content_links_worker($content,$restore) {
    }*/


    //This function creates all the categories and questions
    //from xml
    function ap_restore_create_questions($restore,$xml_file) {

        global $CFG, $db;

        $status = true;
        //Check it exists
        if (!file_exists($xml_file)) {
            $status = false;
        }
        //Get info from xml
        if ($status) {
            //info will contain the old_id of every category
            //in backup_ids->info will be the real info (serialized)
            $info = restore_read_xml_questions($restore,$xml_file);
        }
        //Now, if we have anything in info, we have to restore that
        //categories/questions
        if ($info) {
            if ($info !== true) {
                $status = $status &&  ap_restore_question_categories($info, $restore);
            }
        } else {
            $status = false;
        }
        return $status;
    }


    //This function restores the course files from the temp (course_files) directory to the
    //dataroot/course_id directory
    function ap_restore_course_files($restore) {
        global $CFG;

        $status = true;

        $counter = 0;

        //First, we check to "course_id" exists and create is as necessary
        //in CFG->dataroot
        $dest_dir = $CFG->dataroot."/".$restore->course_id;
        $status = check_dir_exists($dest_dir, true);

        //Now, we iterate over "course_files" records to check if that file/dir must be
        //copied to the "dest_dir" dir.
        $rootdir = $CFG->dataroot."/temp/backup/".$restore->backup_unique_code."/course_files";
        //Check if directory exists
        if (is_dir($rootdir)) {
            $list = list_directories_and_files($rootdir);
            if ($list) {
                //Iterate
                $counter = 0;
                foreach ($list as $dir) {
                    //Copy the dir to its new location
                    //Only if destination file/dir doesn exists
                    if (!file_exists($dest_dir."/".$dir) || !@$CFG->activity_publisher_keep_files_safe){
                        $status = backup_copy_file($rootdir."/".$dir,
                                      $dest_dir."/".$dir,true);
                        $counter ++;
                    }
                    //Do some output
                    if ($counter % 2 == 0) {
                        if (!defined('RESTORE_SILENTLY')) {
                            echo ".";
                            if ($counter % 40 == 0) {
                                echo "<br />";
                            }
                        }
                        backup_flush(300);
                    }
                }
            }
        }
        //If status is ok and whe have dirs created, returns counter to inform
        if ($status and $counter) {
            return $counter;
        } else {
            return $status;
        }
    }

	/*
    //restore the activity questions files . 
    function restore_questions_files($restore) {

        global $CFG;

        $status = true;

        $counter = 0;
     
        //First, we check to "course_id" exists and create is as necessary
        //in CFG->dataroot
        $course_dir = $CFG->dataroot."/".$restore->course_id;
        $status = check_dir_exists($course_dir,true);
           
        //create the moddata/quiz/instance directory 
        if(array_key_exists('quiz', $restore->mods) && @$restore->mods['quiz']->instances > 0){
           
            foreach($restore->mods['quiz']->instances as $mod){
             	$quiz_instance_id =  $mod->restored_as_course_module;

		         $mod_dir = $course_dir."/".$CFG->moddata."/quiz/".$quiz_instance_id;
		         $status = check_dir_exists($mod_dir, true, true);       
		        
				//Now, we iterate over "course_files" records to check if that file/dir must be
		        //copied to the "dest_dir" dir.
		        $rootdir = $CFG->dataroot."/temp/backup/".$restore->backup_unique_code."/questions_files";
		        //Check if directory exists
		        if (is_dir($rootdir)) {
		            $list = list_directories_and_files ($rootdir);
		            if ($list) {
		                //Iterate
		                foreach ($list as $dir) {
		                    //Copy the dir to its new location
		                    //Only if destination file/dir doesn exists
		                    if (!file_exists($mod_dir."/".$dir)) {
		                        $status = backup_copy_file($rootdir."/".$dir, $mod_dir."/".$dir, true);
		                        $counter ++;
		                    }
		                    //Do some output
		                    if ($counter % 2 == 0) {
		                        if (!defined('RESTORE_SILENTLY')) {
		                            echo ".";
		                            if ($counter % 40 == 0) {
		                                echo "<br />";
		                            }
		                        }
		                        backup_flush(300);
		                    }
		                }
		            }
		        }
            }                 
        }
                
        //If status is ok and whe have dirs created, returns counter to inform
        if ($status and $counter) {
            return $counter;
        } else {
            return $status;
        }
    }*/

    
    //This function restores the site files from the temp (site_files) directory to the
    //dataroot/SITEID directory
    //It will check for activity_publisher settings as behaviour modifiers
    function ap_restore_site_files($restore) {
        global $CFG;

        $status = true;

        $counter = 0;

        //First, we check to "course_id" exists and create is as necessary
        //in CFG->dataroot
        if ($CFG->activity_publisher_all_files_in_course){
	        $dest_dir = $CFG->dataroot."/".$restore->course_id;
	    } else {
	        $dest_dir = $CFG->dataroot."/".SITEID;
	    }
        $status = check_dir_exists($dest_dir,true);

        //Now, we iterate over "site_files" files to check if that file/dir must be
        //copied to the "dest_dir" dir.
        $rootdir = $CFG->dataroot."/temp/backup/".$restore->backup_unique_code."/site_files";
        //Check if directory exists
        if (is_dir($rootdir)) {
            $list = list_directories_and_files ($rootdir);
            if ($list) {
                //Iterate
                $counter = 0;
                foreach ($list as $dir) {
                    //Avoid copying maintenance.html. MDL-18594
                    if ($dir == 'maintenance.html') {
                       continue;
                    }
                    //Copy the dir to its new location
                    //Only if destination file/dir doesn exists
                    if (!file_exists($dest_dir."/".$dir) || !@$CFG->activity_publisher_keep_files_safe) {
                        $status = backup_copy_file($rootdir."/".$dir,
                                      $dest_dir."/".$dir, true);
                        $counter ++;
                    }
                    //Do some output
                    if ($counter % 2 == 0) {
                        if (!defined('RESTORE_SILENTLY')) {
                            echo ".";
                            if ($counter % 40 == 0) {
                                echo "<br />";
                            }
                        }
                        backup_flush(300);
                    }
                }
            }
        }
        //If status is ok and whe have dirs created, returns counter to inform
        if ($status and $counter) {
            return $counter;
        } else {
            return $status;
        }
    }




    /**
     * @param string $errorstr passed by reference, if silent is true,
     * errorstr will be populated and this function will return false rather than calling error() or notify()
     * @param boolean $noredirect (optional) if this is passed, this function will not print continue, or
     * redirect to the next step in the restore process, instead will return $backup_unique_code
     */
    function ap_restore_precheck($id,$file,&$errorstr,$noredirect=false,$backup_unique_code) {

        global $CFG, $SESSION;

        //Prepend dataroot to variable to have the absolute path
        //$file = $CFG->dataroot."/".$file;
        if(!$backup_unique_code){
           $backup_unique_code = time();   
        }
                
        if (!defined('RESTORE_SILENTLY')) {
            //Start the main table
            echo "<table cellpadding=\"5\">";
            echo "<tr><td>";

            //Start the mail ul
            echo "<ul>";
        }

        //Check the file exists
        if (!is_file($file)) {
            if (!defined('RESTORE_SILENTLY')) {
                error ("File not exists ($file)");
            } else {
                $errorstr = "File not exists ($file)";
                return false;
            }
        }

        //Check the file name ends with .zip
        if (!substr($file,-4) == ".zip") {
            if (!defined('RESTORE_SILENTLY')) {
                error ("File has an incorrect extension");
            } else {
                $errorstr = 'File has an incorrect extension';
                return false;
            }
        }

        //Now calculate the unique_code for this restore
      
    
        //Now check and create the backup dir (if it doesn't exist)
        if (!defined('RESTORE_SILENTLY')) {
            echo "<li>".get_string("creatingtemporarystructures").'</li>';
        }
        $status = check_and_create_backup_dir($backup_unique_code);
        //Empty dir
        if ($status) {
            $status = clear_backup_dir($backup_unique_code);
        }

        //Now delete old data and directories under dataroot/temp/backup
        if ($status) {
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("deletingolddata").'</li>';
            }
            $status = backup_delete_old_data();
        }

        //Now copy he zip file to dataroot/temp/backup/backup_unique_code
        if ($status) {
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("copyingzipfile").'</li>';
            }
            if (! $status = backup_copy_file($file,$CFG->dataroot."/temp/backup/".$backup_unique_code."/".basename($file))) {
                if (!defined('RESTORE_SILENTLY')) {
                    notify("Error copying backup file. Invalid name or bad perms.");
                } else {
                    $errorstr = "Error copying backup file. Invalid name or bad perms";
                    return false;
                }
            }
        }

        //Now unzip the file
        if ($status) {
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("unzippingbackup").'</li>';
            }
            if (! $status = restore_unzip ($CFG->dataroot."/temp/backup/".$backup_unique_code."/".basename($file))) {
                if (!defined('RESTORE_SILENTLY')) {
                    notify("Error unzipping backup file. Invalid zip file.");
                } else {
                    $errorstr = "Error unzipping backup file. Invalid zip file.";
                    return false;
                }
            }
        }

        // If experimental option is enabled (enableimsccimport)
        // check for Common Cartridge packages and convert to Moodle format
        if ($status && isset($CFG->enableimsccimport) && $CFG->enableimsccimport == 1) {
            require_once($CFG->dirroot. '/backup/cc/restore_cc.php');
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string('checkingforimscc', 'imscc').'</li>';
            }
            $status = cc_convert($CFG->dataroot. DIRECTORY_SEPARATOR .'temp'. DIRECTORY_SEPARATOR . 'backup'. DIRECTORY_SEPARATOR . $backup_unique_code);
        }

        //Check for Blackboard backups and convert
        if ($status){
            require_once("$CFG->dirroot/backup/bb/restore_bb.php");
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("checkingforbbexport").'</li>';
            }
            $status = blackboard_convert($CFG->dataroot."/temp/backup/".$backup_unique_code);
        }

        //Now check for the moodle.xml file
        if ($status) {
            $xml_file  = $CFG->dataroot."/temp/backup/".$backup_unique_code."/moodle.xml";
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("checkingbackup").'</li>';
            }
            if (! $status = restore_check_moodle_file ($xml_file)) {
                if (!is_file($xml_file)) {
                    $errorstr = 'Error checking backup file. moodle.xml not found at root level of zip file.';
                } else {
                    $errorstr = 'Error checking backup file. moodle.xml is incorrect or corrupted.';
                }
                if (!defined('RESTORE_SILENTLY')) {
                    notify($errorstr);
                } else {
                    return false;
                }
            }
        }

        $info = "";
        $course_header = "";

        //Now read the info tag (all)
        if ($status) {
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("readinginfofrombackup").'</li>';
            }
            //Reading info from file
            $info = restore_read_xml_info ($xml_file);
            
            // force course files restore, whatever the info
            // TODO : check status on activity backup time. May be twicked in XML
            $info->backup_course_files = true;
            
            //Reading course_header from file
            $course_header = restore_read_xml_course_header ($xml_file);

            if(!is_object($course_header)){
                // ensure we fail if there is no course header
                $course_header = false;
            }
        }

        if (!defined('RESTORE_SILENTLY')) {
            //End the main ul
            echo "</ul>\n";

            //End the main table
            echo "</td></tr>";
            echo "</table>";
        }

        //We compare Moodle's versions
        if ($status && $CFG->version < $info->backup_moodle_version) {
            $message = new object();
            $message->serverversion = $CFG->version;
            $message->serverrelease = $CFG->release;
            $message->backupversion = $info->backup_moodle_version;
            $message->backuprelease = $info->backup_moodle_release;
            print_simple_box(get_string('noticenewerbackup','',$message), "center", "70%", '', "20", "noticebox");

        }

        //Now we print in other table, the backup and the course it contains info
        if ($info and $course_header and $status) {
            //First, the course info
            if (!defined('RESTORE_SILENTLY')) {
                $status = restore_print_course_header($course_header);
            }
            //Now, the backup info
            if ($status) {
                if (!defined('RESTORE_SILENTLY')) {
                    $status = restore_print_info($info);
                }
            }
        }

        //Save course header and info into php session
        if ($status) {
            $SESSION->info = $info;
            $SESSION->course_header = $course_header;
        }

        //Finally, a little form to continue
        //with some hidden fields
        if ($status) {
            if (!defined('RESTORE_SILENTLY')) {
                echo "<br /><div style='text-align:center'>";
                $hidden["backup_unique_code"] = $backup_unique_code;
                $hidden["launch"]             = "form";
                $hidden["file"]               =  $file;
                $hidden["id"]                 =  $id;
                print_single_button("backup/restore.php", $hidden, get_string("continue"),"post");
                echo "</div>";
            }
            else {
                if (empty($noredirect)) {
                    print_continue($CFG->wwwroot.'/backup/restore.php?backup_unique_code='.$backup_unique_code.'&launch=form&file='.$file.'&id='.$id.'&sesskey='.sesskey());
                    print_footer();
                    die;

                } else {
                    return $backup_unique_code;
                }
            }
        }

        if (!$status) {
            if (!defined('RESTORE_SILENTLY')) {
                error ("An error has ocurred");
            } else {
                $errorstr = "An error has occured"; // helpful! :P
                return false;
            }
        }
        return true;
    }

    function ap_restore_execute(&$restore,$info,$course_header,&$errorstr) {

        global $CFG, $USER;
        $status = true;

        //Checks for the required files/functions to restore every module
        //and include them
        if ($allmods = get_records("modules") ) {
            foreach ($allmods as $mod) {
                $modname = $mod->name;
                $modfile = "{$CFG->dirroot}/blocks/activity_publisher/backup/$modname/restorelib.php";
            	
                if ((file_exists($modfile)) and !empty($restore->mods[$modname]) and ($restore->mods[$modname]->restore)) {
                    include_once($modfile);
            	} else {

					// traps down in standard restore libs for this module            	
	                $modfile = "$CFG->dirroot/mod/$modname/restorelib.php";
	                //If file exists and we have selected to restore that type of module
	                if ((file_exists($modfile)) and !empty($restore->mods[$modname]) and ($restore->mods[$modname]->restore)) {
	                    include_once($modfile);
	                }
	            }
            }
        }

        if (!defined('RESTORE_SILENTLY')) {
            //Start the main table
            echo "<table cellpadding=\"5\">";
            echo "<tr><td>";

            //Start the main ul
            echo "<ul>";
        }

        //Location of the xml file
        $xml_file = $CFG->dataroot."/temp/backup/".$restore->backup_unique_code."/moodle.xml";

        // Re-assure xml file is in place before any further process
        if (! $status = restore_check_moodle_file($xml_file)) {
            if (!is_file($xml_file)) {
                $errorstr = 'Error checking backup file. moodle.xml not found. Session problem?';
            } else {
                $errorstr = 'Error checking backup file. moodle.xml is incorrect or corrupted. Session problem?';
            }
            if (!defined('RESTORE_SILENTLY')) {
                notify($errorstr);
            }
            return false;
        }

        //Preprocess the moodle.xml file spliting into smaller chucks (modules, users, logs...)
        //for optimal parsing later in the restore process.
        if (!empty($CFG->experimentalsplitrestore)) {
            if (!defined('RESTORE_SILENTLY')) {
                echo '<li>'.get_string('preprocessingbackupfile') . '</li>';
            }
            //First of all, split moodle.xml into handy files
            if (!restore_split_xml ($xml_file, $restore)) {
                $errorstr = "Error proccessing moodle.xml file. Process ended.";
                if (!defined('RESTORE_SILENTLY')) {
                    notify($errorstr);
                }
                return false;
            }
        }

        // Precheck the users section, detecting various situations that can lead to problems, so
        // we stop restore before performing any further action

		// ... No user pre-check as no users at all ...

        //If we've selected to restore into new course
        //create it (course)
        //Saving conversion id variables into backup_tables
        if ($restore->restoreto == RESTORETO_NEW_COURSE) {
            if (!defined('RESTORE_SILENTLY')) {
                echo '<li>'.get_string('creatingnewcourse') . '</li>';
            }
            $oldidnumber = $course_header->course_idnumber;
            if (!$status = restore_create_new_course($restore,$course_header)) {
                if (!defined('RESTORE_SILENTLY')) {
                    notify("Error while creating the new empty course.");
                } else {
                    $errorstr = "Error while creating the new empty course.";
                    return false;
                }
            }

            //Print course fullname and shortname and category
            if ($status) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo "<ul>";
                    echo "<li>".$course_header->course_fullname." (".$course_header->course_shortname.")".'</li>';
                    echo "<li>".get_string("category").": ".$course_header->category->name.'</li>';
                    if (!empty($oldidnumber)) {
                        echo "<li>".get_string("nomoreidnumber","moodle",$oldidnumber)."</li>";
                    }
                    echo "</ul>";
                    //Put the destination course_id
                }
                $restore->course_id = $course_header->course_id;
            }

            if ($status = ap_restore_open_html($restore,$course_header)){
                if (!defined('RESTORE_SILENTLY')) {
                    echo "<li>Creating the Restorelog.html in the course backup folder</li>";
                }
            }

        } else {
            $course = get_record("course","id",$restore->course_id);
            if ($course) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo "<li>".get_string("usingexistingcourse");
                    echo "<ul>";
                    echo "<li>".get_string("from").": ".$course_header->course_fullname." (".$course_header->course_shortname.")".'</li>';
                    echo "<li>".get_string("to").": ". format_string($course->fullname) ." (".format_string($course->shortname).")".'</li>';
                    if (($restore->deleting)) {
                        echo "<li>".get_string("deletingexistingcoursedata").'</li>';
                    } else {
                        echo "<li>".get_string("addingdatatoexisting").'</li>';
                    }
                    echo "</ul></li>";
                }
                //If we have selected to restore deleting, we do it now.
                if ($restore->deleting) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo "<li>".get_string("deletingolddata").'</li>';
                    }
                    $status = remove_course_contents($restore->course_id,false) and
                        delete_dir_contents($CFG->dataroot."/".$restore->course_id,"backupdata");
                    if ($status) {
                        //Now , this situation is equivalent to the "restore to new course" one (we
                        //have a course record and nothing more), so define it as "to new course"
                        $restore->restoreto = RESTORETO_NEW_COURSE;
                    } else {
                        if (!defined('RESTORE_SILENTLY')) {
                            notify("An error occurred while deleting some of the course contents.");
                        } else {
                            $errrostr = "An error occurred while deleting some of the course contents.";
                            return false;
                        }
                    }
                }
            } else {
                if (!defined('RESTORE_SILENTLY')) {
                    notify("Error opening existing course.");
                    $status = false;
                } else {
                    $errorstr = "Error opening existing course.";
                    return false;
                }
            }
        }

        //Now create users as needed

		// ... no users included ...

        //Now create groups as needed

		// ... no course/groups/groupings info here ....

        //Now create groupings as needed

		// ... no course/groups/groupings info here ....

        //Now create groupingsgroups as needed

		// ... no course/groups/groupings info here ....

        //Now create the course_sections and their associated course_modules
        //we have to do this after groups and groupings are restored, because we need the new groupings id
        if ($status) {
            //Into new course
            if ($restore->restoreto == RESTORETO_NEW_COURSE) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo "<li>".get_string("creatingsections");
                }
                if (!$status = restore_create_sections($restore,$xml_file)) {
                    if (!defined('RESTORE_SILENTLY')) {
                        notify("Error creating sections in the existing course.");
                    } else {
                        $errorstr = "Error creating sections in the existing course.";
                        return false;
                    }
                }
                if (!defined('RESTORE_SILENTLY')) {
                    echo '</li>';
                }
                //Into existing course
            } else if ($restore->restoreto != RESTORETO_NEW_COURSE) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo "<li>".get_string("checkingsections");
                }
                if (!$status = restore_create_sections($restore,$xml_file)) {
                    if (!defined('RESTORE_SILENTLY')) {
                        notify("Error creating sections in the existing course.");
                    } else {
                        $errorstr = "Error creating sections in the existing course.";
                        return false;
                    }
                }
                if (!defined('RESTORE_SILENTLY')) {
                    echo '</li>';
                }
                //Error
            } else {
                if (!defined('RESTORE_SILENTLY')) {
                    notify("Neither a new course or an existing one was specified.");
                    $status = false;
                } else {
                    $errorstr = "Neither a new course or an existing one was specified.";
                    return false;
                }
            }
        }

        //Now create metacourse info

		// ... no course level information ...
     
        //Now create categories and questions as needed
        if ($status) {
            include_once("$CFG->dirroot/blocks/activity_publisher/backup/question/restorelib.php");
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("creatingcategoriesandquestions");
                echo "<ul>";
            }
            if (!$status = ap_restore_create_questions($restore,$xml_file)) {
                if (!defined('RESTORE_SILENTLY')) {
                    notify("Could not restore categories and questions!");
                } else {
                    $errorstr = "Could not restore categories and questions!";
                    return false;
                }
            }
            if (!defined('RESTORE_SILENTLY')) {
                echo "</ul></li>";
            }
        }

        //Now create user_files as needed

		// ... We never have use rstuff here ....

        //Now create course files as needed
		if ($status and ($restore->course_files)) {
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("copyingcoursefiles");
            }
            if (!$status = ap_restore_course_files($restore)) {
                if (empty($status)) {
                    notify("Could not restore course files!");
                } else {
                    $errorstr = "Could not restore course files!";
                    return false;
                }
            }
            //If all is ok (and we have a counter)
            if ($status and ($status !== true)) {
                //Inform about user dirs created from backup
                if (!defined('RESTORE_SILENTLY')) {
                    echo "<ul>";
                    echo "<li>".get_string("filesfolders").": ".$status.'</li>';
                    echo "</ul>";
                }
            }
            if (!defined('RESTORE_SILENTLY')) {
                echo "</li>";
            }
        }
       
//        if ($status and ($restore->course_files)) {
		/*
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string('copyingquestionsfiles', 'block_activity_publisher');
            }
            if (!$status = restore_questions_files($restore)) {
                if (empty($status)) {
                    notify("Could not restore questions files!");
                } else {
                    $errorstr = "Could not restore questions files!";
                    return false;
                }
            }
     
            //If all is ok (and we have a counter)
            if ($status and ($status !== true)) {
                //Inform about user dirs created from backup
                if (!defined('RESTORE_SILENTLY')) {
                    echo "<ul>";
                    echo "<li>".get_string("filesfolders").": ".$status.'</li>';
                    echo "</ul>";
                }
            }
            if (!defined('RESTORE_SILENTLY')) {
                echo "</li>";
            }
   //     }
   		*/

        //Now create site files as needed
        // if ($status and ($restore->site_files)) {
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string('copyingsitefiles');
            }
            if (!$status = restore_site_files($restore)) {
                if (empty($status)) {
                    notify("Could not restore site files!");
                } else {
                    $errorstr = "Could not restore site files!";
                    return false;
                }
            }
            //If all is ok (and we have a counter)
            if ($status and ($status !== true)) {
                //Inform about user dirs created from backup
                if (!defined('RESTORE_SILENTLY')) {
                    echo "<ul>";
                    echo "<li>".get_string("filesfolders").": ".$status.'</li>';
                    echo "</ul>";
                }
            }
            if (!defined('RESTORE_SILENTLY')) {
                echo "</li>";
            }
        // }

        //Now create messages as needed
        /* ($status and ($restore->messages)) {
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("creatingmessagesinfo");
            }
            if (!$status = restore_create_messages($restore,$xml_file)) {
                if (!defined('RESTORE_SILENTLY')) {
                    notify("Could not restore messages!");
                } else {
                    $errorstr = "Could not restore messages!";
                    return false;
                }
            }
            if (!defined('RESTORE_SILENTLY')) {
                echo "</li>";
            }
        }*/

        //Now create blogs as needed

		// ... No blogs restore ...

        //Now create scales as needed
        if ($status) {
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("creatingscales");
            }
            if (!$status = restore_create_scales($restore,$xml_file)) {
                if (!defined('RESTORE_SILENTLY')) {
                    notify("Could not restore custom scales!");
                } else {
                    $errorstr = "Could not restore custom scales!";
                    return false;
                }
            }
            if (!defined('RESTORE_SILENTLY')) {
                echo '</li>';
            }
        }

        //Now create events as needed

		// ... No even restore ...

        //Now create course modules as needed
        if ($status) {
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("creatingcoursemodules");
            }
            if (!$status = restore_create_modules($restore,$xml_file)) {
                if (!defined('RESTORE_SILENTLY')) {
                    notify("Could not restore modules!");
                } else {
                    $errorstr = "Could not restore modules!";
                    return false;
                }
            }
            if (!defined('RESTORE_SILENTLY')) {
                echo '</li>';
            }
        }

        // Bring back the course blocks -- do it AFTER the modules!!!

		// ... No blocks restore ....

		// Restore course format specific information.

		// ... No course format restored ...        

        //Now create log entries as needed

		// ... No log records restored ... 

        //Now, if all is OK, adjust the instance field in course_modules !!
        //this also calculates the final modinfo information so, after this,
        //code needing it can be used (like role_assignments. MDL-13740)
        if ($status) {
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("checkinginstances");
            }
            if (!$status = restore_check_instances($restore)) {
                if (!defined('RESTORE_SILENTLY')) {
                    notify("Could not adjust instances in course_modules!");
                } else {
                    $errorstr = "Could not adjust instances in course_modules!";
                    return false;
                }
            }
            if (!defined('RESTORE_SILENTLY')) {
                echo '</li>';
            }
        }

        //Now, if all is OK, adjust activity events

		// ... No event processing ...

        //Now, if all is OK, adjust inter-activity links
         if ($status) {
         
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("decodinginternallinks");
            }
            if (!$status = ap_restore_decode_content_links($restore)) {
                if (!defined('RESTORE_SILENTLY')) {
                    notify("Could not decode content links!");
                } else {
                    $errorstr = "Could not decode content links!";
                    return false;
                }
            }
            if (!defined('RESTORE_SILENTLY')) {
                echo '</li>';
            }
        }
            
        //Now, with backup files prior to version 2005041100,
        //convert all the wiki texts in the course to markdown

		// ... By pass processing of old backups ...
        
        //Now create gradebook as needed -- AFTER modules and blocks!!!

		// ... No grade processing ....

        /*******************************************************************************
         ************* Restore of Roles and Capabilities happens here ******************
         *******************************************************************************/
         // try to restore roles even when restore is going to fail - teachers might have
         // at least some role assigned - this is not correct though
         $status = restore_create_roles($restore, $xml_file) && $status;
         $status = restore_roles_settings($restore, $xml_file) && $status;

        //Now if all is OK, update:
        //   - course modinfo field
        //   - categories table
        //   - add user as teacher
       
       /*
        if ($status) {
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("checkingcourse");
            }
            //categories table
            $course = get_record("course","id",$restore->course_id);
            fix_course_sortorder();
            // Check if the user has course update capability in the newly restored course
            // there is no need to load his capabilities again, because restore_roles_settings
            // would have loaded it anyway, if there is any assignments.
            // fix for MDL-6831
            $newcontext = context_course::instance($restore->course_id);
            if (!has_capability('moodle/course:manageactivities', $newcontext)) {
                // fix for MDL-9065, use the new config setting if exists
                if ($CFG->creatornewroleid) {
                    role_assign($CFG->creatornewroleid, $USER->id, 0, $newcontext->id);
                } else {
                    if ($legacyteachers = get_roles_with_capability('moodle/legacy:editingteacher', CAP_ALLOW, context_system::instance())) {
                        if ($legacyteacher = array_shift($legacyteachers)) {
                            role_assign($legacyteacher->id, $USER->id, 0, $newcontext->id);
                        }
                    } else {
                        notify('Could not find a legacy teacher role. You might need your moodle admin to assign a role with editing privilages to this course.');
                    }
                }
            }
            if (!defined('RESTORE_SILENTLY')) {
                echo '</li>';
            }
        }
         */
        //Cleanup temps (files and db)
        if ($status) {
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("cleaningtempdata");
            }
            if (!$status = clean_temp_data ($restore)) {
                if (!defined('RESTORE_SILENTLY')) {
                    notify("Could not clean up temporary data from files and database");
                } else {
                    $errorstr = "Could not clean up temporary data from files and database";
                    return false;
                }
            }
            if (!defined('RESTORE_SILENTLY')) {
                echo '</li>';
            }
        }

        // this is not a critical check - the result can be ignored
        if (restore_close_html($restore)){
            if (!defined('RESTORE_SILENTLY')) {
                echo '<li>Closing the Restorelog.html file.</li>';
            }
        }
        else {
            if (!defined('RESTORE_SILENTLY')) {
                notify("Could not close the restorelog.html file");
            }
        }

        if (!defined('RESTORE_SILENTLY')) {
            //End the main ul
            echo "</ul>";

            //End the main table
            echo "</td></tr>";
            echo "</table>";
        }

        return $status;
    }
    //Create, open and write header of the html log file
    function ap_restore_open_html($restore,$course_header) {

        global $CFG;

        $status = true;
				/**************************************AHD**
				*change make_upload_directory from course_id folder to activity_publisher folder
				*/
        //Open file for writing
        //First, we check the course_id backup data folder exists and create it as necessary in CFG->dataroot
        if (!$dest_dir = make_upload_directory("activity_publisher/backupdata")) {   // Backup folder
            error("Could not create backupdata folder.  The site administrator needs to fix the file permissions");
        }
        $status = check_dir_exists($dest_dir,true);
        $restorelog_file = fopen("$dest_dir/restorelog.html","a");
        //Add the stylesheet
        $stylesheetshtml = '';
        foreach ($CFG->stylesheets as $stylesheet) {
            $stylesheetshtml .= '<link rel="stylesheet" type="text/css" href="'.$stylesheet.'" />'."\n";
        }
        ///Accessibility: added the 'lang' attribute to $direction, used in theme <html> tag.
        $languagehtml = get_html_lang($dir=true);

        //Write the header in the new logging file
        fwrite ($restorelog_file,"<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"");
        fwrite ($restorelog_file," \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">  ");
        fwrite ($restorelog_file,"<html dir=\"ltr\".$languagehtml.");
        fwrite ($restorelog_file,"<head>");
        fwrite ($restorelog_file,$stylesheetshtml);
        fwrite ($restorelog_file,"<title>".$course_header->course_shortname." Restored </title>");
        fwrite ($restorelog_file,"</head><body><br/><h1>The following changes were made during the Restoration of this Course.</h1><br/><br/>");
        fwrite ($restorelog_file,"The Course ShortName is now - ".$course_header->course_shortname." The FullName is now - ".$course_header->course_fullname."<br/><br/>");
        $startdate = addslashes($course_header->course_startdate);
        $date = usergetdate($startdate);
        fwrite ($restorelog_file,"The Originating Courses Start Date was " .$date['weekday'].", ".$date['mday']." ".$date['month']." ".$date['year']."");
        $startdate += $restore->course_startdateoffset;
        $date = usergetdate($startdate);
        fwrite ($restorelog_file,"&nbsp;&nbsp;&nbsp;This Courses Start Date is now  " .$date['weekday'].",  ".$date['mday']." ".$date['month']." ".$date['year']."<br/><br/>");

        if ($status) {
            return $restorelog_file;
        } else {
            return false;
        }
    }


?>
