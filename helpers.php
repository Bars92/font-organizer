<?php 
    function fo_do_settings_section($page, $section_name){
        global $wp_settings_sections, $wp_settings_fields;
    
        if ( ! isset( $wp_settings_sections[$page] ) )
            return;
    
        foreach ( (array) $wp_settings_sections[$page] as $section_from_page ) {
            if($section_name !== $section_from_page['id'])
                continue;

            if ( $section_from_page['title'] )
                echo "<h2>{$section_from_page['title']}</h2>\n";
    
            if ( $section_from_page['callback'] )
                call_user_func( $section_from_page['callback'], $section_from_page );
    
            if ( ! isset( $wp_settings_fields ) || !isset( $wp_settings_fields[$page] ) || !isset( $wp_settings_fields[$page][$section_from_page['id']] ) )
                continue;
                
            echo '<table class="form-table">';
            do_settings_fields( $page, $section_from_page['id'] );
            echo '</table>';
        }
    }
?>