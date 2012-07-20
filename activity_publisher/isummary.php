<?php
    /**
    * import summery page, first step page in the activity import process.
    * @author Wafa Adham, Adham Inc.
    * @version 1.0
    */

    require_once('../../config.php');

     
    $course_id = required_param('course',PARAM_INT); 

    if(!$course = get_record('course', 'id', $course_id) ){
        error('invalid course');
    }

    require_login($course_id);

    if (! $site = get_site()) {
        redirect($CFG->wwwroot .'/'. $CFG->admin .'/index.php');
    }

    print_header(strip_tags($site->fullname), $site->fullname, '', '', '',true, '', '');    

    print('<div id="content-cont">');
        
    print_heading("Activity Publisher");
    print('<div id="summary-cont">');
    print('<div id="title">'.get_string('import', 'block_activity_publisher').'</div>');

    print('<form enctype="multipart/form-data" method="post" action="import.php">
    <table id="summary-table" cellpadding="5" cellspacing="5">');

    print('<tr>');
    print('<td class="title">Course Name</td>');
    print('<td>'.$course->fullname.'</td>');
    print('</tr>');

    print('<tr>');
    print('<td class="title">Select Activity :</td>');
    print('<td><input type="file" name="upfile" id="upfile" /></td>');
    print('</tr>');
    print('</table>');


    print('<div id="download-btn">');

    print('<input type="hidden" value="'.$course_id.'" name="cid"/><input type="submit" value="Import" />');

    print('</form></div>');

    print('</div>');


    print('</div>');     
        
    print_footer();    
?>