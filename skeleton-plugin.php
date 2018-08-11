<?php

/**
 * Plugin Name: Skeleton Plugin
 * Plugin URI: http://natedavis.me 
 * Description: Thank you for downloading the Skeleton Plugin
 * Version: 1.0.0
 * Author: Nate
 * Author URI: http://natedavis.me
 * Requires at least: 4.0.0
 * Tested up to: 4.0.0
 *
 * Text Domain: skeleton-plugin
 * Domain Path: /languages/
 *
 * @package Skeleton_Plugin
 * @category Core
 * @author Nate
 */


if ( ! defined( 'ABSPATH' ) ) exit; 

/**
 * Returns the main instance of Skeleton_Plugin to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Skeleton_Plugin
 */
function Skeleton_Plugin() {
    return Skeleton_Plugin::instance();
}

add_action( 'plugins_loaded', 'skeleton_Plugin' );
/**
 * Main Skeleton_Plugin Class
 *
 * @class Skeleton_Plugin
 * @version	1.0.0
 * @since 1.0.0
 * @package	Skeleton_Plugin
 * @author Nate
 */

final class Skeleton_Plugin {
    /**
     * Skeleton_Plugin The single instance of Skeleton_Plugin.
     * @var 	object
     * @access  private
     * @since 	1.0.0
     */
    private static $_instance = null;
    /**
     * The token.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $token;
    /**
     * The version number.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $version;
    /**
     * The plugin directory URL.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $plugin_url;
    /**
     * The plugin directory path.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $plugin_path;

    /**
     * The admin object.
     * @var     object
     * @access  public
     * @since   1.0.0
     */
    public $admin;
    /**
     * The settings object.
     * @var     object
     * @access  public
     * @since   1.0.0
     */
    public $settings;

    /**
     * The post types we're registering.
     * @var     array
     * @access  public
     * @since   1.0.0
     */
    public $post_types = array();

    /**
     * Constructor function.
     * @access  public
     * @since   1.0.0
     */
    public function __construct () {
        $this->token 			= 'skeleton-plugin';
        $this->plugin_url 		= plugin_dir_url( __FILE__ );
        $this->plugin_path 		= plugin_dir_path( __FILE__ );
        $this->version 			= '1.0.0';

        require_once( 'classes/class-skeleton-plugin-settings.php' );
        $this->settings = Skeleton_Plugin_Settings::instance();
        if ( is_admin() ) {
            require_once( 'classes/class-skeleton-plugin-admin.php' );
            $this->admin = Skeleton_Plugin_Admin::instance();
        }

        require_once( 'classes/class-skeleton-plugin-post-type.php' );
        require_once( 'classes/class-skeleton-plugin-taxonomy.php' );

        $this->post_types['thing'] = new Skeleton_Plugin_Post_Type( 'thing', __( 'Thing', 'skeleton-plugin' ), __( 'Things', 'skeleton-plugin' ), array( 'menu_icon' => 'dashicons-carrot' ) );

        register_activation_hook( __FILE__, array( $this, 'install' ) );
        add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
    }


    /**
     * Main Skeleton_Plugin Instance
     *
     * Ensures only one instance of Skeleton_Plugin is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @see Skeleton_Plugin()
     * @return Main Skeleton_Plugin instance
     */
    public static function instance () {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self();
        return self::$_instance;
    }

    /**
     * Load the localisation file.
     * @access  public
     * @since   1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain( 'skeleton-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * Cloning is forbidden.
     * @access public
     * @since 1.0.0
     */
    public function __clone () {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
    }

    /**
     * Unserializing instances of this class is forbidden.
     * @access public
     * @since 1.0.0
     */
    public function __wakeup () {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
    }

    /**
     * Installation. Runs on activation.
     * @access  public
     * @since   1.0.0
     */
    public function install () {
        $this->_log_version_number();
    }

    /**
     * Log the plugin version number.
     * @access  private
     * @since   1.0.0
     */
    private function _log_version_number () {
        update_option( $this->token . '-version', $this->version );
    }

}