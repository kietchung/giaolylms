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
require_once __DIR__ . '/../../lib.php';
require_once $CFG->dirroot . '/local/newsvnr/lib.php';
require_once $CFG->dirroot . '/blocks/dedication/dedication_lib.php';
require_login();
$PAGE->set_context(context_system::instance());
$pagesize = optional_param('pagesize', 10, PARAM_INT);
$pagetake = optional_param('take', 0, PARAM_INT);
$pageskip = optional_param('skip', 0, PARAM_INT);
$action   = optional_param('action', '', PARAM_RAW);
$data     = [];
$wheresql = "";
if ($pagetake == 0) {
    $ordersql = "RowNum";
} else {
    $ordersql = "RowNum OFFSET $pageskip ROWS FETCH NEXT $pagetake ROWS only";
}
$sql = "SELECT *, (SELECT COUNT(id) FROM {division} $wheresql) AS total
                FROM (
                    SELECT *,ROW_NUMBER() OVER (ORDER BY id) AS RowNum
                    FROM {division} $wheresql
                ) AS Mydata
                ORDER BY $ordersql";
$get_list = $DB->get_records_sql($sql);
foreach ($get_list as $value) {
    $object               = new stdClass();
    $object->id           = $value->id;
    $object->name         = $value->name;
    $object->shortname    = $value->shortname;
    $object->code         = $value->code;
    $object->address      = $value->address;
    $object->phone        = $value->phone;
    $object->website      = $value->website;
    $object->fax          = $value->fax;
    $object->timecreated  = convertunixtime('d/m/Y', $value->timecreated, 'Asia/Ho_Chi_Minh');
    $object->timemodified = convertunixtime('d/m/Y', $value->timemodified, 'Asia/Ho_Chi_Minh');
    $object->usercreate   =
    $object->total        = $value->total;
    $object->visible      = ($value->visible == 1) ? '<input class="apple-switch" onclick="activeDevision(' . $value->id . ',\'unactive\')" type="checkbox" checked>' : '<input class="apple-switch" type="checkbox" onclick="activeDevision(' . $value->id . ',\'active\')" check>';
    $object->isvisible    = $value->visible;
    $usercreate           = $DB->get_record('user', ['id' => $value->usercreate]);
    $object->usercreate   = $usercreate->firstname . ' ' . $usercreate->lastname;
    $data[]               = $object;
}
echo json_encode($data, JSON_UNESCAPED_UNICODE);
