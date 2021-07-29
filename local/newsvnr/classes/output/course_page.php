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
 * Version details
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @package local_newsvnr
 * @copyright 2019 VnResource
 * @author   Le Thanh Vu
 **/
namespace local_newsvnr\output;

require_once("$CFG->dirroot/webservice/externallib.php");
require_once('lib.php');

use renderable;
use templatable;
use renderer_base;
use stdClass;
use context_module;
use DateTime;
use DateTimeZone;
use theme_moove\util\theme_settings;
class course_page implements renderable, templatable 
{

  public function export_for_template(renderer_base $output) 
  {
    global $DB,$USER,$PAGE,$CFG;

    $theme_settings = new theme_settings();
    $searchquery  = optional_param('search', '', PARAM_RAW);
    $showalldata = optional_param('showall','', PARAM_RAW);
    $paginglink = $CFG->wwwroot."/local/newsvnr/course.php?showall=-1&page=1";

    $page = optional_param('page',1, PARAM_INT);
    $pagesize = optional_param('pagesize',12, PARAM_INT);
    $data = \core_webservice_external::get_site_info();
    $data['course'] = true;
    $data['showallurl'] = $CFG->wwwroot.'/course/index.php';
    $data['popularcourse'] = $theme_settings->get_courses_data();
    $data['newestcourse'] = self::get_newest_course();
    $data['allcourse'] = self::get_all_course();
    if($showalldata)
    {
      $data['course'] = false;
      $data['showall'] = true;
      $data['fullcourse'] = self::get_full_course($pagesize,$page,$searchquery);
    }


    return $data;
  }

  public static function get_newest_course() 
  {
    global $CFG, $DB ,$OUTPUT, $USER;
    require_once($CFG->dirroot.'/course/renderer.php');
    $chelper = new \coursecat_helper();


    $sql = "SELECT top 3 * from mdl_course order by timecreated desc";

    $courses = $DB->get_records_sql($sql);

    $newspage = new \local_newsvnr\output\news_page();
    $theme_settings = new theme_settings();

    $imgurl = $CFG->wwwroot."/theme/moove/pix/f2.png";

    $imgdefault = \html_writer::empty_tag('img',array('src' => $imgurl,'class'=>'userpicture defaultuserpic','width' => '50px','height'=>'50px','alt' => 'Default picture','title'=>'Default picture'));
    $coursearr = array();
 
    foreach ($courses as $course) 
    {
      $coursestd = new stdclass();
      $courseobj = new \core_course_list_element($course);
      $courseid = $course->id;
      $time = self::convertunixtime('l, d m Y',$course->timecreated,'Asia/Ho_Chi_Minh');
      $courselink = $CFG->wwwroot."/course/view.php?id=".$course->id;
      $coursestd->fullnameurl = \html_writer::link($courselink,strip_tags($chelper->get_course_formatted_name($course)));
      $coursestd->summary = strip_tags($chelper->get_course_formatted_summary($courseobj,array('overflowdiv' => false, 'noclean' => false, 'para' => false)));
      $coursestd->imageurl = $theme_settings::get_course_images($courseobj, $courselink);
      $coursestd->courseid = $courseid;
      $arr = $theme_settings::role_courses_teacher($courseid);
      $coursestd->fullnamet = $arr->fullnamet;
      $coursestd->countstudent = $arr->studentnumber;
      $enrolmethod = get_enrol_method($courseid);
      $progress = \core_completion\progress::get_course_progress_percentage($course,$USER->id);
      if(isset($progress)) {
        $coursestd->progress = round($progress);
        if($coursestd->progress == 0) 
          $coursestd->progress = -1;
      } else {
        $coursestd->enrolmethod = $enrolmethod;
      }
      if (isset($arr->id)) {
          $stduser = new stdClass();
          $userid = $DB->get_records('user',array('id' => $arr->id));
          foreach ($userid as $userdata)
             $stduser = (object)$userdata;

           $coursestd->imageteacher = $OUTPUT->user_picture($stduser, array('size'=>50));
      }
      else
      {
          $coursestd->imageteacher = $arr->imgdefault;
      }
    
      $coursearr[] = $coursestd;

 
     }
    return array_values($coursearr);
  }

