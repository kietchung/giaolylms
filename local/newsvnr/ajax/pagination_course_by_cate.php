<?php

require_once (__DIR__ . '/../../../config.php');

use theme_moove\util\theme_settings;


$PAGE->set_context(context_system::instance());	

require_once($CFG->dirroot.'/course/renderer.php');



global $DB;

$course_page = new \local_newsvnr\output\course_page();


$cateid = optional_param('cateid', 0 ,PARAM_INT);

$currentPage = optional_param('page', 0 ,PARAM_INT);
	

$itemInPage = 3;


if($currentPage == 1)
{
	$from = 3;
}
else{
	$from  = $currentPage * $itemInPage;
}


	$get_course = $course_page->get_coures_by_cate($cateid, $from, $itemInPage);



	$theme_settings = new theme_settings();

	$imgurl = $CFG->wwwroot."/theme/moove/pix/f2.png";


    $imgdefault = \html_writer::empty_tag('img',array('src' => $imgurl,'class'=>'userpicture defaultuserpic','width' => '50px','height'=>'50px','alt' => 'Default picture','title'=>'Default picture'));


      $chelper = new \coursecat_helper();

  	$coursearr = array();
    foreach ($get_course as $course) 
    {

      $coursestd = new stdclass();

      $courseobj = new \core_course_list_element($course);

      $courseid = $course->id;

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

	$output = '';

	if($coursearr != '')
	{
		$output .= '';
		foreach ($coursearr as $key => $value) {
			$output .= '<div class="col-md-3 mb-3">
	            '. $value->imageurl .'
	            <div class="course-slider-content">
	                <h6 class="title">'. $value->fullnameurl .'</h6>
	                <div class="content"><p>'. $value->summary .'</p></div>

	                <i class="fa fa-graduation-cap p-1" style="font-size: 18px" aria-hidden="true">'. $value->countstudent .'</i>
	                <div class="course-user">
	                    '. $value->imageteacher .'
	                    <div class="course-user-name">
	                        <h6 style="color:#0094ff">'. $value->fullnamet .'</h6>
	                    </div>
	                </div>
	            </div>
	        </div>
	        ';
		}

		$output .= '';
	}
print $output;