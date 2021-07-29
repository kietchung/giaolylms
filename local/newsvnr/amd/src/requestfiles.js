define(['jquery', 'core/config', 'core/str', 'kendo.all.min'], function($, Config, Str, kendo) {
    var gridRequestFiles = "#requestfile_grid";
    var gridManageRoles = "#rolepermissionsmanage_grid";
    var script = Config.wwwroot + '/local/newsvnr/ajax/requestfiles_generallibrary.php';
    var grid, createRequestFilesGrid = function() {
        var strings = [{
            key: 'acceptfile',
            component: 'local_newsvnr'
        }, {
            key: 'filename',
            component: 'local_newsvnr'
        }, {
            key: 'filetype',
            component: 'local_newsvnr'
        }, {
            key: 'filesize',
            component: 'local_newsvnr'
        }, {
            key: 'filetimecreated',
            component: 'local_newsvnr'
        }, {
            key: 'fileauthor',
            component: 'local_newsvnr'
        }, {
            key: 'download',
            component: 'local_newsvnr'
        }, {
            key: 'filepath',
            component: 'local_newsvnr'
        }];
        Str.get_strings(strings).then(function(s) {
            var columns = [{
                selectable: true,
                width: "50px"
            }, {
                template: function(e) {
                    return e.filename;
                },
                field: "filename",
                title: s[1],
                width: "500px"
            }, {
                field: "filepath",
                title: s[7],
                width: "200px"
            }, {
                field: "filetype",
                title: s[2],
                width: "100px"
            }, {
                field: "filesize",
                title: s[3],
                width: "120px"
            }, {
                template: function(e) {
                    return e.download;
                },
                field: "download",
                title: s[6],
                width: "100px"
            }, {
                field: "timecreated",
                title: s[4],
                width: "120px"
            }, {
                field: "author",
                title: s[5],
                width: "200px"
            }, {
                template: function(e) {
                    return e.listbtn;
                },
                field: "listbtn",
                title: s[0],
                width: "100px"
            }];
            grid = $(gridRequestFiles).kendoGrid({
                dataSource: {
                    transport: {
                        read: {
                            url: script,
                            data: {
                                action: 'requestfiles'
                            },
                            dataType: "json",
                            contentType: 'application/json; charset=utf-8',
                            type: "GET",
                        },
                        parameterMap: function(options, operation) {
                            if (operation == "read") {
                                if (options["filter"]) {
                                    options["q"] = options["filter"]["filters"][0].value;
                                }
                                return options;
                            }
                        }
                    },
                    schema: {
                        total: function(data) {
                            if (data != null && data.length > 0) return data[0].total;
                        },
                    },
                    pageSize: 10,
                    serverPaging: true,
                    serverFiltering: true,
                    serverSorting: true
                },
                toolbar: ["search"],
                search: {
                    fields: ["filename"]
                },
                height: 600,
                sortable: false,
                reorderable: true,
                groupable: false,
                resizable: true,
                filterable: true,
                columnMenu: true,
                pageable: {
                    refresh: true,
                    pageSizes: true,
                    buttonCount: 5
                },
                columns: columns
            });
            return grid;
        });
    }
    var createManageRolesGrid = function() {
        var strings = [{
            key: 'name',
            component: 'local_newsvnr'
        }, {
            key: 'gl_listuser',
            component: 'local_newsvnr'
        }, {
            key: 'gl_viewlistuser',
            component: 'local_newsvnr'
        }, ];
        Str.get_strings(strings).then(function(s) {
            var columns = [
            {
                template: function(e) {
                    return e.name;
                },
                field: "name",
                title: s[0],
                width: 500
            }, {
                command: [{
                    name: "listuser",
                    template: s[1] + " <a class='k-button k-grid-listuser'><span class='k-icon k-i-zoom mr-1'></span>"+ s[2] +"</a>",
                    click(e) {
                        e.preventDefault();
                        var dataItem = this.dataItem($(e.currentTarget).closest("tr"));
                        var dialog = $('#md-viewlistuser');
			            var settings = {
			                type: 'GET',
			                dataType: "json",
			                contentType: 'application/json; charset=utf-8',
			                data: {
			                    action: 'listuser',
			                   	capability : dataItem.name_raw
			                }
			            }
			            function onOpen(e) {
			                kendo.ui.progress($(dialog), true);
			                 $.ajax(script, settings).then(function(resp) {
			                    dialog.data('kendoWindow').content(resp.success);
			                    kendo.ui.progress($(dialog), false);
			                });
			                e.sender.center();
			            }
			           
			            dialog.kendoWindow({
			                width: "450px",
			                title: s[2],
			                modal: true,
			                open: onOpen,
			            });
			            dialog.data('kendoWindow').open(); 
                    }
                }],
                width: 400
            }];
            $(gridManageRoles).kendoGrid({
                dataSource: {
                    transport: {
                        read: {
                            url: script,
                            data: {
                                action: 'listrole'
                            },
                            dataType: "json",
                            contentType: 'application/json; charset=utf-8',
                            type: "GET",
                        }
                    },
                },
                resizable: true,
                columns: columns
            });
        });
    }
    var init = function() {
        $('[data-key=requestaccecpt]').click(function() {
            createRequestFilesGrid();
        });
        $('[data-key=rolepermissionsmanage]').click(function() {
            createManageRolesGrid();
        });
    }
    var refreshGrid = function() {
        createRequestFilesGrid()
        return "OK";
    }
    var acceptFile = function(id) {
        var script = Config.wwwroot + '/local/newsvnr/ajax/requestfiles_generallibrary.php';
        var settings = {
            type: "GET",
            processData: true,
            data: {
                action: 'acceptfile',
                id: id
            }
        };
        $.ajax(script, settings).then(function(resp) {});
    }
    var deleteFile = function(fileName, filePath) {
        var script = Config.wwwroot + '/local/newsvnr/ajax/requestfiles_generallibrary.php';
        var settings = {
            type: "GET",
            processData: true,
            data: {
                action: 'deletefile',
                filename: fileName,
                filepath: filePath
            }
        };
        $.ajax(script, settings).then(function(resp) {});
    }
    return {
        init: init,
        refreshGrid: refreshGrid,
        acceptFile: acceptFile,
        deleteFile: deleteFile
    }
});