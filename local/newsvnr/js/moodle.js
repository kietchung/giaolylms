$(document).ready(function () {
    var x;
    $('.moodle-describe').hide();
    $('.moodle-discuss').hide();
    $('.moodle-evaluate').hide();
    $('#btn-describe').click(function () {
        $('.moodle-describe').show().animate({ width: '100%', height: 'auto' }, 2000);
        $('.moodle-discuss').hide();
        $('.moodle-evaluate').hide();
    });
    $('#btn-discuss').click(function () {
        $('.moodle-discuss').show();
        $('.moodle-describe').hide();
        $('.moodle-evaluate').hide();

    });
    $('#btn-evaluate').click(function () {
        $('.moodle-evaluate').show();
        $('.moodle-describe').hide();
        $('.moodle-discuss').hide();

    });
    
   
});
