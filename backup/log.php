<?php  // $Id: log.php,v 1.1 2012-07-10 15:57:42 vf Exp $
       // log.php - old scheduled backups report. Now redirecting
       // to the new admin one

    require_once("../config.php");

    require_login();

    require_capability('moodle/site:backup', context_system::instance());

    redirect("$CFG->wwwroot/$CFG->admin/report/backups/index.php", '', 'admin', 1);

?>
