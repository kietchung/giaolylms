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

defined('MOODLE_INTERNAL') || die();

define('MICROSOFTGRAPH_BASEURL', 'https://graph.microsoft.com');

require_once($CFG->libdir.'/filelib.php');
use theme_moove\util\theme_settings;

/**
 * Lấy danh sách các bài đăng tin tức chung do quản trị viên đăng
 * @param  [type] $id [description]
 * @return [type]  $forumarr   [description]
 */
function get_forums_header_data_id($id) {
    global $DB,$CFG,$COURSE;
    $sql = "
            SELECT CONCAT(u.firstname,' ',u.lastname) AS username,fp.userid AS userid, fd.id AS discussionid,fd.name,fp.id AS postid ,fd.timemodified,fp.message
            FROM mdl_forum_discussions fd 
                JOIN {forum_posts} fp ON fd.id = fp.discussion 
                JOIN {user} u ON  fp.userid = u.id 
            WHERE fd.forum = 85 AND fd.id = ?";
    $data = $DB->get_records_sql($sql,array($id));
    $forumarr = array();
    $newspage = new \local_newsvnr\output\news_page();
    $modcontext = context_module::instance(306);
    foreach ($data as $file) {
        $isimage = true;
        $forumstd = new stdClass();
        $time = $newspage::convertunixtime('l, d m Y, H:i A',$file->timemodified,'Asia/Ho_Chi_Minh');
        $userlink = $CFG->wwwroot."/user/profile.php?id=".$file->userid;
        $forumstd->title = $file->name;
        $forumstd->content = file_rewrite_pluginfile_urls($file->message, 'pluginfile.php', $modcontext->id, 'mod_forum', 'post', $file->postid);
        $forumstd->time = $time;
        $forumstd->discussionid = $file->discussionid;
        $forumstd->discussionart = \html_writer::link($userlink,$file->username);
        $forumarr[] = $forumstd;
    }
    return $forumarr;
}
/**
 * Lấy context của discussion
 * @param  [type] $course_id    [description]
 * @param  [type] $discussionid [description]
 * @return [type]               [description]
 */
function get_context_module($course_id, $discussionid) {
    global $DB;
    $sql = "
            SELECT DISTINCT cm.id AS context_id FROM mdl_course_modules cm JOIN mdl_forum f ON cm.instance = f.id
                JOIN mdl_forum_discussions fd ON f.id = fd.forum
                JOIN mdl_forum_posts fp ON fd.id = fp.discussion
            WHERE  fd.id = ? AND cm.course = ?";
    $data = $DB->get_record_sql($sql,array($course_id, $discussionid));
    return $data;
}

/**
 * Lấy hình ảnh của bài post
 * @param  [type] $firstpost_id [description]
 * @return [type]               [description]
 */
function get_all_image_of_discussion($firstpost_id) {
    global $DB;
    $sql = "SELECT f.id as file_id, f.component, f.filepath, f.filearea, f.contextid, f.itemid, f.filename FROM mdl_files f 
        WHERE f.itemid = ? AND f.filesize > 0
                AND (f.filearea = 'attachment' OR f.filearea = 'post')";
    $data = $DB->get_records_sql($sql, array($firstpost_id));
    return $data;
}

/**
 * Lấy bài đăng discustion(forum) mới nhất
 * @param  [type] $discussionid [description]
 * @return [type]               [description]
 */
function get_froums_coursenews_data_id($discussionid)
{
    global $DB,$CFG,$OUTPUT;
    $sql = "
            SELECT  d.id  AS discussionid, d.countviews, CONCAT(u.firstname,' ',u.lastname) AS username, f.course, p.userid AS userid,d.id,p.message,p.subject,p.modified ,p.id AS postid,d.timemodified, d.firstpost,p.messagetrust,p.messageformat,f.id forumid
            FROM mdl_forum f 
                JOIN mdl_forum_discussions d ON f.id=d.forum AND f.course=d.course 
                JOIN mdl_forum_posts p ON d.id = p.discussion JOIN mdl_user u ON p.userid = u.id
            WHERE f.type='news' AND p.parent = 0 AND d.id = ?";
    $forumdata = $DB->get_recordset_sql($sql,array($discussionid));
    $forumarr = array();
    $course_id = 0;
    
    foreach ($forumdata as $key => $value) {
        $datauser = $DB->get_record_sql('SELECT * FROM {user} u WHERE u.id = :userid ',['userid' => $value->userid]);
        $isimage = true;
        $forumstd = new stdClass();
        $image_data = get_all_image_of_discussion($value->firstpost);
        $time = convertunixtime('l, d m Y, H:i A',$value->timemodified,'Asia/Ho_Chi_Minh');
        $course_id = $value->course;
        $context_module = get_context_module($discussionid, $course_id);
        $modcontext = context_module::instance($context_module->context_id);
        $forumstd->time = $time;
        $forumstd->title = $value->subject;
        $forumstd->useravatar = $OUTPUT->user_picture($datauser, array('size' => 80, 'link' => false));
        $forumstd->discussionid = $value->discussionid;
        $userlink = $CFG->wwwroot."/user/profile.php?id=".$value->userid;
        $forumstd->content2 = file_rewrite_pluginfile_urls($value->message, 'pluginfile.php', $modcontext->id, 'mod_forum', 'post', $value->postid);
        $forumstd->discussionart = \html_writer::link($userlink,$value->username);
        foreach ($image_data as $key => $image) {
            $forumstd->arrImage[] = file_encode_url("$CFG->wwwroot/pluginfile.php",
                             '/'. $image->contextid. '/'. $image->component. '/'.
                             $image->filearea. $image->filepath.$image->itemid.'/'. $image->filename, !$isimage);

        }
        $fs = get_file_storage();
        $imagereturn = '';
        $post = $DB->get_record('forum_posts',['id' => $value->postid]);
        $cm = get_coursemodule_from_instance('forum', $value->forumid, $value->course, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $files = $fs->get_area_files($context->id, 'mod_forum', 'attachment', $post->id, "filename", false);
        
        if ($files) {
            foreach ($files as $file) {
                $filename = $file->get_filename();
                $mimetype = $file->get_mimetype();
                $iconimage = $OUTPUT->pix_icon(file_file_icon($file), get_mimetype_description($file), 'moodle', array('class' => 'icon'));
                $path = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$context->id.'/mod_forum/attachment/'.$post->id.'/'.$filename);
                $imagereturn .= $path;
            }
        }

        if(!$imagereturn) {
            $imagereturn = $OUTPUT->get_generated_image_for_id($value->postid);
        }
        $forumstd->backgroundimg = $imagereturn;
        $options = new stdClass;
        $options->para    = false;
        $options->trusted = $value->messagetrust;
        $options->context = $modcontext;
        $forumstd->content = format_text($forumstd->content2, $value->messageformat, $options, $course_id);
        $forumarr[] = $forumstd;
    }
    return $forumarr;
}

function get_comment_from_disccusion($id_discus) {
    global $DB, $USER,$PAGE,$OUTPUT;
    $sql = "SELECT lnc.id, lnc.userid ,CONCAT(u.firstname, ' ', u.lastname) AS fullname, lnc.content, lnc.createdAt
            FROM mdl_local_newsvnr_comments lnc 
                JOIN mdl_user u ON   lnc.userid =  u.id 
                JOIN mdl_forum_discussions fd ON lnc.discussionid =  fd.id
            WHERE lnc.discussionid = ?
            ORDER BY lnc.id DESC 
            OFFSET 0 ROWS FETCH NEXT 5 ROWS only";
    $data = $DB->get_records_sql($sql, array($id_discus));
    $forumarr = array();
    $i = 0;
    foreach ($data as $key => $comment) {
        $datauser = $DB->get_record_sql('SELECT * FROM {user} u WHERE u.id = :commentid ',['commentid' => $comment->userid]);
        $key = 'key_comment'.$i;
        $forumstd = new stdClass();
        $forumstd->useravatar = $OUTPUT->user_picture($datauser);
        $forumstd->id = $comment->id;
        $forumstd->userid = $comment->userid;
        $forumstd->fullname_comment = $comment->fullname;
        $forumstd->content_comment = $comment->content;
        $forumstd->createdAt_comment = converttime($comment->createdat);
        $reply_comment_data =  get_replies_from_comment($comment->id);
        $forumstd->countreply = count($reply_comment_data);
        $userid = $USER->id;
        // check role for deleted comment
        if(is_siteadmin() || $comment->userid  == $userid) {
             $forumstd->label_delete = '<label class="delete" onclick="DeleteComment('. $comment->id .')" id="'. $comment->id .'">'.get_string('delete').'</label>';
        }
        $i++;
        $id_comment = $comment->id;
        if(!empty($id_comment))
        {
            $reply_comment_data =  get_replies_from_comment($id_comment);
            foreach ($reply_comment_data as $key => $reply) {
                    $forumstd_reply = new stdClass();
                    $forumstd_reply->id_reply = $reply->id;
                    $forumstd_reply->fullname_reply = $reply->fullname;
                    $forumstd_reply->content_reply = $reply->content;
                    $forumstd_reply->createdAt_reply = converttime($reply->createdat);
                    $datauserreply = $DB->get_record_sql('SELECT * FROM {local_newsvnr_replies} r JOIN {user} u ON u.id = r.userid WHERE r.id = :id',['id' => $reply->id]);
                    $forumstd_reply->userid = $datauserreply->userid;
                    $forumstd_reply->userreplyavatar = $OUTPUT->user_picture($datauserreply);
                    if(is_siteadmin() || $forumstd_reply->userid  == $userid) {
                         $forumstd_reply->label_deletereply = '<label class="delete_reply mr-2" onclick="DeleteReply('. $reply->id .')" id="'. $reply->id .'">'.get_string('delete').'</label>';
                    }
                    if($id_comment == $reply->commentid)
                        $forumstd->reply[] = $forumstd_reply;
                        $forumstd->isreply = true;
            }
            $forumarr[] = $forumstd;
        }
    }
    return $forumarr;
}

function get_replies_from_comment($id_comment) {
    global $DB;

    $sql = "
            SELECT lnr.id, lnr.content, lnr.createdAt, CONCAT(u.firstname, ' ', u.lastname) AS fullname, lnr.commentid 
            FROM mdl_local_newsvnr_replies lnr
                JOIN mdl_local_newsvnr_comments lnc ON lnr.commentid = lnc.id
                JOIN mdl_user u ON lnr.userid = u.id
            WHERE lnr.commentid = ?";
    $data = $DB->get_records_sql($sql, array($id_comment));
    return $data;
}

function pagination_comment($id_discus, $offset, $limit) {
    global $DB;
    $sql = "
            SELECT lnc.id, lnc.userid, CONCAT(u.firstname, ' ', u.lastname) AS fullname, lnc.content, lnc.createdAt
            FROM mdl_local_newsvnr_comments lnc 
                JOIN mdl_user u ON   lnc.userid =  u.id 
                JOIN mdl_forum_discussions fd ON lnc.discussionid =  fd.id
            WHERE lnc.discussionid = ?
            ORDER BY lnc.id DESC
            OFFSET $offset ROWS
            FETCH next $limit ROWS only;";
    $data = $DB->get_records_sql($sql, array($id_discus));
    return $data;
}

function append_comments_after_delete($id_discus, $offset, $limited) {
    global $DB;
    $sql = "SELECT lnc.id, lnc.userid, CONCAT(u.firstname, ' ', u.lastname) AS fullname, lnc.content, lnc.createdAt
            FROM mdl_local_newsvnr_comments lnc 
                JOIN mdl_user u ON   lnc.userid =  u.id 
                JOIN mdl_forum_discussions fd ON lnc.discussionid =  fd.id
            WHERE lnc.discussionid = ?
            ORDER BY lnc.id DESC
            OFFSET $offset ROWS
            FETCH next $limited ROWS only;";
    $data = $DB->get_record_sql($sql, array ($id_discus));
    return $data;
}

function get_course_id($discussionid) {
    global $DB;
    $sql = "
            SELECT fd.course AS course_id 
            FROM mdl_forum_discussions fd 
            WHERE fd.id = ?";
    $data = $DB->get_record_sql($sql, array($discussionid));
    return $data;
}

function get_forums_lq_data($course_id, $current_discussion) {
    global $DB,$CFG;
    $sql = "
            SELECT d.countviews, CONCAT(u.firstname,' ',u.lastname) AS name,p.userid AS userid, d.id AS discussionid , d.timemodified,p.message,p.subject,p.modified,fn.contextid,fn.component,fn.filearea,fn.filepath,fn.itemid,fn.filename 
            FROM mdl_forum f 
                JOIN mdl_forum_discussions d ON f.id=d.forum JOIN mdl_forum_posts p ON d.id = p.discussion JOIN mdl_files fn ON d.firstpost = fn.itemid 
                JOIN mdl_user u ON p.userid = u.id
            WHERE f.course = ? AND f.type='news' AND fn.filesize>0 AND fn.filearea='attachment' AND d.id <> ?";
    $forumimg = $DB->get_recordset_sql($sql, array($course_id, $current_discussion));
    $contentimage = '';
    $forumarr = array();
    $i = 1;
    foreach ($forumimg as $file) {

        $count_comment = get_count_comment_by_discussionid($file->discussionid);

        $key = 'key'.$i;
        $isimage = true;
        $forumstd = new stdClass();
        $imageurl = file_encode_url("$CFG->wwwroot/pluginfile.php",
            '/'. $file->contextid. '/'. $file->component. '/'.
            $file->filearea. $file->filepath.$file->itemid.'/'. $file->filename, !$isimage);
        $link = $CFG->wwwroot."/local/newsvnr/news.php?id=".$file->discussionid;;
        $time = convertunixtime('l, d m Y, H:i A',$file->timemodified,'Asia/Ho_Chi_Minh');
        $userlink = $CFG->wwwroot."/user/profile.php?id=".$file->userid;
        $forumstd->newsurl = $link;
        $forumstd->discussionid = $file->discussionid;
        $forumstd->title = $file->subject;
        $forumstd->content = strip_tags($file->message);
        $forumstd->discussionart = \html_writer::link($userlink,$file->name);
        $forumstd->image = $imageurl;
        $forumstd->countviews = $file->countviews;
        $forumstd->time = $time;
        $forumstd->timeago = converttime($file->timemodified);
        $forumstd->name = $file->name;
        if(!empty($count_comment))
            $forumstd->countcomments = $count_comment->countcomments;
        else
            $forumstd->countcomments = 0;
        $forumstd->$key = true;
        $forumarr[] = $forumstd;
        $i++;
    }
    return $forumarr;
}

function forum_get_discussion_subscription_icon_newsvnr($forum, $discussionid, $returnurl = null, $includetext = false) {
    global $USER, $OUTPUT, $PAGE;
    if ($returnurl === null && $PAGE->url)
        $returnurl = $PAGE->url->out();
    $o = '';
    $subscriptionstatus = \mod_forum\subscriptions::is_subscribed($USER->id, $forum, $discussionid);
    $subscriptionlink = new moodle_url('/mod/forum/subscribe.php', array(
        'sesskey' => sesskey(),
        'id' => $forum->id,
        'd' => $discussionid,
        'returnurl' => $returnurl,
    ));
    if ($subscriptionstatus) {
        $output = html_writer::start_tag('i', array('class' => 'fa fa-rss-square','style' => 'color:#ff6a00','aria-hidden' => 'true','aria-label' => get_string('clicktounsubscribe', 'forum'),'aria-title' => get_string('clicktounsubscribe', 'forum')));
        $output.= html_writer::end_tag('i');
        return html_writer::link($subscriptionlink, $output, array(
                'title' => get_string('clicktounsubscribe', 'forum'),
                'class' => 'discussiontoggle',
                'data-forumid' => $forum->id,
                'data-discussionid' => $discussionid,
                // 'data-includetext' => $includetext,
            ));

    } else {
        $output = html_writer::start_tag('i', array('class' => 'fa fa-rss-square','style' => 'color:black','aria-hidden' => 'true','aria-label' => get_string('clicktosubscribe', 'forum'),'aria-title' => get_string('clicktosubscribe', 'forum')));
        $output.= html_writer::end_tag('i');

        return html_writer::link($subscriptionlink, $output, array(
                'title' => get_string('clicktosubscribe', 'forum'),
                'class' => 'discussiontoggle ',
                'data-forumid' => $forum->id,
                'data-discussionid' => $discussionid,
                // 'data-includetext' => $includetext,
            ));
    }
}

function news_countviews($discussionid)
{
    global $DB;
    $query = "UPDATE {forum_discussions}
              SET countviews = countviews + 1 
              WHERE id = :discussionid";
    $params = array(
            'discussionid' => $discussionid
    );
    $DB->execute($query,$params);
}

