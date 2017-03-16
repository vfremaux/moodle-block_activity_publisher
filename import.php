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

require('../../config.php');
require_once("$CFG->dirroot/backup/lib.php");
require_once("$CFG->dirroot/backup/restorelib.php");
require_once("$CFG->libdir/blocklib.php");
require_once("$CFG->libdir/adminlib.php");

$courseid = required_param('cid', PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    error('Bad course ID');
}

// Security.

$context = context_course::instance($courseid);
require_login();
require_capability('moodle/course:manageactivities', $context);

echo $OUTPUT->header();

//$fulldir_file = required_param('upfile',PARAM_TEXT);

$filename = $_FILES['upfile']['name']; 

 //   DebugBreak();

/* uploading the file to activity publisher folder and check if exists
* if not exists create new folder with the same name and place
* and put the file want to upload inside it 
*/
$destination_path = $CFG->dataroot."/activity_publisher/";

if (file_exists($destination_path)){
       $target_path = $destination_path . basename( $_FILES['upfile']['name']);
       
       if(move_uploaded_file($_FILES['upfile']['tmp_name'], $target_path)) {
          echo "uploaded!";
       } else {
           echo "error while uploading!";
       }
    } else {
        $dirPath = $destination_path;
        mkdir($dirPath);
       $target_path = $destination_path . basename( $_FILES['upfile']['name']);

       if(move_uploaded_file($_FILES['upfile']['tmp_name'], $target_path)) {
          echo "uploaded!";
       } else  {
           echo "error while uploading!";
       }
    }

/*
    Creating temporary structures
    Deleting old data
    Copying zip file
    Unzipping backup
    Checking for BlackBoard export
    Checking backup
    Reading info from backup
*/     
$errorstr = '';
$file = $destination_path."/".$filename."";
ap_restore_precheck($courseid, $file, $errorstr, $noredirect=false, null);  

echo $OUTPUT->footer();
