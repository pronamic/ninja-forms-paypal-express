<?php

/*
 *
 * This class does all of the heavy-lifting and interacting with PayPal via cURL.
 *
 * This file is a modified version of a PayPal tutorial that can be found here:
 * http://coding.smashingmagazine.com/2011/09/05/getting-started-with-the-paypal-api/
 *
 * Unlike the rest of this program, this file is licensed under the MIT license.
 *
 * @since 1.0
 */

class NF_Process_Paypal 
{
   /**
    * Last error message(s)
    * @var array
    */
   protected $_errors = array();

   /**
    * API Credentials
    * Use the correct credentials for the environment in use (Live / Sandbox)
    * @var array
    */
  public function get_credentials() {
    global $ninja_forms_processing;

    // Get PayPal settings and return our API credentials.
    $plugin_settings = get_option( 'ninja_forms_paypal' );

    if ( $ninja_forms_processing->get_form_setting( 'paypal_test_mode' ) == 1 ) {
      if ( isset ( $plugin_settings['test_api_user'] ) ) {
        $api_user = $plugin_settings['test_api_user'];
      } else {
        $api_user = '';
      }    

      if ( isset ( $plugin_settings['test_api_pwd'] ) ) {
        $api_pwd = $plugin_settings['test_api_pwd'];
      } else {
        $api_pwd = '';
      }

      if ( isset ( $plugin_settings['test_api_signature'] ) ) {
        $api_signature = $plugin_settings['test_api_signature'];
      } else {
        $api_signature = '';
      }
    } else {
      if ( isset ( $plugin_settings['live_api_user'] ) ) {
        $api_user = $plugin_settings['live_api_user'];
      } else {
        $api_user = '';
      }    

      if ( isset ( $plugin_settings['live_api_pwd'] ) ) {
        $api_pwd = $plugin_settings['live_api_pwd'];
      } else {
        $api_pwd = '';
      }

      if ( isset ( $plugin_settings['live_api_signature'] ) ) {
        $api_signature = $plugin_settings['live_api_signature'];
      } else {
        $api_signature = '';
      }
    }

    $credentials = array(
        'USER' => $api_user,
        'PWD' => $api_pwd,
        'SIGNATURE' => $api_signature,
    );

    return $credentials;
  }
    

   /**
    * API endpoint
    * Live - https://api-3t.paypal.com/nvp
    * Sandbox - https://api-3t.sandbox.paypal.com/nvp
    * @var string
    */
  public function get_endpoint() {
    global $ninja_forms_processing;

    // Get PayPal settings to determine if we are in "test" mode.
    if ( $ninja_forms_processing->get_form_setting( 'paypal_test_mode' ) == 1 ) {
      $end_point = 'https://api-3t.sandbox.paypal.com/nvp';
    } else {
      $end_point = 'https://api-3t.paypal.com/nvp';
    }
    return $end_point;
  }

   /**
    * API Version
    * @var string
    */
  protected $_version = '109.0';

   /**
    * Make API request
    *
    * @param string $method string API method to request
    * @param array $params Additional request parameters
    * @return array / boolean Response array / boolean false on failure
    */
  public function request($method,$params = array()) {
      $this -> _errors = array();
      if( empty($method) ) { //Check if API method is not empty
         $this -> _errors = array('API method is missing');
         return false;
      }

      //Our request parameters
      $requestParams = array(
         'METHOD' => $method,
         'VERSION' => $this -> _version,
         'SOLUTIONTYPE' => 'Sole'
      ) + $this->get_credentials();

      //Building our NVP string
      $request = http_build_query($requestParams + $params);

      //cURL settings
      $curlOptions = array (
         CURLOPT_URL => $this->get_endpoint(),
         CURLOPT_VERBOSE => 1,
         CURLOPT_SSL_VERIFYPEER => true,
         CURLOPT_SSL_VERIFYHOST => 2,
         CURLOPT_CAINFO => NINJA_FORMS_PAYPAL_EXPRESS_DIR.'/includes/cacert.pem', //CA cert file
         CURLOPT_RETURNTRANSFER => 1,
         CURLOPT_POST => 1,
         CURLOPT_POSTFIELDS => $request,
         //CURLOPT_FOLLOWLOCATION => 1
      );

      $ch = curl_init();
      curl_setopt_array($ch,$curlOptions);

      //Sending our request - $response will hold the API response
      $response = curl_exec($ch);

      //Checking for cURL errors
      if (curl_errno($ch)) {
         $this -> _errors = curl_error($ch);
         curl_close($ch);
         return false;
         //Handle errors
      } else  {
         curl_close($ch);
         $responseArray = array();
         parse_str($response,$responseArray); // Break the NVP string to an array
         return $responseArray;
      }
  }
}