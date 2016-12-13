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
            wp_enqueue_style('fo-fonts', $declartions_full_file_url);
        }

        if($only_declarations)
            return;

        $elements_full_file_url = $fo_css_base_url_path . '/' . $fo_elements_css_file_name;
        if(file_exists($fo_css_directory_path . '/' . $fo_elements_css_file_name)){
            wp_enqueue_style('fo-fonts', $elements_full_file_url);
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

    function fo_array_sort(&$array){
        return usort($array, 'cmp_font');
    }

    function fo_get_known_fonts_array()
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
?>