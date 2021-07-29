<?php

global $DB, $CFG;	

require_once (__DIR__ . '/../../../config.php');

require_once (__DIR__ . '/../lib.php');

require_once (__DIR__ . '/../../../lib/filelib.php');

use theme_moove\util\theme_settings;


$PAGE->set_context(context_system::instance());	

require_once($CFG->dirroot.'/course/renderer.php');


require_once("pagination.class.php");
	

$perPage = new PerPage();

$sql = "SELECT c.id, c.category, c.fullname, c.shortname, c.startdate, c.visible, c.timecreated, c.timemodified FROM {course} c order by c.timecreated desc";

$paginationlink =  curPageURL() . '/local/newsvnr/ajax/pagination_course.php?page=';	

$pagination_setting = isset($_GET["pagination_setting"]) ? $_GET["pagination_setting"] : "";
				
$page = 1;

if(!empty($_GET["page"])) {
$page = $_GET["page"];
}

$start = ($page-1)*$perPage->perpageCourse;

if($start < 0) $start = 0;

$query =  $sql . " OFFSET " . $start . " ROWS FETCH next " . $perPage->perpageCourse . " ROWS only"; 

$faq = $DB->get_records_sql($query);

$numrow = $DB->get_records_sql($sql);


if(empty($_GET["rowcount"])) {

 $_GET["rowcount"] = count($numrow);

}

if($pagination_setting == "prev-next") {
	$perpageresult = $perPage->getPrevNext($_GET["rowcount"], $paginationlink,$pagination_setting);	
} else {
	$perpageresult = $perPage->getAllCoursesPageLinks($_GET["rowcount"], $paginationlink,$pagination_setting);	
}


	$courses = $DB->get_records_sql($query);

	$theme_settings = new theme_settings();

	$imgurl = $CFG->wwwroot."/theme/moove/pix/f2.png";


    $imgdefault = \html_writer::empty_tag('img',array('src' => $imgurl,'class'=>'userpicture defaultuserpic','width' => '50px','height'=>'50px','alt' => 'Default picture','title'=>'Default picture'));


      $chelper = new \coursecat_helper();

  	$coursearr = array();
    foreach ($courses as $course) 
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
			$output .= '
                    <div class="col-md-3 col-sm-6 post-slide6">
                                    
                        <div class="post-img">
                                '. $value->imageurl .'
                            <div class="post-info">
                                <ul class="category">
                                    <li>'. get_string('countstudent', 'local_newsvnr') .': <a href="#">'. $value->countstudent .'</a></li>
                                    <li>'. get_string('teachername', 'local_newsvnr') .': <a href="#">'. $value->fullnamet .'</a></li>
                                   
                                </ul>
              
                            </div>
                        </div>
                        <div class="post-review">
                            <span class="icons">
                                  '. $value->imageteacher .'
                            </span>
                            <h3 class="post-title">' . $value->fullnameurl .'</h3>
                            <p class="post-description" >'. $value->summary .'</p>
                          
                        </div> 
                    </div>';
		}

		$output .= '';
	}

if(!empty($perpageresult)) {

$output .= '<div class="col-md-12"> <div id="paginationCourse">' . $perpageresult . '</div> </div>';
}


print $output;