function newsvnr_get_search_query($search, $tablealias = '') {
    global $DB;
    $params = array();
    if (empty($search)) {
        // This function should not be called if there is no search string, just in case return dummy query.
        return array('1=1', $params);
    }
    if ($tablealias && substr($tablealias, -1) !== '.') {
        $tablealias .= '.';
    }
    $searchparam = '%' . $DB->sql_like_escape($search) . '%';
    $conditions = array();
    //fields muốn tìm kiếm
    $fields = array('name', 'idnumber', 'description');
    $cnt = 0;
    foreach ($fields as $field) {
        $conditions[] = $DB->sql_like($tablealias . $field, ':csearch' . $cnt, false);
        $params['csearch' . $cnt] = $searchparam;
        $cnt++;
    }
    $sql = '(' . implode(' OR ', $conditions) . ')';
    return array($sql, $params);
}

function get_count_comment_by_discussionid($discussionid) {
     global $DB;

    $sql = "
            SELECT DISTINCT fd.id AS disid,
                ((SELECT count(id) 
                    FROM mdl_local_newsvnr_comments lnc 
                    WHERE lnc.discussionid = fd.id) + (SELECT count(lnr.id) FROM mdl_local_newsvnr_replies lnr 
                        LEFT JOIN mdl_local_newsvnr_comments lnc ON lnr.commentid = lnc.id
                    WHERE lnc.discussionid = fd.id)) AS countcomments
            FROM mdl_forum_discussions fd JOIN mdl_forum_posts fp ON fd.id = fp.discussion
            WHERE fd.id = ?";
    $data = $DB->get_record_sql($sql, array($discussionid));
    return $data;
}

function get_list_course_have_forum_news() {
    global $DB;
    $sql =  "
            SELECT c.id AS courseid, c.fullname
            FROM mdl_course c
                JOIN mdl_forum f ON c.id = f.course
                JOIN mdl_forum_discussions fd ON fd.forum = f.id 
                JOIN mdl_files fn ON fd.firstpost = fn.itemid
            WHERE f.type = 'news' AND fn.filesize>0 AND fn.filearea = 'attachment'
            GROUP BY c.id, c.fullname";
    $data = $DB->get_records_sql($sql);
    return array_values($data);
}
/**
 * Thêm mới xóa sửa 1 danh mục phòng ban
 * @param [object] $orgcate
 */
function insert_orgcategory($orgcate) {
    global $DB;
    return $DB->insert_record('orgstructure_category',$orgcate);
}

function update_orgcategory($orgcate) {
    global $DB;
    return $DB->update_record('orgstructure_category',$orgcate);
}

function delete_orgcategory($orgcateid) {
    global $DB;
    return $DB->delete_records('orgstructure_category',array('id' => $orgcateid));
}
/**
 * Thêm mới xóa sửa 1 phòng ban
 * @param [object] $orgstructure [description]
 */
function insert_orgstructure($orgstructure) {
    global $DB;
    return $DB->insert_record('orgstructure',$orgstructure);
}

function update_orgstructure($orgstructure) {
    global $DB;
    return $DB->update_record('orgstructure',$orgstructure);
}

function delete_orgstructure($orgstructure) {
    global $DB;
    return $DB->delete_records('orgstructure',array('id' => $orgstructure));
}
/**
 * Thêm mới 1 chức danh
 * @param [object] $orgjobtitle [description]
 */
function insert_orgjobtitle($orgjobtitle) {
    global $DB;
    return $DB->insert_record('orgstructure_jobtitle',$orgjobtitle);
}

function update_orgjobtitle($orgjobtitle) {
    global $DB;
    return $DB->update_record('orgstructure_jobtitle',$orgjobtitle);
}

function delete_orgjobtitle($orgjobtitle) {
    global $DB;
    return $DB->delete_records('orgstructure_jobtitle',array('id' => $orgjobtitle));
}
/**
 * Thêm mới xóa sửa 1 chức vụ
 * @param [oject] $orgposition [description]
 */
function insert_orgposition($orgposition) {
    global $DB;
    return $DB->insert_record('orgstructure_position',$orgposition);
}

function update_orgposition($orgposition) {
    global $DB;
    return $DB->update_record('orgstructure_position',$orgposition);
}

function delete_orgposition($orgposition) {
    global $DB;
    return $DB->delete_records('orgstructure_position',array('id' => $orgposition));
}
/**
 * Func tạo Treeview
 */
function showMenuLi($menus, $table_name) {
    switch ($table_name) {
        case 'mdl_orgstructure':
            $arr = $data = array();
            foreach ($menus as $value) {
                $arr['id'] = $value->id;
                $arr['text'] = $value->name;
                $arr['parentid'] = $value->parentid;
                $arr['encoded'] = false;
                $arr['expanded'] = true;
                $data[] = $arr;
            }
            if(!empty($data)) {
                foreach($data as $key => &$item) {

                $itemsByReference[$item['id']] = &$item;
                // Children array:
                $itemsByReference[$item['id']]['items'] = array();
                // Empty data class (so that json_encode adds "data: {}" )
                }
                // Set items as children of the relevant parent item.
                foreach($data as $key => &$item)
                if($item['parentid'] && isset($itemsByReference[$item['parentid']]))
                    $itemsByReference [$item['parentid']]['items'][] = &$item;
                // Remove items that were added to parents elsewhere:
                foreach($data as $key => &$item) {
                if($item['parentid'] && isset($itemsByReference[$item['parentid']]))
                    unset($data[$key]);
                }
            }
            // Encode:
            if(empty($data)) {
                $data['message'] = get_string('emptyorgstructure', 'local_newsvnr');
            }
            echo json_encode($data);
            break;
        case 'mdl_competency':
            $arr = array();
            foreach ($menus as $value) {
                $arr['id'] = $value->id;
                $arr['text'] = $value->shortname;
                $arr['parentid'] = $value->parentid;
                $arr['encoded'] = false;
                // $arr['expanded'] = true;
                $data[] = $arr;
            }

            foreach($data as $key => &$item) {

               $itemsByReference[$item['id']] = &$item;
               // Children array:
               $itemsByReference[$item['id']]['items'] = array();
               // Empty data class (so that json_encode adds "data: {}" )
            }
            // var_dump($data);die;
            // Set items as children of the relevant parent item.
            foreach($data as $key => &$item) {
               if($item['parentid'] && isset($itemsByReference[$item['parentid']]))
                  $itemsByReference [$item['parentid']]['items'][] = &$item;
            }
            //Remove items that were added to parents elsewhere:
            foreach($data as $key => &$item) {
               if($item['parentid'] && isset($itemsByReference[$item['parentid']]))
                  unset($data[$key]);

            }
            echo json_encode(array_values($data));
            break;
        case 'mdl_course_categories':
            $arr = array();
            foreach ($menus as $value) {
                $arr['id'] = $value->id;
                $arr['text'] = $value->name;
                $arr['parent'] = $value->parent;
                $arr['encoded'] = false;

                // $arr['expanded'] = true;
                $data[] = $arr;
            }
            foreach($data as $key => &$item) {

               $itemsByReference[$item['id']] = &$item;
               // Children array:
               $itemsByReference[$item['id']]['items'] = array();
               // Empty data class (so that json_encode adds "data: {}" )
            }
            // Set items as children of the relevant parent item.
            foreach($data as $key => &$item) {
               if($item['parent'] && isset($itemsByReference[$item['parent']]))
                  $itemsByReference [$item['parent']]['items'][] = &$item;
            }
            //Remove items that were added to parents elsewhere:
            foreach($data as $key => &$item) {
               if($item['parent'] && isset($itemsByReference[$item['parent']]))
                  unset($data[$key]);

            }
            echo json_encode(array_values($data));
            break;
        default:
            break;
    }

}
/**
 * Lây tên chức danh từ id
 * @param  [int] $jobtitleid [description]
 * @return [type]             [description]
 */
function get_name_orgjobtitleid($jobtitleid) {
    global $DB;
    $query = "
            SELECT name
            FROM {orgstructure_jobtitle}
            WHERE id = ?";
    $jobtitlename = $DB->get_field_sql($query,array($jobtitleid));
    return $jobtitlename;
}
/**
 * Lây tên phòng ban từ id
 * @param  [int] $orgstructureid [description]
 * @return [type]             [description]
 */
function get_name_orgstructureid($orgstructureid) {
    global $DB;
    $query = "
            SELECT name
            FROM {orgstructure}
            WHERE id = ?";
    $orgstructurename = $DB->get_field_sql($query,array($orgstructureid));
    return $orgstructurename;
}
/**
 * Lây tên loại phòng ban từ id
 * @param  [int] $orgcateid [description]
 * @return [type]             [description]
 */
function get_name_orgcateid($orgcateid)
{
    global $DB;
    $query = "
            SELECT name
            FROM {orgstructure_category}
            WHERE id = ?";
    $orgcatename = $DB->get_field_sql($query,array($orgcateid));
    return $orgcatename;
}
/**
 * Lây tên  nhân viên
 * @param  [int] $userid [description]
 * @return [type]             [description]
 */
function get_name_userid($userid)
{
    global $DB;
    $query = "
            SELECT CONCAT(firstname,' ',lastname) AS name
            FROM {user}
            WHERE id = ?";
    $username = $DB->get_field_sql($query,array($userid));
    return $username;
}
/**
 * Lây tên phòng ban cha
 * @param  [int] $orgstructureid [description]
 * @return [type]             [description]
 */
function get_name_parentid($parentid)
{
    global $DB;
    $query = "
            SELECT name
            FROM {orgstructure}
            WHERE id = ?";
    $parentname = $DB->get_field_sql($query,array($parentid));
    return $parentname;
}
/**
 * Tạo mã ngẫu nhiên
 * @param  [type] $chars [description]
 * @return [type]        [description]
 */
function password_generate($chars) {
  $data = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcefghijklmnopqrstuvwxyz';
  return substr(str_shuffle($data), 0, $chars);
}

/* Cơ cấu phòng ban */

function get_listusers_orgstructure($orgstructure, $pageskip, $pagetake, $q){
    global $DB;
    $odersql = "";
    $wheresql = "";
    if($q) {
        $wheresql = "WHERE o.id = $orgstructure AND u.usercode LIKE N'%$q%'";
    } else {
        $wheresql = "WHERE o.id = $orgstructure";
    }
    if($pagetake == 0) {
        $ordersql = "ORDER BY uname DESC";
    } else {
        $ordersql = "ORDER BY uname DESC OFFSET $pageskip ROWS FETCH NEXT $pagetake ROWS only";
    }
    $query ="
            SELECT u.id AS userid,u.usercode,CONCAT(u.firstname,' ',u.lastname) AS uname,op.name AS opname,o.name AS oname, op.id AS positionid, (SELECT COUNT(*) FROM mdl_user u 
                LEFT JOIN mdl_orgstructure_position op ON u.orgpositionid = op.id
                LEFT JOIN mdl_orgstructure o ON o.id = op.orgstructureid 
                $wheresql
              ) AS total
            FROM mdl_user u 
                LEFT JOIN mdl_orgstructure_position op ON u.orgpositionid = op.id 
                LEFT JOIN mdl_orgstructure o ON op.orgstructureid = o.id 
                LEFT JOIN mdl_competency_position cp ON cp.id = op.id
            $wheresql
            GROUP BY u.usercode,u.firstname,u.lastname,op.name,o.name,op.id,u.id
            $ordersql";
    $data = $DB->get_records_sql($query,[]);
    return $data;
}
function get_user_with_usercode($userid){
    global $DB;
    $query ="
            SELECT u.id AS userid,u.usercode,CONCAT(u.firstname,' ',u.lastname) AS uname,op.name AS opname,o.name AS oname, op.id AS positionid
            FROM mdl_user u 
                LEFT JOIN mdl_orgstructure_position op ON u.orgpositionid = op.id 
                LEFT JOIN mdl_orgstructure o ON op.orgstructureid = o.id 
                LEFT JOIN mdl_competency_position cp ON cp.id = op.id
            WHERE u.usercode = ?";
    $data = $DB->get_records_sql($query,[$userid]);
    return $data;
}

function get_tcnl($orgpositionid){
    global $DB;
    $query ="
            SELECT COUNT(cp.competencyid) AS tcnlcd 
            FROM mdl_competency_position cp 
            WHERE cp.positionid = ?";
    $data = array_values($DB->get_records_sql($query,[$orgpositionid]));
    return $data;
}

function get_name_competency_position($orgpositionid)
{
    global $DB;
    $query ="
            SELECT cp.competencyid,c.shortname 
            FROM mdl_competency_position cp 
                LEFT JOIN mdl_competency c ON cp.competencyid = c.id 
            WHERE cp.positionid = ? 
            ORDER BY c.shortname";
    $data = array_values($DB->get_records_sql($query,[$orgpositionid]));
    return $data;
}


function get_competency_position($orgpositionid)
{
    global $DB;
    $query ="
            SELECT cp.competencyid 
            FROM mdl_competency_position cp 
            WHERE cp.positionid = ? 
            GROUP BY cp.competencyid";
    $data = array_values($DB->get_records_sql($query,[$orgpositionid]));
    return $data;
}
/* Lấy thông tin chi tiết phòng ban */
function get_detail_orgstructure($orgstructure){
    global $DB;
    $query = "
            SELECT * 
            FROM {orgstructure} 
            WHERE id = ?";
    $data = $DB->get_record_sql($query,[$orgstructure]);
    return $data;
}

/* Lấy thông tin nhân viên theo phòng ban*/
function get_user_detail_orgstructure($userid){
    global $DB;
    $query = "
            SELECT u.usercode, CONCAT(firstname,' ',lastname) AS username, o.name AS orgstructurename, op.name AS orgpositionname 
            FROM mdl_user u 
                LEFT JOIN mdl_orgstructure_position op On u.orgpositionid = op.id  
                LEFT JOIN mdl_orgstructure o On op.orgstructureid = o.id 
            WHERE u.usercode = ?";
    $data = array_values($DB->get_records_sql($query,[$userid]));
    return $data;
}
/*
    Lấy id userevidencecomp
 */
function get_userevidencecompid($userid,$competencyid) {
    global $DB;
    $query = "
              SELECT ue.id
              FROM mdl_competency_userevidence ue 
                JOIN mdl_competency_userevidencecomp uec ON ue.id = uec.userevidenceid 
              WHERE ue.userid= ? AND uec.competencyid = ?";
    $data = $DB->get_fieldset_sql($query,[$userid,$competencyid]);
    return $data;
}
/*
    Lấy chứng chỉ bên ngoài theo userid và compid
 */
function get_userevidence($userevidenceid,$competencyid) {
    global $DB;
    $query = "
                SELECT uc.*,ue.name,uc.id as ucid
                FROM {competency_usercomp} uc
                  JOIN {competency_userevidencecomp} uec
                    ON uc.competencyid = uec.competencyid
                  JOIN {competency_userevidence} ue
                    ON uec.userevidenceid = ue.id
                   AND uc.userid = ue.userid
                   AND ue.id = ?
                WHERE uc.competencyid = ?
                ORDER BY uc.id ASC";
    $data = $DB->get_record_sql($query,[$userevidenceid,$competencyid]);
    return $data;
}

/**
 * End cơ cấu phòng ban
 */

/**
 * Dashboard
 */
function get_teachername($courseid) {
    global $DB,$CFG;
    $arrc = array();
    $infoteacher = array();
    $sql = "SELECT concat(u.firstname,' ',u.lastname) as fullnamet,u.id
    from {role_assignments} as ra
    join {user} as u on u.id= ra.userid
    join {user_enrolments} as ue on ue.userid=u.id
    join {enrol} as e on e.id=ue.enrolid
    join {course} as c on c.id=e.courseid
    join {context} as ct on ct.id=ra.contextid and ct.instanceid= c.id
    join {role} as r on r.id= ra.roleid
    where c.id=? and ra.roleid=3";
    $rolecourse = $DB->get_records_sql($sql,array($courseid,$courseid));
    if (!empty($rolecourse)) {
        foreach ($rolecourse as $value) {
            $infoteacher[] = "<a href='$CFG->wwwroot/user/profile.php?id=$value->id' /> $value->fullnamet </a>";
        }
        $teachername = implode(', ',$infoteacher);
    } else
    $teachername = '';

    return $teachername;

}

function get_positionname($orgpositionid) {
    global $DB;
    $query = "
            SELECT name 
            FROM {orgstructure_position} 
            WHERE id=?";
    $data = $DB->get_field_sql($query,[$orgpositionid]);
    return $data;
}

function get_newest_badge() {
    global $DB;
    $query ="
            SELECT TOP 3 bi.*, b.name,b.description, b.issuername, CONCAT(u.firstname,' ',u.lastname) AS fullname
            FROM mdl_badge_issued bi 
                LEFT JOIN mdl_user u  ON u.id = bi.userid
                LEFT JOIN mdl_badge b ON bi.badgeid=b.id
            ORDER BY bi.dateissued DESC
    ";
    $data = $DB->get_records_sql($query);
    return $data;
}
/**
 * Lấy danh sách khóa học của giáo viên
 * @param  [type] $userid [description]
 * @return [array] $list_courseid [description]
 */
