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
namespace local_newsvnr\output;

require_once('../lib.php');
use renderable;
use templatable;
use renderer_base;
use stdClass;

class user_report_page implements renderable, templatable  {
    public function export_for_template(renderer_base $output) {
        global $CFG;
        $data = [];
        $data['confirm'] = $CFG->wwwroot . '/admin/user/user_bulk_confirm.php';
        $data['message'] = $CFG->wwwroot . '/admin/user/user_bulk_message.php';
        $data['display'] = $CFG->wwwroot . '/admin/user/user_bulk_display.php';
        $data['password'] = $CFG->wwwroot . '/admin/user/user_bulk_forcepasswordchange.php';
        $data['group'] = $CFG->wwwroot . '/admin/user/user_bulk_cohortadd.php';
        if($CFG->sitetype == MOODLE_BUSINESS)
            $data['isbusiness'] = true;
        return $data;
    }

}




