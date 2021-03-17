<?php

function ninja_forms_paypal_transaction_id_shortcode( $atts ){
	global $ninja_forms_processing;
	
	return $ninja_forms_processing->get_form_setting( 'paypal_transaction_id' );

}
add_shortcode( 'ninja_forms_paypal_transaction_id', 'ninja_forms_paypal_transaction_id_shortcode' );