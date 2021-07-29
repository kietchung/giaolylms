     function getDiscussionId(urlink) {
         var url = new URL(urlink);
         var query_string = url.search;
         var search_params = new URLSearchParams(query_string);
         var id = search_params.get('id');
         return id;
     }
     var page = 1;
     var getURL = window.location;
     var discussionID = getDiscussionId(getURL);
     var list_comment = $('#list_comment');
     $('#see-more').click(function(e) {
         $('.loading-page').addClass('active');
         e.preventDefault();
         page++;
         $.ajax({
             url: "./ajax/pagination_comment.php",
             method: "GET",
             data: {
                 page: page,
                 discussionid: discussionID
             },
             success: function(data) {
                 if (data != "") {
                    list_comment.append(data);
                    $('.loading-page').removeClass('active');
                 }
             }
         });
     });