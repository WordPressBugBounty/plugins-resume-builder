<?php

class Resume_Builder_Readme_Parser {

    public $name = '';
    public $tags = array();
    public $requires = '';
    public $tested = '';
    public $requires_php = '';
    public $contributors = array();
    public $stable_tag = '';
    public $donate_link = '';
    public $short_description = '';
    public $license = '';
    public $license_uri = '';
    public $sections = array();
    public $upgrade_notice = array();
    public $screenshots = array();
    public $faq = array();
    public $warnings = array();

    public $expected_sections = array(
        'description',
        'installation',
        'faq',
        'screenshots',
        'changelog',
        'upgrade_notice',
        'other_notes',
    );
    
    public $alias_sections = array(
        'frequently_asked_questions' => 'faq',
        'change_log'                 => 'changelog',
        'screenshot'                 => 'screenshots',
    );

    public $valid_headers = array(
        'tested'            => 'tested',
        'tested up to'      => 'tested',
        'requires'          => 'requires',
        'requires at least' => 'requires',
        'requires php'      => 'requires_php',
        'tags'              => 'tags',
        'contributors'      => 'contributors',
        'donate link'       => 'donate_link',
        'stable tag'        => 'stable_tag',
        'license'           => 'license',
        'license uri'       => 'license_uri',
    );

    public $ignore_tags = array(
        'plugin',
        'wordpress',
    );

    public $maximum_field_lengths = array(
        'short_description' => 150,
        'section'           => 2500,
        'section-changelog' => 5000,
        'section-faq'       => 5000,
    );

    public $raw_contents = '';

    public function __construct( $string = '' ) {
        if (
            (
                strlen( $string ) <= PHP_MAXPATHLEN
                && false === strpos( $string, "\n" )
                && file_exists( $string )
            )
            || preg_match( '!^https?://!i', $string ) 
            || preg_match( '!^data:text/plain!i', $string) ) 
        {
            $this->parse_readme( $string );
        } elseif ( $string ) {
            $this->parse_readme_contents( $string );
        }
    }

    protected function parse_readme( $file_or_url ) {
        $context = stream_context_create( array(
            'http' => array(
                'user_agent' => 'WordPress.org Plugin Readme Parser',
            )
        ) );

        $contents = file_get_contents( $file_or_url, false, $context );

        return $this->parse_readme_contents( $contents );
    }

