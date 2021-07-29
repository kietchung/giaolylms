define(['jquery', 'core/config', 'core/str', 'kendo.all.min'], function($, Config, Str, kendo) {
    var initGrid = function(gridConfig) {
      
        if (gridConfig.columns === undefined) {
            gridConfig.columns = [];
        }
        if(gridConfig.toolbar === undefined) {
            gridConfig.toolbar = ["search"];
        }
        if(gridConfig.height === undefined) {
            gridConfig.height = "";
        }
        var eventArr = [];
        if(gridConfig.editEvent != undefined) {
            gridConfig.columns.unshift({
                selectable: true,
                width: 52
            });
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
                iconClass: 'fa fa-pencil-square-o text-primary',
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
                iconClass: 'fa fa-trash-o text-primary',
            }
            eventArr.push(objEventDelete);
        }
        if(gridConfig.activeEvent != undefined) {
            var funcActive = function(e) {
                e.preventDefault();
                var dataItem = this.dataItem($(e.currentTarget).closest("tr"));
                gridConfig.activeEvent(dataItem);
            }

            var objEventActive = {
                click: funcActive,
                name: "active",
                template: '<input class="apple-switch" type="checkbox" id="sxactive">'
            }
            gridConfig.columns.push({
                title: M.util.get_string('examvisible', 'local_newsvnr'),
                command: objEventActive,
                width: 100
            });
        }

        if(eventArr.length > 0) {
            gridConfig.columns.push({
                title: M.util.get_string('action', 'local_newsvnr'),
                command: eventArr,
                width: 180
            });    
        }
        
        if (gridConfig.enrollExamUsers != undefined) {
            var funcEnroll = function(e) {
                e.preventDefault();
                var dataItem = this.dataItem($(e.currentTarget).closest("tr"));
                gridConfig.enrollExamUsers(dataItem);
            }
            var objEventEdit = {
                click: funcEnroll,
                text: M.util.get_string('enrol', 'local_newsvnr'),
                name: "enroll",
                iconClass: 'fa fa-user-plus text-primary mr-1',
            }
            gridConfig.columns.push({
                title: M.util.get_string('manageenroll', 'local_newsvnr'),
                command: objEventEdit,
                width: 155
            });
            // Str.get_strings(strings).then(function(s) {
                
            // });
        }
        if (gridConfig.listSubjectExamDetailEvent != undefined) {
            var funcListSubjectExamDetail = function(e) {
                e.preventDefault();
                var dataItem = this.dataItem($(e.currentTarget).closest("tr"));
                gridConfig.listSubjectExamDetailEvent(dataItem);
            }
            var objEventListSubjectExamDetail = {
                click: funcListSubjectExamDetail,
                text: M.util.get_string('list', 'local_newsvnr'),
                name: "",
                iconClass: 'fa fa-list-alt text-primary mr-1',
            }
            gridConfig.columns.push({
                title: '',
                command: objEventListSubjectExamDetail,
                width: 155
            });
         
        }
        return {
            dataSource: newDatasourceGrid(gridConfig),
            persistSelection: true,
            groupable: false,
            //sortable: true,
            resizable: true,
            //dataBound: gridConfig.dataBound,
            height: gridConfig.height,
            toolbar: gridConfig.toolbar,    
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