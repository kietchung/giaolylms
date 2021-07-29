<?php 
namespace local_newsvnr\output;

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;

/**
 * Renderer class for local hackfest.
 *
 * @package    local_hackfest
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Defer to template.
     *
     * @param index_page $page
     *
     * @return string html for the page
     */

    public function render_news_page(news_page $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_newsvnr/news', $data);
    }

    public function render_newsdetail_page(newsdetail_page $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_newsvnr/newsdetail', $data);
    }  
    public function render_forum_page(forum_page $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_newsvnr/forum', $data);
    }
    public function render_course_page(course_page $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_newsvnr/course', $data);
    }
    public function render_orgmain_page(orgmain_page $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_newsvnr/orgmain', $data);
    }
    public function render_orgcomp_position_page(orgcomp_position_page $page)
    {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_newsvnr/orgcomp_position', $data);
    }
    public function render_orgmanager_page(orgmanager_page $page)
    {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_newsvnr/orgmanager', $data);
    }
    public function renderer_api_managerment(api_managerment $page){
         $data = $page->export_for_template($this);

        return parent::render_from_template('local_newsvnr/api_managerment', $data);
    }
    public function render_generallibrary_page(generallibrary_page $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_newsvnr/generallibrary', $data);
    }
    public function render_user_report_page(user_report_page $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_newsvnr/user_report', $data);
    }
    public function render_competency_report_page(competency_report_page $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_newsvnr/competency_report', $data);
    }
    public function render_learning_report_page(learning_report_page $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_newsvnr/learning_report', $data);
    }
    public function render_trainingplan_report_page(trainingplan_report_page $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_newsvnr/trainingplan_report', $data);
    }
    public function render_exam_page(exam_page $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_newsvnr/exam', $data);
    }
    public function render_exam_view_page(exam_view_page $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_newsvnr/exam_view', $data);
    }
    public function render_division_page(division_page $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_newsvnr/division', $data);
    }
}
