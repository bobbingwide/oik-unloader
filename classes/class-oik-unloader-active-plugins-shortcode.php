<?php
/**
 * @copyright (C) Copyright Bobbing Wide 2022
 * @package oik-unloader
 *
 */

class OIK_Unloader_Active_Plugins_Shortcode
{

    private $active_plugins;
    private $sitewide_plugins;
    private $combined; // Array of  plugins indicating unchanged, added or deleted
    private $plugins; // Array of all plugins reduced to plugin_file => Name

    function __construct() {
        $this->active_plugins = [];
        $this->sitewide_plugins = [];
        $this->combined = [];
        $this->plugins = [];
    }

    function run( $atts, $content, $tag ) {
        $html = "active_plugins run";
        add_filter( 'option_active_plugins', [ $this, 'option_active_plugins'], -1000, 2 );
        add_filter( 'site_option_active_sitewide_plugins', [ $this, 'site_option_active_sitewide_plugins'], -1000, 3 );
        $active_plugins = $this->list_active_plugins();
        // Do we need to remove the filters?
        $original_plugins = $this->get_original_plugins();
        if ( false ) {
            p("Active plugins");
            $this->report_plugins($active_plugins);
            p("Original plugins");
            $this->report_plugins($original_plugins);


        }
        p("Combination");
        $this->determine_combined( $active_plugins, $original_plugins );
        $this->report_combined();
        $this->get_plugins();
        if ( current_user_can( 'activate_plugins') ) {

            $this->display_plugins_form( );
        } else {
            $this->display_plugins_table();
        }

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

    /**
     * Reports the combined view of differences
     *
     * $active_plugins is the new array. Newly loaded plugins added by oik-loader appear first.
     * $original_plugins is the starting point.
     *  A    n2 n1 o1 o3 o4
     *  O    o1 o2 o3 o4 o5
     *
     *  Combined:
     *
     *  - o1
     *  - o2 Unloaded
     *  - o3
     *  - o4 Unloaded
     *  - o5
     *  - n2 Loaded
     *  - n1 Loaded
     *
     * Notice n2 and n1 are reversed since that's how oik-loader adds plugins.
     * This shouldn't make any difference to the overall result
     *
     * @param $active_plugins
     * @param $original_plugins
     */
    function determine_combined( $active_plugins, $original_plugins ) {
        $assoc_active_plugins = bw_assoc( $active_plugins );
        $assoc_original_plugins = bw_assoc( $original_plugins );
        $combined = array_merge( $assoc_original_plugins, $assoc_active_plugins );
        $this->combined = [];
        foreach (  $combined as $key => $value ) {
            $unloaded = isset( $assoc_original_plugins[ $key ] );
            $loaded = isset( $assoc_active_plugins[ $key ] );
            $unchanged = $unloaded && $loaded;
            if ( $unchanged ) {
                $unloaded = false;
                $loaded = false;
            }
            $this->combined[ $key ] = [ 'unchanged' => $unchanged, 'loaded' => $loaded, 'unloaded' => $unloaded ];

        }
    }

    function report_combined() {
        $report = [];
        foreach (  $this->combined as $key => $values ) {
            $string = $key;
            if ( $values['unchanged'] ) {
                //
            } else {
                $string .= $values['unloaded'] ? " Unloaded" : "";
                $string .= $values['loaded'] ? " Loaded" : "";
            }
            $report[] = $string;
        }
        $this->report_plugins( $report );
    }

    /**
     * Returns the original list of plugins before unload / load.
     *
     * - Sitewide plugins come first.
     * - MU plugins and drop-ins are not included.
     *
     * @return array
     */
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
        //bw_trace2();
        $this->active_plugins = $active_plugins;
        return $active_plugins;
    }

    /**
     * Saves the original values for sitewide_plugins.
     *
     * @param $sitewide_plugins
     * @param $option
     * @param $network_id
     * @return mixed
     */
    function site_option_active_sitewide_plugins( $sitewide_plugins, $option, $network_id ) {
        //bw_trace2();
        $this->sitewide_plugins = array_keys( $sitewide_plugins );
        return $sitewide_plugins;
    }

    function display_plugins_table( $enabled=false ) {
        stag( "table");
        bw_tablerow( [ "Plugin", "Unloaded", "Loaded"], 'tr', 'th' );
        foreach ( $this->combined as $key => $values ) {
            $this->display_plugins_row($key, $values, $enabled );
        }
        etag( "table");
    }

