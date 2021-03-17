<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'nf_paypal_express_plugin_settings', array(

    /*
    |--------------------------------------------------------------------------
    | Live Credentials
    |--------------------------------------------------------------------------
    */

    'ppe_live_api_username' => array(
        'id'    => 'ppe_live_api_username',
        'type'  => 'textbox',
        'label' => __( 'Live API Username', 'ninja-forms' ),
    ),

    'ppe_live_api_password' => array(
        'id'    => 'ppe_live_api_password',
        'type'  => 'textbox',
        'label' => __( 'Live API Password', 'ninja-forms' ),
    ),

    'ppe_live_api_signature' => array(
        'id'    => 'ppe_live_api_signature',
        'type'  => 'textbox',
        'label' => __( 'Live API Signature', 'ninja-forms' ),
    ),

    /*
    |--------------------------------------------------------------------------
    | Divider 2
    |--------------------------------------------------------------------------
    */

    'ppe_divider_2' => array(
        'id'    => 'ppe_divider_2',
        'type'  => 'html',
        'label' => '',
        'html' => '<hr />'
    ),

    /*
    |--------------------------------------------------------------------------
    | Sandbox Credentials
    |--------------------------------------------------------------------------
    */

    'ppe_test_api_username' => array(
        'id'    => 'ppe_test_api_username',
        'type'  => 'textbox',
        'label' => __( 'Test API Username', 'ninja-forms' ),
    ),

    'ppe_test_api_password' => array(
        'id'    => 'ppe_test_api_password',
        'type'  => 'textbox',
        'label' => __( 'Test API Password', 'ninja-forms' ),
    ),

    'ppe_test_api_signature' => array(
        'id'    => 'ppe_test_api_signature',
        'type'  => 'textbox',
        'label' => __( 'Test API Signature', 'ninja-forms' ),
    ),

));
