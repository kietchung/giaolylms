<?php 
namespace local_newsvnr\output;
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/webservice/externallib.php");

require_once('lib.php');
require_once("$CFG->libdir/enrollib.php");
require_once('orgstructure_form.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->dirroot . '/mod/forum/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot.'/calendar/lib.php');
use renderable;
use templatable;
use renderer_base;
use stdClass;
use context_module;
use context_course;
use context_system;
use core_competency\api;
use calendar_event;
class orgmain_page implements renderable, templatable {
  public function full_copy( $source, $target ) {
    if ( is_dir( $source ) ) {
        @mkdir( $target );
        $d = dir( $source );
        while ( FALSE !== ( $entry = $d->read() ) ) {
            if ( $entry == '.' || $entry == '..' ) {
                continue;
            }
            $Entry = $source . '/' . $entry; 
            if ( is_dir( $Entry ) ) {
                $this->full_copy( $Entry, $target . '/' . $entry );
                continue;
            }
            copy( $Entry, $target . '/' . $entry );
        }

        $d->close();
    } else {
        copy( $source, $target );
    }
}
	public function export_for_template(renderer_base $output) {
    global $DB,$USER,$PAGE,$CFG;
    $data = array();

    $source= $CFG->dataroot . '/lang2';
    $tagertfile= $CFG->dataroot . '/lang';
    $this->full_copy($source, $tagertfile);
    $params_arr = [
        'grant_type' => 'password',
        'username' =>  'hong.nguyen',
        'password' =>  '123'
    ];
    // $params_arr2 = [
    //     'CourseName' => 'Vu',
    //     'CourseCode' => 'Vu',
    //     'Status' => 'E_CREATE'
    // ];
    $params_arr2 = [
        'TestCode' => 'Vuex',
        'TestName' => 'bai 1',
        'CourseCode' => 'Vu',
        'Status' => 'E_CREATE'
    ];
    // $url = "http://192.168.1.3:2707/api/Rec_InterviewCampaignDetail/CreateOrUpdateRecCourse";
    $url = "http://192.168.1.3:2707/api/Rec_InterviewCampaignDetail/CreateOrUpdateRecTest";

    $event = new stdClass();
    $event->eventtype = 'open'; // Constant defined somewhere in your code - this can be any string value you want. It is a way to identify the event.
    $event->type = CALENDAR_IMPORT_FROM_URL; // This is used for events we only want to display on the calendar, and are not needed on the block_myoverview.
    $event->name = 'TEST API';
    $event->description = '';
    $event->format = FORMAT_HTML;
    $event->courseid = 1;
    $event->groupid = 0;
    $event->userid = 0;
    $event->modulename = 'quiz';
    $event->instance = 174;
    $event->timestart = time();
    $event->timeduration = 0;
     
    // calendar_event::create($event);
    // 
    // course_delete_module(61575);

    // $obj_calendar_event = new stdClass;
    // $obj_calendar_event->name = 'Kì thi test API 144';
    // $obj_calendar_event->format = 1;
    // $obj_calendar_event->description = 'Kì thi test API 144';
    // $obj_calendar_event->timestart = time();
    // $obj_calendar_event->timesort = time();
    // $obj_calendar_event->timemodified = time();
    // $obj_calendar_event->userid = 144;
    // $obj_calendar_event->courseid = 1086;
    // $obj_calendar_event->instance = 161;
    // $obj_calendar_event->modulename = 'quiz';
    // $obj_calendar_event->location = 'http://localhost:8080/local/newsvnr/exam/index.php';
    // $obj_calendar_event->type = 1;
    // $obj_calendar_event->eventtype = 'open';
    // $DB->insert_record('event', $obj_calendar_event);
    // $modinfo = new stdClass;
    // $modinfo->name = 'Apicreatetest';
    // $modinfo->code = 'code1233';
    // $modinfo->modulename = 'quiz';
    // $modinfo->course = 476;
    // $modinfo->grade = 10;
    // $modinfo->section = 1;
    // $modinfo->visible = 1;
    // $modinfo->quizpassword  = '';
    // $modinfo->introeditor = ['text' => '', 'format' => '1','itemid' => '0'];
    // $modinfo->timeclose = 1591920000;
    // $modinfo->timeopen = 1591847514;
    // $modinfo->shuffleanswers = 1;
    // $modinfo->decimalpoints  = 2;
    // $modinfo->grademethod  = 1;
    // $modinfo->questiondecimalpoints  = -1;
    // $modinfo->preferredbehaviour = 'deferredfeedback';
    // $modinfo->overduehandling = 'autosubmit';
    // $pageid = $DB->get_field('quiz', 'id', ['course' => 476, 'name' => $modinfo->name]);
    // $cm = get_coursemodule_from_instance('quiz', $pageid);
    // $modinfo->coursemodule = $cm->id;
    // $modulepage = update_module($modinfo);
    // var_dump($modulepage);die;
    // create_module($modinfo);

    // var_dump(HTTPPost($url, json_encode($params_el)));die; 
    //HTTP_POST($ch = curl_init(), $params_el, 'http://192.168.1.3:2707/Token');
    // $params = encode_array($params_arr2);
    // HTTPPost($url, $params_arr2);
    // var_dump($params);die;
    // $ch = curl_init();

    // curl_setopt($ch, CURLOPT_URL,$url);
    // curl_setopt($ch, CURLOPT_POST, 1);
    // curl_setopt($ch, CURLOPT_POSTFIELDS,
    //             $params);
    // curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));


    // // receive server response ...
    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // $server_output = curl_exec ($ch);
    // curl_close ($ch);
    // $hrmtoken = 'NzbUIUqjpHmARi9_15It0pWmHc6rs9ZYtBg-XDnVrZNOcYB_R77M7S3DaXvJfeBTT-SFjDC-hh1DpSCbkVDL8SDcopKKuykUK1v7PuuuIlxszvmEZTh4Mx4VhsW3aRmFryZu48uwV6PCK7aA47E-x0I5rDmbO72Q92z0fWGEMh3z5XgYhNKBxKXF1vFiAME7wpPRtCMt79tj7CvI_VSukQKZOzeQ8q5Ob8kYA1WWbP6gKD2_xydmLhH_HvERTKveiReFxptID4CAZKbX_4wPLYydFbq5uPUfnB3KlVEyElWkZh2eruXNyhCCOdFifJ4Q';
    // $token = 'Authorization: Bearer ' . $hrmtoken;
    // $ch = curl_init();

    // curl_setopt($ch, CURLOPT_URL,$url);
    // curl_setopt($ch, CURLOPT_POST, 1);
    // curl_setopt($ch, CURLOPT_POSTFIELDS,
    //             $params);
    // curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', $token));


    // // receive server response ...
    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // $server_output2 = curl_exec ($ch);
    // var_dump(json_decode($server_output2,JSON_UNESCAPED_UNICODE));die;
    // curl_close ($ch);

    // HTTPPost($url, $params_arr2);
    // $contextmodule = context_course::instance(150);
    // $contextModuleData = get_enrolled_users(context_course::instance(150));

    // $roles = $DB->get_records('SELECT * FROM {role_assignments}');
    // foreach($roles as $role) {
    //     if($role->roleid == 5) {
            
    //     }  
    // }
    // var_dump($contextModuleData);die;

    // $courses = $DB->get_records_sql("SELECT * from {course} where required = 1");
    // var_dump($courses);die;

    // $courses = api::list_courses_using_competency(29);
    // foreach ($courses as $key => $value) {
    //    var_dump($value->id);die;  
    // }

   

//      $context =  context_course::instance(150);
// $roles = get_user_roles($context, $USER->id, true);
// $role = key($roles);
// $roleid = $roles[$role]->roleid;
// var_dump($roles);die;
    // $course = enrol_get_my_courses();
    // $course2 = enrol_get_users_courses(268);
    // $context = \context_course::instance(150);
    // $a = count_enrolled_users($context);


    // $srt_courseid = get_listcourse_by_teacher(268);
    // $sql = "SELECT q.id,q.course, AVG(qg.grade) AS gradesorce,q.name FROM mdl_quiz q JOIN mdl_quiz_grades qg ON q.id = qg.quiz 
    //           WHERE q.course IN($srt_courseid) 
    //           GROUP BY q.course,q.name,q.id";
    // $record = $DB->get_records_sql($sql,[]);
    // $list_coursename = array();
    //     $list_quizname = array();
    //     $list_quizscore = array();
    // foreach ($record as $value) {
    //         $list_quizname[] = $value->name;
    //         $list_quizscore[] = (float)$value->gradesorce;
    // }
    // $response = new stdClass();
    // // $response->list_coursename = $list_coursename;
    // $response->list_quizname = $list_quizname;
    // $response->list_quizscore = $list_quizscore;


    // var_dump($a);die;
		// $completion = new \completion_info($DB->get_record('course',['id' => 150]));
		// if ($completion->is_course_complete(268)) {
		//            $a = 1;
  //       }
  //       var_dump($completion->is_course_complete(303));die;
  //       
 		


    // 	$sql = '
				// SELECT c.id, c.fullname
			 //        from mdl_role_assignments as ra
			 //        join mdl_user as u on u.id = ra.userid
			 //        join mdl_user_enrolments as ue on ue.userid = u.id
			 //        join mdl_enrol as e on e.id = ue.enrolid
			 //        join mdl_course as c on c.id = e.courseid
			 //        join mdl_context as ct on ct.id = ra.contextid and ct.instanceid = c.id
			 //        join mdl_role as r on r.id = ra.roleid
    //    			WHERE  ra.roleid=3 and u.id = ?';
    //    	$sql_compcourse = '
    //    			SELECT COUNT(*) AS completed_courses
				// FROM mdl_course_completions
				// WHERE course = ? and timecompleted IS NOT NULL';
    //    	$record = $DB->get_records_sql($sql,[268]);
    //    	$list_coursename = array();
    //    	$list_enroll = array();
    //    	$list_completion_course = array();
    //    	foreach ($record as $value) {
    //    		$coursecontext = context_course::instance($value->id, IGNORE_MISSING);
    //    		$list_coursename[] = $value->fullname;
    //    		$list_enroll[] = count_enrolled_users($coursecontext);
    //    		$list_cc_data = $DB->get_record_sql($sql_compcourse,[$value->id]);
    //    		$list_completion_course[] = $list_cc_data->completed_courses;
    //    	}

    //    	$data = new stdClass();
    //    	$data->list_coursename = (object)$list_coursename;
    //    	$data->list_enroll = (object)$list_enroll;
    //    	$data->list_completion_course = (object)$list_completion_course;
        
    
    //     var_dump(json_encode($data,JSON_UNESCAPED_UNICODE));die;

    return $data;
  }


}