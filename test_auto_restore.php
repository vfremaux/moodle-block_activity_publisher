<?php
  /**
  * Test file for using 1 function to import/export specefic activity in a course.
  */
  
    require_once('../../config.php');
  
    //Units used
    require_once("../../lib/xmlize.php");
    require_once("../../course/lib.php");
    //require_once("lib.php");
    //require_once("backup/bb/restore_bb.php");
    require_once("$CFG->libdir/wiki_to_markdown.php" );
    require_once("$CFG->libdir/adminlib.php");


    require_once('lib/activity_publisher.class.php');
    require_login();

    $ap = new activity_publisher();

    $cid = optional_param('c',null,PARAM_INT);
    $id = optional_param('id',null,PARAM_INT);

    $course = $DB->get_record('course',array('id'=> $cid));
    if(!$course){
    	error("Invalid course.");
    }

    $file_mbz  = $CFG->dataroot.'/backup-moodle2-activity-2-forum2-20140117-1642.mbz';   
 
    
    $ap::restore_single_module($cid, $file_mbz);












?>
