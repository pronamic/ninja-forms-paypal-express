<?php

function ninja_forms_paypal_express_activation(){
	if ( version_compare( NINJA_FORMS_VERSION, '2.7' ) == -1 ) {
		global $wpdb;
		if($wpdb->get_var("SHOW COLUMNS FROM ".NINJA_FORMS_SUBS_TABLE_NAME." LIKE 'paypal_status'") != 'paypal_status') {
			$sql = "ALTER TABLE ".NINJA_FORMS_SUBS_TABLE_NAME." ADD `paypal_status` VARCHAR(50) NULL";
			$wpdb->query($sql);		
			$sql = "ALTER TABLE ".NINJA_FORMS_SUBS_TABLE_NAME." ADD `paypal_transaction_id` VARCHAR(255) NULL";
			$wpdb->query($sql);		
			$sql = "ALTER TABLE ".NINJA_FORMS_SUBS_TABLE_NAME." ADD `paypal_total` VARCHAR(255) NULL";
			$wpdb->query($sql);
			$sql = "ALTER TABLE ".NINJA_FORMS_SUBS_TABLE_NAME." ADD `paypal_error` VARCHAR(255) NULL";
			$wpdb->query($sql);
		}		
	}

}