define(['jquery', 'core/config', 'core/str', 'kendo.all.min'], function($, Config, Str, kendo) {
    var kendoConfigDefault = {
            apiSettings: {
                type:"POST",
                dataType:"json",
                processData:true,
                contenttype:"application/json"
            }
        }
    var initDropDownList = function(kendoConfig) {
        Object.assign(kendoConfig.apiSettings,kendoConfigDefault.apiSettings)
        return {
            dataTextField: "name",
            dataValueField: kendoConfig.value,
            dataSource: newDataSource(kendoConfig),
            autoBind: false,
            cascadeFrom: kendoConfig.cascadeFrom,
            height: 200,
            filter: "contains",
            messages: {
              noData: "empty"
            },
            optionLabel: kendoConfig.optionLabel,
            template: kendoConfig.template
        }
    }
    var initSearchAutoComplete = function(kendoConfig) {
        Object.assign(kendoConfig.apiSettings,kendoConfigDefault.apiSettings)
        return {
            dataTextField: kendoConfig.textfield,
            filter: "contains",
            minLength: 3,
            dataSource: newDataSource(kendoConfig),
            select:kendoConfig.select,
            template:kendoConfig.template
        }
    }   
    var newDataSource = function(kendoConfig) {   
        return new kendo.data.DataSource({
            transport: {
                read: kendoConfig.apiSettings,
                parameterMap: function(options, operation) {
                    if (operation == "read") {
                        if (options["filter"]) {
                            if(options["filter"]["filters"][0])
                                options["q"] = options["filter"]["filters"][0].value;
                        }
                        return options;
                    }
                }
            },
            // serverPaging: true,
            // serverFiltering: true,
            // serverSorting: true,
        });
    };
    return {
        initDropDownList: initDropDownList,
        initSearchAutoComplete: initSearchAutoComplete
    }
})
// vd:
// var kendoConfig = {};
// kendoConfig.cascadeFrom = "examname";
// kendoConfig.apiSettings = apiSettings;
// kendoConfig.value = "examsubjectexamid";
// var initDropDownList = kendoService.initDropDownList(kendoConfig);
// $("#subjectname").kendoDropDownList(initDropDownList);