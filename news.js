$(function() {
   $(".upvote, .downvote").click(function() {
       var me = $(this);
       $.get(me.attr("href"));
       me.parent().children().hide();
       return false;
   });
});