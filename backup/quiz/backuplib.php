<?php // $Id: backuplib.php,v 1.62.2.6 2008/02/05 10:16:40 jamiesensei Exp $
    //This php script contains all the stuff to backup quizzes

//This is the "graphical" structure of the quiz mod:
    //To see, put your terminal to 160cc

    //
    //                           quiz
    //                        (CL,pk->id)
    //                            |
    //           -------------------------------------------------------------------
    //           |               |                |                |               |
    //           |          quiz_grades           |     quiz_question_versions     |
    //           |      (UL,pk->id,fk->quiz)      |      (CL,pk->id,fk->quiz)      |
    //           |                                |                                |
    //      quiz_attempts             quiz_question_instances                quiz_feedback
    //  (UL,pk->id,fk->quiz)       (CL,pk->id,fk->quiz,question)         (CL,pk->id,fk->quiz)
    //
    // Meaning: pk->primary key field of the table
    //          fk->foreign key to link with parent
    //          nt->nested field (recursive data)
    //          SL->site level info
    //          CL->course level info
    //          UL->user level info
    //          files->table may have files
    //
    //-----------------------------------------------------------
    
    require_once $CFG->dirroot.'/blocks/activity_publisher/backup/question/backuplib.php';

   ////Return an array of info (name,value)
/// $instances is an array with key = instanceid, value = object (name,id,userdata)
   function ap_quiz_check_backup_mods($course,$user_data= false,$backup_unique_code,$instances=null) {
        //this function selects all the questions / categories to be backed up.
        ap_quiz_insert_category_and_question_ids($course, $backup_unique_code, $instances);
        if ($course != SITEID){
            ap_question_insert_course_file_names($course, $backup_unique_code);
			ap_question_insert_site_file_names($backup_unique_code);
        }
        if (!empty($instances) && is_array($instances) && count($instances)) {
            $info = array();
            foreach ($instances as $id => $instance) {
                $info += quiz_check_backup_mods_instances($instance,$backup_unique_code);
            }
            return $info;
        }
        
        // this was for interactive session report
        /*
        //First the course data
        $info[0][0] = get_string("modulenameplural","quiz");
        if ($ids = quiz_ids ($course)) {
            $info[0][1] = count($ids);
        } else {
            $info[0][1] = 0;
        }
        //Categories
        $info[1][0] = get_string("categories","quiz");
        if ($ids = question_category_ids_by_backup ($backup_unique_code)) {
            $info[1][1] = count($ids);
        } else {
            $info[1][1] = 0;
        }
        //Questions
        $info[2][0] = get_string("questions","quiz");
        if ($ids = question_ids_by_backup($backup_unique_code)) {
            $info[2][1] = count($ids);
        } else {
            $info[2][1] = 0;
        }

        //Now, if requested, the user_data
        if ($user_data) {
            //Grades
            $info[3][0] = get_string("grades");
            if ($ids = quiz_grade_ids_by_course ($course)) {
                $info[3][1] = count($ids);
            } else {
                $info[3][1] = 0;
            }
        }
        */

        return $info;
    }

    /*
     * Insert necessary category ids to backup_ids table. Called during backup_check.html
     * restrict questions to : 
     * explicitely used questions
     * potential indirectly choosed questions using randomizers
     * This backs up ids for quiz module. It backs up :
     *     all categories and questions in course
     *     all categories and questions in contexts of quiz module instances which have been selected for backup
     *     all categories and questions in contexts above course level that are used by quizzes that have been selected for backup
     */
    function ap_quiz_insert_category_and_question_ids($course, $backup_unique_code, $instances = null) {
        global $CFG;
        $status = true;
        
        // Create missing categories and reassign orphaned questions.
        quiz_fix_orphaned_questions($course);
        
		// get used questions        
		$usedquestioninstances = array();
		foreach($instances as $qi){
			if ($qis = get_records_menu('quiz_question_instances', 'quiz', $qi->id, 'id', 'id,question')){
				$usedquestioninstances += $qis;
			}
		}
		
        // Finally, add all questions and collect categories.
        $backupcategories = array();
        foreach ($usedquestioninstances as $questioninstanceid => $questionid) {
        	$question = get_record('question', 'id', $questionid);
            $status = $status && backup_putid($backup_unique_code, 'question', $question->id, 0);
            $backupcategories[$question->category] = true;
            
            if ($question->qtype == 'random' || $question->qtype == 'randomconstrained'){
            	$qtype = $question->qtype;
            	$qtypeclass = $qtype.'_qtype';
            	require_once($CFG->dirroot."/question/type/{$qtype}/questiontype.php");
            	$qobj = new $qtypeclass();
            	$randomquestions = $qobj->get_usable_questions_from_category($question->category, $question->questiontext == "1", null);
            	// get all potentially selectable questions
            	if (!empty($randomquestions)){
	            	foreach($randomquestions as $rq){
	            		$status = $status && backup_putid($backup_unique_code, 'question', $rq->id, 0);
	            		$backupcategories[$rq->category] = true;
	            	}
	            }
            }
        }

        // Finally, add all these extra categories to the backup_ids table.
        $categories = array_keys($backupcategories);
        $backupdone = array();
        if (!empty($categories)){
	        foreach ($categories as $categoryid) {
	            $status = $status && backup_putid($backup_unique_code, 'question_categories', $categoryid, 0);
	            $backupdone[$categoryid] = true;
            	$category = get_record('question_categories', 'id', $categoryid, '', '', '', '', 'id,parent');
	            while($category->parent != 0){
	            	$category = get_record('question_categories', 'id', $category->parent);
	            	if (!array_key_exists($category->id, $backupdone)){
		            	$status = $status && backup_putid($backup_unique_code, 'question_categories', $category->id, 0);
		            	$backupdone[$category->id] = true;
		            } else {
		            	break;
		            }
	            }
	        }
	    }
        return $status;
    }
    
?>
