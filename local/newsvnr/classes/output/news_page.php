<?php 
namespace local_newsvnr\output;

require_once("$CFG->dirroot/webservice/externallib.php");

use renderable;
use templatable;
use renderer_base;
use stdClass;
use context_module;
use DateTime;
use DateTimeZone;
use theme_moove\util\theme_settings;
class news_page implements renderable, templatable {

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB,$USER,$CFG;
        $theme_settings = new theme_settings();
        $searchquery  = optional_param('q', '', PARAM_RAW);
        $showalldata = optional_param('showall','', PARAM_RAW);
        $data = \core_webservice_external::get_site_info();
        $data['news'] = true;
        $data['showallurl'] = $CFG->wwwroot.'/local/newsvnr/index.php?showall=-1';
        $data['forumdata'] = self::get_forums_header_data();
        $data['forumnewestdata'] = self::get_forums_newest_data();
        $data['forumcoursenewsndata'] = self::get_froums_coursenews_data();
        $data['btnaddnews'] = $theme_settings->get_btn_add_news();
        if ($searchquery) {
            $data['news'] = false;
            $data['forumdatasearch'] = self::get_forums_search($searchquery);
        }
        if ($showalldata) {
            $data['showall'] = true;
            $data['news'] = false;
            $data['newestdatashowall'] = self::get_froums_coursenews_data();
            $data['list_news_of_course'] = get_list_course_have_forum_news();

        }
  
