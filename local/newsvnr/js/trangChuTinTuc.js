$(document).ready(function () {
    
    var _screen = screen.width;

    if (_screen > 768) {
      
        var x1 = $(".box-2").outerHeight() + $(".box-3").outerHeight() -2;
     
        $(".new-image1").css({ 'height': x1 + 'px' });

    }
    if (_screen <= 768 && _screen >500) {
        var x1 = $(".box-2").outerHeight() + $(".box-3").outerHeight() - 2;
        
        $(".new-image1").css({ 'height': x2 + 'px' });
    }
    if (_screen <= 500) {
       
        $(".new-image1").css({ 'width': 100 + '%' });
    }
});

// $(document).ready(function () {
    
//     var _screen = screen.width;
//     alert(_screen);
//     if (_screen > 768) {
//         var x1 = $(".col-md-5").outerHeight() - 2;
      
//         $(".new-image1").css({ 'height': x1 + 'px' });
//     }
//     if (_screen <= 768 && _screen >500) {
//         var x2 = $(".col-sm-5").outerHeight() - 2;
        
//         $(".new-image1").css({ 'height': x2 + 'px' });
//     }
//     if (_screen <= 500) {
       
//         $(".new-image1").css({ 'width': 100 + '%' });
//     }

//$(document).resize(function () {
//    var x = $(".col-md-5").outerHeight() - 2;
//    $(".new-image1").css({ 'height': x + 'px' });
//});