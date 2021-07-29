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
 * Manage request files from general library system
 *
 * @package   local_newsvnr
 * @copyright 2020 Le Thanh Vu
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/repository/lib.php');
require_once(__DIR__ . '/../lib.php');

require_login();

// $modalsection = optional_param('modalsection','',PARAM_RAW);
// $orgstructureid = optional_param('orgstructureid',0,PARAM_INT);
// $usercode = optional_param('usercode','',PARAM_RAW);
$action = required_param('action', PARAM_RAW);
$pagesize = optional_param('pagesize',10, PARAM_INT);
$pagetake = optional_param('take',0, PARAM_INT);
$pageskip = optional_param('skip',0, PARAM_INT);
$q = optional_param('q','', PARAM_RAW);


$context = context_system::instance();
$url = $CFG->wwwroot . '/local/newsvnr/ajax/requestfiles_generallibrary.php';
$PAGE->set_url($url);
$PAGE->set_context($context);
if(is_siteadmin()) {
    $subdirs = 1;
} else {
    $subdirs = 0;
}
$user_context = context_user::instance($USER->id);
$maxbytes = get_user_max_upload_file_size($context, $CFG->maxbytes);
$options = array('subdirs' => $subdirs, 'maxbytes' => $maxbytes, 'maxfiles' => -1, 'accepted_types' => '*');
$draftitemid = new stdClass();
file_prepare_standard_filemanager_filelibrary($draftitemid, 'files', $options, $context, 'local_newsvnr', 'content', 0);
$draftitemid = $draftitemid->files_filemanager;
$fs = get_file_storage();
$list = array();
$data = [];

