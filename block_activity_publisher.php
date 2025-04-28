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

/**
 * @package   block_activity_publisher
 * @category  blocks
 * @copyright 2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

class block_activity_publisher extends block_base {

    public function init() {
        $this->title = get_string('activity_publisher', 'block_activity_publisher');
    }

    public function applicable_formats() {
        return array('all' => false, 'course-view' => true, 'my' => false);
    }

    /**
     * Does the block have global config ?
     */
    public function has_config() {
        return true;
    }

    /**
     * Does the block have instance config ?
     */
    public function instance_allow_config() {
        return true;
    }

    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Standard specialization.
     */
    public function specialization() {
        if (!empty($this->config)) {
            if (!empty($this->config->title)) {
                $this->title = format_string($this->config->title);
            }
        }
    }

    /**
     * Main block content
     */
    public function get_content() {
        global $CFG, $USER, $COURSE;

        if ($COURSE->id <= SITEID) {
            $this->content->text = get_string('errorbadcontext', 'block_activity_publisher');
            return $this->content;
        }

        $blockcontext = context_block::instance($this->instance->id);

        if (has_capability('block/activity_publisher:publish', $blockcontext)) {

            require_once($CFG->dirroot.'/blocks/activity_publisher/lib/activity_publisher.class.php');

            if ($this->content !== null) {
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
