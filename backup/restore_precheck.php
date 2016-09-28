<?php  // $Id: restore_precheck.php,v 1.2 2012-07-15 11:21:14 vf Exp $
    //This page copies th zip to the temp directory,
    //unzip it, check that it is a valid backup file
    //inform about its contents and fill all the necesary
    //variables to continue with the restore.

    //Checks we have the file variable
    // DebugBreak();
    if (!isset($file)) {         
        error ("File not specified");
    }

    //Check login   
    require_login();
 
    //Check admin
    if (!empty($id)) {
        if (!has_capability('moodle/site:restore', context_course::instance($id))) {
            if (empty($to)) {
                error("You need to be a teacher or admin user to use this page.", "$CFG->wwwroot/login/index.php");
            } else {
                if (!has_capability('moodle/site:restore', context_course::instance($to))
                    && !has_capability('moodle/site:import',  context_course::instance($to))) {
                    error("You need to be a teacher or admin user to use this page.", "$CFG->wwwroot/login/index.php");
                }
            }
        }
    } else {
        if (!has_capability('moodle/site:restore', context_system::instance())) {
            error("You need to be an admin user to use this page.", "$CFG->wwwroot/login/index.php");
        }
    }

    //Check site
    if (!$site = get_site()) {
        error("Site not found!");
    }

    $errorstr = '';
    if (!empty($SESSION->restore->importing)) {
        define('RESTORE_SILENTLY',true);
    }
    $status = restore_precheck($id,$file,$errorstr);
 
    if (!$status) {
        error("An error occured");
    }

?>