    protected function parse_readme_contents( $contents ) {
        $this->raw_contents = $contents;

        if ( preg_match( '!!u', $contents ) ) {
            $contents = preg_split( '!\R!u', $contents );
        } else {
            $contents = preg_split( '!\R!', $contents );
        }
        $contents = array_map( array( $this, 'strip_newlines' ), $contents );

        if ( 0 === strpos( $contents[0], "\xEF\xBB\xBF" ) ) {
            $contents[0] = substr( $contents[0], 3 );
        }

        if ( 0 === strpos( $contents[0], "\xFF\xFE" ) ) {
            foreach ( $contents as $i => $line ) {
                $contents[ $i ] = mb_convert_encoding( $line, 'UTF-8', 'UTF-16' );
            }
        }

        $line       = $this->get_first_nonwhitespace( $contents );
        $this->name = $this->sanitize_text( trim( $line, "#= \t\0\x0B" ) );

        if ( $this->parse_possible_header( $line, true ) ) {
            array_unshift( $contents, $line );

            $this->warnings['invalid_plugin_name_header'] = true;
            $this->name                                   = false;
        }

        if ( ! empty( $contents ) && '' === trim( $contents[0], '=-' ) ) {
            array_shift( $contents );
        }

        if ( 'plugin name' == strtolower( $this->name ) ) {
            $this->warnings['invalid_plugin_name_header'] = true;

            $this->name = false;
            $line       = $this->get_first_nonwhitespace( $contents );

            if ( strlen( $line ) < 50 && ! $this->parse_possible_header( $line, true /* only valid headers */ ) ) {
                $this->name = $this->sanitize_text( trim( $line, "#= \t\0\x0B" ) );
            } else {
                array_unshift( $contents, $line );
            }
        }

        $headers = array();

        $line                = $this->get_first_nonwhitespace( $contents );
        $last_line_was_blank = false;
        do {
            $value  = null;
            $header = $this->parse_possible_header( $line );

            if ( ! $header ) {
                if ( empty( $line ) ) {
                    $last_line_was_blank = true;
                    continue;
                } else {
                    break;
                }
            }

            list( $key, $value ) = $header;

            if ( isset( $this->valid_headers[ $key ] ) ) {
                $headers[ $this->valid_headers[ $key ] ] = $value;
            } elseif ( $last_line_was_blank ) {
                break;
            }

            $last_line_was_blank = false;
        } while ( ( $line = array_shift( $contents ) ) !== null );
        array_unshift( $contents, $line );

        if ( ! empty( $headers['tags'] ) ) {
            $this->tags = explode( ',', $headers['tags'] );
            $this->tags = array_map( 'trim', $this->tags );
            $this->tags = array_filter( $this->tags );

            if ( array_intersect( $this->tags, $this->ignore_tags ) ) {
                $this->warnings['ignored_tags'] = array_intersect( $this->tags, $this->ignore_tags );
                $this->tags                     = array_diff( $this->tags, $this->ignore_tags );
            }

            if ( count( $this->tags ) > 5 ) {
                $this->warnings['too_many_tags'] = array_slice( $this->tags, 5 );
                $this->tags                      = array_slice( $this->tags, 0, 5 );
            }
        }
        if ( ! empty( $headers['requires'] ) ) {
            $this->requires = $this->sanitize_requires_version( $headers['requires'] );
        }
        if ( ! empty( $headers['tested'] ) ) {
            $this->tested = $this->sanitize_tested_version( $headers['tested'] );
        }
        if ( ! empty( $headers['requires_php'] ) ) {
            $this->requires_php = $this->sanitize_requires_php( $headers['requires_php'] );
        }
        if ( ! empty( $headers['contributors'] ) ) {
            $this->contributors = explode( ',', $headers['contributors'] );
            $this->contributors = array_map( 'trim', $this->contributors );
            $this->contributors = $this->sanitize_contributors( $this->contributors );
        }
        if ( ! empty( $headers['stable_tag'] ) ) {
            $this->stable_tag = $this->sanitize_stable_tag( $headers['stable_tag'] );
        }
        if ( ! empty( $headers['donate_link'] ) ) {
            $this->donate_link = $headers['donate_link'];
        }
        if ( ! empty( $headers['license'] ) ) {
            if ( empty( $headers['license_uri'] ) && preg_match( '!(https?://\S+)!i', $headers['license'], $url ) ) {
                $headers['license_uri'] = $url[1];
                $headers['license']     = trim( str_replace( $url[1], '', $headers['license'] ), " -*\t\n\r\n" );
            }
            $this->license = $headers['license'];
        }
        if ( ! empty( $headers['license_uri'] ) ) {
            $this->license_uri = $headers['license_uri'];
        }

        while ( ( $line = array_shift( $contents ) ) !== null ) {
            $trimmed = trim( $line );
            if ( empty( $trimmed ) ) {
                $this->short_description .= "\n";
                continue;
            }
            if ( ( '=' === $trimmed[0] && isset( $trimmed[1] ) && '=' === $trimmed[1] ) ||
                 ( '#' === $trimmed[0] && isset( $trimmed[1] ) && '#' === $trimmed[1] )
            ) {
                array_unshift( $contents, $line );
                break;
            }

            $this->short_description .= $line . "\n";
        }
        $this->short_description = trim( $this->short_description );

        $this->sections = array_fill_keys( $this->expected_sections, '' );
        $current        = $section_name = $section_title = '';
        while ( ( $line = array_shift( $contents ) ) !== null ) {
            $trimmed = trim( $line );
            if ( empty( $trimmed ) ) {
                $current .= "\n";
                continue;
            }

            if ( ( '=' === $trimmed[0] && isset( $trimmed[1] ) && '=' === $trimmed[1] ) ||
                 ( '#' === $trimmed[0] && isset( $trimmed[1] ) && '#' === $trimmed[1] && isset( $trimmed[2] ) && '#' !== $trimmed[2] )
            ) {

                if ( ! empty( $section_name ) ) {
                    $this->sections[ $section_name ] .= trim( $current );
                }

                $current       = '';
                $section_title = trim( $line, "#= \t" );
                $section_name  = strtolower( str_replace( ' ', '_', $section_title ) );

                if ( isset( $this->alias_sections[ $section_name ] ) ) {
                    $section_name = $this->alias_sections[ $section_name ];
                }

                if ( ! in_array( $section_name, $this->expected_sections ) ) {
                    $current     .= '<h3>' . $section_title . '</h3>';
                    $section_name = 'other_notes';
                }
                continue;
            }

            $current .= $line . "\n";
        }

        if ( ! empty( $section_name ) ) {
            $this->sections[ $section_name ] .= trim( $current );
        }

        $this->sections = array_filter( $this->sections );

        if ( empty( $this->sections['description'] ) ) {
            $this->sections['description'] = $this->short_description;
        }

        if ( ! empty( $this->sections['other_notes'] ) ) {
            $this->sections['description'] .= "\n" . $this->sections['other_notes'];
            unset( $this->sections['other_notes'] );
        }

        if ( isset( $this->sections['upgrade_notice'] ) ) {
            $this->upgrade_notice = $this->parse_section( $this->sections['upgrade_notice'] );
            $this->upgrade_notice = array_map( array( $this, 'sanitize_text' ), $this->upgrade_notice );
            unset( $this->sections['upgrade_notice'] );
        }

        foreach ( $this->sections as $section => $content ) {
            $max_length = "section-{$section}";
            if ( ! isset( $this->maximum_field_lengths[ $max_length ] ) ) {
                $max_length = 'section';
            }

            $this->sections[ $section ] = $this->trim_length( $content, $max_length, 'words' );

            if ( $content !== $this->sections[ $section ] ) {
                $this->warnings["trimmed_section_{$section}"] = true;
            }
        }

        if ( isset( $this->sections['faq'] ) ) {
            $this->faq             = $this->parse_section( $this->sections['faq'] );
            $this->sections['faq'] = '';
        }
        
        if ( isset( $this->sections['changelog'] ) ) {
            $this->changelog = $this->parse_section( $this->sections['changelog'] );
            $this->sections['changelog'] = '';
        }

        $this->sections       = array_map( array( $this, 'parse_markdown' ), $this->sections );
        $this->upgrade_notice = array_map( array( $this, 'parse_markdown' ), $this->upgrade_notice );
        $this->faq            = array_map( array( $this, 'parse_markdown' ), $this->faq );
        $this->changelog      = array_map( array( $this, 'parse_markdown' ), $this->changelog );
        
        foreach( $this->changelog as $version => $changes_string ){
            $changes_string = explode( '* ', str_replace( '**', '', $changes_string ) );
            if ( !normalize_whitespace( $changes_string[0] ) ){
                unset( $changes_string[0] );
                $changes_string = array_values( $changes_string );
            }
            $this->changelog[$version] = array_map( array( $this, 'parse_markdown' ), $changes_string );
        }

        if ( ! $this->short_description && ! empty( $this->sections['description'] ) ) {
            $this->short_description = array_filter( explode( "\n", $this->sections['description'] ) )[0];
            $this->warnings['no_short_description_present'] = true;
        }

        $this->short_description = $this->sanitize_text( $this->short_description );
        $this->short_description = $this->parse_markdown( $this->short_description );
        $this->short_description = wp_strip_all_tags( $this->short_description );
        $short_description       = $this->trim_length( $this->short_description, 'short_description' );
        if ( $short_description !== $this->short_description ) {
            if ( empty( $this->warnings['no_short_description_present'] ) ) {
                $this->warnings['trimmed_short_description'] = true;
            }
            $this->short_description = $short_description;
        }

        if ( isset( $this->sections['screenshots'] ) ) {
            preg_match_all( '#<li>(.*?)</li>#is', $this->sections['screenshots'], $screenshots, PREG_SET_ORDER );
            if ( $screenshots ) {
                $i = 1;
                foreach ( $screenshots as $ss ) {
                    $this->screenshots[ $i++ ] = $this->filter_text( $ss[1] );
                }
            }
            unset( $this->sections['screenshots'] );
        }

        if ( ! empty( $this->faq ) ) {
            if ( isset( $this->faq[''] ) ) {
                $this->sections['faq'] .= $this->faq[''];
                unset( $this->faq[''] );
            }

            if ( $this->faq ) {
                $this->sections['faq'] .= "\n<dl>\n";
                foreach ( $this->faq as $question => $answer ) {
                    $question_slug          = rawurlencode( trim( strtolower( $question ) ) );
                    $this->sections['faq'] .= "<dt id='{$question_slug}'><h3>{$question}</h3></dt>\n<dd>{$answer}</dd>\n";
                }
                $this->sections['faq'] .= "\n</dl>\n";
            }
        }

        $this->sections = array_map( array( $this, 'filter_text' ), $this->sections );

        return true;
    }