  public static function get_all_course() 
  {
    global $CFG,$DB,$OUTPUT,$USER;
    require_once($CFG->dirroot.'/course/renderer.php');
    $chelper = new \coursecat_helper();
    $sql = "SELECT top 8 * from mdl_course order by timecreated desc";
    $courses = $DB->get_records_sql($sql);
    $newspage = new \local_newsvnr\output\news_page();
    $theme_settings = new theme_settings();
    $imgurl = $CFG->wwwroot."/theme/moove/pix/f2.png";
    $imgdefault = \html_writer::empty_tag('img',array('src' => $imgurl,'class'=>'userpicture defaultuserpic','width' => '50px','height'=>'50px','alt' => 'Default picture','title'=>'Default picture'));
    $coursearr = array();
 
    foreach ($courses as $course) 
    {
      $coursestd = new stdclass();
      $courseobj = new \core_course_list_element($course);
      $courseid = $course->id;
      $time = $newspage::convertunixtime('l, d m Y',$course->timecreated,'Asia/Ho_Chi_Minh');
      $courselink = $CFG->wwwroot."/course/view.php?id=".$course->id;
      $coursestd->fullnameurl = \html_writer::link($courselink,strip_tags($chelper->get_course_formatted_name($course)));
      $coursestd->summary = strip_tags($chelper->get_course_formatted_summary($courseobj,array('overflowdiv' => false, 'noclean' => false, 'para' => false)));
      $coursestd->imageurl = $theme_settings::get_course_images($courseobj, $courselink);
      $coursestd->courseid = $courseid;
      $arr = $theme_settings::role_courses_teacher($courseid);
      $coursestd->fullnamet = $arr->fullnamet;
      $coursestd->countstudent = $arr->studentnumber;
      $enrolmethod = get_enrol_method($courseid);
      $progress = \core_completion\progress::get_course_progress_percentage($course,$USER->id);
      if(isset($progress)) {
        $coursestd->progress = round($progress);
        if($coursestd->progress == 0) 
          $coursestd->progress = -1;
      } else {
        $coursestd->enrolmethod = $enrolmethod;
      }
      if (isset($arr->id)) {
          $stduser = new stdClass();
          $userid = $DB->get_records('user',array('id' => $arr->id));
          foreach ($userid as $userdata)
             $stduser = (object)$userdata;

           $coursestd->imageteacher = $OUTPUT->user_picture($stduser, array('size'=>50));
      }
      else
      {
          $coursestd->imageteacher = $arr->imgdefault;
      }
    
      $coursearr[] = $coursestd;

 
     }
    return array_values($coursearr);
  }
  public static function get_full_course($pagesize=12,$page=1,$search='') 
  {
    global $CFG,$DB,$OUTPUT,$PAGE;
    require_once($CFG->dirroot.'/course/renderer.php');
    $chelper = new \coursecat_helper();
    $searchquery  = optional_param('search', '', PARAM_RAW);
    if(!empty($search))
    {
      $sql = "SELECT * from mdl_course where id>1 and (fullname LIKE '%$search%' or summary LIKE '$%search%')  order by timecreated desc";
      $pagingsql = "SELECT * from mdl_course where id>1 and (fullname LIKE '%$search%' or summary LIKE '%$search%') order by timecreated desc OFFSET $pagesize * ($page - 1) ROWS FETCH NEXT $pagesize ROWS ONLY";
    }
    else
    {
      $sql = "SELECT * from mdl_course where id>1 order by timecreated desc";
      $pagingsql = "SELECT * from mdl_course where id>1 order by timecreated desc OFFSET $pagesize * ($page - 1) ROWS FETCH NEXT $pagesize ROWS ONLY";
    }
    $courses = $DB->get_records_sql($sql);
    $pagingcourses = $DB->get_records_sql($pagingsql);
    $totalpage = ceil(count($courses)/$pagesize);
    $newspage = new \local_newsvnr\output\news_page();
    $theme_settings = new theme_settings();
    $imgurl = $CFG->wwwroot."/theme/moove/pix/f2.png";
    $imgdefault = \html_writer::empty_tag('img',array('src' => $imgurl,'class'=>'userpicture defaultuserpic','width' => '50px','height'=>'50px','alt' => 'Default picture','title'=>'Default picture'));
    $coursearr = array();

    foreach ($pagingcourses as $course) 
    {
      $coursestd = new stdclass();
      $courseobj = new \core_course_list_element($course);
      $courseid = $course->id;
      $time = $newspage::convertunixtime('l, d m Y',$course->timecreated,'Asia/Ho_Chi_Minh');
      $courselink = $CFG->wwwroot."/course/view.php?id=".$course->id;
     
      $coursestd->fullnameurl = \html_writer::link($courselink,strip_tags($chelper->get_course_formatted_name($course)));
      $coursestd->summary = strip_tags($chelper->get_course_formatted_summary($courseobj,array('overflowdiv' => false, 'noclean' => false, 'para' => false)));
      $coursestd->imageurl = $theme_settings::get_course_images($courseobj, $courselink);
      $coursestd->courseid = $courseid;
      $arr = $theme_settings::role_courses_teacher($courseid);
      $coursestd->fullnamet = $arr->fullnamet;
      $coursestd->countstudent = $arr->studentnumber;
  
      if (isset($arr->id)) {
          $stduser = new stdClass();
          $userid = $DB->get_records('user',array('id' => $arr->id));
          foreach ($userid as $userdata)
             $stduser = (object)$userdata;

           $coursestd->imageteacher = $OUTPUT->user_picture($stduser, array('size'=>50));
      }
      else
      {
          $coursestd->imageteacher = $arr->imgdefault;
      }
    
        $coursearr[] = $coursestd;

 
     }
     
      $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
      for($i=1;$i<=$totalpage;$i++)
      {
        if($searchquery)
        {
            $paginglink = $CFG->wwwroot."/local/newsvnr/course.php?showall=-1";
            $paginglink .= "&page=".$i;
            $paginglink .= "&search=".$searchquery;  
        }
        else
        {
            $paginglink = $CFG->wwwroot."/local/newsvnr/course.php?showall=-1";
            $paginglink .= "&page=".$i;
        }

        if($i===1)
        {
          $span = \html_writer::start_tag('span',array('aria-hidden' => 'true'));
          $span .= '&laquo';
          $span .= \html_writer::end_tag('span');
          $firstpage = \html_writer::start_tag('li',array('class' => 'page-item'));
          $firstpage .= \html_writer::link($paginglink,$span,array('class' =>'page-link'));
          $firstpage .= \html_writer::end_tag('li');
        }
        if($i==$totalpage)
        {
          $span = \html_writer::start_tag('span',array('aria-hidden' => 'true'));
          $span .= '&raquo;';
          $span .= \html_writer::end_tag('span');
          $lastpage = \html_writer::start_tag('li',array('class' => 'page-item'));
          $lastpage .= \html_writer::link($paginglink,$span,array('class' =>'page-link'));
          $lastpage .= \html_writer::end_tag('li');
        }

        if ($paginglink == $actual_link) 
        {
            $output = \html_writer::start_tag('li',array('class' => 'page-item active'));
            $output .= \html_writer::link($paginglink,$i,array('class' =>'page-link'));
            $output .= \html_writer::end_tag('li');
        }
        else
        {
            $output = \html_writer::start_tag('li',array('class' => 'page-item '));
            $output .= \html_writer::link($paginglink,$i,array('class' =>'page-link'));
            $output .= \html_writer::end_tag('li');
        }
        $paginghtml[] = $output;
        $firstpagehtml = $firstpage;
        if(isset($lastpage))
        {
            $lastpagehtml = $lastpage;
        }
      } 
      $templatecontext['paginghtml'] = $paginghtml;
      $templatecontext['firstpagehtml'] = $firstpagehtml;
      $templatecontext['lastpagehtml'] = $lastpagehtml;
       
     for ($i = 1, $j = 0; $i <= count($coursearr); $i++, $j++) {
            $templatecontext['course'][$j]['fullnameurl'] = $coursearr[$j]->fullnameurl;
            $templatecontext['course'][$j]['summary'] = $coursearr[$j]->summary;
            $templatecontext['course'][$j]['imageurl'] = $coursearr[$j]->imageurl;
            $templatecontext['course'][$j]['courseid'] = $coursearr[$j]->courseid;
            $templatecontext['course'][$j]['fullnamet'] = $coursearr[$j]->fullnamet;
            $templatecontext['course'][$j]['countstudent'] = $coursearr[$j]->countstudent;
            $templatecontext['course'][$j]['imageteacher'] = $coursearr[$j]->imageteacher;
      }
 
    return $templatecontext;
  }

    // convert time 
  static function convertunixtime($format="r", $timestamp=false, $timezone=false){
      $userTimezone = new DateTimeZone(!empty($timezone) ? $timezone : 'GMT');
      $gmtTimezone = new DateTimeZone('GMT');
      $myDateTime = new DateTime(($timestamp!=false?date("r",(int)$timestamp):date("r")), $gmtTimezone);
      $offset = $userTimezone->getOffset($myDateTime);
      return date($format, ($timestamp!=false?(int)$timestamp:$myDateTime->format('U')) + $offset);
  }

}
