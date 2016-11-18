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
 * @package   block_activity_publisher
 * @category  blocks
 * @copyright 2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

// Choose the backup file from backup files tree.
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

// Deletes an activity backup in the pool *******************************.
if ($action == 'delete') {

    $fileid = required_param('fileid', PARAM_INT);

    $fs = get_file_storage();

    if ($file = $fs->get_file_by_id($fileid)) {
        $file->delete();
    }
}