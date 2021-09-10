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

namespace block_news_mini\output;

defined('MOODLE_INTERNAL') || die();
define('WEDDING_FORUM', 8);
define('MATRIMONY_FORUM', 9);
define('BURIAL_FORUM', 10);

use renderable;
use templatable;
use renderer_base;
use stdClass;
use user_picture;
use context_module;

class short_news_page implements renderable, templatable {

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB,$USER,$CFG;
       
        $data = array();
        $data['matrimony_section1'] = $this->get_forums_matrimony_data();
        $data['management_matrimony_section1'] = $this->add_news_button(18, 'Thêm tin giao hôn phối');
        $data['wedding_section2'] = $this->get_forums_wedding_data();
        $data['management_wedding_section2'] = $this->add_news_button(17, 'Thêm tin lễ cưới');
        $data['burial_section3'] = $this->get_forums_burial_data();
        $data['management_burial_section3'] = $this->add_news_button(19, 'Thêm tin lễ an táng');
        
        
        return $data;
    }

    function get_forums_wedding_data() {
        global $OUTPUT,$DB,$CFG,$USER;
        require_once($CFG->dirroot.'/local/newsvnr/lib.php');
  
        $sql = "SELECT p.subject, LEFT(p.message, 500) as message, d.name,d.id,d.forum,d.course,p.id as postid, p.modified, d.countviews, d.userid
                FROM {forum} as f
                    LEFT JOIN  {forum_discussions} as d on f.id  = d.forum 
                    INNER JOIN {forum_posts} as p on d.id = p.discussion
                WHERE f.type = :typenews AND f.id = :forumid ORDER BY d.timemodified DESC LIMIT 1
                "; 
        $data = $DB->get_records_sql($sql,array('typenews' => 'news', 'forumid' => WEDDING_FORUM));
        $templatecontext['sliderenabled'] = "1";
        foreach ($data as $key => $value) {        
            $arr[] = (array)$value;
        }
        for ($i = 1, $j = 0; $i <= count($data); $i++, $j++) {
            $key = 'key' . $i;
            $templatecontext['weddingnews'][$j][$key] = true;
            $templatecontext['weddingnews'][$j]['active'] = false;
            $fs = get_file_storage();
            $imagereturn = '';

            $post = $DB->get_record('forum_posts',['id' => $arr[$j]['postid']]);
            $cm = get_coursemodule_from_instance('forum', $arr[$j]['forum'], $arr[$j]['course'], false, MUST_EXIST);
            $context = context_module::instance($cm->id);
            $files = $fs->get_area_files($context->id, 'mod_forum', 'attachment', $post->id, "filename", false);
           
            if ($files) {
                foreach ($files as $file) {
                    $filename = $file->get_filename();
                    $mimetype = $file->get_mimetype();
                    $iconimage = $OUTPUT->pix_icon(file_file_icon($file), get_mimetype_description($file), 'moodle', array('class' => 'icon'));
                    $path = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$context->id.'/mod_forum/attachment/'.$post->id.'/'.$filename);
                    $imagereturn .= "<img src=\"$path\" class=\"fh5co_img_special_relative\" alt=\"\" />";
                }
            }

            if(!$imagereturn) {
              $courseimage = $OUTPUT->get_generated_image_for_id($arr[$j]['postid']);
              $imagereturn .= "<img src=\"$courseimage\" class=\"fh5co_img_special_relative\" alt=\"\" />";
            }

            $templatecontext['weddingnews'][$j]['subject'] = $arr[$j]['subject'];
            $templatecontext['weddingnews'][$j]['author'] = fullname($DB->get_record('user', ['id' => $arr[$j]['userid']]));
            $templatecontext['weddingnews'][$j]['message'] = strip_tags($arr[$j]['message']);
            $templatecontext['weddingnews'][$j]['name'] = $arr[$j]['name'];
            $templatecontext['weddingnews'][$j]['image'] = $imagereturn;
            $templatecontext['weddingnews'][$j]['timecreated'] = convertunixtime('l, d m Y',$arr[$j]['modified'],'Asia/Ho_Chi_Minh');
            $templatecontext['weddingnews'][$j]['newsurl'] = $CFG->wwwroot."/news.php?id=".$arr[$j]['id'];
            if ($i === 1) {
                $templatecontext['weddingnews'][$j]['active'] = true;
            }
        }
        return $templatecontext;
    }
    function get_forums_matrimony_data() {
        global $OUTPUT,$DB,$CFG,$USER;
        require_once($CFG->dirroot.'/local/newsvnr/lib.php');
  
        $sql = "SELECT p.subject, LEFT(p.message, 500) as message, d.name,d.id,d.forum,d.course,p.id as postid, p.modified, d.countviews, d.userid
                FROM {forum} as f
                    LEFT JOIN  {forum_discussions} as d on f.id  = d.forum 
                    INNER JOIN {forum_posts} as p on d.id = p.discussion
                WHERE f.type = :typenews AND f.id = :forumid ORDER BY d.timemodified DESC LIMIT 1
                "; 
        $data = $DB->get_records_sql($sql,array('typenews' => 'news', 'forumid' => MATRIMONY_FORUM));
        $templatecontext['sliderenabled'] = "1";
        foreach ($data as $key => $value) {        
            $arr[] = (array)$value;
        }
        for ($i = 1, $j = 0; $i <= count($data); $i++, $j++) {
            $key = 'key' . $i;
            $templatecontext['matrimonynews'][$j][$key] = true;
            $templatecontext['matrimonynews'][$j]['active'] = false;
            $fs = get_file_storage();
            $imagereturn = '';

            $post = $DB->get_record('forum_posts',['id' => $arr[$j]['postid']]);
            $cm = get_coursemodule_from_instance('forum', $arr[$j]['forum'], $arr[$j]['course'], false, MUST_EXIST);
            $context = context_module::instance($cm->id);
            $files = $fs->get_area_files($context->id, 'mod_forum', 'attachment', $post->id, "filename", false);
           
            if ($files) {
                foreach ($files as $file) {
                    $filename = $file->get_filename();
                    $mimetype = $file->get_mimetype();
                    $iconimage = $OUTPUT->pix_icon(file_file_icon($file), get_mimetype_description($file), 'moodle', array('class' => 'icon'));
                    $path = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$context->id.'/mod_forum/attachment/'.$post->id.'/'.$filename);
                    $imagereturn .= "<img src=\"$path\" class=\"fh5co_img_special_relative\" alt=\"\" />";
                }
            }

            if(!$imagereturn) {
              $courseimage = $OUTPUT->get_generated_image_for_id($arr[$j]['postid']);
              $imagereturn .= "<img src=\"$courseimage\" class=\"fh5co_img_special_relative\" alt=\"\" />";
            }

            $templatecontext['matrimonynews'][$j]['subject'] = $arr[$j]['subject'];
            $templatecontext['matrimonynews'][$j]['author'] = fullname($DB->get_record('user', ['id' => $arr[$j]['userid']]));
            $templatecontext['matrimonynews'][$j]['message'] = strip_tags($arr[$j]['message']);
            $templatecontext['matrimonynews'][$j]['name'] = $arr[$j]['name'];
            $templatecontext['matrimonynews'][$j]['image'] = $imagereturn;
            $templatecontext['matrimonynews'][$j]['timecreated'] = convertunixtime('l, d m Y',$arr[$j]['modified'],'Asia/Ho_Chi_Minh');
            $templatecontext['matrimonynews'][$j]['newsurl'] = $CFG->wwwroot."/news.php?id=".$arr[$j]['id'];
            if ($i === 1) {
                $templatecontext['matrimonynews'][$j]['active'] = true;
            }
        }
        return $templatecontext;
    }
    function get_forums_burial_data() {
        global $OUTPUT,$DB,$CFG,$USER;
        require_once($CFG->dirroot.'/local/newsvnr/lib.php');
  
        $sql = "SELECT p.subject, LEFT(p.message, 500) as message, d.name,d.id,d.forum,d.course,p.id as postid, p.modified, d.countviews, d.userid
                FROM {forum} as f
                    LEFT JOIN  {forum_discussions} as d on f.id  = d.forum 
                    INNER JOIN {forum_posts} as p on d.id = p.discussion
                WHERE f.type = :typenews AND f.id = :forumid ORDER BY d.timemodified DESC LIMIT 1
                "; 
        $data = $DB->get_records_sql($sql,array('typenews' => 'news', 'forumid' => BURIAL_FORUM));
        $templatecontext['sliderenabled'] = "1";
        foreach ($data as $key => $value) {        
            $arr[] = (array)$value;
        }
        for ($i = 1, $j = 0; $i <= count($data); $i++, $j++) {
            $key = 'key' . $i;
            $templatecontext['burialnews'][$j][$key] = true;
            $templatecontext['burialnews'][$j]['active'] = false;
            $fs = get_file_storage();
            $imagereturn = '';

            $post = $DB->get_record('forum_posts',['id' => $arr[$j]['postid']]);
            $cm = get_coursemodule_from_instance('forum', $arr[$j]['forum'], $arr[$j]['course'], false, MUST_EXIST);
            $context = context_module::instance($cm->id);
            $files = $fs->get_area_files($context->id, 'mod_forum', 'attachment', $post->id, "filename", false);
           
            if ($files) {
                foreach ($files as $file) {
                    $filename = $file->get_filename();
                    $mimetype = $file->get_mimetype();
                    $iconimage = $OUTPUT->pix_icon(file_file_icon($file), get_mimetype_description($file), 'moodle', array('class' => 'icon'));
                    $path = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$context->id.'/mod_forum/attachment/'.$post->id.'/'.$filename);
                    $imagereturn .= "<img src=\"$path\" class=\"fh5co_img_special_relative\" alt=\"\" />";
                }
            }

            if(!$imagereturn) {
              $courseimage = $OUTPUT->get_generated_image_for_id($arr[$j]['postid']);
              $imagereturn .= "<img src=\"$courseimage\" class=\"fh5co_img_special_relative\" alt=\"\" />";
            }

            $templatecontext['burialnews'][$j]['subject'] = $arr[$j]['subject'];
            $templatecontext['burialnews'][$j]['author'] = fullname($DB->get_record('user', ['id' => $arr[$j]['userid']]));
            $templatecontext['burialnews'][$j]['message'] = strip_tags($arr[$j]['message']);
            $templatecontext['burialnews'][$j]['name'] = $arr[$j]['name'];
            $templatecontext['burialnews'][$j]['image'] = $imagereturn;
            $templatecontext['burialnews'][$j]['timecreated'] = convertunixtime('l, d m Y',$arr[$j]['modified'],'Asia/Ho_Chi_Minh');
            $templatecontext['burialnews'][$j]['newsurl'] = $CFG->wwwroot."/news.php?id=".$arr[$j]['id'];
            if ($i === 1) {
                $templatecontext['burialnews'][$j]['active'] = true;
            }
        }
        return $templatecontext;
    }

    function add_news_button($id, $name) {
        $output = '
            <form method="get" action="/mod/forum/view.php" id="" target="_blank" class="mb-2">
                <input type="hidden" name="id" value="'.$id.'">
                <button type="submit" class="btn btn-primary" title="">'.$name.'</button>
            </form>';
        return $output;
    } 
}