<?php
  /**
  * Test file for using 1 function to import/export specefic activity in a course.
  */
  
  require_once('../../config.php');
  require_once('lib/activity_publisher.class.php');
  
  $ap = new activity_publisher();
  
  $c_id = optional_param('c',2,PARAM_INT);
  $cmid = optional_param('id',1,PARAM_INT);
  
  require_login();
  $course= $DB->get_record('course',array('id'=>$c_id));
  if(!$course){
      error("Invalid course.");      
  }

 
  $ap::backup_single_module($course, $cmid ,14);
  
  
  
  
  
  
  
  
  
  
  
  
?>
