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
require_login();
$PAGE->set_context(context_system::instance());

$moduleid = optional_param('moduleid', 0, PARAM_INT);
$action   = optional_param('action', '', PARAM_TEXT);
if (isset($_POST['dataselect'])) {
    $dataselect = json_decode($_POST['dataselect']);
}
$data = [];
switch ($action) {
    case 'delete':
        if(!has_capability('local/newsvnr:deletefilelibrary', context_user::instance($USER->id))) {
            $data['error'] = true;
            $data['message'] = get_string('nopermissiondelete', 'local_newsvnr');
            break;
        }

        // Gửi email và thông báo lên chuông khi có yêu cầu duyệt file bị từ chối trong thư viện
        send_email_rejectedfile($moduleid);

        $DB->delete_records('library_module', ['coursemoduleid' => $moduleid]);
        course_delete_module($moduleid);

        $data['error'] = false;
        $data['message'] = get_string('deletemodulesuccess', 'local_newsvnr');
        break;
    case 'reject':
        $getid = $DB->get_field('library_module', 'id', ['coursemoduleid' => $moduleid]);
        $DB->update_record('library_module', ['id' => $getid, 'approval' => 3]);
        $data['error'] = false;
        $data['message'] = get_string('deletemodulesuccess', 'local_newsvnr');
        break;
    case 'hide':
        
        $DB->update_record('course_modules', ['id' => $moduleid, 'visible' => 0]);
        break;
    case 'show':
        $DB->update_record('course_modules', ['id' => $moduleid, 'visible' => 1]);
        break;
    case 'approval':
        if (!has_capability('local/newsvnr:confirmfilelibrary', context_user::instance($USER->id))) {
            $data['error'] = true;
            $data['message'] = get_string('nopermissionapproval', 'local_newsvnr');
            break;
        }

        // Gửi email và thông báo lên chuông khi có yêu cầu duyệt file được chấp nhận trong thư viện
        send_email_approvedfile($moduleid);

        $getid = $DB->get_field('library_module', 'id', ['coursemoduleid' => $moduleid]);
        $DB->update_record('library_module', ['id' => $getid, 'approval' => 2]);
        $data['error'] = false;
        $data['message'] = get_string('approvalmodulesuccess', 'local_newsvnr');
        break;
    case 'deleteselect':
        if (!has_capability('local/newsvnr:deletefilelibrary', context_user::instance($USER->id))) {
            $data['error'] = true;
            $data['message'] = get_string('nopermissiondelete', 'local_newsvnr');
            break;
        }
        foreach ($dataselect as $value) {
            // Gửi email và thông báo lên chuông khi có yêu cầu duyệt file bị từ chối trong thư viện
            send_email_rejectedfile($value->id);

            $DB->delete_records('library_module', ['coursemoduleid' => $value->id]);
            course_delete_module($value->id);
        }
        $data['error'] = false;
        $data['message'] = get_string('deletemodulesuccess', 'local_newsvnr');
        break;
    case 'approvalselect':
        if (!has_capability('local/newsvnr:confirmfilelibrary', context_user::instance($USER->id))) {
            $data['error'] = true;
            $data['message'] = get_string('nopermissionapproval', 'local_newsvnr');
            break;
        }
        foreach ($dataselect as $value) {
            // Gửi email và thông báo lên chuông khi có yêu cầu duyệt file được chấp nhận trong thư viện
            send_email_approvedfile($value->id);

            $getid = $DB->get_field('library_module', 'id', ['coursemoduleid' => $value->id]);
            $DB->update_record('library_module', ['id' => $getid, 'approval' => 2]);
        }
        $data['error'] = false;
        $data['message'] = get_string('approvalmodulesuccess', 'local_newsvnr');
        break;
    case 'deleteselectrequest':
        foreach ($dataselect as $value) {
            $DB->delete_records('library_module', ['coursemoduleid' => $value->id]);
            course_delete_module($value->id);
        }
        $data['error'] = false;
        $data['message'] = get_string('deletemodulesuccess', 'local_newsvnr');
        break;
    case 'requestapprovalselect':
        foreach ($dataselect as $value) {
            $getid = $DB->get_field('library_module', 'id', ['coursemoduleid' => $value->id]);
            $DB->update_record('library_module', ['id' => $getid, 'approval' => 1]);
        }
        $data['error'] = false;
        $data['message'] = get_string('requestapprovalsuccess', 'local_newsvnr');
        break;
    case 'requestapproval':
        $getid = $DB->get_field('library_module', 'id', ['coursemoduleid' => $moduleid]);
        $DB->update_record('library_module', ['id' => $getid, 'approval' => 1]);
        $data['error'] = false;
        $data['message'] = get_string('requestapprovalsuccess', 'local_newsvnr');
        break;
    case 'cancelrequestapproval':
        $getid = $DB->get_field('library_module', 'id', ['coursemoduleid' => $moduleid]);
        $DB->update_record('library_module', ['id' => $getid, 'approval' => 0]);
        $data['error'] = false;
        $data['message'] = get_string('requestapprovalsuccess', 'local_newsvnr');
        break;
    default:

        break;
}
echo json_encode($data, JSON_UNESCAPED_UNICODE);
die;
