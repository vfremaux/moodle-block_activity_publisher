<?php //$Id: backuplib.php,v 1.4 2012-07-22 20:28:50 vf Exp $
    //This file contains all the function needed in the backup utility
    //except the mod-related funtions that are into every backuplib.php inside
    //every mod directory

    //Prints General info about the course
    //name, moodle_version (internal and release), backup_version, date, info in file...
    function ap_backup_general_info ($bf,$preferences) {

        global $CFG;

        fwrite ($bf,start_tag("INFO",1,true));

        //The name of the backup
        fwrite ($bf,full_tag("NAME",2,false,$preferences->backup_name));
        //The moodle_version
        fwrite ($bf,full_tag("MOODLE_VERSION",2,false,$preferences->moodle_version));
        fwrite ($bf,full_tag("MOODLE_RELEASE",2,false,$preferences->moodle_release));
        //The backup_version
        fwrite ($bf,full_tag("BACKUP_VERSION",2,false,$preferences->backup_version));
        fwrite ($bf,full_tag("BACKUP_RELEASE",2,false,$preferences->backup_release));
        //The date
        fwrite ($bf,full_tag("DATE",2,false,$preferences->backup_unique_code));
        //The original site wwwroot
        fwrite ($bf,full_tag("ORIGINAL_WWWROOT",2,false,$CFG->wwwroot));
        //The original site identifier. MD5 hashed for security.
        fwrite ($bf,full_tag("ORIGINAL_SITE_IDENTIFIER_HASH",2,false,md5(get_site_identifier())));
        //The zip method used
        if (!empty($CFG->zip)) {
            $zipmethod = 'external';
        } else {
            $zipmethod = 'internal';
        }
        //Indicate it does not includes external MNET users
        fwrite ($bf,full_tag("MNET_REMOTEUSERS",2,false,'false'));
        fwrite ($bf,full_tag("ZIP_METHOD",2,false,$zipmethod));
        // Te includes tag
        fwrite ($bf,start_tag("DETAILS",2,true));
        //Now, go to mod element of preferences to print its status
        foreach ($preferences->mods as $element) {
            //Calculate info
            $included = "false";
            $userinfo = "false";
            if ($element->backup) {
                $included = "true";
                if ($element->userinfo) {
                    $userinfo = "true";
                }
            }
            //Prints the mod start
            fwrite ($bf,start_tag("MOD",3,true));
            fwrite ($bf,full_tag("NAME",4,false,$element->name));
            fwrite ($bf,full_tag("INCLUDED",4,false,$included));
            fwrite ($bf,full_tag("USERINFO",4,false,$userinfo));

            if (isset($preferences->mods[$element->name]->instances)
                && is_array($preferences->mods[$element->name]->instances)
                && count($preferences->mods[$element->name]->instances)) {
                fwrite ($bf, start_tag("INSTANCES",4,true));
                foreach ($preferences->mods[$element->name]->instances as $id => $object) {
                    if (!empty($object->backup)) {
                        //Calculate info
                        $included = "false";
                        $userinfo = "false";
                        if ($object->backup) {
                            $included = "true";
                            if ($object->userinfo) {
                                $userinfo = "true";
                            }
                        }
                        fwrite ($bf, start_tag("INSTANCE",5,true));
                        fwrite ($bf, full_tag("ID",5,false,$id));
                        fwrite ($bf, full_tag("NAME",5,false,$object->name));
                        fwrite ($bf, full_tag("INCLUDED",5,false,$included)) ;
                        fwrite ($bf, full_tag("USERINFO",5,false,$userinfo));
                        fwrite ($bf, end_tag("INSTANCE",5,true));
                    }
                }
                fwrite ($bf, end_tag("INSTANCES",4,true));
            }

            //Print the end
            fwrite ($bf,end_tag("MOD",3,true));
        }
        //The metacourse in backup
        if ($preferences->backup_metacourse == 1) {
            fwrite ($bf,full_tag("METACOURSE",3,false,"true"));
        } else {
            fwrite ($bf,full_tag("METACOURSE",3,false,"false"));
        }
        //The user in backup
        if ($preferences->backup_users == 1) {
            fwrite ($bf,full_tag("USERS",3,false,"course"));
        } else if ($preferences->backup_users == 0) {
            fwrite ($bf,full_tag("USERS",3,false,"all"));
        } else {
            fwrite ($bf,full_tag("USERS",3,false,"none"));
        }
        //The logs in backup
        if ($preferences->backup_logs == 1) {
            fwrite ($bf,full_tag("LOGS",3,false,"true"));
        } else {
            fwrite ($bf,full_tag("LOGS",3,false,"false"));
        }
        //The user files
        if ($preferences->backup_user_files == 1) {
            fwrite ($bf,full_tag("USERFILES",3,false,"true"));
        } else {
            fwrite ($bf,full_tag("USERFILES",3,false,"false"));
        }
        //The course files
        if ($preferences->backup_course_files == 1) {
            fwrite ($bf,full_tag("COURSEFILES",3,false,"true"));
        } else {
            fwrite ($bf,full_tag("COURSEFILES",3,false,"false"));
        }
        //The site files
        if ($preferences->backup_site_files == 1) {
            fwrite ($bf,full_tag("SITEFILES",3,false,"true"));
        } else {
            fwrite ($bf,full_tag("SITEFILES",3,false,"false"));
        }
        //The gradebook histories
        fwrite ($bf,full_tag("GRADEBOOKHISTORIES",3,false,"false"));
        //The messages in backup
        fwrite ($bf,full_tag("MESSAGES",3,false,"false"));
        //The blogs in backup
        fwrite ($bf,full_tag("BLOGS",3,false,"false"));
        //The mode of writing the block data
        fwrite ($bf,full_tag('BLOCKFORMAT',3,false,'instances'));
        fwrite ($bf,end_tag("DETAILS",2,true));

        $status = fwrite ($bf,end_tag("INFO",1,true));

        ///Roles stuff goes in here

        fwrite ($bf, start_tag('ROLES', 1, true));
        $roles = backup_fetch_roles($preferences);

        $sitecontext = context_system::instance();
        $coursecontext = context_course::instance($preferences->backup_course);

        foreach ($roles as $role) {
            fwrite ($bf,start_tag('ROLE',2,true));
            fwrite ($bf,full_tag('ID', 3, false, $role->id));
            fwrite ($bf,full_tag('NAME',3,false,$role->name));
            fwrite ($bf,full_tag('SHORTNAME',3,false,$role->shortname));
        /// Calculate $role name in course
            $nameincourse = role_get_name($role, $coursecontext);
            if ($nameincourse != $role->name) {
                fwrite ($bf,full_tag('NAMEINCOURSE', 3, false, $nameincourse));
            }
            // find and write all default capabilities
            fwrite ($bf,start_tag('CAPABILITIES',3,true));
            // pull out all default (site context) capabilities
            if ($capabilities = role_context_capabilities($role->id, $sitecontext)) {
                foreach ($capabilities as $capability=>$value) {
                    fwrite ($bf,start_tag('CAPABILITY',4,true));
                    fwrite ($bf,full_tag('NAME', 5, false, $capability));
                    fwrite ($bf,full_tag('PERMISSION', 5, false, $value));
                    // use this to pull out the other info (timemodified and modifierid)

                    $cap = get_record_sql("SELECT *
                                           FROM {$CFG->prefix}role_capabilities
                                           WHERE capability = '$capability'
                                                 AND contextid = $sitecontext->id
                                                 AND roleid = $role->id");
                    fwrite ($bf, full_tag("TIMEMODIFIED", 5, false, $cap->timemodified));
                    fwrite ($bf, full_tag("MODIFIERID", 5, false, $cap->modifierid));
                    fwrite ($bf,end_tag('CAPABILITY',4,true));
                }
            }
            fwrite ($bf,end_tag('CAPABILITIES',3,true));
            fwrite ($bf,end_tag('ROLE',2,true));
        }
        fwrite ($bf,end_tag('ROLES', 1, true));
        return $status;
    }

    //Prints course's sections info (table course_sections)
    function ap_backup_course_sections ($bf,$preferences) {

        global $CFG;

        $status = true;


        //Get info from sections
        $section=false;
        if ($sections = get_records("course_sections","course",$preferences->backup_course,"section")) {
            //Section open tag
            fwrite ($bf,start_tag("SECTIONS",2,true));
            //Iterate over every section (ordered by section)
            foreach ($sections as $section) {
                //Begin Section
                fwrite ($bf,start_tag("SECTION",3,true));
                fwrite ($bf,full_tag("ID",4,false,$section->id));
                fwrite ($bf,full_tag("NUMBER",4,false,$section->section));
                fwrite ($bf,full_tag("SUMMARY",4,false,"")); //there is always no summary
                fwrite ($bf,full_tag("VISIBLE",4,false,$section->visible));
                //Now print the mods in section
                backup_course_modules ($bf,$preferences,$section);
                //End section
                fwrite ($bf,end_tag("SECTION",3,true));
            }
            //Section close tag
            $status = fwrite ($bf,end_tag("SECTIONS",2,true));
        }

        return $status;

    }

    //This function makes all the necesary calls to every mod
    //to export itself and its files !!!
    function ap_backup_module($bf,$preferences,$module) {

        global $CFG;

        $status = true;
        $statusm = true;
          
     //  this function look for backup procedude in 3 main entry places
    // - External Entry Point: look for $module_backup_one_mod in external mod [modname/activity_publisher/backuplib.php]
    // - Enternal Enty Point : look for $module_backup_one_mod in internally in activty_publisher in [activity_publisher/backup/modname/backuplib.php]
    // - Legacy Entry Point  : look for $module_backup_one_mod in the mod backup file [modname/backuplib.php]

        if(file_exists($CFG->dirroot.'/mod/'.$module.'/activity_publisher/backuplib.php')){
            require_once($CFG->dirroot.'/mod/'.$module.'/activity_publisher/backuplib.php');
        } else if(file_exists($CFG->dirroot.'/blocks/activity_publisher/backup/'.$module.'/backuplib.php')) {
            require_once($CFG->dirroot.'/blocks/activity_publisher/backup/'.$module.'/backuplib.php');
        } else if(file_exists($CFG->dirroot.'/mod/'.$module.'/backuplib.php')) {
            require_once($CFG->dirroot.'/mod/'.$module.'/backuplib.php');
        } else {
            //no one of the above hooks worked , 
            // invalid backup implementation.
            return;
        }
       
        if (isset($preferences->mods[$module]->instances) && is_array($preferences->mods[$module]->instances)) {
            $onemodbackup = $module.'_backup_one_mod';
            if (function_exists($onemodbackup)) {
                foreach ($preferences->mods[$module]->instances as $instance => $object) {
                    if (!empty($object->backup)) {
                        $statusm = $onemodbackup($bf,$preferences,$instance);
                        if (!$statusm) {
                            if (!defined('BACKUP_SILENTLY')) {
                                notify('backup of '.$module.'-'.$object->name.' failed.');
                            }
                            $status = false;
                        }
                    }
                }
            } else {
                $status = false;
            }
        } else { // whole module.
            //First, re-check if necessary functions exists
            $modbackup = $module."_backup_mods";
            if (function_exists($modbackup)) {
                //Call the function
                $status = $modbackup($bf,$preferences);
            } else {
                //Something was wrong. Function should exist.
                $status = false;
            }
        }

        return $status;

    }

    //This function encode things to make backup multi-site fully functional
    //It does this conversions:
    // - $CFG->wwwroot/file.php/courseid ------------------> $@FILEPHP@$ (slasharguments links)
    // - $CFG->wwwroot/file.php?file=/courseid ------------> $@FILEPHP@$ (non-slasharguments links)
    // - Every module/block/course_format xxxx_encode_content_links() is executed too
    //
    function ap_backup_encode_absolute_links($content) {

        global $CFG,$preferences;

    /// MDL-14072: Prevent NULLs, empties and numbers to be processed by the
    /// heavy interlinking. Just a few cpu cycles saved.
        if ($content === NULL) {
            return NULL;
        } else if ($content === '') {
            return '';
        } else if (is_numeric($content)) {
            return $content;
        }

        //Use one static variable to cache all the require_once calls that,
        //under PHP5 seems to increase load too much, and we are requiring
        //them here thousands of times (one per content). MDL-8700.
        //Once fixed by PHP, we'll delete this hack

        static $includedfiles;
        if (!isset($includedfiles)) {
            $includedfiles = array();
        }

        //Check if we support unicode modifiers in regular expressions. Cache it.
        static $unicoderegexp;
        if (!isset($unicoderegexp)) {
            $unicoderegexp = @preg_match('/\pL/u', 'a'); // This will fail silenty, returning false,
        }                                                // if regexp libraries don't support unicode

        //Check if preferences is ok. If it isn't set, we are
        //in a scheduled_backup to we are able to get a copy
        //from CFG->backup_preferences
        if (!isset($preferences)) {
            $mypreferences = $CFG->backup_preferences;
        } else {
            //We are in manual backups so global preferences must exist!!
            $mypreferences = $preferences;
        }
        //First, we check for every call to file.php inside the course
        $search = array($CFG->wwwroot.'/file.php/'.$mypreferences->backup_course,
                        $CFG->wwwroot.'/file.php?file=/'.$mypreferences->backup_course,
                        $CFG->wwwroot.'/file.php?file=%2f'.$mypreferences->backup_course,
                        $CFG->wwwroot.'/file.php?file=%2F'.$mypreferences->backup_course,
                        $CFG->wwwroot.'/file.php/'.SITEID,
                        $CFG->wwwroot.'/file.php?file=/'.SITEID,
                        $CFG->wwwroot.'/file.php?file=%2f'.SITEID,
                        $CFG->wwwroot.'/file.php?file=%2F'.SITEID);

        $replace = array('$@FILEPHP@$', '$@FILEPHP@$', '$@FILEPHP@$', '$@FILEPHP@$','$@FILEPHP@$','$@FILEPHP@$','$@FILEPHP@$','$@FILEPHP@$');

        $result = str_replace($search, $replace, $content);

        // Now we look for any '$@FILEPHP@$' URLs, replacing:
        //     - slashes and %2F by $@SLASH@$
        //     - &forcedownload=1 &amp;forcedownload=1 and ?forcedownload=1 by $@FORCEDOWNLOAD@$
        // This way, backup contents will be neutral and independent of slasharguments configuration. MDL-18799
        // Based in $unicoderegexp, decide the regular expression to use
        if ($unicoderegexp) { //We can use unicode modifiers
            $search = '/(\$@FILEPHP@\$)((?:(?:\/|%2f|%2F))(?:(?:\([-;:@#&=\pL0-9\$~_.+!*\',]*?\))|[-;:@#&=\pL0-9\$~_.+!*\',]|%[a-fA-F0-9]{2}|\/)*)?(\?(?:(?:(?:\([-;:@#&=\pL0-9\$~_.+!*\',]*?\))|[-;:@#&=?\pL0-9\$~_.+!*\',]|%[a-fA-F0-9]{2}|\/)*))?(?<![,.;])/u';
        } else { //We cannot ue unicode modifiers
            $search = '/(\$@FILEPHP@\$)((?:(?:\/|%2f|%2F))(?:(?:\([-;:@#&=a-zA-Z0-9\$~_.+!*\',]*?\))|[-;:@#&=a-zA-Z0-9\$~_.+!*\',]|%[a-fA-F0-9]{2}|\/)*)?(\?(?:(?:(?:\([-;:@#&=a-zA-Z0-9\$~_.+!*\',]*?\))|[-;:@#&=?a-zA-Z0-9\$~_.+!*\',]|%[a-fA-F0-9]{2}|\/)*))?(?<![,.;])/';
        }
        $result = preg_replace_callback($search, 'backup_process_filephp_uses', $result);

        foreach ($mypreferences->mods as $name => $info) {
        /// We only include the corresponding backuplib.php if it hasn't been included before
        /// This will save some load under PHP5. MDL-8700.
        /// Once fixed by PHP, we'll delete this hack
            if (!in_array($name, $includedfiles)) {
                include_once("$CFG->dirroot/mod/$name/backuplib.php");
                $includedfiles[] = $name;
            }
            //Check if the xxxx_encode_content_links exists
            $function_name = $name."_encode_content_links";
            if (function_exists($function_name)) {
                $result = $function_name($result,$mypreferences);
            }
        }

        // For the current course format call its encode_content_links method (if it exists)
        static $format_function_name;
        if (!isset($format_function_name)) {
            $format_function_name = false;
            if ($format = get_field('course', 'format', 'id', $mypreferences->backup_course)) {
                if (file_exists("$CFG->dirroot/course/format/$format/backuplib.php")) {
                    include_once("$CFG->dirroot/course/format/$format/backuplib.php");
                    $function_name = $format.'_encode_format_content_links';
                    if (function_exists($function_name)) {
                        $format_function_name = $function_name;
                    }
                }
            }
        }
        // If the above worked - then we have a function to call
        if ($format_function_name) {
            $result = $format_function_name($result, $mypreferences);
        }

        // For each block, call its encode_content_links method.
        // This encodes for example links to blocks/something/viewphp?id=666
        // that are stored in other activities.
        static $blockobjects = null;

        if (!isset($blockobjects)) {
            $blockobjects = array();
            if ($blocks = get_records('block', 'visible', 1)) {
                foreach ($blocks as $block) {
/*                    
                    if($block->name == 'publishflow')
                    {
                        continue;
                    }
*/                    
                    if ($blockobject = block_instance($block->name)) {
                        $blockobjects[] = $blockobject;
                    }
                }
            }
        }

        foreach ($blockobjects as $blockobject) {
            $result = $blockobject->encode_content_links($result,$mypreferences);
        }

        // Finally encode some well-know links to course
        $result = backup_course_encode_links($result, $mypreferences);

        if ($result != $content && !BACKUP_SILENTLY) {
            debugging('<br /><hr />'.s($content).'<br />changed to<br />'.s($result).'<hr /><br />');
        }

        return $result;
    }



    /*
     * This function copies all the files under the course directory that have been regiostered in the backup_files table (except the moddata and backupdata
     * directories to the "course_files" directory under temp/backup
     */
    function ap_backup_copy_modules_files_from_course($preferences) {

        global $CFG;

        $status = true;

        if ($preferences->backup_course == SITEID){
            return $status;
        }

        //First we check to "course_files" exists and create it as necessary
        //in temp/backup/$backup_code  dir
        $status = $status && check_and_create_course_files_dir($preferences->backup_unique_code);

        $rootdir = $CFG->dataroot."/".$preferences->backup_course;

        $files = get_records_select('backup_files', "backup_code = '$preferences->backup_unique_code' AND file_type = 'course'");
        if ($files) {
            //Iterate
            foreach ($files as $fileobj) {
                //check for dir structure and create recursively
                $file = $fileobj->path;
                $status = $status && check_dir_exists(dirname($CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/course_files/".$file), true, true);
                $status = $status && backup_copy_file($rootdir."/".$file, $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/course_files/".$file);
                // keep failed in table for debug.
        		if ($status) $files = delete_records('backup_files', 'id', $fileobj->id);
            }
        }
        return $status;
    }

    /*
     * This function copies all the files under the course directory that have been regiostered in the backup_files table (except the moddata and backupdata
     * directories to the "course_files" directory under temp/backup
     */
    function ap_backup_copy_modules_files_from_site($preferences) {

        global $CFG;

        $status = true;

        if ($preferences->backup_course == SITEID){
            return $status;
        }

        //First we check to "site_files" exists and create it as necessary
        //in temp/backup/$backup_code  dir
        $status = $status && check_and_create_site_files_dir($preferences->backup_unique_code);

        $rootdir = $CFG->dataroot.'/'.SITEID;

        $files = get_records_select('backup_files', "backup_code = '$preferences->backup_unique_code' AND file_type = 'site'");
        if ($files) {
            //Iterate
            foreach ($files as $fileobj) {
                //check for dir structure and create recursively
                $file = $fileobj->path;
                $status = $status && check_dir_exists(dirname($CFG->dataroot."/temp/backup/".$preferences->backup_unique_code.'/site_files/'.$file), true, true);
                $status = $status && backup_copy_file($rootdir."/".$file, $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code.'/site_files/'.$file);
                // keep failed in table for debug.
        		if ($status) $files = delete_records('backup_files', 'id', $fileobj->id);
            }
        }
        return $status;
    }


    /*
     * Checks for the required files/functions to backup every mod
     * And check if there is data about it
     */
    function ap_backup_fetch_prefs_from_request(&$preferences,&$count,$course,$mod_id) {
        global $CFG, $SESSION;

        // check to see if it's in the session already
        if (!empty($SESSION->backupprefs)  && array_key_exists($course->id,$SESSION->backupprefs) && !empty($SESSION->backupprefs[$course->id])) {
            $sprefs = $SESSION->backupprefs[$course->id];
            $preferences = $sprefs;
            // refetch backup_name just in case.
            $bn = optional_param('backup_name','',PARAM_FILE);
            if (!empty($bn)) {
                $preferences->backup_name = $bn;
            }
            $count = 1;
            return true;
        }

        if ($allmods = get_records('modules', 'id', $mod_id) ) {
            foreach ($allmods as $mod) {
                $modname = $mod->name;
                $modfile = "$CFG->dirroot/mod/$modname/backuplib.php";
                $modbackup = $modname."_backup_mods";
                $modbackupone = $modname."_backup_one_mod";
                $modcheckbackup = $modname."_check_backup_mods";
                if (!file_exists($modfile)) {
                    continue;
                }
                include_once($modfile);
                if (!function_exists($modbackup) || !function_exists($modcheckbackup)) {
                    continue;
                }
                $var = "exists_".$modname;
                $preferences->$var = true;
                $count++;
                // check that there are instances and we can back them up individually
                if (!count_records('course_modules','course',$course->id,'module',$mod->id) || !function_exists($modbackupone)) {
                    continue;
                }
                $var = 'exists_one_'.$modname;
                $preferences->$var = true;
                $varname = $modname.'_instances';
                $preferences->$varname = get_all_instances_in_course($modname, $course, NULL, true);
                foreach ($preferences->$varname as $instance) {
                    $preferences->mods[$modname]->instances[$instance->id]->name = $instance->name;
                    $var = 'backup_'.$modname.'_instance_'.$instance->id;
                    
                    //check activity instances *****AHD
                    if(in_array($instance->coursemodule,$preferences->activity_instances)){
                       	$$var = optional_param($var,1); 
                    } else {
                     	$$var = optional_param($var,0);    
                    } 
                    //***AHD
                    
                    $preferences->$var = $$var;
                    $preferences->mods[$modname]->instances[$instance->id]->backup = $$var;
                    $var = 'backup_user_info_'.$modname.'_instance_'.$instance->id;
                    $$var = optional_param($var,0);
                    $preferences->$var = $$var;
                    $preferences->mods[$modname]->instances[$instance->id]->userinfo = $$var;
                    $var = 'backup_'.$modname.'_instances';
                    $preferences->$var = 1; // we need this later to determine what to display in modcheckbackup.
                }

                //Check data
                //Check module info
                $preferences->mods[$modname]->name = $modname;

                $var = "backup_".$modname;
                $$var = optional_param( $var,1);
                $preferences->$var = $$var;
                $preferences->mods[$modname]->backup = $$var;

                //Check include user info
                $var = "backup_user_info_".$modname;
                $$var = optional_param( $var,0);
                $preferences->$var = $$var;
                $preferences->mods[$modname]->userinfo = $$var;

            }
        }

        //Check other parameters ... done already


        $roles = get_records('role', '', '', 'sortorder');
        $preferences->backuproleassignments = array();
        foreach ($roles as $role) {
            if (optional_param('backupassignments_' . $role->shortname, 0, PARAM_INT)) {
                $preferences->backuproleassignments[$role->id] = $role;
            }
        }

        // put it (back) in the session
        $SESSION->backupprefs[$course->id] = $preferences;
    }

    function ap_backup_execute(&$preferences, &$errorstr) {
        global $CFG;
        $status = true;
        //Check for temp and backup and backup_unique_code directory
        //Create them as needed
        if (!defined('BACKUP_SILENTLY')) {
            echo "<li>".get_string('creatingtemporarystructures').'</li>';
        }

        $status = check_and_create_backup_dir($preferences->backup_unique_code);
        //Empty dir
        if ($status) {
            $status = clear_backup_dir($preferences->backup_unique_code);
        }

        //Delete old_entries from backup tables
        /*
        if (!defined('BACKUP_SILENTLY')) {
            echo "<li>".get_string("deletingolddata").'</li>';
        }
        $status = backup_delete_old_data();
        if (!$status) {
            if (!defined('BACKUP_SILENTLY')) {
                notify ("An error occurred deleting old backup data");
            }
            else {
                $errorstr = "An error occurred deleting old backup data";
                return false;
            }
        }
        */

        //Create the moodle.xml file
        if ($status) {
            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>".get_string("creatingxmlfile");
                //Begin a new list to xml contents
                echo "<ul>";
                echo "<li>".get_string("writingheader").'</li>';
            }
            //Obtain the xml file (create and open) and print prolog information
            $backup_file = backup_open_xml($preferences->backup_unique_code);
            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>".get_string("writinggeneralinfo").'</li>';
            }
            //Prints general info about backup to file
            if ($backup_file) {
                if (!$status = ap_backup_general_info($backup_file,$preferences)) {
                    if (!defined('BACKUP_SILENTLY')) {
                        notify("An error occurred while backing up general info");
                    }
                    else {
                        $errorstr = "An error occurred while backing up general info";
                        return false;
                    }
                }
            }
            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>".get_string("writingcoursedata");
                //Start new ul (for course)
                echo "<ul>";
                echo "<li>".get_string("courseinfo").'</li>';
            }
            //Prints course start (tag and general info)
            if ($status) {
                if (!$status = backup_course_start($backup_file,$preferences)) {
                    if (!defined('BACKUP_SILENTLY')) {
                        notify("An error occurred while backing up course start");
                    }
                    else {
                        $errorstr = "An error occurred while backing up course start";
                        return false;
                    }
                }
            }
            //Metacourse information
            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>Metacourses..... Not Included.</li>";
            }
            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>Blocks..... Not Included.</li>";
            }
            
            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>".get_string("sections").'</li>';
            }
            //Section info
            if ($status) {
                if (!$status = ap_backup_course_sections($backup_file,$preferences)) {
                    if (!defined('BACKUP_SILENTLY')) {
                        notify("An error occurred while backing up course sections");
                    } else {
                        $errorstr = "An error occurred while backing up course sections";
                        return false;
                    }
                }
            }

            //End course contents (close ul)
            if (!defined('BACKUP_SILENTLY')) {
                echo "</ul></li>";
            }

            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>Users info..... Not Included.</li>";
            }

            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>Messages..... Not Included.</li>";
            }

            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>Blogs..... Not Included.</li>";
            }

            //If we have selected to backup quizzes or other modules that use questions
            //we've already added ids of categories and questions to backup to backup_ids table
            // this has been done before calling to backup_execute in $modname_check_backup_mods calls
            if ($status) {
                if (!defined('BACKUP_SILENTLY')) {
                    echo "<li>".get_string("writingcategoriesandquestions").'</li>';
                }
            
                //our own question backup functions (customized for the activity_publisher)
                require_once($CFG->dirroot.'/blocks/activity_publisher/backup/question/backuplib.php');
                
                //get the current backuped quiz
         //       DebugBreak();
                
                $quiz_questions_ids = array();
                $quiz_question_categories_id = array();

				// detect we are using question instances 
				require_once($CFG->libdir.'/ddllib.php');               
                $cm = get_record('course_modules', 'id', $preferences->activity_instances[0]);
                $module = get_record('modules', 'id', $cm->module);
                $qitablename = $module->name.'_question_instances';
        		$table = new XMLDBTable($qitablename);
                if (table_exists($table)){

                	// if we guess we do, catch question instances and expected categories from here
                	
                	$instance = get_record($module->name, 'id', $cm->instance);
                   	$module_question_instances = get_records($module->name.'_question_instances', $module->name, $instance->id, 'id'); 
                   	if(!empty($module_question_instances)){
                       	foreach($module_question_instances as $q_ins){
                          	$category = get_field('question', 'category', 'id', $q_ins->question) ;
                          	$module_questions_ids[] = $q_ins->question;
                          	$module_question_categories_id[] = $category; 
                          
                          	// FIX :  also get children (multianswer) (one level -- do we need more ?)
                          	if ($children = get_records('question', 'parent', $q_ins->question, 'id', 'id,category')){
                           		foreach($children as $q_ins){
	                              	$module_questions_ids[] = $q_ins->id;
	                              	$module_question_categories_id[] = $q_ins->category; 
                           		}
                           	}
                       	}
		                if (!$status = ap_backup_question_categories($backup_file, $preferences, $module_question_categories_id, $module_questions_ids)) {
		                    if (!defined('BACKUP_SILENTLY')) {
		                        notify('An error occurred while backing up quiz categories');
		                    } else {
		                        $errorstr = 'An error occurred while backing up quiz categories';
		                        return false;
		                    }
		                }
		            }
	            }
            }
            
            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>Logs..... Not Included.</li>";
            }

            //Print scales info
            if ($status) {
                if (!defined('BACKUP_SILENTLY')) {
                    echo "<li>".get_string("writingscalesinfo").'</li>';
                }
                if (!$status = backup_scales_info($backup_file,$preferences)) {
                    if (!defined('BACKUP_SILENTLY')) {
                        notify('An error occurred while backing up scales');
                    } else {
                        $errorstr = 'An error occurred while backing up scales';
                        return false;
                    }
                }
            }

            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>Groups..... Not Included.</li>";
            }

            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>Groupings..... Not Included.</li>";
            }

            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>Grouping groups..... Not Included.</li>";
            }

            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>Events..... Not Included.</li>";
            }

            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>Grades..... Not Included.</li>";
            }

            //Module info, this unique function makes all the work!!
            //db export and module fileis copy
            
            if ($status) {
                $mods_to_backup = false;
                //Check if we have any mod to backup
                foreach ($preferences->mods as $module) {
                    //$module->backup = 1;
                    if ($module->backup) {
                        $mods_to_backup = true;
                    }
                }
                //If we have to backup some module
                if ($mods_to_backup) {
                    if (!defined('BACKUP_SILENTLY')) {
                        echo "<li>".get_string("writingmoduleinfo");
                    }
                    //Start modules tag
                    if (!$status = backup_modules_start($backup_file,$preferences)) {
                        if (!defined('BACKUP_SILENTLY')) {
                            notify("An error occurred while backing up module info");
                        } else {
                            $errorstr = "An error occurred while backing up module info";
                            return false;
                        }
                    }
                    //Open ul for module list
                    if (!defined('BACKUP_SILENTLY')) {
                        echo "<ul>";
                    }
                    //Iterate over modules and call backup
                    foreach ($preferences->mods as $module) {
                        if ($module->backup and $status) {
                            if (!defined('BACKUP_SILENTLY')) {
                                echo "<li>".get_string("modulenameplural",$module->name).'</li>';
                            }
                            if (!$status = ap_backup_module($backup_file,$preferences,$module->name)) {
                                if (!defined('BACKUP_SILENTLY')) {
                                    notify("An error occurred while backing up '$module->name'");
                                } else {
                                    $errorstr = "An error occurred while backing up '$module->name'";
                                    return false;
                                }
                            }
                        }
                    }
                    //Close ul for module list
                    if (!defined('BACKUP_SILENTLY')) {
                        echo "</ul></li>";
                    }
                    //Close modules tag
                    if (!$status = backup_modules_end ($backup_file,$preferences)) {
                        if (!defined('BACKUP_SILENTLY')) {
                            notify("An error occurred while finishing the module backups");
                        }
                        else {
                            $errorstr = "An error occurred while finishing the module backups";
                            return false;
                        }
                    }
                }
            }

            if (!defined('BACKUP_SILENTLY')) {
                echo '<li>Course format info.... Not included</li>';
            }

            //Prints course end
            if ($status) {
                if (!$status = backup_course_end($backup_file,$preferences)) {
                    if (!defined('BACKUP_SILENTLY')) {
                        notify("An error occurred while closing the course backup");
                    }
                    else {
                        $errorstr = "An error occurred while closing the course backup";
                        return false;
                    }
                }
            }
            //Close the xml file and xml data
            if ($backup_file) {
                backup_close_xml($backup_file);
            }

            //End xml contents (close ul)
            if (!defined('BACKUP_SILENTLY')) {
                echo "</ul></li>";
            }
        }

        if (!defined('BACKUP_SILENTLY')) {
            echo "<li>User files..... Not Included.</li>";
        }

        if (!defined('BACKUP_SILENTLY')) {
            echo "<li>Course files (all)..... Not Included.</li>";
        }

        if (!defined('BACKUP_SILENTLY')) {
            echo "<li>Site files (all)..... Not Included.</li>";
        }

    	if ($status) {
            if (!defined('BACKUP_SILENTLY')) {
                echo '<li>Copying module attached files...</li>';
            }
            // question files from course have been collected for course in backup_file table, with file_type = 'course'.
            if (!$status = ap_backup_copy_modules_files_from_course($preferences)) {
                if (!defined('BACKUP_SILENTLY')) {
                    notify("An error occurred while copying questions files");
                } else {
                    $errorstr = "An error occurred while copying questions files";
                    return false;
                }
            }
            // question files from site have been collected for course in backup_file table, with file_type = 'site'.
            if (!$status = ap_backup_copy_modules_files_from_site($preferences)) {
                if (!defined('BACKUP_SILENTLY')) {
                    notify("An error occurred while copying questions files");
                } else {
                    $errorstr = "An error occurred while copying questions files";
                    return false;
                }
            }
        }
     

        //Now, zip all the backup directory contents
        if ($status) {
            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>".get_string("zippingbackup").'</li>';
            }
            if (!$status = backup_zip ($preferences)) {
                if (!defined('BACKUP_SILENTLY')) {
                    notify("An error occurred while zipping the backup");
                }
                else {
                    $errorstr = "An error occurred while zipping the backup";
                    return false;
                }
            }
        }

        //Now, copy the zip file to course directory
        if ($status) {
            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>".get_string("copyingzipfile").'</li>';
            }
            if (!$status = copy_zip_to_course_dir ($preferences)) {
                if (!defined('BACKUP_SILENTLY')) {
                    notify("An error occurred while copying the zip file to the course directory");
                }
                else {
                    $errorstr = "An error occurred while copying the zip file to the course directory";
                    return false;
                }
            }
        }

        //Now, clean temporary data (db and filesystem)
        if ($status) {
            if (!defined('BACKUP_SILENTLY')) {
                echo "<li>".get_string("cleaningtempdata").'</li>';
            }
            if (!$status = clean_temp_data ($preferences)) {
                if (!defined('BACKUP_SILENTLY')) {
                    notify("An error occurred while cleaning up temporary data");
                }
                else {
                    $errorstr = "An error occurred while cleaning up temporary data";
                    return false;
                }
            }
        }

        return $status;
    }


    /**
    * This function generates the default zipfile name for a backup
    * based on the course shortname
    *
    * @param object $course course object
    * @return string filename (excluding path information)
    */
    function ap_backup_get_zipfile_name($module, $course_mod) {
         $backup_word = 'activity';
        //Calculate the backup word 
        $var = $module->name ; 
        $mod_instance = get_record($var, 'id', $course_mod->instance);
        $mod_name = $var.'-'.substr( strtolower( preg_replace("/[^A-Za-z0-9?!]/", '', $mod_instance->name)), 0, 8); 
        
        //Calculate the date format string
        $backup_date_format = str_replace(' ','_',get_string('backupnameformat'));
        //If non-translated, use "%Y%m%d-%H%M"
        if (substr($backup_date_format,0,1) == '[') {
            $backup_date_format = "%%Y%%m%%d-%%H%%M";
        }

        //Calculate the shortname
        $backup_shortname = 'activity'; //clean_filename($course->shortname);
        
        $random = rand(30000,100000);
        
        //Calculate the final backup filename
        //The backup word
        $backup_name = $backup_word."-";
        //The shortname
        $backup_name .= moodle_strtolower($mod_name).'-';
        //The date format
        $backup_name .= userdate(time(), $backup_date_format, 99, false);
        
        $backup_name .= $random;
        //The extension
        $backup_name .= '.zip';
        //And finally, clean everything
        $backup_name = clean_filename($backup_name);

        return $backup_name;
    }


?>
