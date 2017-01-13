<?php
class FoSettingsPage
{
    /**
     * The seperator used when inserting more then 1 font format
     * to the database. The urls are joined with the seperator to
     * create a string a parsed back to urls when needed.
     */
    const CUSTOM_FONT_URL_SPERATOR = ';';
    const FACBOOK_APP_ID = "251836775235565";

    const DEFAULT_CSS_TITLE = "/* This Awesome CSS file was created by Font Orgranizer from Hive :) */\n\n";

    /**
     * Holds the option values for the general section.
     */
    private $general_options;

    /**
     * Holds the option values for the elements section.
     */
    private $elements_options;

    /**
     * Holds all the fonts available.
     * An Objects array that contains the information on each font.
     */
    private $available_fonts;

    /**
     * Holds all the usable to be used in the website from available fonts.
     * An Objects array that contains the information on each font.
     */
    private $usable_fonts;

    /**
     * Holds all the usable fonts from the database.
     * An Objects array that contains the information on each font.
     */
    private $usable_fonts_db;

    /**
     * Holds the known fonts available.
     * An Objects array that contains the information on each font.
     */
    private $known_fonts;

    /**
     * Holds the custom fonts available.
     * An Objects array that contains the information on each font.
     */
    private $custom_fonts;

    /**
     * Holds the google fonts available.
     * An Objects array that contains the information on each font.
     */
    private $google_fonts;

    /**
     * Holds the early access google fonts static list. (No API for full list exists)
     * An Objects array that contains the information on each font.
     */
    private $earlyaccess_fonts;

    /**
     * Holds the number of google fonts to load per request
     */
    private $fonts_per_link;

    /**
     * Holds the list of the supported font files for this settings.
     */
    private $supported_font_files;

    /**
     * Holds the error, if any, recieved from uploading a font.
     */
    private $recent_error;

    /**
     * Holds the known elements id and title to select a font for.
     */
    private $elements;

    /**
     * Holds the value if it should include font link (aka include google fonts for the settings page).
     * If set to false. loads only the usable fonts.
     */
    private $include_font_link;

    /**
     * Holds a list of all the custom elements from the database.
     */
    private $custom_elements;

    /**
     * The selected font to manage in last step.
     */
    private $selected_manage_font;

    /**
     * Should create a css file (override if exists) based on recent actions made.
     */
    private $should_create_css;

    /**
     * Is the current user admin or not.
     */
    private $is_admin;

    /**
     * Is the google fonts list from a static resource or
     * is it from google request.
     */
    private $is_google_static;

    /**
     * Start up
     */
    public function __construct()
    {
        require_once FO_ABSPATH . 'classes/class-ElementsTable.php'; 
        require_once FO_ABSPATH . 'classes/class-FontsDatabaseHelper.php';

        $this->fonts_per_link = 150;
        $this->supported_font_files = array('.woff', '.woff2', '.ttf','.otf');
        $this->custom_fonts = array();
        $this->available_fonts = array();
        $this->usable_fonts = array();
        $this->google_fonts = array();
        $this->should_create_css = false;
        $this->is_google_static = false;
        $this->is_admin = current_user_can('manage_options');
        $this->elements = array('body_font' =>  __('<body> Font', 'font-organizer'),
                                'h1_font'   =>  __('<h1> Font', 'font-organizer'),
                                'h2_font'   =>  __('<h2> Font', 'font-organizer'),
                                'h3_font'   =>  __('<h3> Font', 'font-organizer'),
                                'h4_font'   =>  __('<h4> Font', 'font-organizer'),
                                'h5_font'   =>  __('<h5> Font', 'font-organizer'),
                                'h6_font'   =>  __('<h6> Font', 'font-organizer'),
                                'p_font'    =>  __('<p> Font', 'font-organizer'),
                                'q_font'    =>  __('<q> Font', 'font-organizer'),
                                'li_font'   =>  __('<li> Font', 'font-organizer'),
                                'a_font'    =>  __('<a> Font', 'font-organizer'),
                                );

        // An upload is made. Upload the file and proccess it.
        if (isset($_POST['submit_upload_font'])){  
            if($args = $this->validate_upload()){
                $this->upload_file($args);
                $this->should_create_css = true;
            }else{
                add_action( 'admin_notices', array($this, 'upload_failed_admin_notice') );
            }
        }

        if (isset($_POST['submit_usable_font'])){  
            if($args = $this->validate_add_usable()){
                $this->use_font($args);
                $this->should_create_css = true;
            }else{
                add_action( 'admin_notices', array($this, 'use_font_failed_admin_notice') );
            }
        }

        if (isset($_POST['delete_usable_font'])){  
            if($args = $this->validate_delete_usable()){
                $this->delete_font($args);
                $this->should_create_css = true;
                wp_cache_delete ( 'alloptions', 'options' );

            }else{
                add_action( 'admin_notices', array($this, 'delete_font_failed_admin_notice') );
            }
        }

        if(isset($_POST['submit_custom_elements'])){
            if($args = $this->validate_custom_elements()){
                $this->add_custom_elements($args);
                $this->should_create_css = true;
            }else{
                add_action( 'admin_notices', array($this, 'add_custom_elements_failed_admin_notice') );
            }
        }

        if(isset($_GET['action']) && ($_GET['action'] == 'delete' || $_GET['action'] == 'bulk-delete') && isset($_GET['custom_element'])){
            $this->should_create_css = true;
        }

        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
        add_action( 'wp_ajax_edit_custom_elements', array( $this, 'edit_custom_elements_callback' ) );
    }

    /**
     * Register all the required scripts for this page.
     */
    public function register_scripts() {
        wp_enqueue_script( 'jquery-ui-core' );
        wp_enqueue_script( 'jquery-ui-autocomplete' );
        wp_enqueue_script( 'fo-settings-script', plugins_url( 'assets/js/settings.js', __FILE__ ) , array( 'jquery' ) );
        wp_enqueue_style( 'fo-settings-css', plugins_url( 'assets/css/settings.css', __FILE__ ) );
        wp_enqueue_style( 'fontawesome', plugins_url( 'assets/css/font-awesome.min.css', __FILE__ ) );
    }

