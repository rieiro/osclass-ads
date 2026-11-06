$(document).ready(function(){ 
  $('.scroller, .scroller2').css('width', $('.item #sidebar').width() + 'px');

  $('.cw-close').click(function(){
    $(this).parent().parent('#cat-wide').delay(200).slideUp(300);
  });

  $('#sidebar-wrap').show();
  $('#show_hide_filters').addClass('hide');

  $('#show_hide_filters').click(function(){
    if($('#show_hide_filters').attr('class') == 'show') {
      $('#show_hide_filters').addClass('hide').removeClass('show');
      $('.list #main').addClass('short').removeClass('long').dequeue();
      $('#sidebar-wrap').delay(200).show('slide', {direction: 'left'}, 200);
    } else {
      $('#show_hide_filters').addClass('show').removeClass('hide');
      $('.list #main').removeClass('short').delay(200).queue(function(){
        $(this).addClass('long').dequeue();
      });
      $('#sidebar-wrap').hide('slide', {direction: 'left'}, 200);
    }
  });


  $('#menu_h3').click(function(){
    if($('#menu_h3 #closer').attr('class') == 's-minus') {
      $('#menu .menu-wrap').slideUp('fast');
      $('#menu_h3 #closer').addClass('s-plus').removeClass('s-minus');
    } else {
      $('#menu .menu-wrap').slideDown('fast');
      $('#menu_h3 #closer').addClass('s-minus').removeClass('s-plus');
    }
  }); 
 
  $('.is_child').closest('#showSubcat').siblings('h2').addClass('is_parent');
  $('.is_child').closest('.ukaz').show();
  $('.is_child').closest('.ukaz').siblings('.viac_main').hide();
  $('.is_child').siblings('.ukaz').show();
  $('.is_child').siblings('.viac_main').hide();

  $('.icon-close-div').click(function(){
    $(this).parent().fadeOut(300);
  });
});