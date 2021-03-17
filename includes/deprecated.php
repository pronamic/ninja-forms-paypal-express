<?php

add_filter( 'nf_paypal_express_plugin_settings', 'ninja_forms_ppe_deprecated_plugin_settings' );
function ninja_forms_ppe_deprecated_plugin_settings( $settings ){
    $deprecated_settings = array(
        'ppe_currency_divider' => array(
            'id'    => 'ppe_currency_divider',
            'type'  => 'html',
            'label' => '',
            'html' => '<hr />'
        ),
        'ppe_currency_deprecated' => array(
            'id'    => 'ppe_currency_deprecated',
            'type'  => 'html',
            'label' => __( 'Transaction Currency', 'ninja-forms-paypal-express' ),
            'html'  => __( 'Currency Settings have been moved to a General Setting, which can be overridden per form.', 'ninja-forms-stripe' )
        ),
    );
    return array_merge( $settings, $deprecated_settings );
}
