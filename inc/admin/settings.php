<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

final class WP_Custom_Sidebars_Settings{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $data;
    private $slug = 'wp-custom-sidebars';

    /**
     * Start up
     *
     * @since 1.0.0
     */
    public function __construct(){

        $this->hooks();

    }

    /**
     * Hooks
     *
     * @since 1.0.0
     */
    public function hooks(){
        
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

        add_filter( 'plugin_action_links_' . WP_Custom_Sidebars::plugin_basename(), array( $this, 'add_action_links' ) );
        add_action( 'wp_ajax_wp-custom-sidebars-ajax-action', array( $this, 'process_sidebar' ), 10 );        
       
    }
    /**
     * Get Importable
     *
     * @since 1.0.2
     */
    static function get_importable_sidebars(){

        if( get_transient( 'wpcs_imported_sbg_sidebars' ) ){
            return false;
        }

        $old_sg = get_option( 'sbg_sidebars' );
        $sidebars = WP_Custom_Sidebars_Options::get_option( 'sidebars' );
        $importable = array();
        
        if( !empty( $old_sg ) ){
            foreach ( $old_sg as $key => $value) {
                if( !isset( $sidebars[$key] ) ){
                    $importable[$key] = $value;
                }
            }
        }

        return $importable;
    }
    /**
     * Import sbg sidebars
     *
     * @since 1.0.2
     */
    public function import_sbg_sidebars(){
        $sidebars = WP_Custom_Sidebars_Options::get_option( 'sidebars' );
        $imported = 0;

        foreach (self::get_importable_sidebars() as $key => $value) {
            if( !isset( $sidebars[$key] )){
                $sidebars[sanitize_key( $key )] = $value;
                $imported = true;
                $imported++;
            }
        };
        if( $imported ){
            WP_Custom_Sidebars_Options::update_option( 'sidebars', $sidebars );
            // set 1 week transient
            set_transient( 'wpcs_imported_sbg_sidebars', 1, WEEK_IN_SECONDS );

            return $imported;
        }
    }
     /**
     * Import sbg sidebars notice
     *
     * @since 1.0.2
     */
    public function admin_notice_import(){

        $isb = self::get_importable_sidebars();

        if( count( $isb ) ){
        ?>
        <div class="notice notice-warning is-dismissible">
            <p><?php echo wp_sprintf( esc_html__('WP Custom Sidebars detected old data from Sidebars Generator plugin ? Number of sidebars found: %s', 'wp-custom-sidebars'), count( $isb ) ); ?></p>
            <p><a href="<?php echo esc_url( wp_nonce_url( admin_url( "themes.php?page={$this->slug}&action=import-sbg-sidebars" ), 'wpcs_import_sbg_sidebars' ) );?>" class="button button-primary">Import to WP Custom Sidebars</a> or <a class="wpcs-dismiss-this" href="<?php echo esc_url( wp_nonce_url( admin_url( "themes.php?page={$this->slug}&dismiss-import-sbg-sidebars" ), 'wpcs_dismiss_import_sbg_sidebars' ) );?>">Dismiss this notice</a></p>
        </div>
        <?php
        }else{
            return false;
        }

        if( !empty( $_GET['action'] ) && 'import-sbg-sidebars' === $_GET['action'] 
            && !empty( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'wpcs_import_sbg_sidebars' ) ){

            if( ( $imported = $this->import_sbg_sidebars() ) ){
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e( 'Sidebars Imported successful.', 'wp-custom-sidebars' )?></p>
                </div>
                <?php
            }else{
                ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php esc_html_e( 'Failed to import sidebars. please try again.', 'wp-custom-sidebars' )?></p>
                </div>
                <?php
            }
        }
    }
    /**
     * Setting url
     *
     * @since 1.0.0
     */
    public function add_action_links ( $links ) {

        $mylinks = array(
            wp_sprintf( '<a href="%s">%s</a>', esc_url( admin_url( "themes.php?page={$this->slug}" ) ), esc_html__('Settings', 'wp-custom-sidebars') )
        );

        return array_merge( $links, $mylinks );
    }
    /**
     * Add theme menu
     *
     * @since 1.0.0
     */
    public function admin_menu(){

        // add admin page to Appearance
        $hook = add_theme_page( 
            esc_html__( 'Sidebar Generator', 'wp-custom-sidebars' ),
            esc_html__( 'Sidebars', 'wp-custom-sidebars' ),
            'manage_options',
            $this->slug,
            array( $this, 'create_admin_page')
        );

        // Adding help tab
        add_action( "load-$hook", array( $this, 'plugin_page' ) );

    }
    /**
     * Load stuffs on sidebar pages only
     *
     * @since 1.0.0
     */
    public function plugin_page(){
        add_action( 'admin_notices', array( $this, 'admin_notice_import' ) );
        $this->help_tabs();
    }
    /**
     * Options page callback
     *
     * @since 1.0.0
     */
    public function create_admin_page() {

        // Set class property
        $this->data = WP_Custom_Sidebars_Options::get_options();

        $active_tab = !empty( $_GET['tab'] ) && in_array( $_GET['tab'], array( 'sidebar_generator', 'general_settings' ) ) ? $_GET['tab'] : 'sidebar_generator';
        ?>
        <div class="wrap">
            <h2><?php echo esc_html__( 'WP Custom Sidebars', 'wp-custom-sidebars' );?></h2>
            <?php settings_errors(); ?>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_url( admin_url( "themes.php?page={$this->slug}&tab=sidebar_generator" ) );?>" class="nav-tab<?php echo esc_attr( $active_tab == 'sidebar_generator' ? ' nav-tab-active' : '' );?>">Sidebar Generator</a>
                <a href="<?php echo esc_url( admin_url( "themes.php?page={$this->slug}&tab=general_settings" ) );?>" class="nav-tab<?php echo esc_attr( $active_tab == 'general_settings' ? ' nav-tab-active' : '' );?>">General Settings</a>
            </h2>
            <?php 
                if( 'sidebar_generator' === $active_tab ){
                    $this->sidebar_generator_page();
                }elseif( 'general_settings' === $active_tab ){
                    $this->general_settings_page();
                }
            ?>
        </div>
        <?php
    }
    /**
     * Settings Page
     *
     * @since 1.0.0
     */
    public function general_settings_page(){
        ?>
        <form method="post" action="options.php">
        <?php
            // This prints out all hidden setting fields
            settings_fields( WP_Custom_Sidebars_Options::$option_key . '_settings' );   
            do_settings_sections( $this->slug );
            submit_button(); 
        ?>
        </form>
        <?php
    }
    /**
     * Sidebar Generator page
     *
     * @since 1.0.0
     */
    public function sidebar_generator_page(){

        $sidebars = WP_Custom_Sidebars_Options::get_option( 'sidebars' );
        ?>
        <div class="wpcs-wrap">
            <h2><?php _e( 'Sidebar Generator', 'wp-custom-sidebars' );?></h2>

            <div class="wpcs-wrapper" style="max-width:600px;">
                <p><?php esc_html_e('The sidebar name/id are for your use only. It will not be visible to any of your visitors.','wp-custom-sidebars');?></p>
                <hr>
                <p><?php esc_html_e('-To add new sidebar, Enter sidebar name to the below field then click add sidebar.', 'wp-custom-sidebars');?><br />
                <?php esc_html_e('-To remove sidebar, click &times; button then confirm.', 'wp-custom-sidebars');?></p>
                
                <form>
                    <?php wp_nonce_field('wpcs-ajax-processor-action','wpcs_ajax_processor_nonce'); ?>
                    <p>
                        <input name="sidebar_name" type="text" size="18" id="sidebar_name" value="">
                        <button class="button button-primary wpcs-add-sidebar" data-type="add"><?php _e('+ Add sidebar', 'wp-custom-sidebars');?></button>
                        <span class="spinner"></span>
                    </p>
                    <table class="widefat" id="wpcs-table">
                        <tr>
                            <th><?php _e('Name','wp-custom-sidebars');?></th>
                            <th><?php _e('ID','wp-custom-sidebars');?></th>
                            <th width="10%"><?php _e('Remove','wp-custom-sidebars');?></th>
                        </tr>

                        <?php 

                        if( !$sidebars ):?>
                        <tr class="no-sidebar-tr">
                            <td colspan="3"><?php _e('No Sidebars defined','wp-custom-sidebars');?></td>    
                        </tr>
                        <?php else:

                            foreach ( ( array ) $sidebars as $sidebar_id => $sidebar_name ) {

                                ?>
                                <tr>
                                    <td><?php echo esc_html( $sidebar_name );?></td>
                                    <td><?php echo esc_html( $sidebar_id );?></td>
                                    <td><button class="button button-small wpcs-remove-sidebar" data-type="remove" data-id="<?php echo esc_attr( $sidebar_id );?>">&times;</button></td>
                                </tr>
                                <?php
                            }
                        endif;

                        ?>
                    </table>
                    <p class="wpcs-notice"></p>
                    
                    <table class="form-table" style="width: 100%;">
                        <tr>
                            <th style="width: 20%;">
                                <label><?php echo esc_html__( 'Sidebars data' );?></label>
                            </th>
                            <td>
                                <?php
                                    
                                ?>
                                <textarea name="sidebar_transfer" type="text" size="18" id="sidebar_transfer" rows="5" cols="30" style="width:100%;"><?php echo esc_textarea( !empty( $sidebars ) ? base64_encode( json_encode( $sidebars ) ) : '' );?></textarea>
                                <button class="button wpcs-import-data" data-type="import"><?php echo esc_html__( 'Import Data', 'wp-custom-sidebars' );?></button>
                                <p class="description"><?php esc_html__( 'You can tranfer the saved sidebars data between different sites.', 'wp-custom-sidebars' ); ?></p>
                                
                            </td>
                        </tr>
                    </table>

                    <p><?php esc_html_e('-To import sidebar data, just paste your data over textarea and hit import button.','wp-custom-sidebars');?><br /><?php esc_html_e('-You can also share/transfer sidebar data by copying data from textarea.','wp-custom-sidebars');?></p>
                </form>
            </div>
        </div>

        <?php

    }
    /**
     * Admin Scripts
     * @since 1.0
     */
    public function admin_enqueue_scripts( $hook ){

        if( "appearance_page_{$this->slug}" !== $hook )
            return false;

        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        
        // wp_enqueue_style( 'wp-custom-sidebars-admin', WP_Custom_Sidebars::get_url() . 'css/admin-style.css', array(), '1.0' );
        wp_enqueue_script( 'wp-custom-sidebars-admin', WP_Custom_Sidebars::get_url() . "js/admin-script$suffix.js", array('jquery', 'jquery-ui-sortable'), '1.0', true );
        
        wp_localize_script( 'wp-custom-sidebars-admin', 'wpCustomSidebarsVars', array(
            'msgAddSidebarName' => esc_js( __('Please enter Sidebar name!', 'wp-custom-sidebars') ),
            'msgConfirmRemove' => esc_js(__( "Are you sure you want to remove this sidebar ?\nThis action will remove any widgets you have assigned to this sidebar.\nProceed?", 'wp-custom-sidebars') ),
            'msgConfirmImport' =>  esc_js(__( "The existed sidebars won't be overwritten, only new sidebars (if available) will be added to the list.\nProceed?", 'wp-custom-sidebars') )
        ) );

    }

