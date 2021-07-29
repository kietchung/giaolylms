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

require_once(__DIR__ . '/../../config.php');
require_once('lib.php');

// require_login();

$PAGE->set_url('/local/newsvnr/ajax.php');
$PAGE->set_context(context_system::instance());

// Unlock session during potentially long curl request.
// \core\session\manager::write_close();


$table_name = '';	

if(isset($_GET['org_struct']) && $_GET['org_struct'] == 1)
{
	$table_name = 'mdl_orgstructure';
	$query = "SELECT id,name,parentid
			   FROM mdl_orgstructure
			   WHERE visible = 1
			  ";

	$treeview = $DB->get_records_sql($query);

	echo showMenuLi($treeview, $table_name);

}
else if(isset($_GET['org_competency']) && $_GET['org_competency'] == 1)
{	
	$frameid = isset($_GET['frameid']) ? $_GET['frameid'] : 0;

	$table_name = 'mdl_competency';
	$query = "SELECT id, shortname,parentid
			   FROM mdl_competency cmp
			   WHERE cmp.competencyframeworkid = ?
			  ";

	$treeview = $DB->get_records_sql($query, array($frameid));

	echo showMenuLi($treeview, $table_name);
} else if(isset($_GET['coursesetup']) && $_GET['coursesetup'] == 1) {
	$table_name = 'mdl_course_categories';
	$query = "SELECT id,name,parent
			   FROM mdl_course_categories
			   -- WHERE visible = 1
			  ";

	$treeview = $DB->get_records_sql($query);

	echo showMenuLi($treeview, $table_name);
}

if(isset($_GET['action']) && $_GET['action'] == "delete")
{
	$comp_postion_id =  $_GET['comp_postion_id'];
	$orgpositionid =  $_GET['orgpositionid'];
	$competencyid =  $_GET['competencyid'];

	$DB->delete_records('competency_coursepositioncomp', array('competencyid' => $competencyid,'orgpositionid' => $orgpositionid));
	$DB->delete_records('competency_position', array('id' => $comp_postion_id));

	echo "1";

}