function get_list_course_by_teacher($userid) {
    global $DB;
    $list_courseid = [];
    $list_course_by_user_sql = "
                                SELECT c.fullname,c.id,c.shortname
                                FROM mdl_role_assignments AS ra
                                    JOIN mdl_user AS u ON u.id= ra.userid
                                    JOIN mdl_user_enrolments AS ue ON ue.userid=u.id
                                    JOIN mdl_enrol AS e ON e.id=ue.enrolid
                                    JOIN mdl_course AS c ON c.id=e.courseid
                                    JOIN mdl_context AS ct ON ct.id=ra.contextid AND ct.instanceid= c.id
                                    JOIN mdl_role AS r ON r.id= ra.roleid
                                WHERE  ra.roleid=3 AND u.id = ?";
    $list_course_by_user_ex = $DB->get_records_sql($list_course_by_user_sql,[$userid]);
    if ($list_course_by_user_ex) {
        foreach ($list_course_by_user_ex as $value)
            $list_courseid[] = $value;
    }

    return $list_courseid;
}

/**
 * Lấy danh sách khóa học full thông tin của giáo viên
 * @param  [type] $userid [description]
 * @return [array] $list_courseid [description]
 */
function get_list_courseinfo_by_teacher($userid)
{
    global $DB;
    $list_courseid = [];
    $list_course_by_user_sql = "
                                SELECT c.*
                                FROM mdl_role_assignments AS ra
                                    JOIN mdl_user AS u ON u.id= ra.userid
                                    JOIN mdl_user_enrolments AS ue ON ue.userid=u.id
                                    JOIN mdl_enrol AS e ON e.id=ue.enrolid
                                    JOIN mdl_course AS c ON c.id=e.courseid
                                    JOIN mdl_context AS ct ON ct.id=ra.contextid AND ct.instanceid= c.id
                                    JOIN mdl_role AS r ON r.id= ra.roleid
                                WHERE  ra.roleid=3 AND u.id = ?";
    $list_course_by_user_ex = $DB->get_records_sql($list_course_by_user_sql, [$userid]);
    if ($list_course_by_user_ex) {
        foreach ($list_course_by_user_ex as $value)
            $list_courseid[] = $value;
    }

    return $list_courseid;
}

/**
 * Lấy danh sách khóa học full thông tin của giáo viên dùng cho control kendo
 */
function get_list_courseinfo_by_teacher_kendo($userid, $pagetake, $pageskip, $q) {
    global $DB;
    $odersql = "";
    $wheresql = "";
    if($q) {
        $wheresql = "WHERE  ra.roleid = 3 AND u.id = $userid AND c.fullname LIKE N'%$q%'";
    } else {
        $wheresql = "WHERE ra.roleid = 3 AND u.id = $userid";
    }
    if($pagetake == 0) {
        $ordersql = "RowNum";
    } else {
        $ordersql = "RowNum OFFSET $pageskip ROWS FETCH NEXT $pagetake ROWS only";
    }
    $query = "
            SELECT *, 
                (SELECT COUNT(u.id) FROM mdl_role_assignments ra
                    JOIN mdl_user u ON ra.userid = u.id
                    JOIN mdl_user_enrolments ue ON u.id = ue.userid 
                    JOIN mdl_enrol enr ON ue.enrolid = enr.id
                    JOIN mdl_course c ON enr.courseid = c.id
                    JOIN mdl_context ct ON ct.id = ra.contextid AND ct.instanceid = c.id
                $wheresql
                ) AS total
            FROM (
                SELECT ROW_NUMBER() OVER (ORDER BY u.id) AS RowNum, c.id courseid, c.fullname coursename, CONCAT(u.firstname, ' ', u.lastname) fullname, u.id userid,r.shortname rolename
                FROM mdl_role_assignments ra
                    JOIN mdl_user u ON ra.userid = u.id
                    JOIN mdl_user_enrolments ue ON u.id = ue.userid 
                    JOIN mdl_enrol enr ON ue.enrolid = enr.id
                    JOIN mdl_course c ON enr.courseid = c.id
                    JOIN mdl_context ct ON ct.id = ra.contextid AND ct.instanceid = c.id
                    JOIN mdl_role r ON ra.roleid = r.id
                $wheresql
            ) AS Mydata
            ORDER BY $ordersql
            ";
    $data = $DB->get_records_sql($query,[]);
    return $data;
}

function get_list_courseinfo_by_admin_kendo($pagetake, $pageskip, $q) {
    global $DB;
    $odersql = "";
    $wheresql = "";
    if($q) {
        $wheresql = "WHERE id > 1 AND fullname LIKE N'%$q%'";
    } else {
        $wheresql = "WHERE id > 1";
    }
    if($pagetake == 0) {
        $ordersql = "RowNum";
    } else {
        $ordersql = "RowNum OFFSET $pageskip ROWS FETCH NEXT $pagetake ROWS only";
    }
    $query = "
            SELECT *, 
                (SELECT COUNT(id) 
                    FROM {course}
                    $wheresql
                ) AS total
            FROM (
                SELECT ROW_NUMBER() OVER (ORDER BY id) AS RowNum, id courseid, fullname coursename
                FROM {course}
                $wheresql
            ) AS Mydata
            ORDER BY $ordersql
            ";
    $data = $DB->get_records_sql($query,[]);
    return $data;
}


/**
 * Lấy danh sách khóa học của học viên
 * @param  [type] $userid [description]
 * @return [array] $list_courseid [description]
 */
function get_list_course_by_student($userid) {
    global $DB;
    $list_courseid = [];
    $list_course_by_user_sql = "
                                SELECT c.fullname,c.id,c.shortname, c.required
                                FROM mdl_role_assignments AS ra
                                    JOIN mdl_user AS u ON u.id= ra.userid
                                    JOIN mdl_user_enrolments AS ue ON ue.userid=u.id
                                    JOIN mdl_enrol AS e ON e.id=ue.enrolid
                                    JOIN mdl_course AS c ON c.id=e.courseid
                                    JOIN mdl_context AS ct ON ct.id=ra.contextid AND ct.instanceid= c.id
                                    JOIN mdl_role AS r ON r.id= ra.roleid
                                WHERE  ra.roleid=5 AND ue.status = 0 AND u.id = ? AND c.visible = 1";
    $list_course_by_user_ex = $DB->get_records_sql($list_course_by_user_sql,[$userid]);
    if ($list_course_by_user_ex) {
        foreach ($list_course_by_user_ex as $value)
            $list_courseid[] = $value;
    }

    return $list_courseid;
}

/**
 * Lấy courseid khóa học của giáo viên
 * @param  [type] $userid [description]
 * @return [type]         [description]
 */
function get_list_courseid_by_teacher($userid) {
    global $DB;
    $list_course_by_user_sql = "
                                SELECT c.fullname,c.id
                                FROM mdl_role_assignments AS ra
                                    JOIN mdl_user u ON ra.userid = u.id
                                    JOIN mdl_user_enrolments ue ON u.id = ue.userid 
                                    JOIN mdl_enrol enr ON ue.enrolid = enr.id
                                    JOIN mdl_course c ON enr.courseid = c.id
                                    JOIN mdl_context ct ON ct.id = ra.contextid AND ct.instanceid = c.id
                                    JOIN mdl_role r ON ra.roleid = r.id
                                WHERE  ra.roleid=3 AND u.id = ?";
    $list_course_by_user_ex = $DB->get_records_sql($list_course_by_user_sql,[$userid]);
    $list_courseid = array();
     if (!empty($list_course_by_user_ex)) {
        foreach ($list_course_by_user_ex as $value) {
            $list_courseid[] = $value->id;
        }
        $str_courseid = implode(',',$list_courseid);
    } else
        $str_courseid = '';
    return $str_courseid;
}

function transfer_enrol_method($method) {
    if($method == 'manual')
        $strmethod = get_string('manual_enrol', 'local_newsvnr');
    else if($method == 'apply')
        $strmethod = get_string('apply_enrol', 'local_newsvnr');
    else if($method == 'self')
        $strmethod = get_string('self_enrol', 'local_newsvnr');
    else if($method == 'guest')
        $strmethod = get_string('guest_enrol', 'local_newsvnr');
    return $strmethod;
}

function get_enrol_method($courseid) {
    global $DB,$CFG;
    $courseurl = $CFG->wwwroot . '/course/view.php?id=' . $courseid;
    $query = "
            SELECT enrol FROM mdl_enrol WHERE status = 0 AND courseid = ?
            ";
    $execute = $DB->get_records_sql($query, [$courseid]);
    $methodarr = [];
    foreach($execute as $method) {
        array_push($methodarr, $method->enrol);
    }

    if(in_array('guest', $methodarr)) {
        return '<a href="'.$courseurl.'"><i class="fa fa-unlock-alt fa-fw " title="Guest access" aria-label="Guest access"></i>' . get_string('guest_enrol', 'local_newsvnr') . '</a>';
    }
    if(in_array('self', $methodarr)) {
        return '<a href="'.$courseurl.'"><i class="fa fa-key fa-fw " title="Self enrolment" aria-label="Self enrolment"></i>' . get_string('self_enrol', 'local_newsvnr') . '</a>';
    }
    if(in_array('apply', $methodarr)) {
        return '<a href="'.$courseurl.'">' . get_string('apply_enrol', 'local_newsvnr') . '</a>';
    }
    if(in_array('manual', $methodarr)) {
        return '<a href="'.$courseurl.'" style="color:red"><i class="fa fa-lock fa-fw " title="Manual Access" aria-label="Manual Access"></i>' . get_string('manual_enrol', 'local_newsvnr') . '</a>';
    }

}

/**
 * End dashboard
 */

