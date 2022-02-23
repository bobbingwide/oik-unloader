<?php

/**
 * @copyright (C) Copyright Bobbing Wide 2022
 * @package oik-unloader
 */


class oik_loader_admin
{

    private $index;
    private $active_plugins;
    private $loaded;
    private $url;
    private $plugins;

    function __construct() {
        $this->index = null;
        $this->active_plugins = null;
        $this->loaded = false;
        $this->url = null;
        $this->plugins = [];
    }

    function oik_loader_plugins_box() {
        $this->loaded = $this->load_mu_functions();
        if ( $this->loaded ) {
            $this->load_index();
            $this->maybe_update_plugins();
            $this->report();
        }
    }

    function load_mu_functions() {
        $loaded = function_exists( 'oik_loader_mu_build_index');
        if ( !$loaded ) {
            oik_require('includes/oik-loader-mu.php', 'oik-loader');
            $loaded = function_exists( 'oik_loader_mu_build_index');
        }
        return $loaded;
    }

    /**
     * Loads the extras index.
     *
     * This doesn't load the automatically managed files for oik-loader.
     */
    function load_index() {
        $this->index = oik_loader_mu_build_index('oik-loader-extras');
    }

    function maybe_update_plugins() {
        $this->set_url();
        $update = bw_array_get( $_REQUEST, 'update', null );
        $addurl = bw_array_get( $_REQUEST, 'addurl', null );
        $deleteurl = bw_array_get( $_REQUEST, 'deleteurl', null );
        $write = false;
        if ( $update ) {
            $this->update_url();
            $write = true;
        }
        if ( $addurl) {
            $this->add_url();
            $write = true;
        }
        if ( $deleteurl ) {
            $this->delete_url();
            $write = true;
        }
        if ( $write ) {
            $this->write_csv();
        }
    }

    /**
     * Adds the URL specified in _addurl
     *
     * @TODO Shouldn't this check if the URL isn't already present?
     */
    function add_url() {
        $addurl = bw_array_get( $_REQUEST, '_addurl', null );
        $addurl = trim( $addurl );
        $plugins = $this->get_selected_plugins();
        $this->index[ $addurl ] = $plugins;
        p( "Adding URL:" . $addurl );
        $_REQUEST[ '_url'] = $addurl;
        //$this->set_url();
    }

    function delete_url() {
        $deleteurl = bw_array_get( $_REQUEST, '_url', null  );
        p( "Deleting URL: " . $deleteurl );
        unset( $this->index[ $deleteurl ]);
        $_REQUEST[ '_url'] = null;
    }

    function update_url() {
        $url = $this->get_url();
        p( "Updating URL:" . $url );
        $plugins = $this->get_selected_plugins();
        $this->index[ $url ] = $plugins;
    }

    function update( $csv ) {
        $url = array_shift( $csv );
        $ID = array_shift( $csv );
        if ( 0 === count( $csv )) {
            unset( $this->index[ $url ]);
            unset( $this->index[ $ID ] );
        } else {
            $this->index[$url] = $csv;
            //$this->index[ $ID ] = $csv;
            oik_loader_map_id( $url, $ID );
        }

        //$this->write_csv();
        oik_require( 'includes/oik-loader-admin.php', 'oik-loader');
        $target_folder = oik_unloader_target_folder();
        if ( $target_folder ) {
            $lines = $this->reconstruct_csv();
            $csv_file = oik_loader_csv_file( 'oik-loader-extras');
            //p("Writing CSV file:" . $csv_file);
            file_put_contents($csv_file, $lines );
            do_action( 'oik-loader-mu-reload');
        }

    }


    /**
     * Displays the report of plugins to activate by URL
     */
    function report() {
        if ( $this->index ) {
            $csv_file = oik_loader_csv_file();
            BW_::p($csv_file);
            $this->print_index($this->index);

        }
        $this->edit_URL_mapping();
    }

    function print_index( $index ) {
        stag( 'table', 'widefat' );
        bw_tablerow( ['URL', 'ID', 'Plugins to activate'] );
        foreach ( $index as $key => $plugins ) {
            if ( is_numeric( $key )) {
                continue;
            }
            $ID = oik_loader_map_id( $key );
            bw_tablerow(  [$key, $ID, count( $plugins )]);
        }
        etag( 'table' );
    }

    function edit_URL_mapping() {
        $this->set_url();
        bw_form();
        $this->url_select_list();
        $this->choose_url_button();
        br();
        $this->plugin_checkbox_list();
        $this->get_plugins();
        $this->plugin_select_list();
        if ( $this->index ) {
            e(isubmit('update', 'Update plugins to activate', null, 'button-secondary' ));
        }
        $this->add_url_button();
        //etag( 'table');
        ;
        etag( 'form' );
    }

