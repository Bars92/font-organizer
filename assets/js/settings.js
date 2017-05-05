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
    var maxField = 6; //Input fields increment limitation
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

jQuery(document).ready(function(){
    jQuery('.known_element_fonts').change(function() {

        var font_selected = jQuery(this).find('option:selected')[0].text;

        // The select for the font weights for the element changed.
        var font_weight_list = jQuery(this).closest('tr').next().find('.known_element_fonts_weights');

        // If nothing is selected. just set default and exit.
        if(!data.usable_fonts[font_selected]){
            font_weight_list.closest('tr').hide();
            return;
        }

        populateFontWeights(font_weight_list, font_selected);

        font_weight_list.closest('tr').show();
    });

    jQuery('#font_id').change(function() {

        var font_selected = jQuery(this).find('option:selected')[0].text;

        // The select for the font weights for the element changed.
        var font_weight_list = jQuery(this).closest('tr').next().find('.known_element_fonts_weights');

        // If nothing is selected. just set default and exit.
        if(!data.usable_fonts[font_selected]){
            font_weight_list.closest('tr').hide();
            return;
        }

        populateFontWeights(font_weight_list, font_selected);

        font_weight_list.closest('tr').show();
    });

    function populateFontWeights(font_weight_list, font_selected){
        // Reset the list.
        font_weight_list.empty();

        // Add default option.
        setDefaultValueWeights(font_weight_list, !data.options_values[font_weight_list.attr('id')]);

        // Add all the variants to the select list.
        // If option exists set to selected, otherwise set "regular" as default.
        data.usable_fonts[font_selected].variants.forEach(function(value) {

            var text = parseValueForText(value);
            var style; 

            if(value.includes("italic")){
                var weight = value.replace("italic", "");
                style = "style='font-weight:" + weight + ";font-style:italic;'";
            }else{
                style = "style='font-weight:" + value + ";'";
            }

            var newOption = jQuery("<option " + style + "></option>").attr("value", value).text(text);
            if(data.options_values[font_weight_list.attr('id')] == value){
                newOption.prop("selected", "selected");
            }

            font_weight_list.append(newOption);
        });
    }

    function setDefaultValueWeights(font_weight_list, selected){
        // First empty the list.
        font_weight_list.empty();

        // Add the default list with default as only option.
        var newOption = jQuery("<option></option>").attr("value", "").text(data.labels.default_label);

        if(selected)
            newOption.prop("selected", "selected");

        font_weight_list.append(newOption);
    }

    function parseValueForText(value){
        switch(value){
            case "300":
                return data.labels.light;
            case "300italic":
                return data.labels.light + " " + data.labels.italic;
            case "regular":
                return data.labels.regular;
            case "italic":
                return data.labels.regular + " " + data.labels.italic;
            case "600":
                return data.labels.semibold;
            case "600italic":
                return data.labels.semibold + " " + data.labels.italic;
            case "700":
                return data.labels.bold;
            case "700italic":
                return data.labels.bold + " " + data.labels.italic;
            case "800":
                return data.labels.extrabold;
            case "800italic":
                return data.labels.extrabold + " " + data.labels.italic;
            case "900":
                return data.labels.black;
            case "900italic":
                return data.labels.black + " " + data.labels.italic;
            default:
                return "";
        }
    }

    jQuery('.known_element_fonts').trigger( "change" );
    jQuery('#font_id').trigger( "change" );
});

jQuery(document).ready(function(){
    // On font preview selection changed. a new font to preview is selected.
    jQuery('#font_preview_selection').on('change', function(){
        var font_family = jQuery(this).val();

        // If default value is selected, hide the preview box and text.
        if(!font_family){
            jQuery('#font_preview_text').hide();
            jQuery('#font_preview_demo').hide();
            return;
        }

        // If not, first show the preview box and text.
        jQuery('#font_preview_text').show();
        jQuery('#font_preview_demo').show();

        // Find the font and import it.
        for( key in data.available_fonts){
            font = data.available_fonts[key];
            if(font && font.family == font_family)
                if(font.files["regular"] != ""){
                    if(font.kind == "earlyaccess")
                        jQuery("head").append("<link href='" + font.files["regular"] + "' rel='stylesheet' type='text/css'>");
                    else
                        jQuery("head").append("<link href='https://fonts.googleapis.com/css?family=" + font.family.replace(' ', '+') + "' rel='stylesheet' type='text/css'>");

                   break;
                }

        }

        // Change the demo css to the selected font family.
        jQuery("#font_preview_demo").css("font-family", font_family);
    });
    
    // hide preview box and text by default.
    jQuery('#font_preview_text').hide();
    jQuery('#font_preview_demo').hide();
    
    // On any text changed change the preview in the demo to match the new value.
    jQuery('#font_preview_text').bind("propertychange keyup input cut paste", function(event){
        var value = jQuery(this).val();
        jQuery('#font_preview_demo').text(value);
    });
});