/** - call modal cho orgmanager - **/
function local_newsvnr_output_fragment_new_orgcate_form($args) {
    global $CFG,$SITE;
    require_once($CFG->dirroot . '/local/newsvnr/orgcate_form.php');
    $args = (object) $args;
    $context = $args->context;
    $o = '';
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $orgcate = new stdClass();
    $mform = new orgcate_form(null,array('orgcate' => $orgcate),'post','', null, true, $formdata);
    $mform->set_data($orgcate);

    if (!empty($args->jsonformdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();

    return $o;
}
function local_newsvnr_output_fragment_new_orgposition_form($args) {
    global $CFG,$SITE;

    require_once($CFG->dirroot . '/local/newsvnr/orgposition_form.php');
    $args = (object) $args;
    $context = $args->context;
    $o = '';
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $orgposition = new stdClass();
    $mform = new orgposition_form(null,array('orgposition' => $orgposition),'post','', null, true, $formdata);
    $mform->set_data($orgposition);

    if (!empty($args->jsonformdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
}

function local_newsvnr_output_fragment_new_orgjobtitle_form($args) {
    global $CFG,$SITE;
    require_once($CFG->dirroot . '/local/newsvnr/orgjobtitle_form.php');
    $args = (object) $args;
    $context = $args->context;
    $o = '';
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $orgjobtitle = new stdClass();
    $mform = new orgjobtitle_form(null,array('orgjobtitle' => $orgjobtitle),'post','', null, true, $formdata);
    $mform->set_data($orgjobtitle);
    if (!empty($args->jsonformdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
}

function local_newsvnr_output_fragment_new_orgstructure_form($args) {
    global $CFG,$SITE;
    require_once($CFG->dirroot . '/local/newsvnr/orgstructure_form.php');
    $args = (object) $args;
    $context = $args->context;
    $o = '';
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $orgstructure = new stdClass();
    $mform = new orgstructure_form(null,array('orgstructure' => $orgstructure),'post','', null, true, $formdata);
    $mform->set_data($orgstructure);
    if (!empty($args->jsonformdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();

    return $o;
}

function local_newsvnr_output_fragment_create_email_template_form($args) {
    global $CFG, $SITE, $DB;
    require_once $CFG->dirroot . '/user/sendemail_form.php';
    $args = (object) $args;
    $context = $args->context;
    $o = '';
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
    }
    $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES,
    'maxbytes' => $SITE->maxbytes, 'context' => context_system::instance());
    $get_template = $DB->get_record('email_template', ['id' => $serialiseddata->templateid]);
    $email_template = new stdClass;
    $email_template->id = $serialiseddata->templateid;
    $email_template->subject = $get_template->subject;
    $email_template->content = $get_template->content;
    $email_template = file_prepare_standard_editor($email_template, 'content', $editoroptions,
            context_system::instance(), 'email', 'content', null);
    $mform = new sendemail_form(null,array('email_template' => $email_template),'post','', null, true, $formdata);
    $mform->set_data($email_template);
    // Used to set the courseid.
 
    if (!empty($args->jsonformdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
 
    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
 
    return $o;
}

function local_newsvnr_output_fragment_send_email_form($args) {
    global $CFG, $OUTPUT, $SITE;
    require_once $CFG->dirroot . '/user/sendemail_form.php';
    $args = (object) $args;
    $context = $args->context;
    $o = '';
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES,
    'maxbytes' => $SITE->maxbytes, 'context' => context_system::instance());
    $email = new stdClass;
    $email = file_prepare_standard_editor($email, 'emailcontent', $editoroptions,
            context_system::instance(), 'email', 'emailcontent', null);
    $mform = new sendemail_form(null,array('eamil' => $email),'post','', null, true, $formdata);
    $mform->set_data($email);
    // Used to set the courseid.
 
    if (!empty($args->jsonformdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
 
    ob_start();
    echo $OUTPUT->render_from_template('core_user/manage_sendemail', $context = []);
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
 
    return $o;
}
/** - end call modal cho orgmanager - **/
/* Năng lực theo vị trí  */
function get_list_orgstructure() {
    global $DB;
    $query = "SELECT * FROM {orgstructure}";
    $data = $DB->get_records_sql($query);
    return array_values($data);
}

function get_list_position() {
    global $DB;
    $query = "SELECT * FROM {orgstructure_position}";
    $data = $DB->get_records_sql($query);
    return array_values($data);
}

function get_framework_competency() {
    global $DB;
    $query = "SELECT * FROM {competency_framework} ";
    $data = $DB->get_records_sql($query);
    return array_values($data);
}

function get_list_plan_template_by_positionid($positionid)
{
    global $DB;
    $query = "
            SELECT ct.id AS templateid, op.id AS positionid, op.name AS struct_name, op.orgstructureid, ct.shortname, ct.timecreated, ct.contextid, ct.duedate 
            FROM {orgstructure_position}  op
                JOIN {competency_template} ct ON ct.positionid = op.id 
            WHERE ct.positionid = ?" ;
    $data = $DB->get_records_sql($query, array($positionid));
    $objectArr =  array();
    foreach ($data as $value) {
        $query_plans = "
                        SELECT count(cp.id) AS count 
                        FROM mdl_competency_plan cp 
                        WHERE cp.templateid = ?";
        $count_plans = $DB->get_record_sql($query_plans, array($value->templateid));
        $category_name = get_categoryname_by_contextid($value->contextid);
        $std = new stdClass();
        $std->positionid = $value->positionid;
        $std->struct_name = $value->struct_name;
        if($category_name == '')
            $std->category_name = 'System';
        else
            $std->category_name  = $category_name->name;
        $std->orgstructureid = $value->orgstructureid;
        $std->template_name  = $value->shortname;
        $std->contextid      = $value->contextid;
        $std->timecreated    = $value->timecreated;
        $std->duedate        = $value->duedate;
        $std->templateid     = $value->templateid;
        $std->learn_plans    = $count_plans->count;
        $objectArr[] = $std;
    }
    return $objectArr;
}

/* ------ End năng lực cho vị trí ------ */

/* ------ Customize question bank ------ */

function get_categoryname_by_contextid($contextid) {
    global $DB;
    $query = "
            SELECT cc.name 
                FROM mdl_course_categories cc 
                JOIN mdl_context c ON cc.id = c.instanceid
            WHERE c.id = ?";
    $data = $DB->get_record_sql($query, array($contextid));
    return $data;
}
/* ------ End custiomize question bank ------*/

/* ------ API PB-CD-CV ------ */
function check_auth_api($username, $password) {
    global $DB;
    $sql = "
            SELECT u.id, u.username, u.password
            FROM {user} AS u 
            WHERE u.username =  ?";
    $findUser = $DB->get_record_sql($sql, array($username));
    if($findUser)
    {
        $checkAuthenticate = password_verify($password, $findUser->password);
        return $checkAuthenticate;
    }
}

function find_orgstructure_by_name($name) {
    global $DB;
    $sql = "
            SELECT *
            FROM {orgstructure}
            WHERE name = ?";
   $data = $DB->get_record_sql($sql, array($name));
   return $data;
}

function find_orgstructure_by_code($code) {
   global $DB;
   $sql = "
            SELECT * 
            FROM {orgstructure} 
            WHERE code = ?";
   $data = $DB->get_record_sql($sql, array($code));
   return $data;
}

function find_orgstructure_jobtitle_by_name($name) {
   global $DB;
   $sql = "
            SELECT * 
            FROM {orgstructure_jobtitle}
            WHERE name = ?";
   $data = $DB->get_record_sql($sql, array($name));
   return $data;
}

function find_orgstructure_jobtitle_by_code($code) {
   global $DB;
   $sql = "
            SELECT * 
            FROM {orgstructure_jobtitle} 
            WHERE code = ?";
   $data = $DB->get_record_sql($sql, array($code));
   return $data;
}

function find_orgstructure_category_by_name($name) {
    global $DB;
    $sql = "
            SELECT *
            FROM {orgstructure_category} 
            WHERE name = ?";
    $data = $DB->get_record_sql($sql, array($name));
    return $data;
}

function find_orgstructure_category_by_code($code) {
    global $DB;
    $sql = "
            SELECT * 
            FROM {orgstructure_category} 
            WHERE code = ?";
    $data = $DB->get_record_sql($sql, array($code));
    return $data;
}

function find_orgstructure_parrentcode($parentcode) {
    global $DB;
    $sql = "
            SELECT * 
            FROM {orgstructure} 
            WHERE code = ?";
    $data = $DB->get_record_sql($sql, array($parentcode));
    return $data;
}

function find_orgstructure_position_by_name($name) {
    global $DB;
    $sql = "
            SELECT name 
            FROM {orgstructure_position} 
            WHERE name = ?";
    $data = $DB->get_record_sql($sql, array($name));
    return $data;
}

function find_orgstructure_position_by_code($code) {
    global $DB;
    $sql = "SELECT * 
            FROM {orgstructure_position}
            WHERE code = ?";
    $data = $DB->get_record_sql($sql, array($code));
    return $data;
}

function find_id_orgpostion_by_code($code) {
    global $DB;
    $query = "
            SELECT id 
            FROM {orgstructure_position} 
            WHERE code = ?";
    $data = $DB->get_field_sql($query,[$code]);
    return $data;
}

function find_id_orgstructure_by_code($code) {
    global $DB;
    $query = "
            SELECT id 
            FROM {orgstructure}
            WHERE code = ?";
    $data = $DB->get_field_sql($query,[$code]);
    return $data;
}

function find_id_orgjobtitle_by_code($code) {
    global $DB;
    $query = "
            SELECT id 
            FROM {orgstructure_jobtitle}
            WHERE code = ?";
    $data = $DB->get_field_sql($query,[$code]);
    return $data;
}

function find_usercode_by_code($code)
{
    global $DB;
    $sql = "
            SELECT id 
            FROM {user} 
            WHERE usercode = ?";
    $data = $DB->get_field_sql($sql, [$code]);
    return $data;
}
function find_ueid_by_enrolid($enrolid,$userid)
{
    global $DB;
    $sql = "
            SELECT id,userid 
            FROM {user_enrolments} 
            WHERE enrolid = ? AND userid = ?";
    $data = $DB->get_record_sql($sql, array($enrolid,$userid));
    return $data;
}

function find_id_orgpostion_by_name($name) {
    global $DB;
    $query = "
            SELECT id 
            FROM {orgstructure_position} 
            WHERE name LIKE ?";
    $data = $DB->get_record_sql($query,[$name]);
    return $data;
}

function get_course_by_orgpositioncode($orgpositioncode,$orgjobtitlecode,$orgstructurecode,$typeofcourse = 2) {
    global $DB;
    $query = "
            SELECT c.id,c.fullname 
            FROM {course_position} c 
                JOIN {orgstructure_position} op ON c.courseofposition = op.id 
                JOIN {orgstructure_jobtitle} oj ON c.courseofjobtitle = oj.id 
                JOIN {orgstructure} o ON c.courseoforgstructure = o.id
                JOIN {course} c ON cp.course = c.id
            WHERE op.id = ? AND oj.id = ? AND o.id = ? AND c.typeofcourse = ?";
    $data = $DB->get_record_sql($query,[$orgpositioncode,$orgjobtitlecode,$orgstructurecode,$typeofcourse]);
    return $data;
}

function find_username($name) {
    global $DB;
    $query = "
            SELECT id 
            FROM {user} 
            WHERE username LIKE ?";
    $data = $DB->get_record_sql($query,[$name]);
    return $data;
}
/**
 * Thêm học viên vào khóa học bằng phương thức manual
 * @param  [type]  $userid            [description]
 * @param  [type]  $courseid          [description]
 * @param  [type]  $roleidorshortname [description]
 * @param  string  $enrol             [description]
 * @param  integer $timestart         [description]
 * @param  integer $timeend           [description]
 * @param  [type]  $status            [description]
 * @return [type]                     [description]
 */
function enrol_user($userid, $courseid, $roleidorshortname = null, $enrol = 'manual',
    $timestart = 0, $timeend = 0, $status = null) {
    global $DB;
        // If role is specified by shortname, convert it into an id.
    if (!is_numeric($roleidorshortname) && is_string($roleidorshortname)) {
        $roleid = $DB->get_field('role', 'id', array('shortname' => $roleidorshortname), MUST_EXIST);
    } else {
        $roleid = $roleidorshortname;
    }
    if (!$plugin = enrol_get_plugin($enrol)) {
        return false;
    }
    $instances = $DB->get_records('enrol', array('courseid'=>$courseid, 'enrol'=>$enrol));
    if (count($instances) != 1) {
        return false;
    }
    $instance = reset($instances);
    if (is_null($roleid) and $instance->roleid) {
        $roleid = $instance->roleid;
    }
    $plugin->enrol_user($instance, $userid, $roleid, $timestart, $timeend, $status);
    return true;
}

function check_teacher_in_course($courseid,$userid) {
    global $DB,$CFG;
    $sql = "
            SELECT ue.userid FROM mdl_role_assignments AS ra
                JOIN mdl_user AS u ON u.id= ra.userid
                JOIN mdl_user_enrolments AS ue ON ue.userid=u.id
                JOIN mdl_enrol AS e ON e.id=ue.enrolid
                JOIN mdl_course AS c ON c.id=e.courseid
                JOIN mdl_context AS ct ON ct.id=ra.contextid AND ct.instanceid= c.id
                JOIN mdl_role AS r ON r.id= ra.roleid
            WHERE c.id= ? AND ra.roleid= 3 AND ue.userid = ?";
    $data = $DB->get_record_sql($sql,array($courseid,$userid));
    return $data;
}

function get_course_by_idnumber($idnumber) {
    global $DB;
    $query = "
            SELECT id,fullname
            FROM mdl_course 
            WHERE idnumber = ?";
    $data = $DB->get_record_sql($query,[$idnumber]);
    return $data;
}

function get_course_by_code($code) {
    global $DB;
    $query = "
            SELECT id,fullname
            FROM mdl_course 
            WHERE code = ?";
    $data = $DB->get_record_sql($query,[$code]);
    return $data;
}

function get_orgpositionid_by_code($orgpositioncode) {
    global $DB;
    $query = "
            SELECT id 
            FROM mdl_orgstructure_position
            WHERE code = ?";
    $data = $DB->get_field_sql($query,[$orgpositioncode]);
    return $data;
}

function check_user_in_course($courseid,$userid) {
    global $DB;
    $query = "
            SELECT ue.userid
            FROM mdl_role_assignments AS ra
                JOIN mdl_user AS u ON u.id= ra.userid
                JOIN mdl_user_enrolments AS ue ON ue.userid=u.id
                JOIN mdl_enrol AS e ON e.id=ue.enrolid
                JOIN mdl_course AS c ON c.id=e.courseid
                JOIN mdl_context AS ct ON ct.id=ra.contextid AND ct.instanceid= c.id
                JOIN mdl_role AS r ON r.id= ra.roleid
            WHERE c.id= ? AND ra.roleid= 5 AND ue.userid = ?";
    $data = $DB->get_record_sql($query,[$courseid,$userid]);
    return $data;
}

function split_name($name) {
    $name = trim($name);
    $last_name = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
    $first_name = trim( preg_replace('#'.$last_name.'#', '', $name ) );
    return array($first_name, $last_name);
}
/* --- end api --- */
/**
 * Loại bỏ dấu tiếng việt trong chuỗi
 */
function convert_name($str) {
    $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", 'a', $str);
    $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", 'e', $str);
    $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", 'i', $str);
    $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", 'o', $str);
    $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", 'u', $str);
    $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", 'y', $str);
    $str = preg_replace("/(đ)/", 'd', $str);
    $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", 'A', $str);
    $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", 'E', $str);
    $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", 'I', $str);
    $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", 'O', $str);
    $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", 'U', $str);
    $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", 'Y', $str);
    $str = preg_replace("/(Đ)/", 'D', $str);

    return $str;
}
function str_split_unicode($str, $l = 0) {
    if ($l > 0) {
        $ret = array();
        $len = mb_strlen($str, "UTF-8");
        for ($i = 0; $i < $len; $i += $l) {
            $ret[] = mb_substr($str, $i, $l, "UTF-8");
        }
        return $ret;
    }
    return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
}

function ToCorrectCase($str){

    $str = mb_strtolower($str);
    $str_array = str_split_unicode($str);
    $str_array[0] = mb_strtoupper($str_array[0]);
    $str = '';
    foreach ($str_array as $key){
        $str = $str.$key;
    }
    return $str;
}
/* ------ End API PB-CD-CV ------*/
/*  --***-- */
/*
    Chuyển đổi unix sang time
 */
function convertunixtime($format="r", $timestamp=false, $timezone=false) {
    $userTimezone = new DateTimeZone(!empty($timezone) ? $timezone : 'GMT');
    $gmtTimezone = new DateTimeZone('GMT');
    $myDateTime = new DateTime(($timestamp!=false?date("r",(int)$timestamp):date("r")), $gmtTimezone);
    $offset = $userTimezone->getOffset($myDateTime);
    return date($format, ($timestamp!=false?(int)$timestamp:$myDateTime->format('U')) + $offset);
}
/**
 * Đăng nhập moodle khi sử dụng API
 * [user_login_via_api description]
 * @return [type] [Đăng nhập thành công]
 */
function user_login_via_api() {
    $adminuser = get_complete_user_data('id', 2);
    complete_user_login($adminuser);
}


// Get Domain URL
// Ex: vnresource.vn/
function curPageURL() {
  if(isset($_SERVER["HTTPS"]) && !empty($_SERVER["HTTPS"]) && ($_SERVER["HTTPS"] != 'on' )) {
        $url = 'https://'.$_SERVER["SERVER_NAME"];//https url
  }  else {
    $url =  'http://'.$_SERVER["SERVER_NAME"];//http url
  }
  if(( $_SERVER["SERVER_PORT"] != 80 )) {
     $url .= ':' . $_SERVER["SERVER_PORT"];
  }
  return $url;
}


//CURL kieu du lieu form-data
function HTTP_POST($ch, $params = array(), $url){

    curl_setopt($ch, CURLOPT_URL, $url  );
    curl_setopt($ch, CURLOPT_POST,  count($params));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

    // Receive server response ...
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $server_output = curl_exec($ch);

    curl_close ($ch);
}
function encode_array($args)
{
      if(!is_array($args)) return false;
      $c = 0;
      $out = '';
      foreach($args as $name => $value)
      {
        if($c++ != 0) $out .= '&';
        $out .= urlencode("$name").'=';
        if(is_array($value))
        {
          $out .= urlencode(serialize($value));
        }else{
          $out .= urlencode("$value");
        }
      }
      return $out;
}

function getToken($url) {
    $data = [
        'grant_type' => 'password',
        'username' => 'vnr.hrm@',
        'password' => '123'
    ];
    $params = encode_array($data);
    $curl = curl_init();
    curl_setopt_array($curl, array(
    CURLOPT_RETURNTRANSFER => true,
    // CURLOPT_HEADER => true,
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded',
            // 'Content-Length: ' . strlen($data),
    ),
    CURLOPT_POSTFIELDS => $params));
    $resp = curl_exec($curl);
    curl_close($curl);
    return json_decode($resp,JSON_UNESCAPED_UNICODE)['access_token'];
}
function getTokenHRM() {
    global $DB;
    $token = $DB->get_record('local_newsvnr_api',['functionapi' => 'ApiToken']);
    if(empty($token)) {
        return;
    }
    $username = $DB->get_field('local_newsvnr_api_detail','default_value',['api_id' => $token->id,'client_params' => 'username']);
    $password = $DB->get_field('local_newsvnr_api_detail','default_value',['api_id' => $token->id,'client_params' => 'password']);
    $data = [
        'grant_type' => 'password',
        'username' => $username,
        'password' => $password
    ];
    $params = encode_array($data);
    $curl = curl_init();
    curl_setopt_array($curl, array(
    CURLOPT_RETURNTRANSFER => true,
    // CURLOPT_HEADER => true,
    CURLOPT_URL => $token->url,
    CURLOPT_POST => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded',
            // 'Content-Length: ' . strlen($data),
    ),
    CURLOPT_POSTFIELDS => $params));
    $resp = curl_exec($curl);
    curl_close($curl);
    return json_decode($resp,JSON_UNESCAPED_UNICODE)['access_token'];
}
//curl gửi dữ liệu kiểu json
function HTTPPost($url,$data) {
    global $DB,$USER;
    $urltoken = 'http://125.212.226.105:814/Token';
    $token = getTokenHRM();
    $auth = 'Authorization: Bearer ' . $token;
    $params = encode_array($data);
    $curl = curl_init();
    curl_setopt_array($curl, array(
    CURLOPT_RETURNTRANSFER => true,
    // CURLOPT_HEADER => true,
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded',
            // 'Content-Length: ' . strlen($data),
            $auth
    ),
    CURLOPT_POSTFIELDS => $params));
    $resp = curl_exec($curl);
    // insert_log($resp,$url);
    curl_close($curl);
}

//curl gửi dữ liệu kiểu json
function HTTPPost_EBM($url,$data) {
    // $urltoken = 'http://103.42.56.200:8088/Token';
    // $token = getToken($urltoken);
    // $auth = 'Authorization: Bearer ' . $token;
    $params = json_encode($data);
    $curl = curl_init();
    curl_setopt_array($curl, array(
    CURLOPT_RETURNTRANSFER => true,
    // CURLOPT_HEADER => true,
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            // 'Content-Length: ' . strlen($data),
            // $auth
    ),
    CURLOPT_POSTFIELDS => $params));
    $resp = curl_exec($curl);
    // insert_log($resp,$url);
    curl_close($curl);
}

//curl gửi dữ liệu kiểu json
function get_graph_microsoft_team($url, $token) {
    $auth = 'Authorization: Bearer ' . $token;
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => $url,
        CURLOPT_POST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            $auth
        ))
    );
    $resp = curl_exec($curl);
    curl_close($curl);
    return json_decode($resp);
}

//curl gửi dữ liệu kiểu json
function post_graph_microsoft_team($url, $data, $token) {
    $auth = 'Authorization: Bearer ' . $token;
    $params = json_encode($data);
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            $auth
        ),
        CURLOPT_POSTFIELDS => $params)
    );
    $resp = curl_exec($curl);
    curl_close($curl);
    return $resp;
}

//func get publisherDomain AD azure
/*
    Return [array] list domain trong ad azure
 */
function get_publisherdomain_microsoft_team($token) {
    try {
        $url = MICROSOFTGRAPH_BASEURL . '/v1.0/applications';
        $response = get_graph_microsoft_team($url, $token);
        $domains = [];
        $apps = $response->value;
        foreach($apps as $app) {
            $domains[] = $app->publisherDomain;
        }
        return end($domains);
    } catch (Exception $e) {
        throw new \coding_exception('Lỗi Api get publisherdomain: ' . $e->getMessage());
    }
}

