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
 * activity publisher class, encapsulate the entry points for the activity backup
 * process.
 * @author Wafa Adham, Adham Inc.
 * @version 1.0
 */
defined('MOODLE_INTERNAL') || die;

// Check if this function already loaded , this means the backup lib already included.

require_once($CFG->dirroot.'/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot.'/backup/util/includes/restore_includes.php');

class activity_publisher {

    const BACKUP_STATUS_ERROR = 0;

    /**
     * @param object $course the surrounding course
     * @param object $module instance of “module” record
     * @param array $instance_id the module instance ids, the function normally accepts an array , and 
     * return the link for the generated export file ,if the function is givven a comma seperated instances 
     * ids then all are packed in the same file, in all cases the function generate 1 export package file.
     */
    public static function backup_single_module($course, $cmid,$blockid) {
        global $CFG, $preferences, $SESSION, $DB, $USER;

        // Just a random.
        $backupid = md5(time());

        if (!is_null($cmid)) {
            $cm = get_coursemodule_from_id(null, $cmid, $course->id, false, MUST_EXIST);
            $type = backup::TYPE_1ACTIVITY;
            $id = $cmid;
        }
       
        if (!($bc = backup_ui::load_controller($backupid))) {
            $bc = new backup_controller($type, $id, backup::FORMAT_MOODLE,
                                    backup::INTERACTIVE_NO, backup::MODE_AUTOMATED, $USER->id);
        }

        try {
            $settings = array(
                'users' => 'backup_auto_users',
                'role_assignments' => 'backup_auto_role_assignments',
                'activities' => 'backup_auto_activities',
                'blocks' => 'backup_auto_blocks',
                'filters' => 'backup_auto_filters',
                'comments' => 'backup_auto_comments',
                'completion_information' => 'backup_auto_userscompletion',
                'logs' => 'backup_auto_logs',
                'histories' => 'backup_auto_histories'
            );
            foreach ($settings as $setting => $configsetting) {
                if ($bc->get_plan()->setting_exists($setting)) {
                 //   $bc->get_plan()->get_setting($setting)->set_value($config->{$configsetting});
                }
            }

            // Set the default filename
            $format = $bc->get_format();
            $type = $bc->get_type();
            $id = $bc->get_id();
            $users = $bc->get_plan()->get_setting('users')->get_value();
            $anonymised = $bc->get_plan()->get_setting('anonymize')->get_value();
            $filename = backup_plan_dbops::get_default_backup_filename($format, $type, $id, $users, $anonymised);
            $bc->get_plan()->get_setting('filename')->set_value(backup_plan_dbops::get_default_backup_filename($format, $type, $id, $users, $anonymised));

            $bc->set_status(backup::STATUS_AWAITING);

            $bc->execute_plan();
            $results = $bc->get_results();
        
          //  $outcome = self::outcome_from_results($results);
            $file = $results['backup_destination']; // may be empty if file already moved to target location

            //next we need to copy the file to the right file space .
            $fs = get_file_storage();

            $newfile = new stdClass();
            $newfile->component = 'block_activity_publisher';
            $newfile->filearea = 'activity_backup';
            $block_context = context_block::instance($blockid);
            $newfile->contextid = $block_context->id;

            // Create the new file record.
            $exported_file = $fs->create_file_from_storedfile($newfile, $file);
           
            // Delete the temp file.
            $fs->delete_area_files($file->get_contextid(), 'backup', 'automated', 0);
            return $exported_file;

        } catch (moodle_exception $e) {

            $bc->log('backup_auto_failed_on_activity', backup::LOG_ERROR, $course->shortname); // Log error header.
            $bc->log('Exception: ' . $e->errorcode, backup::LOG_ERROR, $e->a, 1); // Log original exception problem.
            $bc->log('Debug: ' . $e->debuginfo, backup::LOG_DEBUG, null, 1); // Log original debug information.
            $outcome = self::BACKUP_STATUS_ERROR;
        }

        $bc->destroy();
        unset($bc);

        return null;
    }

    /**
     * @param object $course the surrounding course
     * @param object $module
     * @param array $activities_arr gives the configuration list of activities to export
     */
    public static function course_backup_activities($course, $cmids, $blockid) {

        $backup_files = array();
        foreach ($cmids as $cmid) {
             $backup_files[$cmid] = self::backup_single_module($course, $cmid, $blockid);
        }

        return $backup_files;
    }

    /**
     * load the activities select box for the given course id
     * 
     * @param mixed $course_id
     * @return mixed select box with available activities to export.
     */
    public static function load_course_activities_select($course_id) {
        global $CFG;

        $config = get_config('block_activity_publisher');

        if (empty($config->unable_mods)) {
            set_config('unable_mods', '', 'block_activity_publisher');
        }
        $unabled_mods = explode(',', $config->unable_mods);
        $modules = self::get_course_mods($course_id);

        $select = '<select name="mod" >';

        if ( $modules &&  (count($modules) > 0)) {
            foreach ($modules as $mod) {
                if (!in_array($mod->name, $unabled_mods)) {
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

    public static function get_course_mods($course_id) {
        global $CFG, $DB;

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

        $modules = $DB->get_records_sql($query);

        return $modules;
    }

    /**
     * Restore module
     * 
     * @param mixed $course
     * @param mixed $module
     * @param mixed $instance_id
     */
    public static function restore_single_module($to_course_id, $file) {
        global $CFG, $preferences, $SESSION, $USER, $DB;

        $context = context_course::instance($to_course_id);
        $tempdirname = restore_controller::get_tempdir_name($context->id, $USER->id);
        $temppath = $CFG->tempdir."/backup/$tempdirname/";
        $fb = get_file_packer();

        // Get file extracted and ready to be fetched by the restore process.

        if (!$fb->extract_to_pathname($file, $temppath)) {
            print_error('fileerror', 'block_activity_publisher');
            exit; 
        }
        $rc = new restore_controller($tempdirname, $to_course_id, backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id, backup::TARGET_EXISTING_ADDING) ;

        $rc->set_status(backup::STATUS_AWAITING);

        try {
            // Execute the plan.
            $rc->execute_plan();
        } catch (moodle_exception $e) {
        }

        $rc->destroy();
        unset($rc);

        return ;
    }

    /**
     * checks if the file name matches commonpatterns for activity backups
     *
     */
    public static function is_activity_backup($archivename) {
        return preg_match('/[a-fA-F0-9]{32}-activity-.*\.zip$/', $archivename);
    }

    /**
     * publication prepares file to be added to central sharedresource library, and hooks in
     * the metadata form procedure to complete proper registration of the resource.
     * Entry point : mod/sharedresource/edit.php
     * Scenario : First prepare a simulated user's filepicker record with a copy of the original filerec
     * Than simulates a call to the entry point asking for adding a file in this fake filepicker
     *
     */
    public static function publish_file($fileid, $return = 0, $mode = 'add', $sharingcontext = 0) {
        global $USER, $COURSE, $CFG, $SESSION;

        if (!$sharingcontext) {
            $sharingcontext = context_system::instance()->id;
        }

        $fs = get_file_storage();
        if (!$fileinfo = $fs->get_file_by_id($fileid)) {
            print("file does not exist.");
            return false;
        }

        $system_context = context_system::instance();

        if (self::is_ref_published($fileinfo->get_contenthash())) {
               return -1;
        }

        $newfile = new stdClass();
        $newfile->component = 'user';
        $newfile->filearea = 'draft';
        $newfile->itemid = file_get_unused_draft_itemid();
        $newfile->contextid = context_user::instance($USER->id)->id;

        // Create the new file record.
        $draftfile = $fs->create_file_from_storedfile($newfile, $fileinfo);

        $backupmetadataelements = unserialize(base64_decode($draftfile->get_source()));

        // We make a shared resource entry, put it in session and invoke metadataform to finish indexation.
        require_once($CFG->dirroot.'/mod/sharedresource/sharedresource_entry.class.php');
        require_once($CFG->dirroot.'/mod/sharedresource/sharedresource_metadata.class.php');
        require_once($CFG->dirroot.'/mod/sharedresource/lib.php');

        $mtdstandard = sharedresource_plugin_base::load_mtdstandard($CFG->pluginchoice);

        // We can get back some sharedresource internal attributes from metadata
        $sharedresource_entry = new sharedresource_entry(false); 
        $sharedresource_entry->title = $backupmetadataelements['1_2:0_0']->value;
        $sharedresource_entry->description = $backupmetadataelements['1_4:0_0']->value;
        $sharedresource_entry->keywords = $backupmetadataelements['1_5:0_0']->value;
        $sharedresource_entry->type = 'file';
        $sharedresource_entry->identifier = $draftfile->get_contenthash();
        $sharedresource_entry->file = $draftfile->get_id();
        $tempfilename = $draftfile->get_filepath().$draftfile->get_filename();
        if (function_exists('mime_content_type')) {
            $sharedresource_entry->mimetype = mime_content_type($tempfilename);
        }
        $sharedresource_entry->url = '';
        // do not record instance yet, rely on metadataform output to do it properly
        // if (!record_exists('sharedresource_entry', 'identifier', $sharedresource_entry->identifier)){
            // $sharedresource_entry->add_instance();
        // }

        foreach ($backupmetadataelements as $elm) {
            $sharedresource_entry->add_element($elm->name, $elm->value, $elm->plugin);
        }

        $SESSION->sr_entry = serialize($sharedresource_entry);

        $params = array('course' => $COURSE->id,
                        'section' => 0,
                        'type' => 'file',
                        'add' => 'sharedresource',
                        'return' => $return,
                        'mode' => $mode,
                        'context' => $sharingcontext);

        $fullurl = new moodle_url('/mod/sharedresource/metadataform.php', $params);
        redirect($fullurl);
    }

    /**
     * checks if file is already published in the library
     *
     */
    public static function is_file_published($filecontextid, $component, $filearea, $itemid, $filepath, $filename) {

        $fs = get_file_storage();

        $system_context = context_system::instance();

        $stored_file = $fs->get_file($filecontextid, $component, $filearea, $itemid, $filepath, $filename);
        $contenthash = $stored_file->get_contenthash();

        return self::is_ref_published($contenthash);
    }

    /**
     * checks if file is already published in the library
     * @param string $contenthash an MD5 hash characteristic of the resource content
     */
    public static function is_ref_published($contenthash) {
        global $DB;

        if ($DB->record_exists('sharedresource_entry', array('identifier' => $contenthash))) {
            return 1;
        } else {
            return 0;
        }
    }
}
