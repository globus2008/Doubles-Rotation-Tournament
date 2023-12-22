<?php
/**
 * The plugin bootstrap file
 *
 * @since             1.0.0
 * @package           doubles-rotation-tournament
 *
 * @wordpress-plugin
 * Plugin Name:       Doubles Rotation Tournament
 * Plugin URI: 		  https://doroto.ltcchrast.cz/
 * Description:       Doubles Rotation Tournament is an alternative form of a doubles tennis tournament, where players play each match with a different partner and in different positions (alternating left and right sides).
 * Version:           1.0.0
 * Author:            globus2008
 * Author URI: 		  https://doroto.ltcchrast.cz/
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       doubles-rotation-tournament
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// GLOBALS AND CONSTANTS
if( ! defined( 'doroto_VERSION' ) )     { define( 'doroto_VERSION', '1.0.0' ); }
if( ! defined( 'doroto_PLUGIN_NAME' ) ) { define( 'doroto_PLUGIN_NAME', 'doubles-rotation-tournament' ); }
if( ! defined( 'doroto_PATH' ) )        { define( 'doroto_PATH', __DIR__ ); }

//define( 'PLUGIN_NAME_VERSION', '1.0.0' );
include_once(ABSPATH . 'wp-admin/includes/plugin.php');

// HEADER STRINGS (For translation)
esc_html__( 'Doubles Rotation Tournament is an alternative form of a doubles tennis tournament, where players play each match with a different partner and in different positions (alternating left and right sides).', 'doubles-rotation-tournament' );
esc_html__( 'Doubles Rotation Tournament', 'doubles-rotation-tournament' );

function doroto_frontend_styles() {
    wp_register_style('doroto-frontend-styles', plugins_url('includes/doroto-frontend-styles.css', __FILE__));
    wp_enqueue_style('doroto-frontend-styles');
}
add_action('wp_enqueue_scripts', 'doroto_frontend_styles');

function doroto_backend_styles() {
    wp_enqueue_style('doroto-backend-styles', plugins_url('includes/doroto-backend-styles.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'doroto_backend_styles');

function doroto_enqueue_frontend_scripts() {
    wp_enqueue_script( 'doroto-frontend-scripts', plugins_url( 'includes/doroto-frontend-scripts.js', __FILE__ ), array(), '1.0.0', true );
}
add_action( 'wp_enqueue_scripts', 'doroto_enqueue_frontend_scripts' );

require_once plugin_dir_path( __FILE__ ) . 'includes/doroto-shortcodes.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/doroto-repeated-functions.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/doroto-players-management.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/doroto-tournament-management.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/doroto-frontend-pages.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/doroto-backend-pages.php';

// Registering action hooks for page creation and deletion
register_activation_hook(__FILE__, 'doroto_create_tournaments_table');
register_activation_hook(__FILE__, 'doroto_create_tournament_record');
register_activation_hook(__FILE__, 'doroto_create_main_page');
register_activation_hook(__FILE__, 'doroto_create_help_page');
register_activation_hook(__FILE__, 'doroto_create_example_page');
register_activation_hook(__FILE__, 'doroto_update_pages');

function doroto_load_textdomain() {
    load_plugin_textdomain( 'doubles-rotation-tournament', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'doroto_load_textdomain' );

// Global variable declaration
global $doroto_output_form;

 // @version 1.0.0 
function doroto_check_version() {
	if( defined( 'IFRAME_REQUEST' ) ) { return; }
	$old_version = get_option( 'doroto_version' );
	if(!$old_version) {
		$old_version = '0.0.0';
		update_option('doroto_version',doroto_VERSION);
	}
	
	$version_parts = explode('.', $old_version);
	if (count($version_parts) === 3) {
    	list($version_1, $version_2, $version_3) = array_map('intval', $version_parts);
	} else {
    	//Failed to split string into three parts   	
	}
	
	if(($version_2 <= 1 && $version_3 < 1) || $version_2 < 1) {
		//update by version, nothing needed now
		
	}
	if( $old_version !== doroto_VERSION ) {
		update_option('doroto_version',doroto_VERSION);
	}
}
add_action( 'init', 'doroto_check_version', 5 );

function doroto_enqueue_libraries_scripts() {
	// On backend
	if( is_admin() ) return; 

	$tiptip_version            = '1.3';
	$registered_tiptip         = wp_scripts()->query( 'jquery-tiptip', 'registered' );
	$registered_tiptip_version = $registered_tiptip && ! empty( $registered_tiptip->ver ) ? $registered_tiptip->ver : '';
	if( ! $registered_tiptip || ( $registered_tiptip_version && version_compare( $registered_tiptip_version, $tiptip_version, '<' ) ) ) { 
		wp_register_script( 'jquery-tiptip', plugins_url( 'lib/jquery-tiptip/jquery.tipTip.min.js', __FILE__ ), array( 'jquery' ), $tiptip_version, true );
	}
	if( ! wp_script_is( 'jquery-tiptip', 'enqueued' ) ) { wp_enqueue_script( 'jquery-tiptip' ); }
}
add_action( 'admin_enqueue_scripts', 'doroto_enqueue_libraries_scripts', 9 );
add_action( 'wp_enqueue_scripts', 'doroto_enqueue_libraries_scripts', 9 );