    protected function get_first_nonwhitespace( &$contents ) {
        while ( ( $line = array_shift( $contents ) ) !== null ) {
            $trimmed = trim( $line );
            if ( ! empty( $trimmed ) ) {
                break;
            }
        }

        return $line ?? '';
    }

    protected function strip_newlines( $line ) {
        return rtrim( $line, "\r\n" );
    }

    protected function trim_length( $desc, $length = 150, $type = 'char' ) {
        if ( is_string( $length ) ) {
            $length = $this->maximum_field_lengths[ $length ] ?? $length;
        }

        if ( 'words' === $type ) {
            $pieces = @preg_split( '/(\s+)/u', $desc, -1, PREG_SPLIT_DELIM_CAPTURE );

            if ( $pieces === false ) {
                $pieces = preg_split( '/(\s+)/', $desc, -1, PREG_SPLIT_DELIM_CAPTURE );
            }

            $word_count_with_spaces = $length * 2;

            if ( count( $pieces ) < $word_count_with_spaces ) {
                return $desc;
            }

            $pieces = array_slice( $pieces, 0, $word_count_with_spaces );

            return implode( '', $pieces ) . ' &hellip;';
        }

        $str_length = mb_strlen( html_entity_decode( $desc ) ?: $desc );

        if ( $str_length > $length ) {
            $desc = mb_substr( $desc, 0, $length );

            if ( '.' !== mb_substr( $desc, -1 ) ) {
                if ( ( $pos = mb_strrpos( $desc, '.' ) ) > ( 0.8 * $length ) ) {
                    $desc = mb_substr( $desc, 0, $pos + 1 );
                } else {
                    $desc .= ' &hellip;';
                }
            }
        }

        return trim( $desc );
    }

