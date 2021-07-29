define(['jquery', 'core/config', 'validatefm', 'local_newsvnr/initkendogrid', 'alertjs', 'core/str', 'kendo.all.min', 'local_newsvnr/initkendocontrolservices'], function($, Config, Validatefm, kendo, alertify, Str, kendoControl, kendoService) {
    "use strict";
    let gridName = '#division';
    let kendoConfig = {};
    let kendoscript = Config.wwwroot + '/local/newsvnr/division/ajax/division_data.php';
    let actionscript = Config.wwwroot + '/local/newsvnr/division/ajax/division_action.php';
    var addDivision = function() {
        $('#adddivision').click(function() {
            $('.modal-title').text('Tạo mới chi nhánh');
            $('#createdivision').attr('action','add');
            $('#createdivision').attr('divisionid','');
            $('#name').val('');
            $('#shortname').val('');
            $('#code').val('');
            $('#fax').val('');
            $('#phone').val('');
            $('#website').val('');
            $('#address').val('');
        })

        $('#createdivision').click(function(){
            $('.warning-division').text('');
            var name = $('#name').val();
            var shortname = $('#shortname').val();
            var code = $('#code').val();
            var fax = $('#fax').val();
            var phone = $('#phone').val();
            var website = $('#website').val();
            var address = $('#address').val();
            var visible = $('#visible').prop("checked");
            var action = $(this).attr('action');
            var divisionid = $(this).attr('divisionid');
            if(name == "" || shortname == "" || code == "") {
                if(name == "") {
                    $('.warning-division[data="name"]').text('Tên không được để trống')
                }
                if(shortname == "") {   
                    $('.warning-division[data="shortname"]').text('Tên viết tắt không được để trống')
                }
                if(code == "") {   
                    $('.warning-division[data="code"]').text('Mã không được để trống')
                }
                return;
            }
            var settings = {
                type: "GET",
                processData: true,
                data: {
                    action:action,
                    name:name,
                    shortname:shortname,
                    code:code,
                    fax:fax,
                    phone:phone,
                    website:website,
                    address:address,
                    visible:visible,
                    divisionid:divisionid
                }
            }
            $.ajax(actionscript,settings).then(function(response) {
                var obj = $.parseJSON(response);
                alertify.notify(obj.result, 'success', 3);
                var grid = $(gridName).data("kendoGrid");
                grid.dataSource.read();
                $('#popup-add-division').modal('hide');
            })
        })
    }
    var deleteDivision = function() {
        $('#deletedivision').click(function(){
            var arrObject = getSelectRow('#division');
            var data = JSON.stringify(arrObject);
            var settings = {
                type: "POST",
                processData: true,
                data: {
                    dataselect: data,
                    action: 'deleteselected'
                }
            }
            alertify.confirm('Cảnh báo', 'Bạn có chắc muốn xóa các chi nhánh đã chọn ?', function(){ 
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
                field: "name",
                title: "Tên chi nhánh",
                width: "130px"
            },
            {
                field: "shortname",
                title: "Tên viết tắt",
                width: "100px"
            },
            {
                field: "code",
                title: "Mã",
                width: "130px"
            },
            {
                field: "address",
                title: "Địa chỉ",
                width: "120px"
            },
            {
                template: function(e) {
                    return e.visible
                },
                field: "visible",
                title: "Kích hoạt",
                width: "120px"
            },
            {
                field: "phone",
                title: "Điện thoại",
                width: "100px"
            },
            {
                field: "timecreated",
                title: "Ngày tạo",
                width: "100px"
            },
            {
                field: "usercreate",
                title: "Người tạo",
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
        kendoConfig.onChange = onChange;
        kendoConfig.columns = colums;
        kendoConfig.apiSettings = settings;
        kendoConfig.deleteDivisionEvent = function(dataItem) {
            var grid = $(gridName).data("kendoGrid");
            grid.dataSource.read();
        }
        kendoConfig.editDivisionEvent = function(dataItem) {
            $('#popup-add-division').modal('show');
            $('.modal-title').text('Chỉnh sửa chi nhánh');
            $('#name').val(dataItem.name);
            $('#shortname').val(dataItem.shortname);
            $('#code').val(dataItem.code);
            $('#fax').val(dataItem.fax);
            $('#phone').val(dataItem.phone);
            $('#website').val(dataItem.website);
            $('#address').val(dataItem.address);
            $('#createdivision').attr('action','update');
            $('#createdivision').attr('divisionid',dataItem.id);
            if(dataItem.isvisible == 1) {
                $('#visible').prop("checked",true);
            } else {
                $('#visible').prop("checked",false);
            }
        }
        var gridData = kendo.initGrid(kendoConfig);
        if($(gridName).data("kendoGrid")) {
            $(gridName).data("kendoGrid").destroy();
        }
        $(gridName).kendoGrid(gridData);
    }

    // Hàm main
    var init = function() {
        initGrid();
        addDivision();
        deleteDivision();
    }
    return {
        init:init
    }
});