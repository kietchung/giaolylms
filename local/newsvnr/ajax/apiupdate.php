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
$section  = required_param('section',PARAM_ALPHANUMEXT);
$id = required_param('id',PARAM_INT);
require_login();
$PAGE->set_url(new moodle_url('/local/newsvnr/apiupdate.php', array('section' => $section, 'id' => $id)));
$PAGE->set_context(context_system::instance());
switch ($section) {
	case 'show':
		$DB->update_record('local_newsvnr_api',array('id' => $id,'visible'=> 1 ));
		echo "success ";
	break;
	case 'hide':
		$DB->update_record('local_newsvnr_api',array('id' => $id,'visible'=> 0 ));
		echo "success ";
	break;

	default:
	break;
}   

// echo json_encode($objdata,JSON_UNESCAPED_UNICODE);

die();