switch ($action) {
    case 'requestfiles':
        $contextid = $context->id;
        $odersql = "";

        if(has_capability('local/newsvnr:confirmfilesystem', $user_context)) {
            $wheresql = "";
        } else {
            $userid = $USER->id;
            $wheresql = "AND userid = $userid";
        }
        if($q) {
            $wheresql .= "AND filename LIKE N'%$q%'";
        }
        if($pagetake == 0) {
            $ordersql = "RowNum";
        } else {
            $ordersql = "RowNum OFFSET $pageskip ROWS FETCH NEXT $pagetake ROWS only";
        }
        $sql = "
                SELECT *, (SELECT COUNT(id) 
                            FROM {files} WHERE contextid = :q1_contextid 
                                AND component = 'local_newsvnr'
                                AND filearea = 'content'
                                AND itemid = 0
                                AND status = 1
                                $wheresql
                            ) AS total
                FROM (
                    SELECT *, ROW_NUMBER() OVER (ORDER BY id) AS RowNum
                    FROM {files} WHERE contextid = :q2_contextid 
                        AND component = 'local_newsvnr'
                        AND filearea = 'content'
                        AND itemid = 0
                        AND status = 1
                        $wheresql
                ) AS Mydata
                ORDER BY $ordersql";
        $get_list = $DB->get_records_sql($sql, ['q1_contextid' => $contextid, 'q2_contextid' => $contextid]);
        $filearea = 'content';
        $component = 'local_newsvnr';
        $itemid  = 0;
        foreach ($get_list as $exkey => $exfile) {
            if ($files = $fs->get_directory_files($contextid, $component, $filearea, $itemid, $exfile->filepath, false)) {
                foreach ($files as $file) {
                    $item = new stdClass();
                    $item->filename = $file->get_filename();
                    $item->filepath = $file->get_filepath();
                    $item->status = $file->get_status();
                    $item->fullname = trim($item->filename, '/');
                    if ($file->is_directory()) {
                        $item->filesize = 0;
                        $item->icon = $OUTPUT->image_url(file_folder_icon(24))->out(false);
                        $item->type = 'folder';
                        $foldername = explode('/', trim($item->filepath, '/'));
                        $item->fullname = trim(array_pop($foldername), '/');
                        $item->thumbnail = $OUTPUT->image_url(file_folder_icon(90))->out(false);
                    } else {
                        // do NOT use file browser here!
                        $item->mimetype = get_mimetype_description($file);
                        if (file_extension_in_typegroup($file->get_filename(), 'archive')) {
                            $item->type = 'zip';
                        } else {
                            $item->type = 'file';
                        }
                        $itemurl = moodle_url::make_draftfile_url($draftitemid, $item->filepath, $item->filename);
                        $item->url = $itemurl->out();
                        $item->icon = $OUTPUT->image_url(file_file_icon($file, 24))->out(false);
                        $item->thumbnail = $OUTPUT->image_url(file_file_icon($file, 90))->out(false);

                        // The call to $file->get_imageinfo() fails with an exception if the file can't be read on the file system.
                        // We still want to add such files to the list, so the owner can view and delete them if needed. So, we only call
                        // get_imageinfo() on files that can be read, and we also spoof the file status based on whether it was found.
                        // We'll use the same status types used by stored_file->get_status(), where 0 = OK. 1 = problem, as these will be
                        // used by the widget to display a warning about the problem files.
                        // The value of stored_file->get_status(), and the file record are unaffected by this. It's only superficially set.
                        if ($imageinfo = $file->get_imageinfo()) {
                            $item->realthumbnail = $itemurl->out(false, array('preview' => 'thumb',
                                'oid' => $file->get_timemodified()));
                            $item->realicon = $itemurl->out(false, array('preview' => 'tinyicon', 'oid' => $file->get_timemodified()));
                            $item->image_width = $imageinfo['width'];
                            $item->image_height = $imageinfo['height'];
                        }
                    }
                    $list[] = $item;
                }
            }
            foreach ($list as $draftkey => $draft) {
                if($draft->status == 0 || ($exfile->filename != $draft->filename)) {
                    continue;
                }
                $fileicon = '';
                $url = '';
                if(isset($draft->realicon) && ($exfile->filename == $draft->filename)) {
                    $fileicon = $draft->realicon;
                    $url = $draft->url;
                } elseif($exfile->filename == $draft->filename) {
                    $fileicon = $draft->icon;
                    $url = $draft->url;
                }
                if(!empty($fileicon))
                    break;
            }
            $buttons = [];
            if(has_capability('local/newsvnr:confirmfilesystem', $user_context)) {
                $buttons[] = html_writer::link('javascript:;',
                $OUTPUT->pix_icon('t/check', get_string('accept' , 'local_newsvnr')),
                    array('title' => get_string('accept' , 'local_newsvnr'), 'id' => 'accept-file-'.$exfile->id.'', 'onclick' => "acceptFile($exfile->id)"));    
            }
            
            $buttons[] = html_writer::link('javascript:;',
            $OUTPUT->pix_icon('t/delete', get_string('reject', 'local_newsvnr')),
                array('title' => get_string('reject', 'local_newsvnr'), 'id' => 'delete-file-'.$exfile->id.'', 'class' => 'delete-item','data-section' => 'deletefile', 'onclick' => "deleteFile('$exfile->filename','$exfile->filepath', $exfile->id)"));
            $showbuttons = implode(' ', $buttons);
            $object = new stdclass;
            $object->id = $exfile->id;
            $object->filename = html_writer::empty_tag('img', array('src' => $fileicon, 'class' => 'mr-1')) .$exfile->filename;
            $object->filepath = $exfile->filepath;
            $object->filetype = mb_strtoupper(mime2ext($exfile->mimetype));
            $object->filesize = display_size($exfile->filesize);
            $object->author = $exfile->author;
            $object->timecreated = convertunixtime('d/m/Y',$exfile->timecreated);
            $object->download = html_writer::link($url, get_string('downloadfile', 'local_newsvnr'));
            $object->listbtn = $showbuttons;
            $object->total = $exfile->total;
            $data[] = $object;      

        }
        break;
    case 'listrole':
        $roles = ['local/newsvnr:createfoldersystem', 'local/newsvnr:editfoldersystem', 'local/newsvnr:assignmentfilesystem', 'local/newsvnr:confirmfilesystem', 'local/newsvnr:readfilesystem', 'local/newsvnr:editfilesystem', 'local/newsvnr:createfilesystem', 'local/newsvnr:readfoldersystem', 'local/newsvnr:assignmentfoldersystem', 'local/newsvnr:deletefoldersystem', 'local/newsvnr:deletefilesystem'];
        foreach($roles as $role) {
            $split = explode(":",$role);
            $str = 'newsvnr:' . $split[1];
            $obj = new stdClass;
            $obj->name = get_string($str, 'local_newsvnr');
            $obj->name_raw = $role;
            $data[] = $obj;
        }
        break;
    case 'listuser':
        $capability = optional_param('capability', '', PARAM_RAW);
        $sql = "
                SELECT rc.*, u.id AS userid, CONCAT(u.firstname, ' ', u.lastname) AS fullname
                FROM mdl_role_capabilities rc 
                    LEFT JOIN mdl_context ctx ON rc.contextid = ctx.id 
                    LEFT JOIN mdl_user u ON u.id = ctx.instanceid
                WHERE rc.capability = :capability AND rc.roleid > 1 AND rc.contextid <> 1 AND permission = 1";
        $get_list = $DB->get_records_sql($sql, ['capability' => $capability]);
       
        if($get_list) {

            $output = '';
            $stt = 1;
            $userurl = $CFG->wwwroot . '/user/profile.php?id=';
            foreach($get_list as $value) {
                $output .= '<li><a href="'.$userurl.$value->userid.'">' . $stt . '. ' .$value->fullname . '</a></li>';
                $stt++;
            }
            $data['success'] = $output;
        } else {
            $data['success'] = get_string('gl_empty_listuser', 'local_newsvnr');
        }
        break;
    case 'acceptfile': 
        $id   = required_param('id', PARAM_INT);
        // Update bảng files
        $newfiles = new stdClass;
        $newfiles->id = $id;
        $newfiles->status = 0;
        $newrequestfile = new stdClass;
        $DB->update_record('files', $newfiles);

        // Update bảng file request
        $fileid = $DB->get_field('files_request', 'id', ['fileid' => $id]);
        $newrequestfile->id = $fileid;
        $newrequestfile->fileid = $id;
        $newrequestfile->status = 1;
        $newrequestfile->reviewer = $USER->id;
        $newrequestfile->timemodified = time();
        $DB->update_record('files_request', $newrequestfile);

        echo json_encode('OK');
        // $fs = get_file_storage();
        // $filepath = file_correct_filepath($filepath);
        // $return = new stdClass();
        // if ($stored_file = $fs->get_file($context->id, 'local_newsvnr', 'content', 0, $filepath, $filename)) {
        //     $parent_path = $stored_file->get_parent_directory()->get_filepath();
        //     if ($stored_file->is_directory()) {
        //         $files = $fs->get_directory_files($context->id, 'local_newsvnr', 'content', 0, $filepath, true);
        //         foreach ($files as $file) {
        //             $file->delete();
        //         }
        //         $stored_file->delete();
        //         $return->filepath = $parent_path;
        //         echo json_encode($return);
        //     } else {
        //         if($result = $stored_file->delete()) {
        //             $return->filepath = $parent_path;
        //             echo json_encode($return);
        //         } else {
        //             echo json_encode(false);
        //         }
        //     }
        // } else {
        //     echo json_encode(false);
        // }
        die;
    case 'deletefile': 
        $filename   = required_param('filename', PARAM_FILE);
        $filepath   = required_param('filepath', PARAM_PATH);

        $fs = get_file_storage();
        $filepath = file_correct_filepath($filepath);
        $return = new stdClass();
        if ($stored_file = $fs->get_file($context->id, 'local_newsvnr', 'content', 0, $filepath, $filename)) {
            $parent_path = $stored_file->get_parent_directory()->get_filepath();
            $id = $stored_file->get_id();
            $fileid = $DB->get_field('files_request', 'id', ['fileid' => $id]);
            $newrequestfile = new stdClass;
            $newrequestfile->id = (int)$fileid;
            $newrequestfile->status = 2;
            $newrequestfile->reviewer = $USER->id;
            $newrequestfile->timemodified = time();
            if ($stored_file->is_directory()) {
                $files = $fs->get_directory_files($context->id, 'local_newsvnr', 'content', 0, $filepath, true);
                foreach ($files as $file) {
                    $file->delete();
                }
                $stored_file->delete();
                $return->filepath = $parent_path;
                $DB->update_record('files_request', $newrequestfile);

                echo json_encode($return);
            } else {
                if($result = $stored_file->delete()) {
                    $return->filepath = $parent_path;
                    $DB->update_record('files_request', $newrequestfile);
                    echo json_encode($return);
                } else {
                    echo json_encode(false);
                }
            }
            
        } else {
            echo json_encode(false);
        }
        die;
    case 'checkoles':
        $userid = optional_param('userid',0, PARAM_INT);
        $contexid = context_user::instance($userid)->id;
        echo json_encode($contexid,JSON_UNESCAPED_UNICODE);
        die;
    case 'acceptFileSelect':
        $dataSelect = json_decode($_POST['dataSelect']);
        foreach ($dataSelect as $file) {
            $newfiles = new stdClass;
            $newfiles->id = $file->id;
            $newfiles->status = 0;
            $DB->update_record('files', $newfiles);

            $newrequestfile = new stdClass;
            $fileid = $DB->get_field('files_request', 'id', ['fileid' => $file->id]);
            $newrequestfile->id = $fileid;
            $newrequestfile->fileid = $file->id;
            $newrequestfile->status = 1;
            $newrequestfile->reviewer = $USER->id;
            $newrequestfile->timemodified = time();
            $DB->update_record('files_request', $newrequestfile);
        }
        echo json_encode('OK');
        die();
    default:
        # code...
        break;
}

echo json_encode($data,JSON_UNESCAPED_UNICODE);