<?php

require_once('config.php');
require_once("$CFG->dirroot/course/lib.php");
require_once("$CFG->dirroot/mod/page/lib.php");
require_once("$CFG->dirroot/mod/page/locallib.php");

$servername = "localhost";
$username = "root";
$password = "";
$database = "giaoxu";
$port = "3306";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $database, $port);
// $list_mucluc = 'vkgh, ghcg, tngh, tlnc';
// $list_mucluc = 'tsgl, phungvu, mucvu, truyengiao';
// $list_mucluc = 'snlc, loisong, gygl, hct';
// $list_mucluc = 'sdgd, cnsn, cnc, sttg';
// $list_mucluc = 'tcvn, pank, tho, ebook';
$lists = explode(", ", $list_mucluc);
foreach($lists as $listname) {
	$get_get_menu_id = 'SELECT * FROM vct_menu WHERE menuUrl IN ("'.$listname.'") AND menuAlt = "menu" ORDER BY menuUrl ASC LIMIT 1';
	$sql_get_menu_name = 'SELECT menuId,parentId,convert(cast(convert(menuTitle using latin1) as binary) using utf8) menuTitle FROM vct_menu WHERE menuUrl IN ("'.$listname.'") ORDER BY menuUrl ASC LIMIT 1';
	$sql_get_children_menu = 'SELECT menuId,parentId,convert(cast(convert(menuTitle using latin1) as binary) using utf8) menuTitle FROM vct_menu WHERE menuUrl IN ("'.$listname.'") AND menuAlt IS NULL ORDER BY menuUrl ASC';
	$result1 = $conn->query($sql_get_menu_name);
	$result2 = $conn->query($sql_get_children_menu);
	$result3 = $conn->query($get_get_menu_id);
	$datas = [];
	if ($result3->num_rows > 0) {
		// output data of each row
		while ($row = $result3->fetch_assoc()) {
			$getMainMenuId = $row['menuId'];
		}
	} else {
		echo "0 results 3";
	}
	if ($result1->num_rows > 0) {
		// output data of each row
		while ($row = $result1->fetch_assoc()) {
			$data = new stdClass;
			if ($DB->record_exists('course_categories', ['idnumber' => $getMainMenuId]))
				continue;
			if ($row['parentId'] != 0) {
				$get_parentId = $DB->get_record('course_categories', ['idnumber' => $getMainMenuId]);
				if ($get_parentId) {
					$row['parentId'] = $get_parentId->id;
				}
			}
			$data->idnumber = $getMainMenuId;
			$data->name = $row['menuTitle'];
			$data->parent = 4;
			$create = create_category($data);
		}
	} else {
		echo "0 results 1";
	}
	if ($result2->num_rows > 0) {
		// output data of each row
		while ($row = $result2->fetch_assoc()) {
			$data = new stdClass;
			if ($row['parentId'] != 0) {
				$get_parentId = $DB->get_record('course_categories', ['idnumber' => $row['parentId']]);
				if ($get_parentId) {
					$row['parentId'] = $get_parentId->id;
				}
			}
			$data->idnumber = $row['menuId'];
			$data->name = $row['menuTitle'];
			$data->parent = $row['parentId'];
			$create = create_category($data);
			$course = new stdClass;
			$course->data = new stdClass;
			$course->data->fullname = $row['menuTitle'];
			$course->data->shortname = $row['menuTitle'] . '-' . $row['menuId'];
			$course->data->category = $DB->get_field('course_categories', 'id', ['idnumber' => $data->idnumber]);
			$course->data->idnumber = $row['menuId'];
			$course->data->format = 'topics';
			$course->data->showgrades = 1;
			$course->data->numsections = 1;
			$course->data->newsitems = 10;
			$course->data->showreports = 1;
			$course->data->summary = '';
			$course->data->summaryformat = FORMAT_HTML;
			$course->data->lang = 'vi';
			$getCourseId = $DB->get_field('course', 'id',['shortname' => $row['menuTitle'] . '-' . $row['menuId']]);
			if (!$getCourseId) {
				$newcourse = create_course($course->data);
				// Mở chức năng khóa học guest
				$instances = enrol_get_instances($newcourse->id, false);
				$plugins   = enrol_get_plugins(false);
				foreach($instances as $instance) {
					if($instance->enrol == 'guest') {
						$plugin = $plugins[$instance->enrol];
						if ($plugin->can_hide_show_instance($instance)) {
							if ($instance->status != ENROL_INSTANCE_ENABLED) {
								$plugin->update_status($instance, ENROL_INSTANCE_ENABLED);
							}
						}
					}
				}
				$menuId = $row['menuId'];
				$sql_get_children_topic = 'SELECT page.pageId, convert(cast(convert(page.pageTitle using latin1) as binary) using utf8) pageTitle, convert(cast(convert(page.pageContent using latin1) as binary) using utf8) pageContent FROM vct_page as page LEFT JOIN vct_page_category pagecate ON page.pageId = pagecate.objectId WHERE pagecate.module = "'.$listname.'" AND relId = "'.$menuId.'"';
				$result4 = $conn->query($sql_get_children_topic);
				if ($result4->num_rows > 0) {
					// output data of each row
					while ($row = $result4->fetch_assoc()) {
						$modinfo = new stdClass;
						$modinfo->name = trim($row['pageTitle']);
						$modinfo->content = htmlspecialchars_decode(trim($row['pageContent']));
						$modinfo->modulename = 'page';
						$modinfo->course = $newcourse->id;
						$modinfo->visible = 1;
						$modinfo->section = 1;
						$modinfo->display = 5;
						$modinfo->printheading = '1';
						$modinfo->printintro = '0';
						$modinfo->printlastmodified = '1';
						$modinfo->introeditor = ['text' => '', 'format' => '1', 'itemid' => 0];
						$modinfo->contentformat = 1;
						// $modinfo->intoformat = 1;
						$pageid = $DB->get_field('page', 'id', ['course' => $getCourseId, 'name' => trim($row['pageTitle'])]);
						if($pageid) {
							$cm = get_coursemodule_from_instance('page', $pageid);
							$modinfo->id = $pageid;
							$modinfo->revision = 0;
							$modinfo->page = ['text' => htmlspecialchars_decode(trim($row['pageContent'])),'format' => '1', 'itemid' => 0];
							$modinfo->coursemodule = $cm->id;
							$modulepage = update_module($modinfo);
						} else {
							$modulepage = create_module($modinfo);
						}
					}
				} else {
					echo "0 results 4";
				}
			} else {
				$menuId = $row['menuId'];
				$sql_get_children_topic = 'SELECT page.pageId, convert(cast(convert(page.pageTitle using latin1) as binary) using utf8) pageTitle, convert(cast(convert(page.pageContent using latin1) as binary) using utf8) pageContent FROM vct_page as page LEFT JOIN vct_page_category pagecate ON page.pageId = pagecate.objectId WHERE pagecate.module = "'.$listname.'" AND relId = "'.$menuId.'"';
				$result4 = $conn->query($sql_get_children_topic);
				if ($result4->num_rows > 0) {
					// output data of each row
					while ($row = $result4->fetch_assoc()) {
						$modinfo = new stdClass;
						$modinfo->name = trim($row['pageTitle']);
						$modinfo->content = htmlspecialchars_decode(trim($row['pageContent']));
						$modinfo->modulename = 'page';
						$modinfo->course = $getCourseId;
						$modinfo->section = 1;
						$modinfo->visible = 1;
						$modinfo->display = 5;
						$modinfo->printheading = '1';
						$modinfo->printintro = '0';
						$modinfo->printlastmodified = '1';
						$modinfo->introeditor = ['text' => '', 'format' => '1', 'itemid' => 0];
						$modinfo->contentformat = 1;
						$modinfo->intoformat = 1;
						$pageid = $DB->get_field('page', 'id', ['course' => $getCourseId, 'name' => trim($row['pageTitle'])]);
						if($pageid) {
							$cm = get_coursemodule_from_instance('page', $pageid);
							$modinfo->id = $pageid;
							$modinfo->revision = 0;
							$modinfo->page = ['text' => htmlspecialchars_decode(trim($row['pageContent'])),'format' => '1', 'itemid' => 0];
							$modinfo->coursemodule = $cm->id;
							$modulepage = update_module($modinfo);
						} else {
							$modulepage = create_module($modinfo);
						}
					}
				}
			}
		}
	} else {
		echo "0 results 2";
	}
	echo "</br> Hoàn thành index: " . $listname;
}
$conn->close();


