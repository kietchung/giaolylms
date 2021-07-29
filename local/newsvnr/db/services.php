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
 * Local hackfest external services.
 *
 * @package    local_hackfest
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(
    'local_newsvnr_loadsettings' => array(
        'classname' => 'local_newsvnr_external',
        'methodname' => 'loadsettings',
        'classpath' => 'local/newsvnr/externallib.php',
        'description' => 'Load kendo sourcedata',
        'type' => 'read',
        'ajax' => true,
        'capabilities'=> '',
    ),
    'local_newsvnr_submit_create_orgcate_form' => array(
        'classname' => 'local_newsvnr_external',
        'methodname' => 'submit_create_orgcate_form',
        'classpath' => 'local/newsvnr/externallib.php',
        'description' => 'Add new orgcate',
        'type' => 'read',
        'ajax' => true,
        'capabilities'=> '',
    ),
    'local_newsvnr_submit_create_orgposition_form' => array(
        'classname' => 'local_newsvnr_external',
        'methodname' => 'submit_create_orgposition_form',
        'classpath' => 'local/newsvnr/externallib.php',
        'description' => 'Add new orgposition',
        'type' => 'read',
        'ajax' => true,
        'capabilities'=> '',
    ),
    'local_newsvnr_submit_create_orgjobtitle_form' => array(
        'classname' => 'local_newsvnr_external',
        'methodname' => 'submit_create_orgjobtitle_form',
        'classpath' => 'local/newsvnr/externallib.php',
        'description' => 'Add new orgjobtitle',
        'type' => 'read',
        'ajax' => true,
        'capabilities'=> '',
    ),
    'local_newsvnr_submit_create_orgstructure_form' => array(
        'classname' => 'local_newsvnr_external',
        'methodname' => 'submit_create_orgstructure_form',
        'classpath' => 'local/newsvnr/externallib.php',
        'description' => 'Add new orgstructure',
        'type' => 'read',
        'ajax' => true,
        'capabilities'=> '',
    ),
    'local_newsvnr_loadingorgposition' => array(
        'classname' => 'local_newsvnr_external',
        'methodname' => 'loadingorgposition',
        'classpath' => 'local/newsvnr/externallib.php',
        'description' => 'Get The orgposition with orgstructure and orgjobtitle',
        'type' => 'read',
        'ajax' => true,
        'capabilities'=> '',
    ),
    'local_newsvnr_loadingcoursesetup' => array(
        'classname' => 'local_newsvnr_external',
        'methodname' => 'loadingcoursesetup',
        'classpath' => 'local/newsvnr/externallib.php',
        'description' => 'Get The coursesetup from course category',
        'type' => 'read',
        'ajax' => true,
        'capabilities'=> '',
    ),
    'local_newsvnr_submit_create_email_template_form' => array(
        'classname' => 'local_newsvnr_external',
        'methodname' => 'submit_create_email_template_form',
        'classpath' => 'local/newsvnr/externallib.php',
        'description' => 'Create email template',
        'type' => 'read',
        'ajax' => true,
        'capabilities'=> '',
    ),
    'local_newsvnr_submit_send_email_form' => array(
        'classname' => 'local_newsvnr_external',
        'methodname' => 'submit_send_email_form',
        'classpath' => 'local/newsvnr/externallib.php',
        'description' => 'Send email',
        'type' => 'read',
        'ajax' => true,
        'capabilities'=> '',
    ),
    'local_newsvnr_loadingdivision' => array(
        'classname' => 'local_newsvnr_external',
        'methodname' => 'loadingdivision',
        'classpath' => 'local/newsvnr/externallib.php',
        'description' => 'Get The division',
        'type' => 'read',
        'ajax' => true,
        'capabilities'=> '',
    ),
);