    public function add_footer_styles(){ ?>
        <script type="text/javascript" >

            jQuery(document).ready(function() {
                var textBefore = '';

                // Exit focus from the text when clicking 'Enter' but don't submit the form.
                jQuery('table.custom_elements').find('td input:text').on('keyup keypress', function(e) {
                  var keyCode = e.keyCode || e.which;
                  if (keyCode === 13) { 
                    jQuery(this).blur();
                    e.preventDefault();
                    return false;
                  }
                });

                jQuery('table.custom_elements').find('td input:checkbox').change(function () {

                    // Change the No to Yes or Yes to No labels.
                    var $field = jQuery(this);
                    var value = $field.prop('checked') ? 1 : 0;
                    var item = jQuery(this).siblings('span');
                    if(value){
                        item.css('color', 'darkgreen');
                        item.text("<?php _e('Yes', 'font-organizer'); ?>");
                    }else{
                        item.css('color', 'darkred');
                        item.text("<?php _e('No', 'font-organizer'); ?>");
                    }

                    // Send ajax request named 'edit_custom_elements' to change the column to value text
                    // where id.
                    var data = {
                            'action': 'edit_custom_elements',
                            'id': parseInt($field.closest('tr').find('.check-column input').val()),
                            'column': $field.attr('name'),
                            'text': value
                    };
                    jQuery.post(ajaxurl, data, function(response) {

                            // Show message for success and error for 3 seconds.
                            if(response == "true"){
                                jQuery('.custom_elements_message.fo_success').show().delay(3000).fadeOut();
                            }else{
                                jQuery('.custom_elements_message.fo_warning').show().delay(3000).fadeOut();
                                $field.prop('checked', !value);
                            }
                    });
                });

                jQuery('table.custom_elements').find('td input:text').on('focus', function () {
                    var $field = jQuery(this);

                    // Store the current value on focus and on change
                    textBefore = $field.val();
                }).blur(function() {
                    var $field = jQuery(this);
                    var text = $field.val();

                    // Set back previous value if empty
                    if (text.length <= 0) {
                        $field.val(textBefore);
                        return;
                    }

                    if (textBefore !== text) {

                        // Send ajax request named 'edit_custom_elements' to change the column to value text
                        // where id.
                        var data = {
                            'action': 'edit_custom_elements',
                            'id': parseInt($field.closest('tr').find('.check-column input').val()),
                            'column': $field.attr('name'),
                            'text': text
                        };
                        jQuery.post(ajaxurl, data, function(response) {

                            // Show message for success and error for 3 seconds.
                            if(response == "true"){
                                jQuery('.custom_elements_message.fo_success').show().delay(3000).fadeOut();
                            }else{
                                jQuery('.custom_elements_message.fo_warning').show().delay(3000).fadeOut();
                                $field.val(textBefore);
                            }
                        });
                    }
                });
            });
        </script> <?php
    }

    public function edit_custom_elements_callback() {
        global $wpdb;

        $table_name = $wpdb->prefix . FO_ELEMENTS_DATABASE;
        $wpdb->update( 
        $table_name, 
        array( $_POST['column'] => $_POST['text'] ), // change the column selected with the new value.
        array('id' => $_POST['id']) // where id
        );

        // Initialize what is a must for the elements file.
        $this->load_custom_elements();
        $this->elements_options = get_option( 'fo_elements_options' );

        $this->create_elements_file();

        wp_die('true'); // this is required to terminate immediately and return a proper response
    }

    /**
     * Add options settings page in the wordpress settings.
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        $hook = add_options_page(
            'Settings Admin', 
            __('Font Settings', 'font-organizer'), 
            'manage_fonts',
            'font-setting-admin', 
            array( $this, 'create_font_settings_page' )
        );

        add_action( 'load-' . $hook, array( $this, 'init_page' ) );
        add_action( 'load-' . $hook, array( $this, 'register_scripts' ) );
        add_action( 'load-' . $hook, array( $this, 'create_css_file' ) );
        add_action( 'admin_footer', array( $this, 'add_footer_styles' ) );
        add_filter( 'option_page_capability_fo_general_options', array($this, 'options_capability') );
        add_filter( 'option_page_capability_fo_elements_options', array($this, 'options_capability') );
    }

    // Allows to tell wordpress that the options named fo_general_options & fo_elements_options
    // can be saved by manage_fonts capability and by any role with this capability.
	public function options_capability( $cap ) {
    	return 'manage_fonts';
	}

    public function init_page(){
    	$this->init();
    }

    public function create_css_file($force = false){

        if(((!isset($_GET['settings-updated']) || !$_GET['settings-updated']) && !$this->should_create_css) && !$force){
            return;
        }
        
        /* ========= Create the declartions file ========= */
        $this->create_declration_file();

