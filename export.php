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
 * @package block_activity_publisher
 * export summary 
 * @author Wafa Adham, Adham Inc.
 * @version 1.0
 */

require('../../config.php');
require_once($CFG->dirroot.'/blocks/activity_publisher/lib/activity_publisher.class.php');

$modid = required_param('mod',PARAM_INT);
$blockid = required_param('bid',PARAM_INT);
$id = $courseid = required_param('course', PARAM_INT);
$action = optional_param('what', null,PARAM_TEXT);
$instances = required_param_array('instance', PARAM_INT);

$contextid = context_block::instance($blockid)->id;

define('BACKUP_SILENTLY', 1);

$course = $DB->get_record('course', array('id' => $courseid));

if (!$course) {
    print_error('coursemisconf');
}

// Security.

require_login($id);

//get course context
$context = context_course::instance($id);

echo '<ul>';

if(!defined('BACKUP_SILENTLY')){
    $checkingstr = get_string('checkingconfiguration', 'block_activity_publisher');
    print("<li>$checkingstr...</li>");
}

if(!defined('BACKUP_SILENTLY')) {  
    $startingstr = get_string('startingbackup', 'block_activity_publisher');
    print("<li>$startingstr</li>") ;
}
 
//clean up backup pref 
unset ($SESSION->backupprefs);

// get the module object;
$module = $DB->get_record('modules', array('id' => $modid));
$pluginmanager = core_plugin_manager::instance();
$plugininfo = $pluginmanager->get_plugin_info('mod_'.$module->name);

if (empty($plugininfo)) {
    print_error(get_string('errorplugin', 'block_activity_publisher'));
}

//we have all the infos , now we start
$backupfiles = activity_publisher::course_backup_activities($course, $instances, $blockid);

echo '</ul>';

