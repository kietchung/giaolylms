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
require_login();
$PAGE->set_context(context_system::instance());
$pagesize = optional_param('pagesize', 10, PARAM_INT);
$pagetake = optional_param('take', 0, PARAM_INT);
$pageskip = optional_param('skip', 0, PARAM_INT);
$q        = optional_param('q', '', PARAM_RAW);
$data   = [];
$output = '';
switch ($action) {
    case 'coursenoteacher':
        $wheresql = '';
        if($q) 
            $wheresql .= "AND c.fullname LIKE N'%$q%'";
        $sql = "SELECT c.id, c.fullname
                FROM  mdl_context ct
                    JOIN mdl_course c ON c.id = ct.instanceid
                WHERE ct.contextlevel = 50 AND c.id <> 1 $wheresql
                AND (NOT EXISTS (SELECT 1 
                                 FROM mdl_role_assignments r 
                                 WHERE r.contextid = ct.id AND r.roleid = :roleid))";
        $coursenoteacher = $DB->get_records_sql($sql, ['roleid' => 3]);
        foreach($coursenoteacher as $course) {
            $obj = new stdClass;
            $obj->value = $course->id;
            $obj->name = $course->fullname;
            $data[] = $obj;
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        break;
    case 'coursenostudent':
        $wheresql = '';
        if($q) 
            $wheresql .= "AND c.fullname LIKE N'%$q%'";
        $sql = "SELECT c.id, c.fullname
                FROM  mdl_context ct
                    JOIN mdl_course c ON c.id = ct.instanceid
                WHERE ct.contextlevel = 50 AND c.id <> 1 $wheresql
                AND (NOT EXISTS (SELECT 1 
                                 FROM mdl_role_assignments r 
                                 WHERE r.contextid = ct.id AND r.roleid = :roleid))";
        $coursenoteacher = $DB->get_records_sql($sql, ['roleid' => 5]);
        foreach($coursenoteacher as $course) {
            $obj = new stdClass;
            $obj->value = $course->id;
            $obj->name = $course->fullname;
            $data[] = $obj;
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        break;
    case 'courseempty':
        $obj = new stdClass;
        $wheresql = '';
        if($q) 
            $wheresql .= "WHERE c.fullname LIKE N'%$q%'";

        $courseemptysql = "SELECT c.id, c.fullname, COUNT(cm.module) module 
                            FROM mdl_course_modules cm RIGHT JOIN mdl_course c ON cm.course = c.id
                            $wheresql
                            GROUP BY c.id, c.fullname
                            HAVING COUNT(cm.module) <= 1
                            ORDER BY c.fullname";
        $courseempty = $DB->get_records_sql($courseemptysql);
        foreach($courseempty as $course) {
            $obj = new stdClass;
            $obj->value = $course->id;
            $obj->name = $course->fullname;
            $data[] = $obj;
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        break;
    default:
        break;
}
die;