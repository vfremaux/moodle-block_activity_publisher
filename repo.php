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
 * Import backup file or select existing backup file from moodle
 * @package   block_activity_publisher
 * @category  blocks
 * @author Wafa Adham <admin@adham.ps>
 * @author Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright 2014 MyLearningFactory & Adham Ltd
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/blocks/activity_publisher/repo_form.php');
require_once($CFG->dirroot.'/backup/util/includes/restore_includes.php');

// Current context.
$contextid = required_param('contextid', PARAM_INT);
$publish = optional_param('publish', null ,PARAM_INT);
$filecontextid = optional_param('filecontextid', 0, PARAM_INT);

// Action.

$action = optional_param('what', '', PARAM_ALPHA);

// file parameters

// non js interface may require these parameters
$component  = optional_param('component', null, PARAM_COMPONENT);
$filearea   = optional_param('filearea', null, PARAM_AREA);
$itemid     = optional_param('itemid', null, PARAM_INT);
$filepath   = optional_param('filepath', null, PARAM_PATH);
$filename   = optional_param('filename', null, PARAM_FILE);

list($context, $course, $cm) = get_context_info_array($contextid);

// will be used when restore
if (!empty($filecontextid)) {
    $filecontext = context::instance_by_id($filecontextid);
}

$url = new moodle_url('/blocks/activity_publisher/repo.php', array('contextid' => $contextid));
$heading = get_string('activity_publisher_repo', 'block_activity_publisher');

require_login($course, false, $cm);
//require_capability('moodle/restore:restorecourse', $context);

$browser = get_file_browser();

if ($action){
    include $CFG->dirroot.'/blocks/activity_publisher/repo.controller.php';
}

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('course') . ': ' . $course->fullname);
$PAGE->set_heading($heading);
$PAGE->set_pagelayout('admin');

$form = new activity_restore_form(null, array('contextid' => $contextid));
$data = $form->get_data();

// check if tmp dir exists
$tmpdir = $CFG->tempdir . '/backup';
if (!check_dir_exists($tmpdir, true, true)) {
    throw new restore_controller_exception('cannot_create_backup_temp_dir');
}

if ($data && has_capability('moodle/restore:uploadfile', $context)) {
    $filename = restore_controller::get_tempdir_name($course->id, $USER->id);
    $pathname = $tmpdir . '/' . $filename;
    $form->save_file('backupfile', $pathname);
    $restore_url = new moodle_url('/backup/restore.php', array('contextid' => $contextid, 'filename' => $filename));
    redirect($restore_url);
    die;
}

echo $OUTPUT->header();

// require uploadfile cap to use file picker
if (has_capability('moodle/restore:uploadfile', $context)) {
    echo $OUTPUT->heading(get_string('importfile', 'backup'));
    echo $OUTPUT->container_start();
    $form->display();
    echo $OUTPUT->container_end();
}

if ($publish == 1) {
    echo $OUTPUT->box_start('success-message');
    echo $OUTPUT->notification(get_string('successful', 'block_activity_publisher'));
    echo $OUTPUT->box_end();
} elseif ($publish == -1) {
    echo $OUTPUT->box_start('success-message');
    echo $OUTPUT->notification(get_string('alreadypublished', 'block_activity_publisher'));
    echo $OUTPUT->box_end();
}

if ($context->contextlevel == CONTEXT_BLOCK) {
    echo $OUTPUT->heading_with_help(get_string('choosefilefromactivitybackup', 'backup'), 'choosefilefromuserbackup', 'backup');
    echo $OUTPUT->container_start();
    $treeview_options = array();
    $user_context = context_user::instance($USER->id);
    $treeview_options['filecontext'] = $context;
    $treeview_options['currentcontext'] = $context;
    $treeview_options['component']   = 'block_activity_publisher';
    $treeview_options['context']     = $context;
    $treeview_options['filearea']    = 'activity_backup';
  
    $renderer = $PAGE->get_renderer('block_activity_publisher');
    echo $renderer->backup_files_viewer($treeview_options);
    echo $OUTPUT->container_end();
}

echo $OUTPUT->footer();