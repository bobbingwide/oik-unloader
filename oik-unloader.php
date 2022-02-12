<?php
/*
Plugin Name: oik-unloader
Plugin URI: https://www.oik-plugins.com/oik-plugins/oik-unloader
Description: WordPress plugin to dynamically unload unnecessary plugins on demand
Version: 0.1.1
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
    // oik_admin_loaded has been called so we can shared libraries

}

function oik_unloader_oik_admin_menu() {

    if ( did_action( 'oik_admin_menu') ) {
        // oik admin menu has been loaded so we can use shared libaries and oik admin functions
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

oik_unloader_loaded();