    protected function parse_possible_header( $line, $only_valid = false ) {
        if ( ! str_contains( $line, ':' ) || str_starts_with( $line, '#' ) || str_starts_with( $line, '=' ) ) {
            return false;
        }

        list( $key, $value ) = explode( ':', $line, 2 );
        $key                 = strtolower( trim( $key, " \t*-\r\n" ) );
        $value               = trim( $value, " \t*-\r\n" );

        if ( $only_valid && ! isset( $this->valid_headers[ $key ] ) ) {
            return false;
        }

        return [ $key, $value ];
    }

    protected function filter_text( $text ) {
        $text = trim( $text );

        $allowed = array(
            'a'          => array(
                'href'  => true,
                'title' => true,
                'rel'   => true,
            ),
            'blockquote' => array(
                'cite' => true,
            ),
            'br'         => array(),
            'p'          => array(),
            'code'       => array(),
            'pre'        => array(),
            'em'         => array(),
            'strong'     => array(),
            'ul'         => array(),
            'ol'         => array(),
            'dl'         => array(),
            'dt'         => array(
                'id' => true,
            ),
            'dd'         => array(),
            'li'         => array(),
            'h3'         => array(),
            'h4'         => array(),
        );

        $text = force_balance_tags( $text );
        $text = wp_kses( $text, $allowed );
        $text = trim( $text );
        return $text;
    }

    protected function sanitize_text( $text ) {
        $text = strip_tags( $text );
        $text = esc_html( $text );
        $text = trim( $text );
        return $text;
    }

    protected function sanitize_contributors( $users ) {
        foreach ( $users as $i => $name ) {
            $name = ltrim( $name, '@' );

            $user = get_user_by( 'login', $name );

            if ( ! $user ) {
                $user = get_user_by( 'slug', $name );
            }

            if ( ! $user ) {
                $this->warnings['contributor_ignored'] ??= [];
                $this->warnings['contributor_ignored'][] = $name;
                unset( $users[ $i ] );
                continue;
            }

            $users[ $i ] = $user->user_nicename;
        }
        return $users;
    }

