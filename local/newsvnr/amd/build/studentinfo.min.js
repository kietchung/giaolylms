define(['jquery', 'core/config','core/str','local_newsvnr/initkendogrid','alertjs'], ($, Config, Str, kendo, alertify) => {
	var gridName = '#mycourse';
	var script = Config.wwwroot + '/local/newsvnr/ajax/dashboard/mycourse.php';
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
    	},
        {
            key: 'processfinish',
            component: 'block_vnr_db_mycourse'
        },     
        {
            key: 'grade',
            component: 'block_vnr_db_mycourse'
    	},
    	{
            key: 'rank',
            component: 'block_vnr_db_mycourse'
    	},
        {
            key: 'timeopencourse',
            component: 'block_vnr_db_mycourse'
    	},
    	{
            key: 'timeendcourse',
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
			};
			var columns = [
				{
	                field: "rownum",
	                title: s[0],
	                width: "80px"
				},
				{
	                field: "courseid",
	                title: s[1],
	                width: "120px"
				},
				{
					template: "<a href='#: href #' target='_blank'>#: name #</a>",
	                field: "name",
	                title: s[2],
	                width: "150px"
				},
				{
	                field: "timecompleted",
	                title: s[3],
	                width: "100px"
				},
				{
	                field: "startdate",
	                title: s[7],
	                width: "100px"
				},
				{
	                field: "enddate",
	                title: s[8],
	                width: "100px"
				},
	            {
	                field: "process",
	                template:"<div class='d-flex participants-collum'><div class='progress course'><div class='progress-bar' role='progressbar' aria-valuenow='#: process #' aria-valuemin='0' aria-valuemax='100' style='width:#: process #'></div></div><div>#: process #</div></div></div>",
	                title: s[4],
	                width: "150px"
	            },
	            {
	                field: "gradefinal",
	                title: s[5],
	                width: "80px"
	            },
	            {
	                field: "rank",
	                title: s[6],
	                width: "80px"
	            },
	            {
	            	field: "timespent",
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