        /* ========= Create the elements file ========= */
        $this->create_elements_file();
    }

    private function create_elements_file(){
        global $fo_css_directory_path;
        global $fo_elements_css_file_name;

        $content = self::DEFAULT_CSS_TITLE;

        // Add the known elements css.
        foreach ($this->elements_options as $key => $value) {
            if(strpos($key, 'important') || !$value)
                continue;

            $strip_key = str_replace('_font', '', $key);
            $important = $this->elements_options[$key . '_important'];
            $content .= sprintf("%s { font-family: '%s'%s; }\n", $strip_key, $value, $important ? '!important' : '');
        }

        // Add custom elements css.
        foreach ($this->custom_elements as $custom_element_db) {
            // if name is valid create a css for it.
            if($custom_element_db->name){
                $content .= sprintf("%s { font-family: '%s'%s; }\n", $custom_element_db->custom_elements, $custom_element_db->name, $custom_element_db->important ? '!important' : '');
            }
        }

        // If there is any css to write. Create the directory if needed and create the file.
        fo_try_write_file($content, $fo_css_directory_path, $fo_elements_css_file_name, array($this, 'generate_css_failed_admin_notice'));
    }

    private function create_declration_file(){
        global $fo_css_directory_path;
        global $fo_declarations_css_file_name;

        $content = self::DEFAULT_CSS_TITLE;
        $custom_fonts_content = '';
        $google_fonts = array();
        foreach ($this->usable_fonts as $key => $usable_font) {
            switch ($usable_font->kind) {
                case 'custom':
                    $urls = $usable_font->files->regular;

                    // Set all the urls content under the same src.
                    $urls_content_arr = array();
                    foreach ($urls as $url) {
                        // Fix everyone saved with http or https and let the browser decide.
                        $url = str_replace('http://',  '//', $url); 
                        $url = str_replace('https://', '//', $url);

                        $urls_content_arr[] = "url('" . $url . "') format('" . fo_get_font_format($url) . "')";
                    }

                    $urls_content = implode(",\n", $urls_content_arr) . ';';

                    $custom_fonts_content .= "
@font-face {
    font-family: '" . $usable_font->family . "';
    src: " . $urls_content . "
    font-weight: normal;
    font-style: normal;
}\n";
                    break;
                case 'webfonts#webfont': // Google font
                    $google_fonts[] = str_replace(' ', '+', $usable_font->family);
                    break;
                case 'earlyaccess':
                    // Better safe then sorry.
                    reset($usable_font->files->regular);

                    $content .= "@import url(".current($usable_font->files->regular).");\n";
                    break;
                case 'regular':
                default:
                    break;
            }
        }

        // Add Google fonts to the css. MUST BE FIRST.
        if(!empty($google_fonts)){
            // We are assuming not to much google fonts. If it is, we need to split the request.
           // $content .= "<link href='http://fonts.googleapis.com/css?family=". implode("|", $google_fonts) . "' rel='stylesheet' type='text/css'>\n";
            $content .= "@import url('//fonts.googleapis.com/css?family=". implode("|", $google_fonts) . "');\n";
        }

        // Add the custom fonts css that was created before.
        $content .= $custom_fonts_content;

        // If there is any declartions css to write.
        fo_try_write_file($content, $fo_css_directory_path, $fo_declarations_css_file_name, array($this, 'generate_css_failed_admin_notice'));
    }

    /**
     * Initialize the class private fields. Options, Google fonts list, known fonts, available fonts,
     * and all the usable fonts.
     */
    public function init(){
        $this->general_options = get_option( 'fo_general_options' );
        $this->elements_options = get_option( 'fo_elements_options' );
        $this->custom_elements_table = new ElementsTable();

        $this->include_font_link = isset( $this->general_options['include_font_link'] ) && $this->general_options['include_font_link'];

        if(isset($this->general_options['google_key']) && $this->general_options['google_key']){
            // Add Google fonts.
            $response = wp_remote_get("https://www.googleapis.com/webfonts/v1/webfonts?sort=alpha&key=" . $this->general_options['google_key'], array('timeout' => 60));
            if( wp_remote_retrieve_response_code( $response ) == 200){
           		$this->google_fonts = json_decode(wp_remote_retrieve_body($response))->items;
           	}else{
                // Show the most detailed message in the error and display it to the user.
                if ( is_wp_error( $response ) ) {
                    $error = wp_strip_all_tags( $response->get_error_message() );
                }else{
                    $error = json_decode(wp_remote_retrieve_body($response))->error->errors[0];
                }

                add_settings_error('google_key', '', __('Google API key is not valid: ', 'font-organizer') . ' [' . $error->reason . '] ' . $error->message, 'error');
            }
        }

        if(empty($this->google_fonts)){
            // Get a static google fonts list.
            require_once FO_ABSPATH . '/helpers/google-fonts.php';
            
            $this->google_fonts = json_decode(fo_get_all_google_fonts_static_response())->items;
            $this->is_google_static = true;
        }

        // Add known fonts.
        $this->known_fonts = fo_get_known_fonts_array();

        // Add early access google fonts. (this list is static, no api to get full list)
        $this->earlyaccess_fonts = $this->get_early_access_fonts_array();

        // Merge (and sort) the early access google fonts list with the google fonts.
        if(!empty($this->google_fonts)){
            $this->google_fonts = array_merge($this->google_fonts, $this->earlyaccess_fonts);
            fo_array_sort($this->google_fonts);

            $this->available_fonts = array_merge($this->available_fonts, $this->google_fonts, $this->known_fonts);
        }else{
            $this->available_fonts = array_merge($this->available_fonts, $this->earlyaccess_fonts, $this->known_fonts);
        }

        // Get all usable fonts and add them to a list.
        $this->load_usable_fonts();
        $this->load_custom_elements();
    }

    /**
     * Options page callback
     */
    public function create_font_settings_page(){
        if(isset($_GET['manage_font_id'])){
        		foreach ($this->usable_fonts_db as $font_db) {
        			 if(intval($_GET['manage_font_id']) == $font_db->id){

                        // If name is made up/ deleted or unavailable for now just break for now.
                        if(!array_key_exists($font_db->name, $this->usable_fonts))
                            break;

	                	$this->selected_manage_font = $this->usable_fonts[$font_db->name];
        				$this->custom_elements_table->prepare_items_by_font($this->custom_elements, $font_db->id);
	                	break;
	                }
        		}
        }

        // Load the google fonts if selected or if not specified. else load just whats usable.
        if($this->include_font_link)
            fo_print_links($this->google_fonts, $this->fonts_per_link);
        
        ?>
        <a href="#" class="go-top <?php echo is_rtl() ? ' rtl' : 'ltr'; ?>"></a>
        <div class="wrap">
            <h1><?php _e('Font Settings', 'font-organizer'); ?></h1>

                <div id="poststuff">  
                    <div id="post-body" class="metabox-holder columns-2">

                    <!-- main content -->
                    <div id="post-body-content">

                        <!-- General Settings Section -->
                        <div class="postbox">
                            <a name="step1"></a>
                            <h2 class="hndle ui-sortable-handle" style="cursor:default;"><span><?php _e('General Settings', 'font-organizer'); ?></span></h2>
                            <div class="inside">
                                <form method="post" action="options.php">
                                <?php
                                    // This prints out all hidden setting fields
                                    settings_fields( 'fo_general_options' );
                                    fo_do_settings_section( 'font-setting-admin', 'setting_general' );
                                    submit_button();
                                ?>
                                </form>
                            </div>
                        </div>

                        <!-- Add Google & Regular Fonts To Website Section -->
                        <div class="postbox">
                            <a name="step2"></a>
                            <h2 class="hndle ui-sortable-handle" style="cursor:default;"><span><?php _e('1. Add Fonts', 'font-organizer'); ?></span></h2>
                            <div class="inside">
                                <span><?php _e('Step 1: Select and add fonts to be used in your website. Select as many as you wish.', 'font-organizer'); ?></span>
                                <br />
                                <span><?php _e('You can select google or regular fonts.', 'font-organizer'); ?></span>
                                <form action="" id="add_usable_font_form" name="add_usable_font_form" method="post"> 
                                    <table class="form-table">
                                        <tr>
                                            <th scope="row"><?php _e('Available Fonts', 'font-organizer'); ?></th>
                                            <td><?php  $this->print_available_fonts_list('usable_font'); ?></td>
                                        </tr>   
                                        <tr>        
                                            <th scope="row"></th>
                                            <td>
                                             <?php wp_nonce_field( 'add_usable_font', 'add_usable_font_nonce' ); ?>
                                            <input type="submit" name="submit_usable_font" id="submit_usable_font" class="button-primary" value="<?php _e('Use This Font', 'font-organizer'); ?>" />
                                            </td>
                                        </tr>
                                    </table>
                                </form> 
                            </div>  
                        </div>
                   
                        <!-- Add Custom Fonts To Website Section -->
                        <div class="postbox">
                            <a name="step3"></a>
                            <h2 class="hndle ui-sortable-handle" style="cursor:default;"><span><?php _e('2. Custom Fonts', 'font-organizer'); ?></span></h2>
                            <div class="inside">
                                <span><?php _e('Step 2: Upload custom fonts to be used in your website. Here too, you can upload as many as you wish.', 'font-organizer'); ?></span>
                                <br />
                                <span><?php _e('Name the font you want to upload and upload all the files formats for this font. In order to support more browsers you can click the green plus to upload more font formats. We suggest .woff and .woff2.', 'font-organizer'); ?></span>

                                <div class="custom_font_message fo_warning" style="display: none;">
                                        <i class="fa fa-warning"></i>
                                        <?php _e("This font format is already selected. Reminder: you need to upload the font files for the same font weight.", "font-organizer"); ?>
                                        <span></span>
                                </div>

                                <form action="#" id="add_font_form" name="add_font_form" method="post" enctype="multipart/form-data"> 
                                    <table class="form-table">
                                        <tr>
                                            <th scope="row"><label for="font_name" class="required"><?php _e('Font Weight Name', 'font-organizer'); ?></label></th>
                                            <td><input type="text" id="font_name" required oninvalid="this.setCustomValidity('<?php _e('Font weight name cannot be empty.', 'font-organizer'); ?>')" oninput="setCustomValidity('')" name="font_name" value="" class="required" maxlength="20" /></td>
                                        </tr>   
                                        <tr class="font_file_wrapper">    
                                            <th scope="row">
                                                <label for="font_file" class="required"><?php _e('Font Weight File', 'font-organizer'); ?></label>
                                            </th>
                                            <td id="font_file_parent" style="width:33%;">
                                                <input type="file" name="font_file[]" value="" class="add_font_file required" onfocus="this.oldvalue = this.value;" accept="<?php echo join(',',$this->supported_font_files); ?>"  /><br/>
                                                <em><?php echo __('Accepted Font Format : ', 'font-organizer') . '<span style="direction: ltr">' . join(', ',$this->supported_font_files) . '</span>'; ?></em><br/>
                                            </td>
                                            <td>
                                                 <a href="javascript:void(0);" class="add_button" title="<?php _e('Add Another Font Format File', 'font-organizer'); ?>"><i class="fa fa-plus fa-2x" aria-hidden="true"></i></a>
                                                 <span style="font-size: 11px;font-style: italic;position: absolute;padding: 6px;"><?php _e('Add Another Font Format File', 'font-organizer'); ?></span>
                                            </td>
                                        </tr>
                                        <tr>        
                                            <th scope="row"></th>
                                            <td>
                                             <?php wp_nonce_field( 'add_custom_font', 'add_custom_font_nonce' ); ?>
                                            <input type="submit" name="submit_upload_font" id="submit_upload_font" class="button-primary" value="<?php _e('Upload', 'font-organizer'); ?>" />
                                            </td>
                                        </tr>
                                    </table>
                                </form>   
                            </div>
                        </div>
   
                        <!-- Assign Fonts To Known Elements Section -->
                        <div class="postbox">
                            <a name="step4"></a>
                            <h2 class="hndle ui-sortable-handle" style="cursor:default;"><span><?php _e('3. Known Elements Settings', 'font-organizer'); ?></span></h2>
                            <div class="inside">

                                <span><?php _e('Step 3: For each element you can assign a font you have added in step 1 & 2.', 'font-organizer'); ?></span>
                                <p><strong><?php _e('Note: ', 'font-organizer'); ?></strong> <?php _e('Custom fonts you uploaded are automatically used in your website.', 'font-organizer'); ?></p>
                                <p><strong><?php _e('In case of font not displaying in your website after saving, try clear the cache using Shift+F5 or Ctrl+Shift+Delete to clear all.', 'font-organizer'); ?></strong>
                                <form method="post" action="options.php">
                                <?php
                                    // This prints out all hidden setting fields
                                    settings_fields( 'fo_elements_options' );
                                    fo_do_settings_section( 'font-setting-admin', 'setting_elements' );
                                    submit_button();
                                ?>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Assign Fonts To Custom Elements Section -->
                        <div class="postbox">
                            <a name="step5"></a>
                            <h2 class="hndle ui-sortable-handle" style="cursor:default;"><span><?php _e('4. Custom Elements Settings', 'font-organizer'); ?></span></h2>
                            <div class="inside">

                                <span><?php _e('Step 4: Assign font that you have added to your website to custom elements.', 'font-organizer'); ?></span>
                                <form action="#" id="add_custom_elements_form" name="add_custom_elements_form" method="post"> 
                                    <table class="form-table">
                                        <tr>
                                            <th scope="row"><label for="custom_elements" class="required"><?php _e('Font', 'font-organizer'); ?></label></th>
                                            <td><?php $this->print_custom_elements_usable_fonts_list('font_id', __('-- Select Font --', 'font-organizer'), __("You must select a font for the elements.", "font-organizer")); ?></td>
                                        </tr>   
                                        <tr>
                                            <th scope="row">
                                                <label for="custom_elements" class="required">
                                                    <?php _e('Custom Element', 'font-organizer'); ?>
                                                </label>
                                            </th>
                                            <td>
                                                <textarea id="custom_elements" name="custom_elements" required oninvalid="this.setCustomValidity('<?php _e('Font custom elements cannot be empty.', 'font-organizer'); ?>')" oninput="setCustomValidity('')" style="width: 100%" rows="2"></textarea>
                                                <em><?php _e('Custom elements can be seperated by commas to allow multiple elements. Example: #myelementid, .myelementclass, .myelementclass .foo, etc.', 'font-organizer'); ?></em>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th></th>
                                            <td><?php $this->print_is_important_checkbox('important'); ?></td>
                                        </tr>
                                        <tr>        
                                            <th scope="row"></th>
                                            <td>
                                             <?php wp_nonce_field( 'add_custom_elements', 'add_custom_elements_nonce' ); ?>
                                            <input type="submit" name="submit_custom_elements" id="submit_custom_elements" class="button-primary" value="<?php _e('Apply Custom Elements', 'font-organizer'); ?>" />
                                            </td>
                                        </tr>
                                    </table>
                                </form> 
                            </div>
                        </div>

                        <!-- Manage Used fonts Section -->
                        <div class="postbox">
                            <a name="step6"></a>
                            <h2 class="hndle ui-sortable-handle" style="cursor:default;"><span><?php _e('5. Manage Fonts', 'font-organizer'); ?></span></h2>
                            <div class="inside">
                                    <span>
                                        <?php _e('Step 5: Select a font to manage, delete and view the source and custom elements assigned to it.', 'font-organizer'); ?>    
                                    </span>
                                     <p>
                                        <strong><?php _e('Note: ', 'font-organizer'); ?></strong> 
                                        <?php _e('You can edit the values of every row to change the custom elements assigned or add and remove the important tag. Just change the text or check the box and the settings will automatically save.', 'font-organizer'); ?>
                                    </p>
                                    <div class="custom_elements_message fo_success" style="display: none;">
                                        <i class="fa fa-info-circle"></i>
                                        <?php _e('Changes saved!', 'font-organizer'); ?>
                                    </div>
                                    <div class="custom_elements_message fo_warning" style="display: none;">
                                        <i class="fa fa-warning"></i>
                                        <?php _e("Data is invalid", "font-organizer"); ?>
                                        <span></span>
                                    </div>
	                                <table class="form-table">
	                                        <tr>
	                                            <th scope="row"><?php _e('Font', 'font-organizer'); ?></th>
                                                <td>
                                                    <form action="#step6" id="select_font_form" name="select_font_form" method="get"> 
	                                                   <?php $this->print_custom_elements_usable_fonts_list('manage_font_id', __('-- Select Font --', 'font-organizer')); ?>
                                                        <input type="hidden" name="page" value="<?php echo wp_unslash( $_REQUEST['page'] ); ?>">
                                                    </form>
                                                </td>
                                                 <?php if($this->selected_manage_font): ?>

                                                <td style="text-align:left;">
                                                    <form action="#step6" id="delete_usable_font_form" name="delete_usable_font_form" method="post"> 
                                                        <?php wp_nonce_field( 'delete_usable_font', 'delete_usable_font_nonce' ); ?>
                                                        <input type="hidden" name="page" value="<?php echo wp_unslash( $_REQUEST['page'] ); ?>">
                                                        <input type="hidden" name="font_id" value="<?php echo $_GET['manage_font_id']; ?>">
                                                        <input type="hidden" name="font_name" value="<?php echo $this->selected_manage_font->family; ?>">
                                                        <input type="submit" name="delete_usable_font" id="delete_usable_font" class="button-secondary" value="<?php _e('Delete Font', 'font-organizer'); ?>" onclick="return confirm('<?php _e("Are you sure you want to delete this font from your website?", "font-organizer"); ?>')" />
                                                    </form>
                                                </td>

                                            <?php endif; ?>
	                                        </tr>   
	                                </table>
	                            <?php if($this->selected_manage_font): ?>
	                           	<hr/>
                                <table class="form-table">
                                    <tr>
                                        <th scope="row"><?php _e('Source', 'font-organizer'); ?></th>
                                        <td><span><?php fo_print_source($this->selected_manage_font->kind); ?></span></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php _e('Urls', 'font-organizer'); ?></th>
                                        <td style="direction:ltr;text-align:left;line-height:20px;">
                                            <span>
                                            <?php
                                            if(is_array($this->selected_manage_font->files->regular)){
                                                foreach($this->selected_manage_font->files->regular as $url)
                                                    echo $url, '<br>';
                                            }else{
                                                echo $this->selected_manage_font->files->regular;
                                            }
                                            ?>
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                                <div class="wp-table-fo-container">
                                 	<form id="custom_elements-filter" method="get" action="#step6">
                                 		<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                                 		<input type="hidden" name="manage_font_id" value="<?php echo $_GET['manage_font_id']; ?>">
                               			<?php $this->custom_elements_table->display(); ?>
                               		</form>
                                </div>
                                <?php
                                endif;
                                ?>
                            </div>
                        </div>

                    </div>

                    <!-- sidebar -->
                    <div id="postbox-container-1" class="postbox-container">
                        <div class="meta-box-sortables">
                            <div class="postbox">
                                <h2>
                                    <span><?php esc_attr_e('Thank you', 'font-organizer'); ?></span>
                                </h2>

                                <div class="inside">
                                    <p><?php _e(
                                            'Thank you for using an <a href="http://hivewebstudios.com" target="_blank">Hive</a> plugin! We 5 star you already, so why don\'t you <a href="https://wordpress.org/support/plugin/font-organizer/reviews/?rate=5#new-post" target="_blank">5 star us too</a>?', 'font-organizer'); ?>
                                        <br />
                                        <p><?php _e('Anyway, if you need anything, this may help:', 'font-organizer'); ?></p> 
                                        <ul style="list-style-type:disc;margin: 0 20px;">
                                            <li><a href="http://hivewebstudios.com/font-organizer/#faq" target="_blank"><?php _e('FAQ', 'font-organizer'); ?></a></li>
                                            <li><a href="https://wordpress.org/support/plugin/font-organizer" target="_blank"><?php _e('Support forums', 'font-organizer'); ?></a></li>
                                            <li><a href="http://hivewebstudios.com/font-organizer/#contact" target="_blank"><?php _e('Contact us', 'font-organizer'); ?></a></li>
                                        </ul>
                                    </p>
                                </div>
                            </div>
                            <div class="postbox">
                                <h2>
                                    <span><?php esc_attr_e('Like Our Facebook Page', 'font-organizer'); ?></span>
                                </h2>
                                <div class="inside">
                                    <iframe src="https://www.facebook.com/plugins/page.php?href=https%3A%2F%2Fwww.facebook.com%2Fhivewp%2F&tabs&width=340&height=214&small_header=false&adapt_container_width=true&hide_cover=false&show_facepile=true&appId=<?php echo self::FACBOOK_APP_ID; ?>" width="100%" height="200" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowTransparency="true"></iframe>
                                </div>
                            </div>
                        </div>
                    </div>
            </form>
        </div>
        <?php
    } 

    private function validate_upload(){
        if(!isset( $_POST['add_custom_font_nonce'] ) || !wp_verify_nonce( $_POST['add_custom_font_nonce'], 'add_custom_font' )){
            $this->recent_error = __('Session ended, please try again.', 'font-organizer');
            return false;
        }

        $args['font_name'] = sanitize_text_field( $_POST['font_name'] );
        if(!$args['font_name']){
            $this->recent_error = __('Font name is empty or invalid.', 'font-organizer');
            return false;
        }

        if(!isset($_FILES['font_file'])){
            $this->recent_error = __('Font file is not selected.', 'font-organizer');
            return false;
        }

        $args['font_file'] = fo_rearray_files($_FILES['font_file']);

        $i = 0;
        foreach ($args['font_file'] as $file) {
            if(!$file['name']){
                unset($args['font_file'][$i]);
            }

            $i++;
        }
        
        if(empty($args['font_file'])){
            $this->recent_error = __('Font file(s) not selected.', 'font-organizer');
            return false;
        }
        
        return $args;
    }

    private function validate_add_usable(){
        if(!isset( $_POST['add_usable_font_nonce'] ) || !wp_verify_nonce( $_POST['add_usable_font_nonce'], 'add_usable_font' )){
            $this->recent_error = __('Session ended, please try again.', 'font-organizer');
            return false;
        }

        $args['usable_font'] = sanitize_text_field( $_POST['usable_font'] );
        if(!$args['usable_font']){
            $this->recent_error = __('Usable font is empty or invalid.', 'font-organizer');
            return false;
        }

        return $args;
    }

    private function validate_custom_elements(){
        if(!isset( $_POST['add_custom_elements_nonce'] ) || !wp_verify_nonce( $_POST['add_custom_elements_nonce'], 'add_custom_elements' )){
            $this->recent_error = __('Session ended, please try again.', 'font-organizer');
            return false;
        }

        $args['custom_elements'] = sanitize_text_field( $_POST['custom_elements'] );
        if(!$args['custom_elements']){
            $this->recent_error = __('Custom elements is empty or invalid.', 'font-organizer');
            return false;
        }

        $args['important'] = isset($_POST['important']) ? 1 : 0;

        $args['font_id'] = $_POST['font_id'];

        return $args;
    }

    private function validate_delete_usable(){
        if(!isset( $_POST['delete_usable_font_nonce'] ) || !wp_verify_nonce( $_POST['delete_usable_font_nonce'], 'delete_usable_font' )){
            $this->recent_error = __('Session ended, please try again.', 'font-organizer');
            return false;
        }

        $args['font_id'] = intval( $_POST['font_id'] );
        $args['font_name'] = sanitize_text_field( $_POST['font_name'] );
        if(!$args['font_id'] || !$args['font_name']){
            $this->recent_error = __('Something went horribly wrong. Ask the support!', 'font-organizer');
            return false;
        }

        return $args;
    }

    private function upload_file($args = array()){
        $urls = array();
        foreach ($args['font_file'] as $file) {
            $movefile = fo_upload_file($file, array($this, 'fo_upload_dir'));
            if(!$movefile || isset( $movefile['error'] )){
                $this->recent_error = $movefile['error'];
                add_action( 'admin_notices', array($this, 'upload_failed_admin_notice') );
                return false;
            }
            
            $urls[] = $movefile['url'];
        }

        $this->save_usable_font_to_database($args['font_name'], implode(self::CUSTOM_FONT_URL_SPERATOR, $urls), true);
        add_action( 'admin_notices', array($this, 'upload_successfull_admin_notice') );
    }

    private function use_font($args = array()){
            add_action( 'admin_notices', array($this, 'use_font_successfull_admin_notice') );
            $this->save_usable_font_to_database($args['usable_font']);
    }

    private function add_custom_elements($args = array()){
            add_action( 'admin_notices', array($this, 'add_custom_elements_successfull_admin_notice') );
            $this->save_custom_elements_to_database($args['font_id'], $args['custom_elements'], $args['important']);
    }

    private function delete_font($args = array()){
            global $fo_css_directory_path;

            // Delete all the known elements for this font and reset them back to default.
            $elements_options = get_option('fo_elements_options', array());
            foreach ($this->elements as $element_id => $element_display_name) {
                if(array_key_exists($element_id, $elements_options) && $elements_options[$element_id] == $args['font_name']){
                    $elements_options[$element_id] = '';
                }
            }

            update_option('fo_elements_options', $elements_options);

            // Delete all custom elements for this font.
            $table_name = FO_ELEMENTS_DATABASE;
            $this->delete_from_database($table_name, 'font_id', $args['font_id']);

            global $wpdb;

            $usable_fonts = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . FO_USABLE_FONTS_DATABASE . ' ORDER BY id DESC');
            foreach ($usable_fonts as $usable_font) {
                if($usable_font->name == $args['font_name']){
                    if(!$usable_font->custom)
                        break;

                   $urls = explode(self::CUSTOM_FONT_URL_SPERATOR, $usable_font->url);
                   foreach ($urls as $url) {
                        // Delete the old file.
                        $file_name = basename($url);
                        if(file_exists($fo_css_directory_path . '/' . $file_name))
                            unlink($fo_css_directory_path . '/' . $file_name);
                    }
                }
            }

            // Delete this font from the website.
            $table_name = FO_USABLE_FONTS_DATABASE;
            $this->delete_from_database($table_name, 'id', $args['font_id']);

            add_action( 'admin_notices', array($this, 'delete_font_successfull_admin_notice') );
    }

    private function delete_from_database($table_name, $field_name, $field_value){
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . $table_name, array( $field_name => $field_value ) );
    }

    private function save_custom_elements_to_database($id, $custom_elements, $important){
        global $wpdb;
        $table_name = $wpdb->prefix . FO_ELEMENTS_DATABASE;

        $wpdb->insert( 
        $table_name, 
        array( 
            'font_id' => $id, 
            'custom_elements' => $custom_elements, 
            'important' => $important ? 1 : 0,
        ));
    }

    private function save_usable_font_to_database($name, $url = '', $is_custom = false){
        global $wpdb;
        $table_name = $wpdb->prefix . FO_USABLE_FONTS_DATABASE;

        $wpdb->insert( 
        $table_name, 
        array( 
            'name' => $name, 
            'url' => $url, 
            'custom' => $is_custom ? 1 : 0,
        ));
    }


    public function add_custom_elements_failed_admin_notice() {
      ?>
        <div class="error notice">
            <p><?php echo __( 'Error adding custom elements: ', 'font-organizer' ) . $this->recent_error; ?></p>
        </div>
        <?php
    }
    public function add_custom_elements_successfull_admin_notice() {
        ?>
        <div class="updated notice">
            <p><?php _e( 'Custom elements added to your website!', 'font-organizer' ); ?></p>
        </div>
        <?php
    }

    public function use_font_successfull_admin_notice() {
        ?>
        <div class="updated notice">
            <p><?php _e( 'Font can now be used in your website!', 'font-organizer' ); ?></p>
        </div>
        <?php
    }

    public function delete_font_successfull_admin_notice() {
        ?>
        <div class="updated notice">
            <p><?php _e( 'Font deleted from your website!', 'font-organizer' ); ?></p>
        </div>
        <?php
    }

    public function upload_successfull_admin_notice() {
        ?>
        <div class="updated notice">
            <p><?php _e( 'The file has been uploaded!', 'font-organizer' ); ?></p>
        </div>
        <?php
    }

    public function upload_failed_admin_notice() {
        ?>
        <div class="error notice">
            <p><?php echo __( 'Error uploading the file: ', 'font-organizer' ) . $this->recent_error; ?></p>
        </div>
        <?php
    }

    public function use_font_failed_admin_notice() {
        ?>
        <div class="error notice">
            <p><?php echo __( 'Error adding font to website fonts: ', 'font-organizer' ) . $this->recent_error; ?></p>
        </div>
        <?php
    }

    public function delete_font_failed_admin_notice() {
        ?>
        <div class="error notice">
            <p><?php echo __( 'Error deleting font: ', 'font-organizer' ) . $this->recent_error; ?></p>
        </div>
        <?php
    }

    public function generate_css_failed_admin_notice() {
        ?>
        <div class="error notice">
            <p><?php echo __( 'Failed to open or create the css file. Check for permissions.', 'font-organizer' ); ?></p>
        </div>
        <?php
    }

    /**
     * Override the default upload path.
     * 
     * @param   array   $dir
     * @return  array
     */
    public function fo_upload_dir( $dir ) {
        $base_url =   $url = fo_get_all_http_url( $dir['baseurl'] );

        return array(
            'path'   => $dir['basedir'] . '/font-organizer',
            'url'    => $base_url . '/font-organizer',
            'subdir' => '/font-organizer',
        ) + $dir;
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {   
        register_setting(
            'fo_general_options', // Option group
            'fo_general_options', // Option name
            array( $this, 'general_sanitize' ) // Sanitize
        );
        register_setting(
            'fo_elements_options', // Option group
            'fo_elements_options', // Option name
            array( $this, 'elements_sanitize' ) // Sanitize
        );
        add_settings_section(
            'setting_general', // ID
            '', // Title
            array( $this, 'print_general_section_info' ), // Callback
            'font-setting-admin' // Page
        );  
        add_settings_field(
            'google_key', // ID
            __('Google API Key', 'font-organizer'), // Title 
            array( $this, 'google_key_callback' ), // Callback
            'font-setting-admin', // Page
            'setting_general' // Section           
        );   
        add_settings_field(
            'include_font_link', // ID
            __('Show Font Family Preview', 'font-organizer'), // Title 
            array( $this, 'include_font_link_callback' ), // Callback
            'font-setting-admin', // Page
            'setting_general' // Section           
        );   

        // If user is admin, display the permissions option.
    	if ($this->is_admin) {
	        add_settings_field(
	            'permissions', // ID
	            __('Access Settings Role', 'font-organizer'), // Title 
	            array( $this, 'permissions_callback' ), // Callback
	            'font-setting-admin', // Page
	            'setting_general' // Section           
        	);   
		}

        add_settings_section(
            'setting_elements', // ID
            '', // Title
            array( $this, 'print_elements_section_info' ), // Callback
            'font-setting-admin' // Page
        );  

        // Add all the elements to the elements section.
        foreach ($this->elements as $id => $title) {
            add_settings_field(
                $id, // ID
                htmlspecialchars($title), // Title 
                array( $this, 'fonts_list_field_callback' ), // Callback
                'font-setting-admin', // Page
                'setting_elements', // Section 
                $id // Parameter for Callback 
            );   

            add_settings_field(
                $id . '_important', // ID
                '', // Title 
                array( $this, 'is_important_element_field_callback' ), // Callback
                'font-setting-admin', // Page
                'setting_elements', // Section 
                $id . '_important' // Parameter for Callback 
            );   
        }
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function general_sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['google_key'] ) )
            $new_input['google_key'] = sanitize_text_field( $input['google_key'] );

        if( !isset( $input['include_font_link'] ) )
            $new_input['include_font_link'] =  0 ;
        else
        	$new_input['include_font_link'] = $input['include_font_link'];

        // Do not allow change in permissions if user is not admin.
        if(!$this->is_admin)
        	return $new_input;

        // Get the old permissions.
       	$this->general_options = get_option( 'fo_general_options' );
       	$old_permissions = isset($this->general_options['permissions']) ? $this->general_options['permissions'] : array();
       	
       	// Get the new permissions.
       	$new_input['permissions'] = isset($input['permissions']) ? $input['permissions'] : array();
       	if($new_input != $old_permissions){

	        // Remove previus capabilities.
            foreach ($old_permissions as $value) {
            	if($value != FO_DEFAULT_ROLE){
	           		$prev_role = get_role($value);
	           		$prev_role->remove_cap('manage_fonts');
	           	}
            }

            // Add the new capabilities to the new roles.
            foreach ($new_input['permissions'] as $value) {
	           	$prev_role = get_role($value);
	            $prev_role->add_cap('manage_fonts');
            }
        }

        return $new_input;
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function elements_sanitize( $input )
    {
        $new_input = array();
        foreach ($this->elements as $id => $title) {
            if( isset( $input[$id] ) ){
                $new_input[$id] = sanitize_text_field( $input[$id] );
            }else{
                $new_input[$id] = '';
            }

            if( !isset( $input[$id . '_important'] ) )
                $new_input[$id . '_important'] =  0 ;
            else
                $new_input[$id . '_important'] = intval($input[$id . '_important']);

        }

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_general_section_info()
    {
        _e('This is the general settings for the site.', 'font-organizer');
    }

    /** 
     * Print the Section text
     */
    public function print_elements_section_info()
    {
    }

    /** 
     * Get the settings option for google key array and print one of its values
     */
    public function google_key_callback()
    {
        echo '<span class="highlight info">';

        $url = 'https://developers.google.com/fonts/docs/developer_api#acquiring_and_using_an_api_key';
        $faq_url = 'http://hivewebstudios.com/font-organizer/#faq';
        echo sprintf( __( 'To get all the current fonts, Google requires the mandatory use of an API key, get one from <a href="%s" target="_blank">HERE</a>', 'font-organizer' ), esc_url( $url ) );
        echo  sprintf( __( ' Need help? Click <a href="%s" target="_blank">here</a>', 'font-organizer' ), esc_url( $faq_url ) );
        echo '</span> <br />';

        $value = isset( $this->general_options['google_key'] ) ? esc_attr( $this->general_options['google_key']) : '';
        printf(
            '<div class="validate"><input type="text" id="google_key" name="fo_general_options[google_key]" value="%s" class="large-text %s %s" placeholder="Ex: AIzaSyB1I0couKSmsW1Nadr68IlJXXCaBi9wYwM" /><span></span></div>', $value , $this->is_google_static ? '' : 'valid', is_rtl() ? 'rtl' : 'ltr'
        );

        if($this->is_google_static){
            echo '<span style="color:#0073aa;font-weight: 500;">' . __('You are using a static google fonts list, if you want the current list you can specify an API key.') . '</span>';
        }
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function include_font_link_callback()
    {
        $checked = isset($this->general_options['include_font_link']) && $this->general_options['include_font_link'] ? 'checked="checked"' : '';
        printf(
            '<fieldset>
                <legend class="screen-reader-text"><span>%s</span></legend>
                <label for="include_font_link">
                    <input name="fo_general_options[include_font_link]" type="checkbox" id="include_font_link" value="1" %s>
                    %s
                </label>
            </fieldset>',
            __('Include Font Family Preview', 'font-organizer'),
            $checked, 
            __('Show font preview when listing the fonts (might be slow)', 'font-organizer')
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function permissions_callback(){
        $wp_roles = new WP_Roles();
		$roles = $wp_roles->get_names();
        $checked_values = !isset($this->general_options['permissions']) ? array(FO_DEFAULT_ROLE) : $this->general_options['permissions'];
		
		foreach ($roles as $role_value => $role_name) {
			$checked = $role_value == 'administrator' || in_array($role_value, $checked_values) ? 'checked' : '';

			echo '<p><input type="checkbox"'.disabled("administrator", $role_value, false).' name="fo_general_options[permissions][]" value="' . $role_value . '" '.$checked.'>'.translate_user_role($role_name).'</input></p>';
  		}
    }

    /** 
     * Prints the main fonts list.
     */
    public function fonts_list_field_callback($name)
    {
        $this->print_usable_fonts_list($name);
    }

    /** 
     * Prints the main fonts list.
     */
    public function is_important_element_field_callback($name)
    {
        $this->print_is_important_checkbox_options($name);
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function print_is_important_checkbox_options($name)
    {
         $checked = !isset($this->elements_options[$name]) || (isset($this->elements_options[$name]) && $this->elements_options[$name]) ? 'checked="checked"' : '';
        printf(
            '<fieldset>
                <legend class="screen-reader-text"><span>%s</span></legend>
                <label for="%s">
                    <input name="fo_elements_options[%s]" type="checkbox" id="%s" value="1" %s>
                    %s
                </label>
            </fieldset>',
            __('Important', 'font-organizer'),
            $name, $name, $name,
            $checked,
            __('Include !important to this element to always apply.', 'font-organizer')
        );
    }

    public function print_is_important_checkbox($name, $checked = true)
    {
        printf(
            '<fieldset>
                <legend class="screen-reader-text"><span>%s</span></legend>
                <label for="%s">
                    <input name="%s" type="checkbox" id="%s" value="1" %s>
                    %s
                </label>
            </fieldset>',
            __('Important', 'font-organizer'),
            $name, $name, $name,
            checked(true, $checked, false),
            __('Include !important to this element to always apply.', 'font-organizer')
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    private function print_usable_fonts_list($name)
    {
        $selected = isset( $this->elements_options[$name] ) ? esc_attr( $this->elements_options[$name]) : '';
        echo '<select id="'.$name.'" name="fo_elements_options['.$name.']">';
        
        echo '<option value="" '. selected('', $selected, false) . '>' . __('Default', 'font-organizer') . '</option>'; 

        //fonts section
        foreach($this->usable_fonts as $font)
        {
          $font_name = $font->family;
          $is_selected = selected($font_name, $selected, false);
          echo '<option value="'.$font_name.'" style="font-family: '.$font_name.';" '.$is_selected.'>'.$font_name.'</option>\n';
        }

        echo '</select>';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    private function print_custom_elements_usable_fonts_list($name, $default = '', $validity = '')
    {
        echo '<select id="'.$name.'" name="'.$name.'" required oninvalid="this.setCustomValidity(\'' . $validity . '\')" oninput="setCustomValidity(\'\')">';
        
        if($default){
        	 echo '<option value="">'.$default.'</option>\n';
        }

        //fonts section
        foreach($this->usable_fonts_db as $font)
        {
          $font_name = $font->name;
          $selected = isset($_GET[$name]) && $font->id == $_GET[$name];
          echo '<option value="' . $font->id . '" style="font-family: '.$font_name.';" ' . selected($selected) . '>'.$font_name.'</option>\n';
        }

        echo '</select>';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    private function print_available_fonts_list($name)
    {
        echo '<select id="'.$name.'" name="'.$name.'">';

        //fonts section
        foreach($this->available_fonts as $font)
        {
          $font_name = $font->family;
          echo '<option value="'.$font_name.'" style="font-family: '.$font_name.';">'.$font_name.'</option>\n';
        }

        echo '</select>';
    }

    private function load_usable_fonts(){
        $this->usable_fonts_db = FontsDatabaseHelper::get_usable_fonts();
        foreach ( $this->usable_fonts_db as $usable_font) {

            // Find the font from the lists.
            if($usable_font->custom){
                $font_obj = (object) array( 'family' => $usable_font->name, 'files' => (object) array('regular' => explode(self::CUSTOM_FONT_URL_SPERATOR, $usable_font->url)), 'kind' => 'custom', 'variants' => array('regular'));
                $this->usable_fonts[$font_obj->family] = $font_obj;
                $this->custom_fonts[$font_obj->family] = $font_obj;
            }else{
                $i = 0;
                foreach ($this->available_fonts as $available_font) {
                    if($available_font->family == $usable_font->name){
                        $this->usable_fonts[$available_font->family] = $available_font;
                        
                        // Remove the fond font from avaiable since it is already used.
                        unset($this->available_fonts[$i]);

                        $found = true;
                        break;
                    }

                    $i++;
                }
            }
        }
    }

    private function load_custom_elements(){
        $this->custom_elements = FontsDatabaseHelper::get_custom_elements();
    }

    private function get_early_access_fonts_array(){
        return array(
        (object) array( 'family' => 'Open Sans Hebrew', 'kind' => 'earlyaccess', 'variants' => array(), 'files' => (object) array('regular' => array('http://fonts.googleapis.com/earlyaccess/opensanshebrew.css'))),
        (object) array( 'family' => 'Open Sans Hebrew Condensed', 'kind' => 'earlyaccess', 'variants' => array(), 'files' => (object) array('regular' => array('http://fonts.googleapis.com/earlyaccess/opensanshebrewcondensed.css'))),
        (object) array( 'family' => 'Noto Sans Hebrew', 'kind' => 'earlyaccess', 'variants' => array(), 'files' => (object) array('regular' => array('http://fonts.googleapis.com/earlyaccess/notosanshebrew.css'))),
            );
    }
}