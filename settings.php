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
        $this->supported_font_files = array('.woff', '.woff2', '.ttf','.otf');
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
        wp_enqueue_script( 'fo-settings-script', plugins_url( 'assets/js/settings.js', __FILE__ ) , array( 'jquery' ) );
        wp_enqueue_style( 'fo-settings-css', plugins_url( 'assets/css/settings.css', __FILE__ ) );
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
        add_action( 'load-' . $hook, array( $this, 'create_css_file' ) );
    }

    public function create_css_file(){
        global $css_full_file_path;
        global $css_directory_path;

        if(!isset($_GET['settings-updated']) || !$_GET['settings-updated']){
            return;
        }
        
        // This is called when the class is rebuilt before the redirect, so we need to initialize
        // some stuff again.
        $this->init();

        $content = "/* This Awesome CSS file was created by Font Orgranizer from HiveTeam :) */\n\n";
        $custom_fonts_content = '';
        $google_fonts = array();
        foreach ($this->usable_fonts as $key => $usable_font) {
            switch ($usable_font->kind) {
                case 'custom':
                    $url = $usable_font->files->regular;
                    $custom_fonts_content .= "
@font-face {
    font-family: '" . $usable_font->family . "';
    src: url('" . $url . "') format('" . fo_get_font_format($url) . "');
    font-weight: normal;
    font-style: normal;
}\n";
                    break;
                case 'webfonts#webfont': // Google font
                    $google_fonts[] = str_replace(' ', '+', $usable_font->family);
                case 'regular':
                default:
                    break;
            }
        }

        // Add Google fonts to the css. MUST BE FIRST.
        if(!empty($google_fonts)){
            // We are assuming not to much google fonts. If it is, we need to split the request.
           // $content .= "<link href='http://fonts.googleapis.com/css?family=". implode("|", $google_fonts) . "' rel='stylesheet' type='text/css'>\n";
            $content .= "@import url('http://fonts.googleapis.com/css?family=". implode("|", $google_fonts) . "');\n";
        }

        // Add the custom fonts css that was created before.
        $content .= $custom_fonts_content;

        // Add the known elements css.
        foreach ($this->elements_options as $key => $value) {
            if(strpos($key, 'important') || !$value)
                continue;

            $strip_key = str_replace('_font', '', $key);
            $important = $this->elements_options[$key . '_important'];
            $content .= sprintf("%s { font-family: '%s'%s; }\n", $strip_key, $value, $important ? '!important' : '');
        }

        if($content){

            // Make sure directory exists.
            if(!is_dir($css_directory_path))
                 mkdir($css_directory_path, 0777, true);

            $fhandler = fopen($css_full_file_path, "w");
            if(!$fhandler){
                add_action( 'admin_notices', array($this, 'generate_css_failed_admin_notice') );
                return;
            }

            fwrite($fhandler, $content);
            fclose($fhandler);
        }
    }

    /**
     * Initialize the class private fields. Options, Google fonts list, known fonts, available fonts,
     * and all the usable fonts.
     */
    public function init(){
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

            $this->google_fonts = json_decode(wp_remote_retrieve_body($response))->items;
        }else{
            add_settings_error('google_key', '', __('Google API key is not set! Cannot display google fonts.', 'fo'), 'error');
            settings_errors( 'google_key' );
        }

        // Add known fonts.
        $this->known_fonts = $this->get_known_fonts_array();

        $this->available_fonts = array_merge($this->available_fonts, $this->google_fonts, $this->known_fonts );

        // Get all usable fonts and add them to a list.
        $this->load_usable_fonts();
    }

    /**
     * Options page callback
     */
    public function create_font_settings_page(){
        
        $this->init();

        // Load the google fonts if selected or if not specified. else load just whats usable.
        if($this->include_font_link)
            fo_print_links($this->google_fonts, $this->fonts_per_link);
        else
            fo_print_links($this->usable_fonts, $this->fonts_per_link);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Font Settings', 'fo'); ?></h1>

          <div class="steps sticky">
          <ol >
            <li class="level1">
              <div><a href="#step1"><span>1.</span><?php _e('General Settings', 'fo'); ?></a></div>
            </li>
            <li class="level2">
              <div><a href="#step2"><span>2.</span><?php _e('Add Fonts', 'fo'); ?></a></div>
            </li>
            <li class="level3">
              <div><a href="#step3"><span>3.</span><?php _e('Custom Fonts', 'fo'); ?></a></div>
            </li>
            <li class="level4">
              <div><a href="#step4"><span>4.</span><?php _e('Known Elements Settings', 'fo'); ?></a></div>
            </li>
            <li class="level5">
              <div><a href="#step5"><span>5.</span><?php _e('Custom Elements Settings', 'fo'); ?></a></div>
            </li>
            <li class="level6">
              <div><a href="#step6"><span>6.</span><?php _e('Manage Fonts', 'fo'); ?></a></div>
            </li>
          </ol>
        </div>

                <div id="poststuff">  
                    <div id="post-body" class="metabox-holder columns-2">

                    <!-- main content -->
                    <div id="post-body-content">

                        <!-- General Settings Section -->
                        <div class="postbox">
                            <a name="step1"></a>
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

                        <!-- Add Google & Regular Fonts To Website Section -->
                        <div class="postbox">
                            <a name="step2"></a>
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
                   
                        <!-- Add Custom Fonts To Website Section -->
                        <div class="postbox">
                            <a name="step3"></a>
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
   
                        <!-- Assign Fonts To Known Elements Section -->
                        <div class="postbox">
                            <a name="step4"></a>
                            <button type="button" class="handlediv button-link" aria-expanded="false">
                                <span class="screen-reader-text"><?php _e('Toggle panel: Known Elements Settings', 'fo'); ?></span><span class="toggle-indicator" aria-hidden="true"></span>
                            </button>
                            <h2 class="hndle ui-sortable-handle"><span><?php _e('3. Known Elements Settings', 'fo'); ?></span></h2>
                            <div class="inside">

                                <span><?php _e('Step 3: For each element you can assign a font you have added in step 1 & 2.', 'fo'); ?></span>
                                <p><strong><?php _e('Note: ', 'fo'); ?></strong><?php _e('Custom fonts you uploaded are automaticly used in your website.', 'fo'); ?></p>

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
                            <button type="button" class="handlediv button-link" aria-expanded="false">
                                <span class="screen-reader-text"><?php _e('Toggle panel: Custom Elements Settings', 'fo'); ?></span><span class="toggle-indicator" aria-hidden="true"></span>
                            </button>
                            <h2 class="hndle ui-sortable-handle"><span><?php _e('4. Custom Elements Settings', 'fo'); ?></span></h2>
                            <div class="inside">

                                <span><?php _e('Step 4: Assign font that you have added to your website to custom elements.', 'fo'); ?></span>
                                <em><?php _e('Example: #myelementid, .myelementclass, .myelementclass .foo, etc.', 'fo'); ?></em>
                                <form action="" id="add_usable_font_form" name="add_usable_font_form" method="post"> 
                                    <table class="widefat">
                                    <thead>
                                    <tr>
                                        <th class="row-title"><?php _e('Font Name', 'fo'); ?></th>
                                        <th class="row-title"><?php _e('Custom Elements', 'fo'); ?></th>
                                        <th class="row-title"><?php _e('Font URL', 'fo'); ?></th>
                                        <th class="row-title"></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php 
                                        $is_alternate = false;
                                        foreach ($this->usable_fonts_db as $usable_font): ?>
                                             <tr class="<?php echo $is_alternate ? 'alternate' : ''; ?>">
                                                <td style="font-family: <?php echo $usable_font->name; ?>"><?php echo $usable_font->name; ?></td>
                                                <td>
                                                    <textarea id="custom_elements" name="custom_elements" cols="80" rows="10"><?php echo $usable_font->custom_elements; ?></textarea>
                                                </td>
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
                                   </tbody>
                                </table>
                                    <table class="form-table">
                                        <tr>
                                            <th scope="row"><?php _e('Font', 'fo'); ?></th>
                                            <td><?php $this->print_custom_elements_usable_fonts_list(); ?></td>
                                        </tr>   
                                        <tr>        
                                            <th scope="row"><?php _e('Custom Element', 'fo'); ?></th>
                                            <td>
                                                <textarea id="custom_elements" name="custom_elements" cols="80" rows="10"></textarea>
                                            </td>
                                        </tr>
                                        <tr>        
                                            <th scope="row"></th>
                                            <td>
                                             <?php wp_nonce_field( 'add_custom_elements', 'add_custom_elements_nonce' ); ?>
                                            <input type="submit" name="submit_custom_elements" id="submit_custom_elements" class="button-primary" value="<?php _e('Apply Custom Elements', 'fo'); ?>" />
                                            </td>
                                        </tr>
                                    </table>
                                </form> 
                            </div>
                        </div>

                        <!-- Manage Used fonts Section -->
                        <div class="postbox">
                            <a name="step6"></a>
                            <button type="button" class="handlediv button-link" aria-expanded="false">
                                <span class="screen-reader-text"><?php _e('Toggle panel: Manage Fonts', 'fo'); ?></span><span class="toggle-indicator" aria-hidden="true"></span>
                            </button>
                            <h2 class="hndle ui-sortable-handle"><span><?php _e('5. Manage Fonts', 'fo'); ?></span></h2>
                            <div class="inside">

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
                                   </tbody>
                                </table>
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

        $movefile = fo_upload_file($args['font_file'], array($this, 'fo_upload_dir'));

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
            <p><?php _e( 'Error deleting font: ', 'fo' ) . $this->upload_error; ?></p>
        </div>
        <?php
    }

    public function generate_css_failed_admin_notice() {
        ?>
        <div class="error notice">
            <p><?php _e( 'Failed to open or create the css file. Check for permissions.', 'fo' ); ?></p>
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
     * Prints the main fonts list.
     */
    public function is_important_element_field_callback($name)
    {
        $this->print_is_important_checkbox($name);
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function print_is_important_checkbox($name)
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
            __('Important', 'fo'),
            $name, $name, $name,
            $checked,
            __('Include !important to this element to always apply.', 'fo')
        );
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
    private function print_custom_elements_usable_fonts_list()
    {
        echo '<select id="custom_elements_selector" name="custom_elements_selector">';
        
        //fonts section
        foreach($this->usable_fonts as $font)
        {
          $font_name = $font->family;
          echo '<option value="'.$font_name.'" style="font-family: '.$font_name.';">'.$font_name.'</option>\n';
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

    private function load_usable_fonts(){
        global $wpdb;

        $this->usable_fonts_db = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . FO_USABLE_FONTS_DATABASE . ' ORDER BY id DESC');
        foreach ( $this->usable_fonts_db as $usable_font) {

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
    }

    private function get_known_fonts_array()
    {
        return array(
(object) [ 'family' => 'Calibri', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Abadi MT Condensed', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Adobe Minion Web', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Agency FB', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Aharoni', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Aldhabi', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Algerian', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Almanac MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'American Uncial', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Andale Mono', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Andalus', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Andy', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Angsana New', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'AngsanaUPC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Aparajita', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Arabic Transparent', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Arabic Typesetting', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Arial', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Arial Black', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Arial Narrow', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Arial Narrow Special', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Arial Rounded MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Arial Special', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Arial Unicode MS', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Augsburger Initials', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Baskerville Old Face', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Batang', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'BatangChe', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Bauhaus 93', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Beesknees ITC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Bell MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Berlin Sans FB', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Bernard MT Condensed', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Bickley Script', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Blackadder ITC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Bodoni MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Bodoni MT Condensed', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Bon Apetit MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Book Antiqua', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Bookman Old Style', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Bookshelf Symbol', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Bradley Hand ITC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Braggadocio', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'BriemScript', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Britannic', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Britannic Bold', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Broadway', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Browallia New', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'BrowalliaUPC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Brush Script MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Calibri', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Californian FB', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Calisto MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Cambria', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Cambria Math', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Candara', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Cariadings', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Castellar', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Centaur', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Century', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Century Gothic', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Century Schoolbook', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Chiller', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Colonna MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Comic Sans MS', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Consolas', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Constantia', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Contemporary Brush', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Cooper Black', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Copperplate Gothic', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Corbel', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Cordia New', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'CordiaUPC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Courier New', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Curlz MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'DaunPenh', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'David', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Desdemona', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'DFKai-SB', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'DilleniaUPC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Directions MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'DokChampa', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Dotum', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'DotumChe', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Ebrima', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Eckmann', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Edda', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Edwardian Script ITC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Elephant', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Engravers MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Enviro', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Eras ITC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Estrangelo Edessa', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'EucrosiaUPC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Euphemia', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Eurostile', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'FangSong', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Felix Titling', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Fine Hand', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Fixed Miriam Transparent', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Flexure', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Footlight MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Forte', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Franklin Gothic', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Franklin Gothic Medium', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'FrankRuehl', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'FreesiaUPC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Freestyle Script', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'French Script MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Futura', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Gabriola', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Gadugi', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Garamond', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Garamond MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Gautami', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Georgia', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Georgia Ref', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Gigi', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Gill Sans MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Gill Sans MT Condensed', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Gisha', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Gloucester', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Goudy Old Style', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Goudy Stout', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Gradl', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Gulim', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'GulimChe', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Gungsuh', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'GungsuhChe', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Haettenschweiler', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Harlow Solid Italic', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Harrington', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'High Tower Text', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Holidays MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Impact', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Imprint MT Shadow', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Informal Roman', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'IrisUPC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Iskoola Pota', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'JasmineUPC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Jokerman', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Juice ITC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'KaiTi', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Kalinga', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Kartika', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Keystrokes MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Khmer UI', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Kino MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'KodchiangUPC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Kokila', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Kristen ITC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Kunstler Script', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Lao UI', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Latha', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'LCD', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Leelawadee', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Levenim MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'LilyUPC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Lucida Blackletter', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Lucida Bright', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Lucida Bright Math', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Lucida Calligraphy', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Lucida Console', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Lucida Fax', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Lucida Handwriting', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Lucida Sans', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Lucida Sans Typewriter', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Lucida Sans Unicode', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Magneto', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Maiandra GD', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Malgun Gothic', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Mangal', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Map Symbols', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Marlett', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Matisse ITC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Matura MT Script Capitals', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'McZee', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Mead Bold', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Meiryo', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Meiryo UI', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Mercurius Script MT Bold', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Microsoft Himalaya', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Microsoft JhengHei', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Microsoft JhengHei UI', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Microsoft New Tai Lue', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Microsoft PhagsPa', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Microsoft Sans Serif', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Microsoft Tai Le', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Microsoft Uighur', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Microsoft YaHei', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Microsoft YaHei UI', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Microsoft Yi Baiti', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MingLiU', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MingLiU_HKSCS', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MingLiU_HKSCS-ExtB', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MingLiU-ExtB', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Minion Web', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Miriam', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Miriam Fixed', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Mistral', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Modern No. 20', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Mongolian Baiti', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Monotype Corsiva', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Monotype Sorts', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Monotype.com', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MoolBoran', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MS Gothic', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MS LineDraw', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MS Mincho', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MS Outlook', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MS PGothic', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MS PMincho', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MS Reference', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MS UI Gothic', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MT Extra', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'MV Boli', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Myanmar Text', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Narkisim', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'New Caledonia', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'News Gothic MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Niagara', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Nirmala UI', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'NSimSun', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Nyala', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'OCR A Extended', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'OCRB', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'OCR-B-Digits', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Old English Text MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Onyx', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Palace Script MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Palatino Linotype', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Papyrus', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Parade', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Parchment', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Parties MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Peignot Medium', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Pepita MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Perpetua', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Perpetua Titling MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Placard Condensed', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Plantagenet Cherokee', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Playbill', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'PMingLiU', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'PMingLiU-ExtB', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Poor Richard', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Pristina', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Raavi', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Rage Italic', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Ransom', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Ravie', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'RefSpecialty', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Rockwell', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Rockwell Condensed', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Rockwell Extra Bold', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Rod', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Runic MT Condensed', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Sakkal Majalla', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Script MT Bold', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Segoe Chess', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Segoe Print', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Segoe Pseudo', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Segoe Script', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Segoe UI', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Segoe UI Symbol', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Shonar Bangla', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Showcard Gothic', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Shruti', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Signs MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'SimHei', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Simplified Arabic', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Simplified Arabic Fixed', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'SimSun', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'SimSun-ExtB', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Snap ITC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Sports MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Stencil', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Stop', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Sylfaen', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Symbol', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Tahoma', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Temp Installer Font', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Tempo Grunge', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Tempus Sans ITC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Times New Roman', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Times New Roman Special', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Traditional Arabic', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Transport MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Trebuchet MS', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Tunga', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Tw Cen MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Tw Cen MT Condensed', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Urdu Typesetting', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Utsaah', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Vacation MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Vani', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Verdana', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Verdana Ref', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Vijaya', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Viner Hand ITC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Vivaldi', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Vixar ASCI', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Vladimir Script', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Vrinda', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Webdings', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Westminster', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Wide Latin', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
(object) [ 'family' => 'Wingdings', 'kind' => 'standard', 'variants' => array(), 'files' => (object) ['regular' => '']],
        );
    }
}