<?php

require_once __DIR__ . '/../../../config.php';

require_once __DIR__ . '/../lib.php';

global $DB;

$PAGE->set_context(context_system::instance());

$discussionid = $_GET['discussionid'];

$currentPage = optional_param('page', 0, PARAM_INT);

$itemInPage = 5;

if ($currentPage < 1) {
    $currentPage = 1;
}
$from = ($currentPage - 1) * $itemInPage;

if ($from < 5) {
    $from = 0;
}

$get_comment = pagination_comment($discussionid, $from, $itemInPage);
$countcomment = count($DB->get_records_sql('SELECT * FROM mdl_local_newsvnr_comments WHERE discussionid = :discussionid ',['discussionid' => $discussionid]));
$calcomment = $countcomment-$itemInPage*($currentPage-1);
$xhtml       = "";
if (!empty($get_comment)) {

    foreach ($get_comment as $key => $comment) {
        // print_r(is_siteadmin());
        // print_r($comment->userid);print_r($USER->id);die();
        $datauser   = $DB->get_record_sql('SELECT * FROM {user} u WHERE u.id = :userid ', ['userid' => $comment->userid]);
        $useravatar = $OUTPUT->user_picture($datauser);
        $get_reply  = get_replies_from_comment($comment->id);

        if (is_siteadmin() || $comment->userid == $USER->id) {
            $deletecomment = '<label class="delete delete_comment" onclick="DeleteComment(' . $comment->id . ')" id="' . $comment->id . '">' . get_string('delete') . '</label>';
        } else { $deletecomment = '';}
        $html_reply = "";
        foreach ($get_reply as $key => $reply) {
            $userreply       = $DB->get_record_sql('SELECT * FROM {local_newsvnr_replies} r JOIN {user} u ON u.id = r.userid WHERE r.id = :id', ['id' => $reply->id]);
            $userreplyavatar = $OUTPUT->user_picture($userreply);

            if (is_siteadmin() || $userreply->userid == $USER->id) {
                $deletereply = '<label class="delete_reply mr-2" onclick="DeleteReply(' . $reply->id . ')" id="' . $reply->id . '">' . get_string('delete') . '</label>';
            } else { $deletereply = '';}

            if ($comment->id == $reply->commentid) {

                $html_reply .= '

	                            <div class="chat-reply" id="reply_' . $reply->id . '">
	                                <!-- Comment 1 -->
	                                <div class="col chat-panel">
	                                    <div class="chat-image">
	                                        ' . $userreplyavatar . '
	                                    </div>
	                                    <div class="chat-content">
	                                        <div class="chat-body">
	                                           <p> <span class="name mr-2">' . $reply->fullname . '</span>' . $reply->content . '</p>
	                                        </div>

	                                        <div class="chat-footer d-flex">
	                      						' . $deletereply . '
	                                             <input type="hidden" id="delete_reply' . $reply->id . '" name="" value="delete" />
	                                             <label class="date-feedback">' . converttime($reply->createdat) . '</label>

	                                        </div>
	                                    </div>
	                                </div>

	                            </div>';
            } else {
                break;
            }

        }

        $get_reply = get_replies_from_comment($comment->id);
        $xhtml .= '<div class="row">

			                <div class="col chat-panel" id="comment_' . $comment->id . '">
			                    <div class="chat-image">
			                        ' . $useravatar . '
			                    </div>

			                    <div class="chat-content">
			                        <div class="chat-body">
			                            <p><span class="name mr-2">' . $comment->fullname . '</span>' . $comment->content . '</p>

			                        </div>

			                          <div class="chat-footer">
			                             <div class="chat-footer d-flex">

			                                ' . $deletecomment . '
			                                <input type="hidden" id="delete_comment' . $comment->id . '"  value="delete">

			                                <label class="feedback" id="' . $comment->id . '" onclick="FeedBack(' . $comment->id . ')">' . get_string('feedback', 'local_newsvnr') . '</label>
			                            	<label class="date-feedback ml-2">' . converttime($comment->createdat) . '</label>

			                            </div>
			                            ';
        // <!-- ACTION SHOW AND HIDE REPLIES -->
        if (!empty($get_reply)) {
            $xhtml .= '
			                             <p class="chat-show-reply" id="show-reply' . $comment->id . '"
	                                    onclick="ShowReplies(' . $comment->id . ')" ><i class="fa fa-mail-forward"> ' . count($get_reply) . ' ' . get_string('answer', 'local_newsvnr') . '</i></p>';

        }
        $xhtml .= '                        <!-- ACTION END SHOW AND HIDE REPLIES -->
			                            <div class="new-detail-reply-body form-reply" style="width: 80%; display: none;">
			                            	<form>
				                                <textarea class="new-detail-reply-content" name="content_reply" id="content_reply" placeholder="' . get_string('yourcomment', 'local_newsvnr') . '"></textarea>

					                                <input type="hidden" id="commentid" name="" value="' . $comment->id . '">
					                                <input type="hidden" id="userid" name="userid" value="' . $comment->userid . '" />
					                                <input type="hidden" id="fullname" value="' . $comment->fullname . '" name="" />

				                                <div class="new-detail-reply-control">
				                                    <button type="button" id="post_reply" name="post_reply" class="btn btn-submit ">' . get_string('sendcomment', 'local_newsvnr') . '</button>

				                                </div>
				                             </form>
			                            </div>

			                            <div class="clearfix"></div>

			                            <div class="list-reply' . $comment->id . ' mt-1" id="list_reply' . $comment->id . '" style="display: none; overflow: hidden;" >
			                             	' . $html_reply . '
			                            </div>
			                        </div>

			                    </div>
			                </div>

			            </div>
					';
    }
    if($calcomment > 5) {
    $xhtml .= '<div class="new-detail-see-more">
				      <div class="new-detail-see-more-title">
				         <small class="new-detail-btn-see-more" id="see-more" onclick="loadComment('.$discussionid.')">'.get_string('viewmorecomment','local_newsvnr').'</small>
				      </div>
				 </div>';
	}
    echo $xhtml;
} else {
    echo "";
}
