<?php
require_once __DIR__ . '/../../../config.php';

require_once __DIR__ . '/../lib.php';
global $DB;
$PAGE->set_context(context_system::instance());
if (isset($_POST['post_reply'])) {

    $content_reply = $_POST['content_reply'];

    $userid = $USER->id;

    $fullname = $_POST['fullname'];

    $commentid = $_POST['commentid'];
    date_default_timezone_set('Asia/Ho_Chi_Minh');
    $createdAt = time();

    $updatedAt  = time();
    $datauser   = $DB->get_record_sql('SELECT * FROM {user} u WHERE u.id = :userid ', ['userid' => $userid]);
    $useravatar = $OUTPUT->user_picture($datauser);

    $reply_obj            = new stdClass();
    $reply_obj->content   = $content_reply;
    $reply_obj->createdAt = $createdAt;
    $reply_obj->updatedAt = $updatedAt;
    $reply_obj->commentid = $commentid;
    $reply_obj->userid    = $userid;
    $id_reply             = $DB->insert_record('local_newsvnr_replies', $reply_obj);

    echo ' <div class="chat-reply" id="reply_' . $id_reply . '">
            <!-- Comment 1 -->
            <div class="col chat-panel">
                <div class="chat-image">
                    ' . $useravatar . '
                </div>
                <div class="chat-content">
                    <div class="chat-body">
                        <p> <span class="name mr-2">' . $fullname . '</span>' . $content_reply . '</p>
                    </div>
                    <div class="chat-footer d-flex">
                        <label class="delete_reply mr-2"
                        onclick="DeleteReply(' . $id_reply . ')" id="' . $id_reply . '">' . get_string('delete') . '</label>
                        <input type="hidden" id="delete_reply' . $id_reply . '" name="" value="delete" />
                        <label class="date-feedback"> ' . get_string('justnow', 'local_newsvnr') . '</label>
                    </div>
                </div>
            </div>

        </div>';

}

if (isset($_GET['action']) && $_GET['action'] == "delete") {
    $id_reply = $_GET['id_reply'];

    $DB->delete_records('local_newsvnr_replies', array('id' => $id_reply));
}
