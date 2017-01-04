<?php
/*
 * Plugin Name: KL Articles Plugin
 * Version: 1.0
 * Plugin URI: http://www.hughlashbrooke.com/
 * Description: This is your starter template for your next WordPress plugin.
 * Author: Hugh Lashbrooke
 * Author URI: http://www.hughlashbrooke.com/
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: kl-articles-plugin
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Hugh Lashbrooke
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Load plugin class files
require_once( 'includes/class-kl-articles-plugin.php' );
require_once( 'includes/class-kl-articles-plugin-settings.php' );
require_once( 'includes/class-kl-articles-plugin-articles.php' );

// Load plugin libraries
require_once( 'includes/lib/class-kl-articles-plugin-admin-api.php' );
require_once( 'includes/lib/class-kl-articles-plugin-taxonomy.php' );
require_once( 'includes/lib/class-tw-articles-plugin-post-type.php' );


/**
 * Returns the main instance of KL_Articles_Plugin to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object KL_Articles_Plugin
 */
function KL_Articles_Plugin () {
	$instance = KL_Articles_Plugin_Articles::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = KL_Articles_Plugin_Settings::instance( $instance );
	}

	return $instance;
}

KL_Articles_Plugin();
