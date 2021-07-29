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

require_once __DIR__ . '/../../../config.php';
require_once $CFG->dirroot . '/local/newsvnr/lib.php';
require_login();
$courseid   = optional_param('courseid', 0, PARAM_INT);
$action     = optional_param('action', '', PARAM_TEXT);
$listcourse = get_list_course_by_student($USER->id);
$data       = [];
$data[0]    = ['name' => 'Xem tất cả','id' => ''];
foreach ($listcourse as $course) {
    $object       = new stdCLass();
    $object->name = $course->fullname;
    $object->id   = $course->id;
    $data[]       = $object;
}
// var_dump($data);
echo json_encode($data, JSON_UNESCAPED_UNICODE);

die();
