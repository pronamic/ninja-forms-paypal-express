<?php

class NF_Paypal_Subs_Deprecated
{
	/*
	 *
	 * Function that constructs our class.
	 *
	 * @since 1.0
	 * @return void
	 */

	public function __construct() {
		// Add our submission table actions
		add_action( 'ninja_forms_view_sub_table_header', array( $this, 'modify_header' ) );
		add_action( 'ninja_forms_view_sub_table_row', array( $this, 'modify_tr' ), 10, 2 );
		
		// Add our CSV filters
		add_filter( 'ninja_forms_export_subs_label_array', array( $this, 'filter_csv_labels' ), 10, 2 );
		add_filter( 'ninja_forms_export_subs_value_array', array( $this, 'filter_csv_values' ), 10, 2 );

		// Add our submission editor action / filter.
		add_action( 'ninja_forms_display_after_open_form_tag', array( $this, 'change_paypal_status' ) );
		add_filter( 'ninja_forms_edit_sub_args', array( $this, 'save_paypal_status' ) );

    	return;
	} // function __construct

	/*
	 *
	 * Function that modifies our view subs table header if the form has PayPal Express enabled.
	 *
	 * @since 1.0
	 * @return void
	 */

	function modify_header( $form_id ) {
		$form = ninja_forms_get_form_by_id( $form_id );
		if ( isset ( $form['data']['paypal_express'] ) AND $form['data']['paypal_express'] == 1 ) {
			?>
			<th><?php _e( 'PayPal Status', 'ninja-forms-paypal-express' );?></th>
			<th><?php _e( 'Transaction ID', 'ninja-forms-paypal-express' );?></th>
			<?php			
		}
	} // function modify_header

	/*
	 *
	 * Function that modifies our view subs table row with PayPal information.
	 *
	 * @since 1.0
	 * @return void
	 */

	function modify_tr( $form_id, $sub_id ) {
		$form = ninja_forms_get_form_by_id( $form_id );
		if ( isset( $form['data']['paypal_express'] ) AND $form['data']['paypal_express'] == 1 ) {
			$sub_row = ninja_forms_get_sub_by_id( $sub_id );

			if ( isset ( $sub_row['paypal_status'] ) ) {
				$paypal_status = $sub_row['paypal_status'];
			} else {
				$paypal_status = '';
			}		

			if ( isset ( $sub_row['paypal_transaction_id'] ) ) {
				$paypal_transaction_id = $sub_row['paypal_transaction_id'];
			} else {
				$paypal_transaction_id = '';
			}

			if ( isset ( $form['data']['paypal_express'] ) AND $form['data']['paypal_express'] == 1 ) {
				?>
				<td><?php echo $paypal_status;?></td>
				<td><?php echo $paypal_transaction_id;?></td>
				<?php			
			}			
		}

	} // function modify_tr

	/*
	 *
	 * Function that modifies the header-row of the exported CSV file by adding 'PayPal Status' and 'Transaction ID'.
	 *
	 * @since 1.0
	 * @return $label_array array
	 */

	function filter_csv_labels( $label_array, $sub_id_array ) {
		$form = ninja_forms_get_form_by_sub_id( $sub_id_array[0] );
		if ( isset ( $form['data']['paypal_express'] ) AND $form['data']['paypal_express'] == 1 ) {
			array_splice($label_array[0], 2, 0, __( 'PayPal Status', 'ninja-forms-paypal-express' ) );
			array_splice($label_array[0], 3, 0, __( 'Transaction ID', 'ninja-forms-paypal-express' ) );			
		}
		return $label_array;	
	} // function filter_csv_labels

	/*
	 *
	 * Function that modifies each row of our CSV by adding PayPal Status and Transaction ID if the form is set to use PayPal Express.
	 *
	 * @since 1.0
	 * @return $values_array array
	 */

	function filter_csv_values( $values_array, $sub_id_array ) {
		$form = ninja_forms_get_form_by_sub_id( $sub_id_array[0] );
		if ( isset ( $form['data']['paypal_express'] ) AND $form['data']['paypal_express'] == 1 ) {
			if( is_array( $values_array ) AND !empty( $values_array ) ){
				for ($i=0; $i < count( $values_array ); $i++) {
					if( isset( $sub_id_array[$i] ) ){
						$sub_row = ninja_forms_get_sub_by_id( $sub_id_array[$i] );
						$paypal_status = $sub_row['paypal_status'];
						$transaction_id = $sub_row['paypal_transaction_id'];

						array_splice($values_array[$i], 2, 0, $paypal_status );
						array_splice($values_array[$i], 3, 0, $transaction_id );
					}
				}
			}			
		}
		return $values_array;
	} // function filter_csv_values

	/*
	 *
	 * Function that outputs a Select element allowing users to manually change the PayPal status of a submission.
	 *
	 * @since 1.0
	 * @return void
	 */

	function change_paypal_status() {
		if( isset( $_REQUEST['sub_id'] ) ){
			$sub_id = $_REQUEST['sub_id'];
		}else{
			$sub_id = '';
		}
		if( $sub_id != '' ){
			$form = ninja_forms_get_form_by_sub_id( $sub_id );
			if ( isset( $form['data']['paypal_express'] ) AND $form['data']['paypal_express'] == 1 ) {
				$sub_row = ninja_forms_get_sub_by_id( $sub_id );
				$paypal_status = $sub_row['paypal_status'];
				?>
				<div>
					<?php _e( 'PayPal Status', 'ninja-forms-paypal-express' ); ?>	
					<select name="_paypal_status" id="">
						<option value="cancelled" <?php selected( $paypal_status, 'cancelled' );?>><?php _e( 'Cancelled', 'ninja-forms-paypal-express' );?></option>
						<option value="complete" <?php selected( $paypal_status, 'complete' );?>><?php _e( 'Complete', 'ninja-forms-paypal-express' );?></option>
						<option value="error" <?php selected( $paypal_status, 'error' );?>><?php _e( 'Error', 'ninja-forms-paypal-express' );?></option>
						<option value="refund" <?php selected( $paypal_status, 'refund' );?>><?php _e( 'Refund', 'ninja-forms-paypal-express' );?></option>
					</select>
				</div>
				<div>
					<?php _e( 'PayPal Transaction ID', 'ninja-forms-paypal-express' );?>: 
					<?php echo $sub_row['paypal_transaction_id']; ?>
				</div>
				<?php
			}
		}
	} // function change_paypal_status

	/*
	 *
	 * Function that saves our new paypal status
	 *
	 * @since 1.0
	 * @return void
	 */

	function save_paypal_status( $args ) {
		global $ninja_forms_processing;
		if( $ninja_forms_processing->get_extra_value( '_paypal_status' ) !== false ){
			$args['paypal_status'] = $ninja_forms_processing->get_extra_value( '_paypal_status' );
		}

		return $args;
	} // function save_paypal_status
}