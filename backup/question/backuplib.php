<?php // $Id: backuplib.php,v 1.3 2012-07-17 20:35:30 vf Exp $
/**
 * Question bank backup code.
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questionbank
 *//** */

//This is the "graphical" structure of the question database:
    //To see, put your terminal to 160cc

    // The following holds student-independent information about the questions
    //
    //          question_categories
    //             (CL,pk->id)
    //                  |
    //                  |
    //                  |.......................................
    //                  |                                      .
    //                  |                                      .
    //                  |    -------question_datasets------    .
    //                  |    |  (CL,pk->id,fk->question,  |    .
    //                  |    |   fk->dataset_definition)  |    .
    //                  |    |                            |    .
    //                  |    |                            |    .
    //                  |    |                            |    .
    //                  |    |                    question_dataset_definitions
    //                  |    |                      (CL,pk->id,fk->category)
    //              question                                   |
    //        (CL,pk->id,fk->category,files)                   |
    //                  |                             question_dataset_items
    //                  |                          (CL,pk->id,fk->definition)
    //                  |
    //                  |
    //                  |
    //             --------------------------------------------------------------------------------------------------------------
    //             |             |              |              |                       |                  |                     |
    //             |             |              |              |                       |                  |                     |
    //             |             |              |              |             question_calculated          |                     |
    //      question_truefalse   |     question_multichoice    |          (CL,pl->id,fk->question)        |                     |
    // (CL,pk->id,fk->question)  |   (CL,pk->id,fk->question)  |                       .                  |                     |  question_randomsamatch
    //             .             |              .              |                       .                  |                     |--(CL,pk->id,fk->question)
    //             .    question_shortanswer    .      question_numerical              .         question_multianswer.          |
    //             .  (CL,pk->id,fk->question)  .  (CL,pk->id,fk->question)            .        (CL,pk->id,fk->question)        |
    //             .             .              .              .                       .                  .                     |       question_match
    //             .             .              .              .                       .                  .                     |--(CL,pk->id,fk->question)
    //             .             .              .              .                       .                  .                     |             .
    //             .             .              .              .                       .                  .                     |             .
    //             .             .              .              .                       .                  .                     |             .
    //             .             .              .              .                       .                  .                     |    question_match_sub
    //             ........................................................................................                     |--(CL,pk->id,fk->question)
    //                                                   .                                                                      |
    //                                                   .                                                                      |
    //                                                   .                                                                      |  question_numerical_units
    //                                             question_answers                                                             |--(CL,pk->id,fk->question)
    //                                         (CL,pk->id,fk->question)----------------------------------------------------------
    //
    //
    // The following holds the information about student interaction with the questions
    //
    //             question_sessions
    //      (UL,pk->id,fk->attempt,question)
    //                    .
    //                    .
    //             question_states
    //       (UL,pk->id,fk->attempt,question)
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

    require_once("$CFG->libdir/questionlib.php");
    require_once($CFG->dirroot."/blocks/activity_publisher/backup/question/questiontype_filemap.php");
      
      
    function ap_backup_question_category_context($bf, $contextid, $course) {
        $status = true;
        $context = get_context_instance_by_id($contextid);
        $status = $status && fwrite($bf,start_tag("CONTEXT",4,true));
        switch ($context->contextlevel){
            case CONTEXT_MODULE:
                $status = $status && fwrite($bf,full_tag("LEVEL",5,false, 'module'));
                $status = $status && fwrite($bf,full_tag("INSTANCE",5,false, $context->instanceid));
                break;
            case CONTEXT_COURSE:
                $status = $status && fwrite($bf,full_tag("LEVEL",5,false, 'course'));
                break;
            case CONTEXT_COURSECAT:
                $thiscourse = get_record('course', 'id', $course);
                $cat = $thiscourse->category;
                $catno = 1;
                while($context->instanceid != $cat){
                    $catno ++;
                    if ($cat ==0) {
                        return false;
                    }
                    $cat = get_field('course_categories', 'parent', 'id', $cat);
                }
                $status = $status && fwrite($bf,full_tag("LEVEL",5,false, 'coursecategory'));
                $status = $status && fwrite($bf,full_tag("COURSECATEGORYLEVEL",5,false, $catno));
               break;
            case CONTEXT_SYSTEM:
                $status = $status && fwrite($bf,full_tag("LEVEL",5,false, 'system'));
                break;
            default :
                return false;
        }
        $status = $status && fwrite($bf,end_tag("CONTEXT",4,true));
        return $status;
    }

    function ap_backup_question_categories($bf,$preferences,$question_cats_ids_arr, $questions_ids_arr) {

        global $CFG;

        $status = true;
            
        //First, we get the used categories from backup_ids
        $categories = ap_question_category_ids_by_backup ($preferences->backup_unique_code);

        
        //If we've categories
        if ($categories) {
             //Write start tag
             $status = $status && fwrite($bf,start_tag("QUESTION_CATEGORIES",2,true));
             //Iterate over each category
            foreach ($categories as $cat) {
             
                //check the category used by the current quiz instance .
                if (!in_array($cat->old_id,$question_cats_ids_arr) )
                {
                    continue;
                }
                
                //Start category
                $status = $status && fwrite ($bf,start_tag("QUESTION_CATEGORY",3,true));
                //Get category data from question_categories
                $category = get_record ("question_categories","id",$cat->old_id);
                //Print category contents
                $status = $status && fwrite($bf,full_tag("ID",4,false,$category->id));
                $status = $status && fwrite($bf,full_tag("NAME",4,false,$category->name));
                $status = $status && fwrite($bf,full_tag("INFO",4,false,$category->info));
                $status = $status && ap_backup_question_category_context($bf, $category->contextid, $preferences->backup_course);
                $status = $status && fwrite($bf,full_tag("STAMP",4,false,$category->stamp));
                $status = $status && fwrite($bf,full_tag("PARENT",4,false,$category->parent));
                $status = $status && fwrite($bf,full_tag("SORTORDER",4,false,$category->sortorder));
                //Now, backup their questions
              
                $status = $status && ap_backup_question($bf,$preferences,$category->id,4,$questions_ids_arr);
                //End category
                $status = $status && fwrite ($bf,end_tag("QUESTION_CATEGORY",3,true));
            }
            //Write end tag
            $status = $status && fwrite ($bf,end_tag("QUESTION_CATEGORIES",2,true));
        }

        return $status;
    }

    //This function backups all the questions in selected category and their
    //asociated data
    function ap_backup_question($bf,$preferences,$category, $level = 4,$questions_ids_arr) {

        global $CFG, $QTYPES;

        $status = true;

        // We'll fetch the questions sorted by parent so that questions with no parents
        // (these are the ones which could be parents themselves) are backed up first. This
        // is important for the recoding of the parent field during the restore process
        // Only select questions with ids in backup_ids table
        $questions = get_records_sql("SELECT q.* FROM {$CFG->prefix}backup_ids bk, {$CFG->prefix}question q ".
                                    "WHERE q.category= $category AND ".
                                    "bk.old_id=q.id AND ".
                                    "bk.backup_code = {$preferences->backup_unique_code} ".
                                    "ORDER BY parent ASC, q.id");
        //If there are questions
        if ($questions) {
            //Write start tag
            $status = $status && fwrite ($bf,start_tag("QUESTIONS",$level,true));
            $counter = 0;
            //Iterate over each question
            foreach ($questions as $question) {
                
                //check question used by the current quiz instance .
                if(!in_array($question->id,$questions_ids_arr))
                {
                    continue;
                }
                
                // Deal with missing question types - they need to be included becuase
                // user data or quizzes may refer to them.
                if (!array_key_exists($question->qtype, $QTYPES)) {
                    $question->qtype = 'missingtype';
                    $question->questiontext = '<p>' . get_string('warningmissingtype', 'quiz') . '</p>' . $question->questiontext;
                }
                //Start question
                $status = $status && fwrite ($bf,start_tag("QUESTION",$level + 1,true));
                //Print question contents
                fwrite ($bf,full_tag("ID",$level + 2,false,$question->id));
                fwrite ($bf,full_tag("PARENT",$level + 2,false,$question->parent));
                fwrite ($bf,full_tag("NAME",$level + 2,false,$question->name));
                fwrite ($bf,full_tag("QUESTIONTEXT",$level + 2,false,$question->questiontext));
                fwrite ($bf,full_tag("QUESTIONTEXTFORMAT",$level + 2,false,$question->questiontextformat));
                fwrite ($bf,full_tag("IMAGE",$level + 2,false,$question->image));
                fwrite ($bf,full_tag("GENERALFEEDBACK",$level + 2,false,$question->generalfeedback));
                fwrite ($bf,full_tag("DEFAULTGRADE",$level + 2,false,$question->defaultgrade));
                fwrite ($bf,full_tag("PENALTY",$level + 2,false,$question->penalty));
                fwrite ($bf,full_tag("QTYPE",$level + 2,false,$question->qtype));
                fwrite ($bf,full_tag("LENGTH",$level + 2,false,$question->length));
                fwrite ($bf,full_tag("STAMP",$level + 2,false,$question->stamp));
                fwrite ($bf,full_tag("VERSION",$level + 2,false,$question->version));
                fwrite ($bf,full_tag("HIDDEN",$level + 2,false,$question->hidden));
                fwrite ($bf,full_tag("TIMECREATED",$level + 2,false,$question->timecreated));
                fwrite ($bf,full_tag("TIMEMODIFIED",$level + 2,false,$question->timemodified));
                fwrite ($bf,full_tag("CREATEDBY",$level + 2,false,$question->createdby));
                fwrite ($bf,full_tag("MODIFIEDBY",$level + 2,false,$question->modifiedby));
                // Backup question type specific data
                $status = $status && $QTYPES[$question->qtype]->backup($bf,$preferences,$question->id, $level + 2);
                
//                DebugBreak(); 
                //Backup question type specific files 
                $status = $status && ap_backup_questiontype_files($question,$preferences); 
                
                //End question
                $status = $status && fwrite ($bf,end_tag("QUESTION",$level + 1,true));
                //Do some output
                $counter++;
                if ($counter % 10 == 0) {
                    echo ".";
                    if ($counter % 200 == 0) {
                        echo "<br />";
                    }
                    backup_flush(300);
                }
            }
            //Write end tag
            $status = $status && fwrite ($bf,end_tag("QUESTIONS",$level,true));
        }
        return $status;
    }

    function ap_backup_questiontype_files ($question,$preferences)
    {  global $qt_filemap,$CFG;
       //table name .
      

       $qtype=  $question->qtype;
       $status= true;
       
       //just an  exception for the question type description . 
       if($qtype == 'description')
       {
       $table_name = 'question';    
       }
       else
       {
       $table_name= 'question_'.$qtype;
       }
       
       $rootdir = $CFG->dataroot."/".$preferences->backup_course;       
        
       if( !empty($qt_filemap[$table_name]) && count($qt_filemap[$table_name]) >0 )
       {
         foreach($qt_filemap[$table_name] as $field=>$value)
         {
             if($table_name == "question")
             {
                 $qid= "id";
             }
             else
             {
                 $qid = $field;
             }
             
             $fvalue = get_field($table_name,$field,$qid,$question->id); 
            
            if($qt_filemap[$table_name][$field] == "embedded")
            {
               backup_copy_question_files_from_content($fvalue,$preferences);// scan content for any files  
            }
            else
            {
            
             //field is a filepath
        
             if(file_exists($rootdir."/".$fvalue) && $fvalue!="" && $fvalue !=null)
             {
                    //if the file in any inner folders , just trim it and get the file name 
                
                    $path = explode('/',$fvalue);
                    $filename = $path[count($path)-1];
                    $status = backup_copy_file($rootdir."/".$fvalue,
                                       $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/questions_files/".$filename);
             }else if(file_exists($CFG->dataroot."/".SITEID."/".$fvalue) && $fvalue!="" && $fvalue !=null)
             {
                  //looks like its not in the course , we try in the site files.
                  $status = backup_copy_file($CFG->dataroot."/".SITEID."/".$fvalue,
                                       $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/questions_files/".$fvalue);
          
             }
            }
            
         }  
       }
       
       return $status;
        
    }
    
      
    function backup_copy_question_files_from_content ($content , $preferences) {

        global $CFG;

        $status = true;
 
        $status = check_and_create_questions_files_dir($preferences->backup_unique_code);
      
        //copied to backup
        $files_to_export = array();
        $rootdir = $CFG->dataroot;
   
                     
        $files_to_export = list_question_files($content,$preferences);    
        
        $name_moddata = $CFG->moddata;
        $name_backupdata = "backupdata";
        //Check if directory exists
      
        if (is_dir($rootdir)) {
            $list = list_directories_and_files ($rootdir);
            
            //get files included in questions text
        
            
                                          
            if ($files_to_export) {
                //Iterate
                foreach ($files_to_export as $dir) {

                    if($dir== null)
                    {
                        continue ;
                    }
                     if ( $dir !== $name_backupdata && $dir !== $name_moddata)
                     {             
                            if (file_exists($rootdir."/".$dir)) {
                               
                                //get just the filename
                                $path_chunks = split("/",$dir);
                                $filename = $path_chunks[sizeof($path_chunks)-1];
                                
                                $status = backup_copy_file($rootdir."/".$dir,
                                               $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/questions_files/".$filename);
                            }
                            
                     }
                }
            }
        }
        
        //checking question files from site
        $rootdir = $CFG->dataroot."/".SITEID;

        $files_to_export = list_question_files($content,$preferences);    
        
        $dirs = list_directories_and_files ($rootdir);
 
        if ($dirs) {
            //Iterate
            foreach ($dirs as $dir) {
                
            if($dir== null)
             {
               continue ;
             }
                
                //check for dir structure and create recursively
            if ( $dir !== $name_backupdata && $dir !== $name_moddata)
             {
                if(in_array($dir,$files_to_export))
                {        
                $status = $status && backup_copy_file($rootdir."/".$dir,
                                   $CFG->dataroot."/temp/backup/".$preferences->backup_unique_code."/questions_files/".$dir);
                }
             }
            }
        }
       
        return $status;
    }
    
    
    /**
    * this function returns backuped mods 
    * related files as an array
    * @param mixed $preferences
    * @return array mods files 
    */
    function list_question_files ($content , $preferences){ 
    	global $CFG;
   
      	$matches = array();
      	static $unicoderegexp;
      	if (!isset($unicoderegexp)) {
            $unicoderegexp = @preg_match('/\pL/u', 'a'); // This will fail silenty, returning false,
      	} 
          
      	//check if there are any quizes . 
      	//we handle the quiz files.
     
      	//First, we check for every call to file.php inside the question text
		$search = array($CFG->wwwroot.'/file.php/', $CFG->wwwroot.'/file.php?file=/', $CFG->wwwroot.'/file.php?file=%2f', $CFG->wwwroot.'/file.php?file=%2F');
                
        $replace = array('$@FILE@$/', '$@FILE@$/', '$@FILE@$/', '$@FILE@$/','$@FILE@$/', '$@FILE@$/', '$@FILE@$/', '$@FILE@$/');

        $content = str_replace($search,$replace,$content);
             
		// Now we look for any '$@FILE@$' URLs, replacing:
        //     - %2F by /
        //     - &forcedownload=1 &amp;forcedownload=1 and ?forcedownload=1 by "" (nothing)                     
		//                        
        // Based in $unicoderegexp, decide the regular expression to use
		if ($unicoderegexp) { //We can use unicode modifiers
            $search = '/(\$@FILE@\$)
            (
            (?:(?:\/|%2f|%2F))
            (?:(?:\([-;:@#&=\pL0-9\$~_.+!*\',]*?\))|[-;:@#&=\pL0-9\$~_.+!*\',]|%[a-fA-F0-9]{2}|\/)*
            )?
            (\?(?:(?:(?:\([-;:@#&=\pL0-9\$~_.+!*\',]*?\))|[-;:@#&=?\pL0-9\$~_.+!*\',]|%[a-fA-F0-9]{2}|\/)*))
            ?
            (?<![,.;])/u';
        } else { //We cannot use unicode modifiers
            $search = '/(\$@FILE@\$)((?:(?:\/|%2f|%2F))(?:(?:\([-;:@#&=a-zA-Z0-9\$~_.+!*\',]*?\))|[-;:@#&=a-zA-Z0-9\$~_.+!*\',]|%[a-fA-F0-9]{2}|\/)*)?(\?(?:(?:(?:\([-;:@#&=a-zA-Z0-9\$~_.+!*\',]*?\))|[-;:@#&=?a-zA-Z0-9\$~_.+!*\',]|%[a-fA-F0-9]{2}|\/)*))?(?<![,.;])/';
        }
                
		$search = '/(\$@FILE@\$)((?:(?:\/|%2f|%2F))(?:(?:\([-;:@#&=a-zA-Z0-9\$~_.+!*\',]*?\))|[-;:@#&=a-zA-Z0-9\$~_.+!*\',]|%[a-fA-F0-9]{2}|\/)*)?(\?(?:(?:(?:\([-;:@#&=a-zA-Z0-9\$~_.+!*\',]*?\))|[-;:@#&=?a-zA-Z0-9\$~_.+!*\',]|%[a-fA-F0-9]{2}|\/)*))?(?<![,.;])/';   
                     
        $result = preg_match_all($search, $content, $matches);
        
        $file_and_dir = array();
        $files_arr = array();
        if(!empty($matches[2]) ){
            foreach($matches[2] as $m){
        		$files_arr[] = ltrim($m,"/");                    
            }
         	// $file_and_dir = explode("/",$file_url);
        }
		return $files_arr;           
	}
    
    function backup_process_filephp_inquestion_uses($result){     
        return '';
    }
      
    function check_and_create_questions_files_dir($backup_unique_code) {
        global $CFG;

        $status = check_dir_exists($CFG->dataroot."/temp/backup/".$backup_unique_code."/questions_files",true);

        return $status;
    }
  
      
    //This function backups the answers data in some question types
    //(truefalse, shortanswer,multichoice,numerical,calculated)
    function ap_question_backup_answers($bf,$preferences,$question, $level = 6) {
        global $CFG;

        $status = true;

        $answers = get_records("question_answers","question",$question,"id");
        //If there are answers
        if ($answers) {
            $status = $status && fwrite ($bf,start_tag("ANSWERS",$level,true));
            //Iterate over each answer
            foreach ($answers as $answer) {
                $status = $status && fwrite ($bf,start_tag("ANSWER",$level + 1,true));
                //Print answer contents
                fwrite ($bf,full_tag("ID",$level + 2,false,$answer->id));
                fwrite ($bf,full_tag("ANSWER_TEXT",$level + 2,false,$answer->answer));
                fwrite ($bf,full_tag("FRACTION",$level + 2,false,$answer->fraction));
                fwrite ($bf,full_tag("FEEDBACK",$level + 2,false,$answer->feedback));
                $status = $status && fwrite ($bf,end_tag("ANSWER",$level + 1,true));
            }
            $status = $status && fwrite ($bf,end_tag("ANSWERS",$level,true));
        }
        return $status;
    }

    //This function backups question_numerical_units from different question types
    function ap_question_backup_numerical_units($bf,$preferences,$question,$level=7) {

        global $CFG;

        $status = true;

        $numerical_units = get_records("question_numerical_units","question",$question,"id");
        //If there are numericals_units
        if ($numerical_units) {
            $status = $status && fwrite ($bf,start_tag("NUMERICAL_UNITS",$level,true));
            //Iterate over each numerical_unit
            foreach ($numerical_units as $numerical_unit) {
                $status = $status && fwrite ($bf,start_tag("NUMERICAL_UNIT",$level+1,true));
                //Print numerical_unit contents
                fwrite ($bf,full_tag("MULTIPLIER",$level+2,false,$numerical_unit->multiplier));
                fwrite ($bf,full_tag("UNIT",$level+2,false,$numerical_unit->unit));
                //Now backup numerical_units
                $status = $status && fwrite ($bf,end_tag("NUMERICAL_UNIT",$level+1,true));
            }
            $status = $status && fwrite ($bf,end_tag("NUMERICAL_UNITS",$level,true));
        }

        return $status;

    }

    //This function backups dataset_definitions (via question_datasets) from different question types
    function ap_question_backup_datasets($bf,$preferences,$question,$level=7) {

        global $CFG;

        $status = true;

        //First, we get the used datasets for this question
        $question_datasets = get_records("question_datasets","question",$question,"id");
        //If there are question_datasets
        if ($question_datasets) {
            $status = $status &&fwrite ($bf,start_tag("DATASET_DEFINITIONS",$level,true));
            //Iterate over each question_dataset
            foreach ($question_datasets as $question_dataset) {
                $def = NULL;
                //Get dataset_definition
                if ($def = get_record("question_dataset_definitions","id",$question_dataset->datasetdefinition)) {;
                    $status = $status &&fwrite ($bf,start_tag("DATASET_DEFINITION",$level+1,true));
                    //Print question_dataset contents
                    fwrite ($bf,full_tag("CATEGORY",$level+2,false,$def->category));
                    fwrite ($bf,full_tag("NAME",$level+2,false,$def->name));
                    fwrite ($bf,full_tag("TYPE",$level+2,false,$def->type));
                    fwrite ($bf,full_tag("OPTIONS",$level+2,false,$def->options));
                    fwrite ($bf,full_tag("ITEMCOUNT",$level+2,false,$def->itemcount));
                    //Now backup dataset_entries
                    $status = $status && ap_question_backup_dataset_items($bf,$preferences,$def->id,$level+2);
                    //End dataset definition
                    $status = $status &&fwrite ($bf,end_tag("DATASET_DEFINITION",$level+1,true));
                }
            }
            $status = $status &&fwrite ($bf,end_tag("DATASET_DEFINITIONS",$level,true));
        }

        return $status;

    }

    //This function backups datases_items from dataset_definitions
    function ap_question_backup_dataset_items($bf,$preferences,$datasetdefinition,$level=9) {

        global $CFG;

        $status = true;

        //First, we get the datasets_items for this dataset_definition
        $dataset_items = get_records("question_dataset_items","definition",$datasetdefinition,"id");
        //If there are dataset_items
        if ($dataset_items) {
            $status = $status &&fwrite ($bf,start_tag("DATASET_ITEMS",$level,true));
            //Iterate over each dataset_item
            foreach ($dataset_items as $dataset_item) {
                $status = $status &&fwrite ($bf,start_tag("DATASET_ITEM",$level+1,true));
                //Print question_dataset contents
                fwrite ($bf,full_tag("NUMBER",$level+2,false,$dataset_item->itemnumber));
                fwrite ($bf,full_tag("VALUE",$level+2,false,$dataset_item->value));
                //End dataset definition
                $status = $status &&fwrite ($bf,end_tag("DATASET_ITEM",$level+1,true));
            }
            $status = $status &&fwrite ($bf,end_tag("DATASET_ITEMS",$level,true));
        }

        return $status;

    }


    //Backup question_states contents (executed from backup_quiz_attempts)
    function ap_backup_question_states ($bf,$preferences,$attempt, $level = 6) {

        global $CFG;

        $status = true;

        $question_states = get_records("question_states","attempt",$attempt,"id");
        //If there are states
        if ($question_states) {
            //Write start tag
            $status = $status && fwrite ($bf,start_tag("STATES",$level,true));
            //Iterate over each state
            foreach ($question_states as $state) {
                //Start state
                $status = $status && fwrite ($bf,start_tag("STATE",$level + 1,true));
                //Print state contents
                fwrite ($bf,full_tag("ID",$level + 2,false,$state->id));
                fwrite ($bf,full_tag("QUESTION",$level + 2,false,$state->question));
                fwrite ($bf,full_tag("ORIGINALQUESTION",$level + 2,false,$state->originalquestion));
                fwrite ($bf,full_tag("SEQ_NUMBER",$level + 2,false,$state->seq_number));
                fwrite ($bf,full_tag("ANSWER",$level + 2,false,$state->answer));
                fwrite ($bf,full_tag("TIMESTAMP",$level + 2,false,$state->timestamp));
                fwrite ($bf,full_tag("EVENT",$level + 2,false,$state->event));
                fwrite ($bf,full_tag("GRADE",$level + 2,false,$state->grade));
                fwrite ($bf,full_tag("RAW_GRADE",$level + 2,false,$state->raw_grade));
                fwrite ($bf,full_tag("PENALTY",$level + 2,false,$state->penalty));
                //End state
                $status = $status && fwrite ($bf,end_tag("STATE",$level + 1,true));
            }
            //Write end tag
            $status = $status && fwrite ($bf,end_tag("STATES",$level,true));
        }
    }

    //Backup question_sessions contents (executed from backup_quiz_attempts)
    function ap_backup_question_sessions ($bf,$preferences,$attempt, $level = 6) {
        global $CFG;

        $status = true;

        $question_sessions = get_records("question_sessions","attemptid",$attempt,"id");
        //If there are sessions
        if ($question_sessions) {
            //Write start tag (the funny name 'newest states' has historical reasons)
            $status = $status && fwrite ($bf,start_tag("NEWEST_STATES",$level,true));
            //Iterate over each newest_state
            foreach ($question_sessions as $newest_state) {
                //Start newest_state
                $status = $status && fwrite ($bf,start_tag("NEWEST_STATE",$level + 1,true));
                //Print newest_state contents
                fwrite ($bf,full_tag("ID",$level + 2,false,$newest_state->id));
                fwrite ($bf,full_tag("QUESTIONID",$level + 2,false,$newest_state->questionid));
                fwrite ($bf,full_tag("NEWEST",$level + 2,false,$newest_state->newest));
                fwrite ($bf,full_tag("NEWGRADED",$level + 2,false,$newest_state->newgraded));
                fwrite ($bf,full_tag("SUMPENALTY",$level + 2,false,$newest_state->sumpenalty));
                fwrite ($bf,full_tag("MANUALCOMMENT",$level + 2,false,$newest_state->manualcomment));
                //End newest_state
                $status = $status && fwrite ($bf,end_tag("NEWEST_STATE",$level + 1,true));
            }
            //Write end tag
            $status = $status && fwrite ($bf,end_tag("NEWEST_STATES",$level,true));
        }
        return $status;
    }

    //Returns an array of categories id
    function ap_question_category_ids_by_backup ($backup_unique_code) {

        global $CFG;

        return get_records_sql ("SELECT a.old_id, a.backup_code
                                 FROM {$CFG->prefix}backup_ids a
                                 WHERE a.backup_code = '$backup_unique_code' AND
                                       a.table_name = 'question_categories'");
    }

    function ap_question_ids_by_backup ($backup_unique_code) {

        global $CFG;

        return get_records_sql ("SELECT old_id, backup_code
                                 FROM {$CFG->prefix}backup_ids
                                 WHERE backup_code = '$backup_unique_code' AND
                                       table_name = 'question'");
    }
    
    //Function for inserting question and category ids into db that are all called from
    // quiz_check_backup_mods during execution of backup_check.html 


    function ap_question_insert_c_and_q_ids_for_course($coursecontext, $backup_unique_code){
        global $CFG;
        // First, all categories from this course's context.
        $status = execute_sql("INSERT INTO {$CFG->prefix}backup_ids
                                   (backup_code, table_name, old_id, info)
                               SELECT '$backup_unique_code', 'question_categories', qc.id, 'course'
                               FROM {$CFG->prefix}question_categories qc
                               WHERE qc.contextid = {$coursecontext->id}", false);
        $status = $status && ap_question_insert_q_ids($backup_unique_code, 'course');
        return $status;
    }
    /*
     * Insert all question ids for categories whose ids have already been inserted in the backup_ids table
     * Insert code to identify categories to later insert all question ids later eg. course, quiz or other module name.
     */
    function ap_question_insert_q_ids($backup_unique_code, $info){
        global $CFG;
        //put the ids of the questions from all these categories into the db.
        $status = execute_sql("INSERT INTO {$CFG->prefix}backup_ids
                                       (backup_code, table_name, old_id, info)
                                       SELECT '$backup_unique_code', 'question', q.id, '" . sql_empty() . "'
                                       FROM {$CFG->prefix}question q, {$CFG->prefix}backup_ids bk
                                       WHERE q.category = bk.old_id AND bk.table_name = 'question_categories' 
                                       AND " . sql_compare_text('bk.info') . " = '$info'
                                       AND bk.backup_code = '$backup_unique_code'", false);
        return $status;
    }
    
    function ap_question_insert_c_and_q_ids_for_module($backup_unique_code, $course, $modulename, $instances){
        global $CFG;
        $status = true;
        // using 'dummykeyname' in sql because otherwise get_records_sql_menu returns an error
        // if two key names are the same.
        $cmcontexts = array();
        if(!empty($instances)) {
            $cmcontexts = get_records_sql_menu("SELECT c.id, c.id AS dummykeyname FROM {$CFG->prefix}modules m,
                                                        {$CFG->prefix}course_modules cm,
                                                        {$CFG->prefix}context c
                               WHERE m.name = '$modulename' AND m.id = cm.module AND cm.id = c.instanceid
                                    AND c.contextlevel = ".CONTEXT_MODULE." AND cm.course = $course
                                    AND cm.instance IN (".implode(',',array_keys($instances)).")");
        }
                                    
        if ($cmcontexts){
            $status = $status && execute_sql("INSERT INTO {$CFG->prefix}backup_ids
                                       (backup_code, table_name, old_id, info)
                                   SELECT '$backup_unique_code', 'question_categories', qc.id, '$modulename'
                                   FROM {$CFG->prefix}question_categories qc
                                   WHERE qc.contextid IN (".join(array_keys($cmcontexts), ', ').")", false);
        }
        $status = $status && ap_question_insert_q_ids($backup_unique_code, $modulename);
        return $status;
    }

    function ap_question_insert_site_file_names($course, $backup_unique_code){
        global $QTYPES, $CFG;
        $status = true;
        $questionids = ap_question_ids_by_backup ($backup_unique_code);
        $urls = array();
        if ($questionids){
            foreach ($questionids as $question_bk){
                $question = get_record('question', 'id', $question_bk->old_id);
                $QTYPES[$question->qtype]->get_question_options($question);
                $urls = array_merge_recursive($urls, $QTYPES[$question->qtype]->find_file_links($question, SITEID));
            }
        }
        ksort($urls);
        foreach (array_keys($urls) as $url){
            if (file_exists($CFG->dataroot.'/'.SITEID.'/'.$url)){
                $inserturl = new object();
                $inserturl->backup_code = $backup_unique_code;
                $inserturl->file_type = 'site';
                $url = clean_param($url, PARAM_PATH);
                $inserturl->path = addslashes($url);
                $status = $status && insert_record('backup_files', $inserturl);
            } else {
                notify(get_string('linkedfiledoesntexist', 'question', $url));
            }
        }
        return $status;
    }
?>
