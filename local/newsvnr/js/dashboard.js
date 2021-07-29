function getUrlVars() {
    var vars = {};
    var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
        vars[key] = value;
    });
    return vars;
}

function getUrlParam(parameter, defaultvalue){
    var urlparameter = defaultvalue;
    if(window.location.href.indexOf(parameter) > -1){
        urlparameter = getUrlVars()[parameter];
        }
    return urlparameter;
}
$(document).ready(function () {
    "use strict";
  	var view = getUrlParam('view', 'empty');
    var length = $('.singlebutton form button').length;
    if(length == 3) {
        if(view == 'teacher') {
            $('.singlebutton:eq(1) form button').addClass('active');
        } else if(view == 'student') {
            $('.singlebutton:eq(2) form button').addClass('active');
        }
    } else if (length == 4) {
        if(view == 'teacher') {
            $('.singlebutton:eq(2) form button').addClass('active');
        } else if(view == 'student') {
            $('.singlebutton:eq(3) form button').addClass('active');
        }
    }
});
