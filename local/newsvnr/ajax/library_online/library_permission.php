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

$PAGE->set_context(context_system::instance());
require_login();

$data   = array();
$action = required_param('action', PARAM_TEXT);

$data = [];
switch ($action) {
    case 'get_position':
        $listposition = $DB->get_records("orgstructure_position");
        foreach ($listposition as $value) {
            $object               = new stdclass();
            $object->positionname = $value->name;
            $object->jobtitleid   = $value->jobtitleid;
            $object->positionid   = $value->id;
            $data[]               = $object;
        }
        break;
    default:
        break;
}
echo json_encode($data, JSON_UNESCAPED_UNICODE);
die;
