var pageNews;
$body = $('body');

function FilterDicussionByCourseId(courseid) {
    $body.addClass("loading");
    pageNews = 0;
    var URL = window.origin + '/local/newsvnr/ajax/pagination_coursenews.php'
    $.ajax({
        url: URL,
        method: 'GET',
        data: {
            courseid: courseid,
            rowcount: $("#rowcount").val(),
        },
        success: function(data) {
            $("#see-all-news").html(data);
            $body.removeClass("loading");
        }
    });
}
var urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('showall') || urlParams.get('search')) {
    $('#region-main .card-body').hide();
}

function getresultCourseNews(url) {
    $.ajax({
        url: url,
        method: "GET",
        data: {
            rowcount: $("#rowcount").val(),
            pagination_setting: $("#pagination-setting").val()
        },
        success: function(data) {
            $("#see-all-news").html(data);
        }
    });
}

function getresultCourse(url) {
    $.ajax({
        url: url,
        method: "GET",
        data: {
            rowcount: $("#rowcount").val(),
            pagination_setting: $("#pagination-setting").val()
        },
        success: function(data) {
            if (urlParams.get('showall') == -1) {
                $(".course-all .row").html(data);
            }
        }
    });
}

function changePagination(option) {
    if (option != "") {
        getresultCourseNews(window.origin + '/local/newsvnr/ajax/pagination_coursenews.php');
    }
}
getresultCourseNews(window.origin + '/local/newsvnr/ajax/pagination_coursenews.php');