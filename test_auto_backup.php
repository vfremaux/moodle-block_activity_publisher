<?php
  /**
  * Test file for using 1 function to import/export specefic activity in a course.
  */
  
  require_once('../../config.php');
  require_once('lib/activity_publisher.class.php');
  
  $ap = new activity_publisher();
  
  $c_id = optional_param('c',null,PARAM_INT);
  $id = optional_param('id',null,PARAM_INT);
  
  $course= get_record('course','id',$c_id);
  if(!$course){
      error("Invalid course.");      
  }
  
  $cm = get_record('course_modules','id',$id);
  $module = get_record('modules','id',$cm->module);
      
  $ap::backup_single_module($course, $module ,$id);
  
  
  
  
  
  
  
  
  
  
  
  
?>
