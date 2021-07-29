    function getDiscussionId(urlink) {
        var url = new URL(urlink);
        var query_string = url.search;
        var search_params = new URLSearchParams(query_string);
        var id = search_params.get('id');
        return id;
    }

    function PostComment() {
        var button_sutmit = $('#post_comment');
        $(button_sutmit).prop('disabled', true);
        var list = $('#list_comment');
        var set_content = $('#content_comment');
        var content_comment = $('#content_comment').val();
        var post_comment = $('#post_comment').val();
        var URL = window.location;
        var discussionID = getDiscussionId(URL);
        var fullname = $('#fullname').val();
        if (content_comment == "") {
            alert("Bạn chưa nhập bình luận!");
            $(button_sutmit).prop('disabled', false);
        } else {
            $('.loading-page').addClass('active');
            $.post({
                url: "./ajax/comment.php",
                type: "POST",
                data: {
                    content_comment: content_comment,
                    post_comment: post_comment,
                    discussionid: discussionID,
                    fullname: fullname
                },
                success: function(data) {
                    list.prepend(data);
                    set_content.val('');
                    $(button_sutmit).prop('disabled', false);
                    $('.loading-page').removeClass('active');
                }
            });
        }
    }

    function DeleteComment(id_comment) {
        var URL = window.location;
        var discussionID = getDiscussionId(URL);
        var result = confirm("Do you want to delete this?");
        if (result) {
            var delete_comment = $('#delete_comment' + id_comment);
            var value = delete_comment.val();
            var list = $('#list_comment');
            $.ajax({
                url: "./ajax/comment.php?id_comment=" + id_comment + '&action=' + value,
                method: "GET",
                data: {
                    discussionid: discussionID
                },
                success: function(data) {
                    delete_comment.parents().find('#comment_' + id_comment).fadeOut(200, function() {
                        $(this).remove();
                    });
                    list.append(data)
                }
            });
        }
    }