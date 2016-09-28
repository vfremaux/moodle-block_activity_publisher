<?php //$Id: lib.php,v 1.2 2012-07-15 11:21:13 vf Exp $
    //This file contains all the general function needed (file manipulation...)
    //not directly part of the backup/restore utility plus some constants


    /**
    * Function to backup an entire course silently and create a zipfile.
    *
    * @param int $courseid the id of the course
    * @param array $prefs see {@link backup_generate_preferences_artificially}
    */
    function ap_backup_course_silently($courseid, $prefs, &$errorstring) {
        global $CFG, $preferences; // global preferences here because something else wants it :(
        if (!defined('BACKUP_SILENTLY')) {
            define('BACKUP_SILENTLY', 1);
        }
        if (!$course = get_record('course', 'id', $courseid)) {
            debugging("Couldn't find course with id $courseid in backup_course_silently");
            return false;
        }
        $preferences = ap_backup_generate_preferences_artificially($course, $prefs);
        $preferences->destination    = array_key_exists('destination', $prefs) ? $prefs['destination'] : 0;
        if (ap_backup_execute($preferences, $errorstring)) {
            return $CFG->dataroot . '/' . $course->id . '/backupdata/' . $preferences->backup_name;
        } else {
            return false;
        }
    }

    /**
    * Function to generate the $preferences variable that
    * backup uses.  This will back up all modules and instances in a course.
    *
    * @param object $course course object
    * @param array $prefs can contain:
            backup_metacourse
            backup_users
            backup_logs
            backup_user_files
            backup_course_files
            backup_site_files
            backup_messages
            userdata
    * and if not provided, they will not be included.
    */
    function ap_backup_generate_preferences_artificially($course, $prefs) {
        global $CFG;
        $preferences = new StdClass;
        $preferences->backup_unique_code = time();
        $preferences->backup_users = (isset($prefs['backup_users']) ? $prefs['backup_users'] : 0);
        $preferences->backup_name = ap_backup_get_zipfile_name($course, $preferences->backup_unique_code);
        $preferences->mods = array();
        $count = 0;

        if ($allmods = get_records("modules") ) {
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
                    $preferences->$var = true;
                    $preferences->mods[$modname]->instances[$instance->id]->backup = true;
                    $var = 'backup_user_info_'.$modname.'_instance_'.$instance->id;
                    $preferences->$var = 0; // never store userdata
                    $preferences->mods[$modname]->instances[$instance->id]->userinfo = $preferences->$var;
                    $var = 'backup_'.$modname.'_instances';
                    $preferences->$var = 1; // we need this later to determine what to display in modcheckbackup.
                }

                //Check data
                //Check module info
                $preferences->mods[$modname]->name = $modname;

                $var = "backup_".$modname;
                $preferences->$var = true;
                $preferences->mods[$modname]->backup = true;

                //Check include user info
                $var = "backup_user_info_".$modname;
                $preferences->$var = (!array_key_exists('userdata', $prefs) || $prefs['userdata']);
                $preferences->mods[$modname]->userinfo = $preferences->$var;

                //Call the check function to show more info
                $modcheckbackup = $modname."_check_backup_mods";
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
                            $instancestopass[$instance->id]= $obj;
                            $countinstances++;

                        }
                    }
                }
                $modcheckbackup($course->id,$preferences->$var,$preferences->backup_unique_code,$instancestopass);
            }
        }

        //Check other parameters
        $preferences->backup_metacourse = (isset($prefs['backup_metacourse']) ? $prefs['backup_metacourse'] : 0);
        $preferences->backup_logs = (isset($prefs['backup_logs']) ? $prefs['backup_logs'] : 0);
        $preferences->backup_user_files = (isset($prefs['backup_user_files']) ? $prefs['backup_user_files'] : 0);
        $preferences->backup_course_files = (isset($prefs['backup_course_files']) ? $prefs['backup_course_files'] : 0);
        $preferences->backup_site_files = (isset($prefs['backup_site_files']) ? $prefs['backup_site_files'] : 0);
        $preferences->backup_messages = (isset($prefs['backup_messages']) ? $prefs['backup_messages'] : 0);
        $preferences->backup_gradebook_history = (isset($prefs['backup_gradebook_history']) ? $prefs['backup_gradebook_history'] : 0);
        $preferences->backup_blogs = (isset($prefs['backup_blogs']) ? $prefs['backup_blogs'] : 0);
        $preferences->backup_course = $course->id;

        //Check users
        /*
        user_check_backup($course->id,$preferences->backup_unique_code,$preferences->backup_users,$preferences->backup_messages, $preferences->backup_blogs);
        */

        //Check logs
        /*
        log_check_backup($course->id);
        */

        //Check user files
        /*
        user_files_check_backup($course->id,$preferences->backup_unique_code);
        */

        //Check course files
        /*
        course_files_check_backup($course->id,$preferences->backup_unique_code);
        */

        //Check site files
        /*
        site_files_check_backup($course->id,$preferences->backup_unique_code);
        */

        //Role assignments
        $roles = get_records('role', '', '', 'sortorder');
        foreach ($roles as $role) {
            $preferences->backuproleassignments[$role->id] = $role;
        }

        backup_add_static_preferences($preferences);
        return $preferences;
    }


?>
