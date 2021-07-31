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
 
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/local/newsvnr/lib.php');
$id = optional_param('id',0,PARAM_INT);
$array_id = array('id' => $id);
$baseurl = new moodle_url('/index.php');
$url = new moodle_url('/news.php',$array_id);
if($id) {
	//Đếm views trang tin tức
	news_countviews($id);
}
$PAGE->set_url($url);
$title = get_string('pagetitle','local_newsvnr');
$PAGE->set_context(context_system::instance());
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->ignore_active();
$PAGE->navbar->add($title,$baseurl);
$PAGE->navbar->add('Chi tiết');

$discussion = $DB->get_record('forum_discussions', ['id' => $id]);

$data = [];
$data['coursedata'] = get_froums_coursenews_data_id($id);
$data['logonews'] = get_logo_news();
$data['hasnewestnews'] = get_forums_newestnews_data();
$data['hasmostviewsnews'] = get_forums_mostviews_data();
$data['hascoursecategories'] = get_course_categories();
$data['homeurl'] = $CFG->wwwroot;
$forum = $DB->get_record('forum', array('id' => $discussion->forum), '*', MUST_EXIST);
list($course, $cm) = get_course_and_cm_from_instance($forum, 'forum');

// Validate the module context. It checks everything that affects the module visibility (including groupings, etc..).
$modcontext = context_module::instance($cm->id);
$params = array(
    'context' => $modcontext,
    'objectid' => $id
);
$event = \mod_forum\event\discussion_viewed::create($params);
$event->add_record_snapshot('forum_discussions', $discussion);
$event->add_record_snapshot('forum', $forum);
$event->trigger();
echo $OUTPUT->header();

echo $OUTPUT->render_from_template('theme_moove/news_detail', $data);


echo $OUTPUT->footer();
