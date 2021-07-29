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
require_once $CFG->dirroot . '/theme/moove/classes/output/core_renderer.php';
// require_login();
$PAGE->set_context(context_system::instance());
$foldername = optional_param('foldername', "", PARAM_TEXT);
$parentid   = optional_param('parentid', 0, PARAM_INT);
$folderdes  = optional_param('folderdes', "", PARAM_TEXT);
$action     = optional_param('action', "", PARAM_TEXT);
$folderid   = optional_param('folderid', 0, PARAM_INT);
$allmodule  = optional_param('allmodule', 0, PARAM_INT);
$visible    = optional_param('visible', 0, PARAM_INT);
$data       = array();
$obj        = new stdClass();

switch ($action) {
    case 'popupsetting':
        $output = '';
        $output .= '<ul>';
        $output .= '<li onclick="actionPopupLibrary(' . $folderid . ',\'' . $foldername . '\',' . str_replace(" ", "'", " edit ") . ')"><i class="fa fa-pencil-square-o mr-2" aria-hidden="true"></i>' . get_string('editfolder', 'local_newsvnr') . '</li>';
        if ($visible == 0) {
            $output .= '<li onclick="actionPopupLibrary(' . $folderid . ',\'' . $foldername . '\',' . str_replace(" ", "'", " show ") . ')"><i class="fa fa-eye mr-2"></i>' . get_string('show', 'local_newsvnr') . '</li>';
        } else {
            $output .= '<li onclick="actionPopupLibrary(' . $folderid . ',\'' . $foldername . '\',' . str_replace(" ", "'", " hide ") . ')"><i class="fa fa-eye-slash mr-2"></i>' . get_string('hide', 'local_newsvnr') . '</li>';
        }
        if ($allmodule == 0) {
            $output .= '<li onclick="actionPopupLibrary(' . $folderid . ',\'' . $foldername . '\',' . str_replace(" ", "'", " delete ") . ')"><i class="fa fa-trash-o mr-2"></i>' . get_string('deletefolder', 'local_newsvnr') . '</li>';
        }
        $output .= '<li onclick="actionPopupLibrary(' . $folderid . ',\'' . $foldername . '\',' . str_replace(" ", "'", " addchildfolder ") . ')" data-toggle="modal" data-target="#add-popup-modal-folder"><i class="fa fa-plus mr-2" aria-hidden="true"></i>' . get_string('addchildfolder', 'local_newsvnr') . '</li>';
        $output .= '</ul>';
        $data['setting'] = $output;
        break;
    case 'delete':
        $DB->delete_records('library_folder', array('id' => $folderid));
        $DB->delete_records('library_folder', array('parent' => $folderid));
        $permission = $DB->get_record('library_folder_permissions', array('folderlibraryid' => $folderid));
        $DB->delete_records('library_user_permissions', array('permissionid' => $permission->id));
        $DB->delete_records('library_folder_permissions', array('folderlibraryid' => $folderid));
        $data['alert'] = get_string('alertdeletefolder', 'local_newsvnr');
        break;
    case 'add':
        if (isset($_GET['listposition'])) {
            $listposition = json_decode($_GET['listposition']);
        }
        $folder                         = new stdClass();
        $folder->name                   = $foldername;
        $folder->parent                 = $parentid;
        $folder->description            = $folderdes;
        $folderid                       = $DB->insert_record('library_folder', $folder);
        $folderpermiss                  = new stdClass();
        $folderpermiss->permission      = 'view';
        $folderpermiss->folderlibraryid = $folderid;
        $folderpermiss->timecreated     = time();
        $folderpermissid                = $DB->insert_record('library_folder_permissions', $folderpermiss);
        foreach ($listposition as $value) {
            $position               = new stdClass();
            $userscope              = $DB->get_records('user', ['orgpositionid' => $value]);
            $position->positionid   = $value;
            $position->timecreated  = time();
            $position->userscope    = count($userscope);
            $position->permissionid = $folderpermissid;
            $DB->insert_record('library_user_permissions', $position);
        }
        $data['alert'] = get_string('alertaddfolder', 'local_newsvnr');
        break;
    case 'hide':
        $DB->update_record('library_folder', array('id' => $folderid, 'visible' => 0));
        $objupdates = $DB->get_records('library_folder', ['parent' => $folderid]);
        foreach ($objupdates as $value) {
            $DB->update_record('library_folder', array('id' => $value->id, 'visible' => 0));
        }
        $data['alert'] = get_string('alerthidefolder', 'local_newsvnr');
        break;
    case 'show':
        $DB->update_record('library_folder', array('id' => $folderid, 'visible' => 1));
        $objupdates = $DB->get_records('library_folder', ['parent' => $folderid]);
        foreach ($objupdates as $value) {
            $DB->update_record('library_folder', array('id' => $value->id, 'visible' => 1));
        }
        $data['alert'] = get_string('alertshowfolder', 'local_newsvnr');
        break;
    case 'update':
        if (isset($_GET['listposition'])) {
            $listposition = json_decode($_GET['listposition']);
        }
        $obj->name        = $foldername;
        $obj->parent      = $parentid;
        $obj->description = $folderdes;
        $obj->id          = $folderid;
        $DB->update_record('library_folder', $obj);
        $folderpermiss = $DB->get_record('library_folder_permissions', ['folderlibraryid' => $folderid]);
        if (empty($folderpermiss)) {
            $newfolderpermiss                  = new stdClass();
            $newfolderpermiss->permission      = 'view';
            $newfolderpermiss->folderlibraryid = $folderid;
            $newfolderpermiss->timecreated     = time();
            $newfolderpermissid                = $DB->insert_record('library_folder_permissions', $newfolderpermiss);
        }
        $DB->delete_records('library_user_permissions', ['permissionid' => $folderpermiss->id]);
        foreach ($listposition as $value) {
            $position               = new stdClass();
            $userscope              = $DB->get_records('user', ['orgpositionid' => $value]);
            $position->positionid   = $value;
            $position->timecreated  = time();
            $position->userscope    = count($userscope);
            if (empty($folderpermiss)) {
                $position->permissionid = $newfolderpermissid;
            } else {
                $position->permissionid = $folderpermiss->id;
            }
            $DB->insert_record('library_user_permissions', $position);
        }
        $data['alert'] = get_string('alerteditfolder', 'local_newsvnr');
        break;
    case 'edit':
        $folder            = $DB->get_record_sql('SELECT * FROM {library_folder} WHERE id = :id', ['id' => $folderid]);
        $folderparent      = $DB->get_record_sql('SELECT * FROM {library_folder} WHERE id = :parentid', ['parentid' => $folder->parent]);

        if ($folderparent == false) {
            $input = '<input autocomplete="off" class="form-control folderparent" onclick="viewTree()">';
        } else {
            $input = '<input autocomplete="off" class="form-control folderparent" onclick="viewTree()" parentid="' . $folderparent->id . '" value="' . $folderparent->name . '">';
        }
        $output = '<div class="modal fade modal-library" id="edit-popup-modal-folder" role="dialog">
                        <div class="modal-dialog">
                          <!-- Modal content-->
                          <div class="modal-content">
                            <div class="modal-header">
                              <h4 class="modal-title">' . get_string('editfolder', 'local_newsvnr') . '</h4>
                              <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body edit-folder-popup">
                                <div class="form-group ">
                                  <label for="email">' . get_string('foldername', 'local_newsvnr') . ':</label>
                                  <input autocomplete="off" class="form-control" id="foldername" value="' . $folder->name . '">
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input folderdefault" type="checkbox" value="" onchange="defaultFolder()">
                                    <label class="form-check-label mb-2" for="folderdefault">
                                        ' . get_string('defaultfolder', 'local_newsvnr') . '
                                    </label>
                                </div>
                                <div class="form-group">
                                  <label for="pwd">' . get_string('folderparent', 'local_newsvnr') . ':</label>
                                  ' . $input . '
                                  <div class="tree-view-folder">' . $OUTPUT->library_folder() . '</div>
                                </div>
                                <div class="form-group">
                                  <label for="pwd">' . get_string('description', 'local_newsvnr') . ':</label>
                                  <textarea autocomplete="off" class="form-control" id="folderdes" value="' . $folder->description . '"></textarea>
                                </div>
                                 <div class="form-group">
                                    <label class="mb-1" for="pwd">' . get_string('positionviewfolder', 'local_newsvnr') . ':</label>
                                    <select id="edit-positionpermission" multiple="multiple" data-placeholder="' . get_string('selectposition', 'local_newsvnr') . '"></select>
                                </div>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-primary" action="update" onclick="updateFolder(' . $folder->id . ')" id="edit-folder-popup">' . get_string('finisheditfolder', 'local_newsvnr') . '</button>
                              <button type="button" class="btn btn-danger" data-dismiss="modal">' . get_string('cancel', 'local_newsvnr') . '</button>
                            </div>
                          </div>

                        </div>
                  </div>';
        $permission = $DB->get_record('library_folder_permissions', ['folderlibraryid' => $folderid]);
        $arraytemp = [];
        if ($permission) {
            $positionpermisson = $DB->get_records('library_user_permissions', ['permissionid' => $permission->id], 'positionid');
            if ($positionpermisson) {
                foreach ($positionpermisson as $key => $value) {
                    $obj = new stdClass();
                    $obj->positionid = $value->positionid;
                    $arraytemp[] = $obj;
                }
            }
        }
        $data['positionpermisson'] = $arraytemp;
        $data['result']            = $output;
        break;
    default:
        break;
}
echo json_encode($data, JSON_UNESCAPED_UNICODE);

die();
