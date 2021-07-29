define(['jquery', 'kendo.all.min', 'core/config', 'core/notification', 'dttable', 'core/str'], function($, kendo, Config, Notification, dttable, Str) {

    return {

        api_managerment: function() {
            // $body.addClass("loading");
            var strings = [{
                    key: 'functionapi',
                    component: 'local_newsvnr'
                },
                {
                    key: 'urlapi',
                    component: 'local_newsvnr'
                },
                {
                    key: 'methodapi',
                    component: 'local_newsvnr'
                },
                {
                    key: 'contenttypeapi',
                    component: 'local_newsvnr'
                },
                {
                    key: 'description',
                    component: 'local_newsvnr'
                },
                {
                    key: 'action',
                    component: 'local_newsvnr'
                }

               
               
               
            ];
            Str.get_strings(strings).then(function(s) {

                var gird, createGrid = function() {
                    
                    var columns =  [{
                                field: "functionapi",
                                title: s[0],
                                width: "200px"
                            },
                            {
                                field: "url",
                                title: s[1],
                                width: "500px"
                            },
                            {
                                field: "method",
                                title: s[2],
                                width: "100px"
                            },
                            {
                                field: "contenttype",
                                title: s[3],
                                width: "150px"
                            },
                            {
                                template: function(e) {
                                    return e.description;
                                },
                                field: "description",
                                title: s[4],
                                width: "300px"
                            },
                            {
                                template: function(e) {
                                    return e.listbtn;
                                },
                                field: "listbtn",
                                title: s[5],
                                width: "200px"
                            }

                        ];
                    var script = Config.wwwroot + '/local/newsvnr/restfulapi/webservice.php?action=api_managerment';
                    var settings = {
                        // async: false,
                        type: 'GET',
                        processData: true,
                        contentType: "application/json"
                    };
                    grid = $('#showlistapi_data').kendoGrid({
                        dataSource: {
                            transport: {
                                read: {
                                    url: script,
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
                                    if(data != null && data.length > 0)
                                        return data[0].total; 
                                },
                                model: {
                                    id: "functionapi",
                                    fields: {
                                        functionapi: {
                                            type: "string"
                                        },
                                        url: {
                                            type: "string"
                                        },
                                    }
                                }
                            },
                            pageSize: 10,
                            serverPaging: true,
                            serverFiltering: true,
                            serverSorting: true
                        },
                        toolbar: ["search"],
                        search: {
                            fields: ["functionapi"]
                        },
                        sortable: true,
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
                    }).data('kendoGrid');
                };

                createGrid();
               
            });
            
        }
    };
});