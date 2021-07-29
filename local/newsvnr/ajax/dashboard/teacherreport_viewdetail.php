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
 * lấy dữ liệu cho chart trong dashboard student
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @package local_newsvnr
 * @copyright 2020 VnResource
 * @author   Le Thanh Vu
 **/

define('AJAX_SCRIPT', false);

require_once __DIR__ . '/../../../../config.php';
require_once $CFG->dirroot . '/local/newsvnr/lib.php';
use theme_moove\util\theme_settings;
use \core_course\core_course_list_element;
$action = optional_param('action', null, PARAM_RAW);
require_login();
$PAGE->set_context(context_system::instance());
$pagesize = optional_param('pagesize', 10, PARAM_INT);
$pagetake = optional_param('take', 0, PARAM_INT);
$pageskip = optional_param('skip', 0, PARAM_INT);
$q        = optional_param('q', '', PARAM_RAW);
$data   = [];
$output = '';
switch ($action) {
    case 'viewcourse':
        require_once $CFG->dirroot . '/course/lib.php';
        $listcourse     = get_list_course_by_teacher($USER->id);
        $theme_settings = new theme_settings();
        if(!empty($listcourse)) {
            $output .= '<div class="row">';
            foreach ($listcourse as $value) {
                $cinfo     = new completion_info($value);
                $list_user = $DB->get_records_sql("SELECT u.*
                                                    FROM mdl_user_enrolments ue
                                                        JOIN mdl_enrol e ON ue.enrolid = e.id
                                                        JOIN mdl_course c ON e.courseid = c.id
                                                        JOIN mdl_role_assignments ra ON ra.userid = ue.userid
                                                        JOIN mdl_user u ON u.id = ra.userid
                                                        JOIN mdl_context as ct on ra.contextid= ct.id AND ct.instanceid = c.id
                                                    where ra.roleid=5 AND c.id =:courseid", ['courseid' => $value->id]);
                if ($list_user) {
                    $i = 0;
                    foreach ($list_user as $user) {
                        // Kiểm tra số học viên chưa hoàn thành khóa
                        $iscomplete = $cinfo->is_course_complete($user->id);
                        if ($iscomplete == false) {
                            $i++;
                        }
                    }
                    
                    if($i == 0) {
                        $studentfinish_percent = 0;
                    } else {
                        $studentfinish = count($list_user) - $i;
                        $sum = $i + $studentfinish;
                        $studentfinish_percent = floor(($studentfinish * 100) / $sum);
                    }
                } else {
                    $studentfinish_percent = 0;
                }
                $courseobj   = new \core_course_list_element($value);
                $arr         = $theme_settings::role_courses_teacher_slider_block_course_recent($value->id);
                $teachername = $arr->fullnamet;
                $course      = $DB->get_record('course', ['id' => $value->id]);
                $category    = $DB->get_record_sql("SELECT ct.name FROM {course} c
                                                                JOIN {course_categories} ct on ct.id = c.category
                                                        WHERE c.id =:courseid", ['courseid' => $value->id]);
                $courselink  = $CFG->wwwroot . "/course/view.php?id=" . $value->id;
                $courseimage = $theme_settings::get_course_images($courseobj, $courselink);
                $studenttotal = count($list_user);
                $output .= '<div class="col-md-6 col-sm-12 pd-0">';
                $output .= '<div class="card-body row">';
                $output .= '<div class="col-md-3 col-sm-6 courseimage">' . $courseimage . '</div>';
                $output .= '<div class="media-body col-md-9 col-sm-6">';
                $output .= '<a class="font-bold" target="_blank" href="' . $courselink . '">' . $value->fullname . '</a><br>';
                $output .= '<span class="grey">' . $category->name . '</span><br>';
                $output .= '<span>' . get_string('countstudent', 'local_newsvnr'). ': '. $studenttotal . '</span><br>';
                $output .= '<p>'.get_string('teachername', 'theme_moove').': ' . $teachername . '</p>';
                $output .= '</div>'; // end col
                $output .= '<div class="col-md-2 text-left">
                                <div class="progress-circle custom" data-progress="' . $studentfinish_percent . '" title="'.get_string('courseprogress', 'theme_moove').'"></div>
                            </div>';
                $output .= '</div>'; //end row
                $output .= '</div>'; //end col
            }
        $output .= '</div>';
        } else {
            $output .= '<div class="alert alert-warning" role="alert">'.get_string('nodata','local_newsvnr').'</div>';
        }
        $data['result'] = $output;
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        break;
    case 'viewstudent':
        $strcourseid = get_list_courseid_by_teacher($USER->id);
        $courses     = explode(',', $strcourseid);
        foreach ($courses as $course) {
            $odersql  = "";
            $wheresql = "WHERE ra.roleid= 5 AND c.id = $course";
            if ($q) {
                $wheresql = "WHERE ra.roleid= 5 AND c.id = $course AND CONCAT(u.firstname, ' ', u.lastname) like N'%$q%'";
            }
            if ($pagetake == 0) {
                $ordersql = "RowNum";
            } else {
                $ordersql = "RowNum OFFSET $pageskip ROWS FETCH NEXT $pagetake ROWS only";
            }
            $sql = "
                    SELECT *, (SELECT COUNT(u.id)
                                FROM mdl_role_assignments ra
                                    JOIN mdl_user u ON ra.userid = u.id
                                    JOIN mdl_user_enrolments ue ON u.id = ue.userid 
                                    JOIN mdl_enrol enr ON ue.enrolid = enr.id
                                    JOIN mdl_course c ON enr.courseid = c.id
                                    JOIN mdl_context ct ON ct.id = ra.contextid AND ct.instanceid = c.id
                                    JOIN mdl_role r ON ra.roleid = r.id
                                $wheresql
                                ) total
                    FROM (SELECT ROW_NUMBER() OVER (ORDER BY u.id) AS RowNum, u.*
                            FROM mdl_role_assignments ra
                                JOIN mdl_user u ON ra.userid = u.id
                                JOIN mdl_user_enrolments ue ON u.id = ue.userid 
                                JOIN mdl_enrol enr ON ue.enrolid = enr.id
                                JOIN mdl_course c ON enr.courseid = c.id
                                JOIN mdl_context ct ON ct.id = ra.contextid AND ct.instanceid = c.id
                                JOIN mdl_role r ON ra.roleid = r.id
                                $wheresql) Mydata
                                ORDER BY $ordersql";
            $listuser = $DB->get_records_sql($sql);
            foreach ($listuser as $user) {
                $obj              = new stdClass;
                $obj->usercode    = $user->usercode;
                $obj->userid      = $user->id;
                $userurl = '<a href="'.$CFG->wwwroot . '/user/profile.php?id='.$user->id.'" target="_blank">'.fullname($user).'</a>';
                $obj->name        = $OUTPUT->user_picture($user, array('size'=>35)) . $userurl;
                $obj->email       = $user->email;
                $obj->phone       = $user->phone2;
                $obj->timecreated = convertunixtime('d/m/Y', $user->timecreated, 'Asia/Ho_Chi_Minh');
                if ($user->lastaccess) {
                    $obj->lastaccess = convertunixtime('d/m/Y', $user->lastaccess, 'Asia/Ho_Chi_Minh') . " (" . format_time(time() - $user->lastaccess) . ")";
                } else {
                    $obj->lastaccess = get_string("never");
                }
                $data[] = $obj;
            }
        }
        $tempid = [];
        $uniquedata = [];
        foreach ($data as $value) {
            if(!in_array($value->userid,$tempid)) {
                $tempid[] = $value->userid;
                $uniquedata[] = $value;
            } 
        }
        $data = $uniquedata;
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        break;
    case 'viewmodule':
        if ($pagetake == 0) {
            $ordersql = "RowNum";
        } else {
            $ordersql = "RowNum OFFSET $pageskip ROWS FETCH NEXT $pagetake ROWS only";
        }
        $sql = "SELECT *, (SELECT COUNT(id) 
                            FROM mdl_logstore_standard_log
                            WHERE userid = :userid1 AND action = 'created' AND target = 'course_module' AND courseid <> 1
                            ) total
                FROM (SELECT ROW_NUMBER() OVER (ORDER BY lsl.courseid) RowNum, lsl.*, c.fullname, m.name moduletype, cm.instance moduleid, cm.id coursemoduleid
                        FROM mdl_logstore_standard_log  lsl
                            LEFT JOIN mdl_course c ON c.id = lsl.courseid
                            LEFT JOIN mdl_course_modules cm ON cm.id = lsl.contextinstanceid
                            LEFT JOIN mdl_modules m ON m.id = cm.module
                        WHERE userid = :userid2 AND action = 'created' AND target = 'course_module' AND courseid <> 1
                     ) Mydata
                ORDER BY $ordersql";
        $modules = $DB->get_records_sql($sql, ['userid1' => $USER->id, 'userid2' => $USER->id]);
        if($modules) {
            foreach($modules as $module) {
                $get_modulename = $DB->get_field($module->moduletype, 'name',['id' => $module->moduleid]);
                $obj = new stdClass;
                $moduleimg = $OUTPUT->image_url('icon', $module->moduletype);
                $modulenameurl = '<img src="'.$moduleimg.'" with="24px" height="24px"><a href="'.$CFG->wwwroot .'/mod/'.$module->moduletype.'/view.php?id='.$module->coursemoduleid.'" target="_blank" class="ml-1">'.$get_modulename.'</a>';
                $obj->modulename = $modulenameurl;
                $courseurl = '<a href="'.$CFG->wwwroot .'/course/view.php?id='.$module->courseid.'" target="_blank">'.$module->fullname.'</a>';
                $obj->coursename = $courseurl;
                $obj->moduletype = ucfirst($module->moduletype);
                $obj->timecreated = convertunixtime('d/m/Y', $module->timecreated, 'Asia/Ho_Chi_Minh');
                $module_rate_completed = 'SELECT COUNT(cm.id) completed 
                                            FROM mdl_course_modules_completion cmc
                                                JOIN mdl_course_modules cm ON cm.id = cmc.coursemoduleid
                                            WHERE cm.course = :courseid
                                                AND cm.instance = :coursemoduleid
                                                AND cmc.completionstate = 1';
                $get_module_rate_completed = $DB->get_field_sql($module_rate_completed, ['courseid' => $module->courseid, 'coursemoduleid' => $module->coursemoduleid]);
                $numberstudent = count(get_listuser_in_course($module->courseid));
                $obj->modulerate = $get_module_rate_completed .'/'.$numberstudent;
                $spenttimesql = "SELECT lsl.* 
                                FROM mdl_logstore_standard_log lsl 
                                    JOIN mdl_course_modules cm ON lsl.contextinstanceid = cm.id
                                WHERE lsl.target = 'course_module' 
                                    AND lsl.action = 'viewed' 
                                    AND lsl.courseid = :courseid 
                                    AND lsl.contextinstanceid = :coursemoduleid
                                ORDER BY lsl.timecreated";
                $spenttime = $DB->get_records_sql($spenttimesql, ['courseid' => $module->courseid, 'coursemoduleid' => $module->coursemoduleid]);
                if ($spenttime) {
                    $previouslog = array_shift($spenttime);
                    $previouslogtime = $previouslog->timecreated;
                    $sessionstart = $previouslog->timecreated;
                    $dedication = 0;
                    foreach ($spenttime as $log) {
                        if (($log->timecreated - $previouslogtime) > 3600) {
                            $dedication += $previouslogtime - $sessionstart;
                            $sessionstart = $log->timecreated;
                        }
                        $previouslogtime = $log->timecreated;
                    }
                    $dedication += $previouslogtime - $sessionstart;
                } else {
                    $dedication = 0;
                }
                $obj->spenttime = format_dedication($dedication);
                $obj->total = $module->total;
                $data[] = $obj;
            }
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        break;
    case 'viewexam':
        $output .= '';
        $exams = $DB->get_records_sql('SELECT * FROM mdl_exam_user eu 
                                        JOIN mdl_exam e ON e.id = eu.examid 
                                    WHERE eu.userid =:userid AND eu.roleid = 4',['userid' => $USER->id]);
        if($exams) {
            foreach ($exams as $exam) {
                $subjects = $DB->get_records_sql('SELECT DISTINCT esx.id, es.id AS subjectid ,es.name, esx.subjectid, e.name AS examname
                                                    FROM mdl_exam_subject_exam esx
                                                    LEFT JOIN mdl_exam e ON esx.examid = e.id
                                                    LEFT JOIN mdl_exam_subject es ON esx.subjectid = es.id
                                                WHERE e.id =:examid',['examid' => $exam->examid]);
                $output .= '<div class="exam-title">' . $exam->name . '</div>';
                $output .= '<div class="row" style="padding:0 15px">';
                foreach ($subjects as $subject) {
                    $output .= '<div class="col-md-6 pd-0">';
                    $output .= '<div class="subject-title mb-1"><i class="fa fa-clock-o mr-2" aria-hidden="true"></i>'.$subject->name.'</div>';
                    $quizs = $DB->get_records_sql("SELECT q.id as quizid,q.*,eq.*
                                                    FROM {exam_quiz} eq
                                                        JOIN {exam_subject_exam} esx ON esx.id = eq.subjectexamid
                                                        JOIN {course_modules} cm ON cm.id = eq.coursemoduleid
                                                        JOIN {quiz} q ON q.id = cm.instance
                                                    WHERE eq.subjectexamid = :subjectid", ['subjectid' => $subject->id]);
                    foreach ($quizs as $quiz) {
                            $countattempt = $DB->get_record_sql("SELECT COUNT(q.id) as count FROM {quiz} q
                                                JOIN {quiz_attempts} qa ON qa.quiz = q.id
                                                WHERE q.id = :quizid", ['quizid' => $quiz->quizid]);
                            $countquestion = $DB->get_record_sql("SELECT COUNT(qs.id) as count FROM {quiz} q
                                                                    JOIN {quiz_slots} qs ON qs.quizid = q.id
                                                                    WHERE q.id = :quizid", ['quizid' => $quiz->quizid]);
                            $img = '<img title="quiz" class="pr-2 img-module" src="' . $OUTPUT->image_url('icon', 'quiz') . '">';
                            $url = $CFG->wwwroot . '/mod/quiz/view.php?id=' . $quiz->coursemoduleid;
                            $quizurl = '<a href="'.$url.'" target="_blank">'.$quiz->name.'</a>';
                            $output .= '<div class="pl-3 pt-2 pb-2 module-quiz-online">';
                            $output .= '<div class="">' . $img . '' . $quizurl . '</a></div>';
                            $output .= '<div class="info-quiz d-flex ml-4">';
                            $output .= '<div class="detail-quiz"><i class="fa fa-check-square-o mr-1" aria-hidden="true"></i>' . $countquestion->count . ' ' . get_string('question', 'local_newsvnr') . '</div>';
                            $output .= '<div class="detail-quiz"><i class="fa fa-clock-o mr-1" aria-hidden="true"></i>' . convertTimeExam($quiz->timelimit) . '</div>';
                            $output .= '<div class="detail-quiz">' . $countattempt->count . ' ' . get_string('attempt', 'local_newsvnr') . '</div>';
                            $output .= '</div>';
                            $output .= '</div>';
                    }
                    $output .= '</div>';
                }
                $output .= '</div>';
            }
        } else {
            $output .= '<div class="alert alert-warning" role="alert">'.get_string('nodata','local_newsvnr').'</div>';
        }
        $data['result'] = $output;
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        break;
    default:
        break;
}
die;