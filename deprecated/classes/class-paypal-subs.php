<?php

class NF_Paypal_Subs
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
		add_filter( 'nf_sub_table_columns', array( $this, 'filter_sub_table_columns' ), 10, 2 );
		// Add the appropriate data for our custom columns.
		add_action( 'manage_posts_custom_column', array( $this, 'paypal_columns' ), 10, 2 );
		
		// Add our CSV filters
		add_filter( 'ninja_forms_export_subs_label_array', array( $this, 'filter_csv_labels' ), 10, 2 );

		// Add our submission editor action / filter.
		add_action( 'add_meta_boxes', array( $this, 'add_paypal_info' ), 11, 2 );
		// Save our metabox values
		add_action( 'save_post', array( $this, 'save_paypal_info' ), 10, 2 );

    	return;
	} // function __construct

	/*
	 *
	 * Filter our submissions table columns
	 *
	 * @since 1.0.7
	 * @return void
	 */

	function filter_sub_table_columns( $cols, $form_id ) {
		// Bail if we don't have a form id.
		if ( $form_id == '' )
			return $cols;

		// Bail if we aren't working with a PayPal form.
		if ( Ninja_Forms()->form( $form_id )->get_setting( 'paypal_express' ) != 1 )
			return $cols;

		$cols = array_slice( $cols, 0, count( $cols ) - 1, true ) +
		    array( 'paypal_status' => __( 'PayPal Status', 'ninja-forms-paypal-express' ) ) +
		    array( 'paypal_transaction_id' => __( 'PayPal Transaction ID', 'ninja-forms-paypal-express' ) ) +
		    array_slice( $cols, count( $cols ) - 1, count( $cols ) - 1, true) ;

		return $cols;

	} // function filter_sub_table_columns

	/*
	 *
	 * Output our PayPal column data
	 *
	 * @since 1.0.7
	 * @return void
	 */

	function paypal_columns( $column, $sub_id ) {
		if ( $column == 'paypal_status' ) {
			echo Ninja_Forms()->sub( $sub_id )->get_meta( '_paypal_status' );
		} else if ( $column == 'paypal_transaction_id' ) {
			echo Ninja_Forms()->sub( $sub_id )->get_meta( '_paypal_transaction_id' );
		}

	} // function paypal_columns

	/*
	 *
	 * Modifies the header-row of the exported CSV file by adding 'PayPal Status' and 'Transaction ID'.
	 *
	 * @since 1.0
	 * @return $label_array array
	 */

	function filter_csv_labels( $label_array, $sub_id_array ) {
		$form_id = Ninja_Forms()->sub( $sub_id_array[0] )->form_id;
		if ( Ninja_Forms()->form( $form_id )->get_setting( 'paypal_express' ) == 1 ) {
			$label_array[0]['_paypal_status'] = __( 'PayPal Status', 'ninja-forms-paypal-express' );
			$label_array[0]['_paypal_transaction_id'] = __( 'Transaction ID', 'ninja-forms-paypal-express' );		
		}

		return $label_array;	
	} // function filter_csv_labels

	/**
	 * Register a metabox to the side of the submissions page for displaying and editing PayPal status.
	 * 
	 * @since 1.0.7
	 * @return void
	 */
	public function add_paypal_info( $post_type, $post ) {
		if ( $post_type != 'nf_sub' )
			return false;
		
		$form_id = Ninja_Forms()->sub( $post->ID )->form_id;
		if ( Ninja_Forms()->form( $form_id )->get_setting( 'paypal_express' ) == 1 ) {
			// Add our save field values metabox
			add_meta_box( 'nf_paypal_info', __( 'PayPal information', 'ninja-forms-paypal-express' ), array( $this, 'paypal_info_metabox' ), 'nf_sub', 'side', 'default');		
		}
	}

	/*
	 *
	 * Function that outputs a Select element allowing users to manually change the PayPal status of a submission.
	 *
	 * @since 1.0.7
	 * @return void
	 */

	function paypal_info_metabox( $sub ) {
		$form_id = Ninja_Forms()->sub( $sub->ID )->form_id;
		if ( Ninja_Forms()->form( $form_id )->get_setting( 'paypal_express' ) == 1 ) {
			$paypal_status = Ninja_Forms()->sub( $sub->ID )->get_meta( '_paypal_status' );
			?>
			<div class="submitbox" id="submitpost">
				<div id="minor-publishing">
					<div id="misc-publishing-actions">
						<div class="misc-pub-section misc-pub-post-status">
							<label for=""><?php _e( 'Transaction ID', 'ninja-forms-paypal-express' );?>:</label>
							<span id=""><strong><?php echo Ninja_Forms()->sub( $sub->ID )->get_meta( '_paypal_transaction_id' ); ?></strong></span>
						</div>
						<div class="misc-pub-section misc-pub-post-status">
							<label for=""><?php _e( 'Status', 'ninja-forms-paypal-express' ); ?></label>
							<span id="">
								<select name="_paypal_status" id="">
									<option value="pending" <?php selected( $paypal_status, 'pending' );?>><?php _e( 'Pending', 'ninja-forms-paypal-express' );?></option>
									<option value="cancelled" <?php selected( $paypal_status, 'cancelled' );?>><?php _e( 'Cancelled', 'ninja-forms-paypal-express' );?></option>
									<option value="complete" <?php selected( $paypal_status, 'complete' );?>><?php _e( 'Complete', 'ninja-forms-paypal-express' );?></option>
									<option value="error" <?php selected( $paypal_status, 'error' );?>><?php _e( 'Error', 'ninja-forms-paypal-express' );?></option>
									<option value="refund" <?php selected( $paypal_status, 'refund' );?>><?php _e( 'Refund', 'ninja-forms-paypal-express' );?></option>
								</select>
							</span>
						</div>
					</div>
				</div>
			</div>
			<?php			
		}
	} // function paypal_info_metabox

	/**
	 * Save our submission user values
	 * 
	 * @access public
	 * @since 1.0.7
	 * @return void
	 */
	public function save_paypal_info( $sub_id, $post ) {
		global $pagenow;

		if ( ! isset ( $_POST['nf_edit_sub'] ) || $_POST['nf_edit_sub'] != 1 )
			return $sub_id;

		// verify if this is an auto save routine.
		// If it is our form has not been submitted, so we dont want to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		  return $sub_id;

		if ( $pagenow != 'post.php' )
			return $sub_id;

		if ( $post->post_type != 'nf_sub' )
			return $sub_id;

		/* Get the post type object. */
		$post_type = get_post_type_object( $post->post_type );

		/* Check if the current user has permission to edit the post. */
		if ( !current_user_can( $post_type->cap->edit_post, $sub_id ) )
	    	return $sub_id;

        // Bail if the form doesn't have save progress enabled
	    $form_id = Ninja_Forms()->sub( $sub_id )->form_id;
	    if ( Ninja_Forms()->form( $form_id )->get_setting( 'paypal_express' ) != 1 )
	    	return false;

	    Ninja_Forms()->sub( $sub_id )->update_meta( '_paypal_status', $_POST['_paypal_status'] );

	} // function save_paypal_info
}

// Initiate our sub settings class if we are on the admin.
function ninja_forms_paypal_express_modify_sub(){
	if ( is_admin() ) {
		if ( nf_pe_pre_27() ) {
			$NF_Paypal_Subs = new NF_Paypal_Subs_Deprecated();
		} else {
			$NF_Paypal_Subs = new NF_Paypal_Subs();
		}
	}	
}

add_action( 'init', 'ninja_forms_paypal_express_modify_sub', 11 );