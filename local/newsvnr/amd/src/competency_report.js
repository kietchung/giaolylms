define(['jquery', 'core/config', 'validatefm', 'local_newsvnr/initkendogrid', 'alertjs', 'core/str', 'kendo.all.min', 'local_newsvnr/initkendocontrolservices'], function($, Config, Validatefm, kendo, alertify, Str, kendoControl, kendoService) {
    "use strict";
    let gridName = '#competency_report';
    let kendoConfig = {};
    let kendoscript = Config.wwwroot + '/local/newsvnr/report/ajax/competency_report.php';
    var kendoDropdown = function() {
        var datascript = "/local/newsvnr/report/ajax/report_data.php?action=";
        //dropdown trạng thái người dùng
        var kendoConfig = {};
            kendoConfig.apiSettings = { url: datascript + 'get_competency'};
            kendoConfig.value = 'value';
            kendoConfig.optionLabel = M.util.get_string('selectcompetency', 'local_newsvnr');
        var kendoUserStatus = kendoService.initDropDownList(kendoConfig);
        $("#competency").kendoDropDownList(kendoUserStatus);
        //dropdown trạng thái người dùng
        var kendoConfig = {};
            kendoConfig.apiSettings = { url: datascript + 'get_competencyplan'};
            kendoConfig.value = 'value';
            kendoConfig.optionLabel = M.util.get_string('selectplan', 'local_newsvnr');
        var kendoUserStatus = kendoService.initDropDownList(kendoConfig);
        $("#competencyplan").kendoDropDownList(kendoUserStatus);
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
        //dropdown loại báo cáo
        var kendoConfig = {};
            kendoConfig.apiSettings = { url: datascript + 'get_report'};
            kendoConfig.value = 'value';
            kendoConfig.optionLabel = M.util.get_string('selectreport', 'local_newsvnr');
        var kendoReport = kendoService.initDropDownList(kendoConfig);
        $("#report").kendoDropDownList(kendoReport);
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
                field: "competencyname",
                title: M.util.get_string('competency', 'local_newsvnr'),
                width: "100px"
            },
            {
                template:function(e) {
                    return e.activity;
                },
                // hidden: true,
                field: "activity",
                title: M.util.get_string('activeresource', 'local_newsvnr'),
                width: "100px"
            },
            {
                template:function(e) {
                    return  "<a href='"+ e.coursehref +"' target='_blank'>"+ e.coursename +"</a>";
                },
                field: "coursename",
                title: M.util.get_string('course', 'local_newsvnr'),
                width: "130px"
            },
            {
                field: "planname",
                title: M.util.get_string('learningplan', 'local_newsvnr'),
                width: "100px"
            },
            {
                template:function(e) {
                    return "<span class='badge text-white "+e.classstatus+"  font-weight-bold rounded p-2'>"+e.status+"</span>";
                },
                field: "status",
                title: M.util.get_string('status', 'local_newsvnr'),
                width: "90px"
            },
            {
                field: "timecompleted",
                title: M.util.get_string('timefinishcourse', 'local_newsvnr'),
                width: "80px"
            },
            {
                field: "reviewer",
                title: M.util.get_string('reviewer', 'local_newsvnr'),
                width: "100px"
            }
        ];
        var toolbar = ["excel"]
        var excel = { 
            fileName: "Report.xlsx",
            allPages: true
        }
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
    var gridSearchAccount = function(username,competency,competencyplan,course) {
        var data = {
            action:'searchaccount',
            username:username,
            competency:competency,
            competencyplan:competencyplan,
            course:course
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
            var competency = $('#competency').val();
            var competencyplan = $('#competencyplan').val();
            var course = $('#course').val();;
            gridSearchAccount(username,competency,competencyplan,course);
        })
        $('#resettable').click(function() {
            initGrid();
        })
        $("#exporttable").on('click', function(e){
            var trs = $('#competency_report').find('tr');
            if ($(trs).find(":checkbox").is(":checked")) {
                var row = [{
                    cells: [
                        { value: M.util.get_string('studentname', 'local_newsvnr') },
                        { value: M.util.get_string('competency', 'local_newsvnr') },
                        { value: M.util.get_string('course', 'local_newsvnr') },
                        { value: M.util.get_string('learningplan', 'local_newsvnr') },
                        { value: M.util.get_string('status', 'local_newsvnr') },
                        { value: M.util.get_string('timefinishcourse', 'local_newsvnr') },
                        { value: M.util.get_string('reviewer', 'local_newsvnr') },
                    ]
                }]
                exportExcelKendo('#competency_report',row);
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