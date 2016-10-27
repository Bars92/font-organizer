<?php
class FoSettingsPage
{
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
    private $upload_error;

    /**
     * Holds the elements id and title to select a font for.
     */
    private $elements;

    /**
     * Holds the value if it should include font link (aka include google fonts for the settings page).
     * If set to false. loads only the usable fonts.
     */
    private $include_font_link;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );

        $this->fonts_per_link = 150;
        $this->supported_font_files = array('.woff','.ttf','.otf');
        $this->custom_fonts = array();
        $this->available_fonts = array();
        $this->google_fonts = array();
        $this->elements = array('body_font' =>  '<body> Font',
                                'h1_font'   =>  '<h1> Font',
                                'h2_font'   =>  '<h2> Font',
                                'h3_font'   =>  '<h3> Font',
                                'h4_font'   =>  '<h4> Font',
                                'h5_font'   =>  '<h5> Font',
                                'h6_font'   =>  '<h6> Font',
                                'p_font'    =>  '<p> Font',
                                'q_font'    =>  '<q> Font',
                                'li_font'   =>  '<li> Font',
                                'a_font'    =>  '<a> Font',
                                );

        // An upload is made. Upload the file and proccess it.
        if (isset($_POST['submit_upload_font'])){  
            if($args = $this->validate_upload()){
                $this->upload_file($args);
            }else{
                add_action( 'admin_notices', array($this, 'upload_failed_admin_notice') );
            }
        }

        if (isset($_POST['submit_usable_font'])){  
            if($args = $this->validate_add_usable()){
                $this->use_font($args);
            }else{
                add_action( 'admin_notices', array($this, 'use_font_failed_admin_notice') );
            }
        }

         if (isset($_POST['delete_usable_font'])){  
            if($args = $this->validate_delete_usable()){
                $this->delete_font($args);
            }else{
                add_action( 'admin_notices', array($this, 'delete_font_failed_admin_notice') );
            }
        }
    }

    /**
     * Register all the required scripts for this page.
     */
    public function register_scripts() {
        wp_enqueue_script( 'jquery-ui-core' );
        wp_enqueue_script( 'jquery-ui-autocomplete' );
    }

    /**
     * Add options settings page in the wordpress settings.
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        $hook = add_options_page(
            'Settings Admin', 
            'Font Settings', 
            'manage_options', 
            'font-setting-admin', 
            array( $this, 'create_font_settings_page' )
        );

        add_action( 'load-' . $hook, array( $this, 'register_scripts' ) );
    }

    /**
     * Options page callback
     */
    public function create_font_settings_page(){
        global $wpdb;
        // Set class property
        $this->general_options = get_option( 'fo_general_options' );
        $this->elements_options = get_option( 'fo_elements_options' );
        
        $this->include_font_link = !isset( $this->general_options['include_font_link'] ) || (isset( $this->general_options['include_font_link'] ) && $this->general_options['include_font_link']);

        if(isset($this->general_options['google_key']) && $this->general_options['google_key']){
            // Add Google fonts.
            set_time_limit(0);
            $response = wp_remote_get("https://www.googleapis.com/webfonts/v1/webfonts?sort=alpha&key=" . $this->general_options['google_key']);
            if( wp_remote_retrieve_response_code( $response ) != 200){
                    add_settings_error('google_key', '', __('Google API key is not valid!', 'fo'), 'error');
                    settings_errors( 'google_key' );
            }

            $this->google_fonts = json_decode( wp_remote_retrieve_body($response))->items;
        }else{
            add_settings_error('google_key', '', __('Google API key is not set! Cannot display google fonts.', 'fo'), 'error');
            settings_errors( 'google_key' );
        }

        // Add known fonts.
        $this->known_fonts = $this->get_known_fonts_array();

        $this->available_fonts = array_merge($this->available_fonts, $this->google_fonts, $this->known_fonts );

        // Get all usable fonts and add them to a list.
        $usable_fonts = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . FO_USABLE_FONTS_DATABASE . ' ORDER BY id DESC');
        foreach ( $usable_fonts as $usable_font) {

            // Find the font from the lists.
            if($usable_font->custom){
                $font_obj = (object) [ 'family' => $usable_font->name, 'files' => (object) ['regular' => $usable_font->url], 'kind' => 'custom', 'variants' => array('regular')];
                $this->usable_fonts[] = $font_obj;
                $this->custom_fonts[] = $font_obj;
            }else{
                $i = 0;
                foreach ($this->available_fonts as $available_font) {
                    if($available_font->family == $usable_font->name){
                        $this->usable_fonts[] = $available_font;
                        
                        // Remove the fond font from avaiable since it is already used.
                        unset($this->available_fonts[$i]);

                        $found = true;
                        break;
                    }

                    $i++;
                }
            }
        }

        // Load the google fonts if selected or if not specified. else load just whats usable.
        if($this->include_font_link)
            fo_print_links($this->google_fonts, $this->fonts_per_link);
        else
            fo_print_links($this->usable_fonts, $this->fonts_per_link);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Font Settings', 'fo'); ?></h1>
                <div id="poststuff">  
                    <div id="post-body" class="metabox-holder columns-2">

                    <!-- main content -->
                    <div id="post-body-content">
                        <div class="postbox">
                            <button type="button" class="handlediv button-link" aria-expanded="false">
                                <span class="screen-reader-text"><?php _e('Toggle panel: General Settings', 'fo'); ?></span><span class="toggle-indicator" aria-hidden="true"></span>
                            </button>
                            <h2 class="hndle ui-sortable-handle"><span><?php _e('General Settings', 'fo'); ?></span></h2>
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

                        <div class="postbox">
                         <button type="button" class="handlediv button-link" aria-expanded="false">
                                <span class="screen-reader-text"><?php _e('Toggle panel: First Step: Add Fonts', 'fo'); ?></span><span class="toggle-indicator" aria-hidden="true"></span>
                            </button>
                            <h2 class="hndle ui-sortable-handle"><span><?php _e('1. Add Fonts', 'fo'); ?></span></h2>
                            <div class="inside">
                                <span><?php _e('Step 1: Select and add fonts to be used in your website. Select as many as you wish.', 'fo'); ?></span>
                                <br />
                                <span><?php _e('You can select google or regular fonts.', 'fo'); ?></span>
                                <form action="" id="add_usable_font_form" name="add_usable_font_form" method="post"> 
                                    <table class="form-table">
                                        <tr>
                                            <th scope="row"><?php _e('Available Fonts', 'fo'); ?></th>
                                            <td><?php  $this->print_available_fonts_list('usable_font'); ?></td>
                                        </tr>   
                                        <tr>        
                                            <th scope="row"></th>
                                            <td>
                                             <?php wp_nonce_field( 'add_usable_font', 'add_usable_font_nonce' ); ?>
                                            <input type="submit" name="submit_usable_font" id="submit_usable_font" class="button-primary" value="<?php _e('Use This Font', 'fo'); ?>" />
                                            </td>
                                        </tr>
                                    </table>
                                </form> 
                            </div>  
                        </div>
                   
                        <div class="postbox">
                            <button type="button" class="handlediv button-link" aria-expanded="false">
                                <span class="screen-reader-text"><?php _e('Toggle panel: Custom Fonts', 'fo'); ?></span><span class="toggle-indicator" aria-hidden="true"></span>
                            </button>
                            <h2 class="hndle ui-sortable-handle"><span><?php _e('2. Custom Fonts', 'fo'); ?></span></h2>
                            <div class="inside">
                                <span><?php _e('Step 2: Upload custom fonts to be used in your website. Here too, you can upload as many as you wish.', 'fo'); ?></span>
                                <form action="" id="add_font_form" name="add_font_form" method="post" enctype="multipart/form-data"> 
                                    <table class="form-table">
                                        <tr>
                                            <th scope="row"><?php _e('Font Name', 'fo'); ?></th>
                                            <td><input type="text" id="font_name" name="font_name" value="" maxlength="20" class="required" /></td>
                                        </tr>   
                                        <tr>    
                                            <th scope="row"><?php _e('Font File', 'fo'); ?></th>
                                            <td>
                                            <input type="file" id="font_file" name="font_file" value="" class="required" accept="<?php echo join(',',$this->supported_font_files); ?>" /><br/>
                                            <em><?php echo __('Accepted Font Format : ', 'fo') . join(', ',$this->supported_font_files); ?></em><br/>
                                            </td>
                                        </tr>
                                        <tr>        
                                            <th scope="row"></th>
                                            <td>
                                             <?php wp_nonce_field( 'add_custom_font', 'add_custom_font_nonce' ); ?>
                                            <input type="submit" name="submit_upload_font" id="submit_upload_font" class="button-primary" value="<?php _e('Upload', 'fo'); ?>" />
                                            </td>
                                        </tr>
                                    </table>
                                </form>   
                            </div>
                        </div>
   
                        <div class="postbox">
                            <button type="button" class="handlediv button-link" aria-expanded="false">
                                <span class="screen-reader-text"><?php _e('Toggle panel: Elements Settings', 'fo'); ?></span><span class="toggle-indicator" aria-hidden="true"></span>
                            </button>
                            <h2 class="hndle ui-sortable-handle"><span><?php _e('3. Elements Settings', 'fo'); ?></span></h2>
                            <div class="inside">

                                <span><?php _e('Step 3: For each element you can assign a font you have added in step 1 & 2.', 'fo'); ?></span>
                                <p><strong><?php _e('Note: ', 'fo'); ?></strong><?php _e('Custom fonts you uploaded are automaticly used in your website.', 'fo'); ?></p>

                                <table class="widefat">
                                    <thead>
                                    <tr>
                                        <th class="row-title"><?php _e('Font Name', 'fo'); ?></th>
                                        <th class="row-title"><?php _e('Font Source', 'fo'); ?></th>
                                        <th class="row-title"><?php _e('Font URL', 'fo'); ?></th>
                                        <th class="row-title"></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php 
                                        $is_alternate = false;
                                        foreach ($this->usable_fonts as $usable_font): ?>
                                             <tr class="<?php echo $is_alternate ? 'alternate' : ''; ?>">
                                                <td style="font-family: <?php echo $usable_font->family; ?>"><?php echo $usable_font->family; ?></td>
                                                <td><?php strpos($usable_font->kind, 'webfonts') !== false ? _e('Google', 'fo') : _e(ucfirst($usable_font->kind), 'fo'); ?></td>
                                                <td><?php echo $usable_font->files->regular; ?></td>
                                                <td>
                                                    <form action="" method="post" name="delete_usable_font">
                                                        <input type="hidden" name="font_name" value="<?php echo $usable_font->family; ?>" />
                                                      <?php 
                                                        submit_button(__('Delete', 'fo'), $type = 'delete', $name = 'delete_usable_font', $wrap = false);
                                                        wp_nonce_field( 'delete_usable_font', 'delete_usable_font_nonce' );
                                                      ?>
                                                    </form>
                                                </td>
                                            </tr>
                                    <?php
                                        $is_alternate = !$is_alternate;
                                        endforeach;
                                   ?>
                                </table>
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
                    </div>

                    <!-- sidebar -->
                    <div id="postbox-container-1" class="postbox-container">
                        <div class="meta-box-sortables">
                            <div class="postbox">
                            <h2>
                                <span><?php esc_attr_e('Header', 'fo'); ?></span>
                            </h2>

                            <div class="inside">
                                <p><?php esc_attr_e(
                                        'Everything you see here, from the documentation to the code itself, was created by and for the community. WordPress is an Open Source project, which means there are hundreds of people all over the world working on it. (More than most commercial platforms.) It also means you are free to use it for anything from your cat’s home page to a Fortune 500 web site without paying anyone a license fee and a number of other important freedoms.',
                                        'wp_admin_style'
                                    ); ?></p>
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
            $this->upload_error = __('Session ended, please try again.', 'fo');
            return false;
        }

        $args['font_name'] = sanitize_text_field( $_POST['font_name'] );
        if(!$args['font_name']){
            $this->upload_error = __('Font name is empty or invalid.', 'fo');
            return false;
        }

        if(!isset($_FILES['font_file'])){
            $this->upload_error = __('Font file is not selected.', 'fo');
            return false;
        }

        $args['font_file'] = $_FILES['font_file'];
        $args['font_file_name'] = sanitize_file_name( $args['font_file']['name'] );
        if(!$args['font_file_name']){
            $this->upload_error = __('Font file is not valid.', 'fo');
            return false;
        }

        return $args;
    }

    private function validate_add_usable(){
        if(!isset( $_POST['add_usable_font_nonce'] ) || !wp_verify_nonce( $_POST['add_usable_font_nonce'], 'add_usable_font' )){
            $this->upload_error = __('Session ended, please try again.', 'fo');
            return false;
        }

        $args['usable_font'] = sanitize_text_field( $_POST['usable_font'] );
        if(!$args['usable_font']){
            $this->upload_error = __('Usable font is empty or invalid.', 'fo');
            return false;
        }

        return $args;
    }

    private function validate_delete_usable(){
        if(!isset( $_POST['delete_usable_font_nonce'] ) || !wp_verify_nonce( $_POST['delete_usable_font_nonce'], 'delete_usable_font' )){
            $this->upload_error = __('Session ended, please try again.', 'fo');
            return false;
        }

        $args['font_name'] = sanitize_text_field( $_POST['font_name'] );
        if(!$args['font_name']){
            $this->upload_error = __('Something went horribly worng. Ask the support!', 'fo');
            return false;
        }

        return $args;
    }

    private function upload_file($args = array()){
        if ( ! function_exists( 'wp_handle_upload' ) ) 
            require_once( ABSPATH . 'wp-admin/includes/file.php' );

        $uploadedfile = $args['font_file'];

        $upload_overrides = array( 'test_form' => false );
        // Register our path override.
        add_filter( 'upload_dir', array($this, 'fo_upload_dir') );

        $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

        // Set everything back to normal.
        remove_filter( 'upload_dir', array($this, 'fo_upload_dir')  );

        if ( $movefile && ! isset( $movefile['error'] ) ) {
            add_action( 'admin_notices', array($this, 'upload_successfull_admin_notice') );
            $this->save_to_database($args['font_name'], $movefile['url'], true);
        } else {
            /**
             * Error generated by _wp_handle_upload()
             * @see _wp_handle_upload() in wp-admin/includes/file.php
             */
            add_action( 'admin_notices', array($this, 'upload_failed_admin_notice') );
            $this->upload_error = $movefile['error'];
        }
    }

    private function use_font($args = array()){
            add_action( 'admin_notices', array($this, 'use_font_successfull_admin_notice') );
            $this->save_to_database($args['usable_font']);
    }

    private function delete_font($args = array()){
            add_action( 'admin_notices', array($this, 'delete_font_successfull_admin_notice') );
            $this->delete_from_database($args['font_name']);
    }

    private function delete_from_database($name){
        global $wpdb;
        $table_name = $wpdb->prefix . FO_USABLE_FONTS_DATABASE;

        $wpdb->delete( $table_name, array( 'name' => $name ) );
    }

    private function save_to_database($name, $url = '', $is_custom = false){
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

    public function use_font_successfull_admin_notice() {
        ?>
        <div class="updated notice">
            <p><?php _e( 'Font can now be used in your website!', 'fo' ); ?></p>
        </div>
        <?php
    }

    public function delete_font_successfull_admin_notice() {
        ?>
        <div class="updated notice">
            <p><?php _e( 'Font deleted from your website!', 'fo' ); ?></p>
        </div>
        <?php
    }

    public function upload_successfull_admin_notice() {
        ?>
        <div class="updated notice">
            <p><?php _e( 'The file has been uploaded!', 'fo' ); ?></p>
        </div>
        <?php
    }

    public function upload_failed_admin_notice() {
        ?>
        <div class="error notice">
            <p><?php _e( 'Error uploading the file: ', 'fo' ) . $this->upload_error; ?></p>
        </div>
        <?php
    }

    public function use_font_failed_admin_notice() {
        ?>
        <div class="error notice">
            <p><?php _e( 'Error adding font to website fonts: ', 'fo' ) . $this->upload_error; ?></p>
        </div>
        <?php
    }

    public function delete_font_failed_admin_notice() {
        ?>
        <div class="error notice">
            <p><?php _e( 'Error deleted font: ', 'fo' ) . $this->upload_error; ?></p>
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
        return array(
            'path'   => $dir['basedir'] . '/font-organizer',
            'url'    => $dir['baseurl'] . '/font-organizer',
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
            'Google API Key', // Title 
            array( $this, 'google_key_callback' ), // Callback
            'font-setting-admin', // Page
            'setting_general' // Section           
        );   
        add_settings_field(
            'include_font_link', // ID
            'Show Font Family Preview', // Title 
            array( $this, 'include_font_link_callback' ), // Callback
            'font-setting-admin', // Page
            'setting_general' // Section           
        );   
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
        }

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_general_section_info()
    {
        print 'This is the general settings for the site.';
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

        echo sprintf( __( 'To get all the fonts, Google requires the mandatory use of an API key, get one from <a href="%s" target="_blank">HERE</a>', 'res_map' ), esc_url( $url ) );

        echo '</span> <br />';

        printf(
            '<input type="text" id="google_key" name="fo_general_options[google_key]" value="%s" class="large-text" placeholder="Ex: AIzaSyB1I0couKSmsW1Nadr68IlJXXCaBi9wYwM" />',
            isset( $this->general_options['google_key'] ) ? esc_attr( $this->general_options['google_key']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function include_font_link_callback()
    {
        $checked = !isset($this->general_options['include_font_link']) || (isset($this->general_options['include_font_link']) && $this->general_options['include_font_link']) ? 'checked="checked"' : '';
        printf(
            '<fieldset>
                <legend class="screen-reader-text"><span>%s</span></legend>
                <label for="include_font_link">
                    <input name="fo_general_options[include_font_link]" type="checkbox" id="include_font_link" value="1" %s>
                    %s
                </label>
            </fieldset>',
            __('Include Font Family Preview', 'fo'),
            $checked, 
            __('Show font preview when listing the fonts (might be slow)', 'fo')
        );
    }

    /** 
     * Prints the main fonts list.
     */
    public function fonts_list_field_callback($name)
    {
        $this->print_usable_fonts_list($name);
    }

    /** 
     * Get the settings option array and print one of its values
     */
    private function print_usable_fonts_list($name)
    {
        $selected = isset( $this->elements_options[$name] ) ? esc_attr( $this->elements_options[$name]) : '';
        echo '<select id="'.$name.'" name="fo_elements_options['.$name.']">';
        
        echo '<option value="" '. selected('', $selected, false) . '>' . __('None', 'fo') . '</option>'; 

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
    private function print_available_fonts_list($name)
    {
        echo '<select id="'.$name.'" name="'.$name.'">';

        //fonts section
        foreach($this->available_fonts as $font)
        {
          $font_name = $font->family;
          $is_selected = $font_name === $selected ? ' selected' : '';
          echo '<option value="'.$font_name.'" style="font-family: '.$font_name.';">'.$font_name.'</option>\n';
        }

        echo '</select>';
    }

    private function get_known_fonts_array()
    {
        return array(
(object) [ 'family' => 'Calibri', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Abadi MT Condensed', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Adobe Minion Web', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Agency FB', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Aharoni', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Aldhabi', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Algerian', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Almanac MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'American Uncial', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Andale Mono', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Andalus', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Andy', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Angsana New', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'AngsanaUPC', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Aparajita', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Arabic Transparent', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Arabic Typesetting', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Arial', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Arial Black', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Arial Narrow', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Arial Narrow Special', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Arial Rounded MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Arial Special', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Arial Unicode MS', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Augsburger Initials', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Baskerville Old Face', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Batang', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'BatangChe', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Bauhaus 93', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Beesknees ITC', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Bell MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Berlin Sans FB', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Bernard MT Condensed', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Bickley Script', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Blackadder ITC', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Bodoni MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Bodoni MT Condensed', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Bon Apetit MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Book Antiqua', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Bookman Old Style', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Bookshelf Symbol', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Bradley Hand ITC', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Braggadocio', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'BriemScript', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Britannic', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Britannic Bold', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Broadway', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Browallia New', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'BrowalliaUPC', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Brush Script MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Calibri', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Californian FB', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Calisto MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Cambria', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Cambria Math', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Candara', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Cariadings', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Castellar', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Centaur', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Century', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Century Gothic', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Century Schoolbook', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Chiller', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Colonna MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Comic Sans MS', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Consolas', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Constantia', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Contemporary Brush', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Cooper Black', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Copperplate Gothic', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Corbel', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Cordia New', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'CordiaUPC', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Courier New', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Curlz MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'DaunPenh', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'David', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Desdemona', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'DFKai-SB', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'DilleniaUPC', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Directions MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'DokChampa', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Dotum', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'DotumChe', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Ebrima', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Eckmann', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Edda', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Edwardian Script ITC', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Elephant', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Engravers MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Enviro', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Eras ITC', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Estrangelo Edessa', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'EucrosiaUPC', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Euphemia', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Eurostile', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'FangSong', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Felix Titling', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Fine Hand', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Fixed Miriam Transparent', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Flexure', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Footlight MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Forte', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Franklin Gothic', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Franklin Gothic Medium', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'FrankRuehl', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'FreesiaUPC', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Freestyle Script', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'French Script MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Futura', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Gabriola', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Gadugi', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Garamond', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Garamond MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Gautami', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Georgia', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Georgia Ref', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Gigi', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Gill Sans MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Gill Sans MT Condensed', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Gisha', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Gloucester', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Goudy Old Style', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Goudy Stout', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Gradl', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Gulim', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'GulimChe', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Gungsuh', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'GungsuhChe', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Haettenschweiler', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Harlow Solid Italic', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Harrington', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'High Tower Text', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Holidays MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Impact', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Imprint MT Shadow', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Informal Roman', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'IrisUPC', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Iskoola Pota', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'JasmineUPC', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Jokerman', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Juice ITC', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'KaiTi', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Kalinga', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Kartika', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Keystrokes MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Khmer UI', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Kino MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'KodchiangUPC', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Kokila', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Kristen ITC', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Kunstler Script', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Lao UI', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Latha', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'LCD', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Leelawadee', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Levenim MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'LilyUPC', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Lucida Blackletter', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Lucida Bright', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Lucida Bright Math', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Lucida Calligraphy', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Lucida Console', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Lucida Fax', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Lucida Handwriting', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Lucida Sans', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Lucida Sans Typewriter', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Lucida Sans Unicode', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Magneto', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Maiandra GD', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Malgun Gothic', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Mangal', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Map Symbols', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Marlett', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Matisse ITC', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Matura MT Script Capitals', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'McZee', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Mead Bold', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Meiryo', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Meiryo UI', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Mercurius Script MT Bold', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Microsoft Himalaya', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Microsoft JhengHei', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Microsoft JhengHei UI', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Microsoft New Tai Lue', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Microsoft PhagsPa', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Microsoft Sans Serif', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Microsoft Tai Le', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Microsoft Uighur', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Microsoft YaHei', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Microsoft YaHei UI', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Microsoft Yi Baiti', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MingLiU', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MingLiU_HKSCS', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MingLiU_HKSCS-ExtB', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MingLiU-ExtB', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Minion Web', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Miriam', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Miriam Fixed', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Mistral', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Modern No. 20', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Mongolian Baiti', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Monotype Corsiva', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Monotype Sorts', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Monotype.com', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MoolBoran', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MS Gothic', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MS LineDraw', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MS Mincho', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MS Outlook', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MS PGothic', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MS PMincho', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MS Reference', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MS UI Gothic', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MT Extra', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MV Boli', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Myanmar Text', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Narkisim', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'New Caledonia', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'News Gothic MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Niagara', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Nirmala UI', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'NSimSun', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Nyala', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'OCR A Extended', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'OCRB', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'OCR-B-Digits', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Old English Text MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Onyx', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Palace Script MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Palatino Linotype', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Papyrus', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Parade', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Parchment', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Parties MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Peignot Medium', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Pepita MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Perpetua', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Perpetua Titling MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Placard Condensed', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Plantagenet Cherokee', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Playbill', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'PMingLiU', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'PMingLiU-ExtB', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Poor Richard', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Pristina', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Raavi', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Rage Italic', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Ransom', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Ravie', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'RefSpecialty', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Rockwell', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Rockwell Condensed', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Rockwell Extra Bold', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Rod', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Runic MT Condensed', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Sakkal Majalla', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Script MT Bold', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Segoe Chess', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Segoe Print', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Segoe Pseudo', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Segoe Script', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Segoe UI', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Segoe UI Symbol', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Shonar Bangla', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Showcard Gothic', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Shruti', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Signs MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'SimHei', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Simplified Arabic', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Simplified Arabic Fixed', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'SimSun', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'SimSun-ExtB', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Snap ITC', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Sports MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Stencil', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Stop', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Sylfaen', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Symbol', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Tahoma', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Temp Installer Font', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Tempo Grunge', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Tempus Sans ITC', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Times New Roman', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Times New Roman Special', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Traditional Arabic', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Transport MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Trebuchet MS', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Tunga', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Tw Cen MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Tw Cen MT Condensed', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Urdu Typesetting', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Utsaah', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Vacation MT', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Vani', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Verdana', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Verdana Ref', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Vijaya', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Viner Hand ITC', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Vivaldi', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Vixar ASCI', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Vladimir Script', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Vrinda', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Webdings', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Westminster', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Wide Latin', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Wingdings', 'kind' => 'standart', 'variants' => array(), 'files' => (object) ['regular' => '']],
        );
    }
}