        // var_dump($data);die();
        return $data;
    }
    public static function get_forums_header_data() {
        global $DB,$CFG;
        $forumid = $DB->get_field_sql("SELECT id FROM mdl_forum WHERE course = :courseid", ['courseid' => 1]);
        if(empty($forumid)) {
            return;
        }
        $sql = "SELECT fd.id,fd.name,fd.timemodified,fn.contextid,fn.component,fn.filearea,fn.filepath,fn.itemid,fn.filename,CONCAT(u.firstname,' ',u.lastname) as username,fd.countviews
            from mdl_forum_discussions fd join mdl_files fn on fd.firstpost = fn.itemid join mdl_user u on fd.userid = u.id 
            where filesize > 0 and fd.forum = $forumid and fd.pinned=1 and fn.filearea='attachment'
            order by fd.timemodified desc";
        $forumimg = $DB->get_recordset_sql($sql);


        $contentimage = '';
        $forumarr = array();
        $i = 1;
        
        foreach ($forumimg as $file) {
            $key = 'key'.$i;
            $isimage = true;
            $forumstd = new stdClass();
            $imageurl = file_encode_url("$CFG->wwwroot/pluginfile.php",
                '/'. $file->contextid. '/'. $file->component. '/'.
                $file->filearea. $file->filepath.$file->itemid.'/'. $file->filename, !$isimage);
            $link = $CFG->wwwroot."/local/newsvnr/news.php?id=".$file->id;;
            $time = self::convertunixtime('m d, Y',$file->timemodified,'Asia/Ho_Chi_Minh');
            $forumstd->timeago = converttime($file->timemodified);
            $forumstd->newsurl = $link;
            $forumstd->title = $file->name;
            $forumstd->image = $imageurl;
            $forumstd->time = $time;
            $forumstd->countviews = $file->countviews;
            $forumstd->username = $file->username;
            $forumstd->$key = true;
            $forumarr[] = $forumstd;
            $i++;
        }
        return $forumarr;
    }
    public static function get_forums_newest_data(){
        global $DB,$CFG;
        $forumarr = array();
        $forumid = $DB->get_field_sql("SELECT TOP 1 id FROM mdl_forum", []);
        if($forumid) {
            $sql = "SELECT fd.id as disid, fd.countviews, fp.message,fp.subject,fd.timemodified, fn.*,CONCAT(us.firstname,' ',us.lastname) AS name
            from mdl_forum f 
            join mdl_forum_discussions fd on fd.forum = f.id
            join mdl_forum_posts fp on fd.id = fp.discussion 
            join mdl_files fn on fd.firstpost = fn.itemid 
            JOIN mdl_user us ON us.id = fp.userid
            where filesize > 0 and fd.forum = $forumid and fn.filearea='attachment' and f.type = N'news' order by fd.timemodified DESC
            ";
            $forumnewstdata = $DB->get_recordset_sql($sql);
            $contentimage = '';
            $i = 1;
            foreach ($forumnewstdata as $file) {

                $count_comment = get_count_comment_by_discussionid($file->disid);

                $key = 'key'.$i;
                $isimage = true;
                $forumstd = new stdClass();
                $imageurl = file_encode_url("$CFG->wwwroot/pluginfile.php",
                    '/'. $file->contextid. '/'. $file->component. '/'.
                    $file->filearea. $file->filepath.$file->itemid.'/'. $file->filename, !$isimage);
                $link = $CFG->wwwroot."/local/newsvnr/news.php?id=".$file->disid;;
                $time = convertunixtime('l, d m Y',$file->timemodified,'Asia/Ho_Chi_Minh');
                $forumstd->discussionid = $file->disid;
                $forumstd->newsurl = $link;
                $forumstd->title = $file->subject;
                $forumstd->content = strip_tags($file->message);
                $forumstd->image = $imageurl;
                $forumstd->time = $time;
                $forumstd->countviews = $file->countviews;
                $forumstd->timeago = converttime($file->timemodified);
                $forumstd->name = $file->name;
                if(!empty($count_comment))
                {                                 
                    $forumstd->countcomments = $count_comment->countcomments;            
                }
                else{
                    $forumstd->countcomments = 0;
                }

                $forumstd->$key = true;
                $forumarr[] = $forumstd;
                $i++;

            }
        }
        return $forumarr;
    }
    public static function get_froums_coursenews_data()
    {
        global $DB,$CFG;

        $check_show_all = "";

        if($check_show_all == "")
        {
            $sql = "SELECT p.id as postid, d.countviews, d.id as discussionid, f.course, p.message,p.subject,p.modified,fn.contextid,fn.component,fn.filearea,fn.filepath,fn.itemid,fn.filename,CONCAT(us.firstname,' ',us.lastname) as name
            from mdl_forum f join mdl_forum_discussions d on f.id=d.forum and f.course=d.course join mdl_forum_posts p on d.id = p.discussion join mdl_files fn on d.firstpost = fn.itemid join mdl_user us on us.id = p.userid
            where f.type='news' and fn.filesize>0
            and fn.filearea = 'attachment' AND p.parent = 0 AND f.course != 1
            order by p.id desc
            OFFSET 0 ROWS
            FETCH next 8 ROWS only;";
        }
        else{

            $sql = "SELECT p.id as postid, d.countviews, d.id as discussionid, f.course, p.message,p.subject,p.modified,fn.contextid,fn.component,fn.filearea,fn.filepath,fn.itemid,fn.filename,CONCAT(us.firstname,' ',us.lastname) as name
            from mdl_forum f join mdl_forum_discussions d on f.id=d.forum and f.course=d.course join mdl_forum_posts p on d.id = p.discussion join mdl_files fn on d.firstpost = fn.itemid join mdl_user us on us.id = p.userid
            where f.type='news' and fn.filesize>0 AND p.parent = 0
            and fn.filearea = 'attachment' AND f.course != 1
            order by p.id desc";
        }
    

        $forumdata = $DB->get_recordset_sql($sql);


        $contentimage = '';
        $forumarr = array();
        $i = 1;

        foreach ($forumdata as $file) {

            $count_comment = get_count_comment_by_discussionid($file->discussionid);

            $key = 'key'.$i;
            $isimage = true;
            $forumstd = new stdClass();

            $imageurl = file_encode_url("$CFG->wwwroot/pluginfile.php",
                '/'. $file->contextid. '/'. $file->component. '/'.
                $file->filearea. $file->filepath.$file->itemid.'/'. $file->filename, !$isimage);


            $forumstd->image = $imageurl;

            $time = convertunixtime('l, d m Y',$file->modified,'Asia/Ho_Chi_Minh');

            $link = $CFG->wwwroot."/local/newsvnr/news.php?id=".$file->discussionid;

            $forumstd->newsurl = $link;

            $forumstd->title = $file->subject;

            $forumstd->content = strip_tags($file->message);

            $forumstd->countviews = $file->countviews;

            $forumstd->timeago = converttime($file->modified);

            $forumstd->name = $file->name;
            if(!empty($count_comment))
            {                                 
                $forumstd->countcomments = $count_comment->countcomments;            
            }
            else{
                $forumstd->countcomments = 0;
            }
        
            $forumstd->time = $time;
            $forumstd->$key = true;
            $forumarr[] = $forumstd;
            $i++;
        }

           
        return $forumarr;  
    }

   public static function get_forums_search($searchquery)
    {
         global $DB,$CFG;
        $sql = "SELECT fp.*,fn.*, fd.id as discussionid, fd.countviews,CONCAT(us.firstname,' ',us.lastname) as name from mdl_forum ff 
        join mdl_forum_discussions fd on ff.id = fd.forum join mdl_forum_posts fp on fd.id = fp.discussion join mdl_files fn on fd.firstpost = fn.itemid join mdl_user us on us.id = fp.userid
        where fn.filesize>0 and ff.type='news' 
        and (fn.filearea = 'attachment') 
        and (fp.subject LIKE N'%$searchquery%'
        or fp.message LIKE N'%$searchquery%')";
        $data = array_values($DB->get_records_sql($sql));
        $contentimage = '';
        $forumarr = array();
        $forumarr['courseendable'] = "1";
        $forumarr['newsnumber'] = count($data);
        $forumarr['searchquery'] = $searchquery;

         for ($i = 1, $j = 0; $i <= count($data); $i++, $j++) 
        {       
            $count_comment = get_count_comment_by_discussionid($data[$j]->discussionid);

            $isimage = true;
            $imageurl = file_encode_url("$CFG->wwwroot/pluginfile.php",
                '/'. $data[$j]->contextid.'/'. $data[$j]->component. '/'.
                $data[$j]->filearea. $data[$j]->filepath.$data[$j]->itemid.'/'. $data[$j]->filename, !$isimage);
            $time = self::convertunixtime('l, d m Y',$data[$j]->modified,'Asia/Ho_Chi_Minh');

            $link = $CFG->wwwroot."/local/newsvnr/news.php?id=".$data[$j]->discussionid;

            $forumarr['forumsearchdata'][$j]['title'] = $data[$j]->subject;
            $forumarr['forumsearchdata'][$j]['content'] = strip_tags($data[$j]->message);
            $forumarr['forumsearchdata'][$j]['time'] = $time;
            $forumarr['forumsearchdata'][$j]['image'] = $imageurl;
            $forumarr['forumsearchdata'][$j]['countviews'] = $data[$j]->countviews;
            $forumarr['forumsearchdata'][$j]['name'] = $data[$j]->name;
            $forumarr['forumsearchdata'][$j]['timeago'] = converttime($data[$j]->modified);
            $forumarr['forumsearchdata'][$j]['newsurl'] = $link;

            if(!empty($count_comment))
            {                                 
                $forumarr['forumsearchdata'][$j]['countcomments'] = $count_comment->countcomments;            
            }
            else{
                $forumarr['forumsearchdata'][$j]['countcomments'] = 0;
            }


        }

        return $forumarr;  

    }

    public static function convertunixtime($format="r", $timestamp=false, $timezone=false)
    {
        $userTimezone = new DateTimeZone(!empty($timezone) ? $timezone : 'GMT');
        $gmtTimezone = new DateTimeZone('GMT');
        $myDateTime = new DateTime(($timestamp!=false?date("r",(int)$timestamp):date("r")), $gmtTimezone);
        $offset = $userTimezone->getOffset($myDateTime);
        return date($format, ($timestamp!=false?(int)$timestamp:$myDateTime->format('U')) + $offset);
    }
}
