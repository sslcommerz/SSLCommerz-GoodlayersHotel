<?php
	/*
	**Template Name: SSLCommerz IPN
	*/ 
	global $hotel_option, $wpdb;

	$tran_id = $_POST['tran_id'];
	$val_id = $_POST['val_id'];
	$merchant_id = trim($hotel_option['sslcommerz-merchant-id']);
	$merchant_password = trim($hotel_option['sslcommerz-merchant-password']);

	$live_mode = empty($hotel_option['sslcommerz-live-mode'])? 'enable': $hotel_option['sslcommerz-live-mode']; 
	if( empty($live_mode) || $live_mode == 'enable' )
	{
		$environment = 'https://sandbox.sslcommerz.com/gwprocess/v3/api.php';
		$validation = 'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php';
	}
	else
	{
		$environment = 'https://securepay.sslcommerz.com/gwprocess/v3/api.php';
		$validation = 'https://securepay.sslcommerz.com/validator/api/validationserverAPI.php';
	}
	if(empty($val_id) && empty($tran_id))
	{
		echo "No IPN Request Received";
	}

	$table_name = $wpdb->prefix . 'gdlr_hotel_payment';

    $results = $wpdb->get_results('SELECT * FROM ' . $table_name . ' WHERE id = "'.$tran_id.'" ORDER BY id DESC', ARRAY_A);

	function _SSLCOMMERZ_hash_varify($store_passwd = "") 
	{
	    if (isset($_POST) && isset($_POST['verify_sign']) && isset($_POST['verify_key'])) 
	    {
	        # NEW ARRAY DECLARED TO TAKE VALUE OF ALL POST
	        $pre_define_key = explode(',', $_POST['verify_key']);
	        $new_data = array();
	        if (!empty($pre_define_key)) 
	        {
	            foreach ($pre_define_key as $value) 
	            {
	                if (isset($_POST[$value])) 
	                {
	                    $new_data[$value] = ($_POST[$value]);
	                }
	            }
	        }
	        # ADD MD5 OF STORE PASSWORD
	        $new_data['store_passwd'] = md5($store_passwd);

	        # SORT THE KEY AS BEFORE
	        ksort($new_data);
	        $hash_string = "";
	        foreach ($new_data as $key => $value) 
	        {
	            $hash_string .= $key . '=' . ($value) . '&';
	        }

	        $hash_string = rtrim($hash_string, '&');

	        if (md5($hash_string) == $_POST['verify_sign']) 
	        {
	            return true;
	        } 
	        else 
	        {
	            return false;
	        }
	    } 
	    else
	    {
	        return false;
	    }
	}


	if (_SSLCOMMERZ_hash_varify($merchant_password) && !empty($_POST)) 
	{
	    $val_id = urlencode($_POST['val_id']);
	    $store_id = urlencode($merchant_id);
	    $store_passwd = urlencode($merchant_password);
	    $requested_url = ($validation . "?val_id=" . $val_id . "&store_id=" . $store_id . "&store_passwd=" . $store_passwd . "&v=1&format=json");
	    $handle = curl_init();
	    curl_setopt($handle, CURLOPT_URL, $requested_url);
	    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
	    $result = curl_exec($handle);

	    $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

	    if ($code == 200 && !( curl_errno($handle))) 
	    {
	        # TO CONVERT AS ARRAY
	        # $result = json_decode($result, true);
	        # $status = $result['status'];	
	        # TO CONVERT AS OBJECT

	        $result = json_decode($result);
	        
	        $payment_info = array(
				'payment_method' => 'sslcommerz',
				'amount' => $amount,
				'transaction_id' => $tran_id,
				'IPN' => 'Triggered Successfully'
			);
			if(($results[0]['payment_status'] == 'pending') && ($results[0]['pay_amount'] == $_POST['currency_amount']))
			{
				$wpdb->update( $wpdb->prefix . 'gdlr_hotel_payment', 
					array('payment_status'=>'processing', 'payment_info'=>serialize($payment_info), 'payment_date'=>date('Y-m-d H:i:s')), 
					array('id'=>$tran_id), 
					array('%s', '%s', '%s'), 
					array('%d')
				);
				do_action('gdlr_update_transaction_availability', $tran_id);
				echo "IPN & Hash Validation Successful";
			}
	    }
	    else 
	    {
	        echo "Failed to connect with SSLCOMMERZ";
	    }
	} 
	else 
	{
    	echo "Hash validation failed.";
    }
?>