function get_token_microsoft_team() {
    global $CFG;
    unset($_SESSION['AZURA_TOKEN']);
    require_once("$CFG->dirroot/vendor/autoload.php");
    $provider = new \League\OAuth2\Client\Provider\GenericProvider([
        'clientId'                => "1ca50252-42e1-4672-a69a-4c21dc1b73cc",
        'clientSecret'            => "9U8O_wiL62NtzbnNpJ.bqj0okH~F-7Y_sv",
        'redirectUri'             =>  $CFG->wwwroot . '/local/newsvnr/api/msteam/get-token',
        'scopes'                  => 'offline_access email openid profile Calendars.Read Calendars.Read.Shared Calendars.ReadWrite Calendars.ReadWrite.Shared Channel.Create Channel.Delete.All Channel.ReadBasic.All ChannelMember.Read.All ChannelMember.ReadWrite.All ChannelMessage.Delete ChannelMessage.Edit ChannelMessage.Read.All ChannelMessage.Send ChannelSettings.Read.All ChannelSettings.ReadWrite.All Directory.AccessAsUser.All Directory.Read.All Directory.ReadWrite.All Group.Read.All Group.ReadWrite.All GroupMember.Read.All GroupMember.ReadWrite.All Mail.Read Mail.Read.Shared Mail.ReadBasic Mail.ReadWrite Mail.ReadWrite.Shared Mail.Send Mail.Send.Shared OnlineMeetings.Read OnlineMeetings.ReadWrite Team.Create Team.ReadBasic.All TeamMember.Read.All TeamMember.ReadWrite.All TeamMember.ReadWriteNonOwnerRole.All TeamsActivity.Read TeamsActivity.Send TeamsApp.Read TeamsApp.Read.All TeamsApp.ReadWrite TeamsApp.ReadWrite.All TeamsTab.Create TeamsTab.Read.All TeamsTab.ReadWrite.All TeamsTab.ReadWriteForChat TeamsTab.ReadWriteForTeam TeamsTab.ReadWriteForUser User.Export.All User.Invite.All User.ManageIdentities.All User.Read User.Read.All User.ReadBasic.All User.ReadWrite User.ReadWrite.All
',
        'urlAuthorize'            => 'https://login.microsoftonline.com/e229fef4-6618-4a2a-a2df-7fe41a7c1127/oauth2/v2.0/authorize',
        'urlAccessToken'          => 'https://login.microsoftonline.com/e229fef4-6618-4a2a-a2df-7fe41a7c1127/oauth2/v2.0/token',
        'urlResourceOwnerDetails' => 'https://graph.microsoft.com',
    ]);
    if(!isset($_SESSION['AZURA_TOKEN'])) {
        try {

            // Try to get an access token using the resource owner password credentials grant.
            $accessToken = $provider->getAccessToken('password', [
                'scope' => 'offline_access email openid profile Calendars.Read Calendars.Read.Shared Calendars.ReadWrite Calendars.ReadWrite.Shared Channel.Create Channel.Delete.All Channel.ReadBasic.All ChannelMember.Read.All ChannelMember.ReadWrite.All ChannelMessage.Delete ChannelMessage.Edit ChannelMessage.Read.All ChannelMessage.Send ChannelSettings.Read.All ChannelSettings.ReadWrite.All Directory.AccessAsUser.All Directory.Read.All Directory.ReadWrite.All Group.Read.All Group.ReadWrite.All GroupMember.Read.All GroupMember.ReadWrite.All Mail.Read Mail.Read.Shared Mail.ReadBasic Mail.ReadWrite Mail.ReadWrite.Shared Mail.Send Mail.Send.Shared OnlineMeetings.Read OnlineMeetings.ReadWrite Team.Create Team.ReadBasic.All TeamMember.Read.All TeamMember.ReadWrite.All TeamMember.ReadWriteNonOwnerRole.All TeamsActivity.Read TeamsActivity.Send TeamsApp.Read TeamsApp.Read.All TeamsApp.ReadWrite TeamsApp.ReadWrite.All TeamsTab.Create TeamsTab.Read.All TeamsTab.ReadWrite.All TeamsTab.ReadWriteForChat TeamsTab.ReadWriteForTeam TeamsTab.ReadWriteForUser User.Export.All User.Invite.All User.ManageIdentities.All User.Read User.Read.All User.ReadBasic.All User.ReadWrite User.ReadWrite.All',
                'username' => 'userpostman1@workfromhomelms.onmicrosoft.com',
                'password' => 'L3thanhvu@'
            ]);
            $_SESSION['AZURA_TOKEN'] = $accessToken;
        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
            // Failed to get the access token
            exit($e->getMessage());
        }
    } else {
        $existingAccessToken = $_SESSION['AZURA_TOKEN'];
        if ($existingAccessToken->hasExpired()) {
            $newAccessToken = $provider->getAccessToken('refresh_token', [
                'scope' => $provider->getDefaultScopes(),
                'refresh_token' => $existingAccessToken->getRefreshToken()
            ]);
            unset($_SESSION['AZURA_TOKEN']);
            $_SESSION['AZURA_TOKEN'] = $newAccessToken;
            // Purge old access token and store new access token to your data store.
        }
    }
    $token = $_SESSION['AZURA_TOKEN']->getToken();
    return $token;
}

// func lấy id của một user dựa vào email
function get_userid_microsoft_team($user, $token) {
    $url = MICROSOFTGRAPH_BASEURL .'/v1.0/users?$select=displayName,id&$filter=startsWith(userPrincipalName,\''.$user.'\')';
    try {
        $response = get_graph_microsoft_team($url, $token);
        return $response->value[0]->id;
    } catch (Exception $e) {
        throw new \coding_exception('Lỗi Api: ' . $e->getMessage());
    }
}

// cvt fullname thành firstname + lastname
/*
    Return (array) danh sách fullname gồm firstname và lastname
*/
function fullname_cvt($listusers) {
    $data = [];
    $fullnames = explode(',', $listusers);
    foreach($fullnames as $key => $fullname) {
        $parts = explode(" ", trim($fullname));
        if(count($parts) > 1) {
            $lastname = array_pop($parts);
            $firstname = implode(" ", $parts);
        } else {
            $firstname = $firstname = array_pop($parts);
            $lastname = " ";
        }
        $obj = new stdClass;
        $obj->fullname = $fullname;
        $obj->firstname = $firstname;
        $obj->lastname = $lastname;
        $data[] = $obj;
    }
    return $data;
}

//curl gửi dữ liệu kiểu json có trả về dữ liệu
function HTTPPost_EBM_return($url,$data) {
    // $urltoken = 'http://103.42.56.200:8088/Token';
    // $token = getToken($urltoken);
    // $auth = 'Authorization: Bearer ' . $token;
    $params = json_encode($data);
    $curl = curl_init();
    curl_setopt_array($curl, array(
    CURLOPT_RETURNTRANSFER => true,
    // CURLOPT_HEADER => true,
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            // 'Content-Length: ' . strlen($data),
            // $auth
    ),
    CURLOPT_POSTFIELDS => $params));
    $resp = curl_exec($curl);
    curl_close($curl);
    return json_decode($resp);
}

//Đổi unixtime thành chữ
function converttime($time) {
    $currenttime = time();
    $distance = ((int)$currenttime - (int)$time) + 1;
    $result = '';
    switch ((int)$distance) {
        case ($distance <= 60 ):
            $result = get_string('justnow','local_newsvnr');
            break;
        case ($distance > 60 && $distance < 3600):
            $result = round($distance/60) .' '.get_string('minuteago','local_newsvnr').'';
            break;
        case ($distance > 3600 && $distance < 86400):
            $result = round($distance/3600) .' '.get_string('hourago','local_newsvnr').'';
            break;
        case ($distance > 86400 && $distance < 172800 ):
            $result = get_string('yesterday','local_newsvnr');
            break;
        case ($distance > 172800 && $distance < 2592000):
            $result = round($distance/86400) .' '.get_string('dayago','local_newsvnr').'';
            break;
        case (round($distance/2592000) > 1 && round($distance/2592000) < 12 ):
            $result = round($distance/2592000) .' '.get_string('monthago','local_newsvnr').'';
            break;
        default:
            $result = convertunixtime(' d-m-Y',$time,'Asia/Ho_Chi_Minh');
            break;
    }
    return $result;
}
//
function convertTimeExam($time) {
    $result = '';
    $time = (int)$time;
    if($time > 0) {
        switch ($time) {
            case ($time <= 60):
                $result = $time . ' '.get_string('second','local_newsvnr').'';
                break;
            case ($time <= 3600):
                $result = round($time/60) . ' '.get_string('minute','local_newsvnr').'';
                break;
            case ($time > 3600 && $time <= 86400):
                $result = round($time/3600) .' '.get_string('hour','local_newsvnr').'';
                break;
            case ($time > 86400):
                $result = round($time/86400) .' '.get_string('day','local_newsvnr').'';
                break;
            default:
                break;
        }
    } else {
        $result = get_string('nolimit','local_newsvnr');
    }
    return $result;
}
//Hàm chuyển mimetype qua text
function mime2ext($mime) {
    $mime_map = [
        'video/3gpp2'                                                               => '3g2',
        'video/3gp'                                                                 => '3gp',
        'video/3gpp'                                                                => '3gp',
        'application/x-compressed'                                                  => '7zip',
        'audio/x-acc'                                                               => 'aac',
        'audio/ac3'                                                                 => 'ac3',
        'application/postscript'                                                    => 'ai',
        'audio/x-aiff'                                                              => 'aif',
        'audio/aiff'                                                                => 'aif',
        'audio/x-au'                                                                => 'au',
        'video/x-msvideo'                                                           => 'avi',
        'video/msvideo'                                                             => 'avi',
        'video/avi'                                                                 => 'avi',
        'application/x-troff-msvideo'                                               => 'avi',
        'application/macbinary'                                                     => 'bin',
        'application/mac-binary'                                                    => 'bin',
        'application/x-binary'                                                      => 'bin',
        'application/x-macbinary'                                                   => 'bin',
        'image/bmp'                                                                 => 'bmp',
        'image/x-bmp'                                                               => 'bmp',
        'image/x-bitmap'                                                            => 'bmp',
        'image/x-xbitmap'                                                           => 'bmp',
        'image/x-win-bitmap'                                                        => 'bmp',
        'image/x-windows-bmp'                                                       => 'bmp',
        'image/ms-bmp'                                                              => 'bmp',
        'image/x-ms-bmp'                                                            => 'bmp',
        'application/bmp'                                                           => 'bmp',
        'application/x-bmp'                                                         => 'bmp',
        'application/x-win-bitmap'                                                  => 'bmp',
        'application/cdr'                                                           => 'cdr',
        'application/coreldraw'                                                     => 'cdr',
        'application/x-cdr'                                                         => 'cdr',
        'application/x-coreldraw'                                                   => 'cdr',
        'image/cdr'                                                                 => 'cdr',
        'image/x-cdr'                                                               => 'cdr',
        'zz-application/zz-winassoc-cdr'                                            => 'cdr',
        'application/mac-compactpro'                                                => 'cpt',
        'application/pkix-crl'                                                      => 'crl',
        'application/pkcs-crl'                                                      => 'crl',
        'application/x-x509-ca-cert'                                                => 'crt',
        'application/pkix-cert'                                                     => 'crt',
        'text/css'                                                                  => 'css',
        'text/x-comma-separated-values'                                             => 'csv',
        'text/comma-separated-values'                                               => 'csv',
        'application/vnd.msexcel'                                                   => 'csv',
        'application/x-director'                                                    => 'dcr',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
        'application/x-dvi'                                                         => 'dvi',
        'message/rfc822'                                                            => 'eml',
        'application/x-msdownload'                                                  => 'exe',
        'video/x-f4v'                                                               => 'f4v',
        'audio/x-flac'                                                              => 'flac',
        'video/x-flv'                                                               => 'flv',
        'image/gif'                                                                 => 'gif',
        'application/gpg-keys'                                                      => 'gpg',
        'application/x-gtar'                                                        => 'gtar',
        'application/x-gzip'                                                        => 'gzip',
        'application/mac-binhex40'                                                  => 'hqx',
        'application/mac-binhex'                                                    => 'hqx',
        'application/x-binhex40'                                                    => 'hqx',
        'application/x-mac-binhex40'                                                => 'hqx',
        'text/html'                                                                 => 'html',
        'image/x-icon'                                                              => 'ico',
        'image/x-ico'                                                               => 'ico',
        'image/vnd.microsoft.icon'                                                  => 'ico',
        'text/calendar'                                                             => 'ics',
        'application/java-archive'                                                  => 'jar',
        'application/x-java-application'                                            => 'jar',
        'application/x-jar'                                                         => 'jar',
        'image/jp2'                                                                 => 'jp2',
        'video/mj2'                                                                 => 'jp2',
        'image/jpx'                                                                 => 'jp2',
        'image/jpm'                                                                 => 'jp2',
        'image/jpeg'                                                                => 'jpeg',
        'image/pjpeg'                                                               => 'jpeg',
        'application/x-javascript'                                                  => 'js',
        'application/json'                                                          => 'json',
        'text/json'                                                                 => 'json',
        'application/vnd.google-earth.kml+xml'                                      => 'kml',
        'application/vnd.google-earth.kmz'                                          => 'kmz',
        'text/x-log'                                                                => 'log',
        'audio/x-m4a'                                                               => 'm4a',
        'audio/mp4'                                                                 => 'm4a',
        'application/vnd.mpegurl'                                                   => 'm4u',
        'audio/midi'                                                                => 'mid',
        'application/vnd.mif'                                                       => 'mif',
        'video/quicktime'                                                           => 'mov',
        'video/x-sgi-movie'                                                         => 'movie',
        'audio/mpeg'                                                                => 'mp3',
        'audio/mpg'                                                                 => 'mp3',
        'audio/mpeg3'                                                               => 'mp3',
        'audio/mp3'                                                                 => 'mp3',
        'video/mp4'                                                                 => 'mp4',
        'video/mpeg'                                                                => 'mpeg',
        'application/oda'                                                           => 'oda',
        'audio/ogg'                                                                 => 'ogg',
        'video/ogg'                                                                 => 'ogg',
        'application/ogg'                                                           => 'ogg',
        'font/otf'                                                                  => 'otf',
        'application/x-pkcs10'                                                      => 'p10',
        'application/pkcs10'                                                        => 'p10',
        'application/x-pkcs12'                                                      => 'p12',
        'application/x-pkcs7-signature'                                             => 'p7a',
        'application/pkcs7-mime'                                                    => 'p7c',
        'application/x-pkcs7-mime'                                                  => 'p7c',
        'application/x-pkcs7-certreqresp'                                           => 'p7r',
        'application/pkcs7-signature'                                               => 'p7s',
        'application/pdf'                                                           => 'pdf',
        'application/octet-stream'                                                  => 'pdf',
        'application/x-x509-user-cert'                                              => 'pem',
        'application/x-pem-file'                                                    => 'pem',
        'application/pgp'                                                           => 'pgp',
        'application/x-httpd-php'                                                   => 'php',
        'application/php'                                                           => 'php',
        'application/x-php'                                                         => 'php',
        'text/php'                                                                  => 'php',
        'text/x-php'                                                                => 'php',
        'application/x-httpd-php-source'                                            => 'php',
        'image/png'                                                                 => 'png',
        'image/x-png'                                                               => 'png',
        'application/powerpoint'                                                    => 'ppt',
        'application/vnd.ms-powerpoint'                                             => 'ppt',
        'application/vnd.ms-office'                                                 => 'ppt',
        'application/msword'                                                        => 'doc',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/x-photoshop'                                                   => 'psd',
        'image/vnd.adobe.photoshop'                                                 => 'psd',
        'audio/x-realaudio'                                                         => 'ra',
        'audio/x-pn-realaudio'                                                      => 'ram',
        'application/x-rar'                                                         => 'rar',
        'application/rar'                                                           => 'rar',
        'application/x-rar-compressed'                                              => 'rar',
        'audio/x-pn-realaudio-plugin'                                               => 'rpm',
        'application/x-pkcs7'                                                       => 'rsa',
        'text/rtf'                                                                  => 'rtf',
        'text/richtext'                                                             => 'rtx',
        'video/vnd.rn-realvideo'                                                    => 'rv',
        'application/x-stuffit'                                                     => 'sit',
        'application/smil'                                                          => 'smil',
        'text/srt'                                                                  => 'srt',
        'image/svg+xml'                                                             => 'svg',
        'application/x-shockwave-flash'                                             => 'swf',
        'application/x-tar'                                                         => 'tar',
        'application/x-gzip-compressed'                                             => 'tgz',
        'image/tiff'                                                                => 'tiff',
        'font/ttf'                                                                  => 'ttf',
        'text/plain'                                                                => 'txt',
        'text/x-vcard'                                                              => 'vcf',
        'application/videolan'                                                      => 'vlc',
        'text/vtt'                                                                  => 'vtt',
        'audio/x-wav'                                                               => 'wav',
        'audio/wave'                                                                => 'wav',
        'audio/wav'                                                                 => 'wav',
        'application/wbxml'                                                         => 'wbxml',
        'video/webm'                                                                => 'webm',
        'image/webp'                                                                => 'webp',
        'audio/x-ms-wma'                                                            => 'wma',
        'application/wmlc'                                                          => 'wmlc',
        'video/x-ms-wmv'                                                            => 'wmv',
        'video/x-ms-asf'                                                            => 'wmv',
        'font/woff'                                                                 => 'woff',
        'font/woff2'                                                                => 'woff2',
        'application/xhtml+xml'                                                     => 'xhtml',
        'application/excel'                                                         => 'xl',
        'application/msexcel'                                                       => 'xls',
        'application/x-msexcel'                                                     => 'xls',
        'application/x-ms-excel'                                                    => 'xls',
        'application/x-excel'                                                       => 'xls',
        'application/x-dos_ms_excel'                                                => 'xls',
        'application/xls'                                                           => 'xls',
        'application/x-xls'                                                         => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
        'application/vnd.ms-excel'                                                  => 'xlsx',
        'application/xml'                                                           => 'xml',
        'text/xml'                                                                  => 'xml',
        'text/xsl'                                                                  => 'xsl',
        'application/xspf+xml'                                                      => 'xspf',
        'application/x-compress'                                                    => 'z',
        'application/x-zip'                                                         => 'zip',
        'application/zip'                                                           => 'zip',
        'application/x-zip-compressed'                                              => 'zip',
        'application/s-compressed'                                                  => 'zip',
        'multipart/x-zip'                                                           => 'zip',
        'text/x-scriptzsh'                                                          => 'zsh',
    ];

    return isset($mime_map[$mime]) ? $mime_map[$mime] : false;
}
// Đếm , thống kê module 
function count_module($course,$currentsection) {
    global $DB,$OUTPUT;
    $modinfo = get_fast_modinfo($course);
    $output = array(
        //lấy thông tin module
        "activityinfo" => array(),
        //Đếm số lượng module
        "total" => array(),
    );
    $sectionmods = [];
    $total = 0;
    if(!empty($modinfo->sections[$currentsection->section])) { 
        foreach ($modinfo->sections[$currentsection->section] as $cmid) {
            $thismod = $modinfo->cms[$cmid];
            $getmodules = $DB->get_records_sql('SELECT cm.id, cm.deletioninprogress FROM {course_modules} cm JOIN {course_sections} cs ON cm.section = cs.id WHERE cm.instance = :section AND cm.course = :courseid',['section' => $thismod->instance,'courseid' => $course->id]);
            //Check điều kiện là là label hoặc module đã xóa
            if ($thismod->modname == 'label' || $thismod->visible == 0) {
                continue;
            }
            foreach($getmodules as $getmodule) {
                if($getmodule->deletioninprogress != 0) {
                    continue 2;
                }    
            }
            if (isset($sectionmods[$thismod->modname])) {
                $sectionmods[$thismod->modname]['name'] = $thismod->modplural;
                $sectionmods[$thismod->modname]['count']++;
            } else {
                $sectionmods[$thismod->modname]['name'] = $thismod->modfullname;
                $sectionmods[$thismod->modname]['count'] = 1;
                $sectionmods[$thismod->modname]['image'] = $OUTPUT->image_url('icon', $thismod->modname);
            }
            $total++;
        }
    }
    foreach($sectionmods as $mod) {
        $output['activityinfo'][] = '<img class="mr-3" src="'.$mod['image'].'">'.$mod['count'].' '.$mod['name'];
    }
    $output['total'] = $total;
    return $output;
}
// Chuyển mimetype thành hình ảnh
function mimetype2Img($mimetype) {
    global $OUTPUT;
    $typeresource = mime2ext($mimetype);
    // $typeresource = mime2ext('image/jpeg');
    // var_dump($typeresource);die();
    if ($typeresource == 'xls' || $typeresource == 'xlsx' || $typeresource == 'xlsm') {
        $img = html_writer::img($OUTPUT->image_url('f/spreadsheet-24'),'',['class' => 'pr-1 img-module']);
    } elseif ($typeresource == 'ppt' || $typeresource == 'pptx') {
        $img = html_writer::img($OUTPUT->image_url('f/powerpoint-24'),'',['class' => 'pr-1 img-module']);
    } elseif ($typeresource == 'docx' || $typeresource == 'doc' || $typeresource == 'docm') {
        $img = html_writer::img($OUTPUT->image_url('f/document-24'),'',['class' => 'pr-1 img-module']);
    } elseif ($typeresource == 'pdf') {
        $img = html_writer::img($OUTPUT->image_url('f/pdf-24'),'',['class' => 'pr-1 img-module']);
    } elseif ($typeresource == 'jpeg') {
        $img = html_writer::img($OUTPUT->image_url('f/jpeg-24'),'',['class' => 'pr-1 img-module']);
    } elseif ($typeresource == 'png') {
        $img = html_writer::img($OUTPUT->image_url('f/png-24'),'',['class' => 'pr-1 img-module']);
    } elseif ($typeresource == 'txt') {
        $img = html_writer::img($OUTPUT->image_url('f/text-24'),'',['class' => 'pr-1 img-module']);
    } elseif ($typeresource == 'mp4') {
        $img = html_writer::img($OUTPUT->image_url('f/video-24'),'',['class' => 'pr-1 img-module']);
    } elseif ($typeresource == 'mp3') {
        $img = html_writer::img($OUTPUT->image_url('f/wav-24'),'',['class' => 'pr-1 img-module']);
    }
    else {
        $img = '';
    }
    return $img;
}
// lấy url module loại module resource (pdf,word,exel...)
function get_link_file($module) {
    global $DB,$CFG;
    $resource = $DB->get_record('resource', array('id'=>$module->instance), '*', MUST_EXIST);
    $context = context_module::instance($module->coursemoduleid);
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);
    $file = reset($files);
    $filename = $file->get_filename();
    $path = '/'.$file->get_contextid().'/mod_resource/content/'.$resource->revision.$file->get_filepath().$file->get_filename();
    $fullurl = file_encode_url($CFG->wwwroot.'/pluginfile.php', $path, false);
    return $fullurl;
}
/**
 * Lấy danh sách users trong 1 khóa học và custom điều kiện where:
 * role, courseid, userid, etc..
 * @param  [int] $userid      [Mã user]
 * @param  string $customquery [String để custom điều kiện lọc]
 * @return [array Object]
 */
