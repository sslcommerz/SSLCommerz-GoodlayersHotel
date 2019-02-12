<?php
	
if( !function_exists('gdlr_get_sslcommerz_form') ){
	function gdlr_get_sslcommerz_form($option){
		global $hotel_option, $wpdb;
		ob_start();

		$merchant_id = trim($hotel_option['sslcommerz-merchant-id']);
		$merchant_password = trim($hotel_option['sslcommerz-merchant-password']);
		
		$live_mode = empty($hotel_option['sslcommerz-live-mode'])? 'enable': $hotel_option['sslcommerz-live-mode']; 
		if( empty($live_mode) || $live_mode == 'enable' ){
			$environment = 'https://sandbox.sslcommerz.com/gwprocess/v3/api.php';
			$validation = 'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php';
		}else{
			$environment = 'https://securepay.sslcommerz.com/gwprocess/v3/api.php';
			$validation = 'https://securepay.sslcommerz.com/validator/api/validationserverAPI.php';
		}
		// print_r($option);
		$teanid = $option['invoice'];
		$sslcommerz_currency = $hotel_option['sslcommerz-currency'];
		if($sslcommerz_currency == 0)
		{
			$sslc_currency = 'BDT';
		}
		elseif ($sslcommerz_currency == 1) 
		{
			$sslc_currency = 'USD';
		}
		elseif ($sslcommerz_currency == 2) 
		{
			$sslc_currency = 'EUR';
		}

		$success_page = $hotel_option['sslcommerz-success-page'];
        $fail_page = $hotel_option['sslcommerz-fail-page'];
        $cancel_page = $hotel_option['sslcommerz-cancel-page'];

        $success_url = ($success_page =="" || $success_page == 0)?get_site_url() . "/":get_permalink($success_page);
        $fail_url = ($fail_page =="" || $fail_page == 0)?get_site_url() . "/":get_permalink($fail_page);
        $cancel_url = ($cancel_page =="" || $cancel_page == 0)?get_site_url() . "/":get_permalink($cancel_page);

		$temp_sql  = "SELECT * FROM " . $wpdb->prefix . "gdlr_hotel_payment ";
		$temp_sql .= "WHERE id = " . $teanid;	
		$result = $wpdb->get_row($temp_sql);
		// print_r($result);

		if( empty($result->pay_amount) )
		{
			$ret['status'] = 'failed';
			$ret['message'] = esc_html__('Cannot retrieve pricing data, please try again.', 'gdlr-hotel');
		}
		else
		{
			$price = intval(floatval($result->pay_amount) * 100) / 100;

			$post_data = array();
	        $post_data['store_id'] = $merchant_id;
	        $post_data['store_passwd'] = $merchant_password;
	        $post_data['currency'] = $sslc_currency;
	        $post_data['tran_id'] = $teanid;
	        $post_data['total_amount'] = $price;

	        # Customer Info===
	        
	        $post_data['cus_name'] =  $option['contact']['first_name'].' '.$option['contact']['last_name'];
	        $post_data['cus_email'] = $option['email'];
	        $post_data['cus_phone'] = $option['phone'];
	        
	        # END===
	        
	        $post_data['success_url'] = $success_url;
	        $post_data['fail_url'] = $fail_url;
	        $post_data['cancel_url'] = $cancel_url;
	        
	        $handle = curl_init();
	        curl_setopt($handle, CURLOPT_URL, $environment);
	        curl_setopt($handle, CURLOPT_POST, 1);
	        curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
	        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
	        $content = curl_exec($handle);
	        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
	        
	        if ($code == 200 && !( curl_errno($handle))) 
	        {
	            curl_close($handle);
	            $sslcommerzResponse = $content;
	            # PARSE THE JSON RESPONSE 
	            $sslcz = json_decode($sslcommerzResponse, true);
	            // echo "<pre>";
	            // print_r($sslcz);
	            
	            if (isset($sslcz['status']) && $sslcz['status'] == 'SUCCESS') 
	            {
	            	if(isset($sslcz['GatewayPageURL']) && $sslcz['GatewayPageURL'] != '')
	            	{
	            		$GatewayPageURL = $sslcz['GatewayPageURL'];
	            		echo "<form action='$GatewayPageURL' method='POST' class='gdlr-payment-form' id='payment-form'>
				<p align='center' style='font-size: 16px;color: blue;'>Submit Payment will redirect you to SSLCommerz....</p>
				<input type='submit' class='gdlr-form-button cyan' value='Submit Payment'></form>";
	            	}
	            }
	        }

			// try{
			// 	// Common setup for API credentials
			// 	if( $response != null )
			// 	{
			// 	    if( ($tresponse != null) && ($tresponse->getResponseCode() == '1') )
			// 	    {
			// 	      	$payment_info = array(
			// 				'payment_method' => 'sslcommerz',
			// 				'amount' => $price,
			// 				'transaction_id' => $tresponse->getTransId()
			// 			);
			// 			$wpdb->update( $wpdb->prefix . 'gdlr_hotel_payment', 
			// 				array('payment_status'=>'paid', 'payment_info'=>serialize($payment_info), 'payment_date'=>date('Y-m-d H:i:s')), 
			// 				array('id'=>$_POST['tid']), 
			// 				array('%s', '%s', '%s'), 
			// 				array('%d')
			// 			);
			// 			do_action('gdlr_update_transaction_availability', $_POST['tid']);
						
			// 			$contact_info = unserialize($result->contact_info);
			// 			$data = unserialize($result->booking_data);
			// 			$mail_content = gdlr_hotel_mail_content($contact_info, $data, $charge, array(
			// 				'total_price'=>$result->total_price, 'pay_amount'=>$result->pay_amount, 'booking_code'=>$result->customer_code)
			// 			);
			// 			gdlr_hotel_mail($contact_info['email'], __('Thank you for booking the room with us.', 'gdlr-hotel'), $mail_content);
			// 			gdlr_hotel_mail($hotel_option['recipient-mail'], __('New room booking received', 'gdlr-hotel'), $mail_content);

			// 			$ret['status'] = 'success';
			// 			$ret['message'] = __('Payment complete.', 'gdlr-hotel');
			// 			$ret['content'] = gdlr_booking_complete_message();
			// 	    }else{
			// 	        $ret['status'] = 'failed';
			// 	    	$ret['message'] = esc_html__('Cannot charge credit card, please check your card credentials again.', 'gdlr-hotel');

			// 	    	$error = $tresponse->getErrors();
			// 	    	if( !empty($error[0]) ){
			// 		    	$ret['message'] = $error[0]->getErrorText();
			// 	    	}

			// 	   	}
			// 	}else{
			// 	    $ret['status'] = 'failed';
			// 	    $ret['message'] = esc_html__('No response returned, please try again.', 'gdlr-hotel');
			// 	}
			// 	$ret['data'] = $_POST;

			// }catch( Exception $e ){
			// 	$ret['status'] = 'failed';
			// 	$ret['message'] = $e->getMessage();
			// }
		}
?>

<?php	
			$sslcommerz_form = ob_get_contents();
			ob_end_clean();
			return $sslcommerz_form;
		}
	}
	
?>