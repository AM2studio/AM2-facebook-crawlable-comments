<?php
/*
 * Plugin Name: AM2 FB Crawlable Comments
 * Version: 1.0
 * Plugin URI: http://www.am2studio.com/
 * Description: This is your starter template for your next WordPress plugin.
 * Author: Hugh Lashbrooke
 * Author URI: http://www.am2studio.com/
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: am2-facebook-crawlable-comments
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author AM2 Studio
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Load plugin class files
require_once( 'includes/class-am2-facebook-crawlable-comments.php' );
require_once( 'includes/class-am2-facebook-crawlable-comments-settings.php' );
require_once( 'includes/class-am2-facebook.php' );

// Load plugin libraries
require_once( 'includes/lib/class-am2-facebook-crawlable-comments-admin-api.php' );
require_once( 'includes/lib/class-am2-facebook-crawlable-comments-post-type.php' );
require_once( 'includes/lib/class-am2-facebook-crawlable-comments-taxonomy.php' );

/**
 * Returns the main instance of AM2_FB_Crawlable_Comments to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object AM2_FB_Crawlable_Comments
 */
function AM2_FB_Crawlable_Comments () {
	$instance = AM2_FB_Crawlable_Comments::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = AM2_FB_Crawlable_Comments_Settings::instance( $instance );
	}
	
	$instance->register_post_type("fb_object", 'FB objects', "FB object", "", array('public'=>false, 'publicly_queryable' => false));

	return $instance;
}

$am2_fb = new AM2_Facebook( AM2_FB_Crawlable_Comments() );

// var_dump ($am2_fb_comm);