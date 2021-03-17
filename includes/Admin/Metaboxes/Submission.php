<?php if ( ! defined( 'ABSPATH' ) ) exit;

final class NF_PayPalExpress_Admin_Metaboxes_Submission extends NF_Abstracts_SubmissionMetabox
{
    public function __construct()
    {
        parent::__construct();

        $this->_title = __( 'Payment Details', 'ninja-forms' );

        if( $this->sub && ! $this->sub->get_extra_value( 'paypal_status' ) && ! $this->sub->get_extra_value( '_paypal_status' ) ){
            remove_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        }
    }

    public function render_metabox( $post, $metabox )
    {
        $status = $this->sub->get_extra_value( 'paypal_status' );
        if( ! $status ) $status = $this->sub->get_extra_value( '_paypal_status' );

        $total = $this->sub->get_extra_value( 'paypal_total' );
        if( ! $total ) $total = $this->sub->get_extra_value( '_paypal_total' );

        $transaction_id = $this->sub->get_extra_value( 'paypal_transaction_id' );
        if( ! $transaction_id ) $transaction_id = $this->sub->get_extra_value( '_paypal_transaction_id' );

        $data = array(
            __( 'Status', 'ninja-forms-paypal-express' ) => $status,
            __( 'Total', 'ninja-forms-paypal-express' )  => $total,
            __( 'Transaction ID', 'ninja-forms-paypal-express' ) => $transaction_id
        );

        NF_PayPalExpress::template( 'admin-metaboxes-submission.html.php', $data );
    }
}