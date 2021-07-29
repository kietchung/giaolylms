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
require_once $CFG->dirroot . '/theme/moove/classes/output/core_renderer.php';
require_once "../system_paginationAll.class.php";
require_login();
$PAGE->set_context(context_system::instance());
$perPage         = new PerPage();
$folderid        = optional_param('folderid', 0, PARAM_INT);
$page            = optional_param('page', 0, PARAM_INT);
$search          = optional_param('search', "", PARAM_TEXT);
$modulefilter    = optional_param('modulefilter', "", PARAM_TEXT);
$searchtype      = optional_param('searchtype', "", PARAM_TEXT);
$action          = optional_param('action', "", PARAM_TEXT);
$strsearch       = "N'" . '%' . $search . '%' . "'";
$strmodulefilter = "N'" . '%' . $modulefilter . '%' . "'";
$start           = $perPage->getStart($page);
$data            = [];
$modulebyfolder  = [];
$output          = '';
$excelsql        = '';
$allowmodule     = ['book', 'lesson', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt'];
$odersql         = "GROUP BY lm.id,lm.coursemoduleid,lm.moduletype,lm.minetype,lm.filesize,cm.id,lm.timecreated,CONCAT(rs.name,b.name,l.name,i.name,pa.name,ur.name,wk.name),cm.visible,CONCAT(u.firstname,' ', u.lastname),cm.instance ";
if ($modulefilter == 'excel') {
    $excelsql = "OR lm.minetype LIKE N'%spreadsheetml%'";
}
if (is_siteadmin()) {
    $condition = "WHERE cm.deletioninprogress = 0 AND ";
} else {
    $condition = "LEFT JOIN {library_folder_permissions} fp on fp.folderlibraryid = lf.id
                    LEFT JOIN {library_user_permissions} up on up.permissionid = fp.id
                    LEFT JOIN {user} uss on uss.orgpositionid = up.positionid
                    WHERE (uss.id is NULL or uss.id = $USER->id) AND lf.visible = 1 AND cm.visible = 1 AND cm.deletioninprogress = 0 AND";
}
if ($searchtype == "searchcontent") {
    $searchsql = "(lp.title LIKE $strsearch OR lp.contents LIKE $strsearch OR pa.intro LIKE $strsearch OR pa.content LIKE $strsearch OR ur.externalurl LIKE $strsearch OR (bc.CONTENT LIKE $strsearch OR bc.title LIKE $strsearch) OR (wk.intro LIKE $strsearch))";
} else {
    $searchsql = "CONCAT(rs.name,b.name,l.name,i.name,pa.name,ur.name,wk.name) LIKE $strsearch";
}
switch ($action) {
    case 'timesort_asc':
        $odersql .= "ORDER BY lm.timecreated ASC OFFSET $start ROWS FETCH NEXT $perPage->itemPerPage ROWS only";
        break;
    case 'timesort_desc':
        $odersql .= "ORDER BY lm.timecreated DESC OFFSET $start ROWS FETCH NEXT $perPage->itemPerPage ROWS only";
        break;
    case 'viewsort_asc':
        $odersql .= "ORDER BY COUNT(lsl.id) ASC OFFSET $start ROWS FETCH NEXT $perPage->itemPerPage ROWS only";
        break;
    case 'viewsort_desc':
        $odersql .= "ORDER BY COUNT(lsl.id) DESC OFFSET $start ROWS FETCH NEXT $perPage->itemPerPage ROWS only";
        break;
    default:
        $odersql .= "ORDER BY lm.timecreated DESC OFFSET $start ROWS FETCH NEXT $perPage->itemPerPage ROWS only";
        break;
}
$sql = "SELECT DISTINCT lm.id,lm.coursemoduleid,lm.moduletype,lm.minetype,lm.filesize,cm.id as cmid,COUNT(lsl.id) as viewed,lm.timecreated,CONCAT(rs.name,b.name,l.name,i.name,pa.name,ur.name,wk.name) AS name,cm.visible,CONCAT(u.firstname,' ', u.lastname) as fullnamet,cm.instance
            FROM {library_module} lm
                JOIN {course_modules} cm on cm.id = lm.coursemoduleid
                JOIN {modules} m on m.id = cm.module
                LEFT JOIN {resource} rs on cm.instance = rs.id AND rs.course = 1 AND m.name = 'resource'
                LEFT JOIN {book} b on cm.instance = b.id AND b.course = 1 AND m.name = 'book'
                LEFT JOIN {book_chapters} bc on bc.bookid = b.id 
                LEFT JOIN {page} pa on cm.instance = pa.id AND pa.course = 1 AND m.name = 'page'
                LEFT JOIN {url} ur on cm.instance = ur.id AND ur.course = 1 AND m.name = 'url'
                LEFT JOIN {lesson} l on cm.instance = l.id AND l.course = 1 AND m.name = 'lesson'
                LEFT JOIN {lesson_pages} lp on l.id = lp.lessonid 
                LEFT JOIN {imscp} i on cm.instance = i.id AND i.course = 1 AND m.name = 'imscp'
                LEFT JOIN {wiki} wk on cm.instance = wk.id AND wk.course = 1 AND m.name = 'wiki'
                JOIN {user} u on u.id = lm.userid
                JOIN {library_folder} lf on lf.id = lm.folderid
                LEFT JOIN mdl_logstore_standard_log lsl ON lsl.contextinstanceid = cm.id AND lsl.courseid  = cm.course AND lsl.action = 'viewed' and lsl.target = 'course_module' and lsl.courseid = 1
            $condition $searchsql AND lm.approval = 2 AND (lm.moduletype LIKE $strmodulefilter OR lm.minetype LIKE $strmodulefilter $excelsql)";

if ($folderid == 0) {
    $modulebyfolder = $DB->get_records_sql("$sql $odersql");
    $newsql         = str_replace("COUNT(lsl.id) as viewed,", "", "$sql");
    $countall       = $DB->get_records_sql("$newsql");
} else {
    $modulebyfolder = $DB->get_records_sql("$sql AND lm.folderid =:folderid $odersql", ['folderid' => $folderid]);
    $newsql         = str_replace("COUNT(lsl.id) as viewed,", "", "$sql");
    $countall       = $DB->get_records_sql("$newsql AND lm.folderid =:folderid", ['folderid' => $folderid]);
}
if (!empty($modulebyfolder) && has_capability('local/newsvnr:readfolderlibrary', context_user::instance($USER->id))) {
    foreach ($modulebyfolder as $module) {
        if ($module->moduletype == "resource") {
            $url          = get_link_file($module);
            $typeresource = mime2ext($module->minetype);
            $img          = mimetype2Img($module->minetype);
        } else {
            $img = html_writer::tag('img', '', ['title' => $module->moduletype, 'class' => 'pr-1 img-module', 'src' => $OUTPUT->image_url('icon', $module->moduletype)]);
            $url = $CFG->wwwroot . '/mod/' . $module->moduletype . '/view.php?id=' . $module->coursemoduleid;
        }
        $output .= html_writer::start_tag('tr');
        if (($module->moduletype != 'resource' && in_array($module->moduletype, $allowmodule)) || ($module->moduletype == 'resource' && in_array($typeresource, $allowmodule))) {
            $output .= html_writer::tag('td', '<a onclick="iFrameLibrary(\'' . $url . '\',\'' . $module->moduletype . '\')">' . $img . '' . $module->name . '</a>');
        } else {
            $output .= html_writer::tag('td', '<a href="' . $url . '" target="_blank" >' . $img . '' . $module->name . '</a>');
        }
        // Xử lý đạng module
        $output .= ($module->moduletype == "resource") ? html_writer::tag('td', strtoupper($typeresource)) . html_writer::tag('td', display_size($module->filesize)) : html_writer::tag('td', strtoupper($module->moduletype)) . html_writer::tag('td', '');
        $output .= html_writer::tag('td', convertunixtime('d/m/Y', $module->timecreated, 'Asia/Ho_Chi_Minh'));
        $output .= html_writer::tag('td', $module->fullnamet);
        $output .= html_writer::tag('td', $module->viewed);
        $output .= html_writer::start_tag('td');
        $output .= (is_siteadmin() || check_teacherrole($USER->id) != 0) ? html_writer::link('javascript:void(0)', '<i class="icon fa fa-share mr-2" aria-hidden="true"></i>', array('id' => 'share-module-library', 'moduleid' => $module->coursemoduleid)) : '';
        if (is_siteadmin()) {
            $output .= html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/delete', get_string('delete')), array("onclick" => "actionModule($module->coursemoduleid,'delete',$folderid)"));
            $output .= html_writer::link("/course/modedit.php?update=$module->coursemoduleid&return=0&sr=", $OUTPUT->pix_icon('t/edit', get_string('edit')));
            $output .= ($module->visible == 1) ? html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/hide', get_string('hide')), array("onclick" => "actionModule($module->coursemoduleid,'hide',$folderid)")) : html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/show', get_string('show')), array("onclick" => "actionModule($module->coursemoduleid,'show',$folderid)"));
        }
        $output .= html_writer::end_tag('td');
        $output .= html_writer::end_tag('tr');
    }
}
$paginationlink     = $CFG->wwwroot . '/local/newsvnr/ajax/library_online/library_module_ajax.php?folderid=' . $folderid . '&search=' . $search . '&modulefilter=' . $modulefilter . '&action=' . $action . '&page=';
$perpageresult      = $perPage->getAllPageLinks(count($countall), $paginationlink, '#table-library');
$pagination         = (!empty($perpageresult)) ? html_writer::tag('div', $perpageresult, ['class' => 'col-md-12 mb-2', 'id' => 'pagination', 'folderid' => $folderid, 'search' => $search, 'modulefilter' => $modulefilter]) : html_writer::tag('div', '', ['class' => 'col-md-12 mb-2', 'id' => 'pagination', 'folderid' => $folderid, 'search' => $search, 'modulefilter' => $modulefilter]);
$alert              = (empty($countall)) ? html_writer::tag('div', get_string('nomodule', 'local_newsvnr'), ['class' => 'alert-warning', 'role' => 'alert']) : '';
$data['alert']      = $alert;
$data['pagination'] = $pagination;
if(has_capability('local/newsvnr:readfolderlibrary', context_user::instance($USER->id))) {
    $data['header']     = $OUTPUT->count_module_by_folder($countall, $folderid);
}
$data['result']     = $output;
echo json_encode($data, JSON_UNESCAPED_UNICODE);
die();
