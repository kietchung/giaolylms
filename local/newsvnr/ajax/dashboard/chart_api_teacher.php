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
require_once $CFG->dirroot . '/blocks/analytics_graphs/lib.php';
require_once $CFG->dirroot . '/blocks/dedication/dedication_lib.php';
require_once $CFG->dirroot . '/course/lib.php';

require_login();
$PAGE->set_context(context_system::instance());
$pagesize = optional_param('pagesize', 10, PARAM_INT);
$pagetake = optional_param('take', 0, PARAM_INT);
$pageskip = optional_param('skip', 0, PARAM_INT);
$q        = optional_param('q', '', PARAM_RAW);
$action   = optional_param('action', null, PARAM_RAW);

switch ($action) {
    case 'quiz_chart':
        $response = new stdClass;
        $sql      = "SELECT ROW_NUMBER() OVER (ORDER BY q.id) AS RowNum, q.id quizid, q.course, c.fullname coursename, q.name quizname, CONVERT(DECIMAL(10,2),qg.grade) grade
                FROM mdl_quiz q
                    LEFT JOIN mdl_quiz_grades qg ON q.id = qg.quiz
                    LEFT JOIN mdl_course c ON q.course = c.id
                WHERE qg.userid = :userid";
        $data            = $DB->get_records_sql($sql, ['userid' => 276]);
        $coursenames     = [];
        $grades          = [];
        $attemptarr      = [];
        $lowestgrades    = [];
        $temp_coursename = -1;

        foreach ($data as $key => $value) {
            $grademin = -1;
            if ($temp_coursename == -1) {
                $coursenames[]   = trim($value->coursename);
                $temp_coursename = 0;
            }
            if ($coursenames) {
                foreach ($coursenames as $keycoursename => $coursename) {
                    if ($coursename === trim($value->coursename)) {
                        continue;
                    }

                }
            }

            $gradeobj            = new stdClass;
            $gradeobj->name      = $value->quizname;
            $gradeobj->y         = (int) $value->grade;
            $grades[]            = $gradeobj;
            $gradeattempts       = new stdClass;
            $gradeattempts->name = $value->quizname;
            $gradeattempts->id   = $value->quizname;
            $attempts            = $DB->get_records('quiz_attempts', ['quiz' => $value->quizid, 'state' => 'finished', 'userid' => 276]);
            foreach ($attempts as $attempt) {
                $temp_attemptarr = [];
                if ($grademin == -1) {
                    $grademin = round($attempt->sumgrades, 2);
                }
                if (round($attempt->sumgrades, 2) < $grademin) {
                    $grademin = round($attempt->sumgrades, 2);
                }
                $temp_attemptarr[] = $attempt->attempt;
                $temp_attemptarr[] = round($attempt->sumgrades, 2);
                $attemptarr[]      = $temp_attemptarr;
            }
            $lowestgrades[] = $grademin;
        }
        $response->coursenames  = $coursenames;
        $response->grades       = $grades;
        $response->attemptarr   = $attemptarr;
        $response->lowestgrades = $lowestgrades;
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        die;
    case 'listcourse_grid':
        $userid   = $USER->id;
        $odersql  = "";
        $wheresql = "WHERE ra.roleid= 3 AND u.id = $userid";
        if ($q) {
            $wheresql = "WHERE ra.roleid= 3 AND u.id = $userid AND c.fullname like N'%$q%'";
        }
        if ($pagetake == 0) {
            $ordersql = "RowNum";
        } else {
            $ordersql = "RowNum OFFSET $pageskip ROWS FETCH NEXT $pagetake ROWS only";
        }
        $sql = "SELECT *, (SELECT COUNT(c.id)
                            FROM mdl_role_assignments AS ra
                                JOIN mdl_user AS u ON u.id= ra.userid
                                JOIN mdl_user_enrolments AS ue ON ue.userid=u.id
                                JOIN mdl_enrol AS e ON e.id=ue.enrolid
                                JOIN mdl_course AS c ON c.id=e.courseid
                                JOIN mdl_context AS ct ON ct.id=ra.contextid AND ct.instanceid= c.id
                                JOIN mdl_role AS r ON r.id= ra.roleid
                            $wheresql) AS total
                FROM (SELECT ROW_NUMBER() OVER (ORDER BY c.id) AS RowNum, c.*
                        FROM mdl_role_assignments AS ra
                            JOIN mdl_user AS u ON u.id= ra.userid
                            JOIN mdl_user_enrolments AS ue ON ue.userid=u.id
                            JOIN mdl_enrol AS e ON e.id=ue.enrolid
                            JOIN mdl_course AS c ON c.id=e.courseid
                            JOIN mdl_context AS ct ON ct.id=ra.contextid AND ct.instanceid= c.id
                            JOIN mdl_role AS r ON r.id= ra.roleid
                        $wheresql) AS Mydata
                ORDER BY $ordersql";
        $courses = $DB->get_records_sql($sql);
        $data    = [];
        foreach ($courses as $course) {
            $obj                  = new stdClass;
            $coursenameurl        = $CFG->wwwroot . '/course/view.php?id=' . $course->id;
            $obj->coursename      = '<a href="' . $coursenameurl . '" target="_blank">' . $course->fullname . '</a>';
            $studenttotal         = '<a href="javascript:;" onclick="viewStudentDetail(' . $course->id . ')">' . count(get_listuser_in_course($course->id)) . '</a>';
            $obj->studenttotal    = $studenttotal;
            $obj->coursestartdate = convertunixtime('d/m/Y', $course->startdate);
            if($course->enddate == 0) {
                $obj->courseenddate   = '-';
            } else {
                $obj->courseenddate   = convertunixtime('d/m/Y', $course->enddate);
            }
            // $obj->comp = competency_api::count_competencies_in_course($course->id);
            $coursecompletionurl   = $CFG->wwwroot . '/course/completion.php?id=' . $course->id;
            $obj->coursecompletion = '<a href="' . $coursecompletionurl . '" target="_blank">' . $DB->count_records('course_completion_criteria', ['course' => $course->id]) . '</a>';
            $obj->coursemodules    = '<a href="javascript:;" onclick="viewModuleDetail(' . $course->id . ')">' . $DB->count_records('course_modules', ['course' => $course->id]) . '</a>';
            if ($course->startdate > time()) {
                $obj->status = '<span class="badge text-white teacher-bg-1 font-weight-bold rounded p-2">' . get_string('plan', 'theme_moove') . '</span>';
            } else if ($course->enddate != 0 && $course->enddate <= time()) {
                $obj->status = '<span class="badge text-white teacher-bg-2 font-weight-bold rounded p-2">' . get_string('finished', 'theme_moove') . '</span>';
            } else if ($course->startdate <= time() && $course->enddate > time()) {
                $obj->status = '<span class="badge text-white teacher-bg-3 font-weight-bold rounded p-2">' . get_string('progressing', 'theme_moove') . '</span>';
            } else {
                $obj->status = '<span class="badge text-white teacher-bg-3 font-weight-bold rounded p-2">' . get_string('progressing', 'theme_moove') . '</span>';
            }
            $obj->total = $course->total;
            $data[]     = $obj;
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die;
    case 'view_students_detail':
        $courseid = optional_param('courseid', '', PARAM_INT);
        $role     = optional_param('role', '', PARAM_INT);
        $data     = array();
        $odersql  = "";
        $wheresql = "WHERE ra.roleid=$role AND c.id = $courseid";
        if ($q) {
            $wheresql = "WHERE ra.roleid=$role AND c.id = $courseid AND CONCAT(u.firstname, ' ', u.lastname) like N'%$q%'";
        }
        if ($pagetake == 0) {
            $ordersql = "RowNum";
        } else {
            $ordersql = "RowNum OFFSET $pageskip ROWS FETCH NEXT $pagetake ROWS only";
        }
        $sql = "SELECT *,(SELECT COUNT(u.id)
                            FROM mdl_user_enrolments ue
                                JOIN mdl_enrol e ON ue.enrolid = e.id
                                JOIN mdl_course c ON e.courseid = c.id
                                JOIN mdl_role_assignments ra ON ra.userid = ue.userid
                                JOIN mdl_user u ON u.id = ra.userid
                                JOIN mdl_context as ct on ra.contextid= ct.id AND ct.instanceid = c.id
                                LEFT JOIN mdl_course_completions cc ON cc.userid = c.id $wheresql) AS total
                FROM (SELECT ROW_NUMBER() OVER (ORDER BY c.id) AS RowNum,c.*,cc.timecompleted,u.usercode, u.id userid
                      FROM mdl_user_enrolments ue
                        JOIN mdl_enrol e ON ue.enrolid = e.id
                        JOIN mdl_course c ON e.courseid = c.id
                        JOIN mdl_role_assignments ra ON ra.userid = ue.userid
                        JOIN mdl_user u ON u.id = ra.userid
                        JOIN mdl_context as ct on ra.contextid= ct.id AND ct.instanceid = c.id
                        LEFT JOIN mdl_course_completions cc ON cc.userid = c.id AND cc.course = c.id $wheresql) AS Mydata
                ORDER BY $ordersql";
        $get_list = $DB->get_records_sql($sql);
        foreach ($get_list as $value) {
            $get_grade        = get_finalgrade_student($value->userid, $value->id);
            $obj              = new stdCLass();
            $course           = $DB->get_record('course', ['id' => $value->id]);
            $process          = round(\core_completion\progress::get_course_progress_percentage($course, $value->userid));
            $obj->number      = $value->rownum;
            $obj->usercode    = ($value->usercode) ? $value->usercode : "-";
            $userinfo         = $DB->get_record('user', ['id' => $value->userid]);
            $obj->studentname = $OUTPUT->user_picture($userinfo, array('size' => 35)) . fullname($userinfo);
            $obj->coursename  = $value->fullname;
            $obj->courseid    = $value->shortname;
            $timespenttotal   = 0;
            $dm               = new block_dedication_manager($course, $course->startdate, time(), 3600);
            $obj_user         = new stdClass;
            $obj_user->id     = $value->userid;
            $rows             = $dm->get_user_dedication($obj_user);
            foreach ($rows as $index => $row) {
                $timespenttotal += $row->dedicationtime;
            }
            $obj->spenttime = block_dedication_utils::format_dedication($timespenttotal);
            $obj->total     = $value->total;
            if (!empty($get_grade)) {
                $obj->rank       = $get_grade->rank;
                $obj->gradefinal = $get_grade->gradefinal;
            } else {
                $obj->rank       = '-';
                $obj->gradefinal = '-';
            }
            $obj->process = ($role == 5) ? $process . '%' : "-";
            if ($value->timecompleted != null) {
                $obj->timecompleted = convertunixtime('d/m/Y', $value->timecompleted, 'Asia/Ho_Chi_Minh');
                $obj->status        = "<span class='badge badge-success rounded p-2'>" . get_string('org_completed', 'local_newsvnr') . "</span>";
            } else {
                $obj->timecompleted = '-';
                $obj->status        = "<span class='badge badge-danger rounded p-2'>" . get_string('org_incomplete', 'local_newsvnr') . "</span>";
            }
            $data[] = $obj;
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die;
    case 'module_chart':
        $courseid   = optional_param('courseid', '', PARAM_INT);
        $response   = new stdClass;
        $series_sql = "SELECT m.name, c.fullname,COUNT(module) AS module
                FROM mdl_course_modules cm
                    LEFT JOIN mdl_modules m ON cm.module = m.id
                    LEFT JOIN mdl_course c on c.id = cm.course
                WHERE cm.course = :courseid
                GROUP BY m.name, c.fullname";

        $data       = $DB->get_records_sql($series_sql, ['courseid' => $courseid]);
        $categories = $temp_series = $series = $modules = $drilldownc = $temp_drilldown = [];
        $course     = "";
        foreach ($data as $key => $value) {
            if ($course == "") {
                $course = $value->fullname;
            }
            if (!in_array($value->name, $categories)) {
                $categories[] = ucfirst($value->name);
            }
            $obj            = new stdClass;
            $obj->name      = ucfirst($value->name);
            $obj->y         = (int) $value->module;
            $obj->drilldown = ucfirst($value->name);
            $temp_series[]  = $obj;
            // Lấy dữ liệu drilldown cho module
            $modulename    = $value->name;
            $drilldown_sql = "SELECT *
                            FROM (SELECT mn.id, mn.name
                                    FROM mdl_course_modules cm
                                        JOIN mdl_modules m ON cm.module = m.id
                                        JOIN mdl_$modulename mn ON cm.instance = mn.id
                                    WHERE m.name = :modulename AND cm.course = :courseid
                                ) Mydata";
            $modules_detail      = $DB->get_records_sql($drilldown_sql, ['modulename' => $modulename, 'courseid' => $courseid]);
            $obj_drilldown       = new stdclass;
            $obj_drilldown->name = ucfirst($value->name);
            $obj_drilldown->id   = ucfirst($value->name);
            $temp_drilldown      = [];
            $count_module        = [];
            foreach ($modules_detail as $drilldown) {
                // if(!in_array($drilldown->name, $count_module)) {
                //     $count_module[] = $dri
                // }
                $temp_drilldown[] = [$drilldown->name, 0];
                foreach ($temp_drilldown as $key => $temp) {
                    if ($drilldown->name == $temp[0]) {
                        $temp_drilldown[$key][1] = $temp_drilldown[$key][1] + 1;
                    }

                }
            }
            $obj_drilldown->data = $temp_drilldown;
            $drilldownc[]        = $obj_drilldown;
        }
        $obj                  = new stdclass;
        $obj->name            = "Modules";
        $obj->colorByPoint    = true;
        $obj->data            = $temp_series;
        $series[]             = $obj;
        $response->course     = $course;
        $response->series     = $series;
        $response->drilldown  = $drilldownc;
        $response->categories = $categories;
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        die;

    case 'get_listcourse':
        $userid  = $USER->id;
        $data    = [];
        $courses = get_list_courseinfo_by_teacher($userid);
        foreach ($courses as $course) {
            $obj           = new stdclass();
            $obj->name     = $course->fullname;
            $obj->courseid = $course->id;
            $data[]        = $obj;
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die;
    case 'grade_view':
        $users    = get_listuser_in_course(150);
        $gradeavg = [];

        $modulesname = [];
        $data_module = [];
        // $count_module_incourse = $DB->count_records('')
        foreach ($users as $user) {
            $userid      = $user->id;
            $usersname[] = $user->userfullname;
            $grades_sql  = "SELECT *
                        FROM mdl_grade_grades gg
                            LEFT JOIN mdl_grade_items gi ON gg.itemid = gi.id
                        WHERE gi.courseid = :courseid AND gg.userid = :userid";
            $grades       = $DB->get_records_sql($grades_sql, ['courseid' => 150, 'userid' => $userid]);
            $count_module = 0;
            foreach ($grades as $grade) {
                if ($grade->itemmodule == '') {
                    continue;
                }
                if (!in_array($grade->itemname, $modulesname)) {
                    $modulesname[]                 = $grade->itemname;
                    $data_module[$grade->itemname] = '';
                    if ($grade->finalgrade == '') {
                        $grade->finalgrade = 0;
                    }
                    if (in_array($grade->itemname, $data_module)) {
                        array_push($data_module[$grade->itemname], $grade->finalgrade);
                    } else {
                        $data_module[$grade->itemname] .= (int) round($grade->finalgrade, 2) . ',';
                    }
                } else {
                    if ($grade->finalgrade == '') {
                        $grade->finalgrade = 0;
                    }
                    if (in_array($grade->itemname, $data_module)) {
                        array_push($data_module[$grade->itemname], $grade->finalgrade);
                    } else {
                        $data_module[$grade->itemname] .= (int) round($grade->finalgrade, 2) . ',';
                    }
                }
            }

        }
        $data = [];
        foreach ($data_module as $keymodule => $module) {
            $obj       = new stdClass;
            $obj->name = $keymodule;
            str_replace('"', '', $module);
            $str_to_array = explode(",", $module);
            array_pop($str_to_array);
            $temp = [];
            foreach ($str_to_array as $value) {
                $temp[] = (int) $value;
            }
            $obj->data = $temp;
            $data[]    = $obj;
        }
        $obj                 = new stdClass;
        $obj->name           = "AVG grade";
        $obj->type           = "spline";
        $obj->data           = [5, 1, 10, 9, 4];
        $data[]              = $obj;
        $response->usersname = $usersname;
        // $response->gradeavg = $grades;
        $response->modulesdata = $data;
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        die;
    case 'get_list_completion_course':
        $courseid  = optional_param('courseid', null, PARAM_INT);
        $i    = $max    = $min    = 0;
        $course    = $DB->get_record('course', ['id' => $courseid]);
        $cinfo     = new completion_info($course);
        $list_user = $DB->get_records_sql("SELECT u.*
                                            FROM mdl_user_enrolments ue
                                                JOIN mdl_enrol e ON ue.enrolid = e.id
                                                JOIN mdl_course c ON e.courseid = c.id
                                                JOIN mdl_role_assignments ra ON ra.userid = ue.userid
                                                JOIN mdl_user u ON u.id = ra.userid
                                                JOIN mdl_context as ct on ra.contextid= ct.id AND ct.instanceid = c.id
                                            where ra.roleid=5 AND c.id =:courseid", ['courseid' => $course->id]);
        if ($list_user) {
            foreach ($list_user as $user) {
                // Kiểm tra số học viên chưa hoàn thành khóa
                $iscomplete = $cinfo->is_course_complete($user->id);
                if ($iscomplete == false) {
                    $i++;
                }
                $list_grade = get_finalgrade_student($user->id, $course->id);
                if (!empty($list_grade)) {
                    $max = max($max, $list_grade->gradefinal);
                    if ($min == 0) {
                        $min = $list_grade->gradefinal;
                    }
                    $min = min($min, $list_grade->gradefinal);
                }
            }
            $obj                      = new stdClass();
            $obj->studentunfinish     = $i;
            $obj->studentfinish       = count($list_user) - $i;
            $obj->gradeavg            = get_course_grade_avg($courseid)[0]->courseavg;
            $sum                      = $i + $obj->studentfinish;
            $obj->title               = "<div class='position-relative'><p class='m-0 text-center text-dark' style='font-size:30px'>" . round(($obj->studentfinish * 100) / $sum, 2) . "%</p><span style='font-size:14px; color:#AFAFAF'>" . get_string('progress', 'local_newsvnr') . "</span></div>";
            $obj_series               = new stdClass;
            $obj_series->name         = get_string('progress', 'local_newsvnr');
            $obj_series->colorByPoint = true;
            $obj_series->data         = [(object) ['name' => get_string('studentcompleted', 'theme_moove'), 'y' => $obj->studentfinish, 'color' => '#1DB34F'], ['name' => get_string('studentstudying', 'theme_moove'), 'y' => $obj->studentunfinish, 'color' => '#DD4B39']];
            $obj->series              = $obj_series;
            echo json_encode($obj, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(get_string('nodata', 'local_newsvnr'), JSON_UNESCAPED_UNICODE);
        }
        die;
    default:
        break;
}
