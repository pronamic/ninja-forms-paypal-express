<?php

/*
 * Customizing Express Checkout
 * https://developer.paypal.com/docs/classic/express-checkout/integration-guide/ECCustomizing/
 */

/**
 * Class NF_PayPalExpress_Checkout
 *
 * @since 1.0
 * @updated 3.0
 */
class NF_PayPalExpress_Checkout
{
    /**
     * API Version
     *
     * @since 3.0
     * @var string
     */
    const VERSION = '109.0';

    /**
     * Debug Mode Flag
     *
     * @since 3.0
     * @var bool
     */
    protected $_debug = FALSE;

    /**
     * API Username
     *
     * @since 3.0
     * @var string
     */
    protected $_username = '';

    /**
     * API Password
     *
     * @since 3.0
     * @var string
     */
    protected $_password = '';

    /**
     * API Signature
     *
     * @since 3.0
     * @var string
     */
    protected $_signature = '';

    /**
     * Last error message(s)
     *
     * @since 1.0
     * @var array
     */
    protected $_errors = array();

    /**
     * @since 3.0
     * @param string $username
     * @param string $password
     * @param string $signature
     * @param bool $debug
     */
    public function __construct( $username, $password, $signature, $debug = FALSE )
    {
        $this->_debug     = $debug;

        $this->_username  = $username;
        $this->_password  = $password;
        $this->_signature = $signature;
    }

    /**
     * Get API Credentials
     *
     * @return array $credentials
     */
    public function get_credentials()
    {
        return array(
            'USER' => $this->_username,
            'PWD' => $this->_password,
            'SIGNATURE' => $this->_signature,
        );
    }

    /**
     * Checkout (request)
     *
     * @param float $total
     * @param int @form_id
     * @param string $description
     * @return array $response
     */
    public function checkout( $total, $currency, $form_id, $description = '', $details = '' )
    {
        $params = array(
            'NOSHIPPING'                     => 1,
            'PAYMENTREQUEST_0_AMT'           => $total,
            'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
            'PAYMENTREQUEST_0_CURRENCYCODE'  => $currency,
            'RETURNURL'    => add_query_arg( array( 'nf_resume' => $form_id, 'nfpe_checkout' => 'success' ), wp_get_referer() ),
            'CANCELURL'    => add_query_arg( array( 'nf_resume' => $form_id, 'nfpe_checkout' => 'cancel' ), wp_get_referer() )
        );

        if( $description ){
            // TODO: Make description
            $params[ 'NOTETOBUYER' ] = $description;
//            $params[ 'PAYMENTREQUEST_0_DESC' ] = $description;
//            $params[ 'PAYMENTREQUEST_0_CUSTOM' ] = $description;
        }
        if( $details ){
            $params[ 'PAYMENTREQUEST_0_DESC' ] = $details;
        }

        return $this->request( 'SetExpressCheckout', $params );
    }

    public function complete_checkout( $total, $currency, $token, $payer_id )
    {
        $params = array(
            'TOKEN'                          => $token,
            'PAYERID'                        => $payer_id,
            'PAYMENTACTION'                  => 'Sale',
            'BUTTONSOURCE'                   => 'WPNinjas_SP',
            'PAYMENTREQUEST_0_AMT'           => $total, // Same amount as in the original request
            'PAYMENTREQUEST_0_CURRENCYCODE'  => $currency, // Same currency as the original request
        );

        return $this->request( 'DoExpressCheckoutPayment', $params );
    }

    /**
     * Make API request
     *
     * @param string $method string API method to request
     * @param array $params Additional request parameters
     * @return array / boolean Response array / boolean false on failure
     */
    public function request( $method, $params = array() )
    {
        $this->_errors = array();
        if( empty( $method ) ) { //Check if API method is not empty
            $this -> _errors = array( 'API method is missing' );
            return false;
        }

        //Our request parameters
        $requestParams = $this->get_request_params( $method );

        //Building our NVP string
        $request = http_build_query( $requestParams + $params );

        //cURL settings
        $curlOptions = array (
            CURLOPT_URL => $this->get_endpoint(),
            CURLOPT_VERBOSE => 1,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CAINFO => NF_PayPalExpress::$dir . 'includes/cacert.pem', //CA cert file
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $request,
            //CURLOPT_FOLLOWLOCATION => 1
        );

        $ch = curl_init();
        curl_setopt_array($ch,$curlOptions);

        //Sending our request - $response will hold the API response
        $response = curl_exec($ch);

        $curl_info = curl_version();

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

    /**
     * Get API Endpoint
     *
     * @return string
     */
    public function get_endpoint()
    {
        if( $this->_debug ){
            return 'https://api-3t.sandbox.paypal.com/nvp';
        } else {
            return 'https://api-3t.paypal.com/nvp';
        }
    }

    /**
     * Get Checkout URL
     *
     * @param string $token
     * @return string $url
     */
    public function get_checkout_url( $token )
    {
        $encoded_token = urlencode( $token );

        if( $this->_debug ){
            $url = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=$encoded_token&useraction=commit";
        } else {
            $url = "https://www.paypal.com/webscr?cmd=_express-checkout&token=$encoded_token&useraction=commit";
        }

        return $url;
    }

    /**
     * Get Request Parameters
     *
     * @param string $method
     * @return array
     */
    public function get_request_params( $method )
    {
        return array(
            'METHOD'       => $method,
            'VERSION'      => self::VERSION,
            'USER'         => $this->_username,
            'PWD'          => $this->_password,
            'SIGNATURE'    => $this->_signature,
            'SOLUTIONTYPE' => 'Sole'
        );
    }

} // END CLASS NF_PayPalExpress_Checkout