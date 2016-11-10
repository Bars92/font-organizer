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

    function fo_print_links($fonts, $fonts_per_link = 150){
        if(empty($fonts))
            return;

        // Create list of names with no spaces.
        $font_names = array_map(function($font) { return str_replace(' ', '+', $font->family); }, $fonts);

        // Prepare to load the fonts in bulks to improve performance. Cannot include all.
        for ($i=0; $i < count($font_names); $i+=$fonts_per_link) { 
            $calculated_length = count($font_names) - $i > $fonts_per_link ? $fonts_per_link : count($font_names) - $i;
            $font_names_to_load = array_slice($font_names, $i, $calculated_length);
            echo "<link href='http://fonts.googleapis.com/css?family=". implode("|", $font_names_to_load) . "' rel='stylesheet' type='text/css'>";
        }
    }

    function fo_upload_file($file, $upload_dir_callback, $should_override = false){
        if ( ! function_exists( 'wp_handle_upload' ) ) 
            require_once( ABSPATH . 'wp-admin/includes/file.php' );

        $uploadedfile = $args['font_file'];

        $upload_overrides = array( 'test_form' => false );
        if($should_override){
            $upload_overrides['unique_filename_callback'] = 'fo_unique_filename_callback';
        }
        // Register our path override.
        add_filter( 'upload_dir', $upload_dir_callback );

        $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

        // Set everything back to normal.
        remove_filter( 'upload_dir', $upload_dir_callback );

        return $movefile;
    }

    function fo_unique_filename_callback($dir, $name, $ext){
        return $name.$ext;
    }

    function fo_get_font_format($url){
        $extension = pathinfo($url, PATHINFO_EXTENSION);
        switch ($extension) {
            case 'ttf':
                return 'truetype';                
            default:
                return $extension;
        }
    }

    function fo_print_source($kind){
        switch ($kind) {
            case 'webfonts#webfont':
                _e('Google', 'font-organizer');
                return;
            case 'standard':
                _e('Standard', 'font-organizer');
            case 'custom':
                _e('Custom', 'font-organizer');
            default:
                _e(ucfirst($kind), 'font-organizer');
                break;
        }
    }
?>