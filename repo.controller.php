<?php

// choose the backup file from backup files tree
if ($action == 'choosebackupfile') {
    if ($fileinfo = $browser->get_file_info($filecontext, $component, $filearea, $itemid, $filepath, $filename)) {
        $filename = restore_controller::get_tempdir_name($course->id, $USER->id);
        $pathname = $tmpdir . '/' . $filename;
        $fileinfo->copy_to_pathname($pathname);
        $restore_url = new moodle_url('/blocks/activity_publisher/restore.php', array('contextid' => $contextid, 'filename' => $filename));
        redirect($restore_url);
    } else {
        redirect($url, get_string('filenotfound', 'error'));
    }
    die;
}

/************************** Deletes an activity backup in the pool ************************/
if ($action == 'delete'){
	
	$fileid = required_param('fileid', PARAM_INT);
	
	$fs = get_file_storage();
	
	if ($file = $fs->get_file_by_id($fileid)){
		$file->delete();
	}
}