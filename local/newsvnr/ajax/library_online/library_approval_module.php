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

$pagesize = optional_param('pagesize', 10, PARAM_INT);
$pagetake = optional_param('take', 0, PARAM_INT);
$pageskip = optional_param('skip', 0, PARAM_INT);
$q        = optional_param('q', '', PARAM_RAW);
$data     = array();
$odersql  = "";
$wheresql = "WHERE lm.approval = 1";
if ($q) {
    $wheresql = "WHERE lm.approval = 0 AND CONCAT(rs.name,b.name,l.name,i.name,pa.name,ur.name) LIKE N'%$q%'";
}
if ($pagetake == 0) {
    $ordersql = "RowNum";
} else {
    $ordersql = "RowNum OFFSET $pageskip ROWS FETCH NEXT $pagetake ROWS only";
}
$sql = "SELECT *,
            (SELECT COUNT(lm.id) FROM {library_module} lm
                                JOIN {course_modules} cm on cm.id = lm.coursemoduleid
                                JOIN {user} u on u.id = lm.userid
                                JOIN {library_folder} lf on lf.id = lm.folderid $wheresql ) AS total
            FROM (SELECT lm.*,lf.parent,CONCAT(rs.name,b.name,l.name,i.name,pa.name,ur.name,wk.name) AS name,lf.name AS foldername,cm.instance,CONCAT(u.firstname,' ', u.lastname) AS fullnamet,ROW_NUMBER() OVER (ORDER BY lm.id) AS RowNum
                FROM {library_module} lm
                    JOIN {course_modules} cm on cm.id = lm.coursemoduleid
                    LEFT JOIN {resource} rs on cm.instance = rs.id AND rs.course = 1
                    LEFT JOIN {book} b on cm.instance = b.id AND b.course = 1
                    LEFT JOIN {page} pa on cm.instance = pa.id AND pa.course = 1
                    LEFT JOIN {url} ur on cm.instance = ur.id AND ur.course = 1
                    LEFT JOIN {lesson} l on cm.instance = l.id AND l.course = 1
                    LEFT JOIN {lesson_pages} lp on l.id = lp.lessonid
                    LEFT JOIN {imscp} i on cm.instance = i.id AND i.course = 1
                    LEFT JOIN {wiki} wk on cm.instance = wk.id AND wk.course = 1
                    JOIN {user} u on u.id = lm.userid
                    JOIN {library_folder} lf on lf.id = lm.folderid $wheresql) AS Mydata
                ORDER BY $ordersql";

$get_list = $DB->get_records_sql($sql);
foreach ($get_list as $value) {
    $obj     = new stdClass();
    $nameimg = array();
    if ($value->moduletype == "resource") {
        $url     = get_link_file($value);
        $nameimg = html_writer::link('javascript:void(0)', mimetype2Img($value->minetype) . $value->name,
            array('onclick' => 'iFrameLibrary(\'' . $url . '\',\'' . $value->moduletype . '\')'));
        $size = display_size($value->filesize);
    } else {
        $url = $CFG->wwwroot . '/mod/' . $value->moduletype . '/view.php?id=' . $value->coursemoduleid;
        if ($value->moduletype == "url") {
            $nameimg = html_writer::link($url, html_writer::img($OUTPUT->image_url('icon', $value->moduletype), $value->moduletype, ['class' => 'pr-1 img-module']) . $value->name,
                array('target' => '_blank'));
        } else {
            $nameimg = html_writer::link('javascript:void(0)', html_writer::img($OUTPUT->image_url('icon', $value->moduletype), $value->moduletype, ['class' => 'pr-1 img-module']) . $value->name,
                array('onclick' => 'iFrameLibrary(\'' . $url . '\',\'' . $value->moduletype . '\')'));
        }

        $size = '';
    }
    $obj->name = $nameimg;
    $obj->type = $value->moduletype;
    if ($value->moduletype == "resource") {
        $obj->type = mime2ext($value->minetype);
    }
    $obj->size = $size;
    $dt        = new DateTime("@$value->timecreated");
    $dt->setTimeZone(new DateTimeZone('Asia/Ho_Chi_Minh'));
    $obj->timecreated = $dt->format('d/m/Y');
    $obj->author      = $value->fullnamet;
    $obj->folder      = get_link_folder($value);
    $obj->total       = $value->total;
    $obj->id          = $value->coursemoduleid;
    $data[]           = $obj;
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
die;
