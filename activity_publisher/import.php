<?php

	require_once('../../config.php');
	require_once ("backup/lib.php");
	require_once ("backup/restorelib.php");
	require_once ("$CFG->libdir/blocklib.php");
	require_once ("$CFG->libdir/adminlib.php");

    $courseid = required_param('cid', PARAM_INT);
    
    if (!$course = get_record('course', 'id', "$courseid")){
    	error('Bad course ID');
    }
    
    $context = get_context_instance(CONTEXT_COURSE, $courseid);    
    require_capability('moodle/course:manageactivities', $context);
    
    print_header_simple($SITE->shortname, $SITE->shortname, build_navigation(array()));
    
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
    restore_precheck($courseid, $file, $errorstr, $noredirect=false, null);  
    
    print_footer();
?>