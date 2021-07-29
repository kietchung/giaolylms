define(['jquery', 'core/ajax', 'core/templates'], function($, Ajax, Templates) {

    return /** @alias module:core_search/form-search-user-selector */ {

        processResults: function(selector, results) {
            var divisions = [];
            $.each(results, function(index, division) {
                divisions.push({
                    value: division.id,
                    label: division._label
                });
            });
            return divisions;
        },

        transport: function(selector, query, success, failure) {
  
            var promise;

            // Search within specific course if known and if the 'search within' dropdown is set
            // to search within course or activity.
            var args = {};
            
            var categoryid = $('select[id=id_category]').val();
            
           
            if (typeof courseid !== "undefined" && $('#id_searchwithin').val() !== '') {
                args.courseid = courseid;
            } else {
                if(categoryid != undefined) {
                    args.categoryid = categoryid;
                } else {
                    args.categoryid = 0;
                }

            }
            // Call AJAX request.
            promise = Ajax.call([{methodname: 'local_newsvnr_loadingdivision', args: args}]);
            // When AJAX request returns, handle the results.
            promise[0].then(function(results) {
                var promises = [];

                // Render label with user name and picture.
                $.each(results, function(index, division) {
                    promises.push(Templates.render('local_newsvnr/form-coursesetup-selector', division));
                });

                // Apply the label to the results.
                return $.when.apply($.when, promises).then(function() {
                    var args = arguments;
                    var i = 0;
                    $.each(results, function(index, division) {
                        division._label = args[i++];
                    });
                    success(results);
                    return;
                });

            }).fail(failure);
        }

    };

});