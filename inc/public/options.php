<?php

final class WP_Custom_Sidebars_Options{

	public static $option_key = 'wp_custom_sidebars';
    public static $meta_key = 'wp_custom_sidebars';
    public static $defaults = array(
        'sidebars'  => array( ),
        'support_posttypes' => array( 'page' ),
        'support_taxonomies'  => array( 'category' ),
        'support_sidebars'  => array( 'default' )
    );
    /**
     * Get Options
     * @since 1.0
     */
    static function get_options(){
        $data = get_option( self::$option_key, self::$defaults );
        $data = wp_parse_args( $data, self::$defaults );
        return $data;
    }
    /**
     * Get single option
     * @since 1.0
     */
    static function get_option( $key ){

        if( empty( $key ) )
            return false;

        $data = self::get_options();
        
        if( $key && isset( $data[$key] ) )
            return $data[$key];
    }
    /**
     * Update Option
     * @since 1.0
     */
    static function update_option( $key, $value ){

        if( empty( $key ) )
            return false;

        $data = self::get_options();

        // if is string, just trim it
        $data[$key] = is_string( $value ) ? trim( $value ) : $value;

        /*if( !empty( $value ) ){
            $data[$key] = trim( $value );    
        }else{
            unset( $data[$key] );
        }*/
        
        update_option( self::$option_key, $data );

    }
    /**
     * Get post meta data
     * @since 1.0
     */
    static function get_post_meta( $post_id = '' ){

        if( empty( $post_id ) )
            $post_id = $GLOBALS['post']->ID;

        $meta = get_post_meta( $post_id, self::$meta_key, true );

        return $meta;
    }
    /**
     * Get Term meta data
     * @since 1.0.1
     */
    static function get_term_meta( $term_id = 0 ){

        return get_term_meta( $term_id, self::$meta_key, true );
    }
	
}