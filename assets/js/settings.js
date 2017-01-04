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
    jQuery('.known_element_fonts').change(function() {

        var fontSelected = jQuery(this)[0].value;

        // The select for the font weights for the element changed.
        var font_weight_list = jQuery(this).closest('tr').next().find('.known_element_fonts_weights');

        console.dir(data);

        // If nothing is selected. just set default and exit.
        if(!data.usable_fonts[fontSelected]){
            setDefaultValueWeights(data, font_weight_list);
            return;
        }

        // Reset the list.
        font_weight_list.empty();

        // Add all the variants to the select list.
        // If option exists set to selected, otherwise set "regular" as default.
        data.usable_fonts[fontSelected].variants.forEach(function(value) {
            var newOption = jQuery("<option></option>").attr("value", value).text(value);
            if(data.options_values[font_weight_list.attr('id')] == value){
                newOption.prop("selected", "selected");
            }

            if(!data.options_values[font_weight_list.attr('id')] && value == "regular"){
                newOption.prop("selected", "selected");
            }

            font_weight_list.append(newOption);
        });
    });

    function setDefaultValueWeights(data, font_weight_list){
        // First empty the list.
        font_weight_list.empty();

        // Add the default list with default as only option.
        var newOption = jQuery("<option></option>").attr("value", "").text(data.default_label);
        newOption.prop("selected", "selected");
        font_weight_list.append(newOption);
    }

    jQuery('.known_element_fonts').trigger( "change" );
});

jQuery(document).ready(function() {
    jQuery("div#tabs").tabs();

    jQuery("button#add-tab").click(function() {

        var num_tabs = jQuery("div#tabs ul li").length + 1;

        jQuery("div#tabs ul").append(
            "<li><a href='#tab" + num_tabs + "'>#" + num_tabs + "</a></li>"
        );
jQuery("div#tabs").append(
            "<div id='tab" + num_tabs + "'>#" + num_tabs + "</div>"
        );
        jQuery("div#tabs").tabs("refresh");
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
            var wrapper = jQuery('.font_file_wrapper').last(); //Input field wrapper
            jQuery(wrapper).after(fieldHTML); // Add field html
            x++; //Increment field counter
        }
    });

    var selectedFiles = [];
    jQuery('body').on('change', '.add_font_file', function() {
        var element = jQuery(this);
        // Remove the old selected file extension from the array if exists.
        var oldFileExtesion = getFileExtension(element[0].oldvalue);
        var oldIndex = selectedFiles.indexOf(oldFileExtesion);
        if(oldIndex != -1) {
            selectedFiles.splice(oldIndex, 1);
        }

        // If the file extension is empty or already exists. Remove it with wrap.
        var fileExtesion = getFileExtension(element[0].files[0].name);
        if(fileExtesion == "" || selectedFiles.indexOf(fileExtesion) != -1){
            // Reset a fake form to reset the input field.
            element.wrap('<form>').closest('form').get(0).reset();
            element.unwrap();

            // Show an error message for trying to upload the same font format.
            jQuery('.custom_font_message.fo_warning').show().delay(5000).fadeOut();
            return;
        }

        // Add the selected file extension to the array.
        selectedFiles.push(fileExtesion);
    });

    function getFileExtension(name)
    {
       var found = name.lastIndexOf('.') + 1;
       return (found > 0 ? name.substr(found) : "");
    }

    jQuery('#add_font_form').on('click','.remove_button', function(e){ //Once remove button is clicked
        e.preventDefault();

        var element = jQuery(this).closest('.font_file_wrapper').find('.add_font_file');

        if(element[0].files.length > 0){
            var extesion = getFileExtension(element[0].files[0].name);
            var index = selectedFiles.indexOf(extesion);
            if(index != -1) {
                selectedFiles.splice(index, 1);
            }
        }

        jQuery(this).closest('.font_file_wrapper').remove(); //Remove field html
        x--; //Decrement field counter
    });
});