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
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
$searchquery  = optional_param('q', '', PARAM_RAW);
$showalldata = optional_param('showall','', PARAM_RAW);
require_login();
$title = get_string('pagetitle', 'local_newsvnr');
$pagetitle = get_string('pagetitle', 'local_newsvnr');
// $navstr = get_string('navbar','local_newsvnr');
// Set up the page.
$url = new moodle_url("/local/newsvnr/index.php");
$PAGE->set_context(context_system::instance());
$PAGE->set_url($url);
$PAGE->set_title($title);

$PAGE->set_heading($title);

$PAGE->requires->css('/local/newsvnr/css/trangChuTinTuc.css');
$PAGE->requires->css('/local/newsvnr/css/style.css');
// $PAGE->requires->js('/local/newsvnr/js/jquery-3.2.1.min.js', true);
$PAGE->requires->js('/local/newsvnr/js/trangChuTintuc.js', true);
$PAGE->requires->js('/local/newsvnr/js/diendan1.js', true);
$PAGE->requires->js('/local/newsvnr/js/pagination_coursenews.js');
$PAGE->requires->js('/local/newsvnr/js/pagination_coursenews.js');
$PAGE->requires->js_call_amd('local_newsvnr/news','init');
$output = $PAGE->get_renderer('local_newsvnr');

$page = new \local_newsvnr\output\news_page();
$PAGE->navbar->ignore_active();
if ($searchquery) {
	$PAGE->navbar->add($title,$url);
	$PAGE->navbar->add('Tìm kiếm');
}
else if($showalldata){
	$PAGE->navbar->add($title,$url);
	$PAGE->navbar->add('Tin khóa học');
}
else{
	$PAGE->navbar->add($title);
}

echo $output->header();

echo $output->render($page);

echo $output->footer();
