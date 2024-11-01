<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Class WP_Custom_Sidebars_Metabox
 */
if( !class_exists( 'WP_Custom_Sidebars_Metabox' ) ):
    
    final class WP_Custom_Sidebars_Metabox {
        
        private $meta_key;
        private $data;
        /**
         * Constructor
         *
         * @return    void
         *
         * @access    public
         * @since     1.0
         */
        public function __construct() {
            $this->meta_key = WP_Custom_Sidebars_Options::$meta_key;
            $this->data = WP_Custom_Sidebars_Options::get_options();

            if( count( $this->data['support_sidebars'] ) ){
                $this->hooks();    
            }

        }
        /**
         * Hooks
         *
         * @since 1.0
         */
        public function hooks(){
            add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
            add_action( 'save_post',      array( $this, 'save_meta_box' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        }
        /**
         * Admin Metabox Script
         * @since 1.0
         */
        public function admin_enqueue_scripts(){
            $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
            wp_enqueue_script( 'wp-custom-sidebars-metabox', WP_Custom_Sidebars::get_url() . "js/admin-metabox$suffix.js", array('jquery', 'jquery-ui-sortable'), '1.0', true );
        }
        /**
         * Adds a box to the main column on the Post and Page edit screens.
         *
         * @since 1.0.0
         */
        public function add_meta_box( $post_type ) {
            
            if ( in_array( $post_type, $this->data['support_posttypes'] ) ) {

                add_meta_box(
                    'wp_custom_sidebars_settings',
                    __( 'WP Custom Sidebars Settings', 'wp-custom-sidebars' ),
                    array( $this, 'render_meta_box' ),
                    $post_type,
                    'side'
                );
            }
        }
        /**
         * Prints the box content.
         *
         * @since 1.0
         * 
         * @param WP_Post $post The object for the current post/page.
         */
        public function render_meta_box( $post ) {

            // Add an nonce field so we can check for it later.
            wp_nonce_field( 'wp_custom_sidebars', 'wp_custom_sidebars_nonce' );

            /*
             * Use get_post_meta() to retrieve an existing value
             * from the database and use the value for the form.
             */
            do_action( 'wp_custom_sidebars_meta_box_before' );

            global $wp_registered_sidebars, $wp_registered_widgets, $_wp_sidebars_widgets;

            // Neva use global var directly
            $registered_sidebars = array();
            $temp_registered_sidebars = $wp_registered_sidebars;

            foreach ($temp_registered_sidebars as $key => $sidebar) {

                $registered_sidebars[$sidebar['id']] = $sidebar['name'];
            }

            $sidebar_data = WP_Custom_Sidebars_Options::get_post_meta( $post->ID );
            ?>
            <div class="wpcs-wrapper" data-wpcs="true">
                <textarea data-wpcs-data="true" name="<?php echo esc_attr( $this->meta_key );?>" id="<?php echo esc_attr( $this->meta_key );?>" class="widefat hidden" style="display:none !important;"><?php echo esc_textarea( wp_json_encode( $sidebar_data ) );?></textarea>
                <div class="wpcs-content" data-wpcs-fields="true">

            <?php if( !empty( $registered_sidebars ) ) {?>

                <p><?php esc_html_e( 'Overidding sidebars as you want, yay! Leave field(s) empty to display orginal sidebar content.', 'wp-custom-sidebars' );?></p>

                <?php
                $checker = false;
                foreach ($temp_registered_sidebars as $sb_id => $sb_val) {
                    $open_group = false;
                    $close_group = false;

                    if( !in_array( $sb_id, $this->data['support_sidebars'] ) )
                        continue;

                    $checker = true;

                    ?>
                    <p>
                        <label><?php echo esc_html( $sb_val['name'] );?></label>
                        <select data-name="<?php echo esc_attr( $sb_id );?>" id="<?php echo esc_attr( $sb_id );?>">
                            <option value=""></option>
                            <?php

                            foreach ($registered_sidebars as $op_id => $op_name) {
                                if( $sb_id === $op_id )
                                    continue;

                                if( !$close_group && !$open_group && isset( $this->data['sidebars'][$op_id] ) ){
                                   echo '<optgroup label="' . esc_html__( 'Custom Sidebars', 'wp-custom-sidebars' ) . '">';
                                   $open_group = true;
                                }

                                ?>
                                <option value="<?php echo esc_attr( $op_id );?>"<?php echo esc_attr( $sb_id === $op_id ? ' disabled' : '' );?><?php echo esc_attr( isset( $sidebar_data[$sb_id] ) && $sidebar_data[$sb_id] === $op_id ? ' selected' : '');?>><?php echo esc_html( $op_name );?></option>
                                <?php
                                if( !$close_group && $open_group && !isset( $this->data['sidebars'][$op_id] ) ){
                                   echo '</optgroup>';
                                   $close_group = true;
                                }
                            }

                            if( !$close_group && $open_group ){
                                echo '</optgroup>';
                            }

                            ?>
                        </select>
                        <?php
                            if( !empty( $sb_val['description'] )){
                                echo wp_sprintf( '<span class="description">%s</span>', esc_html( $sb_val['description'] ) );
                            }
                        ?>
                    </p>
                    <?php
                }

                if( !$checker && !empty( $this->data['support_sidebars'] ) ){
                    ?>
                    <p><?php 
                        echo wp_sprintf( esc_html__( 'Oops, Looks like you switched theme, please go to %s and update Supported sidebars for your theme', 'wp-custom-sidebars' ), 
                            wp_sprintf( '<a href="%s" target="_blank">%s</a>', 
                                esc_url( admin_url( "themes.php?page=wp-custom-sidebars" ) ), 
                                esc_html__('Settings', 'wp-custom-sidebars') 
                            ) 
                        ); ?></p>
                    <?php    
                }
            }else{
                ?>
                <p><?php esc_html_e( 'No sidebar found!', 'wp-custom-sidebars' ) ?></p>
                <?php

            }
            ?>
                </div>
                <!-- /[data-wpcs-fields] -->
            </div>
            <?php

            do_action( 'wp_custom_sidebars_meta_box_after' );
        }
        
        /**
         * Save the meta when the post is saved.
         *
         * @param int $post_id The ID of the post being saved.
         *
         * @since 1.0.0
         */
        public function save_meta_box( $post_id ) {
     
            /*
             * We need to verify this came from the our screen and with proper authorization,
             * because save_post can be triggered at other times.
             */
     
            // Check if our nonce is set.
            if ( ! isset( $_POST['wp_custom_sidebars_nonce'] ) ) {
                return $post_id;
            }
     
            $nonce = $_POST['wp_custom_sidebars_nonce'];
     
            // Verify that the nonce is valid.
            if ( ! wp_verify_nonce( $nonce, 'wp_custom_sidebars' ) ) {
                return $post_id;
            }
     
            /*
             * If this is an autosave, our form has not been submitted,
             * so we don't want to do anything.
             */
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return $post_id;
            }
     
            // Check the user's permissions.
            if ( 'page' == $_POST['post_type'] ) {
                if ( ! current_user_can( 'edit_page', $post_id ) ) {
                    return $post_id;
                }
            } else {
                if ( ! current_user_can( 'edit_post', $post_id ) ) {
                    return $post_id;
                }
            }
     
            /* OK, it's safe for us to save the data now. */
            
            $meta_value = !empty( $_POST[$this->meta_key] ) ? stripslashes( $_POST[$this->meta_key] ) : '';

            $decoded_meta_value = json_decode( $meta_value, true );

            $new_meta_value = array();

            do_action( 'wp_custom_sidebars_after_saved_data', $decoded_meta_value, $post_id );

            foreach ( $decoded_meta_value as $key => $value) {
                $new_meta_value[sanitize_key( $key )] = sanitize_text_field( $value );
            }
            // Sanitize the user input.
            update_post_meta( $post_id, $this->meta_key, $new_meta_value );

            do_action( 'wp_custom_sidebars_after_saved_data', $decoded_meta_value, $post_id );
            
        }
        
    }
endif;

// Kickstart it
$GLOBALS['wp_custom_sidebars_metabox'] = new WP_Custom_Sidebars_Metabox;