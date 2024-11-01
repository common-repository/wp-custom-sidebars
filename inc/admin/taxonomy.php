<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Class WP_Custom_Sidebars_Taxonomies
 * @since 1.0.1
 */
if( !class_exists( 'WP_Custom_Sidebars_Taxonomies' ) ):
    
    final class WP_Custom_Sidebars_Taxonomies {
        
        private $meta_key;
        private $data;
        private $labels = array(
            'singular'   => '',
            'plural'     => '',
            'descrption' => ''
        );
        /**
         * Constructor
         *
         * @return    void
         *
         * @access    public
         * @since     1.0.1
         */
        public function __construct() {

            $this->meta_key = WP_Custom_Sidebars_Options::$meta_key;
            $this->data = WP_Custom_Sidebars_Options::get_options();
            $this->labels = array(
                'singular'    => esc_html__( 'WP Custom Sidebar Settings',  'wp-custom-sidebars' ),
                'plural'      => esc_html__( 'WP Custom Sidebar Settings', 'wp-custom-sidebars' ),
                'description' => esc_html__( 'Overidding sidebars as you want! Leave field(s) empty to display orginal sidebar content.', 'wp-custom-sidebars' )
            );

            if( count( $this->data['support_sidebars'] ) ){
                $this->hooks();    
            }

        }

        /**
         * Register term meta, key, and callbacks
         *
         * @since 1.0.1
         */
        public function register_meta() {
            register_meta(
                'term',
                $this->meta_key,
                array( $this, 'sanitize_callback' ),
                array( $this, 'auth_callback'     )
            );
        }
        /**
         * Stub method for authorizing the saving of meta data
         *
         * @since 1.0.1
         *
         * @param  bool    $allowed
         * @param  string  $meta_key
         * @param  int     $post_id
         * @param  int     $user_id
         * @param  string  $cap
         * @param  array   $caps
         *
         * @return boolean
         */
        public function auth_callback( $allowed = false, $meta_key = '', $post_id = 0, $user_id = 0, $cap = '', $caps = array() ) {

            // Bail if incorrect meta key
            if ( $meta_key !== $this->meta_key ) {
                return $allowed;
            }

            return $allowed;
        }
        /**
         * Stub method for sanitizing meta data
         *
         * @since 1.0.1
         *
         * @param   mixed $data
         * @return  mixed
         */
        public function sanitize_callback( $data = '' ) {
            return $data;
        }
        /**
         * Hooks
         *
         * @since 1.0.1
         */
        public function hooks(){

            // Queries
            add_action( 'create_term', array( $this, 'save_meta' ), 10, 2 );
            add_action( 'edit_term',   array( $this, 'save_meta' ), 10, 2 );

            foreach ( (array) $this->data['support_taxonomies'] as $tax ) {
                add_action( "{$tax}_add_form_fields",  array( $this, 'add_form_field'  ), 20 );
                add_action( "{$tax}_edit_form_fields", array( $this, 'edit_form_field' ), 20 );
            }

            // Only blog admin screens
            if ( is_blog_admin() || doing_action( 'wp_ajax_inline_save_tax' ) ) {

                // Only add if taxonomy is supported
                if ( ! empty( $_REQUEST['taxonomy'] ) && in_array( $_REQUEST['taxonomy'], (array) $this->data['support_taxonomies'], true ) ) {
                    add_action( 'load-edit-tags.php', array( $this, 'edit_tags_page' ) );
                    add_action( 'load-term.php',      array( $this, 'term_page'      ) );
                    
                }
            }

        }

        /**
         * Term page script/style
         *
         * @since 1.0.1
         */
        public function term_page() {
            add_action( 'admin_head-term.php',          array( $this, 'admin_head'      ) );
            add_action( 'admin_print_scripts-term.php', array( $this, 'enqueue_scripts' ) );
        }
        /**
         * Administration area hooks
         *
         * @since 1.0.1
         */
        public function edit_tags_page() {
            add_action( 'admin_print_scripts-edit-tags.php', array( $this, 'enqueue_scripts' ) );
        }
        /**
         * Admin head
         *
         * @since 1.0.1
         */
        public function admin_head() {

        }
        /**
         * Enqueue quick-edit JS
         *
         * @since 1.0.1
         */
        public function enqueue_scripts() {

            $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
            wp_enqueue_script( 'wp-custom-sidebars-metabox', WP_Custom_Sidebars::get_url() . "js/admin-metabox$suffix.js", array('jquery', 'jquery-ui-sortable'), '1.0', true );

        }
        
        /**
         * Output the form field for this metadata when adding a new term
         *
         * @since 1.0.1
         */
        public function add_form_field() {
            ?>

            <div class="form-field term-<?php echo esc_attr( $this->meta_key ); ?>-wrap">
                <label for="term-<?php echo esc_attr( $this->meta_key ); ?>">
                    <?php echo esc_html( $this->labels['singular'] ); ?>
                </label>

                <?php $this->form_field(); ?>

                <?php if ( ! empty( $this->labels['description'] ) ) : ?>

                    <p class="description">
                        <?php echo esc_html( $this->labels['description'] ); ?>
                    </p>

                <?php endif; ?>

            </div>

            <?php
        }

        /**
         * Output the form field when editing an existing term
         *
         * @since 1.0.1
         * @param object $term
         */
        public function edit_form_field( $term = false ) {
            ?>

            <tr class="form-field term-<?php echo esc_attr( $this->meta_key ); ?>-wrap">
                <th scope="row" valign="top">
                    <label for="term-<?php echo esc_attr( $this->meta_key ); ?>">
                        <?php echo esc_html( $this->labels['singular'] ); ?>
                    </label>
                </th>
                <td>
                    <?php $this->form_field( $term ); ?>

                    <?php if ( ! empty( $this->labels['description'] ) ) : ?>

                        <p class="description">
                            <?php echo esc_html( $this->labels['description'] ); ?>
                        </p>

                    <?php endif; ?>

                </td>
            </tr>

            <?php
        }

        /**
         * Output the form field
         *
         * @since 1.0.1
         * @param  $term
         */
        protected function form_field( $term = '' ) {
            

            global $wp_registered_sidebars, $wp_registered_widgets, $_wp_sidebars_widgets;

            // Neva use global var directly
            $registered_sidebars = array();
            $temp_registered_sidebars = $wp_registered_sidebars;

            foreach ($temp_registered_sidebars as $key => $sidebar) {

                $registered_sidebars[$sidebar['id']] = $sidebar['name'];
            }

            $sidebar_data = isset( $term->term_id ) ? $this->get_meta( $term->term_id ) : '';
            ?>
            <div class="wpcs-wrapper" data-wpcs="true">
                <textarea data-wpcs-data="true" name="term-<?php echo esc_attr( $this->meta_key ); ?>" id="term-<?php echo esc_attr( $this->meta_key ); ?>" class="widefat hidden" style="display:none !important;"><?php echo esc_textarea( wp_json_encode( $sidebar_data ) );?></textarea>
                <div class="wpcs-content" data-wpcs-fields="true">

            <?php if( !empty( $registered_sidebars ) ) {?>

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

        }
        
        /**
         * Add `meta_key` to term when updating
         *
         * @since 1.0.1
         *
         * @param  int     $term_id
         * @param  string  $taxonomy
         */
        public function save_meta( $term_id = 0, $taxonomy = '' ) {

            // Get the term being posted
            $term_key = 'term-' . $this->meta_key;

            /* OK, it's safe for us to save the data now. */
            
            $meta_value = !empty( $_POST[ $term_key ] ) ? stripslashes( $_POST[ $term_key ] ) : '';

            $decoded_meta_value = json_decode( $meta_value, true );
            
            $new_meta_value = array();

            foreach ((array) $decoded_meta_value as $key => $value) {
                $new_meta_value[sanitize_key( $key )] = sanitize_text_field( $value );
            }

            $this->set_meta( $term_id, $taxonomy, $new_meta_value );
        }
        /**
         * Set `meta_key` of a specific term
         *
         * @since 1.0.1
         * @param  int     $term_id
         * @param  string  $taxonomy
         * @param  string  $meta
         * @param  bool    $clean_cache
         */
        public function set_meta( $term_id = 0, $taxonomy = '', $meta = '', $clean_cache = false ) {

            // No meta_key, so delete
            if ( empty( $meta ) ) {
                delete_term_meta( $term_id, $this->meta_key );

            // Update meta_key value
            } else {
                update_term_meta( $term_id, $this->meta_key, $meta );
            }

            // Maybe clean the term cache
            if ( true === $clean_cache ) {
                clean_term_cache( $term_id, $taxonomy );
            }
        }
        /**
         * Return the `meta_key` of a term
         *
         * @since 1.0.1
         * @param int $term_id
         */
        public function get_meta( $term_id = 0 ) {
            return get_term_meta( $term_id, $this->meta_key, true );
        }
        
    }
endif;

// Kickstart it
$GLOBALS['wp_custom_sidebars_taxonomies'] = new WP_Custom_Sidebars_Taxonomies;