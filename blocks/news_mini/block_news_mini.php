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
 * Block nhắc nhở các sự kiện cho giáo viên
 *
 * @package    block_user(student)
 * @copyright  2019 Le Thanh Vu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_news_mini extends block_base {
    public function init() {
        $this->title = '';
    }

    function get_content() {
        global $CFG, $USER, $DB, $OUTPUT;
       
        if ($this->content !== NULL) {
            return $this->content;
        }
      
        $renderable = new \block_news_mini\output\short_news_page();
        $renderer = $this->page->get_renderer('block_news_mini');
        $this->content = new stdClass();
        $this->content->text = $renderer->render($renderable);
        $this->content->footer = '';
           
        return $this->content;
       
    }
    public function applicable_formats() {
        return array('all' => true);
    }
}


