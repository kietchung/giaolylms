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

define('AJAX_SCRIPT', false);

require_once __DIR__ . '/../../../../config.php';
require_once $CFG->dirroot . '/local/newsvnr/lib.php';
require_once $CFG->dirroot . '/blocks/dedication/dedication_lib.php';
require_login();
$PAGE->set_context(context_system::instance());

$pagesize = optional_param('pagesize', 10, PARAM_INT);
$pagetake = optional_param('take', 0, PARAM_INT);
$pageskip = optional_param('skip', 0, PARAM_INT);
$q        = optional_param('q', '', PARAM_RAW);
$data     = array();
$odersql  = "";
$wheresql = "WHERE ra.roleid=5 AND u.id = $USER->id";
if ($q) {
    $wheresql = "WHERE ra.roleid=5 AND u.id = $USER->id AND c.fullname like N'%$q%'";
}
if ($pagetake == 0) {
    $ordersql = "RowNum";
} else {
    $ordersql = "RowNum OFFSET $pageskip ROWS FETCH NEXT $pagetake ROWS only";
}
$sql = "SELECT *,(SELECT COUNT(DiSTINCT c.id)
                    FROM mdl_user_enrolments ue
                        JOIN mdl_enrol e ON ue.enrolid = e.id
                        JOIN mdl_course c ON e.courseid = c.id
                        JOIN mdl_role_assignments ra ON ra.userid = ue.userid
                        JOIN mdl_user u ON u.id = ra.userid
                        JOIN mdl_context as ct on ra.contextid= ct.id AND ct.instanceid = c.id
                        LEFT JOIN mdl_course_completions cc ON cc.userid = c.id $wheresql) AS total
        FROM (SELECT ROW_NUMBER() OVER (ORDER BY c.id) AS RowNum,c.*,cc.timecompleted
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
    $get_grade      = get_finalgrade_student($USER->id, $value->id);
    if (!is_enrolled(context_course::instance($value->id), $USER)) {
        print_error('usernotincourse');
    }
    $dm = new block_dedication_manager($DB->get_record('course', ['id' => $value->id]), $value->timecreated, time(), 3600);
    // get timespent cho user
    $totaldedication = 0;
    $rows = $dm->get_user_dedication($USER);
    foreach ($rows as $index => $row) {
        $totaldedication += $row->dedicationtime;
        $rows[$index] = array(
            userdate($row->start_date),
            block_dedication_utils::format_dedication($row->dedicationtime),
            block_dedication_utils::format_ips($row->ips),
        );
    }
    $obj            = new stdCLass();
    $course         = $DB->get_record('course', ['id' => $value->id]);
    $process        = round(\core_completion\progress::get_course_progress_percentage($course, $USER->id));
    $obj->href      = $CFG->wwwroot . '/course/view.php?id=' . $value->id;
    if($rows) {
        $obj->timespent = $rows[0][1];
    } else {
        $obj->timespent = '-';
    }
    $obj->rownum    = $value->rownum;
    $obj->name      = $value->fullname;
    $obj->courseid  = $value->shortname;
    $obj->total     = $value->total;
    $obj->startdate = convertunixtime('d/m/Y', $value->startdate, 'Asia/Ho_Chi_Minh');
    $obj->enddate   = convertunixtime('d/m/Y', $value->enddate, 'Asia/Ho_Chi_Minh');
    if (!empty($get_grade)) {
        $obj->rank       = $get_grade->rank;
        $obj->gradefinal = $get_grade->gradefinal;
    } else {
        $obj->rank       = '-';
        $obj->gradefinal = '-';
    }
    $obj->process = $process . '%';
    if ($value->timecompleted != null) {
        $obj->timecompleted = convertunixtime('d/m/Y', $value->timecompleted, 'Asia/Ho_Chi_Minh');
    } else {
        $obj->timecompleted = '-';
    }
    $data[] = $obj;
}
echo json_encode($data, JSON_UNESCAPED_UNICODE);
die;
        

        // $view->header[] = get_string('userdedication', 'block_dedication', $OUTPUT->user_picture($USER, array('courseid' => $course->id)) . fullname($USER));
        // $view->header[] = get_string('period', 'block_dedication', (object) array('mintime' => userdate($mintime), 'maxtime' => userdate($maxtime)));
        // $view->header[] = get_string('perioddiff', 'block_dedication', format_time($maxtime - $mintime));
        // $view->header[] = get_string('totaldedication', 'block_dedication', block_dedication_utils::format_dedication($totaldedication));
        // $view->header[] = get_string('meandedication', 'block_dedication', block_dedication_utils::format_dedication(count($rows) ? $totaldedication / count($rows) : 0));

        // $view->table->head = array(get_string('sessionstart', 'block_dedication'), get_string('sessionduration', 'block_dedication'), 'IP');
        // $view->table->data = $rows;
