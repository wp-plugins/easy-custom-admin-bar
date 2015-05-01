<?php
/*
  Plugin Name: Easy Custom Admin Bar
  Plugin URI: http://duogeek.com
  Description: AdminPress is the best plugin for full customization of your Dashboard.
  Version: 1.0
  Author: DuoGeek
  Author URI: http://duogeek.com
  License: GPLv2 or later
  License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

  if ( !defined( 'ABSPATH' ) ) exit;

  if ( !defined( 'DUO_PLUGIN_URI' ) ) define( 'DUO_PLUGIN_URI', plugin_dir_url( __FILE__ ) );

  require 'duogeek/duogeek-panel.php';

  if ( !defined( 'ECAB_PLUGIN_DIR' ) ) define( 'ECAB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
  if ( !defined( 'ECAB_VERSION' ) ) define( 'ECAB_VERSION', '1.0.0' );


  if ( !class_exists( 'EasyCustomAdminBar' ) ) {

    /*
     * Main class of the plugin
     */

    class EasyCustomAdminBar {
        /*
         * Variable of parent class
         */

        public $plugin_url;
        public $plugin_dir;
        public $version;
        public $options;
        public $modules_dir;

        /*
         * Constructor
         *
         * Most of the hooks are called here
         */

        public function __construct() {

            $this->plugin_dir = plugin_dir_path( __FILE__ );
            $this->plugin_url = plugins_url( '/', __FILE__ );
            $this->version = '1.0.0';
            $this->modules_dir = $this->plugin_dir . 'adminbar-files/modules';
            $this->options = $this->get_settings();

            register_activation_hook( __FILE__, array($this, 'menu_tables_install') );
            //add_action( 'admin_enqueue_scripts', array($this, 'adminpress_admin_theme_style') );
            add_filter( 'admin_scripts_styles', array($this, 'admin_scripts_styles_cb') );
            //add_action( 'login_enqueue_scripts', array($this, 'adminpress_admin_theme_style') );
            //add_action( 'login_head', array($this, 'login_internal_style') );
            add_action( 'admin_head', array($this, 'admin_internal_style') );
            //add_action( 'admin_menu', array( $this, 'register_adminpress_page' ) );
            //add_action( 'admin_notices', array($this, 'adminpress_admin_notice') );
            //add_action( 'admin_footer', array($this, 'quick_link') );

            add_filter( 'duogeek_submenu_pages', array($this, 'adminbar_menu') );
            //add_filter( 'get_user_option_admin_color', array($this, 'update_user_option_admin_color') );
        }

        /*
         * Change admin color schema
         */

        function update_user_option_admin_color( $color_scheme ) {
            $color_scheme = 'default';
            return $color_scheme;
        }

        /*
         * Menu table install
         */

        public function menu_tables_install() {
            do_action( 'plg_tables_installed' );
        }

        /**
         * Enqueue styles and scripts in admin end
         */
        public function admin_scripts_styles_cb( $enq ) {

            $scripts = array(
                array(
                    'name' => 'ap-admin-js',
                    'src' => DUO_PLUGIN_URI . 'adminbar-files/assets/js/adminbar.js',
                    'dep' => array('jquery', 'jquery-ui-core', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable'),
                    'version' => ECAB_VERSION,
                    'footer' => true,
                    'condition' => true,
                    'localize' => true,
                    'localize_data' => array(
                        'object' => 'data',
                        'passed_data' => array(
                            'media_box_title' => __( 'Custom Image', 'ecab' ),
                            'media_btn_txt' => __( 'Upload Image', 'ecab' )
                            )
                        )
                    ),
                );

            $styles = array(
                array(
                    'name' => 'ap-admin-css',
                    'src' => DUO_PLUGIN_URI . 'adminbar-files/assets/css/adminbar.css',
                    'dep' => '',
                    'version' => ECAB_VERSION,
                    'media' => 'all',
                    'condition' => true
                    ),
                );

            if ( !isset( $enq['scripts'] ) || !is_array( $enq['scripts'] ) ) $enq['scripts'] = array();
            if ( !isset( $enq['styles'] ) || !is_array( $enq['styles'] ) ) $enq['styles'] = array();
            $enq['scripts'] = array_merge( $enq['scripts'], $scripts );
            $enq['styles'] = array_merge( $enq['styles'], $styles );

            return $enq;
        }

        /*
         * Get stored data
         */

        public function get_settings() {
            return get_option( 'ecab' );
        }

        /*
         * Include dynamic style for admin page
         */

        public function admin_internal_style() {
            include $this->plugin_dir . 'adminbar-files/includes/admin-internal.php';
        }

        /*
         * Registering admin menu for the plugin
         */

        public function adminbar_menu( $submenus ) {
            $submenus[] = array(
                'title' => __( 'Easy Custom Admin Bar', 'ecab' ),
                'menu_title' => __( 'Easy Custom Admin Bar', 'ecab' ),
                'capability' => 'manage_options',
                'slug' => 'adminbar-settings',
                'object' => $this,
                'function' => 'admin_settings_page'
                );

            return $submenus;
        }

        /*
         * Main form
         */

        public function admin_settings_page() {
            global $menu;
            global $wpdb;
            if ( isset( $_POST['adminbar_nonce_field'] ) && wp_verify_nonce( $_POST['adminbar_nonce_field'], 'adminbar_nonce_action' ) ) {
                if ( isset( $_POST['adminbar_option'] ) ) {

                    update_option( 'ecab', $_POST['ecab'] );
                    //var_dump($_POST);

                    //do_action( 'save_adminbar_data', $_POST, $_REQUEST );

                    wp_redirect( admin_url( 'admin.php?page=adminbar-settings&msg=' . __( 'Settings+saved!', 'ecab' ) ) );
                }

                /*
                 * Admin Bar Reset
                 */
                if ( isset( $_POST['adminbar_reset'] ) ) {
                    global $wpdb;

                    delete_option( 'ecab' );

                    $delete_admin_bar = $wpdb->query( "TRUNCATE TABLE " . $wpdb->prefix . 'admin_bar_table' );

                    wp_redirect( admin_url( 'admin.php?page=adminbar-settings&msg=' . __( 'Settings+reset!', 'ecab' ) ) );
                }
            }

            do_action( 'save_adminbar_data', $_POST, $_REQUEST );

            if ( isset( $_REQUEST['msg'] ) && !empty( $_REQUEST['msg'] ) ) {
                ?>
                <div class="updated">
                    <p><strong><?php echo str_replace( '+', ' ', $_REQUEST['msg'] ); ?></strong></p>
                </div>
                <?php
            }

            $form = '';
            ?>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <form action="<?php echo admin_url( 'admin.php?page=adminbar-settings&noheader=true' ) ?>" enctype="multipart/form-data" method="post" class="adminpress_form">
                            <?php wp_nonce_field( 'adminbar_nonce_action', 'adminbar_nonce_field' ); ?>
                            <div class="wrap adminbar_form">
                                <h2><?php _e( 'Adminpress Settings', 'ecab' ); ?></h2>
                                <div id="dashboard-widgets" class="ecab_content metabox-holder">
                                    <div class="postbox-container">
                                        <div id="normal-sortables" class="meta-box-sortables">
                                            <?php echo apply_filters( 'adminbar_form', $form ); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="clear"></div>
                            <div class="wrap adminbar_form">
                                <div id="dashboard-widgets" class="metabox-holder">
                                    <div class="postbox-container">
                                        <div id="normal-sortables" class="meta-box-sortables">
                                            <input class="button button-primary" type="submit" name="adminbar_option" value="<?php _e( 'Save Settings', 'ecab' ) ?>" />
                                            <input class="button button-primary" type="submit" onclick="return confirm('Do you really want to reset?')" name="adminbar_reset" value="<?php _e( 'Reset All', 'ecab' ) ?>" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="clear"></div>
                        </form>
                    </div>
                    <div class="postbox-container" id="postbox-container-1" style="margin-top: 77px;">
                        <?php do_action( 'dg_settings_sidebar', 'free', 'ecab-free' ); ?>
                    </div>
                </div>

            </div>
            <?php
        }

        /*
         * Function to change hexa color code to rgb mode
         */

        public function hex2rgb( $hex ) {
            $hex = str_replace( "#", "", $hex );

            if ( strlen( $hex ) == 3 ) {
                $r = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
                $g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
                $b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
            } else {
                $r = hexdec( substr( $hex, 0, 2 ) );
                $g = hexdec( substr( $hex, 2, 2 ) );
                $b = hexdec( substr( $hex, 4, 2 ) );
            }
            $rgb = array($r, $g, $b);
            return implode( ",", $rgb ); // returns the rgb values separated by commas
            //return $rgb; // returns an array with the rgb values
        }

    }

    /*
     * Load language text domain
     */

    function ecab_load_textdomain() {
        load_plugin_textdomain( 'ecab', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
    }
    add_action( 'init', 'ecab_load_textdomain' );


    /*
     * Function to find the end string
     *
     * @param: String to be tested
     * @param: the sliced string
     *
     * @return: true/false
     */

    function ecab_endswith( $string, $test ) {
        $strlen = strlen( $string );
        $testlen = strlen( $test );
        if ( $testlen > $strlen ) return false;
        return substr_compare( $string, $test, $strlen - $testlen, $testlen ) === 0;
    }

    if( class_exists( 'AdminPress' ) ){
        function show_ab_nag_notice(){
            global $current_user ;
            $user_id = $current_user->ID;
            /* Check that the user hasn't already clicked to ignore the message */
            if ( ! get_user_meta($user_id, 'ab_ignore_notice') ) {
                echo '<div class="update-nag"><p>';
                printf(__('You already have AdminPress installed. Please change Admin Bar settings from "DuoGeek > AdminPress Settings" page. | <a href="%1$s">Hide Notice</a>'), admin_url( 'admin.php?page=adminpress-settings&ab_nag_ignore=0' ) );
                echo '</p></div>';
            }
        }
        add_action( 'admin_notices', 'show_ab_nag_notice' );
    } else {
        $adminbar = new EasyCustomAdminBar();
        // Adding modules
        $modules = scandir( $adminbar->modules_dir );
        foreach ( $modules as $module ) {
            if ( $module != '.' && $module != '..' && strpos( $module, "class." ) === 0 && ecab_endswith( $module, '.php' ) ) {
                include $adminbar->plugin_dir . 'adminbar-files/modules/' . $module;
            }
        }
    }

}