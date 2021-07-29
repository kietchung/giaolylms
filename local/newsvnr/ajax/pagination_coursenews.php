<?php

global $DB, $CFG;	

require_once (__DIR__ . '/../../../config.php');

require_once (__DIR__ . '/../lib.php');

require_once (__DIR__ . '/../../../lib/filelib.php');

require_once("pagination.class.php");
	

$perPage = new PerPage();

$courseid =  optional_param('courseid', "" ,PARAM_INT);
$screen =  optional_param('screen', "" ,PARAM_TEXT);

if($courseid)
{
	$sql = "SELECT p.id as postid, d.countviews, d.id as discussionid, f.course, p.message,p.subject,CONCAT(us.firstname,' ',us.lastname) as name,
			p.modified,fn.contextid,fn.component,fn.filearea,fn.filepath,fn.itemid,fn.filename 
            from mdl_forum f join mdl_forum_discussions d on f.id=d.forum and f.course=d.course 
			join mdl_forum_posts p on d.id = p.discussion join mdl_files fn on d.firstpost = fn.itemid 
			JOIN mdl_user us on us.id = p.userid
            where f.type='news' and fn.filesize>0
            and fn.filearea = 'attachment'
			and f.course = ? and p.parent = 0 and f.course != 1
            order by p.id desc";
}
else{
	$sql = "SELECT p.id as postid, d.countviews, d.id as discussionid, f.course, p.message,p.subject,p.modified,fn.contextid,fn.component,fn.filearea,fn.filepath,fn.itemid,fn.filename,CONCAT(us.firstname,' ',us.lastname) as name
            from mdl_forum f join mdl_forum_discussions d on f.id=d.forum and f.course=d.course join mdl_forum_posts p on d.id = p.discussion join mdl_files fn on d.firstpost = fn.itemid JOIN mdl_user us on us.id = p.userid
            where f.type='news' and fn.filesize>0
            and fn.filearea = 'attachment' and p.parent = 0 and f.course != 1
            order by p.id desc";
}



				
$page = 1;

if(!empty($_GET["page"])) {
	$page = $_GET["page"];
}

$start = ($page-1)*$perPage->perpageCourseNews;

if($start < 0) 
	$start = 0;

$query =  $sql . " OFFSET " . $start . " ROWS FETCH next " . $perPage->perpageCourseNews . " ROWS only"; 



$paginationlink = $CFG->wwwroot . '/local/newsvnr/ajax/pagination_coursenews.php?page=';	

if($courseid)
{
	$faq = $DB->get_records_sql($query, array($courseid));
	$numrow = $DB->get_records_sql($sql, array($courseid));
	
}
else{
	$faq = $DB->get_records_sql($query);
	$numrow = $DB->get_records_sql($sql);

}
if(empty($_GET["rowcount"])) {

 $_GET["rowcount"] = count($numrow);

}


$perpageresult = $perPage->getAllCourseNewsPageLinks($_GET["rowcount"], $paginationlink);	


$output = '';

	foreach($faq as $value) {
			
			$count_comment = get_count_comment_by_discussionid($value->discussionid);

			$get_count_comment = !empty($count_comment) ? $count_comment->countcomments : 0;
			
            $isimage = true;

            $imageurl = file_encode_url("$CFG->wwwroot/pluginfile.php",
                '/'. $value->contextid. '/'. $value->component. '/'.
                $value->filearea. $value->filepath.$value->itemid.'/'. $value->filename, !$isimage);

            $time = converttime($value->modified);

            $link = $CFG->wwwroot."/local/newsvnr/news.php?id=".$value->discussionid;

            $pattern = "/<[^>]*><\\/[^>]*>/"; 

            $message = strip_tags($value->message); 

				$output .='
				<div class="col-xl-3 col-md-4 col-sm-6 col-xs-6" style="padding: 0">
					<div class="news-post">
					   <div class="new-latest-box position-relative">
					      <a href="'.$link.'"><img class="new-latest-image" src="'.$imageurl.'"></a>
					      <div class="position-absolute view-new">
					         <i class="fa fa-eye" aria-hidden="true">  '.$value->countviews.'</i>
					      </div>
					      <div class="position-absolute author">
					         <i class="fa fa-user mr-1" aria-hidden="true">  '.$value->name.'</i>
					      </div>
					      <div class="content-new">
					         <a href="'.$link.'"><label class="new-latest-title" title="'. $value->subject .'">'. $value->subject .'</label></a>
					         <div class="new-latest-content">
					            <p>'. $message .'</p>
					         </div>
					         <div class="position-absolute comment d-flex w-100 justify-content-between">
					            <div class="d-flex"><i class="fa fa-clock-o mr-1"></i><small class="text-date float-left">'.$time.'</small></div>
					            <div class="new-latest-comment-info mr-3">
					               <i class="fa fa-comment-o" aria-hidden="true"> '. $get_count_comment .' '.get_string('comment','local_newsvnr').'</i>
					            </div>
					         </div>
					      </div>
					   </div>
					</div>
				</div>';
	}

	
if(!empty($perpageresult) && $screen != 'news') {

	$output .= '<div class="col-md-12"> <div id="pagination">' . $perpageresult . '</div> </div>';
}

print $output;