function create_category(object $data) {
	global $DB;
	$table = 'course_categories';
	$resp = new stdClass;
	$coursecategoryid = $DB->get_field('course_categories', 'id', ['name' => $data->name, 'idnumber' => $data->idnumber]);
	if ($coursecategoryid) {
		$coursecat = core_course_category::get($coursecategoryid, MUST_EXIST, true);
		// if (!empty($data->idnumber) && $coursecat->idnumber !== $data->idnumber) {
		// 	$check_code = $DB->get_record($table, ['idnumber' => $data->idnumber], 'idnumber');
		// 	if ($check_code) {
		// 		$check_code = $check_code->idnumber;
		// 		$resp->data['code'] = "Mã danh mục khoá '$check_code' đã tồn tại";
		// 	}
		// }


		// if ($data->parent == '') {
		// 	$data->parent = 0;
		// } else {
		// 	$check_parentname = $DB->get_field($table, 'id', ['name' => $data->parent]);
		// 	if ($check_parentname) {
		// 		$data->parent = $check_parentname;
		// 	} else {
		// 		$check_parentname = $data->parent;
		// 		$resp->data['parentname'] = "Tên danh mục khoá cha '$check_parentname' không tồn tại";
		// 	}
		// }

		if (empty($resp->data)) {

			if (isset($coursecat)) {
				if ((int)$data->parent !== (int)$coursecat->parent && !$coursecat->can_change_parent($data->parent)) {
					print_error('cannotmovecategory');
				}
				$coursecat->update($data);
				$resp->error = false;
				$resp->message['info'] = "Chỉnh sửa thành công";
				$resp->data[] = $data;
			} else {
				$resp->error = true;
				$resp->data->message['info'] = "Thêm thất bại";
			}
		} else {
			$resp->error = true;
		}
	} else {
		// if (!empty($data->idnumber)) {
		// 	$check_code = $DB->get_record($table, ['idnumber' => $data->idnumber], 'idnumber');
		// 	if ($check_code) {
		// 		$check_code = $check_code->idnumber;
		// 		$resp->data['code'] = "Mã danh mục khoá '$check_code' đã tồn tại";
		// 	}
		// }

		// if ($data->parent == '') {
		// 	$data->parent = 0;
		// } else {
		// 	$check_parentname = $DB->get_field($table, 'id', ['name' => $data->parent]);
		// 	if ($check_parentname) {
		// 		$data->parent = $check_parentname;
		// 	} else {
		// 		$check_parentname = $data->parent;
		// 		$resp->data['parentname'] = "Tên danh mục khoá cha '$check_parentname' không tồn tại";
		// 	}
		// }
		if (empty($resp->data)) {
			$success = core_course_category::create($data);
			if ($success) {
				$resp->error = false;
				$resp->message['info'] = "Thêm thành công";
				$resp->data[] = $data;
			} else {
				$resp->error = true;
				$resp->message['info'] = "Thêm thất bại";
			}
		} else {
			$resp->error = true;
		}
	}
	return json_encode($data);
}

