define(['jquery', 'core/config', 'validatefm', 'local_newsvnr/initkendogrid', 'alertjs', 'core/str', 'kendo.all.min', 'local_newsvnr/initkendocontrolservices'], function($, Config, Validatefm, kendo, alertify, Str, kendoControl, kendoService) {
    "use strict";
    let gridName = '#user_report';
    let kendoConfig = {};
    let kendoscript = Config.wwwroot + '/local/newsvnr/report/ajax/userreport.php';
    let actionscript = Config.wwwroot + '/local/newsvnr/report/ajax/userreport_action.php';
    setCookie('cookie', 'focusmod');
    setCookie('spa', 'true');
    var deleteUser = function() {
        $('#btn-user-delete').click(function(){
            var arrObject = getSelectRow('#user_report');
            var data = JSON.stringify(arrObject);
            var strname = '';
            for(var i=0 ;i < arrObject.length;i++) {
                strname += arrObject[i]['name'] + ',';
            }
            var settings = {
                type: "POST",
                processData: true,
                data: {
                    dataselect: data,
                    action: 'deleteselected'
                }
            }
            alertify.confirm('Cảnh báo', 'Bạn có chắc muốn xóa '+strname.slice(0,-1)+' khỏi danh sách người dùng ?', function(){ 
                $.ajax(actionscript,settings).then(function(response) {
                    var obj = $.parseJSON(response);
                    alertify.notify(obj.result, 'success', 3);
                    var grid = $(gridName).data("kendoGrid");
                    grid.dataSource.read();
                    $('.user-report-fuction').addClass('disabled');
                })
            }, function(){});

        })
    }
    var openPopUp = function() {
        $('.user-report-fuction').click(function() {
            var link = $(this).attr('data-href');
            if(link == undefined) {
                return;
            }
            var name = $(this).text();
            var arrObject = getSelectRow('#user_report');
            var data = JSON.stringify(arrObject);
            var settings = {
                type: "POST",
                processData: true,
                data: {
                    dataselect:data,
                    action:'addsession'
                }
            }
            $.ajax(actionscript,settings).then(function(response) {
                var initIframe = '<iframe id="iframe-edit-category" src="'+link+'" width="100%" height="600px" style="border:0"></iframe>';
                $('#popup-user-report .modal-title').text(name);
                $('#popup-user-report .modal-body').html(initIframe);
                var iframes;
                $('#iframe-edit-category').on('load', function() {
                    iframes = iFrameResize({  log: false, }, '#iframe-edit-category');
                });
            })

        })
    }
    var kendoDropdown = function() {
        var datascript = "/local/newsvnr/report/ajax/report_data.php?action=";
        //dropdown trạng thái người dùng
        var kendoConfig = {};
            kendoConfig.apiSettings = { url: datascript + 'get_userstatus'};
            kendoConfig.value = 'value';
            kendoConfig.optionLabel = M.util.get_string('selectstatus', 'local_newsvnr');
        var kendoUserStatus = kendoService.initDropDownList(kendoConfig);
        $("#userstatus").kendoDropDownList(kendoUserStatus);
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
        //dropdown vai trò hệ thống
        var kendoConfig = {};
            kendoConfig.apiSettings = { url: datascript + 'get_system_role'};
            kendoConfig.value = 'value';
            kendoConfig.optionLabel = M.util.get_string('selectrole', 'local_newsvnr');
        var kendoSystemRole = kendoService.initDropDownList(kendoConfig);
        $("#system_role").kendoDropDownList(kendoSystemRole);
        //dropdown vai trò trong khóa
        var kendoConfig = {};
            kendoConfig.apiSettings = { url: datascript + 'get_course_role'};
            kendoConfig.value = 'value';
            kendoConfig.optionLabel = M.util.get_string('selectrole', 'local_newsvnr');
        var kendoCourseRole = kendoService.initDropDownList(kendoConfig);
        $("#course_role").kendoDropDownList(kendoCourseRole);
        //dropdown khóa học
        var kendoConfig = {};
            kendoConfig.apiSettings = { url: datascript + 'get_course'};
            kendoConfig.value = 'id';
            kendoConfig.optionLabel = M.util.get_string('selectcourse', 'local_newsvnr');
        var kendoListCourse = kendoService.initDropDownList(kendoConfig);
        $("#list_course").kendoDropDownList(kendoListCourse);
        // thời gian truy cập gần nhất
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
                template:function(e){
                    return  e.useravatar + "<a href='"+ e.href +"' target='_blank'>"+ e.name +"</a>"
                },
                field: "name",
                title: M.util.get_string('studentname', 'local_newsvnr'),
                width: "130px"
            },
            {
                field: "shortname",
                title: M.util.get_string('codeuser', 'local_newsvnr'),
                width: "100px"
            },
            {
                field: "username",
                title: M.util.get_string('username_login', 'local_newsvnr'),
                width: "100px"
            },
            {
                field: "email",
                title: M.util.get_string('email', 'local_newsvnr'),
                width: "130px"
            },
            {
                field: "timecreated",
                title: M.util.get_string('createdtime', 'local_newsvnr'),
                width: "120px"
            },
            {
                field: "timeaccess",
                title: M.util.get_string('timeaccess', 'local_newsvnr'),
                width: "100px"
            },
            {
                field: "lastaccess",
                title: M.util.get_string('lastaccess', 'local_newsvnr'),
                width: "100px"
            }
        ];
        var onChange = function() {
            var selected = $.map(this.select(), function(item) {
                return $(item).text();
            });
            if(selected.length > 0) {
                $('.user-report-fuction').removeClass('disabled');
            } else {
                $('.user-report-fuction').addClass('disabled');
            }
        }   
        var toolbar = ["excel"]
        var excel = { 
            fileName: "userreport.xlsx",
            allPages: true
        }
        kendoConfig.onChange = onChange;
        kendoConfig.toolbar = toolbar;
        kendoConfig.excel = excel;
        kendoConfig.columns = colums;
        kendoConfig.apiSettings = settings;
        kendoConfig.deleteUserEvent = function(dataItem) {
            var grid = $(gridName).data("kendoGrid");
            grid.dataSource.read();
        }
        kendoConfig.hideUserEvent = function(dataItem) {
            var grid = $(gridName).data("kendoGrid");
            grid.dataSource.read();
        }
        kendoConfig.showUserEvent = function(dataItem) {
            var grid = $(gridName).data("kendoGrid");
            grid.dataSource.read();
        }
        kendoConfig.editUserEvent = function(dataItem) {
            
        }
        var gridData = kendo.initGrid(kendoConfig);
        if($(gridName).data("kendoGrid")) {
            $(gridName).data("kendoGrid").destroy();
        }
        $('.user-report-fuction').addClass('disabled');
        $(gridName).kendoGrid(gridData);
    }
    /// Tìm kiếm theo tài khoản
    var gridSearchAccount = function(username,useremail,usercode,userstatus,notaccess,notmodify) {
        var data = {
            action:'searchaccount',
            username:username,
            useremail:useremail,
            usercode:usercode,
            userstatus:userstatus,
            notaccess:notaccess,
            notmodify:notmodify
        }
        initGrid(data);
    }
    /// Tìm kiếm theo phòng ban
    var gridSearchOrgstructure = function(orgstructureid,positionid) {
        var data = {
            action:'searchorgstructure',
            orgstructureid:orgstructureid,
            positionid:positionid
        }
        initGrid(data);
    }
    /// Tìm kiếm nâng cao
    var gridSearchAdvance = function(systemroleid,courseroleid,courseid,datestart,dateend) {
        var data = {
            action:'searchadvance',
            systemroleid:systemroleid,
            courseroleid:courseroleid,
            courseid:courseid,
            datestart:datestart,
            dateend:dateend
        }
        initGrid(data);
    }
    // Hàm main
    var init = function() {
        initGrid();
        $('#searchaccount').click(function() {
            var username = $('#username').val();
            var useremail = $('#useremail').val();
            var usercode = $('#usercode').val();
            var userstatus = $('#userstatus').val();;
            var notaccess = $('#isnotaccess').is(':checked');
            var notmodify = $('#isnotmodify').is(':checked');
            gridSearchAccount(username,useremail,usercode,userstatus,notaccess,notmodify);
        })
        $('#searchorgs').click(function() {
            var orgstructureid = $('#orgstructure').val();
            var positionid = $('#orgstructure_position').val();
            gridSearchOrgstructure(orgstructureid,positionid);
        })
        $('#searchadvance').click(function() {
            var systemroleid = $("#system_role").val();
            var courseroleid = $("#course_role").val();
            var courseid = $("#list_course").val();
            var datestartpicker = $("#start_timeaccess").val();
            var datestart = parseInt((new Date(datestartpicker).getTime() / 1000).toFixed(0));
            var dateendpicker = $("#end_timeaccess").val();
            var dateend = parseInt((new Date(dateendpicker).getTime() / 1000).toFixed(0));
            gridSearchAdvance(systemroleid,courseroleid,courseid,datestart,dateend);
        })
        $('#resettable').click(function() {
            $('#username').val('');
            $('#useremail').val('');
            $('#usercode').val('');
            $('#isnotaccess').prop('checked',false)
            $('#isnotmodify').prop('checked',false)
            $('#userstatus').data('kendoDropDownList').value(-1);
            $('#system_role').data('kendoDropDownList').value(-1);
            $('#course_role').data('kendoDropDownList').value(-1);
            $('#list_course').data('kendoDropDownList').value(-1);
            $('#start_timeaccess').val('')
            $('#end_timeaccess').val('')
            initGrid();
        })
        $("#exporttable").on('click', function(e){
            var trs = $(gridName).find('tr');
            if ($(trs).find(":checkbox").is(":checked")) {
                var row = [{
                    cells: [
                        { value: M.util.get_string('studentname', 'local_newsvnr') },
                        { value: M.util.get_string('codeuser', 'local_newsvnr') },
                        { value: M.util.get_string('username_login', 'local_newsvnr') },
                        { value: M.util.get_string('email', 'local_newsvnr') },
                        { value: M.util.get_string('createdtime', 'local_newsvnr') },
                        { value: M.util.get_string('timeaccess', 'local_newsvnr') },
                        { value: M.util.get_string('lastaccess', 'local_newsvnr') },
                    ]
                }]
                exportExcelKendo(gridName,row);
            } else {
                $('.k-grid-excel').click();
            }
        })
    }
    return {
        init:init,
        deleteUser:deleteUser,
        openPopUp:openPopUp,
        kendoDropdown:kendoDropdown
    }
});