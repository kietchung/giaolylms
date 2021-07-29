define(['jquery', 'core/config','core/str','local_newsvnr/initkendogrid','alertjs'], ($, Config, Str, kendo, alertify) => {
	var gridName = '#logreviewapi';
	var script = Config.wwwroot +  '/local/newsvnr/restfulapi/webservice.php';
    var strings = [
    	{
            key: 'number',
            component: 'block_vnr_db_mycourse'
        },
        {
            key: 'codecourse',
            component: 'local_newsvnr'
        },
        {
            key: 'coursename',
            component: 'local_newsvnr'
        },     
        {
            key: 'finishdate',
            component: 'block_vnr_db_mycourse'
    	}
    ];
    var init = function() {
		var kendoConfig = {};
    	Str.get_strings(strings).then(function(s) {
			var settings = {			
				url: script,
				type : 'GET',
	        	dataType: "json",
	            contentType: 'application/json; charset=utf-8',
	            data: {
	            	action:'logreviewapi'
	            }
			};
			var columns = [
				{
	                field: "time",
	                title: s[0],
	                width: "80px"
				},
				{
	                field: "action",
	                title: s[1],
	                width: "120px"
				},
				{
	                field: "url",
	                title: s[2],
	                width: "120px"
				},
				{
	                field: "info",
	                title: s[3],
	                width: "100px"
				}
			];
			kendoConfig.selectable = false;
			kendoConfig.columns = columns;
			kendoConfig.apiSettings = settings;
			var gridData = kendo.initGrid(kendoConfig);
			$(gridName).kendoGrid(gridData);
		})
    }
	return {
		init : init
	}
});