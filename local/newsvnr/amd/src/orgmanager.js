define(['jquery', 'kendo.all.min', 'core/config', 'core/notification', 'dttable', 'core/str'], function($, kendo, Config, Notification, dttable, Str) {

    return {

        orgmanager: function() {
            // $body.addClass("loading");
            var strings = [{
                    key: 'action',
                    component: 'local_newsvnr'
                },
                {
                    key: 'catename',
                    component: 'local_newsvnr'
                },
                {
                    key: 'catecode',
                    component: 'local_newsvnr'
                },
                {
                    key: 'orgname',
                    component: 'local_newsvnr'
                },
                {
                    key: 'orgcode',
                    component: 'local_newsvnr'
                },
                {
                    key: 'orgcode',
                    component: 'local_newsvnr'
                },
                {
                    key: 'jobtitlename',
                    component: 'local_newsvnr'
                },
                {
                    key: 'jobtitlecode',
                    component: 'local_newsvnr'
                },
                {
                    key: 'posname',
                    component: 'local_newsvnr'
                },
                {
                    key: 'poscode',
                    component: 'local_newsvnr'
                },
                {
                    key: 'description',
                    component: 'local_newsvnr'
                },
                {
                    key: 'managerid',
                    component: 'local_newsvnr'
                },
                {
                    key: 'parentid',
                    component: 'local_newsvnr'
                },
                {
                    key: 'numbermargin',
                    component: 'local_newsvnr'
                },
                {
                    key: 'numbercurrent',
                    component: 'local_newsvnr'
                },
                {
                    key: 'namebylaw',
                    component: 'local_newsvnr'
                },
                {
                    key: 'orgpositioncode',
                    component: 'local_newsvnr'
                },
                {
                    key: 'orgpositionname',
                    component: 'local_newsvnr'
                },
            ];
            Str.get_strings(strings).then(function(s) {

                var gird, createGrid = function(param, columns) {
                    if(!columns)
                        columns =  [{
                                field: "name",
                                title: s[1],
                                width: "500px"
                            },
                            {
                                field: "code",
                                title: s[2],
                                width: "200px"
                            },
                            {
                                template: function(e) {
                                    return e.description;
                                },
                                field: "description",
                                title: s[10],
                                width: "200px"
                            },
                            {
                                template: function(e) {
                                    return e.listbtn;
                                },
                                field: "listbtn",
                                title: s[0],
                                width: "200px"
                            },
                        ];
                    var script = Config.wwwroot + '/local/newsvnr/ajax/orgdata.php?section=' + param;
                    var settings = {
                        // async: false,
                        type: 'GET',
                        data: {
                            section: param
                        },
                        processData: true,
                        contentType: "application/json"
                    };
                    grid = $('#showtable_data').kendoGrid({
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
                                    id: "code",
                                    fields: {
                                        name: {
                                            type: "string"
                                        },
                                        code: {
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
                            fields: ["name"]
                        },
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
                    }).data('kendoGrid');
                };
                createGrid('orgcate_list');
                $('[data-region="orgmanager-page"] button').click(function(event) {
                        var orgcate_columns = [{
                                field: "name",
                                title: s[1],
                                width: 300
                            },
                            {
                                field: "code",
                                title: s[2],
                                width: 150
                            },
                            {
                                template: function(e) {
                                    return e.description;
                                },
                                field: "description",
                                title: s[10],
                         
                            },
                            {
                                template: function(e) {
                                    return e.listbtn;
                                },
                                field: "listbtn",
                                title: s[0],
                                width: 120
                            },
                        ];
                        var orgstructure_columns = [{
                                field: "name",
                                title: s[3],
                                width: 300
                            },
                            {
                                field: "code",
                                title: s[4],
                                width: 150
                            },
                            {
                                field: "orgcatename",
                                title: s[1],
                                width: 150
                            },
                            {
                                field: "managername",
                                title: s[11],
                                width: 150
                            },
                            {
                                field: "parentname",
                                title: s[12],
                                width: 150
                            },
                            {
                                field: "numbermargin",
                                title: s[13],
                                width: 100
                            },
                            {
                                field: "numbercurrent",
                                title: s[14],
                                width: 100
                            },
                            {
                                template: function(e) {
                                    return e.description;
                                },
                                field: "description",
                                title: s[10],
                                width: 200
                                
                            },
                            {
                                template: function(e) {
                                    return e.listbtn;
                                },
                                field: "listbtn",
                                title: s[0],
                                width: 120
                            },
                        ];
                        var orgjobtitle_columns = [{
                                field: "name",
                                title: s[6],
                                width: 300
                            },
                            {
                                field: "code",
                                title: s[7],
                                width: 150
                            },
                            {
                                field: "namebylaw",
                                title: s[15],
                                width: 300
                            },
                            {
                                template: function(e) {
                                    return e.description;
                                },
                                field: "description",
                                title: s[10],
                                
                            },
                            {
                                template: function(e) {
                                    return e.listbtn;
                                },
                                field: "listbtn",
                                title: s[0],
                                width: 120
                            },
                        ];
                        var orgposition_columns = [{
                                field: "name",
                                title: s[16],
                                width: 300
                            },
                            {
                                field: "code",
                                title: s[17],
                                width: 150
                            },
                            {
                                field: "namebylaw",
                                title: s[15],
                                width: 300
                            },
                            {
                                field: "orgjobtitlename",
                                title: s[6],
                                width: 300
                            },
                            {
                                field: "orgstructurename",
                                title: s[3],
                                width: 300
                            },
                            {
                                template: function(e) {
                                    return e.description;
                                },
                                field: "description",
                                title: s[10],
                                width: 200
                               
                            },
                            {
                                field: "level",
                                title: 'level',
                                width: 300
                            },
                            {
                                template: function(e) {
                                    return e.listbtn;
                                },
                                field: "listbtn",
                                title: s[0],
                                width: 120
                            }
                        ];
                        var param = event.target.id; 
                        var columns = '';   
                        if (param === 'orgcate_list')
                            columns = orgcate_columns;
                        if (param === 'orgstructure_list')
                            columns = orgstructure_columns;
                        if (param === 'orgjobtitle_list')
                            columns = orgjobtitle_columns;
                        if (param === 'orgposition_list')
                            columns = orgposition_columns;
                        
                        grid.destroy();

                        $("#showtable_data").empty();

                        createGrid(param, columns);
                        
                });
            });
            
        }
    };
});