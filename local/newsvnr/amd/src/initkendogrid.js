define(['jquery', 'core/config', 'core/str','kendo.all.min','alertjs'], function($, Config, Str, kendo, alertify) {
	var initGrid = function (gridConfig) {
        eventArr = [];
        if (gridConfig.columns === undefined) { gridConfig.columns = []; }
        gridConfig.selectData = function (e) {
            var myGrid = $('div[kendo-grid][k-options=' + gridConfig.gridName + ']').getKendoGrid();
            var selectedRows = myGrid.select();
            var arrObject = [];
            for (var i = 0; i < selectedRows.length; i++) {
                arrObject.push(myGrid.dataItem(selectedRows[i]));
            }
            return arrObject;
        };
        gridConfig.selectId = function (e) {
            var myGrid = $('div[kendo-grid][k-options=' + gridConfig.gridName + ']').getKendoGrid();
            var selectedRows = myGrid.select();
            var arrObject = [];
            for (var i = 0; i < selectedRows.length; i++) {
                arrObject.push(myGrid.dataItem(selectedRows[i]).Id);
            }
            return arrObject;
        };
        if(gridConfig.selectable === undefined) {
            gridConfig.columns.unshift({
                selectable: true,
                width: 50
            });
        }
        // if (gridConfig.selectRowEvent != undefined) {
        //     var funcSelectRow = function(e) {
        //         e.preventDefault();
        //         var myGrid = $('#library-approval-module').getKendoGrid();
        //         var selectedRows = myGrid.select();
        //         var arrObject = [];
        //         for (var i = 0; i < selectedRows.length; i++) {
        //             arrObject.push(myGrid.dataItem(selectedRows[i]));
        //         }
        //         gridConfig.selectRowEvent(arrObject);
        //     }
        // }
        if (gridConfig.approvalModuleEvent != undefined) {
            var funcApprovalModule = function(e) {
                e.preventDefault();
                var dataItem = this.dataItem($(e.currentTarget).closest("tr"));
                var script = Config.wwwroot + '/local/newsvnr/ajax/library_online/library_approval_module_ajax.php?action=approval';
                var settings = {
                    type:"GET",
                    processData:true,
                    dataType: 'json',
                    contentType: "application/json",
                    data:{
                        moduleid:dataItem.id
                    }
                }
                $.ajax(script,settings).then(function(resp) {
                    if(resp.error == true) {
                        alertify.error(resp.message, 'error', 3);
                    } else {
                        alertify.success(resp.message, 'success', 3);
                    }
                }) 
                gridConfig.approvalModuleEvent(dataItem);
            }
            var objEventApprovalModule = {
                click: funcApprovalModule,
                text: " ",
                name: "approval",
                iconClass: 'fa fa-check',
            }
            eventArr.push(objEventApprovalModule);
        }
        if (gridConfig.viewCourseInfoPopupEvent != undefined) {
            var funcViewCourseInfoPopup = function(e) {
                e.preventDefault();
                var dataItem = this.dataItem($(e.currentTarget).closest("tr"));
                gridConfig.viewCourseInfoPopupEvent(dataItem);
            }
            var objEventViewCourseInfoPopup = {
                click: funcViewCourseInfoPopup,
                text: M.util.get_string('viewcourse', 'local_newsvnr'),
                name: M.util.get_string('viewcourse', 'local_newsvnr'),
                iconClass: '',
            }
            eventArr.push(objEventViewCourseInfoPopup);
        }
        if (gridConfig.deleteModuleEvent != undefined) {
            var funcDeleteModule = function(e) {
                e.preventDefault();
                var dataItem = this.dataItem($(e.currentTarget).closest("tr"));
                var script = Config.wwwroot + '/local/newsvnr/ajax/library_online/library_approval_module_ajax.php?action=reject';
                var settings = {
                    type:"GET",
                    processData:true,
                    dataType: 'json',
                    contentType: "application/json",
                    data:{
                        moduleid:dataItem.id
                    }
                }
                $.ajax(script,settings).then(function(resp) {
                    if(resp.error == true) {
                        alertify.error(resp.message, 'error', 3);
                    } else {
                        alertify.success(resp.message, 'success', 3);
                    }
                }) 
                gridConfig.deleteModuleEvent(dataItem);
            }
            var objEventDeleteModule = {
                click: funcDeleteModule,
                text: " ",
                name: "delete",
                iconClass: 'fa fa-trash',
            }
            eventArr.push(objEventDeleteModule);
        }
        if (gridConfig.editEvent != undefined) {
            var funcEdit = function(e) {
                e.preventDefault();
                var dataItem = this.dataItem($(e.currentTarget).closest("tr"));
                gridConfig.editEvent(dataItem);
            }
            var objEventEdit = {
                click: funcEdit,
                text: " ",
                name: "edit",
                iconClass: 'fa fa-pencil-square-o',
            }
            eventArr.push(objEventEdit);
        }
        if (gridConfig.deleteEvent != undefined) {
            var funcDelete = function(e) {
                e.preventDefault();
                var dataItem = this.dataItem($(e.currentTarget).closest("tr"));
                gridConfig.deleteEvent(dataItem);
            }
            var objEventDelete = {
                click: funcDelete,
                text: " ",
                name: "delete",
                iconClass: 'fa fa-trash-o'
            }
            eventArr.push(objEventDelete);
        }
        if (gridConfig.deleteUserEvent != undefined) {
            var funcDeleteUser = function(e) {
                e.preventDefault();
                var dataItem = this.dataItem($(e.currentTarget).closest("tr"));
                var script = Config.wwwroot + '/local/newsvnr/report/ajax/userreport_action.php';
                var settings = {
                    type:"GET",
                    processData:true,
                    data:{
                        userid:dataItem.id,
                        action:'delete'
                    }
                }
                alertify.confirm(M.util.get_string('alert', 'local_newsvnr'),M.util.get_string('deleteuserconfirm', 'local_newsvnr'), function(){
                    $.ajax(script,settings).then(function() {
                        var obj = $.parseJSON(response);
                        alertify.notify(obj.result, 'success', 3);
                    })
                    gridConfig.deleteUserEvent(dataItem);
                },function(){});
            }
            var objEventDeleteUser = {
                click: funcDeleteUser,
                iconClass: 'fa fa-trash mr-1',
                text: '',
                name: 'deleteuser',
            }
            eventArr.push(objEventDeleteUser);
        }
        if (gridConfig.hideUserEvent != undefined) {
            var dataItem = '';
            var funcHideUser = function(e) {
                e.preventDefault();
                dataItem = this.dataItem($(e.currentTarget).closest("tr"));
                var settings = {
                    type:"GET",
                    processData:true,
                    data:{
                        userid:dataItem.id,
                        action:'hide'
                    }
                }
                var script = Config.wwwroot + '/local/newsvnr/report/ajax/userreport_action.php';
                $.ajax(script,settings).then(function(response) {
                    var obj = $.parseJSON(response);
                    alertify.notify(obj.result, 'success', 3);
                })
                gridConfig.hideUserEvent(dataItem);
            }
            var objEventHideUser = {
                click: funcHideUser,
                iconClass: 'fa fa-eye mr-1',
                text: '',
                name: 'hideuser',
                visible: function(dataItem) {
                    return dataItem.suspended == 0;
                }
            }
            eventArr.push(objEventHideUser);
        }
        if (gridConfig.showUserEvent != undefined) {
            var dataItem = '';
            var funcShowUser = function(e) {
                e.preventDefault();
                dataItem = this.dataItem($(e.currentTarget).closest("tr"));
                var settings = {
                    type:"GET",
                    processData:true,
                    data:{
                        userid:dataItem.id,
                        action:'show'
                    }
                }
                var script = Config.wwwroot + '/local/newsvnr/report/ajax/userreport_action.php';
                $.ajax(script,settings).then(function(response) {
                    var obj = $.parseJSON(response);
                    alertify.notify(obj.result, 'success', 3);
                })
                gridConfig.showUserEvent(dataItem);
            }
            var objEventShowUser = {
                click: funcShowUser,
                iconClass: 'fa fa-eye-slash mr-1',
                text: '',
                name: 'showuser',
                visible: function(dataItem) {
                    return dataItem.suspended == 1;
                }
            }
            eventArr.push(objEventShowUser);
        }
        if (gridConfig.editUserEvent != undefined) {
            var funcEditUser = function(e) {
                e.preventDefault();
                var dataItem = this.dataItem($(e.currentTarget).closest("tr"));
                window.open(Config.wwwroot + '/user/editadvanced.php?id='+dataItem.id+'&course=1&returnto=profile');
                gridConfig.editUserEvent(dataItem);
            }
            var objEventEditUser = {
                click: funcEditUser,
                iconClass: 'fa fa-cog mr-1',
                text: '',
                name: 'edituser',
            }
            eventArr.push(objEventEditUser);
        }
        if (gridConfig.deleteDivisionEvent != undefined) {
            var funcDeleteDivision = function(e) {
                e.preventDefault();
                var dataItem = this.dataItem($(e.currentTarget).closest("tr"));
                var script = Config.wwwroot + '/local/newsvnr/division/ajax/division_action.php';
                var settings = {
                    type:"GET",
                    processData:true,
                    data:{
                        divisionid:dataItem.id,
                        action:'delete'
                    }
                }
                $.ajax(script,settings).then(function() {
                    var obj = $.parseJSON(response);
                    alertify.notify(obj.result, 'success', 3);
                })
                gridConfig.deleteDivisionEvent(dataItem);
            }
            var objEventDeleteDivision = {
                click: funcDeleteDivision,
                iconClass: 'fa fa-trash mr-1',
                text: '',
                name: 'deletedivision',
            }
            eventArr.push(objEventDeleteDivision);
        }
        if (gridConfig.editDivisionEvent != undefined) {
            var funcEditDivision = function(e) {
                e.preventDefault();
                var dataItem = this.dataItem($(e.currentTarget).closest("tr"));
                gridConfig.editDivisionEvent(dataItem);
            }
            var objEventEditDivision = {
                click: funcEditDivision,
                iconClass: 'fa fa-cog mr-1',
                text: '',
                name: 'editdivision',
            }
            eventArr.push(objEventEditDivision);
        }
        if(eventArr.length > 0) {
            gridConfig.columns.push({
                title: M.util.get_string('action', 'local_newsvnr'),
                command: eventArr,
                width: 150
            });
        }
        if(gridConfig.toolbar === undefined) {
            gridConfig.toolbar = ["search"];
        } 
        return {
            dataSource: newDatasourceGrid(gridConfig),
            persistSelection: true,
            groupable: false,
            //sortable: true,
            resizable: true,
            // selectable: "multiple",
            //dataBound: gridConfig.dataBound,
            //height: 520,
            change: gridConfig.onChange,
            toolbar: gridConfig.toolbar,
            excel: gridConfig.excel,
            search: {
                fields: ["name"]
            },
            pageable: {
                refresh: true,
                pageSizes: true,
                pageSizes: [10, 20, 50, 100],
                buttonCount: 5
            },
            columns: gridConfig.columns,
            noRecords: {
                template: '<span class="grid-empty">' + M.util.get_string('emptydata', 'local_newsvnr') + '</span>'
            }
        }
    };
    var newDatasourceGrid = function(gridConfig) {
        return new kendo.data.DataSource({
            transport: {
                read: gridConfig.apiSettings,
                parameterMap: function(options, operation) {
                    if (operation == "read") {
                        if (options["filter"]) {
                            options["q"] = options["filter"]["filters"][0].value;
                        }
                        return options;
                    }
                }
            },
            // error: function (evt) {
            //     if (evt.xhr && evt.xhr.status == '401') {
            //         console.log('Errs');
            //     }
            // },
            pageSize: 10,
            serverPaging: true,
            serverFiltering: true,
            serverSorting: true,
            schema: {
                model: {
                    id : "id",
                    fields: {
                        id : { type: "number" },
                        name: { type: "string" },
                    }
                },
                total: function(data) {
                    if (data != null && data.length > 0) return data[0].total;
                },
            }
        });
    };
    return {
        initGrid: initGrid
    }
})
