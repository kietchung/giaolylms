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

define('AJAX_SCRIPT', true);

require_once('../../../config.php');
require_once $CFG->dirroot . '/local/newsvnr/lib.php';
require_once $CFG->dirroot . '/course/lib.php';

$PAGE->set_context(context_system::instance());
$_POST = json_decode(file_get_contents('php://input'), true);

$action  = required_param('action',PARAM_RAW);
$username  = optional_param('username','',PARAM_RAW);
$password  = optional_param('password','',PARAM_RAW);
$date = optional_param('date', '', PARAM_RAW);

/**
 * API thêm, sửa loại phòng ban
 */
if($action == "orgstructure_category"){

	$response = new stdClass();

	$obj_orgstructure_category = new stdClass();

	$name = isset($_POST['name']) ? $_POST['name'] : "";
	$code = isset($_POST['code']) ? $_POST['code'] : "";
	$description = isset($_POST['description']) ? $_POST['description'] : "";

	$data = new stdClass();
	$data->name = $name;
	$data->code = $code;
	$data->description = $description;

	$checkAuthenticate = check_auth_api($username, $password);

	if($checkAuthenticate == false)
	{
		$response->checkAuth = "Authenticate failed!";
		
	}
	else{

		$response->checkAuth = "Authenticate success!";
		
		
		if(!$name)
		{
			$response->error['name'] = "Tên loại phòng ban không được bỏ trống";
		}
		if(!$code)
		{
			$response->error['code'] = "Mã loại phòng ban không được bỏ trống";
		}

		$check_categoryname =  find_orgstructure_category_by_name($name);
		if($check_categoryname) {
			$response->error['name'] = "Tên loại phòng ban '$check_categoryname->name' đã tồn tại";
		}
		$check_categorycode = find_orgstructure_category_by_code($code);
		if(!$check_categorycode)
		{
			if(empty($response->error))
			{

				$obj_orgstructure_category->name = $name;
				$obj_orgstructure_category->code = $code;
				$obj_orgstructure_category->description = $description;

				$success = $DB->insert_record('orgstructure_category', $obj_orgstructure_category);

				if($success)
				{
					$response->message['success'] = "Thêm thành công";
					$response->message['data'] = $data;
				}
				else{
					$response->error[] = "Thêm thất bại";
				}
			}
		}
		else{

			if(empty($response->error))
			{
				$obj_orgstructure_category->id = $check_categorycode->id;
				$obj_orgstructure_category->name = $name;
				$obj_orgstructure_category->description = $description;

				$success = $DB->update_record('orgstructure_category', $obj_orgstructure_category);

				if($success)
				{
					$response->message['success'] = "Chỉnh sửa thành công";
					$response->message['data'] = $data;
				}
				else{
					$response->error[] = "Thêm thất bại";
				}
			}
			

		}
			
	}

	echo json_encode($response,JSON_UNESCAPED_UNICODE);	

}


/**
 * API thêm, sửa phòng ban
 */
if($action == "orgstructure") {
	$response = new stdClass();
	$obj_orgstructure = new stdClass();

	$name = isset($_POST['name']) ? $_POST['name'] : "";
	$code = isset($_POST['code']) ? $_POST['code'] : "";
	$description = isset($_POST['description']) ? $_POST['description'] : "";
	$categoryname = isset($_POST['categoryname']) ? $_POST['categoryname'] : "";
	$parentcode = isset($_POST['parentcode']) ? $_POST['parentcode'] : "";

	$data = new stdClass();
	$data->name = $name;
	$data->code = $code;
	$data->categoryname = $categoryname;
	$data->parentcode = $parentcode;
	$data->description = $description;

	$checkAuthenticate = check_auth_api($username, $password);


	if($checkAuthenticate == false)
	{
		$response->checkAuth = "Authenticate failed!";
		
	}
	else{

		$response->checkAuth = "Authenticate success!";

		if(!$name)
		{
			$response->error['name'] = "Tên phòng ban không được bỏ trống";
		}
		if(!$code){
			$response->error['code'] = "Mã phòng ban không được bỏ trống";
		}
		if(!$categoryname)
		{
			$response->error['categoryname'] = "Loại phòng ban không được bỏ trống";
		}

		if(!$parentcode)
		{
			$response->error['parentcode'] = "Phòng ban cha không được bỏ trống";
		}

		$check_orgstructure_by_code = find_orgstructure_by_code($code);

		if(!$check_orgstructure_by_code)
		{
			if(empty($response->error))
			{
				

				$orgstructureTypeData = find_orgstructure_category_by_name($categoryname);

		
				if(!$orgstructureTypeData){
					$response->error['categoryname'] = 'Loại phòng ban không tồn tại' ;
				}
				else{
					$obj_orgstructure->name = $name;
					$obj_orgstructure->code = $code;


					// phong ban lon nhat mac dinh parentid = 0

					if($code == $parentcode)
					{
						$obj_orgstructure->parentid = 0;
					}

					else{
						// check xem co phong ban cha hop le k?
						$parentData = find_orgstructure_parrentcode($parentcode);
						if(!$parentData)
						{
							$response->error['parentcode'] = 'Phòng ban cha không tồn tại' ;
						}
						else{
							$obj_orgstructure->parentid = $parentData->id;
						}

					}
			
					$obj_orgstructure->orgstructuretypeid = $orgstructureTypeData->id;
					$obj_orgstructure->numbermargin = 0;
					$obj_orgstructure->numbercurrent = 0;
					$obj_orgstructure->description = $description;


					$success = $DB->insert_record('orgstructure', $obj_orgstructure);

					if($success)
					{
						$response->message['success'] = "Thêm thành công";
						$response->message['data'] = $data;
					}
					else{
						$response->error[] = "Thêm thất bại";
					}
				}
				
			}
		}
		else{

			if(empty($response->error))
			{
				$parentData = find_orgstructure_parrentcode($parentcode);

				$orgstructureTypeData = find_orgstructure_category_by_name($categoryname);

				if(!$parentData)
				{
					$response->error['parentcode'] = 'Phòng ban cha không tồn tại' ;
				}
				else if(!$orgstructureTypeData){
					$response->error['categoryname'] = 'Loại phòng ban không tồn tại' ;
				}
				else{
					$obj_orgstructure->id = $check_orgstructure_by_code->id;
					$obj_orgstructure->name = $name;
					$obj_orgstructure->parentid = $parentData->id;
					$obj_orgstructure->orgstructuretypeid = $orgstructureTypeData->id;
					$obj_orgstructure->numbermargin = 0;
					$obj_orgstructure->numbercurrent = 0;
					$obj_orgstructure->description = $description;

					$success = $DB->update_record('orgstructure', $obj_orgstructure);

					if($success)
					{
						$response->message['success'] = "Chỉnh sửa thành công";
						$response->message['data'] = $data;
					}
					else{
						$response->error[] = "Thêm thất bại";
					}
				}
				
			}
		}

		
	}

	echo json_encode($response,JSON_UNESCAPED_UNICODE);

}

/**
 * API thêm, sửa chức danh
 */
if($action == "orgstructure_jobtitle") {
	$response = new stdClass();
	$orgstructure_jobtitle = new stdClass();

	$name = isset($_POST['name']) ? $_POST['name'] : "";
	$code = isset($_POST['code']) ? $_POST['code'] : "";
	$description = isset($_POST['description']) ? $_POST['description'] : "";

	$data = new stdClass();
	$data->name = $name;
	$data->code = $code;
	$data->description = $description;

	$checkAuthenticate = check_auth_api($username, $password);

	if($checkAuthenticate == false)
	{
		$response->checkAuth = "Authenticate failed!";
	}
	else{

		$response->checkAuth = "Authenticate success!";

		if(!$name)
		{
			$response->error['name'] = "Tên chức danh không được bỏ trống";
		}
		if(!$code){
			$response->error['code'] = "Mã chức danh không được bỏ trống";
		}

		$check_orgstructure_jobtitle_by_code = find_orgstructure_jobtitle_by_code($code);
		$check_orgstructure_jobtitle_by_name = find_orgstructure_jobtitle_by_name($name);
		if($check_orgstructure_jobtitle_by_name) {
			$response->error['name'] = "Mã chức danh '$check_orgstructure_jobtitle_by_name->name' đã tồn tại";
		}

		if(empty($check_orgstructure_jobtitle_by_code))
		{
			if(empty($response->error))
			{
				$orgstructure_jobtitle->name = $name;
				$orgstructure_jobtitle->code = $code;
				$orgstructure_jobtitle->description = $description;

				$success = $DB->insert_record('orgstructure_jobtitle', $orgstructure_jobtitle);

				if($success)
				{
					$response->message['success'] = "Thêm thành công";
					$response->message['data'] = $data;
				}

				else{
					$response->error[] = "Thêm thất bại";
				}

			} 
		}
		else{

			if(empty($response->error))
			{
				$orgstructure_jobtitle->id = $check_orgstructure_jobtitle_by_code->id;

				$orgstructure_jobtitle->name = $name;
				$orgstructure_jobtitle->description = $description;

				$success = $DB->update_record('orgstructure_jobtitle', $orgstructure_jobtitle);

				if($success)
				{
					$response->message['success'] = "Chỉnh sửa thành công";
					$response->message['data'] = $data;
				}

				else{
					$response->error[] = "Thêm thất bại";
				}

			} 
		}

		
	}
	
	echo json_encode($response,JSON_UNESCAPED_UNICODE);

}

