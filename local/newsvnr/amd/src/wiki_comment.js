define(['jquery', 'core/config', 'validatefm', 'local_newsvnr/initkendogrid', 'alertjs', 'core/str', 'kendo.all.min', 'local_newsvnr/initkendocontrolservices'], function($, Config, Validatefm, kendo, alertify, Str, kendoControl, kendoService) {
    "use strict";
    let actionscript = Config.wwwroot + '/local/newsvnr/ajax/wiki_comment.php';
    let addcomment = $('.wiki-add textarea');
    var init = function() {
        $('.post_comment').click(function() {
            $(this).addClass('disabled');
            var userid = $(this).parent().prev().attr('userid');
            var wikipage = $(this).parent().prev().attr('wikipage');
            var contextid = $(this).parent().prev().attr('contextid');
            var action = $(this).attr('action')
            var content = $(this).parent().prev().val();
            var commentid = $(this).parent().prev().attr('commentid');
            var settings = {
                type: "GET",
                processData: true,
                data: {
                    action:action,
                    userid:userid,
                    wikipage:wikipage,
                    content:content,
                    contextid:contextid,
                    commentid:commentid
                }
            }
            $.ajax(actionscript,settings).then(function(response) {
                alertify.success(response)
                $(this).removeClass('disabled');
                location.reload();
            })
        })
        $('.delete_comment').click(function() {
            var commentid = $(this).attr('commentid');
            var settings = {
                type: "GET",
                processData: true,
                data: {
                    commentid:commentid,
                    action:'delete'
                }
            }
            alertify.confirm('Thông báo', 'Xóa bình luận này?', function(){ 
                $.ajax(actionscript,settings).then(function(response) {
                    alertify.success(response)
                    location.reload();
                })
            }, function(){ alertify.error('Cancel')});
        })
        $('.show_edit_comment').click(function() {
            var commentid = $(this).attr('commentid');
            var text = $('.content[commentid="'+commentid+'"]').text();
            $('.edit_comment#'+commentid).toggleClass('d-none');
            $('textarea[commentid="'+commentid+'"]').val(text);
        })
        $('.cancel-comment').click(function() {
            var commentid = $(this).attr('commentid');
            $('.edit_comment#'+commentid).addClass('d-none');
        })
        $('.post_file').click(function() {
            $('.fp-btn-add').click();
        })
        $('body').on('change','input[name="repo_upload_file"]', function () {
            var filename = $('.fp-formset input[type="file"]').val();
            filename = filename.replace('C:\\fakepath\\', '');
            $('.filename').html(filename);
        });
    }
    return {
        init:init
    }
});