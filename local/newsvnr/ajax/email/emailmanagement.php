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
require_login();
$PAGE->set_context(context_system::instance());

$action = required_param('action', PARAM_ALPHANUMEXT);
$template_params = optional_param_array('template', '',PARAM_RAW);
$pagesize = optional_param('pagesize', 10, PARAM_INT);
$pagetake = optional_param('take',0, PARAM_INT);
$pageskip = optional_param('skip',0, PARAM_INT);
$q = optional_param('q','', PARAM_RAW);
$data = array();

switch ($action) {
    case 'emailtemplate_add':
	    $obj              = new stdclass;
	    $obj->name        = $template_params['name'];
	    $obj->code        = $template_params['code'];
	    $obj->emailtype        = $template_params['emailtype'];
	    $obj->usercreate        = $USER->id;
	    $obj->usermodified        = $USER->id;
	    $obj->timecreated        = time();
		$obj->timemodified        = time();
	    $obj->description = $template_params['description'];
	    $DB->insert_record('email_template', $obj);
	    $data['success'] = get_string('add_success', 'local_newsvnr');
	    echo json_encode($data, JSON_UNESCAPED_UNICODE);
	    die;
	case 'emailtemplate_edit':
    	$obj              = new stdclass;
        $obj->id        = (int)$template_params['templateid'];
        $obj->name        = $template_params['name'];
        $obj->code        = $template_params['code'];
        $obj->emailtype        = $template_params['emailtype'];
        $obj->usercreate        = $USER->id;
        $obj->usermodified        = $USER->id;
        $obj->timecreated        = time();
		$obj->timemodified        = time();
        $obj->description = $template_params['description'];
        $DB->update_record('email_template', $obj);
        $data['success'] = get_string('edit_success', 'local_newsvnr');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    	die;
    case 'emailtemplate_delete':
    	$templateid = optional_param('templateid', 0, PARAM_INT);
    	$DB->delete_records('email_template', ['id' => $templateid]);
    	$data['success'] = get_string('delete_success', 'local_newsvnr');
    	echo json_encode($data, JSON_UNESCAPED_UNICODE);
    	die;
    case 'emailtemplate_grid':
    	$ordersql = "";
		$wheresql = "";
		if($q) {
			$wheresql = "WHERE name LIKE N'%$q%'";
		}
		if($pagetake == 0) {
			$ordersql = "RowNum";
		} else {
			$ordersql = "RowNum OFFSET $pageskip ROWS FETCH NEXT $pagetake ROWS only";
		}
    	$sql = "SELECT *, (SELECT COUNT(id) FROM mdl_email_template $wheresql) AS total
					FROM (SELECT *, ROW_NUMBER() OVER (ORDER BY id) AS RowNum
						  FROM mdl_email_template $wheresql
						 ) AS Mydata
					ORDER BY $ordersql";
    	$get_list = $DB->get_records_sql($sql);
		foreach ($get_list as $value) {
			$obj = new stdclass;
			$obj->id = $value->id;		
			$obj->name = $value->name;		
			$obj->code = $value->code;
			$obj->emailtype = $value->emailtype;
			$obj->description = $value->description;
			$obj->total = $value->total;
			$data[] = $obj;		
		}
		echo json_encode($data,JSON_UNESCAPED_UNICODE);
		die;
    case 'get_emailtype':
    	$emailtypes = $DB->get_records('email_template');
    	if($emailtypes) {
    		$obj = new stdclass;
    		$obj->value = -1;
    		$obj->text = 'Chọn mẫu email...';
    		$data[] = $obj;
    		foreach($emailtypes as $emailtype) {
    			$obj = new stdclass;
    			$obj->value = $emailtype->id;
    			$obj->text = $emailtype->name;
    			$data[] = $obj;
    		}
    	} else {
    		$obj = new stdclass;
    		$obj->value = -1;
    		$obj->text = "Chưa có loại email nào được cấu hình";
    		$data[] = $obj;
    	}
    	echo json_encode($data, JSON_UNESCAPED_UNICODE);
    	die;
    case 'get_emailcontent':
    	$templateid = optional_param('templateid', 0, PARAM_INT);
    	$templates = $DB->get_record('email_template', ['id' => $templateid]);
    	$data['subject'] = $templates->subject;
    	$data['content'] = $templates->content;
    	echo json_encode($data, JSON_UNESCAPED_UNICODE);
    	die;
    default:
        # code...
        break;
}