/**
 * API thêm, sửa chức vụ(vị trí)
 */
if($action == "orgstructure_position") {

	$response = new stdClass();

	$code = isset($_POST['code']) ? $_POST['code'] : "";
	$name = isset($_POST['name']) ? $_POST['name'] : "";
	$jobtitlecode = isset($_POST['jobtitlecode']) ? $_POST['jobtitlecode'] : "";
	$orgstructurecode = isset($_POST['orgstructurecode']) ? $_POST['orgstructurecode'] : "";
	$description = isset($_POST['description']) ? $_POST['description'] : "";
	$check_orgstructure_ins = preg_match("/\s|,/",$orgstructurecode);
	if($check_orgstructure_ins == true) {
		$orgstructurecode = "";
	}
	$data = new stdClass();
	$data->name = $name;
	$data->code = $code;
	$data->jobtitlecode = $jobtitlecode;
	$data->orgstructurecode = $orgstructurecode;
	$data->description = $description;

	$checkAuthenticate = check_auth_api($username, $password);

	if($checkAuthenticate == false)
	{
		$response->checkAuth = "Authenticate failed!";
	}
	else{

		$response->checkAuth = "Authenticate success!";

		if(!$name)
		{
			$response->error['name'] = "Tên vị trí phòng ban không được bỏ trống";
		}
		if(!$code)
		{
			$response->error['code'] = "Mã vị trí phòng ban không được bỏ trống";
		}

	
		
		$check_orgstructure_jobtitle = find_orgstructure_jobtitle_by_code($jobtitlecode);
		$check_orgstructure = find_orgstructure_by_code($orgstructurecode);

		$check_orgstructure_position = find_orgstructure_position_by_code($code);

		if(!$check_orgstructure_position)
		{

			if(empty($response->error))
			{	
				
				
					$orgstructure_position = new stdClass();
					$orgstructure_position->name = $name;
					$orgstructure_position->code = $code;
					$orgstructure_position->jobtitleid = $check_orgstructure_jobtitle->id;
					$orgstructure_position->orgstructureid = $check_orgstructure->id;
					$orgstructure_position->description = $description;

					$success = $DB->insert_record('orgstructure_position', $orgstructure_position);

					if($success)
					{
						$response->message['success'] = "Thêm thành công";
						$response->message['data'] = $data;
					}

					else{
						$response->error[] = "Thêm thất bại";
					}
			}
		}
		else{

			if(empty($response->error))
			{	
					$orgstructure_position = new stdClass();
					$orgstructure_position->id = $check_orgstructure_position->id;
					$orgstructure_position->name = $name;
					$orgstructure_position->jobtitleid = $check_orgstructure_jobtitle->id;
					$orgstructure_position->orgstructureid = $check_orgstructure->id;
					$orgstructure_position->description = $description;
			

					

					$success = $DB->update_record('orgstructure_position', $orgstructure_position);

					if($success)
					{
						$response->message['success'] = "Chỉnh sửa thành công";
						$response->message['data'] = $data;
					}

					else{
						$response->error[] = "Thêm thất bại";
					}
				// }		
			}
		}

	}

	echo json_encode($response,JSON_UNESCAPED_UNICODE);
}


/**
 * API tạo user và thêm user vào khóa học (Phân hệ tuyển dụng)
 */
if($action == "enroll_recruitment") {
	require_once("$CFG->dirroot/user/lib.php");
	$response = new stdClass();
	
	$checkAuthenticate = check_auth_api($username, $password);

	if($checkAuthenticate == false)
	{
		$response->checkAuth = "Authenticate failed!";
	} 
	else {
		$response->checkAuth = "Authenticate success!";
		$usercode = isset($_POST['usercode']) ? $_POST['usercode'] : "";
		$orgpositioncode = isset($_POST['orgpositioncode']) ? $_POST['orgpositioncode'] : "";
		$orgjobtitlecode = isset($_POST['orgjobtitlecode']) ? $_POST['orgjobtitlecode'] : "";
		$orgstructurecode = isset($_POST['orgstructurecode']) ? $_POST['orgstructurecode'] : "";
		$teacherloginname = isset($_POST['teacherloginname']) ? $_POST['teacherloginname'] : "";
		$fullname = isset($_POST['fullname']) ? $_POST['fullname'] : "";
		$email = isset($_POST['email']) ? $_POST['email'] : "";
		$userlogin = isset($_POST['userlogin']) ? $_POST['userlogin'] : "";
		$phone1 = isset($_POST['phone1']) ? $_POST['phone1'] : "";
		$phone2 = isset($_POST['phone2']) ? $_POST['phone2'] : "";
		$identitycard = isset($_POST['identitycard']) ? $_POST['identitycard'] : "";
		$parts = explode(" ", $fullname);
		if(count($parts) > 1) {
			$lastname = array_pop($parts);
			$firstname = implode(" ", $parts);
		}
		else
		{
			$firstname = $fullname;
			$lastname = " ";
		}
		$fullname_user = preg_replace('/\s+/', '', $fullname);
		$fullname_tolower = mb_strtolower($fullname_user, 'UTF-8');
		$fullname_without_tone = convert_name($fullname_tolower);
		if(!$usercode)
		{
			$response->error['usercode'] = "usercode(Mã ứng viên) không được bỏ trống";
		}
		if(!$email)
		{
			$response->error['email'] = "email không được bỏ trống";
		}
		if(!$orgpositioncode)
		{
			$response->error['orgpositioncode'] = "orgpostioncode(Mã chức vụ) không được bỏ trống";
		}
		if(!$orgjobtitlecode)
		{
			$response->error['orgjobtitlecode'] = "orgjobtitlecode(Mã chức danh) không được bỏ trống";
		}
		if(!$orgstructurecode)
		{
			$response->error['orgstructurecode'] = "orgstructurecode(Mã phòng ban) không được bỏ trống";
		}
		if(!$fullname) {
			$response->error['fullname'] = "fullname(Họ tên ứng viên) không được bỏ trống";
		}
		

		if(empty($userlogin)) {
			$userlogin = $fullname_without_tone;
			$userlognin_user = find_username($fullname_without_tone);
			if($userlognin_user) {
				$time = time();
				$userlogin = $fullname_without_tone . $time;
			}
		}

		$check_orgpositioncode = find_id_orgpostion_by_code($orgpositioncode);
		$check_orgstructurecode = find_id_orgstructure_by_code($orgstructurecode);
		$check_orgjobtitlecode = find_id_orgjobtitle_by_code($orgjobtitlecode);
		if(!$check_orgpositioncode) {
			$response->error['orgpositioncode'] = "orgpositioncode(Mã chức vụ) không tồn tại chức vụ '$orgpositioncode'";
		}
		if(!$check_orgstructurecode) {
			$response->error['orgstructurecode'] = "orgstructurecode(Mã phòng ban) không tồn tại phòng ban '$orgstructurecode'";
		}
		if(!$check_orgjobtitlecode) {
			$response->error['orgjobtitlecode'] = "orgjobtitlecode(Mã chức danh) không tồn tại chức danh '$orgjobtitlecode'";
		}

		
		// if(!$userlogin_teacher) {
		// 	$response->error['teacherloginname'] = "teacherloginname(tài khoản giáo viên) không tồn tại giáo viên '$teacherloginname'";
		// }
		if (!validate_email($email)) {
			$response->error['email'] = "email '$email' không đúng định dạng";
		} else if (empty($CFG->allowaccountssameemail) and $DB->record_exists('user', array('email' => $email))) {
			$response->error['email'] = "email '$email' đã được sử dụng vui lòng chọn email khác";
		}

		if(empty($response->error)) {
			$check_usercode = find_usercode_by_code($usercode);
			$response->checkAuth = "Authenticate success!";
			if(!$check_usercode) {	
				
				$usernew = new stdClass();
				$usernew->username = $userlogin;
				$usernew->usercode = $usercode;
				$usernew->orgpositionid = $check_orgpositioncode->id;
				$usernew->mnethostid = $CFG->mnet_localhost_id;
				$usernew->confirmed= 1;
				$usernew->password = hash_internal_user_password('Vnr@1234');
				$usernew->firstname = $firstname;
				$usernew->lastname = $lastname;
				$usernew->email = $email;
				$usernew->identitycard = $identitycard;
				$usernew->phone1 = $phone1;
				$usernew->phone2 = $phone2;
				$usernew->country = 'VN';
				$usernew->lang = 'vi';
				$course_for_orgpositioncode = get_course_by_orgpositioncode($check_orgpositioncode->id,$check_orgjobtitlecode->id,$check_orgstructurecode->id,1);
				if($course_for_orgpositioncode) {
					$courseid = $course_for_orgpositioncode->id;
					$userlogin_teacher = find_username($teacherloginname);
					if($userlogin_teacher) {
						$teacherid = $userlogin_teacher->id;
						$teacher_name = check_teacher_in_course($courseid,$teacherid);

						if(!$teacher_name) {
							enrol_user($teacherid, $courseid, 'editingteacher');
						} 
					}

					$usernew->id = user_create_user($usernew,false,false);

					$user_in_course = check_user_in_course($courseid,$usernew->id);
					if(!$user_in_course) {
						enrol_user($usernew->id, $courseid, 'student');
						$response->message['success'] = "Thêm thành công";
						$response->message['data'] = $usernew;
					}

					\core\event\user_created::create_from_userid($usernew->id)->trigger();
					
				} else {
					$response->error['course'] = "Không tìm thấy khóa học phù hợp vui lòng tạo khóa học cho 'vị trí - $orgpositioncode , chức danh - $orgjobtitlecode , phòng ban - $orgstructurecode'";
				}
				
			} else {
				$response->error['usercode'] = "usercode(Mã ứng viên) '$usercode' đã tồn tại";
			}
		}
	}

	echo json_encode($response,JSON_UNESCAPED_UNICODE);
}

