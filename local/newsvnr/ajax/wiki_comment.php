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
$userid    = optional_param('userid', 0, PARAM_INT);
$wikipage  = optional_param('wikipage', 0, PARAM_INT);
$contextid = optional_param('contextid', 0, PARAM_INT);
$action    = optional_param('action', '', PARAM_RAW);
$content   = optional_param('content', '', PARAM_RAW);
$commentid = optional_param('commentid', 0, PARAM_INT);
require_login();
$PAGE->set_context(context_system::instance());
$data = [];
switch ($action) {
    case 'add':
        $object              = new stdCLass();
        $object->contextid   = $contextid;
        $object->component   = 'mod_wiki';
        $object->commentarea = 'wiki_page';
        $object->itemid      = $wikipage;
        $object->content     = $content;
        $object->format      = 1;
        $object->userid      = $userid;
        $object->timecreated = time();
        $DB->insert_record('comments', $object);
        $data = "Đã thêm bình luận!";
        break;
    case 'delete':
        $DB->delete_records('comments', ['id' => $commentid]);
        $data = "Xóa thành công!";
        break;
    case 'edit':
        $object          = new stdCLass();
        $object->id      = $commentid;
        $object->content = $content;
        $DB->update_record('comments',$object);
        $data = "Sửa thành công!";
        break;
    default:
        break;
}

echo $data;
die();
