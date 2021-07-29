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

class orgmanager_page implements renderable, templatable {

	   public function export_for_template(renderer_base $output) {
        global $DB,$USER,$PAGE;
        $data = [
        ];
        return $data;
    }
}