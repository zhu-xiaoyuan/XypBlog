
function alert_info(type,title,data,b){
    switch (type){
        case 'success':
            var that = $("#modal-success");
            alert_show(title,data,that,b);
            break;
        case 'danger':
            var that = $("#modal-danger");
            alert_show(title,data,that,b);
            break;
        case 'warning':
            var that = $("#modal-warning");
            alert_show(title,data,that,b);
            break;
        case 'info':
            var that = $("#modal-info");
            alert_show(title,data,that,b);
            break;
    }
}
function alert_show(title,data,that,b){
    if(that){
        that.find(".modal-title").text(title);
        that.find(".modal-body").text(data);
        that.modal({show:true});
        setTimeout(function(){
            that.find(".btn").click();
            if(b){
                location.reload();
            }
        },2000)
        that.find(".btn").click(function(){
            if(b){
                location.reload();
            }
        });

    }
}
function L_trim(str){
    return str.replace(/(^\s*)|(\s*$)/g, "");
}

function pageInfo( settings, start, end, max, total, pre ) {
    if(total==0){
        start=0;
    }
    return '第 ' + start +" 到 "+ end + ' 行   共' + total + '行';
}

bootbox.setDefaults("locale","zh_CN");