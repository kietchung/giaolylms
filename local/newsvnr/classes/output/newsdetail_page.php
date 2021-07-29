<?php 
namespace local_newsvnr\output;

require_once("$CFG->dirroot/webservice/externallib.php");
require_once('lib.php');

use renderable;
use templatable;
use renderer_base;
use stdClass;
use context_module;
use DateTime;
use DateTimeZone;
use single_button;
use moodle_url;
use html_writer;

class newsdetail_page implements renderable, templatable {

    public function export_for_template(renderer_base $output) {
        global $DB,$USER,$PAGE;

        $data = [];
        $buttonedit = get_string('btneditnews', 'local_newsvnr');

        $discussionid = optional_param('id',0,PARAM_INT);

        $forumdatacoursenews = get_froums_coursenews_data_id($discussionid);

        $data['coursedata'] = $forumdatacoursenews;

        $course = get_course_id($discussionid);

        if($course)
        {
          $forumdatalqnews = get_forums_lq_data($course->course_id, $discussionid);
        }
        else{
          $forumdatalqnews = '';
        }

        $data['courselqdata'] = $forumdatalqnews;

        $discus_comment_data = get_comment_from_disccusion($discussionid);

        $data['commentdata'] = $discus_comment_data;
  
        $data['user'] = $USER;

        $btneditnews = '';

        if(is_siteadmin($USER->id) == 2) {
            $postid = $DB->get_field_sql('SELECT firstpost FROM {forum_discussions} WHERE id = ?', [$discussionid]);
            $posturl = new moodle_url('/mod/forum/post.php', ['edit' => $postid]);
            $btneditnews .= html_writer::tag('button', $buttonedit, array('class'=>'btn btn-primary', 'type' => 'button', 'formtarget' => '_blank', 'onClick' => "window.open('$posturl', '_blank');"));
        }

        $data['btneditnews'] = $btneditnews;

        return $data;
    }

    public static function get_btn_edit_news($discussionid) {
        global $OUTPUT,$USER, $DB;
        $data = [];
        $postid = $DB->get_field_sql('SELECT firstpost FROM {forum_discussions} WHERE id = ?', [$discussionid]);
        if(is_siteadmin($USER->id) == 2)
        {
            $buttonadd = get_string('btneditnews', 'local_newsvnr');
            $button = new single_button(new moodle_url('/mod/forum/post.php', ['edit' => $postid]), $buttonadd, 'get', ['target' => '_blank']);
            $button->class = 'singlebutton mt-1';
            $button->formid = 'editdiscussionform';
            $renderbtn = $OUTPUT->render($button);
           
            return $renderbtn;
        }
            
    }
}