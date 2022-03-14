<?php
/*
Plugin Name: oik-unloader
Plugin URI: https://www.oik-plugins.com/oik-plugins/oik-unloader
Description: WordPress plugin to dynamically unload unnecessary plugins on demand
Version: 0.2.0
Author: bobbingwide
Author URI: https://bobbingwide.com/about-bobbing-wide/
Text Domain: oik-unloader
Domain Path: /languages/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

    Copyright 2021,2022 Bobbing Wide (email : herb@bobbingwide.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 2,
    as published by the Free Software Foundation.

    You may NOT assume that you can use any other version of the GPL.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    The license for this software can likely be found here:
    http://www.gnu.org/licenses/gpl-2.0.html

*/

function oik_unloader_loaded() {
    add_action( 'admin_menu', 'oik_unloader_admin_menu', 200 );
    add_action( "oik_loaded", "oik_unloader_oik_loaded" );
    add_action( "oik_admin_menu", "oik_unloader_oik_admin_menu");
    add_action( "oik_admin_loaded", "oik_unloader_oik_admin_loaded");
    add_action( 'plugins_loaded', 'oik_unloader_plugins_loaded', 100 );
    add_action( 'init', 'oik_unloader_init');
	add_action( 'init', 'oik_unloader_active_plugins_block_init' );
	add_action( 'oik_unloader_handle_form', 'oik_unloader_handle_form' );
}

function oik_unloader_admin_menu() {
    oik_require("includes/oik-unloader-admin.php", "oik-unloader");
    oik_unloader_lazy_admin_menu();
}

function oik_unloader_oik_loaded() {
    // oik has been loaded so we can use shared libraries
}

function oik_unloader_oik_admin_loaded()
{
    // oik_admin_loaded has been called, so we can use shared libraries

}

function oik_unloader_oik_admin_menu() {

    if ( did_action( 'oik_admin_menu') ) {
        // oik admin menu has been loaded so we can use shared libraries and oik admin functions
    }
}


/**
 * Implements 'plugins_loaded' action for oik-unloader
 *
 * Prepares use of shared libraries if this has not already been done.
 */
function oik_unloader_plugins_loaded() {
    oik_unloader_boot_libs();
    oik_require_lib( "bwtrace" );
    oik_require_lib( "bobbfunc");
}

/**
 * Boot up process for shared libraries
 *
 * ... if not already performed
 */
function oik_unloader_boot_libs() {
    if ( !function_exists( "oik_require" ) ) {
        $oik_boot_file = __DIR__ . "/libs/oik_boot.php";
        $loaded = include_once( $oik_boot_file );
    }
    oik_lib_fallback( __DIR__ . "/libs" );
}

function oik_unloader_init() {
	add_shortcode('active_plugins', 'oik_unloader_active_plugins');
}

function oik_unloader_active_plugins( $atts, $content, $tag) {
    oik_require( 'classes/class-oik-unloader-active-plugins-shortcode.php', 'oik-unloader');
    $active_plugins = new OIK_unloader_active_plugins_shortcode();
    $html = $active_plugins->run( $atts, $content, $tag);
    return $html;
}

function oik_unloader_handle_form() {
    oik_unloader_plugins_loaded();
    oik_require( 'classes/class-oik-unloader-active-plugins-shortcode.php', 'oik-unloader');
    $active_plugins = new OIK_Unloader_Active_Plugins_Shortcode();
    $active_plugins->handle_form();
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/block-editor/tutorials/block-tutorial/writing-your-first-block-type/
 */
function oik_unloader_active_plugins_block_init() {
	load_plugin_textdomain( 'oik-unloader', false, 'oik-unloader/languages' );
	$args = [ 'render_callback' => 'oik_unloader_active_plugins_dynamic_block'];
	register_block_type_from_metadata( __DIR__ . '/src/active-plugins', $args );
	//register_block_type_from_metadata( __DIR__ . '/src/second-block' );


	/**
	 * Localise the script by loading the required strings for the build/index.js file
	 * from the locale specific .json file in the languages folder.
	 * oik-unloader/active-plugins
	 */
	$ok = wp_set_script_translations( 'oik-unloader-active-plugins-editor-script', 'oik-unloader' , __DIR__ .'/languages' );
	//bw_trace2( $ok, "OK?");
	add_filter( 'load_script_textdomain_relative_path', 'oik_unloader_active_plugins_load_script_textdomain_relative_path', 10, 2);

}

/**
 * Filters $relative so that md5's match what's expected.
 *
 * Depending on how it was built the `build/index.js` may be preceded by `./` or `src/block-name/../../`.
 * In either of these situations we want the $relative value to be returned as `build/index.js`.
 * This then produces the correct md5 value and the .json file is found.
 *
 * @param $relative
 * @param $src
 *
 * @return mixed
 */
function oik_unloader_active_plugins_load_script_textdomain_relative_path( $relative, $src ) {
	if ( false !== strrpos( $relative, './build/index.js' )) {
		$relative = 'build/index.js';
	}
	//bw_trace2( $relative, "relative");
	return $relative;
}

/**
 * Implements the Active plugins block.
 *
 * @param $attributes
 * @param $content
 * @param $tag
 *
 * @return string
 */
function oik_unloader_active_plugins_dynamic_block( $attributes ) {
	$classes = '';
	if ( isset( $attributes['textAlign'] ) ) {
		$classes .= 'has-text-align-' . $attributes['textAlign'];
	}
	if ( isset( $attributes['align'] ) ) {
		$classes .= ' has-text-align-' . $attributes['align'];
	}
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $classes ) );
	//	$localised_time = date_i18n( get_option( 'time_format'));
	/* translators: %s: time in user's preferred format */
	//$content = sprintf( __( 'Active plugins block rendered at %s', 'oik-unloader'), $localised_time );
	$content = oik_unloader_active_plugins( $attributes, '', 'active_plugins');
	$html = sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $content );
	return $html;
}


oik_unloader_loaded();