if(isset($_GET['action']) && $_GET['action'] == "get_orgstruct_position")
{
	$org_struct = $_GET['org_struct'];

	$sql = "SELECT * FROM {orgstructure_position} WHERE orgstructureid = ?";

	$result = $DB->get_records_sql($sql, array($org_struct));

	$select = '<option class="active" id disabled="" selected="selected" value="">'. get_string('chooseorgposition', 'local_newsvnr') .'</option>';
	foreach ($result as $key => $value) {
		$select .= '<option value='. $value->id .'> '. $value->name .'</option>';
	}

	echo $select;
}
// Lấy danh sách khoá học dựa vào cây thư mục khoá học
if(isset($_GET['action']) && $_GET['action'] == "get_list_course") {
	$categoryid = $_GET['categoryid'];

	$sql = "SELECT * FROM {course} WHERE category = ?";

	$result = $DB->get_records_sql($sql, array($categoryid));

	$select = '';
	if($result) {
		$select .= '<option value="0">'. get_string('choosecourse', 'local_newsvnr') .'</option>';
		foreach ($result as $key => $value) {
			$select .= '<option value='. $value->id .'> '. $value->fullname .'</option>';
		}	
	} else {
		$select .= '<option value="0">'. get_string('nocourse', 'local_newsvnr') .'</option>';
	}
	

	echo $select;
}
//Lấy danh sách điểm xếp theo thứ hạng theo userid
if(isset($_GET['action']) && $_GET['action'] == "get_list_topgrade") {
	$courseid = $_GET['courseid'];
	$get_rank = '';
	$get_list_topgrade = $DB->get_records_sql("
							SELECT gg.userid,gi.courseid, CONVERT(DECIMAL(10,1),gg.finalgrade) AS gradefinal, RANK() OVER (ORDER BY gg.finalgrade DESC) AS rank, CONCAT(u.lastname,' ',u.firstname) AS fullname 
							FROM mdl_grade_grades gg join mdl_grade_items gi ON gi.id=gg.itemid JOIN mdl_user u ON gg.userid = u.id
							WHERE gg.finalgrade is not NULL AND gi.itemmodule IS NULL AND gi.courseid = ?
							ORDER BY gg.finalgrade DESC", [$courseid]);
	if($get_list_topgrade) {
		foreach($get_list_topgrade as $value) {
			if($value->userid == $USER->id) {
				$get_rank = $value->rank;
				$value->color = 'text-danger';
			}
		}
		$has_list_topgrade = true;
	} else {
		$has_list_topgrade = false;
		
	}
	$data = [
			'listtopgrade' => array_values($get_list_topgrade),
			'haslisttopgrade' => $has_list_topgrade,
			'rank' => (int)$get_rank,
		];
	echo json_encode($data, JSON_UNESCAPED_UNICODE);
}

// danh sách khoá theo theo cây thư mục khoá học
if(isset($_GET['action']) && $_GET['action'] == "get_list_coursesetup")
{
	$categoryid = $_GET['categoryid'];
	$strcousesetup = get_string('choices_coursesetup', 'local_newsvnr');
	$strnoitem = get_string('noitem_coursesetup', 'local_newsvnr');
	$sql = "SELECT * FROM {course_setup} WHERE category = ?";

	$result = $DB->get_records_sql($sql, array($categoryid));

	if($result) {
		$select = '<option class="active" id disabled="" selected="selected" value="">'.$strcousesetup.'</option>';
		foreach ($result as $value) {
			$select .= '<option value='. $value->id .'> '. $value->fullname .'</option>';
		}
	}
	else
		$select = '<option class="active" id disabled="" selected="selected" value="">'.$strnoitem.'</option>';
	

	echo $select;
}

//Lấy danh sách khoá học cho plan cá nhân block khoá học theo kế hoạch
if(isset($_GET['action']) && $_GET['action'] == "get_courseplan")
{
	$userplanid = $_GET['userplanid'];
	$theme_settings = new theme_moove\util\theme_settings();

	$listusercoursecomp = $theme_settings->get_courses_data(null,null,null, $userplancourse = 1, $userplanid );
	
    //Lấy danh sách khoá học bắt buộc với required = 1
    $data = 
    	array(
    		'listusercoursecomp' => $listusercoursecomp,
   
    	);
    
	echo json_encode($data, JSON_UNESCAPED_UNICODE);

}

//Lấy danh sách học viên xuất sắc nhất trong 1 khoá học
if(isset($_GET['action']) && $_GET['action'] == "get_topgrade")
{
	$courseid = $_GET['courseid'];
	
	$listuser = $DB->get_records_sql("select gg.userid,gi.courseid,gi.itemmodule, gg.finalgrade, CONCAT(u.lastname,' ',u.firstname) AS fullname from
mdl_grade_grades gg join mdl_grade_items gi on gi.id=gg.itemid JOIN mdl_user u ON gg.userid = u.id
where gg.finalgrade is not null and gi.itemtype= ? AND gi.courseid = ?", ['course', $courseid]);
    $data = 
    	array(
    		'listuser' => array_values($listuser),
    	);
	echo json_encode($data, JSON_UNESCAPED_UNICODE);
}

//Chức năng thêm năng lực cho vị trí (Lập kế hoạch)
if(isset($_POST['action']) && $_POST['action'] == "add")
{
	$comp_id = $_POST['comp_id'];
	$ordernumber = $_POST['ordernumber'];
	$orgstructure_position_id = $_POST['orgstructure_position'];
	$timecreate = time();
	$timemodified = time();

	$obj = new stdClass();

	$obj->competencyid = $comp_id;
	$obj->positionid = $orgstructure_position_id;
	$obj->ordernumber = $ordernumber;
	$obj->timecreate = $timecreate;
	$obj->timemodified = $timemodified;



	$checkExist = $DB->record_exists('competency_position', 
				array('competencyid' => $comp_id, 'positionid' => $orgstructure_position_id ));

	if($checkExist)
	{
		echo "0";
	}
	else{

		$id_record = $DB->insert_record('competency_position', $obj);

		$query = "SELECT  cp.id AS com_positionid, o.name AS orgstruct_name, cp.competencyid,
				  op.name org_position_name, cmp.shortname AS cmp_name, cf.shortname 
	              AS framework_name, op.id AS orgpositionid,cp.ordernumber
	              FROM mdl_competency cmp 
	              JOIN mdl_competency_framework cf on cmp.competencyframeworkid = cf.id 
				  JOIN mdl_competency_position  cp on cp.competencyid = cmp.id
				  JOIN mdl_orgstructure_position op on op.id = cp.positionid
				  JOIN mdl_orgstructure o on op.orgstructureid = o.id 
				  where cp.id = ?";

		$data = $DB->get_records_sql($query, array($id_record));
		
		$result = new stdClass();
		$result->html = '';
		$result->modal = '';


		foreach ($data as $key => $value) {
			$sql_get_parentid_comp = "SELECT parentid FROM {competency} WHERE id = ?";
			$data_get_parentid_comp = $DB->get_field_sql($sql_get_parentid_comp,[$value->competencyid]);
			if($data_get_parentid_comp == 0) {
				$sql_get_parentname_comp = "SELECT shortname FROM {competency} WHERE id = ?";
				$data_get_parentname_comp = $DB->get_field_sql($sql_get_parentname_comp,[$value->competencyid]);
			} else {
				$sql_get_parentname_comp = "SELECT shortname FROM {competency} WHERE id = ?";
				$data_get_parentname_comp = $DB->get_field_sql($sql_get_parentname_comp,[$data_get_parentid_comp]);
			}

			$result->html = '<tr>
    			<td>'. $value->ordernumber.'</td>
    			<td>
    				'. $value->cmp_name .'
    			</td>
    			<td>'. $data_get_parentname_comp .'</td>
    			<td>
    				'. $value->framework_name .'
    			</td>
    			<td>
    				<a data-toggle="modal" data-target="#myModal'. $value->competencyid .'" href="javascript:void(0)" onclick="rs_radiobtn()" id ="'. $value->competencyid .'">Danh sách khóa học</a>
    			</td>
    			<td class="center"><i onclick="DeleteCompPosition('. $value->com_positionid .','. $value->orgpositionid.','.$value->competencyid.')" class="fa fa-trash-o delete_comp" id="'. $value->com_positionid .'"></i></td>
    			</tr>';

    	    $sql_course_comp = "SELECT cc.courseid, cc.competencyid, cc.sortorder, c.fullname from mdl_competency_coursecomp cc JOIN mdl_course c on cc.courseid = c.id
			where cc.competencyid = ?";	

			$comp_course_data = $DB->get_records_sql($sql_course_comp, array($value->competencyid));
			
			$list_course = '';
			$strbtn = '';
		    if($comp_course_data) {

				foreach ($comp_course_data as $key => $comp_course) {
					$list_course .= '<input type="radio" name="list_course" value="'.$comp_course->courseid.'" class="mr-3"><a href="'.$CFG->wwwroot.'/course/view.php?id='.$comp_course->courseid.'">'. $comp_course->fullname .'</a></br>';

				}
		
				$strbtn = '<button type="submit" class="btn btn-default" onclick="maneger_enrol('.$value->orgpositionid.','.$value->competencyid.')">'. get_string('enrol', 'local_newsvnr') .'</button>
					         '; 
			} else {
				$list_course .= '<div class="d-flex justify-content-center alert alert-info alert-block fade in ">'. get_string('competencynolinked', 'local_newsvnr') .'</div>';
			}

		    $result->modal .= 
						'<form action="javascript:void(0)" method="POST">
							<div class="modal fade" id="myModal'. $value->competencyid .'" role="dialog">
					
							    <div class="modal-dialog modal-lg">
							      <div class="modal-content">
							        <div class="modal-header">
							        	<h5>'. get_string('listcourselink', 'local_newsvnr') .'</h5>
							        </div>
							        <div class="modal-body" style="display:block;">
											'. $list_course .'
							        </div>
							        <div class="modal-footer">
										'.$strbtn.'
										 <button type="button" class="btn btn-default" data-dismiss="modal">'. get_string('close', 'local_newsvnr') .'</button>
							        </div>
							      </div>
							    </div>
						 	</div>
						 </form>';
			

		}
		echo json_encode($result, JSON_UNESCAPED_UNICODE);  
	}

	
}
if(isset($_GET['action']) && $_GET['action'] == "load_comp_postion")
{
	$org_struct_position = $_GET['org_struct_position'];

	$sql_comp_position = "SELECT cp.id as com_positionid, o.name as orgstruct_name, cp.competencyid,
						  op.name org_position_name, cmp.shortname as cmp_name, cf.shortname 
			              as framework_name, op.id as orgpositionid,cp.ordernumber
			              from mdl_competency cmp 
			              join mdl_competency_framework cf on cmp.competencyframeworkid = cf.id 
						  join mdl_competency_position  cp on cp.competencyid = cmp.id
						  join mdl_orgstructure_position op on op.id = cp.positionid
						  join mdl_orgstructure o on op.orgstructureid = o.id 
						  where op.id  = ?
						";

	$comp_position_data = $DB->get_records_sql($sql_comp_position, array($org_struct_position));

	$html = "";
	$stt_comp_table = 0;

 	$result = new stdClass();
 	$result->comp_table = '<table class="display hover order-column table table-bordered table-striped nowrap" cellspacing="0" width="100%" id="orgcomp-position">
				   			 <thead class="thead">
					      <tr>
					       	<th>STT</th>
					        <th>'. get_string('standardcompetency', 'local_newsvnr') .'</th>
					        <th>'. get_string('parentcompetency', 'local_newsvnr') .'</th>
					        <th>'. get_string('competencyframework', 'local_newsvnr') .'</th>
					        <th>'. get_string('courselink', 'local_newsvnr') .'</th>
					        <th>'. get_string('action', 'local_newsvnr') .'Action</th>
					      </tr>
					    </thead><tbody>';
 	$result->modal = '';
 	$result->check_exist_position_template = '';

 	$check_exist_position_template = $DB->record_exists('competency_template', 
				array('positionid' => $org_struct_position ));


 	$result->check_exist_position_template = $check_exist_position_template;



 	$check_exist_comp_template = $DB->record_exists('competency_position', 
				array('positionid' => $org_struct_position ));


 	$result->check_exist_comp_template = $check_exist_comp_template;
 	 $strbtn = '';
	foreach ($comp_position_data as $key => $value) {
						
			$sql_course_comp = "SELECT cc.courseid, cc.competencyid, cc.sortorder, c.fullname from mdl_competency_coursecomp cc join mdl_course c on cc.courseid = c.id
				where cc.competencyid =  ?";
			$sql_get_parentid_comp = "SELECT parentid FROM {competency} WHERE id = ?";
			$data_get_parentid_comp = $DB->get_field_sql($sql_get_parentid_comp,[$value->competencyid]);
			if($data_get_parentid_comp == 0) {
				$sql_get_parentname_comp = "SELECT shortname FROM {competency} WHERE id = ?";
				$data_get_parentname_comp = $DB->get_field_sql($sql_get_parentname_comp,[$value->competencyid]);
			} else {
				$sql_get_parentname_comp = "SELECT shortname FROM {competency} WHERE id = ?";
				$data_get_parentname_comp = $DB->get_field_sql($sql_get_parentname_comp,[$data_get_parentid_comp]);
			}
			
			$comp_course_data = $DB->get_records_sql($sql_course_comp, array($value->competencyid));	
			
			// $stt_comp_table++;
			
			$result->comp_table  .='
		    		<tr>
		    			<td>'. $value->ordernumber.'</td>
		    			<td>
		    				'. $value->cmp_name .'
		    			</td>
		    			<td>'. $data_get_parentname_comp .'</td>
		    			<td>
		    				'. $value->framework_name .'
		    			</td>
		    			<td>
		    				<a data-toggle="modal" data-target="#myModal'. $value->competencyid .'" href="javascript:void(0)" onClick="rs_radiobtn()" id ="'. $value->competencyid .'">'. get_string('listcourse', 'local_newsvnr') .'</a>
		    			</td>
		    			<td class="center"><i onclick="DeleteCompPosition('. $value->com_positionid .','. $value->orgpositionid.','.$value->competencyid.')" class="fa fa-trash-o delete_comp" id="'. $value->com_positionid .'"></i></td>
		    		</tr>';
		  	$list_course = '';
		    if($comp_course_data) {

				foreach ($comp_course_data as $key => $comp_course) {
					$list_course .= '<input type="radio" name="list_course" value="'.$comp_course->courseid.'" class="mr-3"><a href="'.$CFG->wwwroot.'/course/view.php?id='.$comp_course->courseid.'">'. $comp_course->fullname .'</a></br>';

				}
		
				$strbtn = '<button type="submit" class="btn btn-default" onclick="maneger_enrol('.$value->orgpositionid.','.$value->competencyid.')">'. get_string('enrol', 'local_newsvnr') .'</button>'; 
			} else {
				$list_course .= '<div class="d-flex justify-content-center alert alert-info alert-block fade in ">'. get_string('competencynolinked', 'local_newsvnr') .'</div>';
				$strbtn = '';
			}

		    $result->modal .= 
						'<form action="javascript:void(0)" method="POST">
							<div class="modal fade" id="myModal'. $value->competencyid .'" role="dialog">
					
							    <div class="modal-dialog modal-lg">
							      <div class="modal-content">
							        <div class="modal-header">
							        	<h5>'. get_string('listcourselink', 'local_newsvnr') .'</h5>
							        </div>
							        <div class="modal-body" style="display:block;">
											'. $list_course .'
							        </div>
							        <div class="modal-footer">
										'.$strbtn.'
										 <button type="button" class="btn btn-default" data-dismiss="modal">'. get_string('close', 'local_newsvnr') .'</button>
							        </div>
							      </div>
							    </div>
						 	</div>
						 </form>';
	}
	$result->comp_table  .='</tbody></table>';
	$comp_plan_data = get_list_plan_template_by_positionid($org_struct_position);

	$stt_plan_table = 0;
	$result->plan_table = '<table class="display hover order-column table table-bordered table-striped nowrap" cellspacing="0" width="100%" id="orgcomp-plan">
				   			 <thead class="thead">
					      <tr>
					      	
					        <th>'. get_string('planname', 'local_newsvnr') .'</th>
					        <th>'. get_string('kindofplan', 'local_newsvnr') .'</th>
					        <th>'. get_string('studyplan', 'local_newsvnr') .'</th>
					        <th>'. get_string('position_name', 'local_newsvnr') .'</th>
					        <th>'. get_string('action', 'local_newsvnr') .'</th>
					      </tr>
					    </thead><tbody>';

	foreach ($comp_plan_data as $value) {
			
		// $stt_comp_table++;

		$result->plan_table .= '
							
							<tr>
			    			
			    			<td>
			    				'. $value->template_name .'
			    			</td>
			    			<td>
			    				'. $value->category_name .'
			    			</td>
			    			<td>
			    				'. $value->learn_plans .'
			    			</td>
			    			<td>
			    				'. $value->struct_name .'
			    			</td>

			    			<td class="center"><a href="javascript::void(0)" click="">'. get_string('edit', 'local_newsvnr') .'</a></td>
			    		</tr>';	

	}
	$result->plan_table .='</tbody></table>';

	echo json_encode($result,JSON_UNESCAPED_UNICODE);

}

if(isset($_GET['action']) && $_GET['action'] == "orgmanager_enrol") {

	$courseid = $_GET['courseid'];
	$orgpositionid = $_GET['orgpositionid'];
	$competencyid = $_GET['competencyid'];
	$resp = new stdClass();
	$orgcomp = new stdClass();
	$orgcomp->competencyid = $competencyid;
	$orgcomp->orgpositionid = $orgpositionid;
	$orgcomp->courseid = $courseid;
	$orgcomp->usermodified = $USER->id;
	$orgcomp->timemodified = time();
	$query = "SELECT id FROM {competency_coursepositioncomp} WHERE competencyid = ? and orgpositionid = ?";

	$check_orgcomp = $DB->get_record_sql($query,[$competencyid,$orgpositionid]);

	if($check_orgcomp) {
		$orgcomp->id = $check_orgcomp->id;
		$data = $DB->update_record('competency_coursepositioncomp',$orgcomp);
		$resp->success = get_string('updatesuccess', 'local_newsvnr');
	} else {
		$orgcomp->timecreated = time();
		$data = $DB->insert_record('competency_coursepositioncomp',$orgcomp);
		$resp->success = get_string('insertsuccess', 'local_newsvnr');
	}
	echo json_encode($resp,JSON_UNESCAPED_UNICODE);

}
if(isset($_POST["action"]) && $_POST["action"] == "save-planning")
{
	$name 		 = $_POST['name'];
	$descript 	 = $_POST['descript'];
	$visible	 = $_POST['visible'];
	$getDateTime = $_POST['getDateTime'];
	$orgstructure_position = $_POST['orgstructure_position'];


	$sql_comp_position = "SELECT * FROM {competency_position} WHERE positionid = ?";

	$comp_by_position = $DB->get_records_sql($sql_comp_position, array($orgstructure_position));


	$sql_user_position = "SELECT * FROM {user} WHERE orgpositionid = ?";

	$user_by_position = $DB->get_records_sql($sql_user_position, array($orgstructure_position));

	if(!empty($comp_by_position))
	{	

			// INSERT FOR COMPETENCY TEMPLATE
			$obj_template = new stdClass();
			$obj_template->shortname = $name;
			$obj_template->contextid = 1;
			$obj_template->description = $descript;
			$obj_template->descriptionformat = 1;
			$obj_template->visible = $visible;
			$obj_template->duedate = $getDateTime;
			$obj_template->timecreated = time();
			$obj_template->timemodified = time();
			$obj_template->usermodified = $USER->id;
			$obj_template->positionid = $orgstructure_position;

			$id_comp_template = $DB->insert_record('competency_template', $obj_template);


			foreach ($user_by_position as $key => $user) {

				// INSERT FOR COMPETENCY PLAN

				$obj_plan = new stdClass();
				$obj_plan->name = $name;
				$obj_plan->description = $descript;
				$obj_plan->descriptionformat = 1;
				$obj_plan->userid = $user->id;
				$obj_plan->templateid = $id_comp_template;
				$obj_plan->status = 0;
				$obj_plan->duedate = $getDateTime;
				$obj_plan->timecreated = time();
				$obj_plan->timemodified = time();
				$obj_plan->usermodified = $USER->id;
				
				$DB->insert_record('competency_plan', $obj_plan);
			}

			foreach ($comp_by_position as $key => $value) {

				$obj_temp_comp = new stdClass();
				$obj_temp_comp->templateid = $id_comp_template;
				$obj_temp_comp->competencyid = $value->competencyid;
				$obj_temp_comp->timecreated = time();
				$obj_temp_comp->timemodified = time();
				$obj_temp_comp->usermodified = $USER->id;

				$DB->insert_record('competency_templatecomp', $obj_temp_comp);
			}

			echo "1";

		
	}

	
}
//get_assignment cho block vnr_db_teacher_rm
if(isset($_GET['action']) && $_GET['action'] == "get_assignment_submit") {
	$resp = new stdClass();
	$courseid = $_GET['courseid'];
	$resp->list_assignment ='';
	
	$get_assignment_submit_ex = $DB->get_records_sql('SELECT distinct a.id,a.course,a.name,c.fullname,cm.id as vid
														from mdl_assign_grades ag  right join mdl_assign a on a.id = ag.assignment 
														left join mdl_course c on c.id = a.course  join mdl_course_modules cm on a.id = cm.instance
														where a.course = ? and ag.assignment is null ',[$courseid]);
    foreach ($get_assignment_submit_ex as $assign) {
        $assignlink = $CFG->wwwroot."/mod/assign/view.php?id=".$assign->vid."&action=grading";
        $assign->assignlink = \html_writer::link($assignlink,$assign->name);
        $resp->list_assignment .= '<div class="pl-1">'.$assign->assignlink.'</div>';                 
    }
	echo json_encode($resp,JSON_UNESCAPED_UNICODE);

}
//get_feedback cho block vnr_db_teacher_rm
if(isset($_GET['action']) && $_GET['action'] == "get_feedback") {
	$resp = new stdClass();
	$courseid = $_GET['courseid'];
	$resp->list_feedback = '';

	$get_feedback_ex = $DB->get_records_sql('SELECT f.id AS fid, f.name, cm.id AS vid 
												FROM mdl_course_modules cm 
												JOIN mdl_feedback f ON cm.instance = f.id AND cm.course = f.course 
												WHERE cm.course = ?',[$courseid]);
    foreach ($get_feedback_ex as $feedback) {
        $feedbacklink = $CFG->wwwroot."/mod/feedback/show_entries.php?id=".$feedback->vid;
        $feedback->feedbacklink = \html_writer::link($feedbacklink,$feedback->name);
        $resp->list_feedback .= '<div class="pl-1">'.$feedback->feedbacklink.'</div>';
    }
	echo json_encode($resp,JSON_UNESCAPED_UNICODE);

}

// test tich hop API
// table DB cua E-L la mdl_test

if(isset($_POST['action']) && $_POST['action'] == "test")
{	
	$usercode = $_POST['usercode'];
	$grade = $_POST['grade'];
	$result = $_POST['result'];
	$orgstruct_position = $_POST['orgstruct_position'];
	$quiz = $_POST['quiz'];
	$type = $_POST['type'];

	$timestart = $_POST['timestart'];


	$sql = "SELECT * FROM {test} t where t.usercode = ?";

	$checkExist = $DB->get_record_sql($sql, array($usercode));

	$obj = new stdClass();

	if(empty($checkExist))
	{	

		$obj->usercode = $usercode;
		$obj->grade = $grade;
		$obj->result = $result;
		$obj->orgstruct_position = $orgstruct_position;
		$obj->quiz = $quiz;
		$obj->type = $type;
		$obj->timestart = $timestart;

		$DB->insert_record('test',  $obj );
	}
	else{


		$obj->id = $checkExist->id;
		$obj->grade = $grade;
		$obj->result = $result;
		$obj->timestart = $timestart;

		$DB->update_record('test',  $obj );
	}

}
if(isset($_POST['action']) && $_POST['action'] == "api_created") {
	// setting api and send data
	// cho nay em chi cau hinh luu xuong DB.
	// anh muon send data thi dung  CURL cua PHP.

	$function_api = $_POST['function_api'];
	$method = $_POST['method'];
	$URL = $_POST['URL'];
	$description = $_POST['description'];

	$contenttype = isset($_POST['contenttype']) ? $_POST['contenttype'] : "";

	$clientArr = $_POST['clientArr'];
	$serverArr = $_POST['serverArr'];
	$defaultArr = $_POST['defaultArr'];

	$headerNameArr = $_POST['headerNameArr'];
	$headerValueArr = $_POST['headerValueArr'];

	$sortBody = array();

	for($i = 0; $i < count($clientArr); $i++)
	{
		for($j = 0; $j < count($serverArr); $j++)
		{
			if($i == $j)
			{
				$sortBody[] = array(
									'client_params' => $clientArr[$i]['value'],
									'server_params' => $serverArr[$j]['value'],
									'default_value' => $defaultArr[$j]['value']);
			}
		}
	}

	$sortHeader = array();
	for($i = 0; $i < count($headerNameArr); $i++)
	{
		for($j = 0; $j < count($headerValueArr); $j++)
		{
			if($i == $j)
			{
				$sortHeader[] = array(
							'name' => $headerNameArr[$i]['value'],
							'value' => $headerValueArr[$j]['value']);
			}
		}
	}

	$api = new stdClass();
	$api->url = $URL;
	$api->method = $method;
	$api->functionapi = $function_api;
	$api->contenttype = $contenttype;
	$api->description = $description;

	$api_id = $DB->insert_record('local_newsvnr_api', $api);	


	for($i = 0; $i < count($sortHeader); $i++)
	{
		$apiHeader = new stdClass();
		$apiHeader->name = $sortHeader[$i]['name'];
		$apiHeader->value = $sortHeader[$i]['value'];
		$apiHeader->api_id = $api_id;

		$DB->insert_record('local_newsvnr_api_header', $apiHeader);	
	}

	for($i = 0; $i < count($sortBody); $i++)
	{

		$apiDetail = new stdClass();

		$apiDetail->client_params = $sortBody[$i]['client_params'];

		$apiDetail->server_params = $sortBody[$i]['server_params'];

		$apiDetail->default_value = $sortBody[$i]['default_value'];

		$apiDetail->api_id = $api_id;

		$DB->insert_record('local_newsvnr_api_detail', $apiDetail);	
	}

	echo "Success";

}
die();


