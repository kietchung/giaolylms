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
 * Mustache helper to load a theme configuration.
 *
 * @package    theme_moove
 * @copyright  2017 Willian Mano - http://conecti.me
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_moove\util;

use theme_config;
use stdClass;
use single_button;
use moodle_url;
use context_course;
use theme_moove\util\extras;
use coursecat_helper;
use core_course_category;
use core_course_list_element;
use DateTime;
use context_system;
use context_module;
use context_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper to load a theme configuration.
 *
 * @package    theme_moove
 * @copyright  2017 Willian Mano - http://conecti.me
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_settings {

    /**
     * Get config theme footer itens
     *
     * @return array
     */
    public function footer_items() {
        $theme = theme_config::load('moove');

        $templatecontext = [];

        $footersettings = [
            'facebook', 'twitter', 'whatsapp', 'telegram', 'linkedin', 'youtube', 'instagram', 'getintouchcontent',
            'website', 'mobile', 'mail'
        ];

        foreach ($footersettings as $setting) {
            if (!empty($theme->settings->$setting)) {
                $templatecontext[$setting] = $theme->settings->$setting;
            }
        }

        return $templatecontext;
    }

    /**
     * Get config theme slideshow
     *
     * @return array
     */
    public function slideshow() {
        global $OUTPUT;

        $theme = theme_config::load('moove');

        $templatecontext['sliderenabled'] = $theme->settings->sliderenabled;

        if (empty($templatecontext['sliderenabled'])) {
            return $templatecontext;
        }

        $slidercount = $theme->settings->slidercount;

        for ($i = 1, $j = 0; $i <= $slidercount; $i++, $j++) {
            $sliderimage = "sliderimage{$i}";
            $slidertitle = "slidertitle{$i}";
            $slidercap = "slidercap{$i}";

            $templatecontext['slides'][$j]['key'] = $j;
            $templatecontext['slides'][$j]['active'] = false;

            $image = $theme->setting_file_url($sliderimage, $sliderimage);
            if (empty($image)) {
                $image = $OUTPUT->image_url('slide_default', 'theme');
            }
            $templatecontext['slides'][$j]['image'] = $image;
            $templatecontext['slides'][$j]['title'] = format_string($theme->settings->$slidertitle);
            $templatecontext['slides'][$j]['caption'] = format_text($theme->settings->$slidercap);

            if ($i === 1) {
                $templatecontext['slides'][$j]['active'] = true;
            }
        }

        return $templatecontext;
    }

    /**
     * Get config theme marketing itens
     *
     * @return array
     */
    public function marketing_items() {
        global $OUTPUT;

        $theme = theme_config::load('moove');

        $templatecontext = [];

        for ($i = 1; $i < 5; $i++) {
            $marketingicon = 'marketing' . $i . 'icon';
            $marketingheading = 'marketing' . $i . 'heading';
            $marketingsubheading = 'marketing' . $i . 'subheading';
            $marketingcontent = 'marketing' . $i . 'content';
            $marketingurl = 'marketing' . $i . 'url';

            $templatecontext[$marketingicon] = $OUTPUT->image_url('icon_default', 'theme');
            if (!empty($theme->settings->$marketingicon)) {
                $templatecontext[$marketingicon] = $theme->setting_file_url($marketingicon, $marketingicon);
            }

            $templatecontext[$marketingheading] = '';
            if (!empty($theme->settings->$marketingheading)) {
                $templatecontext[$marketingheading] = theme_moove_get_setting($marketingheading, true);
            }

            $templatecontext[$marketingsubheading] = '';
            if (!empty($theme->settings->$marketingsubheading)) {
                $templatecontext[$marketingsubheading] = theme_moove_get_setting($marketingsubheading, true);
            }

            $templatecontext[$marketingcontent] = '';
            if (!empty($theme->settings->$marketingcontent)) {
                $templatecontext[$marketingcontent] = theme_moove_get_setting($marketingcontent, true);
            }

            $templatecontext[$marketingurl] = '';
            if (!empty($theme->settings->$marketingurl)) {
                $templatecontext[$marketingurl] = $theme->settings->$marketingurl;
            }
        }

        return $templatecontext;
    }

    /**
     * Get the frontpage numbers
     *
     * @return array
     */
    public function numbers() {
        global $DB;

        $templatecontext['numberusers'] = $DB->count_records('user', array('deleted' => 0, 'suspended' => 0)) - 1;
        $templatecontext['numbercourses'] = $DB->count_records('course', array('visible' => 1)) - 1;
        $templatecontext['numberactivities'] = $DB->count_records('course_modules');

        return $templatecontext;
    }

    /**
     * Get config theme sponsors logos and urls
     *
     * @return array
     */
    public function sponsors() {
        $theme = theme_config::load('moove');

        $templatecontext['sponsorstitle'] = $theme->settings->sponsorstitle;
        $templatecontext['sponsorssubtitle'] = $theme->settings->sponsorssubtitle;

        $sponsorscount = $theme->settings->sponsorscount;

        for ($i = 1, $j = 0; $i <= $sponsorscount; $i++, $j++) {
            $sponsorsimage = "sponsorsimage{$i}";
            $sponsorsurl = "sponsorsurl{$i}";

            $image = $theme->setting_file_url($sponsorsimage, $sponsorsimage);
            if (empty($image)) {
                continue;
            }

            $templatecontext['sponsors'][$j]['image'] = $image;
            $templatecontext['sponsors'][$j]['url'] = $theme->settings->$sponsorsurl;

        }

        return $templatecontext;
    }

    /**
     * Get config theme clients logos and urls
     *
     * @return array
     */
    public function clients() {
        $theme = theme_config::load('moove');

        $templatecontext['clientstitle'] = format_string($theme->settings->clientstitle);
        $templatecontext['clientssubtitle'] = format_string($theme->settings->clientssubtitle);

        $clientscount = $theme->settings->clientscount;

        for ($i = 1, $j = 0; $i <= $clientscount; $i++, $j++) {
            $clientsimage = "clientsimage{$i}";
            $clientsurl = "clientsurl{$i}";

            $image = $theme->setting_file_url($clientsimage, $clientsimage);
            if (empty($image)) {
                continue;
            }

            $templatecontext['clients'][$j]['image'] = $image;
            $templatecontext['clients'][$j]['url'] = $theme->settings->$clientsurl;

        }

        return $templatecontext;
    }

    public function get_forums_header_data() {
        global $OUTPUT,$DB,$CFG,$USER;
        require_once($CFG->dirroot.'/local/newsvnr/lib.php');

        $arr = array();
        $sql = "SELECT p.subject, LEFT(p.message, 500) as message, d.name,d.id,d.forum,d.course,p.id as postid, p.modified
                FROM {forum} as f
                    LEFT JOIN  {forum_discussions} as d on f.id  = d.forum 
                    INNER JOIN {forum_posts} as p on d.id = p.discussion
                WHERE f.type = ? AND d.pinned= ? LIMIT 5
                ";  
        $data = $DB->get_records_sql($sql,array('news',1));
        $templatecontext['sliderenabled'] = "1";
        foreach ($data as $key => $value) {        
            $arr[] = (array)$value;
        }
        for ($i = 1, $j = 0; $i <= count($data); $i++, $j++) {
            $key = 'key' . $i;
            $templatecontext['hotnews'][$j][$key] = true;
            $templatecontext['hotnews'][$j]['active'] = false;
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
                    $imagereturn .= "<img src=\"$path\" alt=\"\" />";
                }
            }

            if(!$imagereturn) {
              $courseimage = $OUTPUT->get_generated_image_for_id($arr[$j]['postid']);
              $imagereturn = "<div style='background-image: url($courseimage); width: 100%;
    height: 100%;'></div>";
            }

            $templatecontext['hotnews'][$j]['subject'] = $arr[$j]['subject'];
            $templatecontext['hotnews'][$j]['message'] = strip_tags($arr[$j]['message']);
            $templatecontext['hotnews'][$j]['name'] = $arr[$j]['name'];
            $templatecontext['hotnews'][$j]['image'] = $imagereturn;
            $templatecontext['hotnews'][$j]['timecreated'] = convertunixtime('l, d m Y',$arr[$j]['modified'],'Asia/Ho_Chi_Minh');
            $templatecontext['hotnews'][$j]['newsurl'] = $CFG->wwwroot."/news.php?id=".$arr[$j]['id'];
            if ($i === 1) {
                $templatecontext['hotnews'][$j]['active'] = true;
            }
        }
        return $templatecontext;
    }
    public function get_btn_add_news() {
        global $CFG, $DB;
        if(has_capability('moodle/site:configview', context_system::instance())) { 
            $forumid = $DB->get_field_sql("SELECT id FROM mdl_forum WHERE course = :courseid LIMIT 1", ['courseid' => 1]);
            if(isset($_SESSION['USER']->editing) &&  $_SESSION['USER']->editing == 1) {
                $editing = '<form method="get" action="/course/view.php" id="newdiscussionform" class="col-12 col-md-auto">
                                <input type="hidden" name="id" value="1">
                                <input type="hidden" name="sesskey" value="'.sesskey().'">
                                <input type="hidden" name="edit" value="off">
                                <button type="submit" class="btn btn-primary" title=""><i class="icon fa slicon-pencil fa-fw "></i>Tắt chỉnh sửa trang chủ</button>
                            </form>';
                $editing .= '<div class="col-12 col-md-auto"><button class="btn btn-primary" href="/?bui_addblock&amp;sesskey='.sesskey().'" data-key="addblock" data-isexpandable="0" data-indent="0" data-showdivider="1" data-type="60" data-nodetype="0" data-collapse="0" data-forceopen="0" data-isactive="0" data-hidden="0" data-preceedwithhr="0" id="yui_3_17_2_1_1627703431544_208">
                                <div class="ml-0">
                                    <div class="media">
                                            <span class="media-left">
                                                <i class="icon fa fa-plus-square fa-fw mt-1" aria-hidden="true"></i>
                                            </span>
                                        <span class="media-body">Thêm khối</span>
                                    </div>
                                </div>
                            </button></div>';
            } else {
                $editing = '<form method="get" action="/course/view.php" id="newdiscussionform" class="col-12 col-md-auto">
                                <input type="hidden" name="id" value="1">
                                <input type="hidden" name="sesskey" value="'.sesskey().'">
                                <input type="hidden" name="edit" value="on">
                                <button type="submit" class="btn btn-primary" title=""><i class="icon fa slicon-pencil fa-fw "></i>Bật chỉnh sửa trang chủ</button>
                            </form>';
            }
            $renderbtn = '  <div class="row">';
            $renderbtn .= '<form method="get" action="/mod/forum/view.php" id="newdiscussionform" target="_blank" class="col-12 col-md-auto">
                            <input type="hidden" name="id" value="'.$forumid.'">
                            <button type="submit" class="btn btn-primary" title="">Thêm tin tức mới</button>
                        </form>
                        <form method="get" action="/admin/search.php" id="newdiscussionform" target="_blank" class="col-12 col-md-auto">
                            <button type="submit" class="btn btn-primary" title=""><i class="icon fa slicon-settings fa-fw"></i>Quản trị</button>
                        </form>';
            $renderbtn .= $editing;
                                
                                
            $renderbtn .= '</div>';
                        

            // $buttonadd = get_string('addanewdiscussion', 'forum');
            // $button = new single_button(new moodle_url('/mod/forum/view.php', ['id' => $forumid]), $buttonadd, 'get', trues);
            // $button->class = 'singlebutton forumaddnew';
            // $button->formid = 'newdiscussionform';
            // $renderbtn = $OUTPUT->render($button);
            $templatecontext['btnaddnews'] = $renderbtn;
             return $templatecontext;
        } else {   $templatecontext['btnaddnews'] = '';
            return $templatecontext;   
        } 
           
    }
    public function get_forums_trending_data() {
        global $OUTPUT,$DB,$CFG,$USER;
        require_once($CFG->dirroot.'/local/newsvnr/lib.php');
        $forumid = $DB->get_field_sql("SELECT id FROM mdl_forum WHERE course = :courseid LIMIT 1", ['courseid' => 1]);
        $arr = array();
        $forum = $DB->get_record('forum', array('id' => $forumid), '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($forum, 'forum');

        $context = context_module::instance($cm->id);
        $sql_gettrending = "SELECT objectid 
                            FROM mdl_logstore_standard_log 
                            WHERE contextid = :contextid1 
                                AND action = 'viewed' 
                                AND (timecreated BETWEEN (
                                                        SELECT (timecreated-86400) timecreated 
                                                        FROM mdl_logstore_standard_log 
                                                        WHERE contextid = :contextid2 AND ACTION = 'viewed' ORDER BY timecreated DESC LIMIT 1) 
                                                        AND 
                                                        timecreated)
                            GROUP by objectid
                            ORDER BY timecreated DESC LIMIT 8
        ";
        $exc_gettrending = $DB->get_records_sql($sql_gettrending, ['contextid1' => $context->id, 'contextid2' => $context->id]);
        $discussionids = [];
        foreach($exc_gettrending as $discussion) {
            $discussionids[] = $discussion->objectid; 
        }
        $strdiscussionids = implode(',', $discussionids);
        $sql = "SELECT p.subject, LEFT(p.message, 500) as message, d.name,d.id,d.forum,d.course,p.id as postid, p.modified, d.countviews, d.userid
                FROM {forum} as f
                    LEFT JOIN  {forum_discussions} as d on f.id  = d.forum 
                    INNER JOIN {forum_posts} as p on d.id = p.discussion
                WHERE f.type = ? AND d.id IN ($strdiscussionids)
                "; 
        $data = $DB->get_records_sql($sql,array('news'));
        $templatecontext['sliderenabled'] = "1";
        foreach ($data as $key => $value) {        
            $arr[] = (array)$value;
        }
        for ($i = 1, $j = 0; $i <= count($data); $i++, $j++) {
            $key = 'key' . $i;
            $templatecontext['trendingnews'][$j][$key] = true;
            $templatecontext['trendingnews'][$j]['active'] = false;
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
    //           $imagereturn = "<div style='background-image: url($courseimage); width: 100%;
    // height: 100%;'></div>";
            }

            $templatecontext['trendingnews'][$j]['subject'] = $arr[$j]['subject'];
            $templatecontext['trendingnews'][$j]['author'] = fullname($DB->get_record('user', ['id' => $arr[$j]['userid']]));
            $templatecontext['trendingnews'][$j]['message'] = strip_tags($arr[$j]['message']);
            $templatecontext['trendingnews'][$j]['name'] = $arr[$j]['name'];
            $templatecontext['trendingnews'][$j]['image'] = $imagereturn;
            $templatecontext['trendingnews'][$j]['timecreated'] = convertunixtime('l, d m Y',$arr[$j]['modified'],'Asia/Ho_Chi_Minh');
            $templatecontext['trendingnews'][$j]['newsurl'] = $CFG->wwwroot."/news.php?id=".$arr[$j]['id'];
            if ($i === 1) {
                $templatecontext['trendingnews'][$j]['active'] = true;
            }
        }
        return $templatecontext;
    }
    public function get_forums_newestnews_data() {
        global $OUTPUT,$DB,$CFG,$USER;
        require_once($CFG->dirroot.'/local/newsvnr/lib.php');

        $arr = array();
        $sql = "SELECT p.subject, LEFT(p.message, 500) as message, d.name,d.id,d.forum,d.course,p.id as postid, p.modified, d.userid
                FROM {forum} as f
                    LEFT JOIN  {forum_discussions} as d on f.id  = d.forum 
                    INNER JOIN {forum_posts} as p on d.id = p.discussion
                WHERE f.type = ?
                ORDER BY d.timemodified DESC
                LIMIT 5
                ";  
        $data = $DB->get_records_sql($sql,array('news'));
        $templatecontext['sliderenabled'] = "1";
        foreach ($data as $key => $value) {        
            $arr[] = (array)$value;
        }
        for ($i = 1, $j = 0; $i <= count($data); $i++, $j++) {
            $key = 'key' . $i;
            $templatecontext['newestnews'][$j][$key] = true;
            $templatecontext['newestnews'][$j]['active'] = false;
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
                    $imagereturn .= "<img src=\"$path\" alt=\"\" />";
                }
            }

            if(!$imagereturn) {
              $courseimage = $OUTPUT->get_generated_image_for_id($arr[$j]['postid']);
              $imagereturn = "<div style='background-image: url($courseimage); width: 100%;
    height: 100%;'></div>";
            }

            $templatecontext['newestnews'][$j]['subject'] = $arr[$j]['subject'];
            $templatecontext['newestnews'][$j]['author'] = fullname($DB->get_record('user', ['id' => $arr[$j]['userid']]));
            $templatecontext['newestnews'][$j]['message'] = strip_tags($arr[$j]['message']);
            $templatecontext['newestnews'][$j]['name'] = $arr[$j]['name'];
            $templatecontext['newestnews'][$j]['image'] = $imagereturn;
            $templatecontext['newestnews'][$j]['timecreated'] = convertunixtime('l, d m Y',$arr[$j]['modified'],'Asia/Ho_Chi_Minh');
            $templatecontext['newestnews'][$j]['newsurl'] = $CFG->wwwroot."/news.php?id=".$arr[$j]['id'];
            if ($i === 1) {
                $templatecontext['newestnews'][$j]['active'] = true;
            }
        }
        return $templatecontext;
    }

    public function get_forums_mostviews_data() {
        global $OUTPUT,$DB,$CFG,$USER;
        require_once($CFG->dirroot.'/local/newsvnr/lib.php');

        $arr = array();
        $sql = "SELECT p.subject, LEFT(p.message, 500) as message, d.name,d.id,d.forum,d.course,p.id as postid, p.modified, d.userid
                FROM {forum} as f
                    LEFT JOIN  {forum_discussions} as d on f.id  = d.forum 
                    INNER JOIN {forum_posts} as p on d.id = p.discussion
                WHERE f.type = ?
                ORDER BY d.countviews DESC
                LIMIT 3
                ";  
        $data = $DB->get_records_sql($sql,array('news'));
        $templatecontext['sliderenabled'] = "1";
        foreach ($data as $key => $value) {        
            $arr[] = (array)$value;
        }
        for ($i = 1, $j = 0; $i <= count($data); $i++, $j++) {
            $key = 'key' . $i;
            $templatecontext['mostviewsnews'][$j][$key] = true;
            $templatecontext['mostviewsnews'][$j]['active'] = false;
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
                    $imagereturn .= $path;
                }
            }

            if(!$imagereturn) {
              $courseimage = $OUTPUT->get_generated_image_for_id($arr[$j]['postid']);
              $imagereturn = $courseimage;
            }

            $templatecontext['mostviewsnews'][$j]['subject'] = $arr[$j]['subject'];
            $templatecontext['mostviewsnews'][$j]['author'] = fullname($DB->get_record('user', ['id' => $arr[$j]['userid']]));
            $templatecontext['mostviewsnews'][$j]['message'] = strip_tags($arr[$j]['message']);
            $templatecontext['mostviewsnews'][$j]['name'] = $arr[$j]['name'];
            $templatecontext['mostviewsnews'][$j]['image'] = $imagereturn;
            $templatecontext['mostviewsnews'][$j]['timecreated'] = convertunixtime('l, d m Y',$arr[$j]['modified'],'Asia/Ho_Chi_Minh');
            $templatecontext['mostviewsnews'][$j]['newsurl'] = $CFG->wwwroot."/news.php?id=".$arr[$j]['id'];
            if ($i === 1) {
                $templatecontext['mostviewsnews'][$j]['active'] = true;
            }
        }
        return $templatecontext;
    }

    public function get_course_category() {
        global $DB, $CFG;
        $sql = "SELECT * FROM {course_categories} WHERE parent = 0 ORDER BY timemodified DESC LIMIT 7";
        $data = $DB->get_records_sql($sql);
        $arr = array();
        foreach ($data as $key => $value) {        
            $arr[] = (array)$value;
        }
        for ($i = 1, $j = 0; $i <= count($data); $i++, $j++) {
            $templatecontext['coursecategories'][$j]['name'] = $arr[$j]['name'];
            $templatecontext['coursecategories'][$j]['url'] = $CFG->wwwroot . '/course/index.php?categoryid=' . $arr[$j]['id'];
        }
        return $templatecontext;
    }
    
    public function get_logo_news() {
        $theme = theme_config::load('moove');
        $templatecontext['logonews'] = $theme->setting_file_url('logo', 'logo');
        return $templatecontext;
    }

    public function btn_loggin() {
        global $USER;
        $context = context_course::instance(1);
        $output = '';
        if(is_guest($context, $USER)) {
            $output .= '<a href="/login/index.php"><button type="button" class="btn btn-primary fh5co_text_select_option ml-5 btn-login-logout"><i class="fa fa-sign-in mr-1" aria-hidden="true"></i>Đăng nhập</button></a>';
        } else if(is_siteadmin()) {
            $output .= '<a href="/login/logout.php?sesskey='.sesskey().'"><button type="button" class="btn btn-primary fh5co_text_select_option ml-5 btn-login-logout"><i class="fa fa-sign-out mr-1" aria-hidden="true"></i>Đăng xuất</button></a>';
        }
        $templatecontext['btnlogin'] = $output;
        return $templatecontext;
    }

}