    function display_plugins_form() {
        //p( "Form goes here");
        bw_form();
        $this->display_plugins_table( true );

        oik_require_lib( "oik-honeypot" );
        do_action( "oik_add_honeypot" );

        e( isubmit( '_oik_unloader_mu', "Update activated plugins" ));
        e( ihidden( 'url', $_SERVER['REQUEST_URI'] ));
        e( ihidden( 'ID', bw_current_post_id() ));
        e( wp_nonce_field( "_oik_unloader_mu", "_oik_nonce", false, false ) );

        etag( "form" );
    }

    function display_plugins_row( $key, $values, $enabled ) {
        $row = [];
        $row[] = $this->plugin_name( $key );
        $row[] = icheckbox( "unloaded[$key]",$values['unloaded'], !$enabled );
        $row[] = icheckbox( "loaded[$key]", $values['loaded'], !$enabled );
        bw_tablerow( $row );
    }

    function plugin_name( $key ) {
        $name = $this->get_plugin_name( $key );
        $name .= ' ';
        $name .= $this->is_network_activated( $key );
        $name .= " <code>( $key )</code>";
        return $name;
    }

    /**
     *
     */
    function get_plugins() {
        require_once ABSPATH . 'wp-admin/includes/plugin.php' ;
        $plugins = get_plugins();
        $this->plugins = [];
        foreach ( $plugins as $plugin => $data ) {
            $this->plugins[ $plugin ] = $data['Name'];
        }
    }

    function get_plugin_name( $key ) {
        $name = bw_array_get( $this->plugins, $key, $key );
        return $name;
    }

    function is_network_activated( $key ) {
        $sitewide_plugins = bw_assoc( $this->sitewide_plugins );
        //print_r( $sitewide_plugins );
        $network_activated = array_key_exists( $key, $sitewide_plugins ) ? "<b>Network</b>" : " ";
        return $network_activated;

    }

    /**
     * Validates the form.
     *
     * @return false|int
     */
    function validate_form()    {
        oik_require_lib("bobbforms");
        oik_require_lib("oik-honeypot");
        do_action("oik_check_honeypot", "Human check failed.");

        // We can't call bw_verify_nonce until later since it calls wp_verify_nonce()
        // which is in pluggable.php and that's not yet loaded.
        // $process_form = bw_verify_nonce("_oik_form", "_oik_nonce");
        $url = $this->get_url();
        $process_form = ( $url === $_SERVER['REQUEST_URI']);
        if ( $process_form ) {
            $ID = $this->get_ID();
            $process_form = ( empty($ID) || is_numeric( $ID ) );
        }
        return $process_form;
    }

    function handle_form() {
        //echo "Handling form";
        if ( $this->validate_form() ) {
            $this->process_unloads();
            $this->process_loads();
        } else {
            gpb();
        }
    }

    /**
     * Applies any changes to the unloads.
     *
     * If there are no unloads selected then we need to delete the entry for the URL if it exists.
     */
    function process_unloads() {
        $unloaded = bw_array_get( $_POST, "unloaded");

        $csv = [];
        $csv[] = $this->get_url();
        $csv[] = $this->get_ID();
        foreach ( $unloaded as $key => $value ) {
            if ( 'on' === $value ) {
                $csv[] = $key;
            }
        }
        //if ( count( $csv ) > 2 ) {
            $this->update_unloaded( $csv );
        //}
    }

    function get_url() {
        $url = bw_array_get( $_POST, "url");
        $url = trim( $url); // Not necessary since it should match REQUEST_URI.
        return $url;
    }

    function get_ID() {
        $ID = bw_array_get( $_POST, "ID");
        $ID = trim( $ID );
        return $ID;
    }

    function update_unloaded( $csv ) {
        p( "Updating unloads for: " . $csv[0] );
        p( implode( ',', $csv ));

        oik_require( 'admin/class-oik-unloader-admin.php', 'oik-unloader' );
        $oik_unloader_admin = new oik_unloader_admin();
        $oik_unloader_admin->load_index();
        $oik_unloader_admin->update( $csv );

        //$index = oik_unloader_mu_build_index();

    }

    function process_loads() {
        $loaded = bw_array_get( $_POST, "loaded");
        print_r( $loaded );

    }



}