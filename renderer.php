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

require_once($CFG->dirroot.'/blocks/activity_publisher/lib/activity_publisher.class.php');

class block_activity_publisher_renderer extends plugin_renderer_base {

    public function backup_files_viewer(array $options = null) {
        $files = new backup_files_viewer($options);
        return $this->render($files);
    }

    /**
     * Displays a backup files viewer
     *
     * @global stdClass $USER
     * @param backup_files_viewer $tree
     * @return string
     */
    public function render_backup_files_viewer(backup_files_viewer $viewer) {
        global $CFG, $OUTPUT, $COURSE;

        $files = $viewer->files;

        $filenamestr = get_string('filename', 'backup');
        $timestr = get_string('time');
        $sizestr = get_string('size');
        $downloadstr = get_string('download');
        $restorestr = get_string('restore');
        $publishstr = get_string('publish', 'block_activity_publisher');
        $statusstr = get_string('status', 'block_activity_publisher');
        $deletestr = get_string('delete');

        $table = new html_table();
        $table->attributes['class'] = 'backup-files-table generaltable';
        $table->head = array($filenamestr, $timestr, $sizestr, $downloadstr, $restorestr, $publishstr, $statusstr, '');
        $table->width = '100%';
        $table->data = array();

        foreach ($files as $file) {

            if ($file->is_directory()) {
                continue;
            }

            $fileurl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(),
                                                       null, $file->get_filepath(), $file->get_filename(), true);
            $params = array();
            $params['what'] = 'choosebackupfile';
            $params['fileid'] = $file->get_id();
            $params['contextid'] = $viewer->currentcontext->id;
            $params['course'] = $COURSE->id;
            $restoreurl = new moodle_url('/blocks/activity_publisher/restorefile.php', $params);
            $publishurl = new moodle_url('/blocks/activity_publisher/publish.php', $params);

            $params = array();
            $params['contextid'] = $viewer->currentcontext->id;
            $params['fileid'] = $file->get_id();
            $params['what'] = 'delete';
            $repourl = new moodle_url('/blocks/activity_publisher/repo.php', $params);

            if (activity_publisher::is_ref_published($file->get_contenthash())) {
                $title = get_string('published', 'block_activity_publisher');
                $resource_status = $OUTPUT->pix_icon('published', $title, 'block_activity_publisher');
            } else {
                $resource_status = '';
            }

            $table->data[] = array(
                $file->get_filename(),
                userdate($file->get_timemodified()),
                display_size($file->get_filesize()),
                html_writer::link($fileurl, get_string('download')),
                html_writer::link($restoreurl, get_string('restore')),
                html_writer::link($publishurl, get_string('publish','block_activity_publisher')),
                html_writer::label($resource_status, null),
                html_writer::link($repourl, $OUTPUT->pix_icon('t/delete', $deletestr)),
            );
        }

        $html = html_writer::table($table);
        $html .= $this->output->single_button(
            new moodle_url('/blocks/activity_publisher/backupfilesedit.php',
                array('currentcontext' => $viewer->currentcontext->id,
                  'contextid' => $viewer->filecontext->id,
                  'filearea' => $viewer->filearea,
                  'component' => $viewer->component,
                  'returnurl' => $this->page->url->out())), get_string('managefiles', 'backup'), 'post');

        return $html;
    }
}

/**
 * Data structure representing backup files viewer
 *
 * @copyright 2010 Dongsheng Cai
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     Moodle 2.0
 */
class backup_files_viewer implements renderable {
    public $files;
    public $filecontext;
    public $component;
    public $filearea;
    public $currentcontext;

    /**
     * Constructor of backup_files_viewer class
     * @param array $options
     */
    public function __construct(array $options = null) {
        global $CFG, $USER;
        $fs = get_file_storage();
        $this->currentcontext = $options['currentcontext'];
        $this->filecontext = $options['filecontext'];
        $this->component = $options['component'];
        $this->filearea = $options['filearea'];
        $files = $fs->get_area_files($this->filecontext->id, $this->component, $this->filearea, false, 'timecreated');
        $this->files = array_reverse($files);
    }
}