function get_list_user_in_course($userid, $customquery = '') {
    global $DB;
    $query = "
         SELECT *, 
            (SELECT COUNT(u.id) FROM mdl_role_assignments ra
                JOIN mdl_user u ON ra.userid = u.id
                JOIN mdl_user_enrolments ue ON u.id = ue.userid 
                JOIN mdl_enrol enr ON ue.enrolid = enr.id
                JOIN mdl_course c ON enr.courseid = c.id
                JOIN mdl_context ct ON ct.id = ra.contextid AND ct.instanceid = c.id
            WHERE u.id = ?) AS total
                    FROM (
                        SELECT ROW_NUMBER() OVER (ORDER BY u.id) AS RowNum, c.id courseid,c.fullname coursename, CONCAT(u.firstname, ' ', u.lastname) fullname, u.id userid,r.shortname rolename
                        FROM mdl_role_assignments ra
                            JOIN mdl_user u ON ra.userid = u.id
                            JOIN mdl_user_enrolments ue ON u.id = ue.userid 
                            JOIN mdl_enrol enr ON ue.enrolid = enr.id
                            JOIN mdl_course c ON enr.courseid = c.id
                            JOIN mdl_context ct ON ct.id = ra.contextid AND ct.instanceid = c.id
                            JOIN mdl_role r ON ra.roleid = r.id
                        WHERE u.id = ?
                    ) AS Mydata";
    $data = $DB->get_records_sql($query, [$userid, $userid]);
    return $data;
}


/**
 * Tạo node trên menu flat_nav
 * @param  global_navigation $navigation []
 * @return [type]                        []
 */
