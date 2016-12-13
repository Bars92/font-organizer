<?php
defined( 'ABSPATH' ) or die( 'Jog on!' );

/**
 * This class is designed to get information from the database using simple methods in order
 * to save the data for same requests for cache purposes.
 */
class FontsDatabaseHelper {
	private static $usable_fonts;
	private static $custom_elements;

	public static function get_usable_fonts(){
		
		// If usable fonts is not yet set, get it from the database.
		if(!self::$usable_fonts){
 			global $wpdb;

        	self::$usable_fonts = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . FO_USABLE_FONTS_DATABASE . ' ORDER BY id DESC');
		}

		return self::$usable_fonts;
	}

	public static function get_custom_elements(){
		
		// If usable fonts is not yet set, get it from the database.
		if(!self::$custom_elements){
 			global $wpdb;

        	self::$custom_elements = $wpdb->get_results('SELECT e.id, u.name, e.font_id, e.custom_elements, e.important FROM ' . $wpdb->prefix . FO_ELEMENTS_DATABASE . ' as e LEFT OUTER JOIN ' . $wpdb->prefix . FO_USABLE_FONTS_DATABASE . ' as u ON ' . ' e.font_id = u.id ORDER BY e.font_id DESC');
		}

		return self::$custom_elements;
	}
}
?>
