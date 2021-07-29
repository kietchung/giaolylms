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

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');
use core_competency\course_competency;
use core_competency\api;
use core_competency\user_competency;
use core_competency\external\competency_exporter;
use core_competency\external\user_competency_exporter;
use core_user\external\user_summary_exporter;
require_login();
$section  = required_param('section',PARAM_ALPHANUMEXT);
$modalsection = optional_param('modalsection','',PARAM_RAW);
$orgstructureid = optional_param('orgstructureid',0,PARAM_INT);
$usercode = optional_param('usercode','',PARAM_RAW);
$pagesize = optional_param('pagesize',10, PARAM_INT);
$pagetake = optional_param('take',0, PARAM_INT);
$pageskip = optional_param('skip',0, PARAM_INT);
$q = optional_param('q','', PARAM_RAW);
$params = [];
if($section){
	$params['section'] = $section;
}
if($modalsection){
	$params['modalsection'] = $modalsection;
}
if($orgstructureid) {
	$params['orgstructureid'] = $orgstructureid;
}

$url = new moodle_url('/local/newsvnr/ajax/orgdata.php',$params);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());

$objdata = new stdclass();
$strdata = '';


switch ($section) {
	case 'orgcate_list':
	$odersql = "";
	$wheresql = "";
	if($q) {
		$wheresql = "WHERE name LIKE N'%$q%'";
	}
	if($pagetake == 0) {
		$ordersql = "RowNum";
	} else {
		$ordersql = "RowNum OFFSET $pageskip ROWS FETCH NEXT $pagetake ROWS only";
	}
	$sql = "
			SELECT *, (SELECT COUNT(id) FROM {orgstructure_category} $wheresql) AS total
			FROM (
			    SELECT *, ROW_NUMBER() OVER (ORDER BY id) AS RowNum
			    FROM {orgstructure_category}
			    $wheresql
			) AS Mydata
			ORDER BY $ordersql";
	$get_list = $DB->get_records_sql($sql);
	$data = [];
	foreach ($get_list as $value) {
		$buttons = array();
		if($value->visible == 1) {
			$buttons[] = html_writer::link('javascript:void(0)',
			$OUTPUT->pix_icon('t/hide', get_string('hide')),
			array('title' => get_string('hide'),'id' => $value->id, 'class' => 'hide-item','data-active' => 'orgcate_list','id' => $value->id,'onclick' => 'org_active('.$value->id.',0)'));	
		} else {
			$buttons[] = html_writer::link('javascript:void(0)',
			$OUTPUT->pix_icon('t/show', get_string('show')),
			array('title' => get_string('show'),'id' => $value->id, 'class' => 'show-item','data-active' => 'orgcate_list','id' => $value->id,'onclick' => 'org_active('.$value->id.',1)'));	
		}
		$buttons[] = html_writer::link('javascript:void(0)',
			$OUTPUT->pix_icon('t/edit', get_string('edit')),
			array('title' => get_string('edit'),'onclick' => 'org_edit_popup('.$value->id.')','data-toggle' => 'modal','data-target' => '#modal-iframe-org'));
		$buttons[] = html_writer::link('javascript:void(0)',
			$OUTPUT->pix_icon('t/delete', get_string('delete')),
			array('title' => get_string('delete'),'id' => $value->id, 'class' => 'delete-item','data-section' => 'orgcate','id' => $value->id,'onclick' => 'org_delete('.$value->id.')'));
		$showbuttons = implode(' ', $buttons);
		$object = new stdclass;
		$object->name = $value->name;		
		$object->code = $value->code;
		$object->description = $value->description;
		$object->listbtn = $showbuttons;
		$object->total = $value->total;
		$data[] = $object;		
	}
	echo json_encode($data,JSON_UNESCAPED_UNICODE);
	
	break;
	case 'orgjobtitle_list':
	$odersql = "";
	$wheresql = "";
	if($q) {
		$wheresql = "WHERE name LIKE N'%$q%'";
	}
	if($pagetake == 0) {
		$ordersql = "RowNum";
	} else {
		$ordersql = "RowNum OFFSET $pageskip ROWS FETCH NEXT $pagetake ROWS only";
	}
	$sql = "
			SELECT *, (SELECT COUNT(id) FROM {orgstructure_jobtitle} $wheresql) AS total
			FROM (
			    SELECT *, ROW_NUMBER() OVER (ORDER BY id) AS RowNum
			    FROM {orgstructure_jobtitle}
				$wheresql
			) AS Mydata
			ORDER BY $ordersql";
	$get_list = $DB->get_records_sql($sql);
	$data = [];
	foreach ($get_list as $value) {
		$buttons = array();
		if($value->visible == 1) {
			$buttons[] = html_writer::link('javascript:void(0)',
			$OUTPUT->pix_icon('t/hide', get_string('hide')),
			array('title' => get_string('hide'),'id' => $value->id, 'class' => 'hide-item','data-active' => 'orgjobtitle_list','id' => $value->id,'onclick' => 'org_active('.$value->id.',0)'));	
		} else {
			$buttons[] = html_writer::link('javascript:void(0)',
			$OUTPUT->pix_icon('t/show', get_string('show')),
			array('title' => get_string('show'),'id' => $value->id, 'class' => 'show-item','data-active' => 'orgjobtitle_list','id' => $value->id,'onclick' => 'org_active('.$value->id.',1)'));	
		}
		$buttons[] = html_writer::link(new moodle_url('/local/newsvnr/orgjobtitle.php',array('id' => $value->id)),
			$OUTPUT->pix_icon('t/edit', get_string('edit')),
			array('title' => get_string('edit')));
		$buttons[] = html_writer::link('javascript:void(0)',
			$OUTPUT->pix_icon('t/delete', get_string('delete')),
			array('title' => get_string('delete'),'id' => $value->id, 'class' => 'delete-item','data-section' => 'orgjobtitle','id' => $value->id,'onclick' => 'org_delete('.$value->id.')'));
		$showbuttons = implode(' ', $buttons);
		$object = new stdclass;
		$object->name = $value->name;		
		$object->code = $value->code;
		$object->namebylaw = $value->namebylaw;
		$object->description = $value->description;
		$object->listbtn = $showbuttons;
		$object->total = $value->total;
		$data[] = $object;		
	}
	echo json_encode($data,JSON_UNESCAPED_UNICODE);
	
	break;
	case 'orgposition_list':
	$odersql = "";
	$wheresql = "";
	if($q) {
		$wheresql = "WHERE name LIKE N'%$q%'";
	}
	if($pagetake == 0) {
		$ordersql = "RowNum";
	} else {
		$ordersql = "RowNum OFFSET $pageskip ROWS FETCH NEXT $pagetake ROWS only";
	}
	$sql = "
			SELECT *, (SELECT COUNT(id) FROM {orgstructure_position} $wheresql) AS total
			FROM (
			    SELECT *, ROW_NUMBER() OVER (ORDER BY id) AS RowNum
			    FROM {orgstructure_position}
				$wheresql
			) AS Mydata
			ORDER BY $ordersql";
	$get_list = $DB->get_records_sql($sql);
	$data = [];
	foreach ($get_list as $value) {
		$buttons = array();
		if($value->visible == 1) {
			$buttons[] = html_writer::link('javascript:void(0)',
			$OUTPUT->pix_icon('t/hide', get_string('hide')),
			array('title' => get_string('hide'),'id' => $value->id, 'class' => 'hide-item','data-active' => 'orgposition_list','id' => $value->id,'onclick' => 'org_active('.$value->id.',0)'));	
		} else {
			$buttons[] = html_writer::link('javascript:void(0)',
			$OUTPUT->pix_icon('t/show', get_string('show')),
			array('title' => get_string('show'),'id' => $value->id, 'class' => 'show-item dim','data-active' => 'orgposition_list','id' => $value->id,'onclick' => 'org_active('.$value->id.',1)'));	
		}
		$buttons[] = html_writer::link(new moodle_url('/local/newsvnr/orgposition.php',array('id' => $value->id)),
			$OUTPUT->pix_icon('t/edit', get_string('edit')),
			array('title' => get_string('edit')));
		$buttons[] = html_writer::link('javascript:void(0)',
			$OUTPUT->pix_icon('t/delete', get_string('delete')),
			array('title' => get_string('delete'),'id' => $value->id, 'class' => 'delete-item','data-section' => 'orgposition','id' => $value->id,'onclick' => 'org_delete('.$value->id.')'));
		$showbuttons = implode(' ', $buttons);
		if($value->jobtitleid) {
			$orgjobtitlename = get_name_orgjobtitleid($value->jobtitleid);
		} else {
			$orgjobtitlename = "";
		}
		if($value->orgstructureid) {
			$orgstructurename = get_name_orgstructureid($value->orgstructureid);
		} else {
			$orgstructurename = "";
		}
		$object = new stdclass;
		$object->name = $value->name;		
		$object->code = $value->code;
		$object->namebylaw = $value->namebylaw;
		$object->orgjobtitlename = $orgjobtitlename;
		$object->orgstructurename = $orgstructurename;
		$object->description = $value->description;
		$object->listbtn = $showbuttons;
		$object->total = $value->total;
		$object->level = $value->level;
		$data[] = $object;		
	}
	echo json_encode($data,JSON_UNESCAPED_UNICODE);
	
	break;
	case 'orgstructure_list':
	$odersql = "";
	$wheresql = "";
	if($q) {
		$wheresql = "WHERE name LIKE N'%$q%'";
	}
	if($pagetake == 0) {
		$ordersql = "RowNum";
	} else {
		$ordersql = "RowNum OFFSET $pageskip ROWS FETCH NEXT $pagetake ROWS only";
	}
	$sql = "
			SELECT *, (SELECT COUNT(id) FROM {orgstructure} $wheresql) AS total
			FROM (
			    SELECT *, ROW_NUMBER() OVER (ORDER BY id) AS RowNum
			    FROM {orgstructure}
				$wheresql
			) AS Mydata
			ORDER BY $ordersql";
	$get_list = $DB->get_records_sql($sql);
	$data = [];
	foreach ($get_list as $value) {
		$buttons = array();
		if($value->visible == 1) {
			$buttons[] = html_writer::link('javascript:void(0)',
			$OUTPUT->pix_icon('t/hide', get_string('hide')),
			array('title' => get_string('hide'),'id' => $value->id, 'class' => 'hide-item','data-active' => 'orgstructure_list','id' => $value->id,'onclick' => 'org_active('.$value->id.',0)'));	
		} else {
			$buttons[] = html_writer::link('javascript:void(0)',
			$OUTPUT->pix_icon('t/show', get_string('show')),
			array('title' => get_string('show'),'id' => $value->id, 'class' => 'show-item','data-active' => 'orgstructure_list','id' => $value->id,'onclick' => 'org_active('.$value->id.',1)'));	
		}
		$buttons[] = html_writer::link(new moodle_url('/local/newsvnr/orgstructure.php',array('id' => $value->id)),
			$OUTPUT->pix_icon('t/edit', get_string('edit')),
			array('title' => get_string('edit')));
		$buttons[] = html_writer::link('javascript:void(0)',
			$OUTPUT->pix_icon('t/delete', get_string('delete')),
			array('title' => get_string('delete'),'id' => $value->id, 'class' => 'delete-item','data-section' => 'orgstructure','id' => $value->id,'onclick' => 'org_delete('.$value->id.')'));
		$showbuttons = implode(' ', $buttons);
		$orgcatename = get_name_orgcateid($value->orgstructuretypeid);
		if($value->parentid > 0){
			$parentname = get_name_parentid($value->parentid);
		} else {
			$parentname = get_string('headoffice', 'local_newsvnr');
		}
		if($value->managerid == 0 ) {
			$username = "";
		} else {
			$username = get_name_userid($value->managerid);	
		}
		$object = new stdclass;
		$object->name = $value->name;		
		$object->code = $value->code;
		$object->orgcatename = $orgcatename;
		$object->managername = $username;
		$object->parentname = $parentname;
		$object->numbermargin = $value->numbermargin;
		$object->numbercurrent = $value->numbercurrent;
		$object->description = $value->description;
		$object->listbtn = $showbuttons;
		$object->total = $value->total;
		$data[] = $object;		
	}
	echo json_encode($data,JSON_UNESCAPED_UNICODE);
	
	break;
	case 'orgmain_list';
	require_once("$CFG->libdir/completionlib.php"); 
	$userarr = get_user_with_usercode($usercode);
	
	//lấy thông tin user theo PB-CD-CV để hiện thị chi tiết user
	$userstd = new stdclass();
	foreach ($userarr as $userinfo) {
		$userstd->userid = $userinfo->userid;
		$userstd->orgpositionid = $userinfo->positionid;
		$userstd->usercode = $userinfo->usercode;
		$userstd->username = $userinfo->uname;
		$userstd->oname = $userinfo->oname;
		$userstd->opname = $userinfo->opname;
	}
	switch ($modalsection) {
		case 'modalinfo':
			
		
			$strmodal = '';
			$strmodal .= '<div class="modal  fade" id="userdetail" role="dialog">
								<div class="modal-dialog modal-lg mw-100 modal-width">
									<div class="modal-content fs-14">
										<div class="modal-header">
											<h5 class="modal-title">'. get_string('studentdetail', 'local_newsvnr') .'</h5>
										</div>
									<div class="modal-body">
										<div data-region="userdetail-iframe" class="">';
			$strmodal .= ' <div class="container">
						    <div class="row mb-3">
						      <div class="col-md-2"><span class="userdt-span">'. get_string('username', 'local_newsvnr') .'</span></div>
						      <div class="col-md-4"><input type="text" class="form-control w-100 fs-14" placeholder="" id="username_inp" name="username_inp" value="'.$userstd->username.'" disabled></div>
						      <div class="col-md-2"><span class="userdt-span">'. get_string('usercode', 'local_newsvnr') .'</span></div>
						      <div class="col-md-4"><input type="text" class="form-control w-100 fs-14" placeholder="" id="usercode_inp" name="usercode_inp" value="'.$userstd->usercode.'" disabled></div>
						    </div>  
						     <div class="row mb-3">
						      <div class="col-md-2"><span class="userdt-span">'. get_string('dependentorgstructure', 'local_newsvnr') .'</span></div>
						      <div class="col-md-4"><input type="text" class="form-control fs-14" placeholder="" id="orgstructure_inp" name="orgstructure_inp" value="'.$userstd->oname.'" disabled></div>
						      <div class="col-md-2"><span class="userdt-span">'. get_string('orgposition', 'local_newsvnr') .'</span></div>
						      <div class="col-md-4"><input type="text" class="form-control fs-14" placeholder="" id="orgposition_inp" name="orgposition_inp" value="'.$userstd->opname.'" disabled></div>
						    </div>  
  						   </div>';
  			$strmodal .=' 						<div id="userdetailtable"></div>
											</div>
										</div>	
									
									<div class="modal-footer">
										<button type="button" class="btn btn-danger" data-dismiss="modal">'. get_string('close', 'local_newsvnr') .'</button>
									</div>
								</div>
							</div>
						</div>';
			$objdata->modal = $strmodal;
			echo json_encode($objdata,JSON_UNESCAPED_UNICODE);
			die;
			break;
		case 'gridinfo':
			//lấy danh sách tất cả năng lực theo vị trí
			$get_name_competency = get_name_competency_position($userstd->orgpositionid);
			$data = [];
			foreach ($get_name_competency as $value) {
				$griddata = new stdclass;
				$completecourse_evid = false;
				$completecourse_prog = false;
				$tcnlht = 0;
				$timecompleted = '';
				//lấy chứng chỉ bên ngoài cho năng lực nếu có
				$get_userevidencecompid = get_userevidencecompid($userstd->userid,$value->competencyid);
				$get_userevidence = [];
				$strevidence = 'Không có';
				if(!empty($get_userevidencecompid)) {
					$strevidence = '';
				    foreach ($get_userevidencecompid as $key => $uec) {
				       $get_userevidence[$key] = get_userevidence($uec,$value->competencyid);
				       $get_userevidence[$key]->link = $CFG->wwwroot."/admin/tool/lp/user_competency.php?id=".$get_userevidence[$key]->ucid;
				    }
			
				    if(!empty($get_userevidence)) {
					    foreach ($get_userevidence as $ue) {
					    
					    	$strevidence .= html_writer::link($ue->link,$ue->name,['target' => '_blank','style' => 'color:black']);
					    	if ($ue->status == 0 and $ue->proficiency == 0) {
					    		$completecourse_evid = true;
					    		$timecompleted = convertunixtime('l, d m Y',$ue->timemodified);
					    		$tcnlht += 1;
					    		$strevidence .= get_string('reviewed','local_newsvnr');
					    	} 
					    	else {
					    		$completecourse_evid = false;
					    		if ($ue->status == 1) {
						    		$strevidence .= get_string('waitreview','local_newsvnr');
						    	} elseif ($ue->status == 2) {
						    		$strevidence .= get_string('inprogressreview','local_newsvnr');
						    	} elseif ($ue->status == 0 and $ue->proficiency == 1) {
						    		$strevidence .= get_string('reviewed','local_newsvnr');
						    	} else {
						    		$strevidence .= get_string('notrequestreview','local_newsvnr');
						    	}
					    	}
					    	
					    	$strevidence .= '</br>';
					    }
					}
			    }

			

				//lấy danh danh sách khóa học theo năng lực dựa vào bảng lập kế hoạch
				if($value->competencyid) {
					$list_comp = $DB->get_record('competency_coursepositioncomp',['competencyid' => $value->competencyid, 'orgpositionid' => $userstd->orgpositionid]);
				}
				$list_comp_name = [];
				$progress = '';
				//Thêm tiến trình vào khóa học nếu user tham gia khóa học
				if(!empty($list_comp)){
					$list_courses = $DB->get_records_sql('SELECT * FROM {course} WHERE id = ?',[$list_comp->courseid]);
					foreach ($list_courses as $course) {
						//kiểm tra user đã tham gia khóa học chưa từ danh khóa học theo năng lực
						$check_enrolled = is_enrolled(context_course::instance($course->id),$userstd->userid);
						if ($check_enrolled == true) {
							$progress = round(\core_completion\progress::get_course_progress_percentage($course,$userstd->userid));
							$course->progress = floor($progress);
							$course->link = $CFG->wwwroot."/course/view.php?id=".$course->id;
							$course->colprogress = '<div class="d-flex participants-collum"><div class="progress course"><div class="progress-bar" role="progressbar" aria-valuenow="'.$progress.'" aria-valuemin="0" aria-valuemax="100" style="width:'.$progress.'%"></div></div><div>'.$progress.'%</div></div></div>';
							$list_comp_name[] = $course;
		
						} else {
							$course->progress = 0;
							$course->link = $CFG->wwwroot."/course/view.php?id=".$course->id;
							$course->colprogress = '<span>'.get_string('notenrol','local_newsvnr').'</span>';
							$list_comp_name[] = $course;
						}							
					}
				} 
				//Xuất thông tin cho grid gồm: Tên comp, khóa học liên kết, chứng chỉ, ngày hoàn thành, kết thúc
				foreach ($list_comp_name as $comp) {
					//UI tiến trình học
					$prog = $comp->progress;
					switch (true) {
						case $prog == 100:	
							$completecourse_prog = true;
							if($completecourse_evid == false) {
								$tcnlht += 1;
							}
							$timecompleted = convertunixtime('l, d m Y',$comp->startdate);
							$strhtml = '<div class="row align-items-center">
			                  <div class="col">
			                    <a href="'.$comp->link.'" class="d-block mb-0" target="_blank">'.$comp->fullname.'</a>
			                   
			                  </div>
			                </div>';		
								break;
							  // <div class="col-auto pr-0	">
			                  //   	 <div class="progress-circle progress-sm" data-progress="'.$prog.'"></div>
			                  // </div>
							case $prog >= 0 and $prog < 100:	
								$strhtml = '<div class="row align-items-center">
			                  <div class="col">
			                    <a href="'.$comp->link.'" class="d-block mb-0" target="_blank">'.$comp->fullname.'</a>
			                  </div>
			                </div>';
								break;
							case $prog < 0:	
								$strhtml .= '<div class="row align-items-center">
			                  <div class="col">
			                    <a href="'.$comp->link.'" class="d-block mb-0" target="_blank">'.$comp->fullname.'</a>
			                   
			                  </div>
			                </div>';

								break;
							
							default:
								
							break;
					}

				
				}
				if(empty($list_comp)){
					$strcourselink = '<div class="row align-items-center">
		                  <div class="col">
		                    '. get_string('nocoursefit', 'local_newsvnr') .'
	
		                  </div>
		                </div>';
		             $strevidences = '<span class="badge badge-pill badge-cornflowerblue text-black">'.$strevidence.'</span>';
		            $griddata->courselink = $strcourselink;
		            $griddata->evidence = $strevidences;

				} else {
					$strcourselink = '<div data-region="progress-circle">'.$strhtml.'</div>';
					$strevidences = '<span class="badge badge-pill badge-cornflowerblue text-black">'.$strevidence.'</span>';
					$griddata->courselink = $strcourselink;
		            $griddata->evidence = $strevidences;
				}
				if(!empty($timecompleted))
					$griddata->timecompleted = $timecompleted;
				else {
					$strtimecompleted = '<span class="badge badge-pill badge-secondary text-black">'. get_string('org_incomplete', 'local_newsvnr') .'</span>';
					$griddata->timecompleted = $strtimecompleted;
				}
				if($completecourse_prog == true || $completecourse_evid == true ) {
					$strcompleted = '<span class="badge badge-pill badge-success">'. get_string('org_completed', 'local_newsvnr') .'</span>';
					$griddata->completed = $strcompleted;
				}
				else {
					$strcompleted = '<span class="badge badge-pill badge-secondary text-black">'. get_string('org_incomplete', 'local_newsvnr') .'</span>';
					$griddata->completed = $strcompleted;
				}
				if(!empty($list_comp_name)) {
					foreach ($list_comp_name as $progress) {
						$griddata->courseprogress = $progress->colprogress;
					}
				} else {
					$griddata->courseprogress = "<span>None</span>";
				}
				$griddata->competency = $value->shortname;
				
				$data[] = $griddata;	
			}

			echo json_encode($data,JSON_UNESCAPED_UNICODE);

			
			die;
			break;
		
		default:
			// code...
			break;
	}

	$get_list = get_listusers_orgstructure($orgstructureid,$pageskip,$pagetake,$q);
	//đếm tiêu chuẩn năng lực
	foreach ($get_list as $value) {
		$get_tcnl_position = get_tcnl($value->positionid)[0]->tcnlcd;
		$get_list_competency = get_competency_position($value->positionid);
		
		$get_name_competency = get_name_competency_position($value->positionid);
		$tcnlht = 0;

		foreach ($get_name_competency as $np) {
			$completecourse_evid = false;
			$completecourse_prog = false;
			$get_userevidencecompid = get_userevidencecompid($value->userid,$np->competencyid);
			$get_userevidence = [];
			$strevidence = '';
			if(!empty($get_userevidencecompid)) {
				
			    foreach ($get_userevidencecompid as $key => $uec) {
			       $get_userevidence[$key] = get_userevidence($uec,$np->competencyid);
			    }

			    if(!empty($get_userevidence)) {
				    foreach ($get_userevidence as $ue) {
				
				    	if ($ue->status == 0 and $ue->proficiency == 0) {
				    		$completecourse_evid = true;
				    		$tcnlht++;
				    			
				    	}
				    	else {
				    		$completecourse_evid = false;
				    	}
				    }

				}
		    }

		
			if($np->competencyid) {
				$list_comp = $DB->get_record('competency_coursepositioncomp',['competencyid' => $np->competencyid, 'orgpositionid' => $value->positionid]);
			}
		
			$list_comp_name = [];
			$progress = '';
			if(!empty($list_comp)){
				$list_courses = $DB->get_records_sql('SELECT * FROM {course} WHERE id = ?',[$list_comp->courseid]);
				foreach ($list_courses as $lc) {
					$check_enrolled = is_enrolled(context_course::instance($lc->id),$value->userid);
					if ($check_enrolled == true) {
						$progress = \core_completion\progress::get_course_progress_percentage($lc,$value->userid);
						$lc->progress = floor($progress);
						
						$list_comp_name[] = $lc;
					}else {
						$lc->progress = -1;

						$list_comp_name[] = $lc;
					}
						
				}
			}
		
			foreach ($list_comp_name as $cn) {
				$prog = $cn->progress;
				switch (true) {
					case $prog == 100:
						$completecourse_prog = true;	
						if($completecourse_evid == false) {
							$tcnlht++;
						}
					
						break;
					
					default:
						
						break;
				}
			}

		}

		$tcnlcht = $get_tcnl_position - $tcnlht;

		$userdata = new stdclass;
		$userdata->usercode = $value->usercode;		
		$userdata->uname = $value->uname;
		$userdata->oname = $value->oname;
		$userdata->opname = $value->opname;
		$userdata->positioncomp = $get_tcnl_position;
		$userdata->positioncomp_number = $tcnlcht;
		$userdata->total = $value->total;
		$data[] = $userdata;		
		
	}
	if(isset($data))
		$objdata->userdata = $data;
	else {
		$objdata->userdata = [];
	}
	//chi tiết phòng ban
	$orgdetail = get_detail_orgstructure($orgstructureid);
	$orgcatename_detail = get_name_orgcateid($orgdetail->orgstructuretypeid);
	if ($orgdetail->managerid) {
		$username = get_name_userid($orgdetail->managerid);
	} else {
		$username = "";
	}
	if($orgdetail->parentid > 0){
		$parentname_detail = get_name_parentid($orgdetail->parentid);
	} else {
		$parentname_detail = 'Head Office';
	}
	$objdata->form = '<div class="container mt-3">
		<div class="form-group row">
	    <label for="orgname" class="col-sm-2 col-form-label">'. get_string('orgname', 'local_newsvnr') .'</label>
	    <div class="col-sm-10">
	      <input type="text" class="form-control" id="orgname" value="'.$orgdetail->name.'">
	    </div>
	  </div>
	  <div class="form-group row">
	    <label for="orgcode" class="col-sm-2 col-form-label">'. get_string('orgcode', 'local_newsvnr') .'</label>
	    <div class="col-sm-10">
	      <input type="text" class="form-control" id="orgcode" value="'.$orgdetail->code.'">
	    </div>
	  </div>
	  <div class="form-group row">
	    <label for="managerid" class="col-sm-2 col-form-label">'. get_string('parentorgstructurename', 'local_newsvnr') .'</label>
	    <div class="col-sm-10">
	      <input type="text" class="form-control" id="managerid" value="'.$username.'">
	    </div>
	  </div>
	  <div class="form-group row">
	    <label for="orgstructuretypeid" class="col-sm-2 col-form-label">'. get_string('orgstructuretypeid', 'local_newsvnr') .'</label>
	    <div class="col-sm-10">
	      <input type="text" class="form-control" id="orgstructuretypeid" value="'.$orgcatename_detail.'">
	    </div>
	  </div>
	  <div class="form-group row">
	    <label for="parentid" class="col-sm-2 col-form-label">'. get_string('parentid', 'local_newsvnr') .'</label>
	    <div class="col-sm-10">
	      <input type="text" class="form-control" id="parentid" value="'.$parentname_detail.'">
	    </div>
	  </div>
	  <div class="form-group row">
	    <label for="numbermargin" class="col-sm-2 col-form-label">'. get_string('numbermargin', 'local_newsvnr') .'</label>
	    <div class="col-sm-10">
	      <input type="text" class="form-control" id="numbermargin" value="'.$orgdetail->numbermargin.'">
	    </div>
	  </div>
	  <div class="form-group row">
	    <label for="numbercurrent" class="col-sm-2 col-form-label">'. get_string('numbercurrent', 'local_newsvnr') .'</label>
	    <div class="col-sm-10">
	      <input type="text" class="form-control" id="numbercurrent" value="'.$orgdetail->numbercurrent.'">
	    </div>
	  </div>
	  <div class="form-group row">
	    <label for="org_description" class="col-sm-2 col-form-label">'. get_string('description', 'local_newsvnr') .'</label>
	    <div class="col-sm-10">
	      <textarea class="form-control" rows="5" id="org_description">'.$orgdetail->description.'</textarea>
	    </div>
	  </div>
  </div>';
   echo json_encode($objdata,JSON_UNESCAPED_UNICODE);
	break;
}   

die();

