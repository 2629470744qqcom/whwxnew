//star
$(document).ready(function(){
    var stepW = 24;
    var description = new Array("","","","","");
    var stars = $("#star > li");
    var descriptionTemp;
    $("#showb").css("width", 120);
    stars.each(function(i){
        $(stars[i]).click(function(e){
            var n = i+1;
            $("#showb").css({"width":stepW*n});
            $("#showb").data('id', n);
            descriptionTemp = description[i];
            $(this).find('a').blur();
            return stopDefault(e);
            return descriptionTemp;
        });
    });
    stars.each(function(i){
        $(stars[i]).hover(
            function(){
                $(".description").text(description[i]);
            },
            function(){
                if(descriptionTemp != null)
                    $(".description").text("当前您的评价为："+descriptionTemp);
                else 
                    $(".description").text(" ");
            }
        );
    });
});
function stopDefault(e){
    if(e && e.preventDefault)
           e.preventDefault();
    else
           window.event.returnValue = false;
    return false;
};
