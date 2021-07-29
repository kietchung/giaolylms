$(document).ready(function () {
    $(".fa-chevron-down").hide();
    $(".fa-chevron-up").click(function () {

        $(".fa-chevron-up").hide();
        //$(".chat-reply").toggle(500);
        $(".chat-reply").hide();
        $(".fa-chevron-down").show();
       
    });
    $(".fa-chevron-down").click(function () {
        $(".fa-chevron-up").show();
        $(".chat-reply").show();
        $(".fa-chevron-down").hide();
    });
});


