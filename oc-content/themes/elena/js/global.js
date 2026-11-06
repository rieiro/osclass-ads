$(document).ready(function(){
  // User_menu show/hide submenu
  $("#user_menu .with_sub").hover(function(){
      $(this).find("ul").fadeIn(200);
  },
  function(){
      $(this).find("ul").fadeOut(200);
  });

  $('#login_open').click(function(e) {
      e.preventDefault();
      $('#login').fadeToggle(300, function(){});
  });

  // Apply the UniForm plugin to pulldows and button
  $("input:file, textarea, select, button, .search select, .search button, .filters select, .filters button, #comments form button, #contact form button, .user_forms form button, .add_item form select, .add_item form button, .modify_profile select, .modify_profile button").uniform({selectAutoWidth: false, fileDefaultText: fileDefaultText,fileBtnText: fileBtnText});

  // $("body").on("click", ".button", function(e) {
    // e.preventDefault();
    // $(this).closest("form").submit();
  // });
  
  // Show advanced search in internal pages
  $("#expand_advanced").click(function(e){
      e.preventDefault();
      $(".search .extras").slideToggle();
  });

  // Show/hide Report as
  $("#report").hover(function(){
      $(this).find("span").show();
  },
  function(){
      $(this).find("span").hide();
  });

  // Hide login box
  $('html').click(function() {
      $('#login').hide();
  });
  $('#login,#login_open').click(function(event){
      event.stopPropagation();
  });

  // PAGINATION FONTAWESOME FIX FOR NEXT, LAST, PREV AND FIRST
  $('.searchPaginationNext').html('<i class="fa fa-angle-right"></i>');
  $('.searchPaginationLast').html('<i class="fa fa-angle-double-right"></i>');
  $('.searchPaginationPrev').html('<i class="fa fa-angle-left"></i>');
  $('.searchPaginationFirst').html('<i class="fa fa-angle-double-left"></i>');


  // USER MENU HIGHLIGHT ACTIVE
  var url = window.location.toString();

  $('.user_account #sidebar li a').each(function(){
    var myHref= $(this).attr('href');
    if( url == myHref) {
      $(this).parent('li').addClass('active');
    }
  });
});