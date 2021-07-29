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
require_once(__DIR__ . '/../../../config.php');
$courseid  = optional_param('courseid',0,PARAM_INT);
$action = optional_param('action','',PARAM_TEXT);
require_login();

switch ($action) {
	case 'unstarred':
		if($DB->delete_records('favourite',['itemid' => $courseid])) {
			echo "success ";
		}
	break;

	case 'starred':
		$contextid = $DB->get_field('context','id',['instanceid' => $courseid]);
		$obj = new stdClass();
		$obj->component = 'core_course';
		$obj->itemtype = 'courses';
		$obj->itemid = $courseid;
		$obj->contextid = $contextid;
		$obj->userid = $USER->id;
		$obj->timecreated = time();
		$obj->timemodified = time();
		if($DB->insert_record('favourite',$obj)) {
			echo "success ";
		}
	break;

	default:

	break;
}   



die();