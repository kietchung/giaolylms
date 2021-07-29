<?php
class PerPage
{

    public $perpage;

    public function __construct()
    {

        $this->itemPerPage = 10;

    }
    public function getStart($page)
    {
        if ($page < 1) {
            $page = 1;
        }
        $start = ($page - 1) * $this->itemPerPage;
        if ($start < $this->itemPerPage) {
            $start = 0;
        }
        return $start;
    }
    public function getAllPageLinks($count, $href, $place)
    {
        $output = '';

        if (!isset($_GET["page"])) {
            $_GET["page"] = 1;
        }
        if ($this->itemPerPage != 0) {
            $pages = ceil($count / $this->itemPerPage);
        }
        if ($pages > 1) {
            if ($_GET["page"] == 1) {
                $output = $output . '<span class="link disabled">«</span>';
            } else {
                $output = $output . '<a class="link" onclick="nextPage(\'' . $href . ($_GET["page"] - 1) . '\',\'' . $place . '\')" >«</a>';
            }
            if (($_GET["page"] - 3) > 0) {
                if ($_GET["page"] == 1) {
                    $output = $output . '<span id=1 class="link current1">1</span>';
                } else {
                    $output = $output . '<a class="link" onclick="nextPage(\'' . $href . '1 \',\'' . $place . '\')" >1</a>';
                }
            }
            if (($_GET["page"] - 3) > 1) {
                $output = $output . '<span class="dot">...</span>';
            }
            for ($i = ($_GET["page"] - 2); $i <= ($_GET["page"] + 2); $i++) {
                if ($i < 1) {
                    continue;
                }
                if ($i > $pages) {
                    break;
                }
                if ($_GET["page"] == $i) {
                    $output = $output . '<span id=' . $i . ' class="link current1">' . $i . '</span>';
                } else {
                    $output = $output . '<a class="link" onclick="nextPage(\'' . $href . $i . '\',\'' . $place . '\')" >' . $i . '</a>';
                }
            }
            if (($pages - ($_GET["page"] + 2)) > 1) {
                $output = $output . '<span class="dot">...</span>';
            }
            if (($pages - ($_GET["page"] + 2)) > 0) {
                if ($_GET["page"] == $pages) {
                    $output = $output . '<span id=' . ($pages) . ' class="link current1">' . ($pages) . '</span>';
                } else {
                    $output = $output . '<a class="link" onclick="nextPage(\'' . $href . ($pages) . '\',\'' . $place . '\')" >' . ($pages) . '</a>';
                }
            }
            if ($_GET["page"] < $pages) {
                $output = $output . '<a  class="link" onclick="nextPage(\'' . $href . ($_GET["page"] + 1) . '\',\'' . $place . '\')" >»</a>';
            } else {
                $output = $output . '<span class="link disabled">»</span>';
            }
        }
        return $output;
    }
}
?>
