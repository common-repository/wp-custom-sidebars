<?php

final class WP_Custom_Sidebars_Main{

	/**
     * Constructor
     *
     * @since 1.0.0
     */
	public function __construct(){

		add_action( 'widgets_init', array( $this, 'register_sidebars' ), 150 );				
		add_action( 'wp_head', array( $this, 'replace_sidebar' ), 150 );
	}
    /**
     * Register custom sidebars
     *
     * @since 1.0.0
     */
    public function register_sidebars(){

        $sidebars = WP_Custom_Sidebars_Options::get_option( 'sidebars' );

        if( empty( $sidebars ) )
            return;

        foreach ( ( array ) $sidebars as $sidebar_id => $sidebar_name ) {
            register_sidebar(
                array(
                    'name'              => $sidebar_name,
                    'id'                => $sidebar_id,
                    'before_title'      => apply_filters( 'wpcs_sidebar_before_title', '<div class="widget-title-wrapper w-t-w"><h3 class="widget-title w-t"><span>' ),
                    'after_title'       => apply_filters( 'wpcs_sidebar_after_title', '</span></h3></div>' ),
                    'before_widget'     => apply_filters( 'wpcs_sidebar_before_widget', '<aside id="%1$s" class="widget w %2$s">' ),
                    'after_widget'      => apply_filters( 'wpcs_sidebar_after_widget', '</aside><!--/.widget-->' )
                )
            );  
        }
    }
    /**
     * Replace sidebars
     *
     * @since 1.0.0
     */
    public function replace_sidebar(){

    	$supported_posttypes = WP_Custom_Sidebars_Options::get_option( 'support_posttypes' );

    	global $post;
        $context = false;
        $meta = array();

        // Posts page
        if( is_home() && ( $pfp_id = get_option( 'page_for_posts' ) ) ){
            $context = 'posts_page';
            $meta = WP_Custom_Sidebars_Options::get_post_meta( $pfp_id );
        }
        // Singular posts
        else if( is_singular() && !empty( $post->post_type ) && in_array( $post->post_type, $supported_posttypes ) ){
            $post_id = $post->ID;
            $context = 'singular_' . $post->post_type ;
            $meta = WP_Custom_Sidebars_Options::get_post_meta( $post_id );
    	}
        // Categories, Tags and Taxonomies
        else if( is_category() || is_tag() || is_tax() ){
            
            $queried_object = get_queried_object();

            if( is_category() ){
                $context = 'category';
            }else if( is_tag() ){
                $context = 'post_tag';
            }else if( is_tax() ){
                $context = $queried_object->taxonomy;
            }

            if( !empty( $queried_object->term_id ) ){
                $meta = WP_Custom_Sidebars_Options::get_term_meta( $queried_object->term_id );
            }

        }
        // Other pages
        else{
            $meta = apply_filters( 'wpcs_output_custom_pages', $meta );
        }

        $meta = apply_filters( 'wpcs_output_before_replace_sidebars', $meta );

        if( empty( $meta ) )
            return;

        global $_wp_sidebars_widgets;

        // Make a clone
        $sbs_widgets = $_wp_sidebars_widgets;

        foreach ($meta as $id => $replace_id) {
            if( isset( $sbs_widgets[$replace_id]) )
                $_wp_sidebars_widgets[$id] = $sbs_widgets[$replace_id];
        }

    }
}

// Kickstart it
$GLOBALS['wp_custom_sidebars_main'] = new WP_Custom_Sidebars_Main;