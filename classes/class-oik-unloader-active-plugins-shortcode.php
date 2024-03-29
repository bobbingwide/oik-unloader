<?php
/**
 * @copyright (C) Copyright Bobbing Wide 2022
 * @package oik-unloader
 *
 */

class OIK_Unloader_Active_Plugins_Shortcode {

	private $active_plugins; // Array of active plugins as far as WordPress is concerned.
	private $sitewide_plugins; // Array of Network Activated plugins as far as WordPress is concerned.
	private $combined; // Array of plugins indicating unchanged, added or deleted
	private $plugins; // Array of all plugins reduced to plugin_file => Name

	/**
	 * Initialises the class.
	 */
	function __construct() 	{
		$this->active_plugins = [];
		$this->sitewide_plugins = [];
		$this->combined = [];
		$this->plugins = [];
	}

	/**
	 * Returns true if oik-loader-MU plugin is loaded.
	 *
	 * If the oik-loader-MU plugin is loaded then we can probably assume that
	 * oik-loader functions can be used. I'll find out during testing.
	 * @return bool
	 */
	function is_oik_loader_mu_loaded() 	{
		$loaded = function_exists('oik_loader_mu_loaded');
		$loaded &= function_exists( 'oik_loader_map_id');
		return $loaded;
	}

	/**
	 * Runs the [active_plugins] shortcode.
	 *
	 * @TODO Add some args to support debug or timing or something.
	 * @param $atts
	 * @param $content
	 * @param $tag
	 * @return string
	 */
	function run($atts, $content, $tag) {
		$html = "active_plugins run";
		add_filter('option_active_plugins', [$this, 'option_active_plugins'], -1000, 2);
		add_filter('site_option_active_sitewide_plugins', [$this, 'site_option_active_sitewide_plugins'], -1000, 3);
		$active_plugins = $this->list_active_plugins();
		// Remove the filters, they're no longer needed.
		remove_filter('option_active_plugins', [$this, 'option_active_plugins'], -1000);
		remove_filter('site_option_active_sitewide_plugins', [$this, 'site_option_active_sitewide_plugins'], -1000);
		$original_plugins = $this->get_original_plugins();

		$this->determine_combined($active_plugins, $original_plugins);
		//$this->report_combined();
		$this->get_plugins();
		$loaded_available = $this->is_oik_loader_mu_loaded();
		$is_front_end = $this->is_front_end();
		$checkboxes_enabled = $is_front_end && current_user_can('activate_plugins');
		if ( $checkboxes_enabled ) {
			$this->display_plugins_form( $loaded_available);
		} else {
			$this->display_plugins_table( false, $loaded_available);
			if ( !$is_front_end ) {
				if ($loaded_available) {
					p("Save the post then view it to choose the plugins to unload or load.");
				} else {
					p("Save the post then view it to choose the plugins to unload.");
				}
			}
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
	function list_active_plugins() 	{
		$active_plugins = [];
		$sitewide_plugins = [];
		if (is_multisite()) {
			$sitewide_plugins = get_site_option('active_sitewide_plugins');
			$sitewide_plugins = array_keys($sitewide_plugins);
		}
		$active_plugins = get_option('active_plugins');
		$active_plugins = array_merge($sitewide_plugins, $active_plugins);
		return $active_plugins;
	}

	function report_plugins($plugins) 	{
		sol();
		foreach ($plugins as $plugin) {
			li($plugin);
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
	function determine_combined($active_plugins, $original_plugins) {
		$assoc_active_plugins = bw_assoc($active_plugins);
		$assoc_original_plugins = bw_assoc($original_plugins);
		$combined = array_merge($assoc_original_plugins, $assoc_active_plugins);
		$this->combined = [];
		foreach ($combined as $key => $value) {
			$unloaded = isset($assoc_original_plugins[$key]);
			$loaded = isset($assoc_active_plugins[$key]);
			$unchanged = $unloaded && $loaded;
			if ($unchanged) {
				$unloaded = false;
				$loaded = false;
			}
			$this->combined[$key] = ['unchanged' => $unchanged, 'loaded' => $loaded, 'unloaded' => $unloaded];

		}
	}

	/**
	 * Simple report of the combined plugins.
	 */
	function report_combined() {
		$report = [];
		foreach ($this->combined as $key => $values) {
			$string = $key;
			if ($values['unchanged']) {
				//
			} else {
				$string .= $values['unloaded'] ? " Unloaded" : "";
				$string .= $values['loaded'] ? " Loaded" : "";
			}
			$report[] = $string;
		}
		$this->report_plugins($report);
	}

	/**
	 * Returns the original list of plugins before unload / load.
	 *
	 * - Sitewide plugins come first.
	 * - MU plugins and drop-ins are not included.
	 *
	 * @return array
	 */
	function get_original_plugins() {
		$original_plugins = array_merge($this->sitewide_plugins, $this->active_plugins);
		return $original_plugins;
	}

	/**
	 * Saves the original values for active_plugins.
	 *
	 * @param $active_plugins
	 * @param $option
	 * @return mixed
	 */
	function option_active_plugins($active_plugins, $option) {
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
	function site_option_active_sitewide_plugins($sitewide_plugins, $option, $network_id) {
		//bw_trace2();
		$this->sitewide_plugins = array_keys($sitewide_plugins);
		return $sitewide_plugins;
	}

	/**
	 * Displays the plugins table.
	 *
	 * @param false $enabled
	 * @param bool $loaded_available
	 */
	function display_plugins_table($enabled = false, $loaded_available = true) {
		stag("table");
		//
		if ($loaded_available) {
			bw_tablerow(["Plugin", "Unload", "Load"], 'tr', 'th');
		} else {
			bw_tablerow(["Plugin", "Unload"], 'tr', 'th');
		}
		foreach ($this->combined as $key => $values) {
			$this->display_plugins_row($key, $values, $enabled, $loaded_available);
		}
		etag("table");
	}

	/**
	 * Displays the plugins form.
	 */
	function display_plugins_form( $loaded_available) {

		$this->form_tag();
		$this->display_plugins_table( true, $loaded_available);
		if ($loaded_available) {
			$this->display_load_plus();
		}
		oik_require_lib("oik-honeypot");
		do_action("oik_add_honeypot");

		e(isubmit('_oik_unloader_mu', "Update activated plugins", null, 'button-primary wp-block-button__link'));
		$this->display_hidden_url_field();
		$this->display_hidden_ID_field();

		e(wp_nonce_field("_oik_unloader_mu", "_oik_nonce", false, false));
		$this->display_timings();

		etag("form");
	}

	function is_front_end() {
		$front_end = ! ( defined( 'REST_REQUEST' ) && REST_REQUEST );
		return $front_end;
	}

	function form_tag() {
		oik_require_lib('bobbforms');
		bw_form();
	}

	function display_hidden_url_field() {
		e( ihidden( 'url', $_SERVER['REQUEST_URI'] ));
    }

	/**
	 * Displays the hidden ID field.
	 *
	 * Only sets the ID when it's a single post being displayed.
	 *
	 */
    function display_hidden_ID_field() {
		if ( is_single() ) {
			e( ihidden('ID', bw_current_post_id()));
		} else {
			e( ihidden( 'ID', '' ));
		}
	}

    function display_plugins_row( $key, $values, $enabled, $loaded_available ) {
        $row = [];
        $row[] = $this->plugin_name( $key );
        $row[] = icheckbox( "unloaded[$key]",$values['unloaded'], !$enabled );
        if ( $loaded_available ) {
            $row[] = icheckbox("loaded[$key]", $values['loaded'], !$enabled);
        }
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
        //echo " get_plugins: " . count( $this->plugins );
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
        //   echo "Handling form";
        if ( $this->validate_form() ) {
            $this->process_unloads();
            if ( $this->is_oik_loader_mu_loaded()) {
                $this->process_loads();
            }
        } else {
            p( "Something went wrong with form validation");
        }
    }

    /**
     * Applies any changes to the unloads.
     *
     * If there are no unloads selected then we need to delete the entry for the URL if it exists.
     */
    function process_unloads() {
        $unloaded = bw_array_get( $_POST, "unloaded");
        $csv = $this->get_csv( $unloaded );
        $this->update_unloaded( $csv );
    }

    /**
     * Process the loads options.
     *
     * Also needs to handle the additional field for the select list.
     * Only invoked when oik-loader-MU is available.
     */
    function process_loads() {

        $loaded = bw_array_get( $_POST, "loaded");
        //echo PHP_EOL;
        //echo "Loaded:" ;
        //print_r( $loaded );
        $csv = $this->get_csv( $loaded );
        $load_plus = $this->get_load_plus();
        $load_plus = $this->validate_load_plus( $load_plus );
        if ( $load_plus ) {
            $csv[] = $load_plus;
        }
        $this->update_loaded( $csv );
    }

    function get_csv( $field ) {
        $csv = [];
        $csv[] = $this->get_url();
        $csv[] = $this->get_ID();
        foreach ( $field as $key => $value ) {
            if ( 'on' === $value ) {
                $csv[] = $key;
            }
        }
        return $csv;
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

    /**
     * Displays a select list to allow another plugin to be loaded.
     *
     * @TODO The plugin list should exclude those already displayed.
     */
    function display_load_plus() {
        $plugins = $this->get_inactive_plugins();
        $args = [ '#options' => $plugins, '#optional' => true ];
        br();
        BW_::bw_select( 'load_plus', 'Add a plugin to be loaded', null, $args );
        br();
    }

    /**
     * Returns an array of inactive plugins.
     *
     * @return array
     */
    function get_inactive_plugins() {
        $this->get_plugins();
        $all_plugins = array_keys( $this->plugins );
        //print_r( $this->combined );
        $inactive_plugins = array_diff( $all_plugins, array_keys( $this->combined ));
        //print_r( $plugins );

        $inactive_plugins = bw_assoc( $inactive_plugins) ;
        asort( $inactive_plugins );
        return $inactive_plugins;
    }

    function get_load_plus() {
        $load_plus = bw_array_get( $_POST, "load_plus" );
        return $load_plus;
    }

    /**
     * Checks the plugin is installed.
     *
     * @param $load_plus
     * @return mixed
     */
    function validate_load_plus( $load_plus ) {
        $this->get_plugins();
        $valid = bw_array_get( $this->plugins, $load_plus, null );
        if ( !$valid ) {
            return null;
        }
        return $load_plus;
    }

    function update_unloaded( $csv ) {
        //p( "Updating unloads for: " . $csv[0] );
        //p( implode( ',', $csv ));

        oik_require( 'admin/class-oik-unloader-admin.php', 'oik-unloader' );
        $oik_unloader_admin = new oik_unloader_admin();
        $oik_unloader_admin->load_index();
        $oik_unloader_admin->update( $csv );

        //$index = oik_unloader_mu_build_index();

    }

    function update_loaded( $csv ) {
        //p( "Updating loads for: " . $csv[0] );
        //p( implode( ',', $csv ));

        oik_require( 'admin/class-oik-loader-admin.php', 'oik-unloader' );
        $oik_loader_admin = new oik_loader_admin();
        $oik_loader_admin->load_index();
        $oik_loader_admin->update( $csv );

        //$index = oik_unloader_mu_build_index();

    }

    function display_timings() {
        $timings = $this->get_timings();
        $elapsed = $this->get_elapsed();
        $timings = $this->display_elapsed_previous( $elapsed, $timings );
        e( ihidden( 'timings', $timings ));

    }

    function get_timings() {
        $timings = bw_array_get( $_POST, 'timings', null );
        return $timings;
    }

    function get_elapsed() {
        global $timestart;
        $elapsed = microtime( true ) - $timestart;
        $elapsed = sprintf( "%.6f", $elapsed );
        return $elapsed;
    }

    function display_elapsed_previous( $elapsed, $timings ) {
        $timings_array = [];
        if ( !empty( $timings )) {
            $timings_array = explode(',', $timings);
        }
        //print_r( $timings_array);
        array_unshift( $timings_array, $elapsed );
        array_splice( $timings_array, 10 );
        $timings = implode( ',', $timings_array );
        p( $timings );
        return $timings;

    }





}