    function get_url() {
        return $this->url;
    }

    /**
     * Sets the URL to edit/update
     */
    function set_url() {
        $this->url = bw_array_get( $_REQUEST, '_url', null);
        if ( null === $this->url ) {
            $this->url = $this->get_first_url_in_index();
        }
    }

    function get_first_url_in_index() {
        $first = null;
        if ( $this->index ) {
            $first = array_key_first($this->index);
        }
        return $first;
    }

    function display_add_url_field() {
        e( "New URL");
        e( itext( '_addurl', 80, ''));
    }

    function choose_url_button() {
        if ( $this->index ) {
            e(isubmit('chooseurl', "Choose URL to update"), null, 'button-primary');
            e(isubmit('deleteurl', "Delete URL"), null, 'button-secondary');
        }
    }

    function add_url_button() {
        br();
        $this->display_add_url_field();
        e( isubmit( 'addurl', "Add URL" ), null, 'button-secondary');
    }

    function get_plugins_for_url() {
        $url = $this->get_url();
        if ( isset( $this->index[ $url ])) {
            return $this->index[$url];
        }
        return [];
    }

    /**
     * Gets the active plugins - including sitewide activated plugins.
     *
     * @return array of active plugins
     */
    function get_active_plugins() {
        $active = get_option('active_plugins');
        //bw_get_active_plugins();
        $active = bw_assoc( $active );
        bw_trace2( $active, "active", false, BW_TRACE_VERBOSE );

        if ( is_multisite() ) {
            $active_plugins = get_site_option('active_sitewide_plugins');
            bw_trace2($active_plugins, "sitewide active", false, BW_TRACE_VERBOSE );
            if (count($active_plugins)) {
                foreach ($active_plugins as $key => $value) {
                    $active[$key] = $key;
                }
            }
        }
        return $active;

    }

    function url_select_list( ) {
        if ( $this->index ) {
            $filtered = $this->filtered_index();
            $args = array('#options' => $filtered);
            BW_::bw_select('_url', "URL", $this->get_url(), $args);
        }
    }

    function filtered_index() {
        $filtered = [];
        foreach ( $this->index as $key => $plugins ) {
            if ( is_numeric( $key )) {
                continue;
            }
            $filtered[ $key ] = $key . ' ' . count( $plugins );
        }
        return $filtered;
    }

    function plugin_select_list() {
        //$options = $this->get_active_plugins();
        $args = array('#options' => $this->plugins, '#multiple' => count( $this->plugins ) );
        BW_::bw_select('_plugins', 'Plugins', $this->get_plugins_for_url(), $args);


    }
    function plugin_checkbox_list() {
        $this->get_plugins();
        $selected = $this->unkeyed_to_checkbox( $this->get_plugins_for_url() );
        stag( 'table');
        foreach ( $this->plugins as $plugin ) {
            bw_checkbox_arr( '_plugins', $plugin, $selected, $plugin );
        }
        etag( 'table');
    }

    function unkeyed_to_checkbox( $unkeyed ) {
        $checkbox = [];
        foreach ( $unkeyed as $key ) {
            $checkbox[ $key ] = 'on';
        }
        return $checkbox;
    }

    function get_selected_plugins() {
        $plugins = bw_array_get( $_REQUEST, '_plugins', [] );
        $selected = [];
        foreach ( $plugins as $key => $value ) {
            if ( 'on' === $value ) {
                $selected[]=$key;
            }
        }
        return $selected;
    }

    function write_csv() {
        $csv = $this->reconstruct_csv();
        stag( 'pre');
        e( $csv );
        etag( 'pre');
        $target_folder = oik_loader_target_folder();
        if ( $target_folder ) {
            $csv_file = oik_loader_csv_file();
            p("Writing CSV file:" . $csv_file);
            file_put_contents($csv_file, $csv);
        } else {
            p("Error: Cannot create/access mu-plugins folder");
        }
    }

    function reconstruct_csv() {
        $lines = '';
        if ( null === $this->index ) {
            return $lines;
        }
        foreach ($this->index as $url => $plugins) {
            //if ( null === $ID) {
			$url = trim( $url );
			if ( is_numeric( $url ) || empty( $url )) {
				continue;
			}
            $ID = $this->get_id_for_url($url);
            ///}
            $line = "$url,$ID,";
            $line .= implode(',', $plugins);
            $line .= PHP_EOL;
            $lines .= $line;
        }
        return $lines;
    }

    function get_id_for_url( $url ) {
        $ID = oik_loader_map_id( $url, null );
        return $ID;
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
            $this->plugins[ $plugin ] = $plugin;
        }

    }
}