    protected function sanitize_stable_tag( $stable_tag ) {
        $stable_tag = trim( $stable_tag );
        $stable_tag = trim( $stable_tag, '"\'' ); // "trunk"
        $stable_tag = preg_replace( '!^/?tags/!i', '', $stable_tag ); // "tags/1.2.3"
        $stable_tag = preg_replace( '![^a-z0-9_.-]!i', '', $stable_tag );

        if ( '.' == substr( $stable_tag, 0, 1 ) ) {
            $stable_tag = "0{$stable_tag}";
        }

        return $stable_tag;
    }

    protected function sanitize_requires_php( $version ) {
        $version = trim( $version );

        // x.y or x.y.z
        if ( $version && ! preg_match( '!^\d+(\.\d+){1,2}$!', $version ) ) {
            $this->warnings['requires_php_header_ignored'] = true;
            // Ignore the readme value.
            $version = '';
        }

        return $version;
    }

    protected function sanitize_tested_version( $version ) {
        $version = trim( $version );

        if ( $version ) {

            $strip_phrases = [
                'WordPress',
                'WP',
            ];
            $version = trim( str_ireplace( $strip_phrases, '', $version ) );

            list( $version, ) = explode( '-', $version );

            if (
                ! preg_match( '!^\d+\.\d(\.\d+)?$!', $version ) ||
                (
                    defined( 'WP_CORE_STABLE_BRANCH' ) &&
                    version_compare( (float)$version, (float)WP_CORE_STABLE_BRANCH+0.1, '>' )
                )
             ) {
                $this->warnings['tested_header_ignored'] = true;
                $version = '';
            }
        }

        return $version;
    }

    protected function sanitize_requires_version( $version ) {
        $version = trim( $version );

        if ( $version ) {

            $strip_phrases = [
                'WordPress',
                'WP',
                'or higher',
                'and above',
                '+',
            ];
            $version = trim( str_ireplace( $strip_phrases, '', $version ) );

            list( $version, ) = explode( '-', $version );

            if (
                ! preg_match( '!^\d+\.\d(\.\d+)?$!', $version ) ||
                defined( 'WP_CORE_STABLE_BRANCH' ) && ( (float)$version > (float)WP_CORE_STABLE_BRANCH+0.1 )
             ) {
                $this->warnings['requires_header_ignored'] = true;
                $version = '';
            }
        }

        return $version;
    }

    protected function parse_section( $lines ) {
        $key    = $value = '';
        $return = array();

        if ( ! is_array( $lines ) ) {
            $lines = explode( "\n", $lines );
        }
        $trimmed_lines = array_map( 'trim', $lines );

        $heading_style = 'bold';
        foreach ( $trimmed_lines as $trimmed ) {
            if ( $trimmed && ( $trimmed[0] == '#' || $trimmed[0] == '=' ) ) {
                $heading_style = 'heading';
                break;
            }
        }

        $line_count = count( $lines );
        for ( $i = 0; $i < $line_count; $i++ ) {
            $line    = &$lines[ $i ];
            $trimmed = &$trimmed_lines[ $i ];
            if ( ! $trimmed ) {
                $value .= "\n";
                continue;
            }

            $is_heading = false;
            if ( 'heading' == $heading_style && ( $trimmed[0] == '#' || $trimmed[0] == '=' ) ) {
                $is_heading = true;
            } elseif ( 'bold' == $heading_style && ( substr( $trimmed, 0, 2 ) == '**' && substr( $trimmed, -2 ) == '**' ) ) {
                $is_heading = true;
            }

            if ( $is_heading ) {
                if ( $value ) {
                    $return[ $key ] = trim( $value );
                }

                $value = '';
                $key = trim( $line, $trimmed[0] . " \t" );
                continue;
            }

            $value .= $line . "\n";
        }

        if ( $key || $value ) {
            $return[ $key ] = trim( $value );
        }

        return $return;
    }

    protected function parse_markdown( $text ) {
        static $markdown = null;

        if ( ! class_exists( '\WordPressdotorg\Plugin_Directory\Markdown' ) ) {
            return $text;
        }

        if ( is_null( $markdown ) ) {
            $markdown = new Markdown();
        }

        return $markdown->transform( $text );
    }

}
