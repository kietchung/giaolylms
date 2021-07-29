define(['jquery', 'kendo.all.min', 'core/config', 'core/notification', 'dttable', 'core/str'], function($, kendo, Config, Notification, dttable, Str) {
    var init = function() {
        var script = Config.wwwroot + '/local/newsvnr/ajax/news.php';
        var script2 = Config.wwwroot + '/local/newsvnr/ajax/pagination_coursenews.php';
        $("#filter-newsbycourse").kendoDropDownList({
            dataTextField: "name",
            dataValueField: "id",
            // autoBind: false,
            filter: "contains",
            dataSource: {
                transport: {
                    read: {
                        url: script,
                        contentType: 'application/json; charset=utf-8',
                        type: 'POST',
                        dataType: 'json',
                        serverFiltering: true
                    }
                }
            },
            change: function(e) {
                var courseid = this.value();
                var settings = {
                    type: "GET",
                    processData: true,
                    data: {
                        courseid: courseid,
                        screen: 'news'
                    }
                }
                $.ajax(script2, settings).then(function(response) {
                    $('#newsbycourse').hide().html(response).fadeIn('fast');
                });
            }
        });
    }
    return {
        init:init
    }
});