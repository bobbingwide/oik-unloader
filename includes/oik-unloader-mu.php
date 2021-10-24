<?php

/*
Plugin Name: oik-unloader-MU
Plugin URI: https://www.oik-plugins.com/oik-plugins/oik-unloader-mu
Description: WordPress Must Use plugin to unload unnecessary plugins on demand
Version: 0.0.0
Author: bobbingwide
Author URI: https://www.oik-plugins.com/author/bobbingwide
License: GPL2

    Copyright 2021 Bobbing Wide (email : herb@bobbingwide.com )

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

if (PHP_SAPI !== "cli") {
    oik_unloader_mu_loaded();
}

function oik_unloader_mu_loaded()
{
    $index = oik_unloader_mu_build_index();
    //print_r( $index );

    if ($index) {
        $uri = $_SERVER['REQUEST_URI'];
        $path = parse_url($uri, PHP_URL_PATH);
        $plugins = oik_unloader_mu_query_plugins($index, $path);
        /*
        if (null === $plugins) {
            $post_id = oik_unloader_mu_determine_post_id($uri);
            if ($post_id) {
                $plugins = oik_unloader_mu_query_plugins($index, $post_id);
            }
            if (null === $plugins) {
                $plugins = oik_unloader_mu_query_plugins_for_query($index);
            }
        }
        //print_r( $plugins );
        //echo "cfd;";
        */

        if (null !== $plugins) {
            //$plugins = oik_unloader_plugin_dependencies($plugins);
            oik_unloader_unload_plugins($plugins);
            add_filter("option_active_plugins", "oik_unloader_option_active_plugins", 10, 2);
        }

    }
}

/**
 * Builds the lookup index from oik-loader.blog_id.csv
 *
 * @return array|null
 */
function oik_unloader_mu_build_index()
{
    $oik_unloader_csv = oik_unloader_csv_file();
    $index = null;
    if (file_exists($oik_unloader_csv)) {
        //echo "File exists:" . $oik_unloader_csv;
        $lines = file($oik_unloader_csv);
        //echo count( $lines );
        //echo PHP_EOL;
        if (count($lines)) {
            $index = oik_unloader_build_index($lines);
        }
    }
    return $index;
}

//$oik_unloader_csv = dirname( __FILE__  ) . "/oik-loader." . $blog_id . ".csv";


function oik_unloader_csv_file($file = 'oik-unloader')
{
    global $blog_id;
    $csv_file = WPMU_PLUGIN_DIR;
    $csv_file .= '/';
    $csv_file .= $file;
    $csv_file .= '.';
    $csv_file .= $blog_id;
    $csv_file .= '.csv';
    return $csv_file;
}

/**
 * Builds the lookup index for access by URI or post ID
 *
 * Post ID will be required when editing the post, server rendering in the REST API, or when previewing
 *
 * The format of the oik-unloader.csv file is
 * `
 * URL,ID,plugin1,plugin2,...
 * e.g.
 *
 * /,0,cookie-cat/cookie-cat.php
 * /blog,1433,wordpress-seo/wp-seo-main.php
 * /privacy-notice,147,woocommerce/woocommerce.php
 * `
 *
 * @param $lines
 * @return array
 */
function oik_unloader_build_index($lines)
{
    $index = [];
    foreach ($lines as $line) {
        $csv = str_getcsv($line);
        if (count($csv) >= 3) {
            //echo $csv[0];
            $url = array_shift($csv);
            $ID = array_shift($csv);
            $index[$url] = $csv;
            //$index[$ID] = $csv;
            oik_unloader_map_id( $url, $ID );
        }
    }
    //print_r( $index );
    return $index;
}

function oik_unloader_map_id( $url, $id=null ) {
    static $url_id_map = [];
    if ( null !== $id ) {
        $url_id_map[ $url ] = $id;
        $url_id_map[ $id ] = $url;
    }
    if ( isset( $url_id_map[ $url ] ) ) {
        return $url_id_map[ $url ];
    }
    return null;
}

/**
 * Returns the plugin names to unload for the current post
 *
 * @param $index
 * @param $page
 * @return array of plugins to unload
 */
function oik_unloader_mu_query_plugins($index, $page)
{
    //echo $page;
    $plugins = null;
    if (isset($index[$page])) {
        $plugins = $index[$page];
    }
    //echo "$" . count( $plugins ) . "£";
    return $plugins;
}

/**
 * Implements 'option_active_plugins' filter
 *
 * Removes the plugins to be deactivated
 *
 * @param $active_plugins
 * @param $option
 *
 * @return array
 */
function oik_unloader_option_active_plugins($active_plugins, $option)
{
    //print_r( $active_plugins );
    //bw_backtrace();
    $unload_plugins = oik_unloader_unload_plugins();
    // build plugin dependency list
    if ($unload_plugins) {

        //echo "<br />before";
        //print_r( $unload_plugins);
        //echo count( $active_plugins );
        $active_plugins = array_diff($active_plugins, $unload_plugins);
        //echo "<br />After";
        //echo count( $active_plugins );

    }
    //print_r( $active_plugins );
    return $active_plugins;
}

/**
 * Sets / gets the names of the plugins to unload
 *
 * @param null|array $plugins
 *
 * @return null|array
 */
function oik_unloader_unload_plugins($plugins = null)
{
    static $unload_plugins = null;
    if ($plugins !== null) {
        $unload_plugins = $plugins;
    }
    //echo "Unload plugins";
    //print_r( $unload_plugins );
    return $unload_plugins;
}

/**
 * Attempts to determine the post ID for the request
 *
 * @param $uri
 *
 * @return mixed|null
 */
function oik_unloader_mu_determine_post_id($uri)
{
    //$querystring = parse_url( $uri, PHP_URL_QUERY );
    $id = null;
    $querystring = $_SERVER['QUERY_STRING'];
    $parms = [];
    if ($querystring) {
        parse_str($querystring, $parms);
        $id = isset($parms['post']) ? $parms['post'] : null;
        if (!$id) {
            $id = isset($parms['post_id']) ? $parms['post_id'] : null;
        }
        if (!$id) {
            $id = isset($parms['preview_id']) ? $parms['preview_id'] : null;
        }
        //print_r( $parms );


    } else {
        // No querystring is fine.
    }
    return $id;

}



function oik_unloader_mu_query_plugins_for_query($index)
{
    $plugins = null;
    $querystring = $_SERVER['QUERY_STRING'];
    $parms = [];
    if ($querystring) {
        parse_str($querystring, $parms);
        //print_r( $parms );
        $key = key($parms);
        $value = current($parms);
        $query_index = "$key=$value";
        //echo $query_index;
        $plugins = oik_unloader_mu_query_plugins($index, $query_index);
    }
    /*
    if ( isset( $parms['edd-api'])) {
        $plugins[] = 'edd-blocks/edd-blocks.php';
        $plugins[] = 'easy-digital-downloads/easy-digital-downloads.php';
    }
    */
    return $plugins;
}

