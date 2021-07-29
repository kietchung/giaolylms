<?php

require_once("$CFG->libdir/externallib.php");

class local_newsvnr_external extends external_api {


    //loading orgposition from orgstructure and orgjobtitle
    public static function loadingorgposition_allowed_from_ajax() {
        return true;
    }

    public static function loadingorgposition_parameters() {
        return new external_function_parameters(
            array(
                'orgstructureid' => new external_value(PARAM_RAW, 'The orgstructure id'),
                'orgjobtitleid' => new external_value(PARAM_RAW, 'The orgjobtitle id'),
            )    
        );
    }

    public static function loadingorgposition($orgstructureid, $orgjobtitleid) {
        global $DB;
        //$params = self::validate_parameters(self::getExample_parameters(), array());
        $params = self::validate_parameters(self::loadingorgposition_parameters(), 
                array(
                        'orgstructureid' => $orgstructureid,
                        'orgjobtitleid'  => $orgjobtitleid
                    ));
        $list_orgjobtitleid =  (explode(",", $params['orgjobtitleid']));
        $str_orgstructureid = $params['orgstructureid'];
        $str_orgjobtitleid = implode(',', $list_orgjobtitleid);
        $sql = "SELECT * FROM {orgstructure_position} WHERE orgstructureid IN($str_orgstructureid) AND jobtitleid IN($str_orgjobtitleid)";
        $result = [];
        $data = $DB->get_records_sql($sql,[]);
        foreach ($data as $value) {
            $result[] = (object)['id' => $value->id, 'name' => $value->name];
        }
              
        return $result;
    }