/**
 * API tạo user và thêm user vào khóa học (Phân hệ đào tạo)
 */
if($action == "enroll_course") {
	require_once("$CFG->dirroot/user/lib.php");
	$response = new stdClass();

	$checkAuthenticate = check_auth_api($username, $password);

	if($checkAuthenticate == false)
	{
		$response->checkAuth = "Authenticate failed!";
	} 
	else {
		$response->checkAuth = "Authenticate success!";

		$usercode = isset($_POST['usercode']) ? $_POST['usercode'] : "";
		$fullname = isset($_POST['fullname']) ? $_POST['fullname'] : "";
		$email = isset($_POST['email']) ? $_POST['email'] : "";
		$coursecode =isset($_POST['coursecode']) ? $_POST['coursecode'] : "";
		$orgpositioncode = isset($_POST['orgpositioncode']) ? $_POST['orgpositioncode'] : "";
		$parts = explode(" ", $fullname);
		if(count($parts) > 1) {
			$lastname = array_pop($parts);
			$firstname = implode(" ", $parts);
		}
		else
		{
			$firstname = $fullname;
			$lastname = " ";
		}
		$fullname_user = preg_replace('/\s+/', '', $fullname);
		$fullname_tolower = mb_strtolower($fullname_user, 'UTF-8');
		$fullname_without_tone = convert_name($fullname_tolower);

		if(!$usercode)
		{
			$response->error['usercode'] = "usercode(Mã ứng viên) không được bỏ trống";
		}
		if(!$email)
		{
			$response->error['email'] = "email không được bỏ trống";
			
		}
		if(!$coursecode)
		{
			$response->error['coursecode'] = "coursecode(Mã khóa học) không được bỏ trống";
			
		}
		if(!$fullname) {
			$response->error['fullname'] = "fullname(Họ tên ứng viên) không được bỏ trống";
		}
		// if(!$orgpositioncode) {
		// 	$response->error['orgpositioncode'] = "orgpositioncode(Chức vụ - Vị trí) không được bỏ trống";
		// }


		if(empty($userlogin)) {
			$userlogin = $fullname_without_tone;
			$userlognin_user = find_username($fullname_without_tone);
			if($userlognin_user) {
				$time = time();
				$userlogin = $fullname_without_tone . $time;
			}
		}
		$check_usercode = find_usercode_by_code($usercode);
		$get_orgpositionid = get_orgpositionid_by_code($orgpositioncode);
		$get_course = get_course_by_idnumber($coursecode); 
		if($get_course)
			$courseid = $get_course->id;
		if(!$check_usercode) {
			if (!validate_email($email)) {
				$response->error['email'] = "email '$email' không đúng định dạng";
			} else if (empty($CFG->allowaccountssameemail) and $DB->record_exists('user', array('email' => $email))) {
				$response->error['email'] = "email '$email' đã được sử dụng vui lòng chọn email khác";
			}
		}
		if(empty($response->error)) {
			$usernew = new stdClass();
			$usernew->course = 1;
			$usernew->username = $userlogin;
			$usernew->usercode = $usercode;
			$usernew->orgpositionid = $get_orgpositionid;
			$usernew->auth = 'manual';
			$usernew->suspended = '0';
			$usernew->password = '';
			$usernew->preference_auth_forcepasswordchange = 0;
			$usernew->mnethostid = $CFG->mnet_localhost_id;
			$usernew->confirmed= 1;
			$usernew->firstname = $firstname;
			$usernew->lastname = $lastname;
			$usernew->email = $email;
			$usernew->maildisplay = 2;
			$usernew->country = 'VN';
			$usernew->lang = 'vi';
			$createpassword = true;
			if(!$check_usercode) {	
				if($get_course) {
					
				    $usernew->id = user_create_user($usernew,false,false);
				    $usernew = $DB->get_record('user', array('id' => $usernew->id));
				    $user_in_course = check_user_in_course($courseid,$usernew->id);
				    if(!$user_in_course) {
				    	enrol_user($usernew->id, $courseid, 'student');
						$response->message['success'] = "Thêm thành công và tạo mới user '$fullname'";
						$response->message['data'] = $usernew;
				    }

				    $usercontext = context_user::instance($usernew->id);
				    
				    if ($createpassword) {
				    	setnew_password_and_mail($usernew);
				    	unset_user_preference('create_password', $usernew);
				    	set_user_preference('auth_forcepasswordchange', 1, $usernew);
				    }
				    \core\event\user_created::create_from_userid($usernew->id)->trigger();

				} else {
					$response->error['coursecode'] = "Không tìm thấy khóa học với mã '$coursecode'";
				}
				
			} else {
				if($get_course) {
	
				    $user_in_course = check_user_in_course($courseid,$check_usercode->id);

				    if(!$user_in_course) {

				    	enrol_user($check_usercode->id, $courseid, 'student');
						$response->message['success'] = "Thêm thành công thêm user vào khóa học '$get_course->fullname'";
						$response->message['data'] = $usernew;
				    } else {
				    	$response->message['success'] = "User đã tham gia vào khóa '$get_course->fullname'";
				    }
				} else {
					$response->error['coursecode'] = "Không tìm thấy khóa học với mã '$coursecode'";
				}
			}
		}	
	}
	echo json_encode($response,JSON_UNESCAPED_UNICODE);
}


/**
 * API rút tên user ra khỏi khóa học, hàm dùng chung 2 phân hệ đào tạo và tuyển dụng
 */
if($action == "delete_enroll_course") {
	require_once($CFG->dirroot . '/enrol/locallib.php');
	$response = new stdClass();

	$action = isset($_POST['action']) ? $_POST['action'] : "";

	
	$checkAuthenticate = check_auth_api($username, $password);

	if($checkAuthenticate == false){
		$response->checkAuth = "Authenticate failed!";
	} 
	else {
		
		$response->checkAuth = "Authenticate success!";

		$usercode = isset($_POST['usercode']) ? $_POST['usercode'] : "";
		$orgpositioncode = isset($_POST['orgpositioncode']) ? $_POST['orgpositioncode'] : "";
		$orgjobtitlecode = isset($_POST['orgjobtitlecode']) ? $_POST['orgjobtitlecode'] : "";
		$orgstructurecode = isset($_POST['orgstructurecode']) ? $_POST['orgstructurecode'] : "";

		if(!$usercode) {
			$response->error['usercode'] = "usercode('Mã nhân viên') không được bỏ trống";
		}
		if(!$orgpositioncode)
		{
			$response->error['orgpositioncode'] = "orgpostioncode(Mã chức vụ) không được bỏ trống";
		}
		if(!$orgjobtitlecode)
		{
			$response->error['orgjobtitlecode'] = "orgjobtitlecode(Mã chức danh) không được bỏ trống";
		}
		if(!$orgstructurecode)
		{
			$response->error['orgstructurecode'] = "orgstructurecode(Mã phòng ban) không được bỏ trống";
		}

		$check_orgpositioncode = find_id_orgpostion_by_code($orgpositioncode);
		$check_orgstructurecode = find_id_orgstructure_by_code($orgstructurecode);
		$check_orgjobtitlecode = find_id_orgjobtitle_by_code($orgjobtitlecode);
		if(!$check_orgpositioncode) {
			$response->error['orgpositioncode'] = "orgpositioncode(Mã chức vụ) không tồn tại chức vụ '$orgpositioncode'";
		}
		if(!$check_orgstructurecode) {
			$response->error['orgstructurecode'] = "orgstructurecode(Mã phòng ban) không tồn tại phòng ban '$orgstructurecode'";
		}
		if(!$check_orgjobtitlecode) {
			$response->error['orgjobtitlecode'] = "orgjobtitlecode(Mã chức danh) không tồn tại chức danh '$orgjobtitlecode'";
		}

		if(empty($response->error)) {
			if($action == 'delete_recruitment') {
				$find_course = get_course_by_orgpositioncode($check_orgpositioncode->id,$check_orgjobtitlecode->id,$check_orgstructurecode->id,1);
				if(!$find_course) {
					$response->message['success'] = "Ứng viên với mã '$usercode' chưa tham gia khóa học";
				} else {
					$get_userid = find_usercode_by_code($usercode);
					if($get_userid) {
						$instance = $DB->get_record('enrol', array('courseid'=>$find_course->id, 'enrol'=>'manual'), '*', MUST_EXIST);
						$get_ueid = find_ueid_by_enrolid($instance->id,$get_userid->id);
						$plugin = enrol_get_plugin($instance->enrol);
						$plugin->unenrol_user($instance, $get_ueid->userid);
						$response->message['success'] = "Rút ứng viên với mã '$usercode' từ khóa '$find_course->fullname' thành công";
					}
				}
			} elseif ($action == 'delete_enroll_course') {
				$find_course = get_course_by_orgpositioncode($check_orgpositioncode->id,$check_orgjobtitlecode->id,$check_orgstructurecode->id,2);
				if(!$find_course) {
					$response->message['success'] = "Ứng viên với mã '$usercode' chưa tham gia khóa học";
				} else {
					$get_userid = find_usercode_by_code($usercode);
					if($get_userid) {
						$instance = $DB->get_record('enrol', array('courseid'=>$find_course->id, 'enrol'=>'manual'), '*', MUST_EXIST);
						$get_ueid = find_ueid_by_enrolid($instance->id,$get_userid->id);
						$plugin = enrol_get_plugin($instance->enrol);
						$plugin->unenrol_user($instance, $get_ueid->userid);
						$response->message['success'] = "Rút ứng viên với mã '$usercode' từ khóa '$find_course->fullname' thành công";
					}
				}
			}
			
		}
	}
	echo json_encode($response,JSON_UNESCAPED_UNICODE);
}



