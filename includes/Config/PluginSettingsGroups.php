<?php if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters( 'nf_paypal_express_plugin_settings_groups', array(

    'paypal_express' => array(
        'id' => 'paypal_express',
        'label' => __( 'PayPal Express', 'ninja-forms' ),
    ),
));