    public static function loadingorgposition_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'The id of the orgposition'),
                    'name' => new external_value(PARAM_RAW, 'The name of the orgposition'),
                )
            )
        );
    }

    //loading couresetup from course categoryid
    public static function loadingcoursesetup_allowed_from_ajax() {
        return true;
    }

    public static function loadingcoursesetup_parameters() {
        return new external_function_parameters(
            array(
                'categoryid' => new external_value(PARAM_RAW, 'The orgstructure id'),
            )    
        );
    }

    public static function loadingcoursesetup($categoryid) {
        global $DB;
        $params = self::validate_parameters(self::loadingcoursesetup_parameters(), 
                array(
                        'categoryid' => $categoryid,
                    ));
        $sql = "SELECT * FROM {course_setup} WHERE category = ?";
        $result = [];
        $data = $DB->get_records_sql($sql,[$params['categoryid']]);
        foreach ($data as $value) {
            $result[] = (object)['id' => $value->id, 'name' => $value->fullname];
        }

        return $result;
    }

    public static function loadingcoursesetup_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'The id of the orgposition'),
                    'name' => new external_value(PARAM_RAW, 'The name of the orgposition'),
                )
            )
        );
    }
    //loading division from course categoryid
    public static function loadingdivision_allowed_from_ajax() {
        return true;
    }

    public static function loadingdivision_parameters() {
        return new external_function_parameters(
            array(
                'categoryid' => new external_value(PARAM_RAW, 'The categoryid division'),
            )    
        );
    }

    public static function loadingdivision($categoryid = null) {
        global $DB;
        $params = self::validate_parameters(self::loadingdivision_parameters(), 
                array(
                        'categoryid' => $categoryid,
                    ));
        if($categoryid) {
            $sql = "SELECT d.* FROM mdl_division d 
                                JOIN mdl_division_categories cd on cd.divisionid = d.id
                            WHERE cd.coursecategorysid = ?";
            $result = [];
            $data = $DB->get_records_sql($sql,[$params['categoryid']]);
        } else {
            $sql = "SELECT * FROM {division}";
            $result = [];
            $data = $DB->get_records_sql($sql);
        }
        foreach ($data as $value) {
            $result[] = (object)['id' => $value->id, 'name' => $value->name];
        }
        return $result;
    }

    public static function loadingdivision_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'The id of the division'),
                    'name' => new external_value(PARAM_RAW, 'The name of the division'),
                )
            )
        );
    }
    //orgcate modal;
    /**
     * Describes the parameters for submit_create_group_form webservice.
     * @return external_function_parameters
     */
    public static function submit_create_orgcate_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the course'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create group form, encoded as a json array')
            )
        );
    }
 
    /**
     * Submit the create group form.
     *
     * @param int $contextid The context id for the course.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new group id.
     */
    public static function submit_create_orgcate_form($contextid, $jsonformdata) {
        global $CFG, $USER,$SITE,$DB;
 
        require_once($CFG->dirroot . '/local/newsvnr/lib.php');
        require_once($CFG->dirroot . '/local/newsvnr/orgcate_form.php');
 
        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_create_orgcate_form_parameters(),
                                            ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);
 
        $context = context::instance_by_id($params['contextid'], MUST_EXIST);
 
        // We always must call validate_context in a webservice.
        self::validate_context($context);
        // require_capability('moodle/course:managegroups', $context);
 
        list($ignored, $course) = get_context_info_array($context->id);
        $serialiseddata = json_decode($params['jsonformdata']);
 
        $data = array();
        parse_str($serialiseddata, $data);
 
        $warnings = array();
 
        
        $orgcate = new stdClass();

 
        // The last param is the ajax submitted data.
        $mform = new orgcate_form(null, array('orgcate' => $orgcate), 'post', '', null, true, $data);
 
        $validateddata = $mform->get_data();
        if ($validateddata) {
            // Do the action.
             $orgcate->name = $validateddata->catename;
             $orgcate->code = $validateddata->catecode;
             $orgcate->description = $validateddata->orgcate_description;

       
             $groupid = insert_orgcategory($orgcate);
        } else {
            // Generate a warning.
            throw new moodle_exception('erroreditgroup', 'group');
        }

        return $groupid;
    }
 
    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_create_orgcate_form_returns() {
        return new external_value(PARAM_INT, 'group id');
    }
    //orgposition modal
    public static function submit_create_orgposition_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the course'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create group form, encoded as a json array')
            )
        );
    }
 
    /**
     * Submit the create group form.
     *
     * @param int $contextid The context id for the course.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new group id.
     */
    public static function submit_create_orgposition_form($contextid, $jsonformdata) {
        global $CFG, $USER,$SITE,$DB;
 
        require_once($CFG->dirroot . '/local/newsvnr/lib.php');
        require_once($CFG->dirroot . '/local/newsvnr/orgposition_form.php');
 
        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_create_orgposition_form_parameters(),
                                            ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);
 
        $context = context::instance_by_id($params['contextid'], MUST_EXIST);
 
        // We always must call validate_context in a webservice.
        self::validate_context($context);
        // require_capability('moodle/course:managegroups', $context);
 
        list($ignored, $course) = get_context_info_array($context->id);
        $serialiseddata = json_decode($params['jsonformdata']);
 
        $data = array();
        parse_str($serialiseddata, $data);
 
        $warnings = array();
 
        
        $orgposition = new stdClass();

 
        // The last param is the ajax submitted data.
        $mform = new orgposition_form(null, array('orgposition' => $orgposition), 'post', '', null, true, $data);
 
        $validateddata = $mform->get_data();
        if ($validateddata) {
            // Do the action.
            $getorgstructureid = $DB->get_field_sql('SELECT id FROM {orgstructure} WHERE name = ?',array($validateddata->orgstructureid)); 
            $orgposition->name = $validateddata->posname;
            $orgposition->code = $validateddata->poscode;
            $orgposition->level = $validateddata->level;
            $orgposition->namebylaw = $validateddata->position_namebylaw;
            $orgposition->jobtitleid = $validateddata->jobtitleid;
            $orgposition->orgstructureid = $getorgstructureid;
            $orgposition->description = $validateddata->orgposition_description;
            
            $groupid = insert_orgposition($orgposition);
        } else {
            // Generate a warning.
            throw new moodle_exception('erroreditgroup', 'group');
        }

        return $groupid;
    }
 
    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_create_orgposition_form_returns() {
        return new external_value(PARAM_INT, 'group id');
    }

    //orgjobtitle modal
    /**
     * Describes the parameters for submit_create_group_form webservice.
     * @return external_function_parameters
     */
    public static function submit_create_orgjobtitle_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the course'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create group form, encoded as a json array')
            )
        );
    }
 
    /**
     * Submit the create group form.
     *
     * @param int $contextid The context id for the course.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new group id.
     */
    public static function submit_create_orgjobtitle_form($contextid, $jsonformdata) {
        global $CFG, $USER,$SITE,$DB;
 
        require_once($CFG->dirroot . '/local/newsvnr/lib.php');
        require_once($CFG->dirroot . '/local/newsvnr/orgjobtitle_form.php');
 
        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_create_orgjobtitle_form_parameters(),
                                            ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);
 
        $context = context::instance_by_id($params['contextid'], MUST_EXIST);
 
        // We always must call validate_context in a webservice.
        self::validate_context($context);
        // require_capability('moodle/course:managegroups', $context);
 
        list($ignored, $course) = get_context_info_array($context->id);
        $serialiseddata = json_decode($params['jsonformdata']);
 
        $data = array();
        parse_str($serialiseddata, $data);
 
        $warnings = array();
 
        
        $orgjobtitle = new stdClass();

 
        // The last param is the ajax submitted data.
        $mform = new orgjobtitle_form(null, array('orgjobtitle' => $orgjobtitle), 'post', '', null, true, $data);
 
        $validateddata = $mform->get_data();
        if ($validateddata) {
            // Do the action.
            $orgjobtitle->name = $validateddata->jobtitlename;
            $orgjobtitle->code = $validateddata->jobtitlecode;
            $orgjobtitle->namebylaw = $validateddata->jobtitle_namebylaw;
            $orgjobtitle->jobtitleid = $validateddata->orgjobtitle_description;
       
            $groupid = insert_orgjobtitle($orgjobtitle);
        } else {
            // Generate a warning.
            throw new moodle_exception('erroreditgroup', 'group');
        }

        return $groupid;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_create_orgjobtitle_form_returns() {
        return new external_value(PARAM_INT, 'group id');
    }

    //orgstructure modal
    /**
     * Describes the parameters for submit_create_group_form webservice.
     * @return external_function_parameters
     */
    public static function submit_create_orgstructure_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the course'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create group form, encoded as a json array')
            )
        );
    }
 
    /**
     * Submit the create group form.
     *
     * @param int $contextid The context id for the course.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new group id.
     */
    public static function submit_create_orgstructure_form($contextid, $jsonformdata) {
        global $CFG, $USER,$SITE,$DB;
 
        require_once($CFG->dirroot . '/local/newsvnr/lib.php');
        require_once($CFG->dirroot . '/local/newsvnr/orgstructure_form.php');
        
        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_create_orgstructure_form_parameters(),
                                            ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);
 
        $context = context::instance_by_id($params['contextid'], MUST_EXIST);
 
        // We always must call validate_context in a webservice.
        self::validate_context($context);
        // require_capability('moodle/course:managegroups', $context);
 
        list($ignored, $course) = get_context_info_array($context->id);
        $serialiseddata = json_decode($params['jsonformdata']);
 
        $data = array();
        parse_str($serialiseddata, $data);
 
        $warnings = array();
        
        $orgstructure = new stdClass();

 
        // The last param is the ajax submitted data.
        $mform = new orgstructure_form(null, array('orgstructure' => $orgstructure), 'post', '', null, true, $data);
 
        $validateddata = $mform->get_data();
        if ($validateddata) {
            // Do the action.
            $getorgstructureid = $DB->get_field('orgstructure','id',array('name' => $validateddata->parentid));
            $orgstructure->name = $validateddata->orgname;
            $orgstructure->code = $validateddata->orgcode;
            $orgstructure->managerid = $validateddata->managerid;
            $orgstructure->orgstructuretypeid = $validateddata->orgstructuretypeid;
            $orgstructure->parentid = $getorgstructureid;
            $orgstructure->numbermargin = $validateddata->numbermargin;
            $orgstructure->numbercurrent = $validateddata->numbercurrent;
            $orgstructure->description = $validateddata->org_description;
            
            $groupid = insert_orgstructure($orgstructure);
        } else {
            // Generate a warning.
            throw new moodle_exception('erroreditgroup', 'group');
        }

        return $groupid;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_create_orgstructure_form_returns() {
        return new external_value(PARAM_INT, 'group id');
    }

    //orgstructure modal
    /**
     * Describes the parameters for submit_create_group_form webservice.
     * @return external_function_parameters
     */
    public static function submit_send_email_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the course'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the send email form, encoded as a json array')
            )
        );
    }
 
    /**
     * Submit the create group form.
     *
     * @param int $contextid The context id for the course.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new group id.
     */
    public static function submit_send_email_form($contextid, $jsonformdata) {
        global $CFG, $USER, $DB, $EMAILUSER, $EMAILCOURSE;
 
        require_once($CFG->dirroot . '/user/sendemail_form.php');
        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_send_email_form_parameters(),
                                            ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);
 
        $context = context::instance_by_id($params['contextid'], MUST_EXIST);
        // We always must call validate_context in a webservice.
        self::validate_context($context);
 
        list($ignored, $course) = get_context_info_array($context->id);
        $serialiseddata = json_decode($params['jsonformdata']);
        
        $data = array();
        parse_str($serialiseddata, $data);
        $EMAILCOURSE = $DB->get_record('course', ['id' => $data['courseid']]);
        $EMAILUSER = new stdClass();
        $get_users = explode(',', $data['users']);
        foreach($get_users as $user) {
            $EMAILUSER->id = (int)$user;
            $content = trim(format_text($data['content_editor']['text'], $data['content_editor']['format'], '', null));
            $emailuser = new stdClass();
            $emailuser->email = $DB->get_field('user', 'email', ['id' => $user]);
            $emailuser->id = -99;
            ob_start();
            $supportuser = \core_user::get_support_user();
            $success = email_to_user($emailuser, $supportuser, $data['subject'], $content);
            $smtplog = ob_get_contents();
            ob_end_clean();
        }

        return 'success';
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_send_email_form_returns() {
        return new external_value(PARAM_RAW, 'group id');
    }

    public static function submit_create_email_template_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the course'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the send email form, encoded as a json array')
            )
        );
    }
 
    /**
     * Submit the create group form.
     *
     * @param int $contextid The context id for the course.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new group id.
     */
    public static function submit_create_email_template_form($contextid, $jsonformdata) {
        global $CFG, $USER, $DB;
 
        require_once($CFG->dirroot . '/user/sendemail_form.php');
        
        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_create_email_template_form_parameters(),
                                            ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);
 
        $context = context::instance_by_id($params['contextid'], MUST_EXIST);
 
        // We always must call validate_context in a webservice.
        self::validate_context($context);
 
        list($ignored, $course) = get_context_info_array($context->id);
        $serialiseddata = json_decode($params['jsonformdata']);
 
        $data = array();

        parse_str($serialiseddata, $data);
 
        $warnings = array();
        $result = '';
        $email_template = new stdClass();

        // The last param is the ajax submitted data.
        $mform = new sendemail_form(null, array('email_template' => $email_template), 'post', '', null, true, $data);
        
        $getform = $mform->get_data();
        if ($getform) {
            $obj = new stdClass;
            $obj->id = $getform->id;
            $obj->subject = $getform->subject;
            $obj->subjectformat = '1';
            $obj->content = $getform->content_editor['text'];
            $obj->contentformat = $getform->content_editor['format'];
            $obj->timemodified = time();
            $obj->usermodified = $USER->id;
            $update = $DB->update_record('email_template', $obj);
            if($update) {
                $result = get_string('edit_success', 'local_newsvnr');
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('erroreditgroup', 'group');
        }
        return $result;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_create_email_template_form_returns() {
        return new external_value(PARAM_RAW, 'response info');
    }
    
}


