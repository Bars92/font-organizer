<?php
/**
 * @package Font_Organizer
 * @version 1.3.2
 */
/*
Plugin Name: Font Organizer
Plugin URI: https://wordpress.org/plugins/font-organizer/
Description: Font Organizer is the complete solution for font implementation in WordPress websites.
Author: Hive
Version: 1.3.2
Author URI: https://hivewebstudios.com
Text Domain: font-organizer
*/

define( 'FO_ABSPATH', plugin_dir_path( __FILE__ ) );
define( 'FO_USABLE_FONTS_DATABASE', 'fo_usable_fonts' );
define( 'FO_ELEMENTS_DATABASE', 'fo_elements' );
define( 'FO_DEFAULT_ROLE', 'administrator' );

require_once FO_ABSPATH . 'helpers/helpers.php';

global $fo_db_version;
$fo_db_version = '1.3.2';

$upload_dir = wp_upload_dir(); // Must create a temp variable for PHP 5.3.
global $fo_css_directory_path;
$fo_css_directory_path =  $upload_dir['basedir'] . '/font-organizer';

global $fo_css_base_url_path;
$fo_css_base_url_path = $upload_dir['baseurl'] . '/font-organizer';

// Fix ssl for base url.
$fo_css_base_url_path = fo_get_all_http_url( $fo_css_base_url_path ); 

global $fo_declarations_css_file_name;
$fo_declarations_css_file_name = 'fo-declarations.css';

global $fo_elements_css_file_name;
$fo_elements_css_file_name = 'fo-elements.css';

function fo_update_db_check() {
    global $fo_db_version;
    if ( get_site_option( 'fo_db_version' ) != $fo_db_version ) {
        fo_install();

        // As of 1.2 we split the css file to declartions and elements.
        // Create the files and delete the old fo-fonts.css.
        global $fo_css_directory_path;

		require_once FO_ABSPATH . 'settings.php'; 

	    $settings_page = new FoSettingsPage();
	    $settings_page->init();
	    $settings_page->create_css_file(true);

	    // Delete the old file.
	    if(file_exists($fo_css_directory_path . '/fo-fonts.css'))
	    	unlink($fo_css_directory_path . '/fo-fonts.css');

    }
}

add_action( 'plugins_loaded', 'fo_update_db_check' );
register_activation_hook( __FILE__, 'fo_install' );
register_deactivation_hook( __FILE__, 'fo_uninstall' );
add_action( 'init', 'fo_init' );
add_action('plugins_loaded', 'fo_load_textdomain');

function fo_load_textdomain() {
	load_plugin_textdomain( 'font-organizer', false, dirname( plugin_basename(__FILE__) ) . '/languages/' );
}

function fo_init(){

	if( is_admin() ){
		require_once FO_ABSPATH . 'settings.php'; 

		// Add the declarations to the editor, so in preview you can see
		// the selected font family.
	    add_editor_style( '../../uploads/font-organizer/fo-declarations.css' );

		add_filter( 'upload_mimes', 'fo_allow_upload_types' );
		add_filter( 'plugin_action_links', 'fo_add_action_plugin', 10, 5 );
		add_filter( 'tiny_mce_before_init', 'fo_add_tinymce_fonts' );
		add_filter( 'mce_buttons_2', 'fo_mce_buttons' );
		add_action( 'admin_enqueue_scripts', 'fo_enqueue_declarations_fonts_css' );

	    $settings_page = new FoSettingsPage();
	}else{
		add_action( 'wp_enqueue_scripts', 'fo_enqueue_all_fonts_css' );
	}
}

function fo_enqueue_all_fonts_css(){
	fo_enqueue_fonts_css();
}

function fo_enqueue_declarations_fonts_css(){
	fo_enqueue_fonts_css(true);
}

// Enable font size & font family selects in the editor
function fo_mce_buttons( $buttons ) {
	array_unshift( $buttons, 'fontselect' ); // Add Font Select
	array_unshift( $buttons, 'fontsizeselect' ); // Add Font Size Select
	return $buttons;
}

function fo_add_tinymce_fonts($initArray){
	$usable_fonts = FontsDatabaseHelper::get_usable_fonts();
	$font_formats = array();
	foreach ($usable_fonts as $font) {
		$font_formats[] = $font->name . '=' . $font->name;
	}

	// Set the font families from the usable fonts list.
	$initArray['font_formats'] = implode(';', $font_formats);

	// Apply the filter to allow quick change in the font sizes list in tinymce editors.
	// The input is a string of the default standart font sizes spereated by spaces (' ').
	$sizes = apply_filters('fo_tinyme_font_sizes', "8px 10px 12px 14px 16px 20px 24px 28px 32px 36px 48px 60px");

	// Set font sizes.
	$initArray['fontsize_formats'] = $sizes;
	return $initArray;
}

function fo_allow_upload_types($existing_mimes = array()){
	$existing_mimes['ttf'] = 'application/octet-stream';
	$existing_mimes['eot'] = 'application/octet-stream';
	$existing_mimes['woff'] = 'application/x-font-woff';
	$existing_mimes['woff2'] = 'application/x-font-woff';
	$existing_mimes['otf'] = 'application/x-font-woff';

	return $existing_mimes;
}

function fo_uninstall(){
	// This is disabled and may move to permanent unistall option.
	//$roles = wp_roles();

	// Remove all capabilities added by this plugin.
	//foreach ($roles as $role_name => $role) {
	//	if(array_key_exists('manage_fonts', $role['capabilities']) && $role['capabilities']['manage_fonts'])
	//		 $roles->remove_cap( $role_name, 'manage_fonts' ); 
	//}
}

function fo_install() {
	global $wpdb;
	global $fo_db_version;

	$usable_table_name = $wpdb->prefix . FO_USABLE_FONTS_DATABASE;
	$elements_table_name = $wpdb->prefix . FO_ELEMENTS_DATABASE;
	
	$charset_collate = $wpdb->get_charset_collate();

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	$sql = "CREATE TABLE IF NOT EXISTS $usable_table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL,
		url text DEFAULT NULL,
		custom int(1) DEFAULT 0,
		PRIMARY KEY  (id)
	) $charset_collate;";

	dbDelta( $sql );
	
	$sql = "CREATE TABLE IF NOT EXISTS $elements_table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		font_id mediumint(9) NOT NULL,
		important int(1) DEFAULT 0,
		custom_elements TEXT DEFAULT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	dbDelta( $sql );

	// Set the db version to current.
	update_option( 'fo_db_version', $fo_db_version );

	// Set roles
	$role = get_role( 'administrator' );
	if(!$role->has_cap('manage_fonts'))
	 	$role->add_cap( 'manage_fonts' );
}

function fo_add_action_plugin( $actions, $plugin_file ) {
	static $plugin;

	if (!isset($plugin))
		$plugin = plugin_basename(__FILE__);

	if ($plugin == $plugin_file) {

		$settings = array('settings' => '<a href="options-general.php?page=font-setting-admin">' . __('Font Settings', 'font-organizer') . '</a>');
    	$actions = array_merge($settings, $actions);

	}
		
	return $actions;
}
?>