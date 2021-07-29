define(['jquery', 'core/ajax', 'core/templates'], function($, Ajax, Templates) {

    return /** @alias module:core_search/form-search-user-selector */ {

        processResults: function(selector, results) {
            var orgpositions = [];
            $.each(results, function(index, orgposition) {
                orgpositions.push({
                    value: orgposition.id,
                    label: orgposition._label
                });
            });
            return orgpositions;
        },

        transport: function(selector, query, success, failure) {
  
            var promise;

            // Search within specific course if known and if the 'search within' dropdown is set
            // to search within course or activity.
            var args = {};
            var str = '';
            var treeview = $("#treeview-orgstructure-course").data("kendoTreeView");
            var selected = treeview.select(),item;
            var listorgjobtitle = $('#id_courseofjobtitle').parent().find('[id^="form_autocomplete_selection"] .badge');

            $.each(listorgjobtitle, function(index, value) {
                var isLastElement = index == listorgjobtitle.length -1;
                var data = $(this).attr('data-value');
                if (isLastElement) {
                    str = str + data;
                } else {
                    str = str + data + ',';
                }
               
            })
           
            if (typeof courseid !== "undefined" && $('#id_searchwithin').val() !== '') {
                args.courseid = courseid;
            } else {
                if(selected.length >0)
                    item = treeview.dataItem(selected);
                if (item) {
                    args.orgstructureid = item.id;
                }
                
                args.orgjobtitleid = str;

            }
            // Call AJAX request.
            promise = Ajax.call([{methodname: 'local_newsvnr_loadingorgposition', args: args}]);

            // When AJAX request returns, handle the results.
            promise[0].then(function(results) {
       
                var promises = [];

                // Render label with user name and picture.
                $.each(results, function(index, orgposition) {
                    promises.push(Templates.render('local_newsvnr/form-orgposition-selector', orgposition));
                });

                // Apply the label to the results.
                return $.when.apply($.when, promises).then(function() {
                    var args = arguments;
                    var i = 0;
                    $.each(results, function(index, orgposition) {
                        orgposition._label = args[i++];
                    });
                    success(results);
                    return;
                });

            }).fail(failure);
        }

    };

});