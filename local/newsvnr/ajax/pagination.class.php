<?php
class PerPage {

	public $perpageCourseNews, $perpageCourse;
	
	function __construct() {

		$this->perpageCourseNews = 8;
		$this->perpageCourse = 12;

	}
	
	function getAllCourseNewsPageLinks($count,$href) {
		$output = '';
		if(!isset($_GET["page"])) $_GET["page"] = 1;

		if(!isset($_GET["courseid"])) $_GET["courseid"] = "";

		if($this->perpageCourseNews != 0)
			$pages  = ceil($count/$this->perpageCourseNews);

		if($pages>1) {

			if(!empty($_GET['courseid']))
			{
				if($_GET["page"] == 1) 
				$output = $output . '<span class="link first disabled">&#8810;</span><span class="link disabled">&#60;</span>';
				else	
					$output = $output . '<a class="link first" onclick="getresultCourseNews(\'' . $href . (1) . '&courseid='   . $_GET["courseid"] . '\')" >&#8810;</a><a class="link" onclick="getresultCourseNews(\'' . $href . ($_GET["page"]-1) .  '&courseid='   . $_GET["courseid"] . '\')" >&#60;</a>';
				
				
				if(($_GET["page"]-3)>0) {
					if($_GET["page"] == 1)
						$output = $output . '<span id=1 class="link current1">1</span>';
					else				
						$output = $output . '<a class="link" onclick="getresultCourseNews(\'' . $href . '1' . '&courseid='   . $_GET['courseid']  . '\')" >1</a>';
				}
				if(($_GET["page"]-3)>1) {
						$output = $output . '<span class="dot">...</span>';
				}
				
				for($i=($_GET["page"]-2); $i<=($_GET["page"]+2); $i++)	{
					if($i<1) continue;
					if($i>$pages) break;
					if($_GET["page"] == $i)
						$output = $output . '<span id='.$i.' class="link current1">'.$i.'</span>';
					else				
						$output = $output . '<a class="link" onclick="getresultCourseNews(\'' . $href . $i . '&courseid='   . $_GET["courseid"] . '\')" >'.$i.'</a>';
				}
				
				if(($pages-($_GET["page"]+2))>1) {
					$output = $output . '<span class="dot">...</span>';
				}
				if(($pages-($_GET["page"]+2))>0) {
					if($_GET["page"] == $pages)
						$output = $output . '<span id=' . ($pages) .' class="link current1">' . ($pages) .'</span>';
					else				
						$output = $output . '<a class="link" onclick="getresultCourseNews(\'' . $href .  ($pages) . '&courseid='   . $_GET["courseid"] .  '\')" >' . ($pages) .'</a>';
				}
				
				if($_GET["page"] < $pages)
					$output = $output . '<a  class="link" onclick="getresultCourseNews(\'' . $href . ($_GET["page"]+1) . '&courseid='   . $_GET["courseid"] . '\')" >></a><a  class="link" onclick="getresultCourseNews(\'' . $href . ($pages) .  '&courseid='   . $_GET["courseid"] . '\')" >&#8811;</a>';
				else				
					$output = $output . '<span class="link disabled">></span><span class="link disabled">&#8811;</span>';
			}

			else{

				if($_GET["page"] == 1) 
				$output = $output . '<span class="link first disabled">&#8810;</span><span class="link disabled">&#60;</span>';
				else	
					$output = $output . '<a class="link first" onclick="getresultCourseNews(\'' . $href . (1) . '\')" >&#8810;</a><a class="link" onclick="getresultCourseNews(\'' . $href . ($_GET["page"]-1) . '&courseid='   . $_GET["courseid"] . '\')" >&#60;</a>';
				
				
				if(($_GET["page"]-3)>0) {
					if($_GET["page"] == 1)
						$output = $output . '<span id=1 class="link current1">1</span>';
					else				
						$output = $output . '<a class="link" onclick="getresultCourseNews(\'' . $href . '1\')" >1</a>';
				}
				if(($_GET["page"]-3)>1) {
						$output = $output . '<span class="dot">...</span>';
				}
				
				for($i=($_GET["page"]-2); $i<=($_GET["page"]+2); $i++)	{
					if($i<1) continue;
					if($i>$pages) break;
					if($_GET["page"] == $i)
						$output = $output . '<span id='.$i.' class="link current1">'.$i.'</span>';
					else				
						$output = $output . '<a class="link" onclick="getresultCourseNews(\'' . $href . $i  . '\')" >'.$i.'</a>';
				}
				
				if(($pages-($_GET["page"]+2))>1) {
					$output = $output . '<span class="dot">...</span>';
				}
				if(($pages-($_GET["page"]+2))>0) {
					if($_GET["page"] == $pages)
						$output = $output . '<span id=' . ($pages) .' class="link current1">' . ($pages) .'</span>';
					else				
						$output = $output . '<a class="link" onclick="getresultCourseNews(\'' . $href .  ($pages) .'\')" >' . ($pages) .'</a>';
				}
				
				if($_GET["page"] < $pages)
					$output = $output . '<a  class="link" onclick="getresultCourseNews(\'' . $href . ($_GET["page"]+1) . '&courseid='   . $_GET["courseid"] . '\')" >></a><a  class="link" onclick="getresultCourseNews(\'' . $href . ($pages) . '&courseid='   . $_GET["courseid"] . '\')" >&#8811;</a>';
				else				
					$output = $output . '<span class="link disabled">></span><span class="link disabled">&#8811;</span>';
			}	
			
		}
		return $output;
	}





	function getAllCoursesPageLinks($count,$href) {
		$output = '';
		if(!isset($_GET["page"])) $_GET["page"] = 1;
		if($this->perpageCourse != 0)
			$pages  = ceil($count/$this->perpageCourse);
		if($pages>1) {
			if($_GET["page"] == 1) 
				$output = $output . '<span class="link first disabled"> << </span>';
			else	
				$output = $output . '<a class="link first" onclick="getresultCourse(\'' . $href . (1) . '\')" >&#8810;</a>';
			
			
			if(($_GET["page"]-3)>0) {
				if($_GET["page"] == 1)
					$output = $output . '<span id=1 class="link current1">1</span>';
				else				
					$output = $output . '<a class="link" onclick="getresultCourse(\'' . $href . '1\')" >1</a>';
			}
			if(($_GET["page"]-3)>1) {
					$output = $output . '<span class="dot">...</span>';
			}
			
			for($i=($_GET["page"]-2); $i<=($_GET["page"]+2); $i++)	{
				if($i<1) continue;
				if($i>$pages) break;
				if($_GET["page"] == $i)
					$output = $output . '<span id='.$i.' class="link current1">'.$i.'</span>';
				else				
					$output = $output . '<a class="link" onclick="getresultCourse(\'' . $href . $i . '\')" >'.$i.'</a>';
			}
			
			if(($pages-($_GET["page"]+2))>1) {
				$output = $output . '<span class="dot">...</span>';
			}
			if(($pages-($_GET["page"]+2))>0) {
				if($_GET["page"] == $pages)
					$output = $output . '<span id=' . ($pages) .' class="link current1">' . ($pages) .'</span>';
				else				
					$output = $output . '<a class="link" onclick="getresultCourse(\'' . $href .  ($pages) .'\')" >' . ($pages) .'</a>';
			}
			
			if($_GET["page"] < $pages)
				$output = $output . '<a  class="link" onclick="getresultCourse(\'' . $href . ($pages) . '\')" >&#8811;</a>';
			else				
				$output = $output . '<span class="link disabled">&#8811;</span>';
			
			
		}
		return $output;
	}

}
?>