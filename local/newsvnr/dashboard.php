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

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/my/lib.php');

redirect_if_major_upgrade_required();

// TODO Add sesskey check to edit
$edit   = optional_param('edit', null, PARAM_BOOL);    // Turn editing on and off
$reset  = optional_param('reset', null, PARAM_BOOL);
$view   = required_param('view', PARAM_RAW);

require_login();

$hassiteconfig = has_capability('moodle/site:config', context_system::instance());
if ($hassiteconfig && moodle_needs_upgrading()) {
    redirect(new moodle_url('/admin/index.php'));
}
$check = [];
$check[] = 'student';
$check[] = 'teacher';

if(!in_array($view, $check))
    throw new moodle_exception(get_string('invalidrequest', 'local_newsvnr'), 'local_newsvnr');
if($view == 'student') {
    $strmymoodle = get_string('studentdashboard', 'local_newsvnr');
}
elseif($view == 'teacher') {
    $strmymoodle = get_string('teacherdashboard', 'local_newsvnr');
}


if (isguestuser()) {  // Force them to see system default, no editing allowed
    // If guests are not allowed my moodle, send them to front page.
    if (empty($CFG->allowguestmymoodle)) {
        redirect(new moodle_url('/', array('redirect' => 0)));
    }

    $userid = null;
    $USER->editing = $edit = 0;  // Just in case
    $context = context_system::instance();
    $PAGE->set_blocks_editing_capability('moodle/my:configsyspages');  // unlikely :)
    $header = "$SITE->shortname: $strmymoodle (GUEST)";
    $pagetitle = $header;

} else {        // We are trying to view or edit our own My Moodle page
    $userid = $USER->id;  // Owner of the page
    $context = context_user::instance($USER->id);
    $PAGE->set_blocks_editing_capability('moodle/my:manageblocks');
    $header = fullname($USER);
    $pagetitle = $strmymoodle;
}

// Get the My Moodle page info.  Should always return something unless the database is broken.
if (!$currentpage = my_get_page($userid, MY_PAGE_PRIVATE)) {
    print_error('mymoodlesetup');
}

// Start setting up the page
$params = array();
if($view) {
    $params['view'] = $view; 
}
$PAGE->set_context($context);
$PAGE->set_url('/local/newsvnr/dashboard.php', $params);
$PAGE->set_pagelayout('mydashboard');
if($view == 'student') {
    $PAGE->set_pagetype('my-newsvnr-student');
}
elseif($view == 'teacher') {
    $PAGE->set_pagetype('my-newsvnr-teacher');
}
$PAGE->blocks->add_region('content');
$PAGE->set_subpage($currentpage->id);
$PAGE->set_title($pagetitle);
$PAGE->set_heading($header);
$PAGE->requires->js('/local/newsvnr/js/jquery-3.2.1.min.js', true);
$PAGE->requires->js('/local/newsvnr/js/dashboard.js', true);
if (!isguestuser()) {   // Skip default home page for guests
    if (get_home_page() != HOMEPAGE_MY) {
        if (optional_param('setdefaulthome', false, PARAM_BOOL)) {
            set_user_preference('user_home_page_preference', HOMEPAGE_MY);
        } else if (!empty($CFG->defaulthomepage) && $CFG->defaulthomepage == HOMEPAGE_USER) {
            $frontpagenode = $PAGE->settingsnav->add(get_string('frontpagesettings'), null, navigation_node::TYPE_SETTING, null);
            $frontpagenode->force_open();
            $frontpagenode->add(get_string('makethismyhome'), new moodle_url('/local/newsvnr/dashboard.php', array('setdefaulthome' => true, 'view' => $view)),
                    navigation_node::TYPE_SETTING);
        }
    }
}

// Toggle the editing state and switches
if (empty($CFG->forcedefaultmymoodle) && $PAGE->user_allowed_editing()) {
    if ($reset !== null) {
        if (!is_null($userid)) {
            require_sesskey();
            if (!$currentpage = my_reset_page($userid, MY_PAGE_PRIVATE)) {
                print_error('reseterror', 'my');
            }
            redirect(new moodle_url('/local/newsvnr/dashboard.php', $params));
        }
    } else if ($edit !== null) {             // Editing state was specified
        $USER->editing = $edit;       // Change editing state
    } else {                          // Editing state is in session
        if ($currentpage->userid) {   // It's a page we can edit, so load from session
            if (!empty($USER->editing)) {
                $edit = 1;
            } else {
                $edit = 0;
            }
        } else {
            // For the page to display properly with the user context header the page blocks need to
            // be copied over to the user context.
            if (!$currentpage = my_copy_page($USER->id, MY_PAGE_PRIVATE)) {
                print_error('mymoodlesetup');
            }
            $context = context_user::instance($USER->id);
            $PAGE->set_context($context);
            $PAGE->set_subpage($currentpage->id);
            // It's a system page and they are not allowed to edit system pages
            $USER->editing = $edit = 0;          // Disable editing completely, just to be safe
        }
    }

    // Add button for editing page
    $params = array('edit' => !$edit);

    $resetbutton = '';
    $resetstring = get_string('resetpage', 'my');
    $reseturl = new moodle_url("$CFG->wwwroot/local/newsvnr/dashboard.php", array('view' => $view, 'edit' => 1, 'reset' => 1, 'sesskey'=>sesskey()));
    $params['view'] = $view;
    $url = new moodle_url("$CFG->wwwroot/local/newsvnr/dashboard.php", $params);
    if (!$currentpage->userid) {
        // viewing a system page -- let the user customise it
        $editstring = '<a href="'.$url.'" class="text-icon-dashboard"><i class="fa fa-cog text-icon-dashboard" aria-hidden="true"></i>'.get_string('updatemymoodleon').'</a>';
    } else if (empty($edit)) {
        // $editstring = get_string('updatemymoodleon');
        $editstring = '<a href="'.$url.'" class="text-icon-dashboard"><i class="fa fa-cog text-icon-dashboard" aria-hidden="true"></i>'.get_string('updatemymoodleon').'</a>';
    } else {
        $editstring = '<a href="'.$url.'" class="text-icon-dashboard"><i class="fa fa-cog text-icon-dashboard" aria-hidden="true"></i>'.get_string('updatemymoodleoff').'</a>';
        // $resetbutton = $OUTPUT->single_button($reseturl, $resetstring);
        $resetbutton = '<a href="'.$reseturl.'" class="text-icon-dashboard"><i class="fa fa-cog text-icon-dashboard" aria-hidden="true"></i>'.$resetstring.'</a>';
        ;
    }
    if (!$currentpage->userid) {
        $params['edit'] = 1;
    }
    
    $button = $editstring;
    if($resetbutton !== '') 
        $PAGE->set_button($resetbutton .' | '. $button);
    else 
        $PAGE->set_button($button);
} else {
    $USER->editing = $edit = 0;
}

echo $OUTPUT->header();

echo $OUTPUT->custom_block_region('content');

echo $OUTPUT->footer();

// Trigger dashboard has been viewed event.
$eventparams = array('context' => $context);
$event = \core\event\dashboard_viewed::create($eventparams);
$event->trigger();
