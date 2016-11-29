jQuery(document).ready(function(){
    jQuery('#manage_font_id').change(function(){
            select_font_form.submit();
        });

    // Show or hide the sticky footer button
	jQuery(window).scroll(function() {
		if (jQuery(this).scrollTop() > 200) {
	    	jQuery('.go-top').fadeIn(300);
		} else {
	        jQuery('.go-top').fadeOut(300);
		}
    });

    // Animate the scroll to top
    jQuery('.go-top').click(function(event) {
    	event.preventDefault();
		jQuery('html, body').animate({scrollTop: 0}, 300);
    });
});

jQuery(document).ready(function(){
    var l = jQuery('.font_file').clone();
    var maxField = 4; //Input fields increment limitation
    var addButton = jQuery('.add_button'); //Add button selector
    var fieldHTML = '<tr class="font_file_wrapper"><td></td><td>' + jQuery('#font_file_parent').html() + '</td><td><a href="javascript:void(0);" class="remove_button"><i class="fa fa-times fa-2x" aria-hidden="true"></i></a></td></tr>'; //New input field html 
    var x = 1; //Initial field counter is 1
    jQuery(addButton).click(function(){ //Once add button is clicked
        if(x < maxField){ //Check maximum number of input fields
            x++; //Increment field counter
            var wrapper = jQuery('.font_file_wrapper').last(); //Input field wrapper
            jQuery(wrapper).after(fieldHTML); // Add field html
        }
    });

    jQuery('#add_font_form').on('click','.remove_button', function(e){ //Once remove button is clicked
        e.preventDefault();
        jQuery(this).closest('.font_file_wrapper').remove(); //Remove field html
        x--; //Decrement field counter
    });
});