function create_course_with_module(object $datas)
{
	global $DB;
	$table = 'course';
	$resp = new stdClass;
	$data = new stdClass;
	$data->fullname = $datas->fullname;
	$data->shortname = $datas->shortname;
	$data->categorycode = $datas->categorycode;
	$data->pagename = $datas->pagename;
	$data->pagecode = $datas->pagecode;
	$data->pageintro = $datas->pageintro;
	$data->idnumber = '';
	$data->format = 'topcoll';
	$data->showgrades = 1;
	$data->newsitems = 10;
	$data->visible = 1;
	$data->showreports = 1;
	$data->summary = '';
	$data->summaryformat = FORMAT_HTML;
	$data->lang = 'vi';
	$modarr = [];
	$arrtempb = []; // Lồng array để trả về kiểu dữ liệu là 1 arrya - object 1 : 1
	$courseid = $DB->get_field('course', 'id', ['fullname' => $data->fullname, 'shortname' => $data->shortname]);

	if ($courseid) {
		$data->id = $courseid;

		if (empty($resp->data)) {

			try {
				update_course($data);
				if ($courseid) {
					$pagenamearr = $data->pagename;
					$pageintroarr = $data->pageintro;
					$modinfo = new stdClass;
					$modinfo->name = trim($pagenamearr);
					$modinfo->modulename = 'page';
					$modinfo->course = $courseid;
					$modinfo->section = 1;
					$modinfo->visible = 1;
					$modinfo->display = 5;
					$modinfo->printheading = '1';
					$modinfo->printintro = '0';
					$modinfo->printlastmodified = '1';
					$modinfo->introeditor = ['text' => '', 'format' => '1', 'itemid' => rand(1, 999999999)];
					$pageid = $DB->get_field('page', 'id', ['course' => $courseid, 'name' => trim($pagenamearr)]);
					if ($pageid) {
						$cm = get_coursemodule_from_instance('page', $pageid);
						$modinfo->id = $pageid;
						$modinfo->revision = 0;
						$modinfo->page = ['text' => $pageintroarr, 'format' => '1', 'itemid' => 0];
						$modinfo->coursemodule = $cm->id;
						$modulepage = update_module($modinfo);
					} else {
						$modinfo->content = $pageintroarr;
						$modinfo->intoformat = 1;
						$modulepage = create_module($modinfo);
					}

					$modarr['trackclassid'] = $modulepage->coursemodule;
				} else {
					$modarr['trackclassid'] = 'null';
				}
				$resp->error = false;
				$resp->message['info'] = "Chỉnh sửa thành công";
				$resp->classid = $courseid;
				$resp->data[] = $arrtempb;
			} catch (Exception $e) {
				$resp->error = true;
				$error = $e->getMessage();
				$resp->data->message['info'] = "Chỉnh sửa thất bại với lỗi: $error";
			}
		} else {
			$resp->error = true;
		}
	} else {
		if ($data->categoryname and $data->categorycode) {
			$existing = $DB->get_field('course_categories', 'id', ['name' => $data->categoryname, 'idnumber' => $data->categorycode]);
			if ($existing) {
				$data->category = $existing;
			} else {
				$categoryname = $data->categoryname;
				$resp->data['categoryname'] = "Không tìm thấy tên '$categoryname' trong danh mục khoá học ";
			}
		} else {
			$resp->data['category'] = "Thiếu 'categoryname' hoặc 'categorycode";
		}

		if (!empty($data->shortname)) {
			$check_code = $DB->get_record($table, ['shortname' => $data->shortname], 'shortname');
			if ($check_code) {
				$check_code = $check_code->shortname;
				$resp->data['code'] = "Mã khoá học '$check_code' đã tồn tại!";
			}
		}
		if (empty($resp->data)) {

			try {
				$course = create_course($data);
				if ($course) {
					$pagenamearr = $data->pagename;
					$pageintroarr = $data->pageintro;
					$modinfo = new stdClass;
					$modinfo->name = trim($pagenamearr);
					$modinfo->content = trim($pageintroarr);
					$modinfo->modulename = 'page';
					$modinfo->course = $course->id;
					$modinfo->visible = 1;
					$modinfo->display = 5;
					$modinfo->printheading = '1';
					$modinfo->printintro = '0';
					$modinfo->printlastmodified = '1';
					$modinfo->introeditor = ['text' => '', 'format' => '1', 'itemid' => 0];
					$modinfo->contentformat = 1;
					$modinfo->intoformat = 1;
					$resp->error = false;
					$resp->message['info'] = "Tạo mới thành công";
				}
			} catch (Exception $e) {
				$resp->error = true;
				$error = $e->getMessage();
				$resp->data->message['info'] = "Tạo mới thất bại với lỗi: $error";
			}
		} else {
			$resp->error = true;
		}
	}
}
