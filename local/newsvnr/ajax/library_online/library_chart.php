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

define('AJAX_SCRIPT', false);

require_once __DIR__ . '/../../../../config.php';
require_once $CFG->dirroot . '/local/newsvnr/lib.php';
$PAGE->set_context(context_system::instance());
require_login();

$data      = array();
$now = time();
$sevendaysago_unix = time() - (86400 * 7);
$sixdaysago_unix = time() - (86400 * 6);
$fivedaysago_unix = time() - (86400 * 5);
$fourndaysago_unix = time() - (86400 * 4);
$threedaysago_unix = time() - (86400 * 3);
$twodaysago_unix = time() - (86400 * 2);
$onedaysago_unix = time() - 86400;
$sevendaysago_date = convertunixtime('M Y, d', time() - (86400 * 6), 'Asia/Ho_Chi_Minh');
$sixdaysago_date = convertunixtime('M Y, d', time() - (86400 * 5), 'Asia/Ho_Chi_Minh');
$fivedaysago_date = convertunixtime('M Y, d', time() - (86400 * 4), 'Asia/Ho_Chi_Minh');
$fourdaysago_date = convertunixtime('M Y, d', time() - (86400 * 3), 'Asia/Ho_Chi_Minh');
$threedaysago_date = convertunixtime('M Y, d', time() - (86400 * 2), 'Asia/Ho_Chi_Minh');
$twodaysago_date = convertunixtime('M Y, d', time() - (86400 * 1), 'Asia/Ho_Chi_Minh');
$onedaysago_date = convertunixtime('M Y, d', time());

$i = 1;
$unixdate = 0;
$date;
$lastaccess = $DB->get_field_sql("SELECT MAX(timecreated) lastaccess
                                FROM mdl_logstore_standard_log
                                WHERE action = 'viewed' and target = 'library'
                                ");
$series = array();
$temp_series = array();
$categories = array();
for($i = 1; $i <=7; $i++) {
    if ($i == 1) {
        $unixdate = $onedaysago_unix;
        $befor_unixdate = $now;
        $date = $onedaysago_date;
    } elseif ($i == 2) {
        $unixdate = $twodaysago_unix;
        $befor_unixdate = $onedaysago_unix;
        $date = $twodaysago_date;
    } elseif ($i == 3) {
        $unixdate = $threedaysago_unix;
        $befor_unixdate = $twodaysago_unix;
        $date = $threedaysago_date;
    } elseif ($i == 4) {
        $unixdate = $fourndaysago_unix;
        $befor_unixdate = $threedaysago_unix;
        $date = $fourdaysago_date;
    } elseif ($i == 5) {
        $unixdate = $fivedaysago_unix;
        $befor_unixdate = $fourndaysago_unix;
        $date = $fivedaysago_date;
    } elseif ($i == 6) {
        $unixdate = $sixdaysago_unix;
        $befor_unixdate = $fivedaysago_unix;
        $date = $sixdaysago_date;
    } elseif ($i == 7) {
        $unixdate = $sevendaysago_unix;
        $befor_unixdate = $sixdaysago_unix;
        $date = $sevendaysago_date;
    }
    $wheresql = "WHERE target = 'library' AND action = 'viewed' AND (timecreated BETWEEN :unixdate AND :beforunixdate)";
    $sql =  "
    SELECT COUNT(id) log
    FROM mdl_logstore_standard_log
    $wheresql
    ";
    $get_log = $DB->get_field_sql($sql, ['beforunixdate' => $befor_unixdate, 'unixdate' => $unixdate]);
    
    $categories[] = $date;
    $series[] = (int)$get_log;
}
$response = new stdClass();
$response->series = array_reverse($series);
$response->categories = array_reverse($categories);
$response->lastaccess = '<span>'.get_string('lastaccesstime', 'theme_moove', convertunixtime('h:i A', $lastaccess), true).'</span>';
echo json_encode($response,JSON_UNESCAPED_UNICODE);
die;
