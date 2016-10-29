jQuery(document).ready(function($){
	$('.site-url select').on('change', function(){
		var param = $(this).val();
		var url = $('.site-url object').attr('data');
		url += param;
		$('.site-url object').attr('data', url);
	}); 
});