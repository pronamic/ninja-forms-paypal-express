<?php

/*
 *
 * This class handles our responses to the user when they return from the PayPal site.
 *
 * @since 1.0
 */

class NF_Paypal_Response
{
  
  public $token = '';
  public $checkout = '';

  /*
   * Initialize the response class
   * 
   */
  public function __construct() {
    global $ninja_forms_processing;

    // Bail if we are in the wp_admin
    if ( is_admin() )
      return false;
    
    // Bail if we don't have a $ninja_forms_processing variable.
    if ( !is_object ( $ninja_forms_processing ) )
      return false;

    // Get our "nfpe_checkout" from the querystring.
    if ( isset ( $_GET['nfpe_checkout'] ) ) {
      $this->checkout = $_GET['nfpe_checkout'];
    } else {
      $this->checkout = '';
    }
 
    // Bail if $nfpe_checkout hasn't been set or is an empty string.
    if ( $ninja_forms_processing->get_form_setting( 'paypal_redirect' ) != 1 AND ( empty( $this->checkout ) OR ( $this->checkout != 'cancel' AND $this->checkout != 'success' ) ) )
      return false;
    
    // Check to see if we've cancelled using the PayPal cancel link.
    if ( $ninja_forms_processing->get_form_setting( 'paypal_redirect' ) == 1 AND $this->checkout == 'cancel' ) {
      $this->checkout_cancel();
      return false;
    }

    // Check to see if our user pressed the "back" button on their browser after clicking the submit button.
    if ( $ninja_forms_processing->get_form_setting( 'paypal_redirect' ) == 1 AND empty ( $this->checkout ) ) {
      $this->checkout_cancel();
      return false;
    }

    // Get our "TOKEN" from the querystring.
    if ( isset ( $_GET['token'] ) ) {
      $this->token = $_GET['token'];
    } else {
      $this->token = '';
    }

    // We have a successful transaction from PayPal, run our complete function
    $this->checkout_complete();

  } // function __construct

  /*
   *
   * Function to handle a success response from PayPal.
   * 1) Call do_checkout() to finalize our payment.
   * 2) If there is an error in the checkout, add that error to our $ninja_forms_processing variable and return to our form page.
   * 3) If payment was successful, update the submission paypal_status to 'complete' and add the paypal_transaction_id
   *
   * @since 1.0
   * @return void
   */

  function checkout_complete() {
    global $ninja_forms_processing;

    $plugin_settings = get_option( 'ninja_forms_paypal' );
    // Complete the transaction with PayPal
    $response = $this->do_checkout();
    if ( ( isset ( $plugin_settings['debug'] ) and $plugin_settings['debug'] == 1 ) or ( ! isset ( $plugin_settings['debug'] ) and NINJA_FORMS_PAYPAL_EXPRESS_DEBUG ) ) {
      
      echo "<pre>";
      var_dump( $response );
      echo "</pre>";
    }
    $sub_id = $ninja_forms_processing->get_form_setting( 'sub_id' );

    // Backwards compatibility code for Ninja Forms versions before 2.7.
    if ( version_compare( NINJA_FORMS_VERSION, '2.7' ) == -1 ) {
       $sub_row = ninja_forms_get_sub_by_id( $sub_id );
    }

    if ( $response['ACK'] == 'Success' or $response['ACK'] == 'SuccessWithWarning' ) { // Payment successful
      $paypal_transaction_id = $response['PAYMENTINFO_0_TRANSACTIONID'];
      $ninja_forms_processing->update_form_setting( 'paypal_transaction_id', $paypal_transaction_id );
      $ninja_forms_processing->update_form_setting( 'paypal_redirect', 0 );
      
      // Backwards compatibility code for Ninja Forms versions before 2.7.
      if ( nf_pe_pre_27() ) {
        if ( $sub_row AND is_array ( $sub_row ) ) {
          $sub_row['paypal_status'] = 'complete';
          $sub_row['paypal_transaction_id'] = $paypal_transaction_id;
          unset( $sub_row['id'] );
          $sub_row['sub_id'] = $sub_id;
          ninja_forms_update_sub( $sub_row );
        }
      } else {
        Ninja_Forms()->sub( $sub_id )->delete_meta( '_paypal_status' );
        Ninja_Forms()->sub( $sub_id )->add_meta( '_paypal_status', 'complete' );
        Ninja_Forms()->sub( $sub_id )->add_meta( '_paypal_transaction_id', $paypal_transaction_id );        
      }

      do_action( 'ninja_forms_checkout_success', $response );
      // Run our post_process functions.
      ninja_forms_post_process();

    } else {
      // We need to add an error message to our $ninja_forms_processing.
      $ninja_forms_processing->add_error( 'paypal-fail', __( 'PayPal encountered an error in processing your transaction. Please try again.', 'ninja-forms-paypal-express' ) );
      if ( isset ( $response['L_SHORTMESSAGE0'] ) ) {
        $ninja_forms_processing->add_error( 'paypal-short-error', $response['L_SHORTMESSAGE0'] );
      }
      if ( isset ( $response['L_LONGMESSAGE0'] ) ) {
        $ninja_forms_processing->add_error( 'paypal-long-error', $response['L_LONGMESSAGE0'] );
      }

      // Backwards compatibility code for Ninja Forms versions before 2.7.
      if ( nf_pe_pre_27() ) {
        if ( $sub_row AND is_array ( $sub_row ) ) {
          $sub_row['paypal_status'] = 'error';
          $sub_row['paypal_error'] = $response['L_ERRORCODE0'];
          unset( $sub_row['id'] );
          $sub_row['sub_id'] = $sub_id;
          ninja_forms_update_sub( $sub_row );
        }
      } else {
        Ninja_Forms()->sub( $sub_id )->delete_meta( '_paypal_status' );
        Ninja_Forms()->sub( $sub_id )->add_meta( '_paypal_status', 'error' );
        Ninja_Forms()->sub( $sub_id )->add_meta( '_paypal_error', $response['L_ERRORCODE0'] );        
      }

      do_action( 'ninja_forms_checkout_fail', $response );

      /*
      ninja_forms_set_transient();
      wp_redirect( $ninja_forms_processing->get_form_setting( 'form_url' ) );
      die();
      */
    }
  } // function checkout_complete