function get_link_folder($folder,&$output = '',$stt = 0) {
    global $DB;
    if($folder->parent != 0) {
        $parentfolder = $DB->get_record_sql("SELECT lf.name,lf.id,lf.parent FROM {library_folder} lf WHERE lf.id = $folder->parent");
        $temp = $output;
        $output = '';
        if($stt == 0) {
            $temp = $folder->foldername;
        }
        $output .= $parentfolder->name .'/ '. $temp;
        get_link_folder($parentfolder,$output,++$stt);
    }
    if($folder->parent == 0 && $stt == 0) {
        $output = $folder->foldername;
    }
    return $output;
}
// Lấy điểm tổng kết của học viên
function get_finalgrade_student($userid,$courseid) {
    global $DB;
    $get_grade = $DB->get_record_sql("
                    SELECT gg.userid,gi.courseid, CONVERT(DECIMAL(10,1),gg.finalgrade) AS gradefinal, RANK() OVER (ORDER BY gg.finalgrade DESC) AS rank, CONCAT(u.lastname,' ',u.firstname) AS fullname 
                    FROM mdl_grade_grades gg join mdl_grade_items gi ON gi.id=gg.itemid JOIN mdl_user u ON gg.userid = u.id
                    WHERE gg.finalgrade is not NULL AND gi.itemmodule IS NULL AND gi.courseid = :courseid AND gg.userid = :userid
                    ORDER BY gg.finalgrade DESC", ['courseid' => $courseid, 'userid' => $userid]);
    return $get_grade;
}
// // Xếp hạng học viên trong khóa
function get_rank_student_incourse($courseid,$userid) {
    $theme_settings = new theme_settings();
    $listrank = [];
    $liststudent = get_listuser_in_course($courseid);
    foreach ($liststudent as $value) {
        $arrgrade = [];
        $grade = get_finalgrade_student($value->id,$courseid);
        $arrgrade[] = ($grade != null) ? $grade->gradefinal : 0;
        $arrgrade[] = $value->id;
        $data[] = $arrgrade;
    }
    rsort($data);
    $rank = 1;
    $prev_rank = $rank;
    for($x = 0; $x < count($data); $x++) {
        if ($x==0) {
            $temp = [$data[$x][1] => get_string('rank','local_newsvnr').' '.($rank)];
        }
        elseif ($data[$x][0] != $data[$x-1][0]) {
            $rank++;
            $prev_rank = $rank;
            $temp = [$data[$x][1] => get_string('rank','local_newsvnr').' '.($rank)];
        }
        else {
            $temp = [$data[$x][1] => get_string('rank','local_newsvnr').' '.($prev_rank)];
        }
        array_push($listrank,$temp);
    }
    foreach ($listrank as $list) {
        foreach ($list as $key => $value) {
            if($key == $userid) {
                $rank = $value;
                break;
            };
        }
    }
    return $rank;
}
// Lấy form check hoàn thành module trong khóa
function get_course_section_cm_completion($course,&$completioninfo,$mod) {
    global $CFG, $DB, $USER, $PAGE , $OUTPUT;
    $output = '';
    $istrackeduser = $completioninfo->is_tracked_user($USER->id);
    $completion = $completioninfo->is_enabled($mod);
    $isediting = $PAGE->user_is_editing();
    if ($completion == COMPLETION_TRACKING_NONE) {
        if ($isediting) {
            $output .= html_writer::span('&nbsp;', 'filler');
        }
        return $output;
    }
    $completionicon = '';
    if ($isediting || !$istrackeduser) {
        switch ($completion) {
            case COMPLETION_TRACKING_MANUAL :
                $completionicon = 'manual-enabled'; break;
            case COMPLETION_TRACKING_AUTOMATIC :
                $completionicon = 'auto-enabled'; break;
        }
    } else {
        $completiondata = $completioninfo->get_data($mod, true);
        if ($completion == COMPLETION_TRACKING_MANUAL) {
            switch($completiondata->completionstate) {
                case COMPLETION_INCOMPLETE:
                    $completionicon = 'manual-n' . ($completiondata->overrideby ? '-override' : '');
                    break;
                case COMPLETION_COMPLETE:
                    $completionicon = 'manual-y' . ($completiondata->overrideby ? '-override' : '');
                    break;
            }
        } else { // Automatic
            switch($completiondata->completionstate) {
                case COMPLETION_INCOMPLETE:
                    $completionicon = 'auto-n' . ($completiondata->overrideby ? '-override' : '');
                    break;
                case COMPLETION_COMPLETE:
                    $completionicon = 'auto-y' . ($completiondata->overrideby ? '-override' : '');
                    break;
                case COMPLETION_COMPLETE_PASS:
                    $completionicon = 'auto-pass'; break;
                case COMPLETION_COMPLETE_FAIL:
                    $completionicon = 'auto-fail'; break;
            }
        }
    }
    if($completionicon) {
        $formattedname = html_entity_decode($mod->get_formatted_name(), ENT_QUOTES, 'UTF-8');
        if (!$isediting && $istrackeduser && $completiondata->overrideby) {
            $args = new stdClass();
            $args->modname = $formattedname;
            $overridebyuser = \core_user::get_user($completiondata->overrideby, '*', MUST_EXIST);
            $args->overrideuser = fullname($overridebyuser);
            $imgalt = get_string('completion-alt-' . $completionicon, 'completion', $args);
        } else {
            $imgalt = get_string('completion-alt-' . $completionicon, 'completion', $formattedname);
        }
        if ($isediting || !$istrackeduser || !has_capability('moodle/course:togglecompletion', $mod->context)) {
            // When editing, the icon is just an image.
            $completionpixicon = new pix_icon('i/completion-'.$completionicon, $imgalt, '',
                    array('title' => $imgalt, 'class' => 'iconsmall'));
            $output .= html_writer::tag('span', $OUTPUT->render($completionpixicon),
                    array('class' => 'autocompletion'));
        } else if ($completion == COMPLETION_TRACKING_MANUAL) {
            $newstate =
                $completiondata->completionstate == COMPLETION_COMPLETE
                ? COMPLETION_INCOMPLETE
                : COMPLETION_COMPLETE;
            // In manual mode the icon is a toggle form...

            // If this completion state is used by the
            // conditional activities system, we need to turn
            // off the JS.
            $extraclass = '';
            if (!empty($CFG->enableavailability) &&
                    core_availability\info::completion_value_used($course, $mod->id)) {
                $extraclass = ' preventjs';
            }
            $output .= html_writer::start_tag('form', array('method' => 'post',
                'action' => new moodle_url('/course/togglecompletion.php'),
                'class' => 'togglecompletion'. $extraclass));
            $output .= html_writer::start_tag('div');
            $output .= html_writer::empty_tag('input', array(
                'type' => 'hidden', 'name' => 'id', 'value' => $mod->id));
            $output .= html_writer::empty_tag('input', array(
                'type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
            $output .= html_writer::empty_tag('input', array(
                'type' => 'hidden', 'name' => 'modulename', 'value' => $formattedname));
            $output .= html_writer::empty_tag('input', array(
                'type' => 'hidden', 'name' => 'completionstate', 'value' => $newstate));
            $output .= html_writer::tag('button',
                $OUTPUT->pix_icon('i/completion-' . $completionicon, $imgalt),
                    array('class' => 'btn btn-link', 'aria-live' => 'assertive'));
            $output .= html_writer::end_tag('div');
            $output .= html_writer::end_tag('form');
        } else {
            // In auto mode, the icon is just an image.
            $completionpixicon = new pix_icon('i/completion-'.$completionicon, $imgalt, '',
                    array('title' => $imgalt));
            $output .= html_writer::tag('span', $OUTPUT->render($completionpixicon),
                    array('class' => 'autocompletion'));
        }
    }
    return $output;
}
// Lấy điểm các module trong khóa 
function get_grade_module_incourse($courseid,$userid) {
    global $DB;
    $listgrade = $DB->get_records_sql('SELECT gi.id,gi.itemname,gg.finalgrade,gi.grademax FROM mdl_grade_grades gg
                                                    JOIN mdl_grade_items gi ON gg.itemid = gi.id
                                                WHERE gg.userid = :userid AND gi.courseid = :courseid AND rawgrade IS NOT NULL', ['userid' => $userid, 'courseid' => $courseid]);
    return $listgrade;

}
// Lấy danh sách user dựa vào role trong 1 khóa học
function get_listuser_in_course($courseid, $roleid = 5, $userid = 0) {
    global $DB;
    $params = [];
    if(!$courseid) {

    }
    $params['courseid'] = $courseid;

    $wheresql = "WHERE c.id = :courseid AND r.id = :roleid";

    if($userid > 2) {
        $wheresql .= " AND u.id = :userid";
        $params['userid'] = $userid;
    }
    $params['roleid'] = $roleid;


    $sql = "SELECT u.*, CONCAT(u.firstname, ' ', u.lastname) userfullname
            FROM mdl_role_assignments ra
                JOIN mdl_user u ON ra.userid = u.id
                JOIN mdl_user_enrolments ue ON u.id = ue.userid 
                JOIN mdl_enrol enr ON ue.enrolid = enr.id
                JOIN mdl_course c ON enr.courseid = c.id
                JOIN mdl_context ct ON ct.id = ra.contextid AND ct.instanceid = c.id
                JOIN mdl_role r ON ra.roleid = r.id
            $wheresql";
    $data = $DB->get_records_sql($sql, $params);
    return $data;
}
// Lấy điểm trung bình của khóa học
function get_course_grade_avg($courseid, $courseavg = true) {
    global $CFG, $DB, $USER;

    require_once $CFG->dirroot . '/grade/lib.php';
    require_once $CFG->dirroot . '/grade/report/grader/lib.php';
    require_once $CFG->libdir  . '/gradelib.php';

    $course = $DB->get_record('course', ['id' => $courseid]);
    $page = 0;
    $sortitemid = 0;
    $context = context_course::instance($course->id);
    $displayaverages = true;
    $USER->gradeediting[$courseid] = $courseid;
    $gpr = new grade_plugin_return(
        array(
            'type' => 'report',
            'plugin' => 'grader',
            'course' => $course,
            'page' => $page
        )
    );
    $report = new grade_report_grader($course->id, $gpr, $context, $page, $sortitemid);
    $report->load_users();
    $report->load_final_grades();
    $report->canviewhidden = true;
    $data = $report->get_right_rows($displayaverages);
    $lastitem = end($data);
    $count = count($lastitem->cells);
    $grades = [];
    if($courseavg == false) {
        $modules = array_values($DB->get_records('grade_items', ['courseid' => $courseid]));
        foreach($modules as $keymodule => $module) {
            if($module->itemtype == 'course' && $module->calculation != null) {
                $grade_avg = new stdClass;
                $grade_avg->avg = 'Course AVG';
                $grade_avg->courseavg = explode(' ', $lastitem->cells[$count - 1]->text)[0];
                $grades[] = $grade_avg;
                continue;
            }
            $grade_avg = new stdClass;
            $grade_avg->modulename = $module->itemname;
            $grade_avg->moduletype = $module->itemtype;
            $grade_avg->moduleavg = explode(' ', $lastitem->cells[$keymodule - 1]->text)[0];
            $grades[] = $grade_avg;
        }
    } else {
        $grade_avg = new stdClass;
        $grade_avg->avg = 'Course AVG';
        $grade_avg->courseavg = explode(' ', $lastitem->cells[$count - 1]->text)[0];
        $grades[] = $grade_avg;
    }
    return $grades;
}

// Tính spent total modules trong course
function get_spenttime_total_module($courseid, $coursemoduleid = 0, $userid = 0, $action = 'viewed') {
    global $DB;
    $params = [];
    $wheresql = '';
    if(!is_int($courseid)) {
        throw new coding_exception('courseid not found!');
    } else {
        $params['courseid'] = $courseid;
        $params['action'] = $action;
    }
    if($userid != 0) {
        $wheresql .= 'AND lsl.userid = :userid';
        $params['userid'] = $userid;
    }
    if(is_int($coursemoduleid) && $coursemoduleid != 0) {
        $wheresql .= 'AND lsl.contextinstanceid = :coursemoduleid';
        $params['coursemoduleid'] = $coursemoduleid;
    } else {
        throw new coding_exception('coursemoduleid not found!');
    }
    $sql = "SELECT lsl.* 
            FROM mdl_logstore_standard_log lsl 
                JOIN mdl_course_modules cm ON lsl.contextinstanceid = cm.id
            WHERE lsl.target = 'course_module' 
                AND lsl.action = :action 
                AND lsl.courseid = :courseid 
                $wheresql
            ORDER BY lsl.timecreated";
    $data = $DB->get_records_sql($sql, $params);
    $timespent_modules = [];
    foreach($data as $keymodule => $module) {
        if(count($timespent_modules) == 0) {
            $timespent_total = 0;
            $timespent_modules[$module->contextinstanceid] = $timespent_total;
        } else {
            if($timespent_modules[$module->contextinstanceid] == $module->contextinstanceid) {
                $previouslog = array_shift($logs);
                $previouslogtime = $previouslog->time;
                $sessionstart = $previouslog->time;
                $dedication = 0;
            }
        }
    }
}

// Đổi unixtime thành giờ phút giây
function format_dedication($totalsecs) {
    $totalsecs = abs($totalsecs);

    $str = new stdClass();
    $str->hour = get_string('hour');
    $str->hours = get_string('hours');
    $str->min = get_string('min');
    $str->mins = get_string('mins');
    $str->sec = get_string('sec');
    $str->secs = get_string('secs');

    $hours = floor($totalsecs / HOURSECS);
    $remainder = $totalsecs - ($hours * HOURSECS);
    $mins = floor($remainder / MINSECS);
    $secs = round($remainder - ($mins * MINSECS), 2);

    $ss = ($secs == 1) ? $str->sec : $str->secs;
    $sm = ($mins == 1) ? $str->min : $str->mins;
    $sh = ($hours == 1) ? $str->hour : $str->hours;

    $ohours = '';
    $omins = '';
    $osecs = '';

    if ($hours) {
        $ohours = $hours . ' ' . $sh;
    }
    if ($mins) {
        $omins = $mins . ' ' . $sm;
    }
    if ($secs) {
        $osecs = $secs . ' ' . $ss;
    }

    if ($hours) {
        return trim($ohours . ' ' . $omins);
    }
    if ($mins) {
        return trim($omins . ' ' . $osecs);
    }
    if ($secs) {
        return $osecs;
    }
    return '-';
}

// Lấy tỉ lệ hoàn thành module trong khóa 
function get_course_complete_module_rate($courseid) {
    global $DB;
    $liststudent = get_listuser_in_course($courseid);
    $sum = 0;
    foreach ($liststudent as  $student) {
        $studentmdfinish = $DB->get_record_sql('SELECT COUNT(*) as count
                                                FROM mdl_course_modules_completion cmc
                                                JOIN mdl_course_modules cm ON cm.id = cmc.coursemoduleid
                                            WHERE cm.course = :courseid AND cmc.userid = :userid',['courseid' => $courseid,'userid' => $student->id]);
        $allcoursemodule = $DB->get_record_sql('SELECT COUNT(*) as count FROM mdl_course_modules WHERE course =:courseid AND deletioninprogress = 0 AND visible = 1',['courseid' => $courseid]);
        $ratestudentfinish = round(($studentmdfinish->count*100)/$allcoursemodule->count);
        $sum = $sum + $ratestudentfinish;
    }
    $rate = round($sum/count($liststudent));
    return $rate;
}
// Kiểm tra vai trò giáo viên 
function check_teacherrole($userid) {
    global $DB;
    $check_is_teacher = $DB->get_field_sql('SELECT COUNT(c.id) course
                            FROM  mdl_context ct
                                JOIN mdl_course c ON c.id = ct.instanceid
                            WHERE ct.contextlevel = 50 AND c.id <> 1
                            AND (EXISTS (SELECT 1 
                                         FROM mdl_role_assignments ra
                                         WHERE ra.contextid = ct.id 
                                            AND ra.roleid = 3 
                                            AND ra.userid = :userid))', ['userid' => $userid]);
    return $check_is_teacher;
}
// Kiểm tra vai trò học viên
function check_studentrole($userid) {
    global $DB;
    $check_is_student = $DB->get_field_sql('SELECT COUNT(c.id) course
                            FROM  mdl_context ct
                                JOIN mdl_course c ON c.id = ct.instanceid
                            WHERE ct.contextlevel = 50 AND c.id <> 1
                            AND (EXISTS (SELECT 1 
                                         FROM mdl_role_assignments ra
                                         WHERE ra.contextid = ct.id 
                                            AND ra.roleid = 5 
                                            AND ra.userid = :userid))', ['userid' => $userid]);
    return $check_is_student;
}
// Quyền xem thư mục thư viện
function folder_permission_list($folderid) {
    global $DB;
    $listpositionid = [];;
    $permission = $DB->get_record('library_folder_permissions',['folderlibraryid' => $folderid]);
    if(!empty($permission)) {
        $userpermissions = $DB->get_records('library_user_permissions',['permissionid' => $permission->id]);
            foreach ($userpermissions as $value) {
                $listpositionid[] = $value->positionid;
            }
    }
    return $listpositionid;

}
// Lấy huy hiệu của người dùng
function get_user_badge($userid) {
    global $CFG;
    require_once $CFG->dirroot . '/badges/renderer.php';
    $data = [];
    $badges = badges_get_user_badges($userid, 0, null, null, null, true);
    foreach ($badges as $badge) {
        $obj = new stdClass;
        $context  = ($badge->type == BADGE_TYPE_SITE) ? context_system::instance() : context_course::instance($badge->courseid);
        $imageurl = moodle_url::make_pluginfile_url($context->id, 'badges', 'badgeimage', $badge->id, '/', 'f1', false);
        $obj->name = $badge->name;
        $obj->url = new moodle_url('/badges/badge.php', array('hash' => $badge->uniquehash));
        $obj->image = html_writer::empty_tag('img', array('src' => $imageurl, 'class' => 'badge-image'));
        $data[] = $obj;
    }
    return $data;
}

// course card info
function get_coursecard_info($courseid) {
    global $DB,$OUTPUT,$CFG,$USER;
    $obj = new stdClass;
    $course         = $DB->get_record('course',['id' => $courseid]);
    $coursestarred = $DB->get_record('favourite', ['component' => 'core_course', 'itemid' => $courseid, 'userid' => $USER->id]);
    $progress = round(\core_completion\progress::get_course_progress_percentage($course));
    $theme_settings = new theme_settings();
    $courseobj      = new \core_course_list_element($course);
    $obj->link      = $CFG->wwwroot . "/course/view.php?id=" . $courseid;
    $arr            = $theme_settings::role_courses_teacher_slider_block_course_recent($courseid);
    $obj->fullnamet      = $arr->fullnamet;
    $obj->countstudent   = $arr->studentnumber;
    $obj->hasstarred = ($coursestarred) ? true : false;
    $obj->id = $courseid;
    if($progress > 0) {
        $noprogress = "";
    } else {
        $noprogress = $progress.'%';
    }
    $obj->enrolmethod = '<div class="progress">
                         <div class="progress-bar" role="progressbar" aria-valuenow="' . $progress . '"
                            aria-valuemin="0" aria-valuemax="100" style="width:' . $progress . '%">
                            ' . $progress . '%
                         </div>'.$noprogress.'
                      </div>';
    $obj->courseimage    = $theme_settings::get_course_images($courseobj, $obj->link);
    $obj->fullname = $course->fullname;
    if (isset($arr->id)) {
        $stduser = new stdClass();
        $userid  = $DB->get_records('user', array('id' => $arr->id));
        foreach ($userid as $userdata) {
            $stduser = (object) $userdata;
        }
        $obj->imageteacher = $OUTPUT->user_picture($stduser, array('size' => 72));
    } else {
        $obj->imageteacher = $arr->imgdefault;
    }
    return $obj;
}
////
function form_comment_wiki($title,$contextid,$pageid,$userid,$action,$commentid = null) {
    $cancel = ($commentid) ? '<button type="button" action="'.$action.'" commentid="'.$commentid.'" class="mt-2 btn btn-danger cancel-comment">Hủy</button>' : '<div class="filename"></div><button class="mt-2 btn post_file btn-secondary" style="background-color:#ced4da !important">Chọn file</button>';
    $output =   '<div class="wiki-add new-detail-comment">
                     <div class="new-detail-comment-body">
                        <label class="new-detail-comment-title">'.$title.'</label>
                        <textarea commentid="'.$commentid.'" contextid="'.$contextid.'" wikipage="'.$pageid.'" userid="'.$userid.'" class="new-detail-comment-content" name="content" id="content_comment" maxlength="1000"></textarea>
                        <div class="new-detail-comment-control">
                            '.$cancel.'
                           <button type="button" action="'.$action.'" class="mt-2 btn btn-submit post_comment">Gửi</button>
                        </div>
                     </div>
                </div>';
    return $output;

}
function insert_log($resp,$url) {
    global $DB,$USER;
    $apiname = $DB->get_field('local_newsvnr_api','functionapi',['url' => $url]);
    $log = new stdClass();
    $log->url = $url;
    $log->time = time();
    $log->userid = $USER->id;
    $log->info = $resp;
    $log->action = $apiname;
    $DB->insert_record('log',$log);
}
// form popup
function get_modal_boostrap($html,$idmodal,$title = '',$footer = false) {
    $output = '';
    $output .= html_writer::start_div('modal',['id' => $idmodal]);
    $output .= html_writer::start_div('modal-dialog');
    $output .= html_writer::start_div('modal-content');
    $output .= html_writer::tag('div','<h4 class="modal-title">'.$title.'</h4><button type="button" class="close" data-dismiss="modal">&times;</button>' ,['class' => 'modal-header']);
    $output .= html_writer::tag('div',$html,['class' => 'modal-body']);
    if($footer == true) {
        $output .= html_writer::start_div('modal-footer');
        $output .= html_writer::tag('button',get_string('savechange','local_newsvnr'),['type' => 'button','class' => 'submit btn btn-primary']);
        $output .= html_writer::tag('button',get_string('close','local_newsvnr'),['type' => 'button','class' => 'btn btn-secondary','data-dismiss' => 'modal']);
        $output .= html_writer::end_div();
    }
    $output .= html_writer::end_div();
    $output .= html_writer::end_div();
    $output .= html_writer::end_div();
    return $output;
}

// Gửi message và email yêu cầu duyệt tài liệu
function send_email_requestfile($moduleinfo) {
    global $CFG, $DB, $USER;
    $detailurl = $CFG->wwwroot . '/library.php';
    $fullmessage = '<p>Dear (Mr/Mrs): <strong>Võ Tâm Ngọc Tinh</strong></p>
                    <p>Anh/Chị đang có yêu cầu duyệt tài liệu từ thư viện bên dưới:</p>
                    <p>Anh/Chị bấm vào link sau để xem chi tiết, tiến hành <strong>phê duyệt</strong> hay <strong>từ chối </strong>(<a href="'.$detailurl.'">Đường link</a>)</p>
                    <table style="border-collapse: collapse;width: 100%;">
                        <thead>
                            <tr>
                                <th scope="col" style="border: 1px solid #ddd;padding: 8px;text-align: left;background-color:#c5f3f3">STT</th>
                                <th scope="col" style="border: 1px solid #ddd;padding: 8px;text-align: left;background-color:#c5f3f3">Mã nhân viên</th>
                                <th scope="col" style="border: 1px solid #ddd;padding: 8px;text-align: left;background-color:#c5f3f3">Tên nhân viên</th>
                                <th scope="col" style="border: 1px solid #ddd;padding: 8px;text-align: left;background-color:#c5f3f3">Chức vụ</th>
                                <th scope="col" style="border: 1px solid #ddd;padding: 8px;text-align: left;background-color:#c5f3f3">Tên tài liệu</th>
                                <th scope="col" style="border: 1px solid #ddd;padding: 8px;text-align: left;background-color:#c5f3f3">Loại tài liệu</th>
                                <th scope="col" style="border: 1px solid #ddd;padding: 8px;text-align: left;background-color:#c5f3f3">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="border: 1px solid #ddd;padding: 8px;text-align: left;">1</td>
                                <td style="border: 1px solid #ddd;padding: 8px;text-align: left;">'.$USER->usercode.'</td>
                                <td style="border: 1px solid #ddd;padding: 8px;text-align: left;">'.fullname($USER).'</td>
                                <td style="border: 1px solid #ddd;padding: 8px;text-align: left;">'.$DB->get_field('orgstructure_position', 'name',['id' => $USER->orgpositionid]).'</td>
                                <td style="border: 1px solid #ddd;padding: 8px;text-align: left;">'.$moduleinfo->name.'</td>
                                <td style="border: 1px solid #ddd;padding: 8px;text-align: left;">'.ucwords($moduleinfo->modulename).'</td>
                                <td style="border: 1px solid #ddd;padding: 8px;text-align: left;">Yêu cầu</td>
                            </tr>
                        </tbody>
                    </table>
                    <br><strong>Thanks &amp; best regards,</strong><br>----------------------------------------<br><span><em><span style=""><span style="">Lưu ý</span></span>:</em>
                    </span> Thư này được gửi tự động từ hệ thống, vui lòng không reply. Để hỗ trợ thông tin, liên hệ bộ phân IT.<br>
                    <p></p>';
    // lấy danh sách users có quyền duyệt file trong thư viện
    $get_users_has_capability = $DB->get_records_sql("SELECT ctx.instanceid userid 
                                                    FROM mdl_role_capabilities rc
                                                        LEFT JOIN mdl_context ctx ON ctx.id = rc.contextid
                                                    WHERE rc.capability = :cap
                                                        AND rc.permission = 1 
                                                        AND ctx.contextlevel = :contextlevel", ['cap' =>'local/newsvnr:confirmfilelibrary', 'contextlevel' => 30]);
    if($get_users_has_capability) {
        foreach($get_users_has_capability as $user) {
            $userto = $DB->get_record('user', ['id' => $user->userid]);
            $message = new \core\message\message();
            $message->component = 'local_newsvnr';
            $message->name = 'requestfile';
            $message->userfrom = $USER->id;
            $message->userto = $userto;
            $message->subject = '[VnR.Admin] Nhắc nhở duyệt tài liệu từ thư viện';
            $message->fullmessage = $fullmessage;
            $message->fullmessageformat = FORMAT_HTML;
            $message->fullmessagehtml = $fullmessage;
            $message->smallmessage = 'Yêu cầu duyệt tài liệu';
            $message->notification = 1;
            $message->contexturl = (new \moodle_url('/library.php'))->out(false);
            $message->contexturlname = 'Chi tiết';

            message_send($message);
        }
    }
    $message = new \core\message\message();
    $message->component = 'local_newsvnr';
    $message->name = 'requestfile';
    $message->userfrom = $USER->id;
    $message->userto = \core_user::get_support_user();
    $message->subject = '[VnR.Admin] Nhắc nhở duyệt tài liệu từ thư viện';
    $message->fullmessage = $fullmessage;
    $message->fullmessageformat = FORMAT_HTML;
    $message->fullmessagehtml = $fullmessage;
    $message->smallmessage = 'Yêu cầu duyệt tài liệu';
    $message->notification = 1;
    $message->contexturl = (new \moodle_url('/library.php'))->out(false);
    $message->contexturlname = 'Chi tiết';

    message_send($message);
}

// Gửi message và email từ chối duyệt tài liệu
function send_email_rejectedfile($moduleid) {
    global $CFG, $DB, $USER;
    $get_moduleinfo = $DB->get_record_sql('SELECT m.name, cm.instance, lm.userid FROM mdl_library_module lm
                                                LEFT JOIN mdl_course_modules cm ON lm.coursemoduleid = cm.id
                                                LEFT JOIN mdl_modules m ON cm.module = m.id 
                                            WHERE lm.coursemoduleid = :coursemoduleid', 
                                            ['coursemoduleid' => $moduleid]
                                        );

    $sql = "SELECT name FROM {" . $get_moduleinfo->name . "} WHERE id = :cmid";
    $moduleinfo = $DB->get_record_sql($sql, 
                                        ['cmid' => $get_moduleinfo->instance]
                                    );
    // User của người tạo file
    $userinfo = $DB->get_record('user', ['id' => $get_moduleinfo->userid]);

    $detailurl = $CFG->wwwroot . '/library.php';
    $fullmessage = '<p>Dear (Mr/Mrs): <strong>'.fullname($userinfo).'</strong></p>
                    <p>Yêu cầu duyệt tài liệu của Anh/Chị đã bị từ chối</p>
                    <p>Anh/Chị bấm vào link sau để xem chi tiết(<a href="'.$detailurl.'">Đường link</a>)</p>
                    <table style="border-collapse: collapse;width: 100%;">
                        <thead>
                            <tr>
                                <th scope="col" style="border: 1px solid #ddd;padding: 8px;text-align: left;background-color:#c5f3f3">STT</th>
                                <th scope="col" style="border: 1px solid #ddd;padding: 8px;text-align: left;background-color:#c5f3f3">Mã nhân viên</th>
                                <th scope="col" style="border: 1px solid #ddd;padding: 8px;text-align: left;background-color:#c5f3f3">Tên nhân viên</th>
                                <th scope="col" style="border: 1px solid #ddd;padding: 8px;text-align: left;background-color:#c5f3f3">Chức vụ</th>
                                <th scope="col" style="border: 1px solid #ddd;padding: 8px;text-align: left;background-color:#c5f3f3">Tên tài liệu</th>
                                <th scope="col" style="border: 1px solid #ddd;padding: 8px;text-align: left;background-color:#c5f3f3">Loại tài liệu</th>
                                <th scope="col" style="border: 1px solid #ddd;padding: 8px;text-align: left;background-color:#c5f3f3">Người duyệt</th>
                                <th scope="col" style="border: 1px solid #ddd;padding: 8px;text-align: left;background-color:#c5f3f3">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="border: 1px solid #ddd;padding: 8px;text-align: left;">1</td>
                                <td style="border: 1px solid #ddd;padding: 8px;text-align: left;">'.$userinfo->usercode.'</td>
                                <td style="border: 1px solid #ddd;padding: 8px;text-align: left;">'.fullname($userinfo).'</td>
                                <td style="border: 1px solid #ddd;padding: 8px;text-align: left;">'.$DB->get_field('orgstructure_position', 'name',['id' => $userinfo->orgpositionid]).'</td>
                                <td style="border: 1px solid #ddd;padding: 8px;text-align: left;">'.$moduleinfo->name.'</td>
                                <td style="border: 1px solid #ddd;padding: 8px;text-align: left;">'.ucwords($get_moduleinfo->name).'</td>
                                <td style="border: 1px solid #ddd;padding: 8px;text-align: left;">'.fullname($USER).'</td>
                                <td style="border: 1px solid #ddd;padding: 8px;text-align: left;">Từ chối</td>
                            </tr>
                        </tbody>
                    </table>
                    <br><strong>Thanks &amp; best regards,</strong><br>----------------------------------------<br><span><em><span style=""><span style="">Lưu ý</span></span>:</em>
                    </span> Thư này được gửi tự động từ hệ thống, vui lòng không reply. Để hỗ trợ thông tin, liên hệ bộ phân IT.<br>
                    <p></p>';
    $message = new \core\message\message();
    $message->component = 'local_newsvnr';
    $message->name = 'rejectedfile';
    $message->userfrom = \core_user::get_support_user();
    $message->userto = $get_moduleinfo->userid;
    $message->subject = '[VnR.Admin] Yêu cầu duyệt tài liệu đã bị từ chối';
    $message->fullmessage = $fullmessage;
    $message->fullmessageformat = FORMAT_HTML;
    $message->fullmessagehtml = $fullmessage;
    $message->smallmessage = 'Yêu cầu duyệt tài liệu đã bị từ chối';
    $message->notification = 1;
    $message->contexturl = (new \moodle_url('/library.php'))->out(false);
    $message->contexturlname = 'Chi tiết';

    message_send($message);
}

// Gửi message và email đồng ý duyệt tài liệu
function send_email_approvedfile($moduleid) {
    global $CFG, $DB, $USER;
    $get_moduleinfo = $DB->get_record_sql('SELECT m.name, cm.instance, lm.userid FROM mdl_library_module lm
                                                LEFT JOIN mdl_course_modules cm ON lm.coursemoduleid = cm.id
                                                LEFT JOIN mdl_modules m ON cm.module = m.id 
                                            WHERE lm.coursemoduleid = :coursemoduleid', 
                                            ['coursemoduleid' => $moduleid]
                                        );

    $sql = "SELECT name FROM {" . $get_moduleinfo->name . "} WHERE id = :cmid";
    $moduleinfo = $DB->get_record_sql($sql, 
                                        ['cmid' => $get_moduleinfo->instance]
                                    );
    // User của người tạo file
    $userinfo = $DB->get_record('user', ['id' => $get_moduleinfo->userid]);

    $detailurl = $CFG->wwwroot . '/library.php';
    $fullmessage = '<p>Dear (Mr/Mrs): <strong>'.fullname($userinfo).'</strong></p>
                    <p>Yêu cầu duyệt tài liệu của Anh/Chị đã được chấp nhận</p>
                    <p>Anh/Chị bấm vào link sau để xem chi tiết(<a href="'.$detailurl.'">Đường link</a>)</p>
                    <table style="border-collapse: collapse;width: 100%;">
                        <thead>
                            <tr>
                                <th scope="col" style="border: 1px solid #ddd;padding: 8px;text-align: left;background-color:#c5f3f3">STT</th>
                                <th scope="col" style="border: 1px solid #ddd;padding: 8px;text-align: left;background-color:#c5f3f3">Mã nhân viên</th>
                                <th scope="col" style="border: 1px solid #ddd;padding: 8px;text-align: left;background-color:#c5f3f3">Tên nhân viên</th>
                                <th scope="col" style="border: 1px solid #ddd;padding: 8px;text-align: left;background-color:#c5f3f3">Chức vụ</th>
                                <th scope="col" style="border: 1px solid #ddd;padding: 8px;text-align: left;background-color:#c5f3f3">Tên tài liệu</th>
                                <th scope="col" style="border: 1px solid #ddd;padding: 8px;text-align: left;background-color:#c5f3f3">Loại tài liệu</th>
                                <th scope="col" style="border: 1px solid #ddd;padding: 8px;text-align: left;background-color:#c5f3f3">Người duyệt</th>
                                <th scope="col" style="border: 1px solid #ddd;padding: 8px;text-align: left;background-color:#c5f3f3">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="border: 1px solid #ddd;padding: 8px;text-align: left;">1</td>
                                <td style="border: 1px solid #ddd;padding: 8px;text-align: left;">'.$userinfo->usercode.'</td>
                                <td style="border: 1px solid #ddd;padding: 8px;text-align: left;">'.fullname($userinfo).'</td>
                                <td style="border: 1px solid #ddd;padding: 8px;text-align: left;">'.$DB->get_field('orgstructure_position', 'name',['id' => $userinfo->orgpositionid]).'</td>
                                <td style="border: 1px solid #ddd;padding: 8px;text-align: left;">'.$moduleinfo->name.'</td>
                                <td style="border: 1px solid #ddd;padding: 8px;text-align: left;">'.ucwords($get_moduleinfo->name).'</td>
                                <td style="border: 1px solid #ddd;padding: 8px;text-align: left;">'.fullname($USER).'</td>
                                <td style="border: 1px solid #ddd;padding: 8px;text-align: left;">Chấp nhận</td>
                            </tr>
                        </tbody>
                    </table>
                    <br><strong>Thanks &amp; best regards,</strong><br>----------------------------------------<br><span><em><span style=""><span style="">Lưu ý</span></span>:</em>
                    </span> Thư này được gửi tự động từ hệ thống, vui lòng không reply. Để hỗ trợ thông tin, liên hệ bộ phân IT.<br>
                    <p></p>';
    $message = new \core\message\message();
    $message->component = 'local_newsvnr';
    $message->name = 'approvedfile';
    $message->userfrom = \core_user::get_support_user();
    $message->userto = $get_moduleinfo->userid;
    $message->subject = '[VnR.Admin] Yêu cầu duyệt tài liệu đã được chấp nhận';
    $message->fullmessage = $fullmessage;
    $message->fullmessageformat = FORMAT_HTML;
    $message->fullmessagehtml = $fullmessage;
    $message->smallmessage = 'Yêu cầu duyệt tài liệu đã được chấp nhận';
    $message->notification = 1;
    $message->contexturl = (new \moodle_url('/library.php'))->out(false);
    $message->contexturlname = 'Chi tiết';

    message_send($message);
}

function get_logo_news() {
    $theme_settings = new theme_settings();
    return $theme_settings->get_logo_news();
}

function get_forums_newestnews_data() {
    global $OUTPUT,$DB,$CFG,$USER;
    require_once($CFG->dirroot.'/local/newsvnr/lib.php');

    $arr = array();
    $sql = "SELECT p.subject, LEFT(p.message, 500) as message, d.name,d.id,d.forum,d.course,p.id as postid, p.modified, d.userid
            FROM {forum} as f
                LEFT JOIN  {forum_discussions} as d on f.id  = d.forum 
                INNER JOIN {forum_posts} as p on d.id = p.discussion
            WHERE f.type = ?
            ORDER BY d.timemodified DESC
            LIMIT 6
            ";  
    $data = $DB->get_records_sql($sql,array('news'));
    $templatecontext['sliderenabled'] = "1";
    foreach ($data as $key => $value) {        
        $arr[] = (array)$value;
    }
    for ($i = 1, $j = 0; $i <= count($data); $i++, $j++) {
        $key = 'key' . $i;
        $templatecontext['newestnews'][$j][$key] = true;
        $templatecontext['newestnews'][$j]['active'] = false;
        $fs = get_file_storage();
        $imagereturn = '';

        $post = $DB->get_record('forum_posts',['id' => $arr[$j]['postid']]);
        $cm = get_coursemodule_from_instance('forum', $arr[$j]['forum'], $arr[$j]['course'], false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $files = $fs->get_area_files($context->id, 'mod_forum', 'attachment', $post->id, "filename", false);
       
        if ($files) {
            foreach ($files as $file) {
                $filename = $file->get_filename();
                $mimetype = $file->get_mimetype();
                $iconimage = $OUTPUT->pix_icon(file_file_icon($file), get_mimetype_description($file), 'moodle', array('class' => 'icon'));
                $path = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$context->id.'/mod_forum/attachment/'.$post->id.'/'.$filename);
                $imagereturn .= $path;
            }
        }

        if(!$imagereturn) {
          $courseimage = $OUTPUT->get_generated_image_for_id($arr[$j]['postid']);
          $imagereturn = $courseimage;
        }

        $templatecontext['newestnews'][$j]['subject'] = $arr[$j]['subject'];
        $templatecontext['newestnews'][$j]['author'] = fullname($DB->get_record('user', ['id' => $arr[$j]['userid']]));
        $templatecontext['newestnews'][$j]['message'] = strip_tags($arr[$j]['message']);
        $templatecontext['newestnews'][$j]['name'] = $arr[$j]['name'];
        $templatecontext['newestnews'][$j]['image'] = $imagereturn;
        $templatecontext['newestnews'][$j]['timecreated'] = convertunixtime('l, d m Y',$arr[$j]['modified'],'Asia/Ho_Chi_Minh');
        $templatecontext['newestnews'][$j]['newsurl'] = $CFG->wwwroot."/news.php?id=".$arr[$j]['id'];
        if ($i === 1) {
            $templatecontext['newestnews'][$j]['active'] = true;
        }
    }
    return $templatecontext;
}

function get_forums_mostviews_data() {
    global $OUTPUT,$DB,$CFG,$USER;
    require_once($CFG->dirroot.'/local/newsvnr/lib.php');

    $arr = array();
    $sql = "SELECT p.subject, LEFT(p.message, 500) as message, d.name,d.id,d.forum,d.course,p.id as postid, p.modified, d.userid
            FROM {forum} as f
                LEFT JOIN  {forum_discussions} as d on f.id  = d.forum 
                INNER JOIN {forum_posts} as p on d.id = p.discussion
            WHERE f.type = ?
            ORDER BY d.countviews DESC
            LIMIT 3
            ";  
    $data = $DB->get_records_sql($sql,array('news'));
    $templatecontext['sliderenabled'] = "1";
    foreach ($data as $key => $value) {        
        $arr[] = (array)$value;
    }
    for ($i = 1, $j = 0; $i <= count($data); $i++, $j++) {
        $key = 'key' . $i;
        $templatecontext['mostviewsnews'][$j][$key] = true;
        $templatecontext['mostviewsnews'][$j]['active'] = false;
        $fs = get_file_storage();
        $imagereturn = '';

        $post = $DB->get_record('forum_posts',['id' => $arr[$j]['postid']]);
        $cm = get_coursemodule_from_instance('forum', $arr[$j]['forum'], $arr[$j]['course'], false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $files = $fs->get_area_files($context->id, 'mod_forum', 'attachment', $post->id, "filename", false);
       
        if ($files) {
            foreach ($files as $file) {
                $filename = $file->get_filename();
                $mimetype = $file->get_mimetype();
                $iconimage = $OUTPUT->pix_icon(file_file_icon($file), get_mimetype_description($file), 'moodle', array('class' => 'icon'));
                $path = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$context->id.'/mod_forum/attachment/'.$post->id.'/'.$filename);
                $imagereturn .= $path;
            }
        }

        if(!$imagereturn) {
          $courseimage = $OUTPUT->get_generated_image_for_id($arr[$j]['postid']);
          $imagereturn = $courseimage;
        }

        $templatecontext['mostviewsnews'][$j]['subject'] = $arr[$j]['subject'];
        $templatecontext['mostviewsnews'][$j]['author'] = fullname($DB->get_record('user', ['id' => $arr[$j]['userid']]));
        $templatecontext['mostviewsnews'][$j]['message'] = strip_tags($arr[$j]['message']);
        $templatecontext['mostviewsnews'][$j]['name'] = $arr[$j]['name'];
        $templatecontext['mostviewsnews'][$j]['image'] = $imagereturn;
        $templatecontext['mostviewsnews'][$j]['timecreated'] = convertunixtime('l, d m Y',$arr[$j]['modified'],'Asia/Ho_Chi_Minh');
        $templatecontext['mostviewsnews'][$j]['newsurl'] = $CFG->wwwroot."/news.php?id=".$arr[$j]['id'];
        if ($i === 1) {
            $templatecontext['mostviewsnews'][$j]['active'] = true;
        }
    }
    return $templatecontext;
}
function get_course_categories() {
    global $DB, $CFG;
    $sql = "SELECT * FROM {course_categories} WHERE parent = 0 ORDER BY timemodified DESC LIMIT 7";
    $data = $DB->get_records_sql($sql);
    $arr = array();
    foreach ($data as $key => $value) {        
        $arr[] = (array)$value;
    }
    for ($i = 1, $j = 0; $i <= count($data); $i++, $j++) {
        $templatecontext['coursecategories'][$j]['name'] = $arr[$j]['name'];
        $templatecontext['coursecategories'][$j]['url'] = $CFG->wwwroot . '/course/index.php?categoryid=' . $arr[$j]['id'];
    }
    return $templatecontext;
}