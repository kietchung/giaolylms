<?php 
namespace local_newsvnr\output;

require_once("$CFG->dirroot/webservice/externallib.php");
require_once($CFG->dirroot.'/mod/forum/lib.php');
require_once('lib.php');

use renderable;
use templatable;
use renderer_base;
use stdClass;
use context_module;
use DateTime;
use DateTimeZone;
class forum_page implements renderable, templatable {

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB,$USER,$CFG;

        $data = array();
        $data['forumnewstdata'] = self::get_newest_fourm();
        $data['forumgeneraldata'] = self::get_type_forum('general');
        $data['forumblogdata'] = self::get_type_forum('blog');
        $data['forumsingledata'] = self::get_type_forum('single');
        $data['quandadata'] = self::get_type_forum('qanda');
        $data['eachuserdata'] = self::get_type_forum('eachuser');

        return $data;
    }
    public static function get_newest_fourm()
    {
        global $DB,$CFG,$USER;
        $sql = "SELECT TOP 10 ff.id as forumid,ff.name as chude,fd.name as baiviet, CONCAT(u.firstname,' ',u.lastname) as nguoitao,fd.id as discussid,cm.id as viewid,fd.userid as userid, fd.course as courseid
        from mdl_forum ff 
        join mdl_forum_discussions fd on ff.id = fd.forum 
        join mdl_forum_posts fp on fd.id = fp.discussion 
        join mdl_user u on fd.userid = u.id 
        join mdl_course_modules cm on fd.forum = cm.instance
        where fp.parent = 0 AND cm.module = 9 order by fp.created DESC";
        $forumdata = $DB->get_recordset_sql($sql);
        $forumarr = array();
        if(empty($forumdata))
        {
            $forumstd = new stdclass();
            $forumarr[] = $forumstd->empty = get_string('nodata', 'local_newsvnr');
        }
        foreach ($forumdata as $file) {

            $forumstd = new stdClass();
            $discussion = $DB->get_record('forum_discussions', array('id' => $file->discussid), '*', MUST_EXIST);
            $parent = $discussion->firstpost;
            $post = forum_get_post_full($parent);

            $course = $DB->get_record('course', array('id' => $discussion->course), '*', MUST_EXIST);
            $forum = $DB->get_record('forum', array('id' => $discussion->forum), '*', MUST_EXIST);
            $cm = get_coursemodule_from_instance('forum', $forum->id, $course->id, false, MUST_EXIST);
            $modcontext = context_module::instance($cm->id);

            if ((!is_guest($modcontext, $USER) && isloggedin()) && has_capability('mod/forum:viewdiscussion', $modcontext)) {
                if (\mod_forum\subscriptions::is_subscribable($forum)) {
                    $subcribe =  \html_writer::div(
                        forum_get_discussion_subscription_icon_newsvnr($forum, $post->discussion, null, true),
                        'discussionsubscription'
                    );
                    $subcribespan = forum_get_discussion_subscription_icon_preloaders();
                    $forumstd->subcribe = $subcribe;

                }
            }
            $discusslink = $CFG->wwwroot."/mod/forum/discuss.php?d=".$file->discussid;
            $userlink = $CFG->wwwroot."/user/view.php?id=".$file->userid."&course=".$file->courseid;
            $viewlink = $CFG->wwwroot."/mod/forum/view.php?id=".$file->viewid;
            $forumstd->disscussurl = \html_writer::link($discusslink,$file->baiviet);
            $forumstd->userurl = \html_writer::link($userlink,$file->nguoitao);    
            $forumstd->viewurl = \html_writer::link($viewlink,$file->chude);
            $forumarr[] = $forumstd;
        }


        return $forumarr;

    }
    public static function get_type_forum($typeforum)
    {
        global $DB,$CFG,$USER;

       
        if(is_siteadmin())
        {
            $query_admin = "SELECT cm.id as viewid, f.id as forumid, c.id as courseid, f.name as forumname, f.timemodified as ngaytao, f.type
            from mdl_course as c 
            join mdl_forum as f on f.course = c.id
            join mdl_course_modules cm on cm.instance = f.id
            where  f.type = ? AND cm.module = 9
            group by cm.id, f.id, c.id, c.fullname, f.type, f.name,f.timemodified";
            $forumdata = $DB->get_records_sql($query_admin,array($typeforum));

        }
        else{
               $query_user ="SELECT f.id as forumid, c.id as courseid, cm.id as viewid, f.name as forumname, f.timemodified as ngaytao, f.type, e.courseid, e.status, c.fullname
                    from mdl_forum f                    
                  join mdl_course c on f.course = c.id
                  join mdl_course_modules cm on cm.instance = f.id
                  join mdl_enrol e on e.courseid = c.id
                  join mdl_user_enrolments ue on ue.enrolid = e.id                        
                  where f.type = ? and ue.userid = ? AND cm.module = 9
                  GROUP BY f.id, c.id, cm.id, f.name, f.timemodified, f.type, e.courseid,c.fullname,e.status";

                $forum_of_user = $DB->get_records_sql($query_user, array($typeforum,$USER->id));

                $public_general_forum = self::get_forums_of_type_public($typeforum);

                $forumdata = array_values($public_general_forum + $forum_of_user);

        
        }


        $forumarr = array();
        foreach ($forumdata as $file) {

            $countpost  = self::get_count_post_by_forumid($file->forumid);

            $info_discuss = self::get_info_last_discussion($file->forumid);


            $forumstd = new stdClass();

            if(!empty($info_discuss))
            {

                $discussion = $DB->get_record('forum_discussions', array('id' => $info_discuss->discussid), '*', MUST_EXIST); 
                $parent = $discussion->firstpost;
                $post = forum_get_post_full($parent);
                $course = $DB->get_record('course', array('id' => $discussion->course), '*', MUST_EXIST);
                $forum = $DB->get_record('forum', array('id' => $discussion->forum), '*', MUST_EXIST);
                $cm = get_coursemodule_from_instance('forum', $forum->id, $course->id, false, MUST_EXIST);
                $modcontext = context_module::instance($cm->id);


                if ((!is_guest($modcontext, $USER) && isloggedin()) && has_capability('mod/forum:viewdiscussion', $modcontext)) {
                    if (\mod_forum\subscriptions::is_subscribable($forum)) {
                        $subcribe =  \html_writer::div(
                            forum_get_discussion_subscription_icon_newsvnr($forum, $post->discussion, null, true),
                            'discussionsubscription'
                        );
                        $subcribespan = forum_get_discussion_subscription_icon_preloaders();
                        $forumstd->subcribe = $subcribe;

                    }
                }
            }
            else{
                $info_discuss = new stdClass();
                $info_discuss->viewid = '';
                $info_discuss->discussid = '';
                $info_discuss->post_userid = '';
                $info_discuss->lastdisscus = '';
                $info_discuss->nguoitao = '';
            }


            $viewlink = $CFG->wwwroot."/mod/forum/view.php?id=".$file->viewid;
            $discusslink = $CFG->wwwroot."/mod/forum/discuss.php?d=".$info_discuss->discussid;
            $userlink = $CFG->wwwroot."/user/profile.php?id=".$info_discuss->post_userid;
            $timelink = convertunixtime('l, d m Y, H:i A',$file->ngaytao);
            $forumstd->viewurl = \html_writer::link($viewlink,$file->forumname);
            $forumstd->disscussurl = \html_writer::link($discusslink,$info_discuss->lastdisscus);
            $forumstd->userurl = \html_writer::link($userlink,$info_discuss->nguoitao);
            $forumstd->postnumber = $countpost->count;
            $forumstd->createtime = $timelink;
            $forumarr[] = $forumstd;

        }

        return $forumarr;

    }

   
    public static function get_count_post_by_forumid($forumid)
    {
        global $DB;
        $sql = "SELECT count(*) as count from mdl_forum_discussions  fd where  fd.forum = ?";

        $data = $DB->get_record_sql($sql, array($forumid));

        return $data;
    }


    public static function get_forums_of_type_public($typeforum)
    {
        global $DB;
            $sql = "SELECT f.id as forumid, c.id as courseid, f.name as forumname, f.timemodified as ngaytao, f.type,  e.status, c.fullname, cm.id as viewid
            from mdl_enrol  as e
            join mdl_course as c on c.id = e.courseid
            join mdl_forum as f on f.course = c.id
            join mdl_forum_discussions as fd on f.id = fd.forum
            JOIN mdl_course_modules cm on cm.instance = f.id
            where e.enrol='guest'and e.status= 0 and f.type = ? AND cm.module = 9
            group by f.id, c.id, f.name, f.timemodified, f.type, e.status, c.fullname, cm.id";
        $data = $DB->get_records_sql($sql,array($typeforum));

        return $data;
    }


    public static function get_info_last_discussion($forumid){
        global $DB;

        $sql = "SELECT TOP 1 fd.firstpost, cm.id as viewid, MAX(fd.course) as courseid, MAX(fd.id) as discussid, MAX(fd.name) as lastdisscus,
                 MAX(fd.userid) as post_userid, CONCAT(u.firstname,' ',u.lastname) as nguoitao
                from mdl_forum_discussions fd 
                JOIN mdl_course_modules cm on fd.forum = cm.instance
                join mdl_user u on u.id = fd.userid
                where fd.forum = ? AND cm.module = 9
                group by fd.firstpost, cm.id, fd.forum, u.firstname,u.lastname
                order by fd.firstpost DESC";
        $data = $DB->get_record_sql($sql, array($forumid));
        
        return $data;        

    }


}