/**
 * API trả điểm về cho HRM từ các bài quiz 
 * lưu trong bảng {test}
 */
if($action == "test") {	

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


// -- Get dữ liệu cho blocks chart (giáo viên)-- \\

//Số lượng học viên hoàn thành khoá học
if($action == "coursecomp_chart_vp") {

	$courseid = isset($_GET['courseid']) ? $_GET['courseid'] : "";
	if($courseid) {
		$wheresql = 'ra.roleid =? AND u.id = ? AND c.id = ?';
		$params = [3,$USER->id,$courseid];
	} else {
		$wheresql = 'ra.roleid =? AND u.id = ?';
		$params = [3,$USER->id];
	}
	$sql = "
			SELECT c.id, c.fullname
		    FROM mdl_role_assignments as ra
		        join mdl_user as u on u.id = ra.userid
		        join mdl_user_enrolments as ue on ue.userid = u.id
		        join mdl_enrol as e on e.id = ue.enrolid
		        join mdl_course as c on c.id = e.courseid
		        join mdl_context as ct on ct.id = ra.contextid and ct.instanceid = c.id
		        join mdl_role as r on r.id = ra.roleid
   			WHERE $wheresql";
	$record = $DB->get_records_sql($sql,[$USER->id]);
	$list_coursename = $list_enroll = array();
	foreach ($record as $value) {
		$list_coursename[] = $value->fullname;
		$list_user_enroll = get_listuser_in_course($value->id, 5);
		$list_enroll[] = count($list_user_enroll);
		$course    = $DB->get_record('course', ['id' => $value->id]);
		$cinfo     = new completion_info($course);
		$usercompletionnumber = 0;
		if($list_user_enroll) {
			foreach($list_user_enroll as $user) {
				$iscomplete = $cinfo->is_course_complete($user->id);
				if($iscomplete == true)
					$usercompletionnumber++;
			}
		}
		$list_completion_course[] = $usercompletionnumber;
	}

   	$response = new stdClass();
    $response->chart = (object)['height' => 400,'type' => 'column'];
    $response->title = (object)['text' => ''];
    $response->subtitle = (object)['text' => ''];
    $response->xAxis = (object)['categories' => $list_coursename,'crosshair' => true];
    $response->yAxis = (object)['min' => 0, 'title' => (object)['text' => get_string('numberstudent', 'local_newsvnr')]];
    $response->credits = (object)['enabled' => false];
    $response->tooltip = (object)[
    								'headerFormat' => '<span style="font-size:10px">{point.key}</span><table>',
    								'pointFormat' => '<tr><td style="color:{series.color};padding:0">{series.name}: </td>'.'<td style="padding:0"><b>{point.y:.0f}</b></td></tr>', 
    								'footerFormat' => '</table>', 
    								'shared' => true, 
    								'useHTML' => true
    							];
    $response->plotOptions = (object)['column' => (object)['pointPadding' => 0.2, 'borderWidth' => 0]];
    $response->series = [
    						(object)['name' => get_string('totalstudent', 'local_newsvnr'),'data' => $list_enroll,'color' => '#223dc4'],
    						(object)['name' => get_string('numbercompletedcourse', 'local_newsvnr'),'data' => $list_completion_course,'color' => '#f24012']
    					];

    echo json_encode($response,JSON_UNESCAPED_UNICODE);
}

if($action == "coursecomp_chart") {
	$params = [];
	$wheresql = 'WHERE course = :courseid AND timecompleted IS NOT NULL ';
	if ($date != '') {
		$wheresql .= "AND timecompleted BETWEEN :start AND :end";
		$date = json_decode($date);
		$params['start'] = $date->start;
		$params['end'] = $date->end;
	}
	$sql = '
			SELECT c.id, c.fullname
		        FROM mdl_role_assignments AS ra
		        JOIN mdl_user AS u ON u.id = ra.userid
		        JOIN mdl_user_enrolments AS ue ON ue.userid = u.id
		        JOIN mdl_enrol AS e ON e.id = ue.enrolid
		        JOIN mdl_course AS c ON c.id = e.courseid
		        JOIN mdl_context AS ct ON ct.id = ra.contextid AND ct.instanceid = c.id
		        JOIN mdl_role AS r ON r.id = ra.roleid
   			WHERE  ra.roleid=3 AND u.id = ?';
   	$record = $DB->get_records_sql($sql,[$USER->id]);
   	$list_coursename = $list_enroll = array();
   	foreach ($record as $value) {
   		$list_coursename[] = $value->fullname;
   		$list_user_enroll = get_listuser_in_course($value->id, 5);
		$list_enroll[] = count($list_user_enroll);
		$course    = $DB->get_record('course', ['id' => $value->id]);
        $cinfo     = new completion_info($course);
		$usercompletionnumber = 0;
		if($list_user_enroll) {
			foreach($list_user_enroll as $user) {
				$iscomplete = $cinfo->is_course_complete($user->id);
				if($iscomplete == true)
					$usercompletionnumber++;
			}
		}
   		$list_completion_course[] = $usercompletionnumber;
   	}

   	$response = new stdClass();
   	$response->list_coursename = $list_coursename;
   	$response->list_enroll = $list_enroll;
   	$response->list_completion_course = $list_completion_course;
    
    echo json_encode($response,JSON_UNESCAPED_UNICODE);
}

//Xu hướng hoạt động (modules)
if($action == "coursemodulecomp_chart_vp") {
	$courseid = isset($_GET['courseid']) ? $_GET['courseid'] : "";
	if($courseid) {
		$wheresql = 'ra.roleid =? AND u.id = ? AND c.id = ?';
		$params = [3,$USER->id,$courseid];
	} else {
		$wheresql = 'ra.roleid =? AND u.id = ?';
		$params = [3,$USER->id];
	}
	
	$sql = "
			SELECT COUNT(cmc.id) as slht,c.fullname
		        from mdl_role_assignments as ra
		        join mdl_user as u on u.id= ra.userid
		        join mdl_user_enrolments as ue on ue.userid=u.id
		        join mdl_enrol as e on e.id=ue.enrolid
		        join mdl_course as c on c.id=e.courseid
		        join mdl_context as ct on ct.id=ra.contextid and ct.instanceid= c.id
		        join mdl_role as r on r.id= ra.roleid
				join mdl_course_modules cm on c.id = cm.course
				join mdl_course_modules_completion cmc on cm.id = cmc.coursemoduleid
	        where $wheresql
			group by c.fullname";

   	$record = $DB->get_records_sql($sql,$params);
   	$list_coursename = array();
   	$list_modules_comp = array();
   	foreach ($record as $value) {
   		$list_coursename[] = $value->fullname;
   		$list_modules_comp[] = (int)$value->slht;
   	}
   	$response = new stdClass();
   	$response->chart = (object)['type' => 'column'];
    $response->title = (object)['text' => ''];
    $response->subtitle = (object)['text' => ''];
    $response->credits = (object)['enabled' => false];
    $response->xAxis = (object)['categories' => $list_coursename, 'crosshair' => true];
    $response->yAxis = (object)['min' => 0, 'title' => (object)['text' => get_string('numbercompletedmodule', 'local_newsvnr')]];
    $response->tooltip = (object)['headerFormat' => '<span style="font-size:10px">{point.key}</span><table>', 'pointFormat' => '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                            '<td style="padding:0"><b>{point.y:.0f}</b></td></tr>', 'footerFormat' => '</table>', 'shared' => true, 'useHTML' => true];
    $response->plotOptions = (object)['column' => (object)['pointPadding' => 0.4, 'borderWidth' => 0]];
    $response->series = [(object)['name' => get_string('numbertickcompteledcourse', 'local_newsvnr'), 'data' => $list_modules_comp,'color' => '#e87c01']];

   	// $response->list_coursename = $list_coursename;
   	// $response->list_modules_comp = $list_modules_comp;

    echo json_encode($response,JSON_UNESCAPED_UNICODE);
}

if($action == "coursemodulecomp_chart") {
	$courseid = isset($_GET['courseid']) ? $_GET['courseid'] : "";
	if($courseid) {
		$wheresql = 'ra.roleid =? AND u.id = ? AND c.id = ?';
		$params = [3,$USER->id,$courseid];
	} else {
		$wheresql = 'ra.roleid =? AND u.id = ?';
		$params = [3,$USER->id];
	}
	
	$sql = "
			SELECT COUNT(cmc.id) AS slht,c.fullname
		        FROM mdl_role_ASsignments AS ra
		        JOIN mdl_user AS u ON u.id= ra.userid
		        JOIN mdl_user_enrolments AS ue ON ue.userid=u.id
		        JOIN mdl_enrol AS e ON e.id=ue.enrolid
		        JOIN mdl_course AS c ON c.id=e.courseid
		        JOIN mdl_context AS ct ON ct.id=ra.contextid AND ct.instanceid= c.id
		        JOIN mdl_role AS r ON r.id= ra.roleid
				JOIN mdl_course_modules AS cm ON c.id = cm.course
				JOIN mdl_course_modules_completion AS cmc ON cm.id = cmc.coursemoduleid
	        WHERE $wheresql
			GROUP BY c.fullname";

   	$record = $DB->get_records_sql($sql,$params);
   	$list_coursename = array();
   	$list_modules_comp = array();
   	foreach ($record as $value) {
   		$list_coursename[] = $value->fullname;
   		$list_modules_comp[] = (int)$value->slht;
   	}
   	$response = new stdClass();
   	$response->list_coursename = $list_coursename;
   	$response->list_modules_comp = $list_modules_comp;

    echo json_encode($response,JSON_UNESCAPED_UNICODE);
}

//Xu hướng ghi danh khoá học
if($action == "joincourse_chart_vp") {
	$strdate = isset($_GET['strdate']) ? $_GET['strdate'] : "";
	$courseid = isset($_GET['courseid']) ? $_GET['courseid'] : "";
	$strdate_unix = strtotime($strdate);
	$strtoday_unix = time();
	$srt_courseid = get_list_courseid_by_teacher($USER->id);
	$params = [];
	if($strdate) {
		$wheresql = "e.courseid In($srt_courseid) AND c.startdate >= $strdate_unix";
	} elseif($strdate and $courseid) {
		$wheresql = "e.courseid = ? AND c.startdate >= $strdate_unix";
		$params = [$courseid];
	} elseif($courseid) {
		$wheresql = "e.courseid = ?";
		$params = [$courseid];
	} else {
		$wheresql = "e.courseid In($srt_courseid)";
	}

	$sql = "
			SELECT COUNT(e.id) AS cid, e.courseid, c.fullname 
			FROM mdl_enrol e 
				RIGHT JOIN mdl_user_enrolments ue ON e.id = ue.enrolid
				JOIN mdl_course c ON c.id = e.courseid 
			WHERE $wheresql
			GROUP BY e.courseid,c.fullname";

	$record = $DB->get_records_sql($sql,$params);
	$list_coursename = array();
   	$list_joincourse = array();
   	foreach ($record as $value) {
   		$list_coursename[] = $value->fullname;
   		$list_joincourse[] = (int)$value->cid;
   	}
 
   	$response = new stdClass();
    $response->chart = (object)['height' => 400 ,'type' => 'line'];
    $response->title = (object)['text' => ''];
    $response->subtitle = (object)['text' => ''];
    $response->xAxis = (object)['categories' => $list_coursename];
    $response->yAxis = (object)['title' => (object)['text' => get_string('numberstudent', 'local_newsvnr')]];
    $response->credits = (object)['enabled' => false];
    $response->plotOptions = (object)['line' => (object)['dataLabels' => (object)['enabled' => true], 'enableMouseTracking' => true]];
    $response->series = [(object)['name' => get_string('newsenrolcourse', 'local_newsvnr'),'data' => $list_joincourse,'color' => '#ef4914']];

	echo json_encode($response,JSON_UNESCAPED_UNICODE);
}

if($action == "joincourse_chart") {
	$strdate = isset($_GET['strdate']) ? $_GET['strdate'] : "";
	$strdate_unix = strtotime($strdate);
	$strtoday_unix = time();
	$wheresql = '';
	$params = [];
	if ($date != '') {
		$wheresql .= "AND ra.timemodified BETWEEN :start AND :end";
		$date = json_decode($date);
		$params['start'] = $date->start;
		$params['end'] = $date->end;
	}
	$courses = get_list_courseinfo_by_teacher($USER->id);
	$list_coursename = array();
   	$list_joincourse = array();
	foreach($courses as $course) {
		$params['courseid'] = $course->id;
		if(!empty($strdate))
			$wheresql .= "and c.startdate >= $strdate_unix";
		$sql = "SELECT COUNT(ra.roleid) studentnumber
				FROM mdl_role_assignments ra JOIN mdl_context ct ON ra.contextid = ct.id
					JOIN mdl_course c on c.id = ct.instanceid 
				WHERE c.id = :courseid AND ct.contextlevel = 50 AND ra.roleid = 5
				$wheresql
				GROUP BY c.fullname";
		$record = $DB->get_field_sql($sql, $params);
		if($record) {
			$list_coursename[] = $course->fullname;
	   		$list_joincourse[] = (int)$record;
		} else {
			$list_coursename[] = $course->fullname;
	   		$list_joincourse[] = 0;
		}
   	}
   	$response = new stdClass();
   	$response->list_coursename = $list_coursename;
   	$response->list_joincourse = $list_joincourse;
	echo json_encode($response,JSON_UNESCAPED_UNICODE);
}

//Lượt xem khoá học
if($action == "viewcount_chart_vp") {
	$courseid = isset($_GET['courseid']) ? $_GET['courseid'] : "";
	$params = [];
	if ($courseid) {
		$wheresql = "courseid = ? and lsl.action = 'viewed'";
		$params = [$courseid];
	} else {
		$srt_courseid = get_list_courseid_by_teacher($USER->id);
		$wheresql = "courseid IN($srt_courseid) AND lsl.action = 'viewed'";
	}
	$sql = "SELECT COUNT(lsl.id) as vc,lsl.courseid, c.fullname
			FROM mdl_logstore_standard_log lsl
			 	left join mdl_course c on  lsl.courseid = c.id 
			WHERE $wheresql
			GROUP BY lsl.courseid,c.fullname";
	$record = $DB->get_records_sql($sql,$params);
	$list_coursename = array();
   	$list_viewcount = array();
   	foreach ($record as $value) {
   		$list_coursename[] = $value->fullname;
   		$list_viewcount[] = (int)$value->vc;
   	}
   	$response = new stdClass();
   	$response->chart = (object)['type' => 'line'];
    $response->title = (object)['text' => ''];
    $response->subtitle = (object)['text' => ''];
    $response->xAxis = (object)['categories' => $list_coursename];
    $response->yAxis = (object)['title' => (object)['text' => get_string('numberstudent', 'local_newsvnr')]];
    $response->credits = (object)['enabled' => false];
    $response->plotOptions = (object)['line' => (object)['dataLabels' => (object)['enabled' => true], 'enableMouseTracking' => true]];
    $response->series = [(object)['name' => get_string('numberviewcourse', 'local_newsvnr'),'data' => $list_viewcount,'color' => '#1120f3']];
   	
	echo json_encode($response,JSON_UNESCAPED_UNICODE);
}

if($action == "viewcount_chart") {
	$courseid = isset($_GET['courseid']) ? $_GET['courseid'] : "";
	$params = [];
	$wheresql = '';
	$srt_courseid = get_list_courseid_by_teacher($USER->id);
	if($courseid) {
		$wheresql .= "lsl.courseid = :courseid AND lsl.action = 'viewed'";
		$params['courseid'] = $courseid;
	} else {
		$wheresql = "courseid IN($srt_courseid) AND lsl.action = 'viewed'";
	}
	if($date != '') {
		$date = json_decode($date);
		$wheresql .= "AND lsl.timecreated BETWEEN :start AND :end";
		$params['start'] = $date->start;
		$params['end'] = $date->end;
	}
	$sql = "SELECT COUNT(lsl.id) AS vc,lsl.courseid, c.fullname
			FROM mdl_logstore_standard_log lsl
			 	LEFT JOIN mdl_course c ON  lsl.courseid = c.id 
			WHERE $wheresql
			GROUP BY lsl.courseid,c.fullname";
	$record = $DB->get_records_sql($sql, $params);
	$list_coursename = array();
	$list_course_temp = array();
	$list_viewcount = array();
   	foreach ($record as $value) {
		$list_course_temp[] = $value->courseid;
   		$list_coursename[] = $value->fullname;
   		$list_viewcount[] = (int)$value->vc - 1;
	}

	$list_course = explode(',', $srt_courseid);
	$compare = array_diff($list_course, $list_course_temp);
	if($compare) {
		foreach($compare as $course) {
			$list_coursename[] = $DB->get_field('course', 'fullname', ['id' => $course]);
			$list_viewcount[] = 0;
		}
	}
   	$response = new stdClass();
   	$response->list_coursename = $list_coursename;
   	$response->list_viewcount = $list_viewcount;
	echo json_encode($response,JSON_UNESCAPED_UNICODE);
}

//Báo cáo học tập
if($action == "gradereport_chart") {
	$courseid = optional_param('courseid', 0, PARAM_INT);
	$lastcourseid = optional_param('lastcourseid', 0, PARAM_INT);
	$response = new stdClass;
	$sql = "
			SELECT 
				COUNT(DISTINCT gg.userid) AS grade_total,
				(SELECT COUNT(*) 
					FROM mdl_grade_grades gg JOIN mdl_grade_items gi ON gi.id=gg.itemid JOIN mdl_user u ON gg.userid = u.id JOIN mdl_course_completion_criteria ccc ON ccc.course = gi.courseid
					WHERE gg.finalgrade IS NOT NULL
							AND gi.itemtype= 'course' 
							AND gi.courseid = ?
							AND (gg.finalgrade >= ccc.gradepass)
				) AS gradepass_total,
				(SELECT COUNT(*) 
					FROM mdl_grade_grades gg JOIN mdl_grade_items gi ON gi.id=gg.itemid JOIN mdl_user u ON gg.userid = u.id JOIN mdl_course_completion_criteria ccc ON ccc.course = gi.courseid
					WHERE gg.finalgrade IS NOT NULL
							AND gi.itemtype= 'course'
							AND gi.courseid = ?
							AND (gg.finalgrade < ccc.gradepass)
				) AS gradefailed_total
			FROM mdl_grade_grades gg JOIN mdl_grade_items gi ON gi.id=gg.itemid JOIN mdl_user u ON gg.userid = u.id JOIN mdl_course_completion_criteria ccc ON ccc.course = gi.courseid
			WHERE gi.itemtype= 'course' AND gi.courseid = ?
		";
	if($courseid) {
		$record = $DB->get_record_sql($sql,[$courseid,$courseid,$courseid]);
		$coursename = $DB->get_field('course', 'fullname', ['id' => $courseid]);	
	} else {
		$lastcourseid = $DB->get_record_sql('SELECT TOP 1 courseid from mdl_logstore_standard_log WHERE action = ?  AND target = ? AND userid = ? ORDER BY timecreated DESC', ['viewed', 'course', $USER->id]);
		$record = $DB->get_record_sql($sql,[$lastcourseid->courseid,$lastcourseid->courseid,$lastcourseid->courseid]);
		$coursename = $DB->get_field('course', 'fullname', ['id' => $lastcourseid->courseid]);	
	}
	

	// $record = $DB->get_record_sql($sql); 
	$data = [];
	if($record->grade_total != '0') {
		$gradepass_obj = new stdClass;
		$gradepass_obj->name = get_string('completed', 'local_newsvnr');
		$gradepass_obj->y = round(($record->gradepass_total/$record->grade_total)*100, 2);
		$gradefailed_obj = new stdClass;
		$gradefailed_obj->name = get_string('notcompleted', 'local_newsvnr');
		$gradefailed_obj->y = round(($record->gradefailed_total/$record->grade_total)*100, 2);
		$gradeorther_obj = new stdClass;
		$gradeorther_obj->name = get_string('orthers', 'local_newsvnr');
		$gradeorther_obj->y = round((100 - ($gradepass_obj->y + $gradefailed_obj->y)), 2);
		$data[] = $gradepass_obj;
		$data[] = $gradefailed_obj;
		$data[] = $gradeorther_obj;
	} else {
		$gradepass_obj = new stdClass;
		$gradepass_obj->name = get_string('completed', 'local_newsvnr');
		$gradepass_obj->y = 0;
		$gradefailed_obj = new stdClass;
		$gradefailed_obj->name = get_string('notcompleted', 'local_newsvnr');
		$gradefailed_obj->y = 0;
		$gradeorther_obj = new stdClass;
		$gradeorther_obj->name = get_string('orthers', 'local_newsvnr');
		$gradeorther_obj->y = 0;
		$data[] = $gradepass_obj;
		$data[] = $gradefailed_obj;
		$data[] = $gradeorther_obj;
	}
	$response->data = $data;
	$response->coursename = $coursename;
	
	echo json_encode($response,JSON_UNESCAPED_UNICODE);
}

//Load data chi tiết cho báo cáo học tập
if($action == "gradereport_detail") {
	$completed_course_status = optional_param('status', '', PARAM_RAW);
	$courseid = optional_param('courseid', 0, PARAM_INT);
	$lastcourseid = optional_param('courseid', 0, PARAM_INT);
	$pagesize = optional_param('pagesize',10, PARAM_INT);
	$pagetake = optional_param('take',0, PARAM_INT);
	$pageskip = optional_param('skip',0, PARAM_INT);
	$q = optional_param('q','', PARAM_RAW);
	$odersql = "";
	$wheresql = "";
	$data = [];
	if($q) {
		$wheresql = "WHERE fullname LIKE N'%$q%'";
	}
	if($pagetake == 0) {
		$ordersql = "RowNum";
	} else {
		$ordersql = "RowNum OFFSET $pageskip ROWS FETCH NEXT $pagetake ROWS only";
	}
	if($completed_course_status == get_string('completed', 'local_newsvnr')) {
		$where_subsql = "gg.finalgrade IS NOT NULL AND ccc.gradepass IS NOT NULL AND gi.itemtype = 'course' AND gg.finalgrade >= ccc.gradepass AND gi.courseid = ?";
	} else if($completed_course_status == "Không đạt") {
		$where_subsql = "gg.finalgrade IS NOT NULL AND ccc.gradepass IS NOT NULL AND gi.itemtype = 'course' AND gg.finalgrade < ccc.gradepass AND gi.courseid = ?";
	} else if($completed_course_status == "Khác"){
		$where_subsql = "gg.finalgrade IS NULL AND gi.itemtype = 'course' AND gi.courseid = ?";
	}
	$sql = "
			SELECT *, (SELECT COUNT (gg.userid)
						FROM mdl_grade_grades gg 
							LEFT JOIN mdl_grade_items gi ON gi.id=gg.itemid 
							LEFT JOIN mdl_user u ON gg.userid = u.id 
							LEFT JOIN mdl_course_completion_criteria ccc ON ccc.course = gi.courseid AND ccc.gradepass IS NOT NULL
						WHERE 
						$where_subsql
			) AS total
			FROM (
			    SELECT CONCAT(u.firstname, ' ', u.lastname) AS fullname, gg.finalgrade, ccc.gradepass, ROW_NUMBER() OVER (ORDER BY u.id) AS RowNum
					FROM mdl_grade_grades gg 
						LEFT JOIN mdl_grade_items gi ON gi.id=gg.itemid 
						LEFT JOIN mdl_user u ON gg.userid = u.id 
						LEFT JOIN mdl_course_completion_criteria ccc ON ccc.course = gi.courseid AND ccc.gradepass IS NOT NULL
					WHERE $where_subsql
				) AS Mydata
			$wheresql
			ORDER BY $ordersql";
	if($lastcourseid == 0) {
		$lastcourseid = $DB->get_record_sql('SELECT TOP 1 courseid from mdl_logstore_standard_log WHERE action = ?  AND target = ? AND userid = ? ORDER BY timecreated DESC', ['viewed', 'course', $USER->id]);
		$get_list = $DB->get_records_sql($sql, [$lastcourseid->courseid, $lastcourseid->courseid]);
	} else {
		$get_list = $DB->get_records_sql($sql, [$courseid, $courseid]);
	}
 	foreach ($get_list as $value) {
		$object = new stdclass;
		$object->fullname = $value->fullname;
		if($value->finalgrade >= $value->gradepass) {
			$object->status = get_string('completed', 'local_newsvnr');
		} else if($value->finalgrade < $value->gradepass) {
			$object->status = get_string('notcompleted', 'local_newsvnr');
		}
		if($value->finalgrade == null or $value->gradepass == null){
			$object->status = get_string('orthers', 'local_newsvnr');
		}

		$object->finalgrade = round($value->finalgrade,1);
		$object->total = $value->total;
		$data[] = $object;		
	}
	echo json_encode($data,JSON_UNESCAPED_UNICODE);
}

//Báo cáo điểm
if($action == "quizoverview_chart_vp") {
	$courseid = isset($_GET['courseid']) ? $_GET['courseid'] : "";
	$params = [];
	if($courseid) {
		$wheresql = "q.course = ?";
		$params = [$courseid];
	} else {
		$srt_courseid = get_list_courseid_by_teacher($USER->id);
		$wheresql = "q.course IN($srt_courseid)";
	}
	$sql = "
			SELECT q.course, SUM(qg.grade)/COUNT(qg.grade) AS gradescore
			FROM mdl_quiz q 
				JOIN mdl_quiz_grades qg ON q.id = qg.quiz 
            WHERE $wheresql
            GROUP BY q.course";
	$list_coursename_sql = "SELECT q.course,c.fullname FROM mdl_quiz q JOIN mdl_quiz_grades qg ON q.id = qg.quiz JOIN mdl_course c ON q.course = c.id WHERE $wheresql GROUP BY q.course,c.fullname";
	$record = $DB->get_records_sql($sql,$params);
	$list_coursename_ex = $DB->get_records_sql($list_coursename_sql,$params);
	$list_coursename = array();
	$list_quizname_parent = array();
   	$list_quiz = new stdClass();
   	$list_quizscore = array();
   	foreach ($list_coursename_ex as $cname) {
   		foreach ($record as $value) {
   			if($cname->course == $value->course) {
   				$list_coursename[] = $cname->fullname;
   				$list_quizscore[] = round((float)$value->gradescore,2);
   			}
   		}
   	}
   	$list_quiz->name = 'Điểm trung bình khoá';
   	$list_quiz->data = $list_quizscore;
   	$list_quizname_parent[] = $list_quiz;
   	
   	$response = new stdClass();
   	$response->chart = (object)['type' => 'bar'];
   	$response->title = (object)['text' => ''];
   	$response->subtitle = (object)['text' => ''];
   	$response->xAxis = (object)['categories' => $list_coursename];
   	$response->yAxis = (object)['min' => 0, 'title' => (object)['text' => get_string('avgscore', 'local_newsvnr'), 'align' => 'high'], 'labels' => (object)['overflow' => 'justify']];
   	$response->tooltip = (object)['valueSuffix' => ' Điểm'];
   	$response->plotOptions = (object)['bar' => (object)['dataLabels' => (object)['enabled' => true]]];
   	$response->legend = (object)['borderWidth' => 1, 'backgroundColor' => '#FFFFFF', 'shadow' => true];
   	$response->credits = (object)['enabled' => false];
   	$response->series = $list_quizname_parent;
   	
	echo json_encode($response,JSON_UNESCAPED_UNICODE);
}

if($action == "quizoverview_chart") {
	$courseid = isset($_GET['courseid']) ? $_GET['courseid'] : "";
	$params = [];
	if($courseid) {
		$wheresql = "q.course = ?";
		$params = [$courseid];
	} else {
		$srt_courseid = get_list_courseid_by_teacher($USER->id);
		$wheresql = "q.course IN($srt_courseid)";
	}
	$sql = "
			SELECT q.course, SUM(qg.grade)/COUNT(qg.grade) AS gradescore
			FROM mdl_quiz q 
				JOIN mdl_quiz_grades qg ON q.id = qg.quiz 
            WHERE $wheresql
            GROUP BY q.course";
	$list_coursename_sql = "SELECT q.course,c.fullname FROM mdl_quiz q JOIN mdl_quiz_grades qg ON q.id = qg.quiz JOIN mdl_course c ON q.course = c.id WHERE $wheresql GROUP BY q.course,c.fullname";
	$record = $DB->get_records_sql($sql,$params);
	$list_coursename_ex = $DB->get_records_sql($list_coursename_sql,$params);
	$list_coursename = array();
	$list_quizname_parent = array();
   	$list_quiz = new stdClass();
   	$list_quizscore = array();
   	foreach ($list_coursename_ex as $cname) {
   		foreach ($record as $value) {
   			if($cname->course == $value->course) {
   				$list_coursename[] = $cname->fullname;
   				$list_quizscore[] = round((float)$value->gradescore,2);
   			}
   		}
   	}
   	$list_quiz->name = get_string('avgcourse', 'local_newsvnr');
   	$list_quiz->data = $list_quizscore;
   	$list_quizname_parent[] = $list_quiz;
   	
   	$response = new stdClass();
   	// $response->chart = (object)['type' => 'bar'];
   	// $response->title = (object)['text' => ''];
   	// $response->subtitle = (object)['text' => ''];
   	// $response->xAxis = (object)['categories' => $list_coursename];
   	// $response->yAxis = (object)['min' => 0, 'title' => (object)['text' => 'Điểm trung bình', 'align' => 'high'], 'labels' => (object)['overflow' => 'justify']];
   	// $response->tooltip = (object)['valueSuffix' => ' Điểm'];
   	// $response->plotOptions = (object)['bar' => (object)['dataLabels' => (object)['enabled' => true]]];
   	// $response->legend = (object)['borderWidth' => 1, 'backgroundColor' => '#FFFFFF', 'shadow' => true];
   	// $response->credits = (object)['enabled' => false];
   	// $response->series = $list_quizname_parent;
   	$response->list_coursename = $list_coursename;
   	$response->list_quizname_parent = $list_quizname_parent;
	echo json_encode($response,JSON_UNESCAPED_UNICODE);
}
//Get dữ liệu cho block xu hướng ghi dnah
if($action == 'joincourse_grid') {
	$pagesize = optional_param('pagesize',10, PARAM_INT);
	$pagetake = optional_param('take',0, PARAM_INT);
	$pageskip = optional_param('skip',0, PARAM_INT);
	$q = optional_param('q','', PARAM_RAW);
	$courseid = optional_param('courseid',0, PARAM_INT);
	$odersql = "";
	$wheresql = "";
	if($q) {
		$wheresql = "WHERE fullnamet LIKE N'%$q%'";
	}
	if($pagetake == 0) {
		$ordersql = "RowNum";
	} else {
		$ordersql = "RowNum OFFSET $pageskip ROWS FETCH NEXT $pagetake ROWS only";
	}
	$sql = "
			SELECT *, (SELECT COUNT(ra.id) FROM mdl_role_assignments AS ra
                    JOIN mdl_user AS u ON u.id= ra.userid
                    JOIN mdl_user_enrolments AS ue ON ue.userid=u.id
                    JOIN mdl_enrol AS e ON e.id=ue.enrolid
                    JOIN mdl_course AS c ON c.id=e.courseid
                    JOIN mdl_context AS ct ON ct.id=ra.contextid AND ct.instanceid= c.id
                    JOIN mdl_role AS r ON r.id= ra.roleid
                    WHERE c.id=? AND ra.roleid=5) AS total FROM (
			SELECT concat(u.firstname,' ',u.lAStname) AS fullnamet,u.id, e.enrol, e.timecreated, ROW_NUMBER() OVER (ORDER BY u.id) AS RowNum
                    FROM mdl_role_ASsignments AS ra
                    JOIN mdl_user AS u ON u.id= ra.userid
                    JOIN mdl_user_enrolments AS ue ON ue.userid=u.id
                    JOIN mdl_enrol AS e ON e.id=ue.enrolid
                    JOIN mdl_course AS c ON c.id=e.courseid
                    JOIN mdl_context AS ct ON ct.id=ra.contextid AND ct.instanceid= c.id
                    JOIN mdl_role AS r ON r.id= ra.roleid
                    WHERE c.id=? AND ra.roleid=5
					GROUP BY u.id, u.firstname, u.lastname, e.enrol, e.timecreated) AS MyData
			$wheresql
			ORDER BY $ordersql";
	$get_list = $DB->get_records_sql($sql,[$courseid,$courseid]);
	$data = [];
	foreach ($get_list as $value) {
		$object = new stdclass;
		$object->fullnamet = $value->fullnamet;		
		$object->timecreated = convertunixtime('l, d m Y',$value->timecreated);
		$object->enrol = $value->enrol;
		$object->total = $value->total;
		$data[] = $object;		
	}
	echo json_encode($data,JSON_UNESCAPED_UNICODE);
}

// Get dữ liệu cho điểm trung bình khóa học
if($action == "courseavg_chart") {
	$courses = get_list_courseinfo_by_teacher($USER->id);
	$series = [];
	$categories = [];
   	foreach($courses as $course) {
   		$categories[] = $course->fullname;
		$avg = get_course_grade_avg($course->id)[0]->courseavg;
		if($avg == '-') {
			$series[] = 0;
		} else {
			if(is_numeric($avg))
				$series[] = $avg;
			else
				$series[] = (float)str_replace(',', '.', $avg);
		}
   	}
   	$response = new stdClass();
   	$response->categories = $categories;
   	$response->series = $series;
	echo json_encode($response,JSON_UNESCAPED_UNICODE);
}

// -- End Get dữ liệu cho blocks chart -- \\

//Get dữ liệu cho grid api_managerment
if($action == 'api_managerment') {
	$pagesize = optional_param('pagesize',10, PARAM_INT);
	$pagetake = optional_param('take',0, PARAM_INT);
	$pageskip = optional_param('skip',0, PARAM_INT);
	$q = optional_param('q','', PARAM_RAW);
	$odersql = "";
	$wheresql = "";
	if($q) {
		$wheresql = "WHERE functionapi LIKE N'%$q%'";
	}
	if($pagetake == 0) {
		$ordersql = "RowNum";
	} else {
		$ordersql = "RowNum OFFSET $pageskip ROWS FETCH NEXT $pagetake ROWS only";
	}
	$sql = "
			SELECT *, (SELECT COUNT(id) FROM {local_newsvnr_api}) AS total
			FROM (
			    SELECT *, ROW_NUMBER() OVER (ORDER BY id) AS RowNum
			    FROM {local_newsvnr_api}
			) AS Mydata
			$wheresql
			ORDER BY $ordersql";
	$get_list = $DB->get_records_sql($sql);
	$data = [];
	foreach ($get_list as $value) {
		$object = new stdclass;
		$buttons = array();
		// if($value->visible == 1) {
		// 	$buttons[] = html_writer::link('javascript:void(0)',
		// 	$OUTPUT->pix_icon('t/hide', get_string('hide')),
		// 	array('title' => get_string('hide'),'id' => $value->id, 'class' => 'hide-item','data-active' => 'orgcate_list','id' => $value->id,'onclick' => 'org_active('.$value->id.',0)'));	
		// } else {
		// 	$buttons[] = html_writer::link('javascript:void(0)',
		// 	$OUTPUT->pix_icon('t/show', get_string('show')),
		// 	array('title' => get_string('show'),'id' => $value->id, 'class' => 'show-item','data-active' => 'orgcate_list','id' => $value->id,'onclick' => 'org_active('.$value->id.',1)'));	
		// }
		$buttons[] = html_writer::link(new moodle_url('/local/newsvnr/api_management_edit.php',array('id' => $value->id)),
		$OUTPUT->pix_icon('t/edit', get_string('edit')),
		array('title' => get_string('edit')));
		$buttons[] = html_writer::link('javascript:void(0)',
		$OUTPUT->pix_icon('t/delete', get_string('delete')),
		array('title' => get_string('delete'),'id' => $value->id, 'class' => 'delete-item','data-section' => 'apidelete','id' => $value->id,'onclick' => 'api_delete('.$value->id.')'));
	
		if($value->visible){
			$buttons[] = html_writer::link('javascript:void(0)',
			$OUTPUT->pix_icon('t/hide',get_string('show')),
			array('title' => get_string('show'),'id' => $value->id,'class' => 'item_'.$value->id.'','action-status' => 'hide','onclick' => 'api_update('.$value->id.')' ));
		}
		else{
			$buttons[] = html_writer::link('javascript:void(0)',
			$OUTPUT->pix_icon('t/show',get_string('hide')),
			array('title' => get_string('hide'),'id' => $value->id,'class' => 'item_'.$value->id.'','action-status' => 'show','onclick' => 'api_update('.$value->id.')' ));
		}
		$showbuttons = implode(' ', $buttons);
		$object->listbtn = $showbuttons;
		$object->functionapi = $value->functionapi;		
		$object->url = $value->url;
		$object->method = $value->method;
		$object->contenttype = $value->contenttype;
		$object->description = $value->description;
		$object->total = $value->total;
		$data[] = $object;		
	}
	echo json_encode($data,JSON_UNESCAPED_UNICODE);
}


if($action == 'coursesetup_management') {
	$pagesize = optional_param('pagesize',10, PARAM_INT);
	$pagetake = optional_param('take',0, PARAM_INT);
	$pageskip = optional_param('skip',0, PARAM_INT);
	$q = optional_param('q','', PARAM_RAW);
	$odersql = "";
	$wheresql = "";
	if($q) {
		$wheresql = "WHERE fullname LIKE N'%$q%'";
	}
	if($pagetake == 0) {
		$ordersql = "RowNum";
	} else {
		$ordersql = "RowNum OFFSET $pageskip ROWS FETCH NEXT $pagetake ROWS only";
	}
	$sql = "
			SELECT *, (SELECT COUNT(id) FROM {course_setup} $wheresql) AS total
			FROM (
			    SELECT *, ROW_NUMBER() OVER (ORDER BY id) AS RowNum
			    FROM {course_setup}
			) AS Mydata
			$wheresql
			ORDER BY $ordersql";
	$get_list = $DB->get_records_sql($sql);
	$data = [];

	foreach ($get_list as $value) {
		$sql_category = 'SELECT * FROM {course_categories} 
					WHERE id = ? '; 
		$get_category = $DB->get_records_sql($sql_category, [$value->category]);
		$object = new stdclass;
		$buttons = array();
		$buttons[] = html_writer::link(new moodle_url('/course/coursesetup.php',array('id' => $value->id)),
		$OUTPUT->pix_icon('t/edit', get_string('edit')),
		array('title' => get_string('edit')));
		if($value->visible == 1) {
			$buttons[] = html_writer::link('javascript:void(0)',
			$OUTPUT->pix_icon('t/hide', get_string('hide')),
			array('title' => get_string('hide'),'id' => $value->id, 'class' => 'hide-item','data-active' => 'coursesetup_hide','id' => $value->id,'onclick' => 'coursesetup_hide('.$value->id.',0)'));	
		} else {
			$buttons[] = html_writer::link('javascript:void(0)',
			$OUTPUT->pix_icon('t/show', get_string('show')),
			array('title' => get_string('show'),'id' => $value->id, 'class' => 'show-item','data-active' => 'coursesetup_hide','id' => $value->id,'onclick' => 'coursesetup_hide('.$value->id.',1)'));	
		}
		$buttons[] = html_writer::link('javascript:void(0)',
		$OUTPUT->pix_icon('t/delete', get_string('delete')),
		array('title' => get_string('delete'),'id' => $value->id, 'class' => 'delete-item','id' => $value->id,'onclick' => 'coursesetup_delete('.$value->id.')'));
		$showbuttons = implode(' ', $buttons);
		$object->listbtn = $showbuttons;
		$object->fullname = $value->fullname;		
		$object->shortname = $value->shortname;
		foreach ($get_category as $value2) {
			$object->category = $value2->name;
		}
		$object->total = $value->total;
		$object->description = $value->description;
		$data[] = $object;		
	}
	echo json_encode($data,JSON_UNESCAPED_UNICODE);
}

if($action == 'search_category') {
	$user = $DB->get_record('user',['id' => $USER->id]);
	$wheresql = '';
	if($CFG->sitetype == MOODLE_EDUCATION) {
		if($user->divisionid && !is_siteadmin()) {
			$wheresql .= "JOIN {division_categories} dc on dc.coursecategorysid = cc.id";
		}
	} 
	$sql = "SELECT DISTINCT cc.name FROM {course_categories} cc $wheresql WHERE cc.visible = 1";
	$get_list = $DB->get_records_sql($sql);
	$data = [];
	foreach ($get_list as $value) {
		$object = new stdclass;
		$object->name = $value->name;
		$data[] = $object;
	}
	echo json_encode($data,JSON_UNESCAPED_UNICODE);
}

if($action == 'search_course') {
	$user = $DB->get_record('user',['id' => $USER->id]);
	$wheresql = '';
	if($CFG->sitetype == MOODLE_EDUCATION) {
		if($user->divisionid && !is_siteadmin()) {
			$wheresql .= "AND c.divisionid = $user->divisionid";
		}
	}
	$sql = "SELECT c.id,c.fullname,cc.name FROM {course} c
			JOIN {course_categories} cc on c.category = cc.id
			WHERE c.visible = 1 $wheresql";
	$get_list = $DB->get_records_sql($sql);
	$data = [];
	foreach ($get_list as $value) {
		$object = new stdclass;
		$object->fullname = $value->fullname;
		$object->name = $value->name;
		$object->courseid = $value->id;
		$data[] = $object;
	}
	echo json_encode($data,JSON_UNESCAPED_UNICODE);
}

if($action == 'search_teacher') {
	$sql = "SELECT CONCAT(u.firstname,' ', u.lastname) fullnamet,u.id
            FROM {role_assignments} ra
                JOIN {user} u ON ra.userid = u.id
                JOIN {user_enrolments} ue ON u.id = ue.userid 
                JOIN {enrol} enr ON ue.enrolid = enr.id
                JOIN {course} c ON enr.courseid = c.id
                JOIN {context} ct ON ct.id = ra.contextid AND ct.instanceid = c.id
                JOIN {role} r ON ra.roleid = r.id
                JOIN {course_categories} cc ON c.category = cc.id
            WHERE c.visible = 1 AND cc.visible = 1 AND ra.roleid = 3";
	$get_list = $DB->get_records_sql($sql);
	$data = [];
	foreach ($get_list as $value) {
		$object = new stdclass;
		$object->fullnamet = $value->fullnamet;
		$data[] = $object;
	}
	echo json_encode($data,JSON_UNESCAPED_UNICODE);
}

if($action == 'search_coursesetup') {
	$sql = "SELECT *
			FROM {course_setup}";
	$get_list = $DB->get_records_sql($sql);
	$data = [];
	foreach ($get_list as $value) {
		$object = new stdclass;
		$object->coursesetup = $value->fullname;
		$data[] = $object;
	}
	echo json_encode($data,JSON_UNESCAPED_UNICODE);
}


if($action == "library_folder") {
	$sql = "SELECT * FROM {library_folder}";
	$get_list = $DB->get_records_sql($sql);
	$data = [];
	foreach ($get_list as $value) {
		$object = new stdclass;
		$object->name = $value->name;
		$object->parent = $value->parent;
		$data[] = $object;
	}
	echo json_encode($data,JSON_UNESCAPED_UNICODE);
}

if($action == "search_module_library") {
	$data = [];
	$folderid = optional_param('folderid',0, PARAM_INT);
	if($folderid == 0) {
		$modulebyfolder  = $DB->get_records('library_module');
	} else {
		$modulebyfolder  = $DB->get_records('library_module',['folderid' => $folderid]);
	}
	foreach ($modulebyfolder as $value) {
		$alldatamodule = $DB->get_record_sql("SELECT rs.name
 											FROM {course_modules} cm
 											JOIN mdl_$value->moduletype rs on cm.instance = rs.id
 											WHERE cm.id = :coursemoduleid",['coursemoduleid' => $value->coursemoduleid]);
		$object = new stdclass;
		$object->name = $alldatamodule->name;
		$data[] = $object;
	}
	echo json_encode($data,JSON_UNESCAPED_UNICODE);
}
if($action == "logreviewapi") {
	$pagesize = optional_param('pagesize',10, PARAM_INT);
	$pagetake = optional_param('take',0, PARAM_INT);
	$pageskip = optional_param('skip',0, PARAM_INT);
	$q = optional_param('q','', PARAM_RAW);
	$odersql = "";
	$wheresql = "";
	if($q) {
		$wheresql = "WHERE action LIKE N'%$q%'";
	}
	if($pagetake == 0) {
		$ordersql = "time DESC";
	} else {
		$ordersql = "time DESC OFFSET $pageskip ROWS FETCH NEXT $pagetake ROWS only";
	}
	$sql = "
			SELECT *, (SELECT COUNT(id) FROM {log}) AS total
			FROM (
			    SELECT *, ROW_NUMBER() OVER (ORDER BY id) AS RowNum
			    FROM {log}
			) AS Mydata
			$wheresql
			ORDER BY $ordersql";
	$get_list = $DB->get_records_sql($sql);
	$data = [];
	foreach ($get_list as $value) {
		$object = new stdClass();
		$dt = new DateTime('@'.$value->time.'');
		$dt->setTimeZone(new DateTimeZone('Asia/Ho_Chi_Minh'));
		$object->time = $dt->format('F j, Y, g:i a');
		$object->action = $value->action;
		$object->url = $value->url;
		$object->info = $value->info;
		$object->total = $value->total;
		$data[] = $object;
	}
	echo json_encode($data,JSON_UNESCAPED_UNICODE);
}
die();