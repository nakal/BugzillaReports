$(document).ready(function(){
  $("div.bz_comment").hide();
  $("tr.bz_bug").hover(
    function () {
      $(this).find("td div.bz_comment").show();
    },
    function () {
      $(this).find("td div.bz_comment").hide();
    }
  )
});

