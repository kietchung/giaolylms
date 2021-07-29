define(['jquery', 'kendo.all.min', 'core/config', 'core/notification', 'dttable', 'core/str'], function($, kendo, Config, Notification, dttable, Str) {

    //loại bỏ thẻ html
    function strip_tags(str) {
        str = str.toString();
        return str.replace(/<\/?[^>]+>/gi, '');
    }

    return {
        orgmain: function() {
            $body = $('body');
            var st = $("#search-term").val();
            var data;
            var tv;
            var grid;
            var settings = {
                type: 'GET',
                data: {
                    org_struct: 1
                },
                processData: true,
                contentType: "application/json"
            };
            
            var script = Config.wwwroot + '/local/newsvnr/ajax.php';
            $('[data-region="buttons-iframe"] #userdetail_orgstructure').click(function() {
                $('[data-region="userdetail-iframe"]').removeClass("d-none");
            });
            $body.addClass("loading");
            //Xuất cây phòng ban
            $.ajax(script, settings)
                .then(function(response) {
                    if (response.error) {
                        Notification.addNotification({
                            message: response.error,
                            type: "error"
                        });
                    } else {
                        // Reload the page, don't show changed data warnings.
                        if (typeof window.M.core_formchangechecker !== "undefined") {
                            window.M.core_formchangechecker.reset_form_dirty_state();
                        }
                        $('#search-term').on('keyup', function() {
                            var treeView = $("#treeview-sprites").getKendoTreeView();
                            treeView.dataSource.data(data);
                            // ignore if no search term
                            if ($.trim($(this).val()) == '') {
                                return;
                            }
                            var term = this.value.toUpperCase();
                            var tlen = term.length;
                            $('#treeview-sprites span.k-in').each(function(index) {
                                var text = $(this).text();
                                var html = '';
                                var q = 0;
                                while ((p = text.toUpperCase().indexOf(term, q)) >= 0) {
                                    html += text.substring(q, p) + '<span class="highlight">' + text.substr(p, tlen) + '</span>';
                                    q = p + tlen;
                                }

                                if (q > 0) {
                                    html += text.substring(q);
                                    var dataItem = treeView.dataItem($(this));
                                    dataItem.set("text", html);
                                    $(this).parentsUntil('.k-treeview').filter('.k-item').each(

                                        function(index, element) {
                                            $('#treeview-sprites').data('kendoTreeView').expand($(this));
                                            $(this).data('search-term', term);
                                        });
                                }
                            });
                            $('#treeview-sprites .k-item').each(function() {
                                if ($(this).data('search-term') != term) {
                                    $('#treeview-sprites').data('kendoTreeView').collapse($(this));
                                }
                            });
                        });
                        // window.location.reload();
                        data = JSON.parse(response);
                        tv = $("#treeview-sprites").kendoTreeView({
                            dataSource: data
                        }).data('kendoTreeView');
                        $body.removeClass("loading");
                    }
                    return;
                }).fail(Notification.exception);
            //click vào cây phòng ban
            $('#treeview-sprites').on('click', '', function() {
                $('[data-region="buttons-iframe"] #orgstructuredetail').removeClass("btn-primary");
                $('[data-region="buttons-iframe"] #list_users').removeAttr('disabled');
                $('[data-region="buttons-iframe"] #orgstructuredetail').removeAttr('disabled');

                var selected = tv.select(),
                    item = tv.dataItem(selected);
                if (item) {
                    var itemid = item.id;
                    var itemtext = item.text;
                    document.getElementById("search-term").value = strip_tags(itemtext);
                }
                var settings = {
                    // async: false,
                    type: 'GET',
                    data: {
                        section: 'orgmain_list',
                        orgstructureid: itemid
                    },
                    processData: true,
                    contentType: "application/json"
                };

                $body.addClass("loading");
                var script = Config.wwwroot + '/local/newsvnr/ajax/orgdata.php';
                $body.removeClass("loading");
                $.ajax(script, settings)
                    .then(function(response) {

                        if (response.error) {
                            Notification.addNotification({
                                message: response.error,
                                type: "error"
                            });
                        } else {
                            var data = JSON.parse(response);
                            $('#show_orgform').html(data.form); 
                            var strings = [
                                {
                                    key: 'nodatatable',
                                    component: 'local_newsvnr'
                                },
                                {
                                    key: 'usercode',
                                    component: 'local_newsvnr'
                                },
                                {
                                    key: 'username',
                                    component: 'local_newsvnr'
                                },
                                {
                                    key: 'orgstructureunder',
                                    component: 'local_newsvnr'
                                },
                                {
                                    key: 'position_name',
                                    component: 'local_newsvnr'
                                },
                                {
                                    key: 'compstandard',
                                    component: 'local_newsvnr'
                                },
                                {
                                    key: 'compless',
                                    component: 'local_newsvnr'
                                },
                                {
                                    key: 'courselink',
                                    component: 'local_newsvnr'
                                },
                                {
                                    key: 'evidence',
                                    component: 'local_newsvnr'
                                },
                                {
                                    key: 'timecompletecourse',
                                    component: 'local_newsvnr'
                                },
                                {
                                    key: 'completed',
                                    component: 'local_newsvnr'
                                },
                                {
                                    key: 'progress',
                                    component: 'local_newsvnr'
                                },
                            ];
                            Str.get_strings(strings).then(function(s) {
                                columns = [{
                                        template: '<div class="hyperlink" data-toggle="tooltip-usercode" title="Nhấn DoubleClick xem chi tiết nhân viên">#: usercode #</div>',
                                        field: "usercode",
                                        title: s[1],
                                        width: "200px"
                                    },
                                    {   
                                        template: '<div class="hyperlink" data-toggle="tooltip-usercode" title="Nhấn DoubleClick xem chi tiết nhân viên">#: uname #</div>',
                                        field: "uname",
                                        title: s[2],
                                        width: "200px"
                                    },
                                    {
                                        field: "oname",
                                        title: s[3],
                                        width: "200px"
                                    },
                                    {
                                        field: "opname",
                                        title: s[4],
                                        width: "200px"
                                    },
                                    {
                                        field: "positioncomp",
                                        title: s[5],
                                        width: "200px"
                                    },
                                    {
                                        field: "positioncomp_number",
                                        title: s[6],
                                        width: "200px"
                                    },
                                ];
                                var orguser_script = Config.wwwroot + '/local/newsvnr/ajax/orgdata.php?section=orgmain_list&orgstructureid=' + itemid;
                                //lấy dánh sách nhân viên theo phòng ban và show ra gird
                                if(grid != null || grid !== 'undefined') {
                                    $('#showtable_data').empty();
                                }
                                grid = $('#showtable_data').kendoGrid({
                                    dataSource: {
                                        transport: {
                                            read: {
                                                url: orguser_script,
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
                                            data: "userdata",
                                            total: function(data) {
                                                if (data.userdata != null && data.userdata.length > 0)
                                                    return data.userdata[0].total;
                                            },
                                        },
                                        pageSize: 10,
                                        serverPaging: true,
                                        serverFiltering: true,
                                        serverSorting: true
                                    },
                                    toolbar: ["search"],
                                    search: {
                                        fields: ["usercode"]
                                    },
                                    sortable: {
                                        mode: "single",
                                        allowUnsort: false,
                                        field: "usercode"
                                    },
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
                                    change: onChange,
                                    selectable: true,
                                    columns: columns
                                }).data('kendoGrid');
                                $body.removeClass("loading");
                                function onChange(arg) {
                                    var grid = this;
                                    var selected = $.map(this.select(), function(item) {
                                        var usercode = grid.dataItem(item);
                                                        var modal_settings = {
                                        // async: false,
                                        type: 'GET',
                                        data: {
                                            section: 'orgmain_list',
                                            modalsection: 'modalinfo',
                                            orgstructureid: itemid,
                                            usercode: usercode.usercode

                                        },
                                        processData: true,
                                        contentType: "application/json"
                                    };
                                    var modal_script = Config.wwwroot + '/local/newsvnr/ajax/orgdata.php';
                                    var grid_script = Config.wwwroot + '/local/newsvnr/ajax/orgdata.php?section=orgmain_list&modalsection=gridinfo&orgstructureid=' + itemid + '&usercode=' + usercode.usercode;
                                        $body.addClass("loading");
                                        $.ajax(modal_script, modal_settings)
                                            .then(function(response) {
                                                var data = JSON.parse(response);
                                                $('#showmodal_data').html(data.modal);
                                                $('#userdetail').modal('toggle');
                                                $('#userdetailtable').kendoGrid({
                                                    dataSource: {
                                                        transport: {
                                                            read: {
                                                                url: grid_script,
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
                                                                if (data != null && data.length > 0)
                                                                    return data[0].total;
                                                            },

                                                        },
                                                        // pageSize: 10,
                                                        // serverPaging: true,
                                                        // serverFiltering: true,
                                                        // serverSorting: true
                                                    },
                                                    scrollable: true,
                                                    columns: [{
                                                            field: "competency",
                                                            title: s[5],
                                                            width: 300
                                                        },
                                                        {
                                                            template: function(e) {
                                                                return e.courselink;
                                                            },
                                                            field: "courselink",
                                                            title: s[7],
                                                            width: 300
                                                        },
                                                        {
                                                            template:function(e) {
                                                                return e.courseprogress;
                                                            },
                                                            title: s[11],
                                                            width: 200
                                                        },
                                                        {
                                                            template: function(e) {
                                                                return e.evidence;
                                                            },
                                                            field: "evidence",
                                                            title: s[8],
                                                            width: 100
                                                        },
                                                        {
                                                            template: function(e) {
                                                                return e.timecompleted;
                                                            },
                                                            field: "timecompleted",
                                                            title: s[9],
                                                            width: 200
                                                        },
                                                        {
                                                            template: function(e) {
                                                                return e.completed;
                                                            },
                                                            field: "completed",
                                                            title: s[10],
                                                            width: 150
                                                        },
                                                    ]
                                                });
                                                $body.removeClass("loading");
                                            }).fail(Notification.exception);
                                                    });

                                                 
                                }
                                var click_orgdetail = $('[data-region="buttons-iframe"] #orgstructuredetail').click(function() {
                                    $(this).addClass('btn-primary');
                                    $('[data-region="buttons-iframe"] #list_users').removeClass('btn-primary')
                                    $('#show_orgform').removeClass("d-none");
                                    $('#showtable_data').addClass("d-none");

                                });
                                var click_listusers = $('[data-region="buttons-iframe"] #list_users').click(function() {
                                    $(this).addClass('btn-primary');
                                    $('[data-region="buttons-iframe"] #orgstructuredetail').removeClass('btn-primary')
                                    $('#showtable_data').removeClass("d-none");
                                    $('#show_orgform').addClass("d-none");

                                });
                              
                                //Show chi tiết năng lực nhân viên theo usercode
                                // $("#showtable_data tbody").on("dblclick", "tr", function() {
                                //     var rowElement = this;
                                //     var row = $(rowElement);
                                //     var selected = grid.select();
                                //     var item = tv.dataItem(selected);
                                //     if(item) {
                                //         console.log(item);
                                //     }
                                //     var data_clicktable = grid.dataItem(row);
                                //     var usercode = data_clicktable.usercode;
                                //     var modal_settings = {
                                //         // async: false,
                                //         type: 'GET',
                                //         data: {
                                //             section: 'orgmain_list',
                                //             modalsection: 'modalinfo',
                                //             orgstructureid: itemid,
                                //             usercode: usercode

                                //         },
                                //         processData: true,
                                //         contentType: "application/json"
                                //     };
                                //     var modal_script = Config.wwwroot + '/local/newsvnr/ajax/orgdata.php';
                                //     var grid_script = Config.wwwroot + '/local/newsvnr/ajax/orgdata.php?section=orgmain_list&modalsection=gridinfo&orgstructureid=' + itemid + '&usercode=' + data_clicktable.usercode;
                                //     if (data_clicktable !== 'undefined') {
                                //         $body.addClass("loading");
                                //         $.ajax(modal_script, modal_settings)
                                //             .then(function(response) {
                                //                 var data = JSON.parse(response);
                                //                 $('#showmodal_data').html(data.modal);
                                //                 $('#userdetail').modal('toggle');
                                //                 $('#userdetailtable').kendoGrid({
                                //                     dataSource: {
                                //                         transport: {
                                //                             read: {
                                //                                 url: grid_script,
                                //                                 dataType: "json",
                                //                                 contentType: 'application/json; charset=utf-8',
                                //                                 type: "GET",
                                //                             },
                                //                             parameterMap: function(options, operation) {
                                //                                 if (operation == "read") {
                                //                                     if (options["filter"]) {
                                //                                         options["q"] = options["filter"]["filters"][0].value;
                                //                                     }
                                //                                     return options;
                                //                                 }
                                //                             }

                                //                         },
                                //                         schema: {
                                //                             total: function(data) {
                                //                                 if (data != null && data.length > 0)
                                //                                     return data[0].total;
                                //                             },

                                //                         },
                                //                         // pageSize: 10,
                                //                         // serverPaging: true,
                                //                         // serverFiltering: true,
                                //                         // serverSorting: true
                                //                     },
                                //                     scrollable: true,
                                //                     columns: [{
                                //                             field: "competency",
                                //                             title: s[5],
                                //                             width: 300
                                //                         },
                                //                         {
                                //                             template: function(e) {
                                //                                 return e.courselink;
                                //                             },
                                //                             field: "courselink",
                                //                             title: s[7],
                                //                             width: 300
                                //                         },
                                //                         {
                                //                             template: function(e) {
                                //                                 return e.evidence;
                                //                             },
                                //                             field: "evidence",
                                //                             title: s[8],
                                //                             width: 100
                                //                         },
                                //                         {
                                //                             template: function(e) {
                                //                                 return e.timecompleted;
                                //                             },
                                //                             field: "timecompleted",
                                //                             title: s[9],
                                //                             width: 200
                                //                         },
                                //                         {
                                //                             template: function(e) {
                                //                                 return e.completed;
                                //                             },
                                //                             field: "completed",
                                //                             title: s[10],
                                //                             width: 150
                                //                         },
                                //                     ]
                                //                 });
                                //                 $body.removeClass("loading");
                                //             }).fail(Notification.exception);
                                //     }
                                // });
                            });

                        }
                        return;
                    }).fail(Notification.exception);
            });

        }
    };
});