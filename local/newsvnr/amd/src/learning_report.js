define(['jquery', 'core/config', 'validatefm', 'local_newsvnr/initkendogrid', 'alertjs', 'core/str', 'kendo.all.min', 'local_newsvnr/initkendocontrolservices'], function($, Config, Validatefm, kendo, alertify, Str, kendoControl, kendoService) {
    "use strict";
    let gridName = '#learning_report';
    let kendoConfig = {};
    let kendoscript = Config.wwwroot + '/local/newsvnr/report/ajax/learning_report.php';
    var kendoDropdown = function() {
        var datascript = "/local/newsvnr/report/ajax/report_data.php?action=";
        //dropdown trạng thái người dùng
        $("#start_timeaccess").kendoDatePicker({
            change: onChange
        });
        function onChange() {
            $('#end_timeaccess').val('');
            var date =  $("#start_timeaccess").val().split('/');
            if(date.length > 1) {
                var datepicker = $("#end_timeaccess").data("kendoDatePicker");
                var year = parseInt(date[2]);
                var month = parseInt(date[0]);
                var day = parseInt(date[1]);
                datepicker.setOptions({
                    min: new Date(year,month-1,day+1)
                });
            }
        }
        $("#end_timeaccess").kendoDatePicker();
        //dropdown trạng thái người dùng
        var kendoConfig = {};
            kendoConfig.apiSettings = { url: datascript + 'get_course'};
            kendoConfig.value = 'value';
            kendoConfig.optionLabel = M.util.get_string('selectcourse', 'local_newsvnr');
        var kendoUserStatus = kendoService.initDropDownList(kendoConfig);
        $("#course").kendoDropDownList(kendoUserStatus);
        //dropdown loại phòng ban
        var kendoConfig = {};
            kendoConfig.apiSettings = { url: datascript + 'get_orgstructure_category'};
            kendoConfig.value = 'categoryid';
            kendoConfig.optionLabel = M.util.get_string('selectorgstructuretype', 'local_newsvnr');
        var kendoOrgCategory = kendoService.initDropDownList(kendoConfig);
        $("#orgstructure_category").kendoDropDownList(kendoOrgCategory);
        //dropdown phòng ban
        var kendoConfig = {};
            kendoConfig.apiSettings = { url: datascript + 'get_orgstructure'};
            kendoConfig.value = 'orgid';
            kendoConfig.optionLabel = M.util.get_string('selectorgstructure', 'local_newsvnr');
            kendoConfig.cascadeFrom = 'orgstructure_category';
        var kendoOrgstructure = kendoService.initDropDownList(kendoConfig);
        $("#orgstructure").kendoDropDownList(kendoOrgstructure);
        //dropdown chức danh
        var kendoConfig = {};
            kendoConfig.apiSettings = { url: datascript + 'get_orgstructure_jobtitle'};
            kendoConfig.value = 'jobtitleid';
            kendoConfig.optionLabel = M.util.get_string('selectjobtitle', 'local_newsvnr');
        var kendoOrgJobtitle = kendoService.initDropDownList(kendoConfig);
        $("#orgstructure_jobtitle").kendoDropDownList(kendoOrgJobtitle);
        //dropdown chức vụ
        var kendoConfig = {};
            kendoConfig.apiSettings = { url: datascript + 'get_orgstructure_position'};
            kendoConfig.value = 'positionid';
            kendoConfig.optionLabel = M.util.get_string('selectjobposition', 'local_newsvnr');
            kendoConfig.cascadeFrom = 'orgstructure_jobtitle';
        var kendoOrgPosition = kendoService.initDropDownList(kendoConfig);
        $("#orgstructure_position").kendoDropDownList(kendoOrgPosition);
        // Chọn trạng thái
        var kendoConfig = {};
            kendoConfig.apiSettings = { url: datascript + 'get_learning_status'};
            kendoConfig.value = 'value';
            kendoConfig.optionLabel = M.util.get_string('select', 'local_newsvnr');
        var kendoStatus = kendoService.initDropDownList(kendoConfig);
        $("#status").kendoDropDownList(kendoStatus);

        $("#start_process").kendoNumericTextBox({
            format: "p0",
            factor: 100,
            min: 0,
            max: 1,
            step: 0.01, 
        });

        $("#end_process").kendoNumericTextBox({
            format: "p0",
            factor: 100,
            min: 0,
            max: 100,
            step: 1, 
        });

        $("#grade").kendoNumericTextBox({
            format: "0",
           decimals: 0,
            min: 0,
            max: 100,
            step: 1, 
        });
    }
    var initGrid = function(data) {
        var settings = {            
            url: kendoscript,
            type : 'GET',
            dataType: "json",
            contentType: 'application/json; charset=utf-8',
            data : data
        };
        var colums = [
            {
                template:function(e) {
                    return  e.useravatar + "<a href='"+ e.userhref +"' target='_blank'>"+ e.name +"</a>";
                },
                field: "name",
                title: M.util.get_string('studentname', 'local_newsvnr'),
                width: "100px"
            },
            {
                template:function(e) {
                    return  "<a href='"+ e.coursehref +"' target='_blank'>"+ e.coursename +"</a>";
                },
                field: "coursename",
                title: M.util.get_string('course', 'local_newsvnr'),
                width: "120px"
            },
            {
                template:"<div class='d-flex participants-collum'><div class='progress course'><div class='progress-bar' role='progressbar' aria-valuenow='#: process #' aria-valuemin='0' aria-valuemax='100' style='width:#: process #'></div></div><div></div></div></div>",
                field: "process",
                title: M.util.get_string('learningprocess', 'local_newsvnr'),
                width: "100px"
            },
            {
                template:function(e) {
                    return '<div class="text-center"><span class="badge text-white '+e.classstatus+' font-weight-bold rounded p-2">'+e.status+'</span></div>';
                },
                field: "status",
                title: M.util.get_string('status', 'local_newsvnr'),
                width: "100px"
            },
            {
                field: "timestart",
                title: M.util.get_string('joindate', 'local_newsvnr'),
                width: "100px"
            },
            {
                field: "timecompleted",
                title: M.util.get_string('timefinishcourse', 'local_newsvnr'),
                width: "70px"
            },
            {
                field: "timeaccess",
                title: M.util.get_string('timeaccess', 'local_newsvnr'),
                width: "80px"
            },
            {
                field: "grade",
                title: M.util.get_string('grade', 'local_newsvnr'),
                width: "100px"
            }
        ];
        var onChange = function() {
            var selected = $.map(this.select(), function(item) {
                return $(item).text();
            });
            if(selected.length > 0) {
                $('#exporttable').addClass('action');
            } else {
                $('#exporttable').removeClass('action');
            }
        }   
        var toolbar = ["excel"]
        var excel = { 
            fileName: "Report.xlsx",
            allPages: true
        }
        kendoConfig.onChange = onChange;
        kendoConfig.toolbar = toolbar;
        kendoConfig.excel = excel;
        kendoConfig.columns = colums;
        kendoConfig.apiSettings = settings;
        var gridData = kendo.initGrid(kendoConfig);
        if($(gridName).data("kendoGrid")) {
            $(gridName).data("kendoGrid").destroy();
        }
        $(gridName).kendoGrid(gridData);
    }
    var gridSearchAccount = function(username,datestart,dateend,courseid,status,startprocess,endprocess,grade) {
        var data = {
            action:'searchaccount',
            username:username,
            datestart:datestart,
            dateend:dateend,
            courseid:courseid,
            status:status,
            startprocess:startprocess,
            endprocess:endprocess,
            grade:grade
        }
        initGrid(data);
    }
    var gridSearchOrgstructure = function(orgstructureid,positionid) {
        var data = {
            action:'searchorgstructure',
            orgstructureid:orgstructureid,
            positionid:positionid
        }
        initGrid(data);
    }

    var init = function() {
        initGrid();
        $('#searchorgs').click(function() {
            var orgstructureid = $('#orgstructure').val();
            var positionid = $('#orgstructure_position').val();
            gridSearchOrgstructure(orgstructureid,positionid);
        })
        $('#searchaccount').click(function() {
            var username = $('#username').val();
            var datestart = $('#start_timeaccess').val();
            var dateend = $('#end_timeaccess').val();
            var courseid = $('#course').val();;
            var status = $('#status').val();
            var startprocess = $('#start_process').val();
            var endprocess = $('#end_process').val();
            var grade = $('#grade').val();
            gridSearchAccount(username,datestart,dateend,courseid,status,startprocess,endprocess,grade);
        })
        $('#resettable').click(function() {
            initGrid();
        })
        $('#changereport').click(function() {
            var report = $('#report').val();
            if(report) {
                location.replace(Config.wwwroot + '/local/newsvnr/report/' + report + '.php');
            } else {
                alertify.alert('Thông báo', 'Vui lòng chọn báo cáo!');
            }
        })
        $("#exporttable").on('click', function(e){
            var trs = $('#learning_report').find('tr');
            if ($(trs).find(":checkbox").is(":checked")) {
                var row = [{
                    cells: [
                        { value: M.util.get_string('studentname', 'local_newsvnr') },
                        { value: M.util.get_string('course', 'local_newsvnr') },
                        { value: M.util.get_string('learningprocess', 'local_newsvnr') },
                        { value: M.util.get_string('status', 'local_newsvnr') },
                        { value: M.util.get_string('joindate', 'local_newsvnr') },
                        { value: M.util.get_string('timefinishcourse', 'local_newsvnr') },
                        { value: M.util.get_string('timeaccess', 'local_newsvnr') },
                        { value: M.util.get_string('grade', 'local_newsvnr') },
                    ]
                }]
                exportExcelKendo('#learning_report',row);
            } else {
                $('.k-grid-excel').click();
            }
        })
    }
    return {
        init:init,
        kendoDropdown:kendoDropdown
    }
});