  /*
   *
   * Function to handle the cancelling of payment (using the PayPal cancel link )
   * 1) Remove success messages.
   * 2) Add error messages and set the processing_complete variable to 0 (incomplete)
   * 3) Update the submission row to reflect the fact that this was a cancelled transaction.
   * 4) Redirect the user to the form.
   *
   * @since 1.0
   * @return void
   */

  public function checkout_cancel() {
    global $ninja_forms_processing;

    // Remove all our success messages.
    $ninja_forms_processing->remove_all_success_msgs();
    // Add our error message.
    $ninja_forms_processing->add_error( 'paypal-fail', __( 'PayPal authorization was cancelled. Please try again.', 'ninja-forms-paypal-express' ) );
    // Set processing_complete to 0 so that the form on the other end doesn't think that this was a successful submission.
    $ninja_forms_processing->update_form_setting( 'processing_complete', 0 );
    
    // If this submission has been saved, update the "paypal status" of that submission to fail.
    $sub_id = $ninja_forms_processing->get_form_setting( 'sub_id' );
    
    // Backwards compatibility code for Ninja Forms versions before 2.7.
    if ( nf_pe_pre_27() ) {
      $sub_row = ninja_forms_get_sub_by_id( $sub_id );
      if ( $sub_row AND is_array ( $sub_row ) ) {
        $sub_row['paypal_status'] = 'cancelled';
        unset( $sub_row['id'] );
        $sub_row['sub_id'] = $sub_id;
        ninja_forms_update_sub( $sub_row );
      }
    } else {
      Ninja_Forms()->sub( $sub_id )->delete_meta( '_paypal_status' );
      Ninja_Forms()->sub( $sub_id )->add_meta( '_paypal_status', 'cancelled' );
    }
   
    $ninja_forms_processing->update_form_setting( 'paypal_redirect', 0 );

    ninja_forms_set_transient();

    wp_redirect( remove_query_arg( array( 'nfpe_checkout', 'token' ), $ninja_forms_processing->get_form_setting( 'form_url' ) ) );
    die();
    
  } // function checkout_cancel

  /*
   *
   * Function to complete the PayPal transaction.
   * 
   *
   * @since 1.0
   * @return void
   */

  public function do_checkout() {

    // Get checkout details, including buyer information.
    // We can save it for future reference or cross-check with the data we have
    $paypal = new NF_Process_Paypal();
    $checkoutDetails = $paypal -> request( 'GetExpressCheckoutDetails', array( 'TOKEN' => $this->token ) );
    $currency = ninja_forms_paypal_get_currency();
    $total = ninja_forms_paypal_get_total();

    if ( is_array ( $total ) ) {
      if ( isset ( $total['total'] ) ) {
        $purchase_total = $total['total'];
      } else {
        $purchase_total = '';
      }
    } else {
      $purchase_total = $total;
    }

    // Complete the checkout transaction
    $requestParams = array(
      'TOKEN' => $this->token,
      'PAYMENTACTION' => 'Sale',
      'PAYERID' => $_GET['PayerID'],
      'BUTTONSOURCE' => 'WPNinjas_SP',
      'PAYMENTREQUEST_0_AMT' => $purchase_total, // Same amount as in the original request
      'PAYMENTREQUEST_0_CURRENCYCODE' => $currency, // Same currency as the original request
    );

    $response = $paypal -> request('DoExpressCheckoutPayment',$requestParams);
    return $response;
  } // function do_checkout

} // Class


function ninja_forms_paypal_express_response(){
  $NF_Paypal_Response = new NF_Paypal_Response();
}

add_action( 'init', 'ninja_forms_paypal_express_response', 1001 );