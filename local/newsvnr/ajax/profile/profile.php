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
require_once "../system_paginationAll.class.php";
require_login();
$PAGE->set_context(context_system::instance());
$userid         = required_param('userid',PARAM_INT);
$page           = optional_param('page', 0, PARAM_INT);
$perPage        = new PerPage();
$data           = [];
$output         = '';
$datauser       = $DB->get_record('user', ['id' => $userid]);
$useravatar     = $OUTPUT->user_picture($datauser);
$paginationlink = $CFG->wwwroot . '/local/newsvnr/ajax/profile/profile.php?userid=' . $userid . '&page=';
$start          = $perPage->getStart($page);
$sql            = "SELECT ROW_NUMBER() OVER (ORDER BY fp.id) AS RowNum,fp.discussion,fd.name,fp.message,fp.created,fd.userid,c.fullname,fp.parent,fd.course
			            FROM {forum_posts} fp
			            JOIN {forum_discussions} fd ON fp.discussion = fd.id
			            JOIN {course} c ON c.id = fd.course
			        WHERE fd.userid =:userid AND fd.course != 1 AND fp.message != ''";
$forum_posts_all = $DB->get_records_sql($sql, ['userid' => $userid]);
$forum_posts     = $DB->get_records_sql($sql . "ORDER BY fp.created DESC OFFSET $start ROWS FETCH NEXT $perPage->itemPerPage ROWS only", ['userid' => $userid]);
foreach ($forum_posts as $value) {
    $created    = converttime($value->created);
    $courselink = $CFG->wwwroot . '/course/view.php?id=' . $value->course;
    $forumlink  = $CFG->wwwroot . '/mod/forum/discuss.php?d=' . $value->discussion;
    $comment    = ($value->parent != 0) ? get_string('replypost','local_newsvnr') : '';
    $output .= '<div class="d-flex flex-column w-100 user-forum-post mb-3">
			        <header  class="header d-flex">
			            <div class="mr-2" style="width: 35px;">
			            	' . $useravatar . '
			            </div>
			            <div class="d-flex flex-column">
			                <span class="grey">' . $comment . ' <a class="black font-bold" href="' . $courselink . '" target="_blank">' . $value->fullname . '</a> &rarr; <a class="black font-bold" href="' . $forumlink . '" target="_blank">' . $value->name . '</a></span>
			                <span class="grey">by <span class="blue"> ' . $datauser->firstname . ' ' . $datauser->lastname . '</span> - <time>' . $created . '</time></span>
			            </div>
			        </header>
			        <div class="d-flex body-content-container">
			            <div class="w-100 content-alignment-container">
			                <div class="post-content-container mt-1">
			                    ' . $value->message . '
			                </div>
			            </div>
			        </div>
			    </div>';
}
$perpageresult = $perPage->getAllPageLinks(count($forum_posts_all), $paginationlink, '#load-forumpost');
$pagination    = '<div class="col-md-12 mb-2" id="pagination">' . $perpageresult . '</div>';
$data['pagination'] = $pagination;
$data['result'] = ($output != '') ? $output : '<div class="alert alert-warning" role="alert">'.get_string('nodata','local_newsvnr').'</div>';
echo json_encode($data, JSON_UNESCAPED_UNICODE);

die();
