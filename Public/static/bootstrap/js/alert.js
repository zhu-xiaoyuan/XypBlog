//重定义alert
function alert(msg){
    $('#alert').html("<div style='margin-left:30%;margin-top:30px' class='alert message fade in hide'><a class='dismiss close' data-dismiss='alert'>×</a><label>"
        + msg + "</label></div>"
    );
    $(".alert").show();
    $(".alert").delay(2000).fadeIn(1000).fadeOut(500);
}