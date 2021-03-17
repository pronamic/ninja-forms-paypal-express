<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_PayPalExpress_MergeTags
 */
final class NF_PayPalExpress_MergeTags extends NF_Abstracts_MergeTags
{
    protected $id = 'paypal_express';

    private $transaction_id = '';

    public function __construct()
    {
        parent::__construct();
        $this->title = __( 'PayPal Express', 'ninja-forms' );

        $this->merge_tags = array(
            'transaction_id' => array(
                'id' => 'transaction_id',
                'tag' => '{paypal_express:transaction_id}',
                'label' => __( 'Transaction ID', 'ninja-forms-paypal-express' ),
                'callback' => 'get_transaction_id'
            ),
        );
    }

    public function set_transaction_id( $transaction_id = '' )
    {
        $this->transaction_id = $transaction_id;
    }

    public function get_transaction_id()
    {
        return $this->transaction_id;
    }

} // END CLASS NF_PayPalExpress_MergeTags
