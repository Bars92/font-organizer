<?php
/**
 * @package Font_Organizer
 * @version 1.0.0
 */
/*
Plugin Name: Font Organizer
Plugin URI: https://wordpress.org/plugins/font-organizer/
Description: afsaf.
Author: HiveTeam
Version: 1.0.0
Author URI: 
*/

define( 'FO_ABSPATH', plugin_dir_path( __FILE__ ) );
define( 'FO_USABLE_FONTS_DATABASE', 'fo_usable_fonts' );
define( 'FO_ELEMENTS_DATABASE', 'fo_elements' );
define( 'FO_DEFAULT_ROLE', 'administrator' );

global $fo_db_version;
$fo_db_version = '1.0.0';
global $css_full_file_path;
global $css_full_url_path;
global $css_directory_path;
$css_full_file_path = wp_upload_dir()['basedir'] . '/font-organizer' . '/fo-fonts.css';
$css_full_url_path = wp_upload_dir()['baseurl'] . '/font-organizer' . '/fo-fonts.css';
$css_directory_path =  wp_upload_dir()['basedir'] . '/font-organizer';

function fo_update_db_check() {
    global $fo_db_version;
    if ( get_site_option( 'fo_db_version' ) != $fo_db_version ) {
        fo_install();
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
	global $css_full_file_path;

	if( is_admin() ){
		add_filter('upload_mimes', 'fo_allow_upload_types');
		add_filter( 'plugin_action_links', 'fo_add_action_plugin', 10, 5 );
		
		include FO_ABSPATH . 'helpers.php';

		include FO_ABSPATH . 'settings.php'; 

	    $settings_page = new FoSettingsPage();
	}else{
		if(file_exists($css_full_file_path)){
			add_action( 'wp_enqueue_scripts', 'fo_enqueue_fonts_css' );
		}
	}
}

function fo_enqueue_fonts_css(){
	global $css_full_url_path;
	wp_enqueue_style('fo-fonts', $css_full_url_path);
}

function fo_allow_upload_types($existing_mimes = array()){
	$existing_mimes['ttf'] = 'application/octet-stream';
	$existing_mimes['woff'] = 'application/x-font-woff';
	$existing_mimes['woff2'] = 'application/x-font-woff';
	$existing_mimes['otf'] = 'application/x-font-woff';

	return $existing_mimes;
}

function fo_uninstall(){
	$roles = wp_roles();

	// Remove all capabilities added by this plugin.
	foreach ($roles as $role_name => $role) {
		if(array_key_exists('manage_fonts', $role['capabilities']) && $role['capabilities']['manage_fonts'])
			 $roles->remove_cap( $role_name, 'manage_fonts' ); 
	}
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
	add_option( 'fo_db_version', $fo_db_version );

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