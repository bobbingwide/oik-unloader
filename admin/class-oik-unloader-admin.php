<?php

/**
 * @copyright (C) Copyright Bobbing Wide 2021
 * @package oik-unloader
 */


class oik_unloader_admin
{

    function __construct() {

    }

    function oik_unloader_plugins_box() {
        BW_::p( "Hi");
        $this->load_index();


    }

    function load_index() {
        if ( function_exists( 'oik_unloader_mu_build_index') ) {
            $index = oik_unloader_mu_build_index();
            //print_r( $index );
        }
    }
}