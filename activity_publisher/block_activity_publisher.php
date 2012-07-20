<?php

class block_activity_publisher extends block_base {

    function init() {
        $this->title = get_string('activity_publisher', 'block_activity_publisher');
        $this->version = 2012071400;
    }

    function applicable_formats() {
        return array('all' => true);
    }

    function specialization() {
        $this->title = isset($this->config->title) ? format_string($this->config->title) : format_string(get_string('newhtmlblock', 'block_activity_publisher'));
    }

    function instance_allow_multiple() {
        return false;
    }

    /**
    *
    */
    function user_can_addto($page) {
        global $CFG, $COURSE;

        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
        if (has_capability('block/activity_publisher:addtocourse', $context)){
        	return true;
        }
        return false;
    }

    function get_content() {
		global $CFG, $USER;
        
        $blockcontext = get_context_instance(CONTEXT_BLOCK, $this->instance->id);

        if (has_capability('block/activity_publisher:publish', $blockcontext)){
        
	        require_once('lib/activity_publisher.class.php');
	        
	        $course_id = optional_param('id');
	        
	        //create the activity publisher object
	        $ap = new activity_publisher();
	        
	        if ($this->content !== NULL) {
	            return $this->content;
	        }
	
	        if (!empty($this->instance->pinned) or $this->instance->pagetype === 'course-view' or $this->instance->pagetype === 'format_page') {
	            // fancy html allowed only on course page and in pinned blocks for security reasons
	            $filteropt = new stdClass;
	            $filteropt->noclean = true;
	        } else {
	            $filteropt = null;
	        }
	        
	        $select = $ap::load_course_activities_select($course_id); 
	        $form = '';
	        $form .= "<form name=\"exportactivitiesform\" method=\"post\" action=\"{$CFG->wwwroot}/blocks/activity_publisher/summary.php\">";
	        $form .= $select;
	        $form .= "<input type=\"hidden\" name=\"course\" value=\"".$course_id."\" />";
	        $form .= '<input type="hidden" name="what" value="" />';
	       	     
	       $mods =  $ap::get_course_mods($course_id);
	       
	       if($mods){
	       		$exportstr = get_string('export', 'block_activity_publisher');
	    		$form .= ' <input type="button" value="'.$exportstr.'" onclick="document.exportactivitiesform.what.value=\'publish\';document.exportactivitiesform.submit();" />';
		    	if (file_exists($CFG->dirroot.'/mod/sharedresource/lib.php')){
	       			$sharestr = get_string('share', 'block_activity_publisher');
		    		$form .= ' <input type="button" value="'.$sharestr.'" onclick="document.exportactivitiesform.what.value=\'share\';document.exportactivitiesform.submit();" />';
		    	}
		   }
	        
	       $form .= '</form>';	       
	       $form .= "<form method=\"post\" action=\"{$CFG->wwwroot}/blocks/activity_publisher/isummary.php\">
	         <input type=\"hidden\" name=\"course\" value=\"".$course_id."\" />
	         <input type=\"submit\" value=\"".get_string('import', 'block_activity_publisher')."\" style=\"margin-top:3px\" />
	        </form>";
	           
	        $this->content = new stdClass;
	        $this->content->text = $form;
	        $this->content->footer = '';
	
	        unset($filteropt); // memory footprint

	    } else {
        	$this->content->text = '';
        	$this->content->footer = '';
        	return $this->content;
		}
		
        return $this->content;
    }

    /**
     * Will be called before an instance of this block is backed up, so that any links in
     * any links in any HTML fields on config can be encoded.
     * @return string
     */
    function get_backup_encoded_config() {
        /// Prevent clone for non configured block instance. Delegate to parent as fallback.
        if (empty($this->config)) {
            return parent::get_backup_encoded_config();
        }
        $data = clone($this->config);
        $data->text = backup_encode_absolute_links($data->text);
        return base64_encode(serialize($data));
    }

    /**
     * This function makes all the necessary calls to {@link restore_decode_content_links_worker()}
     * function in order to decode contents of this block from the backup 
     * format to destination site/course in order to mantain inter-activities 
     * working in the backup/restore process. 
     * 
     * This is called from {@link restore_decode_content_links()} function in the restore process.
     *
     * NOTE: There is no block instance when this method is called.
     *
     * @param object $restore Standard restore object
     * @return boolean
     **/
    function decode_content_links_caller($restore) {
        global $CFG;

        if ($restored_blocks = get_records_select("backup_ids","table_name = 'block_instance' AND backup_code = $restore->backup_unique_code AND new_id > 0", "", "new_id")) {
            $restored_blocks = implode(',', array_keys($restored_blocks));
            $sql = "SELECT bi.*
                      FROM {$CFG->prefix}block_instance bi
                           JOIN {$CFG->prefix}block b ON b.id = bi.blockid
                     WHERE b.name = 'html' AND bi.id IN ($restored_blocks)"; 

            if ($instances = get_records_sql($sql)) {
                foreach ($instances as $instance) {
                    $blockobject = block_instance('html', $instance);
                    $blockobject->config->text = restore_decode_absolute_links($blockobject->config->text);
                    $blockobject->config->text = restore_decode_content_links_worker($blockobject->config->text, $restore);
                    $blockobject->instance_config_commit($blockobject->pinned);
                }
            }
        }

        return true;
    }


}
?>