foreach($backupfiles as $cmid => $bf){
    // generic metadata
    if (is_null($bf)){
        continue;
    }

    $instanceid = $DB->get_field('course_modules', 'instance', array('id' => $cmid));
    $instance = $DB->get_record($module->name, array('id' => $instanceid));

    $backupmetadataelements = array();

    if (preg_match('/lom/', $CFG->pluginchoice)) {

        // find here some mapping between moodle objects and some metadata standard defines
        require_once $CFG->dirroot.'/mod/sharedresource/moodlemetadata.php';

        // Title
        sharedresource_append_metadata_elements($backupmetadataelements, '1_2:0_0', $instance->name, $CFG->pluginchoice);

        // Lang
        $lang = ($course->lang) ? $course->lang : $CFG->lang ;
        sharedresource_append_metadata_elements($backupmetadataelements, '1_3:0_0', $lang, $CFG->pluginchoice);

        // Description
        if (isset($instance->description)) {
            $description = $instance->description;
        } else if (isset($instance->summary)) {
            $description = $instance->summary;
        } else if (isset($instance->intro)) {
            $description = $instance->intro;
        }
        sharedresource_append_metadata_elements($backupmetadataelements, '1_4:0_0', $description, $CFG->pluginchoice);

        // Keywords
        sharedresource_append_metadata_elements($backupmetadataelements, '1_5:0_0', $plugininfo->displayname, $CFG->pluginchoice);

        // Structure.
        sharedresource_append_metadata_elements($backupmetadataelements, '1_7:0_0', 'atomic', $CFG->pluginchoice);

        $adddate = $DB->get_field('course_modules', 'added', array('id' => $cmid));
        sharedresource_append_author_data($backupmetadataelements, $course->id, $adddate);

        sharedresource_append_metadata_elements($backupmetadataelements, '4_1:0_0', '.mbz', $CFG->pluginchoice);
        sharedresource_append_metadata_elements($backupmetadataelements, '4_2:0_0', $bf->get_filesize(), $CFG->pluginchoice);
        $sharedurl = new moodle_url('/mod/sharedresource/view.php', array('identifier' => $bf->get_contenthash()));
        sharedresource_append_metadata_elements($backupmetadataelements, '4_3:0_0', $sharedurl, $CFG->pluginchoice);
        sharedresource_append_metadata_elements($backupmetadataelements, '4_4_1_1:0_0_0_0', 'application', $CFG->pluginchoice);
        sharedresource_append_metadata_elements($backupmetadataelements, '4_4_1_2:0_0_0_0', 'moodle', $CFG->pluginchoice);
        sharedresource_append_metadata_elements($backupmetadataelements, '4_4_1_3:0_0_0_0', $CFG->version, $CFG->pluginchoice);
        sharedresource_append_metadata_elements($backupmetadataelements, '4_4_1_4:0_0_0_0', $CFG->version, $CFG->pluginchoice);
        sharedresource_append_metadata_elements($backupmetadataelements, '4_4_1_1:0_0_1_0', 'module', $CFG->pluginchoice);
        sharedresource_append_metadata_elements($backupmetadataelements, '4_4_1_2:0_0_1_0', $plugininfo->name, $CFG->pluginchoice);
        sharedresource_append_metadata_elements($backupmetadataelements, '4_4_1_3:0_0_1_0', $plugininfo->versiondb, $CFG->pluginchoice);
        sharedresource_append_metadata_elements($backupmetadataelements, '4_4_1_4:0_0_1_0', $plugininfo->versiondb, $CFG->pluginchoice);

        sharedresource_append_metadata_elements($backupmetadataelements, '4_5:0_0', get_string('installation', 'block_activity_publisher', get_string('pluginname', $module->name)), $CFG->pluginchoice);
        sharedresource_append_metadata_elements($backupmetadataelements, '4_6:0_0', get_string('platformrequirement', 'block_activity_publisher'), $CFG->pluginchoice);

        if (in_array($module->name, array('resource', 'sharedresource', 'directory', 'label', 'customlabel'))){
            sharedresource_append_metadata_elements($backupmetadataelements, '5_1:0_0', 'expositive', $CFG->pluginchoice);
        } else if (in_array($module->name, array('url'))){
            sharedresource_append_metadata_elements($backupmetadataelements, '5_1:0_0', 'mixed', $CFG->pluginchoice);
        } else {
            sharedresource_append_metadata_elements($backupmetadataelements, '5_1:0_0', 'active', $CFG->pluginchoice);
        }
        if (array_key_exists($module->name, $MODRESOURCETYPES)){
            sharedresource_append_metadata_elements($backupmetadataelements, '5_2:0_0', $MODRESOURCETYPES[$module->name], $CFG->pluginchoice);
        }
        if (array_key_exists($module->name, $MODINTERACTIVITYLEVELS)){
            sharedresource_append_metadata_elements($backupmetadataelements, '5_3:0_0', $MODINTERACTIVITYLEVELS[$module->name], $CFG->pluginchoice);
        }
        if (array_key_exists($module->name, $MODSEMANTICDENSITIES)){
            sharedresource_append_metadata_elements($backupmetadataelements, '5_4:0_0', $MODSEMANTICDENSITIES[$module->name], $CFG->pluginchoice);
        }
        sharedresource_append_metadata_elements($backupmetadataelements, '5_5:0_0', 'learner', $CFG->pluginchoice);
    }

    if (preg_match('/lomfr/', $CFG->pluginchoice)){
        if (array_key_exists($module->name, $MODDOCUMENTTYPES)){
            sharedresource_append_metadata_elements($backupmetadataelements, '1_9:0_0', $MODDOCUMENTTYPES[$module->name], $CFG->pluginchoice);
        }
        if (array_key_exists($module->name, $MODLEARNINGACTIVITIES)){
            sharedresource_append_metadata_elements($backupmetadataelements, '5_12:0_0', $MODLEARNINGACTIVITIES[$module->name], $CFG->pluginchoice);
        }
    }

    if (preg_match('/scolomfr/', $CFG->pluginchoice)) {
        if (array_key_exists($module->name, $MODGENERALDOCUMENTTYPES)) {
            sharedresource_append_metadata_elements($backupmetadataelements, '1_10:0_0', $MODGENERALDOCUMENTTYPES[$module->name], $CFG->pluginchoice);
        }
    }

    // store metadata in field "source". this will be used at "publish time"
    $bf->set_source(base64_encode(serialize($backupmetadataelements)));
}

// finalize

if ($action == 'publish') {
    redirect(new moodle_url('/blocks/activity_publisher/repo.php', array('contextid' => $contextid)));
} 

/*
// Removed use case for the moment
if ($action == 'share'){

    // when sharing, only one activity can be selected        

    $singleinstance = array_pop($filelink);
    $singlecoursemod = array_pop($instances);
    $cm = $DB->get_record('course_modules', array('id' => $singlecoursemod));
    $instance = $DB->get_record($module->name, array('id' => $cm->instance));
    
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
        $description = $instance->description;
    } elseif(!empty($instance->summary)){
        $description = $instance->summary;
    } elseif(!empty($instance->intro)){
        $description = $instance->intro;
    }
    
    $sharedresource_entry->description = $description;
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

    foreach($backupmetadataelements as $elm){
        $sharedresource_entry->add_element($elm->name, $elm->value, $elm->plugin);
    }
    
    $SESSION->sr_entry = serialize($sharedresource_entry);
    $paramstr = "course={$id}&type=file&add=sharedresource&mode=add";
    redirect($CFG->wwwroot.'/mod/sharedresource/metadataform.php?'.$paramstr);
}
*/

// if we reach this point, we are in error
throw(new CodingException('Code should never reach this point'));