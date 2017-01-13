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

    function fo_websafe_font_names($font) { 
        return str_replace(' ', '+', $font->family); 
    }

    function fo_print_links($fonts, $fonts_per_link = 150){
        if(empty($fonts))
            return;

        // Create list of names with no spaces.
        $font_names = array_map('fo_websafe_font_names', $fonts);

        // Prepare to load the fonts in bulks to improve performance. Cannot include all.
        for ($i=0; $i < count($font_names); $i+=$fonts_per_link) { 
            $calculated_length = count($font_names) - $i > $fonts_per_link ? $fonts_per_link : count($font_names) - $i;
            $font_names_to_load = array_slice($font_names, $i, $calculated_length);
            echo "<link href='http://fonts.googleapis.com/css?family=". implode("|", $font_names_to_load) . "' rel='stylesheet' type='text/css'>";
        }
    }

    function fo_upload_file($uploadedfile, $upload_dir_callback, $should_override = false){
        if ( ! function_exists( 'wp_handle_upload' ) ) 
            require_once( ABSPATH . 'wp-admin/includes/file.php' );

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
                break;
            case 'standard':
                _e('Standard', 'font-organizer');
                break;
            case 'custom':
                _e('Custom', 'font-organizer');
                break;
            case 'earlyaccess':
                _e('Google (Early Access)', 'font-organizer');
                break;
            default:
                _e(ucfirst($kind), 'font-organizer');
                break;
        }
    }

    function fo_enqueue_fonts_css($only_declarations = false){
        global $fo_css_base_url_path;
        global $fo_css_directory_path;
        global $fo_declarations_css_file_name;
        global $fo_elements_css_file_name;

        $declartions_full_file_url = $fo_css_base_url_path . '/' . $fo_declarations_css_file_name;
        if(file_exists($fo_css_directory_path . '/' . $fo_declarations_css_file_name)){
            wp_enqueue_style('fo-fonts-declaration', $declartions_full_file_url);
        }

        if($only_declarations)
            return;

        $elements_full_file_url = $fo_css_base_url_path . '/' . $fo_elements_css_file_name;
        if(file_exists($fo_css_directory_path . '/' . $fo_elements_css_file_name)){
            wp_enqueue_style('fo-fonts-elements', $elements_full_file_url);
        }
    }

    /**
     * Create or override the file given with the content.
     * Create the directory if needed and create or override the file.
     */
    function fo_try_write_file($content, $base_dir, $file_name, $failed_callback){
        if($content){

            // Make sure directory exists.
            if(!is_dir($base_dir))
                 mkdir($base_dir, 0755, true);

            $fhandler = fopen($base_dir . '/' . $file_name, "w");
            if(!$fhandler){
                add_action( 'admin_notices', $failed_callback );
                return false;
            }

            fwrite($fhandler, $content);
            fclose($fhandler);
            return true;
        }

        return false;
    }

    function fo_rearray_files($file){
        $file_ary = array();
        $file_count = count($file['name']);
        $file_key = array_keys($file);
        
        for($i=0;$i<$file_count;$i++)
        {
            foreach($file_key as $val)
            {
                $file_ary[$i][$val] = $file[$val][$i];
            }
        }
        return $file_ary;
    }

    function cmp_font($a, $b){
        $al = strtolower($a->family);
        $bl = strtolower($b->family);
        if ($al == $bl) {
            return 0;
        }
        return ($al > $bl) ? +1 : -1;
    }

    function fo_get_all_http_url($url){
        if ( is_ssl() ) 
            $url = str_replace( 'http://', 'https://', $url ); 
        else 
            $url = str_replace('https://', 'http://', $url);
        return $url;
    }

    function fo_array_sort(&$array){
        return usort($array, 'cmp_font');
    }

    function fo_get_known_fonts_array()
    {
        return array(
(object) array( 'family' => 'Calibri', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Abadi MT Condensed', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Adobe Minion Web', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Agency FB', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Aharoni', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Aldhabi', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Algerian', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Almanac MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'American Uncial', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Andale Mono', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Andalus', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Andy', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Angsana New', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'AngsanaUPC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Aparajita', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Arabic Transparent', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Arabic Typesetting', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Arial', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Arial Black', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Arial Narrow', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Arial Narrow Special', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Arial Rounded MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Arial Special', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Arial Unicode MS', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Augsburger Initials', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Baskerville Old Face', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Batang', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'BatangChe', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Bauhaus 93', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Beesknees ITC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Bell MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Berlin Sans FB', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Bernard MT Condensed', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Bickley Script', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Blackadder ITC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Bodoni MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Bodoni MT Condensed', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Bon Apetit MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Book Antiqua', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Bookman Old Style', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Bookshelf Symbol', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Bradley Hand ITC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Braggadocio', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'BriemScript', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Britannic', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Britannic Bold', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Broadway', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Browallia New', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'BrowalliaUPC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Brush Script MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Calibri', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Californian FB', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Calisto MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Cambria', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Cambria Math', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Candara', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Cariadings', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Castellar', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Centaur', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Century', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Century Gothic', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Century Schoolbook', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Chiller', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Colonna MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Comic Sans MS', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Consolas', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Constantia', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Contemporary Brush', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Cooper Black', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Copperplate Gothic', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Corbel', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Cordia New', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'CordiaUPC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Courier New', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Curlz MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'DaunPenh', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'David', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Desdemona', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'DFKai-SB', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'DilleniaUPC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Directions MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'DokChampa', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Dotum', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'DotumChe', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Ebrima', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Eckmann', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Edda', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Edwardian Script ITC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Elephant', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Engravers MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Enviro', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Eras ITC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Estrangelo Edessa', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'EucrosiaUPC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Euphemia', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Eurostile', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'FangSong', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Felix Titling', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Fine Hand', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Fixed Miriam Transparent', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Flexure', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Footlight MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Forte', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Franklin Gothic', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Franklin Gothic Medium', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'FrankRuehl', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'FreesiaUPC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Freestyle Script', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'French Script MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Futura', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Gabriola', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Gadugi', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Garamond', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Garamond MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Gautami', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Georgia', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Georgia Ref', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Gigi', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Gill Sans MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Gill Sans MT Condensed', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Gisha', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Gloucester', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Goudy Old Style', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Goudy Stout', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Gradl', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Gulim', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'GulimChe', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Gungsuh', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'GungsuhChe', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Haettenschweiler', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Harlow Solid Italic', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Harrington', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'High Tower Text', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Holidays MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Impact', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Imprint MT Shadow', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Informal Roman', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'IrisUPC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Iskoola Pota', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'JasmineUPC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Jokerman', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Juice ITC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'KaiTi', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Kalinga', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Kartika', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Keystrokes MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Khmer UI', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Kino MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'KodchiangUPC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Kokila', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Kristen ITC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Kunstler Script', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Lao UI', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Latha', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'LCD', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Leelawadee', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Levenim MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'LilyUPC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Lucida Blackletter', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Lucida Bright', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Lucida Bright Math', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Lucida Calligraphy', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Lucida Console', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Lucida Fax', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Lucida Handwriting', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Lucida Sans', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Lucida Sans Typewriter', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Lucida Sans Unicode', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Magneto', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Maiandra GD', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Malgun Gothic', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Mangal', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Map Symbols', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Marlett', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Matisse ITC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Matura MT Script Capitals', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'McZee', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Mead Bold', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Meiryo', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Meiryo UI', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Mercurius Script MT Bold', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Microsoft Himalaya', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Microsoft JhengHei', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Microsoft JhengHei UI', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Microsoft New Tai Lue', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Microsoft PhagsPa', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Microsoft Sans Serif', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Microsoft Tai Le', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Microsoft Uighur', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Microsoft YaHei', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Microsoft YaHei UI', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Microsoft Yi Baiti', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'MingLiU', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'MingLiU_HKSCS', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'MingLiU_HKSCS-ExtB', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'MingLiU-ExtB', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Minion Web', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Miriam', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Miriam Fixed', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Mistral', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Modern No. 20', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Mongolian Baiti', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Monotype Corsiva', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Monotype Sorts', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Monotype.com', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'MoolBoran', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'MS Gothic', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'MS LineDraw', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'MS Mincho', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'MS Outlook', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'MS PGothic', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'MS PMincho', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'MS Reference', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'MS UI Gothic', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'MT Extra', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'MV Boli', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Myanmar Text', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Narkisim', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'New Caledonia', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'News Gothic MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Niagara', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Nirmala UI', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'NSimSun', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Nyala', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'OCR A Extended', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'OCRB', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'OCR-B-Digits', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Old English Text MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Onyx', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Palace Script MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Palatino Linotype', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Papyrus', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Parade', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Parchment', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Parties MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Peignot Medium', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Pepita MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Perpetua', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Perpetua Titling MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Placard Condensed', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Plantagenet Cherokee', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Playbill', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'PMingLiU', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'PMingLiU-ExtB', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Poor Richard', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Pristina', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Raavi', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Rage Italic', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Ransom', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Ravie', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'RefSpecialty', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Rockwell', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Rockwell Condensed', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Rockwell Extra Bold', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Rod', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Runic MT Condensed', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Sakkal Majalla', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Script MT Bold', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Segoe Chess', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Segoe Print', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Segoe Pseudo', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Segoe Script', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Segoe UI', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Segoe UI Symbol', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Shonar Bangla', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Showcard Gothic', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Shruti', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Signs MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'SimHei', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Simplified Arabic', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Simplified Arabic Fixed', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'SimSun', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'SimSun-ExtB', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Snap ITC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Sports MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Stencil', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Stop', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Sylfaen', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Symbol', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Tahoma', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Temp Installer Font', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Tempo Grunge', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Tempus Sans ITC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Times New Roman', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Times New Roman Special', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Traditional Arabic', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Transport MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Trebuchet MS', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Tunga', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Tw Cen MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Tw Cen MT Condensed', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Urdu Typesetting', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Utsaah', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Vacation MT', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Vani', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Verdana', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Verdana Ref', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Vijaya', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Viner Hand ITC', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Vivaldi', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Vixar ASCI', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Vladimir Script', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Vrinda', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Webdings', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Westminster', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Wide Latin', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
(object) array( 'family' => 'Wingdings', 'kind' => 'standard', 'variants' => array(), 'files' => (object) array('regular' => '')),
        );
    }
?>