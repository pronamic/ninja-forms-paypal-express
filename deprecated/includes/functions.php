<?php

function ninja_forms_paypal_get_currency(){
  $plugin_settings = get_option( 'ninja_forms_paypal' );

  if ( isset ( $plugin_settings['currency'] ) ) {
    $currency = $plugin_settings['currency'];
  } else {
    $currency = 'USD';
  }

  return $currency;
}

function ninja_forms_paypal_get_total(){
  global $ninja_forms_processing;
  $total = $ninja_forms_processing->get_calc_total();

  if ( !$total ) {
    $total = $ninja_forms_processing->get_form_setting( 'paypal_default_total' );
  }
  
  return $total;
}