<?php

/**
 * Admin functions for oik-unloader.php
 */

//oik_unloader_activate_mu( true );

/**
 * Implement "oik_admin_menu" action for oik-unloader
 *
 * Register the plugin as being supported from an oik-plugins server
 * Does this work for oik-wp as well?
 */
function oik_unloader_lazy_admin_menu()
{

    //oik_register_plugin_server(__FILE__);
    //add_action( "oik_menu_box", "oik_batch_oik_menu_box" );
    //add_action( "oik_menu_box", "oik_batch_oik_menu_box" );
    //add_action( "admin_menu", "oik_batch_admin_menu" );
    add_options_page( __('oik unloader', 'oik-unloader'), __("oik unloader", 'oik-unloader'), 'manage_options', 'oik-unloader', "oik_unloader_do_page");
    //add_options_page( __('oik trace options', 'oik-bwtrace' ), __( 'oik trace options', 'oik-bwtrace' ), 'manage_options', 'bw_trace_options', 'bw_trace_options_do_page');
}

function oik_unloader_do_page()
{
    oik_require( "admin/class-oik-unloader-admin.php", 'oik-unloader');
    //oik_require( "admin/class-oik-loader-admin.php", 'oik-unloader' );
    $oik_unloader_admin = new oik_unloader_admin();
    //$oik_loader_admin = new oik_loader_admin();

    BW_::oik_menu_header(__("oik unloader", "oik"), "w100pc");
    BW_::oik_box(null, null, __('plugins', 'oik-unloader'), [ $oik_unloader_admin, "oik_unloader_plugins_box"] );
    //BW_::oik_box( null, null, __('loader', 'oik-unloader'), [ $oik_loader_admin, "oik_loader_plugins_box"] );
    BW_::oik_box(null, null, __('oik-unloader-mu', 'oik'), "oik_unloader_oik_menu_box");
    oik_menu_footer();
    bw_flush();
}

/**
 * Checks if the MU plugin is activated.
 *
 * We can't rely on the presence of the oik_unloader_build_index function since this
 * is used by the admin interface when the MU plugin is not installed.
 * We have to check if the file exists.
 * @return bool
 */
function oik_unloader_query_loader_mu()
{
    $target = oik_unloader_target_file();
    if ($target && file_exists($target)) {
        $installed = true;
    } else {
        $installed = false;
    }
    return $installed;
}

/**
 * Checks if the logic is available.
 *
 * @return bool
 */

function oik_unloader_query_loader_active()
{
    $active = function_exists("oik_unloader_mu_build_index");
    return $active;

}

function oik_unloader_oik_menu_box()
{
    oik_unloader_mu_maybe_activate();
    $oik_unloader_mu_installed = oik_unloader_query_loader_mu();
    if ($oik_unloader_mu_installed) {
        p("oik-unloader-mu is installed");
        alink(null, admin_url("admin.php?page=oik-unloader&amp;mu=deactivate"), __("Click to deactivate MU", "oik-unloader"));
    } else {
        p("Click on the link to install oik-unloader-mu logic");
    }
    br();
    alink(null, admin_url("admin.php?page=oik-unloader&amp;mu=activate"), __("Click to activate/update MU", "oik-unloader"));

}

/**
 * Activate / deactivate the oik-unloader-mu plugin as required.
 *
 * MU plugins are activated as soon as they are installed.
 * Obviously they don't become active until the next page load.
 */
function oik_unloader_mu_maybe_activate() {
    $mu_parm = bw_array_get($_REQUEST, "mu", null);
    switch ($mu_parm) {
        case "activate":
            oik_unloader_activate_mu();
            break;
        case "deactivate":
            oik_unloader_activate_mu(false);
            break;
        default:
            break;
    }
}

function oik_unloader_target_folder() {
    if (defined('WPMU_PLUGIN_DIR')) {
        $target = WPMU_PLUGIN_DIR;
    } else {
        $target = ABSPATH . '/wp-content/mu-plugins';
    }
    bw_trace2($target, "target dir", true, BW_TRACE_DEBUG);
    if ( !is_dir( $target) ) {
        wp_mkdir_p( $target );
    }
    if ( !is_dir($target)) {
        $target = null;
    }
    return $target;

}

/**
 * Returns fully qualified name for a file in the mu-plugins folder
 *
 * It may need to create the folder.
 *
 * @return string|null
 */
function oik_unloader_target_file( $file='/oik-unloader-mu.php')
{
    $target = oik_unloader_target_folder();

    if ( $target) {
        $target .= $file;
    } else {
        // Do we need to make this ourselves?
        bw_trace2($target, "Not a dir?", true, BW_TRACE_ERROR);
        $target = null;
    }
    return $target;
}

/**
 * Activate / deactivate oik-unloader-mu processing
 *
 * @param bool $activate true to activate, false to deactivate
 */
function oik_unloader_activate_mu($activate = true)
{
    $target = oik_unloader_target_file();
    if ($target) {
        if ($activate) {
            $source = oik_path('includes/oik-unloader-mu.php', "oik-unloader");
            if (!file_exists($target) || filemtime($source) > filemtime($target)) {
                copy($source, $target);
            }

        } else {
            if (file_exists($target)) {
                unlink($target);
            }
        }
    }
}
