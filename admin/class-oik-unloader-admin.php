<?php

/**
 * @copyright (C) Copyright Bobbing Wide 2021,2022
 * @package oik-unloader
 */


class oik_unloader_admin
{

    private $index;
    private $active_plugins;
    private $loaded;
    private $url;
    private $id;

    function __construct() {
        $this->index = null;
        $this->active_plugins = null;
        $this->loaded = false;
        $this->url = null;
        $this->id = null;
    }

    function oik_unloader_plugins_box() {
        $this->loaded = $this->load_mu_functions();
        if ( $this->loaded ) {
            $this->load_index();
            $this->maybe_update_plugins();
            $this->report();
        }
    }

    function load_mu_functions() {
        $loaded = function_exists( 'oik_unloader_mu_build_index');
        if ( !$loaded ) {
            oik_require('includes/oik-unloader-mu.php', 'oik-unloader');
            $loaded = function_exists( 'oik_unloader_mu_build_index');
        }
        return $loaded;
    }

    function load_index() {
        $this->index = oik_unloader_mu_build_index();
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
	 * OR otherwise invalid
     */
    function add_url() {
        $addurl = bw_array_get( $_REQUEST, '_addurl', null );
        $addurl = trim( $addurl );
        if ( empty( $addurl)) {
        	p( "URL should not be blank for Add URL");
        	return;
		}

        $plugins = $this->get_selected_plugins();
        $this->index[ $addurl ] = $plugins;
		$this->set_id();
		$this->index[ $this->id ] = $csv;
		oik_unloader_map_id( $url, $this->id );

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
        $this->set_id();
        $this->index[ $url ] = $plugins;
		$this->index[ $this->id ] = $plugins;
		oik_unloader_map_id( $url, $this->id );

    }

	/**
	 * Updates the CSV file for the selected URL.
	 *
	 * @param $csv
	 *
	 */
    function update( $csv ) {
        $url = array_shift( $csv );
        $ID = array_shift( $csv );
        if ( 0 === count( $csv )) {
            unset( $this->index[ $url ]);
            unset( $this->index[ $ID ] );
        } else {
            $this->index[$url] = $csv;
            $this->index[ $ID ] = $csv;
		    oik_unloader_map_id( $url, $ID );

        }
        //$this->write_csv();
        oik_require( 'includes/oik-unloader-admin.php', 'oik-unloader');
        $target_folder = oik_unloader_target_folder();
        if ( $target_folder ) {
            $lines = $this->reconstruct_csv();
            $csv_file = oik_unloader_csv_file();
            //p("Writing CSV file:" . $csv_file);
            file_put_contents($csv_file, $lines );
        }

    }


    /**
     * Displays the report of plugins to deactivate by URL
     */
    function report() {
        if ( $this->index ) {
            $csv_file = oik_unloader_csv_file();
            BW_::p($csv_file);
            $this->print_index($this->index);

        }
        $this->edit_URL_mapping();
    }

    function print_index( $index ) {
        stag( 'table', 'widefat' );
        bw_tablerow( ['URL', 'ID', 'Plugins to deactivate'] );
        foreach ( $index as $key => $plugins ) {
        	$key = trim( $key );
            if ( is_numeric( $key ) || empty( $key )) {
            	continue;
			}
            $ID = oik_unloader_map_id( $key );
            bw_tablerow(  [$key, $ID, implode("<br />", $plugins )]);

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
		br();
		$this->display_ID_field();
        if ( $this->index ) {
            e(isubmit('update', 'Update plugins to deactivate', null, 'button-secondary'));
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

    function set_id() {
    	$id = bw_array_get( $_REQUEST, '_id', null );
    	$id = trim( $id );
    	if ( is_numeric( $id ) && !empty( $id )) {
    		$this->id = $id;
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
    	br();
        e( "New URL");
        e( itext( '_addurl', 80, ''));
    }

   	function display_id_field() {
    	br();
		e( "ID" );
		e( itext( '_id', 8, $this->get_id_for_url( $this->url ) ));
	}

    function choose_url_button() {
        if ( $this->index ) {
            e(isubmit('chooseurl', "Choose URL to update", null, 'button-primary'));
            e(isubmit('deleteurl', "Delete URL", null, 'button-secondary' ));
        }
    }

    function add_url_button() {

        $this->display_add_url_field();

        e( isubmit( 'addurl', "Add URL" , null, 'button-secondary'));
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
        	$urls = $this->get_index_urls();
            $args = array('#options' => $urls );
            BW_::bw_select('_url', "URL", $this->get_url(), $args);
        }
    }

    function get_index_urls() {
    	$urls = [];
    	foreach ( $this->index as $key => $csv ) {
    		$key = trim( $key );
    		if ( is_numeric( $key ) || empty( $key )) {
    			continue;
			}
    		$urls[ $key ] = $key;
		}
    	// $urls = bw_assoc( array_keys( $this->index ) );

    	return $urls;
	}

    function plugin_select_list() {
        $options = $this->get_active_plugins();
        $args = array('#options' => $options, '#multiple' => count( $options ) );
        BW_::bw_select('_plugins', 'Plugins', $this->get_plugins_for_url(), $args);
    }

    function plugin_checkbox_list() {
    	$options = $this->get_active_plugins();
    	$selected = $this->unkeyed_to_checkbox( $this->get_plugins_for_url() );
    	stag( 'table');
    	foreach ( $options as $plugin ) {
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
        $target_folder = oik_unloader_target_folder();
        if ( $target_folder ) {
            $csv_file = oik_unloader_csv_file();
            p("Writing CSV file:" . $csv_file);
            file_put_contents($csv_file, $csv);
        } else {
            p("Error: Cannot create/access mu-plugins folder");
        }
    }

	/**
	 * Reconstructs the CSV.
	 *
	 * Ignores records keyed by the post ID or with blank URL.
	 *
	 * @return string
	 */
    function reconstruct_csv() {
        $lines = '';
        foreach ( $this->index as $url => $plugins ) {
        	if ( is_numeric( $url ) || empty( $url ) ) {
        		continue;
			}
            $ID = $this->get_id_for_url( $url );
            $line = "$url,$ID,";
            $line .= implode(',', $plugins ) ;
            $line .= PHP_EOL;
            $lines .= $line;
        }
        return $lines;

    }

    function get_id_for_url( $url ) {
        $ID = oik_unloader_map_id( $url, null );
        return $ID;
    }
}
