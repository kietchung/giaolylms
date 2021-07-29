<?php 
namespace local_newsvnr\output;

require_once("$CFG->dirroot/webservice/externallib.php");
require_once('lib.php');

use renderable;
use templatable;
use renderer_base;
use stdClass;
use context_module;
use DateTime;
use DateTimeZone;

class orgcomp_position_page implements renderable, templatable {

	   public function export_for_template(renderer_base $output) {
        global $DB,$USER,$PAGE;

        $data = [];

        $data['user'] = $USER;

        $data = [
            'orgstructure'          => get_list_orgstructure(),
            'orgstructure_position' => get_list_position(),
            'orgcomp_framework'     => get_framework_competency(),
            'user'                  => $USER
        ];

        
        return $data;
    }
}