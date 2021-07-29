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
 * lấy dữ liệu cho chart trong dashboard student
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @package local_newsvnr
 * @copyright 2020 VnResource
 * @author   Le Thanh Vu
 **/

define('AJAX_SCRIPT', false);

require_once __DIR__ . '/../../../../config.php';
require_once $CFG->dirroot . '/local/newsvnr/lib.php';

require_login();

$key = optional_param('key', null, PARAM_RAW);
$action = optional_param('action',null,PARAM_RAW);
$itemadmin = $DB->get_record('config_plugins',['name' => 'administrationtab'],'id,value');
$object = new stdClass();
$object->id = $itemadmin->id;
if($action == 'hide') {
    if($itemadmin->value != '') {
        $object->value = $itemadmin->value.','.$key;     
    } else {
        $object->value = $key;
    }
} else {
    $listitem = explode(",",$itemadmin->value);
    foreach ($listitem as $k => $item) {
        if($item == $key) {
            unset($listitem[$k]);
        }
    }
    $object->value = implode(",",$listitem);
}
// $object->value = '';
$update = $DB->update_record('config_plugins',$object,['']);
echo "Success";

die;