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
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
$PAGE->set_context(context_system::instance());
require_login();

$data      = array();
$action    = required_param('action', PARAM_TEXT);
$courseid  = optional_param('courseid', 0, PARAM_INT);
$moduleid  = optional_param('moduleid', 0, PARAM_INT);
$sectionid = optional_param('sectionid', 0, PARAM_INT);

$data = [];
switch ($action) {
    case 'filter_course':
        if (is_siteadmin()) {
            $datacourse = $DB->get_records("course");
        } elseif (check_teacherrole($USER->id) != 0) {
            $datacourse = get_list_courseinfo_by_teacher($USER->id);
        }
        foreach ($datacourse as $course) {
            $object           = new stdclass();
            $object->name     = $course->fullname;
            $object->courseid = $course->id;
            $data[]           = $object;
        }
        break;
    case 'filter_course_section':
        $datasection = [];
        if (is_siteadmin()) {
            $datasection = $DB->get_records("course_sections");
        } elseif (check_teacherrole($USER->id) != 0) {
            $datacourse = get_list_courseinfo_by_teacher($USER->id);
            foreach ($datacourse as $course) {
                $temp = $DB->get_records("course_sections", ['course' => $course->id]);
                $datasection = array_merge($temp, $datasection);
            }
        }
        foreach ($datasection as $section) {
            $object = new stdclass();
            if ($section->name != null) {
                $object->name = $section->name;
            } else {
                if ($section->section == 0) {
                    $object->name = 'General';
                } else {
                    $object->name = get_string('topic', 'local_newsvnr') . ' ' . $section->section;
                }
            }
            $object->sectionid = $section->id;
            $object->courseid  = $section->course;
            $data[]            = $object;
        }
        break;
    case 'share_module':
        list($course, $cm) = get_course_and_cm_from_cmid($moduleid);
        $course            = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $newcm             = new stdClass;
        $newcm->id         = $cm->id;
        $newcm->modname    = $cm->modname;
        $newcm->name       = $cm->name;
        $newcm->section    = $sectionid;
        $newcm->course     = $courseid;
        $newcm             = duplicate_module_library($course, $newcm);
        $data['success']   = get_string('sharemodulesuccess', 'local_newsvnr');
        // cache_helper::purge_all();
        break;
    default:
        break;
}
echo json_encode($data, JSON_UNESCAPED_UNICODE);
die;