    /**
     * Help Screen
     *
     * @since 1.0.0
     */
    public function help_tabs() {
        $screen = get_current_screen();
        $msg = 'Step 1: Select post types you want to support custom sidebars in General Settings tab.';
        $msg .= '<br><br>';
        $msg .= "Step 2: Create as many sidebars as you want. Then go to Appearance -> Widgets and add widgets to your sidebar you've just created.";
        $msg .= '<br><br>';
        $msg .= 'Step 3: Go edit page/post or category/tag/custom taxonomy, then look for WP Custom Sidebars setting box, and overriding sidebars. That\'s all';

        // Add my_help_tab if current screen is My Admin Page
        $screen->add_help_tab( array(
            'id'    => 'wp_custom_sidebars_help',
            'title' => esc_html__('How to use?'),
            'content'   => wpautop(  esc_html__( $msg, 'wp-custom-sidebars' ) ) ,
        ) );
    }

    /**
     * Add/Remove sidebar in action
     *
     * @since 1.0.0
     */
    public function process_sidebar(){

        if( empty( $_POST['nonce'] )) die('-1'); 

        $nonce = $_POST['nonce'];

        $action = 'wpcs-ajax-processor-action';
        /**
         * Check sercurity
         */

        $adminurl = strtolower( admin_url() );
        $referer = strtolower( wp_get_referer() );
        check_admin_referer( $action, 'nonce' );

        $value = !empty( $_POST['value'] ) ? $_POST['value'] : '';
        $type = !empty( $_POST['type'] ) ? $_POST['type'] : '';


        if( empty( $value ) && empty( $type ) )
            die('-1');

        if( !in_array( $type, array( 'add', 'remove', 'import') ) )
            die('-1');

        /**
         * Get saved option
         */
        $sidebars = WP_Custom_Sidebars_Options::get_option( 'sidebars' );

        $return = array( 
            'success' => true,
        );

        if( 'import' == $type ){

            $data = !empty( $_POST['value'] ) ? json_decode( base64_decode( $_POST['value'] ) ) : '';

            if( !empty( $data ) ){
                $temp_sidebars = $sidebars;

                foreach ( (array) $data as $key => $value) {
                    if( !isset( $sidebars[$key] ) ){
                        $temp_sidebars[$key] = $value;      
                    }
                }

                if( $temp_sidebars !== $sidebars ){
                    WP_Custom_Sidebars_Options::update_option( 'sidebars', $temp_sidebars );
                }
            }

            $return['data'] = array(
                'message'   => esc_html__('Data has been successfully imported! Refreshing the page...', 'wp-custom-sidebars'),
                'type'      => $type,
            );

        }
        elseif( 'add' == $type ){
            $sidebar_name = $value;
            $sidebar_id = preg_replace ("/ +/", " ", $sidebar_name); // convert all multispaces to space
            $sidebar_id = str_replace( ' ', '-', $sidebar_id );
            $sidebar_id = sanitize_key( $sidebar_id );

            
            $return['data'] = array(
                'message'   => esc_html__('Sidebar added!', 'wp-custom-sidebars'),
                'type'      => $type,
                'id'        => $sidebar_id,
                'name'      => $sidebar_name,
            );

            if( isset( $sidebars[$sidebar_id] ) ){
                $return['success'] = false;
                $return['data']['message'] = esc_html__('Sidebar already exists, please use a different name.', 'wp-custom-sidebars');
            }else{

                $sidebars[$sidebar_id] = $sidebar_name;
                // $return['data']['xxxx'] = json_encode( $sidebars );
                WP_Custom_Sidebars_Options::update_option( 'sidebars', $sidebars );

            }
            
        }else{
            if( isset( $sidebars[$value] ) ){
                unset( $sidebars[$value] );
                $return['data'] = array(
                    'message'   => esc_html__('Sidebar removed!', 'wp-custom-sidebars'),
                    'type'      => $type,
                );

                WP_Custom_Sidebars_Options::update_option( 'sidebars', $sidebars );

                //Wipe all widget inside this sidebar
                /*$widgets = get_option('sidebars_widgets');
                
                if( isset(  $widgets[$value] ) )
                    unset( $widgets[$value] );
                update_option('sidebars_widgets', $widgets);*/

            }else{
                $return['success'] = false;
                $return['data'] = array(
                    'message'   => esc_html__('Sidebar doesn\'t exist!', 'wp-custom-sidebars'),
                    'type'      => $type,
                );
            }


        }

        wp_send_json($return);

    }
    /**
     * Register and add settings
     *
     * @since 1.0.0
     */
    public function page_init() {

        register_setting(
            WP_Custom_Sidebars_Options::$option_key . "_settings", // Option group
            WP_Custom_Sidebars_Options::$option_key, // Option name
            array( $this, '_sanitize' ) // Sanitize
        );

        // General Settings
        add_settings_section(
            'general_settings_section', // ID
            esc_html__( 'General Settings', 'wp-custom-sidebars' ), // Title
            '__return_empty_string', // Callback
            $this->slug // Page
        );

        add_settings_field(
            'support_posttypes', // ID
            esc_html__( 'Post types', 'wp-custom-sidebars' ), // Title 
            array( $this, '_field_support_posttypes' ), // Callback
            $this->slug, // Page
            'general_settings_section' // Section           
        ); 



        add_settings_field(
            'support_taxonomies', // ID
            esc_html__( 'Taxonomies', 'wp-custom-sidebars' ), // Title 
            array( $this, '_field_support_taxonomies' ), // Callback
            $this->slug, // Page
            'general_settings_section' // Section           
        );

        add_settings_field(
            'support_sidebars', // ID
            esc_html__( 'Supported Sidebars', 'wp-custom-sidebars' ), // Title 
            array( $this, '_field_support_sidebars' ), // Callback
            $this->slug, // Page
            'general_settings_section' // Section           
        );  
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     * @since 1.0.0
     */
    public function _sanitize( $input ){

        $new_input = array();
        if( isset( $input['support_posttypes'] ) )
            $new_input['support_posttypes'] = $input['support_posttypes'];

        if( isset( $input['support_sidebars'] ) )
            $new_input['support_sidebars'] = $input['support_sidebars'];

        if( isset( $input['support_taxonomies'] ) )
            $new_input['support_taxonomies'] = $input['support_taxonomies'];

        // Store sidebars via ajax
        if( isset( $input['sidebars'] ) ){
            $new_input['sidebars'] = $input['sidebars'];
        }
        else{
            // This prevent deleting sidebars when saving setting page
            $new_input['sidebars'] = WP_Custom_Sidebars_Options::get_option( 'sidebars' );
        }

        return $new_input;
    }
    /** 
     * Field post types
     *
     * @since 1.0.0
     */
    public function _field_support_posttypes(){

        $post_types = get_post_types( array( 'public' => true, 'show_ui' => true ), 'names' );

        // if( NULL == $this->data['support_posttypes'] ){
        //     $this->data['support_posttypes'] = array('post');
        // }

        foreach ( $post_types as $key => $value) {

            if( 'media' === $key )
                continue;

            printf(
                '<div><label><input type="checkbox" name="%1$s" value="%2$s" %3$s/> %4$s</label></div>',
                esc_attr( WP_Custom_Sidebars_Options::$option_key . '[support_posttypes][]' ),
                esc_html( $key ),
                isset( $this->data['support_posttypes'] ) && in_array( $key , (array) $this->data['support_posttypes'] ) ? ' checked' : '',
                esc_html( $value )
            );
        }

        printf(
            '<p class="description">%s</p>',
            esc_html__( 'Select post types you wish to enable WP Custom Sidebars. By default, WP Custom Sidebars is available for posts only.', 'wp-custom-sidebars' )
        );
        
    }
    /** 
     * Field taxonomies
     *
     * @since 1.0.1
     */
    public function _field_support_taxonomies(){

        $taxonomies = get_taxonomies( array( 'public' => true, 'show_ui' => true ) );

        foreach ( $taxonomies as $key => $value) {

            printf(
                '<div><label><input type="checkbox" name="%1$s" value="%2$s" %3$s/> %4$s</label></div>',
                esc_attr( WP_Custom_Sidebars_Options::$option_key . '[support_taxonomies][]' ),
                esc_html( $key ),
                isset( $this->data['support_taxonomies'] ) && in_array( $key , (array) $this->data['support_taxonomies'] ) ? ' checked' : '',
                esc_html( $value )
            );
        }

        printf(
            '<p class="description">%s</p>',
            esc_html__( 'Select Taxonomies you wish to enable WP Custom Sidebars. By default, WP Custom Sidebars is available for categories.', 'wp-custom-sidebars' )
        );
        
    }
    /** 
     * Field supported sidebars
     *
     * @since 1.0.0
     */
    public function _field_support_sidebars(){

        $post_types = get_post_types( array( 'public' => true ), 'names' );
        global $wp_registered_sidebars;

        $open_group = false;
        $close_group = false;
        foreach ( $wp_registered_sidebars as $key => $value) {

            if( !$open_group && isset( $this->data['sidebars'][$key] ) ){
                $open_group = true;
                echo '<div class="wpcs-custom-sidebars" style="margin-top: 15px;">';
                echo '<div style="margin-bottom: 5px;"><strong>' . esc_html__( 'Your Custom Sidebars:', 'wp-custom-sidebars' ) . '</strong></div>';
            }
            printf(
                '<div><label><input type="checkbox" name="%1$s" value="%2$s" %3$s/> %4$s</label></div>',
                esc_attr( WP_Custom_Sidebars_Options::$option_key . '[support_sidebars][]' ),
                esc_html( $key ),
                isset( $this->data['support_sidebars'] ) && in_array( $key , (array) $this->data['support_sidebars'] ) ? ' checked' : '',
                esc_html( $value['name'] )
            );
        }

        if( $open_group ){
            echo '</div>';
        }

        printf(
            '<p class="description">%s</p>',
            esc_html__( 'Select sidebars you want to support overriding by different pages.', 'wp-custom-sidebars' )
        );
        

    }

}
// Kickstart
if( is_admin() )
    $wp_custom_sidebars_settings = new WP_Custom_Sidebars_Settings();