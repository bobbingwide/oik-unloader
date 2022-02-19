<?php

class OIK_Unloader_Active_Plugins_Shortcode
{

    private $active_plugins;
    private $sitewide_plugins;

    function __construct() {
        $this->active_plugins = [];
        $this->sitewide_plugins = [];
    }

    function run( $atts, $content, $tag ) {
        $html = "active_plugins run";
        add_filter( 'option_active_plugins', [ $this, 'option_active_plugins'], -1000, 2 );
        add_filter( 'site_option_active_sitewide_plugins', [ $this, 'site_option_active_sitewide_plugins'], -1000, 3 );
        $active_plugins = $this->list_active_plugins();
        $original_plugins = $this->get_original_plugins();
        p( "Active plugins");
        $this->report_plugins( $active_plugins );
        p( "Original plugins");
        $this->report_plugins( $original_plugins );
        // Do we need to remove the filters?
        $html = bw_ret();
        return $html;
    }

    /**
     * Lists the actual active plugins.
     *
     * This is the list after the filters have been run.
     * We also need the list of Network enabled plugins.
     *
     * class OIK_unloader_admin's logic works since oik-unloader and oik-unloader aren't expected to do things in wp-admin.
     * How do we get the raw list of plugins before it's been fiddled with?
     * Answer... filter the options before the fiddlers.
     *
     */
    function list_active_plugins() {
        p( "getting active plugins ");
        $active_plugins = $this->bw_get_active_plugins();
        //print_r( $active_plugins );
        bw_trace2( $active_plugins, "active_plugins xx", false );
        return $active_plugins;
    }

    function bw_get_active_plugins() {
       $active_plugins = [];
       $sitewide_plugins = [];
       if ( is_multisite() ) {
           $sitewide_plugins = get_site_option('active_sitewide_plugins');
           $sitewide_plugins = array_keys( $sitewide_plugins );
       }
       $active_plugins = get_option( 'active_plugins' );
       $active_plugins = array_merge( $sitewide_plugins, $active_plugins );
       return $active_plugins;

    }

    function report_plugins( $plugins ) {
        sol();
        foreach ( $plugins as $plugin ) {
            li( $plugin );
        }
        eol();
    }

    function get_original_plugins()     {
        $original_plugins = array_merge( $this->sitewide_plugins, $this->active_plugins );
        return $original_plugins;
    }

    /**
     * Saves the original values for active_plugins.
     *
     * @param $active_plugins
     * @param $option
     * @return mixed
     */
    function option_active_plugins( $active_plugins, $option ) {
        bw_trace2();
        $this->active_plugins = $active_plugins;
        return $active_plugins;
    }

    /**
     * Saves the original values for sitewide_plugins.
     * @param $sitewide_plugins
     * @param $option
     * @param $network_id
     * @return mixed
     */
    function site_option_active_sitewide_plugins( $sitewide_plugins, $option, $network_id ) {
        bw_trace2();
        $this->sitewide_plugins = array_keys( $sitewide_plugins );
        return $sitewide_plugins;
    }
}