jQuery(window).scroll(function(){
  var sticky = jQuery('.sticky');
  var eTop = jQuery('#poststuff').offset().top;
  var l = eTop - jQuery(window).scrollTop();
  if (l < 0){
  	l = l * -1 + sticky.offsetWidth + 30;
  	sticky.addClass('fixed');
  	sticky.css('top', l + 'px');
  } 
  else sticky.removeClass('fixed');
});