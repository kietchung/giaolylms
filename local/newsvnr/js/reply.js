    
    
function FeedBack(id_comment)
{

            var list_reply = $('#list_reply'+id_comment);

            var form_reply = $('#' + id_comment).parents('.chat-footer').siblings('.form-reply');


            form_reply.slideToggle('fast');
            
            var submit = form_reply.find('#post_reply');

            $(submit).click(function() {

                submit.prop('disabled', true);    
                var content_reply = $(form_reply.find('#content_reply')).val();
                var commentid = $(form_reply.find('#commentid')).val();
                var userid = $(form_reply.find('#userid')).val();
                var fullname = $('#fullname').val();

                var set_content = $(form_reply.find('#content_reply'));

                if(content_reply == "")
                {
                     alert("Bạn chưa nhập bình luận!");
                     submit.prop('disabled', false);    
                }
                else{


                      setTimeout(function(){ 
                        $.ajax({    

                        url: "./ajax/reply.php",
                        method: "POST",
                        data: {
                            userid: userid,
                            fullname: fullname,
                            commentid: commentid,
                            content_reply: content_reply,
                            post_reply: submit.val()
                        },

                        success: function(result) {
                        $('#list_reply'+id_comment).css('display','block');
                          set_content.val('');  
                           list_reply.append(result);
                           submit.prop('disabled', false);    
        
                        }
                        });
                      }
                    , 300 );
                }   
               
            });
    }

    
    function DeleteReply(id_reply)
    {
        var result = confirm("Do you want to delete this?");

            var delete_reply = $('#delete_reply'+id_reply);

            var value = delete_reply.val();

            if (result) { 

                $.ajax({
                    url: "./ajax/reply.php?id_reply=" + id_reply + '&action=' + value,
                    method: "GET",
                    data: {},

                    success: function(data) {


                        delete_reply.parents().find('#reply_' + id_reply).fadeOut(500, function() {
                            $(this).remove();
                        });

                        $(delete_reply).closest('p').fadeOut(300, function(){
                            $(this).remove();
                        })
                    }
                });
            } 
    }