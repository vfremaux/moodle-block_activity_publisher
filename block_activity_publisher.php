<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

class block_activity_publisher extends block_base {

    function init() {
        $this->title = get_string('activity_publisher', 'block_activity_publisher');
    }

    function applicable_formats() {
        return array('all' => false, 'course-view' => true, 'my' => false);
    }

    function instance_allow_multiple() {
        return false;
    }

    function get_content() {
        global $CFG, $USER, $COURSE;

        if ($COURSE->id <= SITEID) {
            $this->content->text = get_string('errorbadcontext', 'block_activity_publisher');
            return $this->content;
        }

        $blockcontext = context_block::instance($this->instance->id);

        if (has_capability('block/activity_publisher:publish', $blockcontext)) {

            require_once($CFG->dirroot.'/blocks/activity_publisher/lib/activity_publisher.class.php');

            if ($this->content !== NULL) {
                return $this->content;
            }

            $select = activity_publisher::load_course_activities_select($COURSE->id);
            $form = '';
            $formurl = new moodle_url('/blocks/activity_publisher/summary.php');
            $form .= '<form name="exportactivitiesform" method="post" action="'.$formurl.'">';
            $form .= $select;
            $form .= '<input type="hidden" name="course" value="'.$COURSE->id.'" />';
            $form .= '<input type="hidden" name="what" value="" />';
            $form .= '<input type="hidden" name="contextid" value="'.$this->context->id.'" />';
            $form .= '<input type="hidden" name="bid" value="'.$this->instance->id.'" />';

            $mods =  activity_publisher::get_course_mods($COURSE->id);

            if ($mods) {
                $exportstr = get_string('export', 'block_activity_publisher');
                $form .= ' <input type="button" value="'.$exportstr.'" onclick="document.exportactivitiesform.what.value=\'publish\';document.exportactivitiesform.submit();" />';
                if (file_exists($CFG->dirroot.'/mod/sharedresource/lib.php')) {
                    $sharestr = get_string('share', 'block_activity_publisher');
                    /*$form .= ' <input type="button" value="'.$sharestr.'" onclick="document.exportactivitiesform.what.value=\'share\';document.exportactivitiesform.submit();" />';*/
                }
            }

            $formurl = new moodle_url('/blocks/activity_publisher/isummary.php');
            $form .= '</form>';
            $form .= '<form method="post" action="'.$formurl.'">';
            $form .= '<input type="hidden" name="course" value="'.$COURSE->id.'" />';
            $form .= '<input type="hidden" name="contextid" value="'.$this->context->id.'" />';
            $form .= '</form>';
            $repourl = new moodle_url('/blocks/activity_publisher/repo.php', array('contextid' => $this->context->id));
            $form .= '<a href="'.$repourl.'"><b>'.get_string('activityrepository', 'block_activity_publisher').'</b></a>';
            $this->content = new stdClass;
            $this->content->text = $form;
            $this->content->footer = '';
        } else {
            $this->content = new StdClass;
            $this->content->text = '';
            $this->content->footer = '';
            return $this->content;
        }

        return $this->content;
    }
}
