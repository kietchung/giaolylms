<?php

require_once __DIR__ . '/../../../config.php';

require_once __DIR__ . '/../lib.php';

global $DB, $USER;

$PAGE->set_context(context_system::instance());

if (isset($_POST['post_comment'])) {

    $content = $_POST['content_comment'];
    date_default_timezone_set('Asia/Ho_Chi_Minh');
    $createdAt = time();

    // print_r($createdAt());die
    $updatedAt = time();

    $discussionid = $_POST['discussionid'];

    $userid   = $USER->id;
    $fullname = $_POST['fullname'];

    $datauser   = $DB->get_record_sql('SELECT * FROM {user} u WHERE u.id = :userid ', ['userid' => $userid]);
    $useravatar = $OUTPUT->user_picture($datauser);
    //create object

    $data = new stdClass();

    $data->content      = $content;
    $data->createdAt    = $createdAt;
    $data->updatedAt    = $updatedAt;
    $data->discussionid = $discussionid;
    $data->userid       = $userid;

    $id_comment = $DB->insert_record('local_newsvnr_comments', $data);
    echo '
            <div class="row">
             <div class="col chat-panel" id="comment_' . $id_comment . '">

                         <div class="chat-image">
                           ' . $useravatar . '
                        </div>

                        <div class="chat-content">
                            <div class="chat-body">
                                <p><span class="name mr-2">' . $fullname . '</span>' . $content . '</p>
                            </div>


                            <div class="chat-footer">


                                   <div class="chat-footer d-flex">

                                        <label class="delete" onclick="DeleteComment(' . $id_comment . ')"  id="' . $id_comment . '" >' . get_string('delete') . '</label>

                                         <input type="hidden" id="delete_comment' . $id_comment . '"  value="delete">

                                          <label class="feedback" onclick="FeedBack(' . $id_comment . ')" id="' . $id_comment . '">' . get_string('feedback', 'local_newsvnr') . '</label>

                                        <label class="date-feedback ml-2"> ' . get_string('justnow', 'local_newsvnr') . '</label>



                                     </div>
                                    <div class="new-detail-reply-body form-reply" style="width: 80%; display:none;" >

                                        <form >
                                            <textarea class="new-detail-reply-content" name="content_reply" id="content_reply" placeholder="' . get_string('yourcomment', 'local_newsvnr') . '"></textarea>

                                            <input type="hidden" id="commentid" name="" value="' . $id_comment . '">
                                            <input type="hidden" id="userid" name="userid" value="' . $userid . '">
                                            <input type="hidden" id="fullname" value="' . $fullname . '" name="">

                                            <div class="new-detail-reply-control" >
                                                <button type="button" id="post_reply" name="post_reply" class="btn btn-submit ">' . get_string('sendcomment', 'local_newsvnr') . '</button>

                                            </div>
                                        </form>
                                    </div>

                                    <div class="clearfix"></div>

                                    <div class="list-reply' . $id_comment . '" id="list_reply' . $id_comment . '" style="display: none; overflow: hidden;">
                                        <!-- COMMENT IS HAVING REPLIES -->
                                        <!-- END COMMENT IS HAVING REPLIES -->
                                    </div>
                            </div>
                        </div>
                    </div>
         </div>  ';

}

if (isset($_GET['action']) && $_GET['action'] == "delete") {

    $id_comment = $_GET['id_comment'];

    $DB->delete_records('local_newsvnr_comments', array('id' => $id_comment));
    $DB->delete_records('local_newsvnr_replies', array('commentid' => $id_comment));

    $discussionid = $_GET['discussionid'];

    $currentPage = optional_param('page', 0, PARAM_INT);

    $get_append_comment = append_comments_after_delete($discussionid, 2, 1);

    // if (!empty($get_append_comment)) {
    //     $append_comment = '<div class="row">
    //          <div class="col chat-panel" id="comment_' . $get_append_comment->id . '">

    //                      <div class="chat-image">
    //                         <img class="rounded-circle" src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS6IMTq-efHer8sp1p23DxIw_NsFFUtc6ZI0vAexxMm0MPEsii-" />
    //                     </div>

    //                     <div class="chat-content">
    //                         <div class="chat-body">
    //                             <p><span class="name mr-2">' . $get_append_comment->fullname . '</span>' . $get_append_comment->content . '</p>
    //                         </div>
    //                         <div class="chat-footer">

    //                                <div class="chat-footer d-flex">
    //                                     <label class="delete" onclick="DeleteComment(' . $get_append_comment->id . ')"  id="' . $get_append_comment->id . '" >' . get_string('delete') . '</label>
    //                                      <input type="hidden" id="delete_comment' . $get_append_comment->id . '"  value="delete">
    //                                     <label class="feedback"  id="' . $get_append_comment->id . '">Phản hồi</label>
    //                                     <label class="date-feedback ml-2">' . convertunixtime(' d-m-Y H:i A', $get_append_comment->createdat, 'Asia/Ho_Chi_Minh') . '</label>

    //                                  </div>

    //                                 <!-- ACTION SHOW AND HIDE REPLIES -->

    //                                 <p style="display: none;" class="chat-hidden-reply" id="hidden-reply' . $get_append_comment->id . '"
    //                                  onclick="HiddenReplies(' . $get_append_comment->id . ')"><i class="fa fa-chevron-up"> ' . get_string('hidefeedback', 'local_newsvnr') . '</i></p>
    //                                 <p class="chat-show-reply" id="show-reply' . $get_append_comment->id . '"
    //                                 onclick="ShowReplies(' . $get_append_comment->id . ')" ><i class="fa fa-chevron-down" style="display:block"> ' . get_string('showfeedback', 'local_newsvnr') . '</i></p>
    //                                 <!-- ACTION END SHOW AND HIDE REPLIES -->
    //                         </div>

    //                     </div>
    //                 </div>
    //      </div>  ';

    //     echo $append_comment;
    // }

}
