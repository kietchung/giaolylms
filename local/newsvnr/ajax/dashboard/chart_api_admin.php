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
$action = optional_param('action', null, PARAM_RAW);
$PAGE->set_context(context_system::instance());
$user   = $DB->get_record('user', ['id' => $USER->id]);
$data   = [];
$output = '';
require_login();

switch ($action) {
    case 'coursestatus':
        require_once "{$CFG->libdir}/completionlib.php";
        $currenttime = time();
        if ($CFG->sitetype == MOODLE_EDUCATION && $user->divisionid && !is_siteadmin()) {
            $coursefinish = $DB->get_record_sql("SELECT COUNT(*) as count FROM {course}
                                                    WHERE enddate < $currenttime AND enddate != 0 AND divisionid = :divisionid",
                ['divisionid' => $user->divisionid]);
            $courseopening = $DB->get_record_sql("SELECT COUNT(*) as count FROM {course}
                                                    WHERE (enddate > $currenttime OR enddate = 0) AND startdate < $currenttime AND divisionid = :divisionid",
                ['divisionid' => $user->divisionid]);
            $coursefuture = $DB->get_record_sql("SELECT COUNT(*) as count FROM {course}
                                                    WHERE startdate > $currenttime AND divisionid = :divisionid",
                ['divisionid' => $user->divisionid]);
        } else {
            $coursefinish  = $DB->get_record_sql("SELECT COUNT(*) as count FROM {course} WHERE enddate < $currenttime AND enddate != 0");
            $courseopening = $DB->get_record_sql("SELECT COUNT(*) as count FROM {course} WHERE (enddate > $currenttime OR enddate = 0) AND startdate < $currenttime");
            $coursefuture  = $DB->get_record_sql("SELECT COUNT(*) as count FROM {course} WHERE startdate > $currenttime");
        }
        $data[0] = ['name' => get_string('coursefinished', 'local_newsvnr'), 'y' => (int) $coursefinish->count];
        $data[1] = ['name' => get_string('courseopening', 'local_newsvnr'), 'y' => (int) $courseopening->count];
        $data[2] = ['name' => get_string('coursefuture', 'local_newsvnr'), 'y' => (int) $coursefuture->count];
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        break;
    case 'access_site':
        $strdate           = isset($_GET['strdate']) ? $_GET['strdate'] : "";
        $strdate_unix      = strtotime($strdate);
        $now               = time();
        $sevendaysago_unix = time() - (86400 * 7);
        $sixdaysago_unix   = time() - (86400 * 6);
        $fivedaysago_unix  = time() - (86400 * 5);
        $fourndaysago_unix = time() - (86400 * 4);
        $threedaysago_unix = time() - (86400 * 3);
        $twodaysago_unix   = time() - (86400 * 2);
        $onedaysago_unix   = time() - 86400;
        $sevendaysago_date = convertunixtime('M Y, d', time() - (86400 * 6), 'Asia/Ho_Chi_Minh');
        $sixdaysago_date   = convertunixtime('M Y, d', time() - (86400 * 5), 'Asia/Ho_Chi_Minh');
        $fivedaysago_date  = convertunixtime('M Y, d', time() - (86400 * 4), 'Asia/Ho_Chi_Minh');
        $fourdaysago_date  = convertunixtime('M Y, d', time() - (86400 * 3), 'Asia/Ho_Chi_Minh');
        $threedaysago_date = convertunixtime('M Y, d', time() - (86400 * 2), 'Asia/Ho_Chi_Minh');
        $twodaysago_date   = convertunixtime('M Y, d', time() - (86400 * 1), 'Asia/Ho_Chi_Minh');
        $onedaysago_date   = convertunixtime('M Y, d', time());

        $i        = 1;
        $unixdate = 0;
        $date;
        $lastacces = $DB->get_field_sql("SELECT MAX(timecreated) lastacces
                                        FROM mdl_logstore_standard_log
                                        WHERE [action] = 'loggedin'
                                        ");
        $series      = array();
        $temp_series = array();
        $categories  = array();
        for ($i = 1; $i <= 7; $i++) {
            if ($i == 1) {
                $unixdate       = $onedaysago_unix;
                $befor_unixdate = $now;
                $date           = $onedaysago_date;
            } elseif ($i == 2) {
                $unixdate       = $twodaysago_unix;
                $befor_unixdate = $onedaysago_unix;
                $date           = $twodaysago_date;
            } elseif ($i == 3) {
                $unixdate       = $threedaysago_unix;
                $befor_unixdate = $twodaysago_unix;
                $date           = $threedaysago_date;
            } elseif ($i == 4) {
                $unixdate       = $fourndaysago_unix;
                $befor_unixdate = $threedaysago_unix;
                $date           = $fourdaysago_date;
            } elseif ($i == 5) {
                $unixdate       = $fivedaysago_unix;
                $befor_unixdate = $fourndaysago_unix;
                $date           = $fivedaysago_date;
            } elseif ($i == 6) {
                $unixdate       = $sixdaysago_unix;
                $befor_unixdate = $fivedaysago_unix;
                $date           = $sixdaysago_date;
            } elseif ($i == 7) {
                $unixdate       = $sevendaysago_unix;
                $befor_unixdate = $sixdaysago_unix;
                $date           = $sevendaysago_date;
            }
            $wheresql = "WHERE lsl.action = 'loggedin' AND (lsl.timecreated BETWEEN :unixdate AND :beforunixdate)";
            if ($CFG->sitetype == MOODLE_EDUCATION && $user->divisionid && !is_siteadmin()) {
                $sql = "SELECT COUNT(lsl.id) log
                        FROM mdl_logstore_standard_log lsl
                            JOIN mdl_user u on u.id = lsl.objectid and u.divisionid = $user->divisionid
                        $wheresql";

            } else {
                $sql = "SELECT COUNT(lsl.id) log
                        FROM mdl_logstore_standard_log lsl
                        $wheresql";
            }
            $get_log = $DB->get_field_sql($sql, ['beforunixdate' => $befor_unixdate, 'unixdate' => $unixdate]);

            $categories[] = $date;
            $series[]     = (int) $get_log;
        }

        $response             = new stdClass();
        $response->series     = array_reverse($series);
        $response->seriesname = get_string('accesssite', 'local_newsvnr');
        $response->categories = array_reverse($categories);
        $response->lastaccess = '<span id="last-access" style="font-size: 13px; color: grey;">' . get_string('lastaccesstime', 'theme_moove', convertunixtime('h:i A', $lastacces), true) . '</span>';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        break;
    case 'noticeadmin':
        $obj = new stdClass;
        if ($CFG->sitetype == MOODLE_EDUCATION && $user->divisionid && !is_siteadmin()) {
            $sql = "SELECT COUNT(c.id) course
                        FROM  mdl_context ct
                            JOIN mdl_course c ON c.id = ct.instanceid
                        WHERE ct.contextlevel = 50 AND c.id <> 1 AND c.divisionid = $user->divisionid
                        AND (NOT EXISTS (SELECT 1
                                         FROM mdl_role_assignments r
                                         WHERE r.contextid = ct.id AND r.roleid = :roleid))";

            $courseemptysql = "SELECT c.id, c.fullname, COUNT(cm.module) module
                    FROM mdl_course_modules cm RIGHT JOIN mdl_course c ON cm.course = c.id
                    WHERE c.divisionid = $user->divisionid
                    GROUP BY c.id, c.fullname
                    HAVING COUNT(cm.module) <= 1
                    ORDER BY c.fullname";
        } else {
            $sql = "SELECT COUNT(c.id) course
                        FROM  mdl_context ct
                            JOIN mdl_course c ON c.id = ct.instanceid
                        WHERE ct.contextlevel = 50 AND c.id <> 1
                        AND (NOT EXISTS (SELECT 1
                                         FROM mdl_role_assignments r
                                         WHERE r.contextid = ct.id AND r.roleid = :roleid))";

            $courseemptysql = "SELECT c.id, c.fullname, COUNT(cm.module) module
                    FROM mdl_course_modules cm RIGHT JOIN mdl_course c ON cm.course = c.id
                    GROUP BY c.id, c.fullname
                    HAVING COUNT(cm.module) <= 1
                    ORDER BY c.fullname";
        }

        $coursenoteacher      = $DB->get_field_sql($sql, ['roleid' => 3]);
        $obj->coursenoteacher = (int) $coursenoteacher;

        $coursenostudent      = $DB->get_field_sql($sql, ['roleid' => 5]);
        $obj->coursenostudent = (int) $coursenostudent;

        $courseempty      = $DB->get_records_sql($courseemptysql);
        $obj->courseempty = count($courseempty);

        $obj->reviewcompetency = $DB->count_records('competency_usercomp', ['status' => 1]);

        $obj->courselibraryrequest = $DB->count_records('library_module', ['approval' => 0]);

        $obj->courserequests = $DB->count_records('course_request');
        echo json_encode($obj, JSON_UNESCAPED_UNICODE);
        break;
    case 'studentincoursechart':
        $user = $DB->get_record('user',['id' => $USER->id]);
        $listcourse = $DB->get_records('course',['visible' => 1,'divisionid' => $user->divisionid]);
        if($listcourse) {
            foreach ($listcourse as $value) {
                $obj         = new stdClass();
                $obj->name   = $value->fullname;
                $liststudent = get_listuser_in_course($value->id);
                $sum         = 0;
                foreach ($liststudent as $student) {
                    $studentmdfinish = $DB->get_record_sql('SELECT COUNT(*) as count
                                            FROM mdl_course_modules_completion cmc
                                            JOIN mdl_course_modules cm ON cm.id = cmc.coursemoduleid
                                        WHERE cm.course = :courseid AND cmc.userid = :userid', ['courseid' => $value->id, 'userid' => $student->id]);
                    if ($studentmdfinish->count > 0) {
                        $sum++;
                    }
                }
                $obj->y            = count($liststudent);
                $studentincourse[] = $obj;
                $studentjoincourse[]  = $sum;
            }
            $data['avggradecourse'] = $studentjoincourse;
            $data['avggrade']       = $studentincourse;
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        break;
    default:
        break;
}
die();
