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
$data   = [];
$output = '';
switch ($action) {
    case 'viewcourse':
        $listcourse     = get_list_course_by_student($USER->id);
        $theme_settings = new theme_settings();
        if(!empty($listcourse)) {
            $output .= '<div class="row">';
            foreach ($listcourse as $value) {
                $courseobj   = new \core_course_list_element($value);
                $arr         = $theme_settings::role_courses_teacher_slider_block_course_recent($value->id);
                $teachername = $arr->fullnamet;
                $course      = $DB->get_record('course', ['id' => $value->id]);
                $category    = $DB->get_record_sql("SELECT ct.name FROM {course} c
                                                                JOIN {course_categories} ct on ct.id = c.category
                                                        WHERE c.id =:courseid", ['courseid' => $value->id]);
                $courselink  = $CFG->wwwroot . "/course/view.php?id=" . $value->id;
                $process     = round(\core_completion\progress::get_course_progress_percentage($course, $USER->id));
                $courseimage = $theme_settings::get_course_images($courseobj, $courselink);
                $output .= '<div class="col-md-6 col-sm-12 pd-0">';
                $output .= '<div class="card-body row">';
                $output .= '<div class="col-md-3 col-sm-6 courseimage">' . $courseimage . '</div>';
                $output .= '<div class="media-body col-md-9 col-sm-6">';
                $output .= '<a class="font-bold mb-1" target="_blank" href="' . $courselink . '">' . $value->fullname . '</a><br>';
                $output .= '<span class="grey mb-1">' . $category->name . '</span><br>';
                $output .= '<p>Giáo viên: ' . $teachername . '</p>';
                $output .= '</div>'; // end col
                $output .= '<div class="col-md-2 text-left">
                                <div class="progress-circle custom" data-progress="' . $process . '"></div>
                            </div>';
                $output .= '</div>'; //end row
                $output .= '</div>'; //end col
            }
        $output .= '</div>';
        } else {
            $output .= '<div class="alert alert-warning" role="alert">'.get_string('nodata','local_newsvnr').'</div>';
        }
        $data['result'] = $output;
        $data['title']  = '<h5 class="card-title d-inline">'.get_string('mycourses','local_newsvnr').'</h5>';
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        break;
    case 'viewbadge':
        require_once $CFG->dirroot . '/badges/renderer.php';
        $badges = badges_get_user_badges($USER->id, 0, null, null, null, true);
        if(!empty($badges)) {
            $output .= '<ul class="badges">';
            foreach ($badges as $badge) {
                $context  = ($badge->type == BADGE_TYPE_SITE) ? context_system::instance() : context_course::instance($badge->courseid);
                $imageurl = moodle_url::make_pluginfile_url($context->id, 'badges', 'badgeimage', $badge->id, '/', 'f1', false);
                $name     = html_writer::tag('span', $badge->name, array('class' => 'badge-name'));
                $url      = new moodle_url('/badges/badge.php', array('hash' => $badge->uniquehash));
                $image    = html_writer::empty_tag('img', array('src' => $imageurl, 'class' => 'badge-image'));
                $output .= '<li><a target="_blank" href="' . $url . '">' . $image . '' . $name . '</a></li>';
            }
        } else {
            $output .= '<div class="alert alert-warning" role="alert">'.get_string('nodata','local_newsvnr').'</div>';
        }
        $output .= '</ul>';
        $data['result'] = $output;
        $data['title']  = '<h5 class="card-title d-inline">'.get_string('badge','local_newsvnr').' (' . count($badges) . ')</h5>';
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        break;
    case 'viewcompetencyplan':
        $competencyplan       = 'competencyplan';
        $competency           = 'competency';
        $competencyenterprise = 'competencyenterprise';
        $tab                  = 'competency';
        $output .= '<ul class="nav-tabs nav multi-tab mb-3" tab="competency">';
        $output .= '<li class="nav-item"><a href="javascript:void(0)" onclick="tabClick(\'' . $competencyplan . '\',\'' . $tab . '\')" class="nav-link active" data="competencyplan">'.get_string('personalplan','local_newsvnr').'</a></li>';
        $output .= '</ul>';
        $competencyplans = \theme_moove\util\extras::get_user_competency_plans($USER);
        $output .= '<div class="tab-content" tab="competency" id="personalplan">';
        $output .= '<div data="competencyplan" class="tab-pane active">';
        if(!empty($competencyplans)) {
            foreach ($competencyplans as $value) {
                $randomcolor = str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT) . str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT) . str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT);
                $output .= '<div class="card">';
                $output .= '<div class="card-body d-flex justify-content-between pb-0 pt-0 align-items-center" style="border-left:3px solid #'.$randomcolor.'">';
                $output .= '<div>';
                $output .= '<a href="'.$CFG->wwwroot.'/admin/tool/lp/plan.php?id='.$value['id'].'" target="_blank" class="card-title">' . $value['name'] . '</a>';
                $duedate = $DB->get_field('competency_plan', 'duedate', ['id' => $value['id']]);
                $date    = ($duedate != 0) ? convertunixtime('l, d m Y, H:i A', $duedate, 'Asia/Ho_Chi_Minh') : ''.get_string('nodatatable','local_newsvnr').'';
                $output .= '<p class="card-text grey">'.get_string('deadline','local_newsvnr').': ' . $date . '</p>';
                $output .= '</div>';
                $output .= '<div class="text-right">
                                <div class="progress-circle custom" data-progress="' . round($value['proficientcompetencypercentage']) . '"></div>
                            </div>';
                $output .= '</div>';
                $output .= '</div>';
                $output .= '<hr>';
            }
        } else {
            $output .= '<div class="alert alert-warning" role="alert">'.get_string('nodata','local_newsvnr').'</div>';
        }
        $output .= '</div>';
        $output .= '<div data="competency" class="tab-pane"></div>';
        $output .= '<div data="competencyenterprise" class="tab-pane"></div>';
        $output .= '</div>'; // end tab
        $data['result'] = $output;
        $data['title'] = '';
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        break;
    case 'viewexam':
        $output .= '';
        $exams = $DB->get_records_sql('SELECT * FROM mdl_exam_user eu
                                        JOIN mdl_exam e ON e.id = eu.examid
                                    WHERE eu.userid =:userid', ['userid' => $USER->id]);
        if(!empty($exams)) {
            foreach ($exams as $exam) {
                $subjects = $DB->get_records_sql('SELECT DISTINCT esx.id, es.id AS subjectid ,es.name, esx.subjectid, e.name AS examname
                                                    FROM mdl_exam_subject_exam esx
                                                    JOIN mdl_exam e ON esx.examid = e.id
                                                    JOIN mdl_exam_subject es ON esx.subjectid = es.id
                                                WHERE e.id =:examid', ['examid' => $exam->examid]);
                $output .= '<div class="exam-title mt-1">' . $exam->name . '</div>';
                $output .= '<div class="row" style="padding:0 15px">';
                foreach ($subjects as $subject) {
                    $output .= '<div class="col-md-6 pd-0">';
                    $output .= '<div class="subject-title mb-1"><i class="fa fa-clock-o mr-2" aria-hidden="true"></i>' . $subject->name . '</div>';
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
                        $output .= '<div class="pl-3 pt-2 pb-2 module-quiz-online mr-2">';
                        $output .= '<div class=""><a class="text-default" target="_blank" href="'.$url.'">' . $img . '' . $quiz->name . '</a><a class="float-right mr-2" href="' . $url . '" target="_blank">' . get_string('enterexam', 'local_newsvnr') . '</a></div>';
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
        $data['title']  = '<h5 class="card-title d-inline">'.get_string('exam','local_newsvnr').'</h5>';
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        break;
    default:
        break;
}
die();
