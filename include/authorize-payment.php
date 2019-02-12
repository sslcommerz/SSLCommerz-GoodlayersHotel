<?php
	
	if( !function_exists('gdlr_get_authorize_form') ){
		function gdlr_get_authorize_form($option){
			global $hotel_option;
			
			ob_start();
?>
<form action="" method="POST" class="gdlr-payment-form" id="payment-form" data-ajax="<?php echo AJAX_URL; ?>" data-invoice="<?php echo $option['invoice']; ?>" >
	<p class="gdlr-form-half-left">
		<label><span><?php _e('Card Number', 'gdlr-hotel'); ?></span></label>
		<input type="text" size="20" data-authorize="number"/>
	</p>
	<div class="clear" ></div>
	
	<p class="gdlr-form-half-left">
		<label><span><?php _e('CVC', 'gdlr-hotel'); ?></span></label>
		<input type="text" size="4" data-authorize="cvc"/>
	</p>
	<div class="clear" ></div>

	<p class="gdlr-form-half-left gdlr-form-expiration">
		<label><span><?php _e('Expiration (MM/YYYY)', 'gdlr-hotel'); ?></span></label>
		<input type="text" size="2" data-authorize="exp-month"/>
		<span class="gdlr-separator" >/</span>
		<input type="text" size="4" data-authorize="exp-year"/>
	</p>
	<div class="clear" ></div>
	<div class="gdlr-form-error payment-errors" style="display: none;"></div>
	<div class="gdlr-form-loading gdlr-form-instant-payment-loading"><?php _e('loading', 'gdlr-hotel'); ?></div>
	<div class="gdlr-form-notice gdlr-form-instant-payment-notice"></div>
	<input type="submit" class="gdlr-form-button cyan" value="<?php _e('Submit Payment', 'gdlr-hotel'); ?>" >
</form>
<script type="text/javascript">
	(function($){
		var form = $('#payment-form');

		function goodlayersAuthorizeCharge(){

			var tid = form.attr('data-invoice');
			var form_value = {};
			form.find('[data-authorize]').each(function(){
				form_value[$(this).attr('data-authorize')] = $(this).val(); 
			});

			$.ajax({
				type: 'POST',
				url: form.attr('data-ajax'),
				data: { 'action':'gdlr_hotel_authorize_payment', 'tid': tid, 'form': form_value },
				dataType: 'json',
				error: function(a, b, c){ 
					console.log(a, b, c); 

					// display error messages
					form.find('.payment-errors').text('<?php echo esc_html__('An error occurs, please refresh the page to try again.', 'gdlr-hotel'); ?>').slideDown(200);
					form.find('input[type="submit"]').prop('disabled', false).removeClass('now-loading'); 
				},
				success: function(data){
					if( data.content ){
						$('#gdlr-booking-content-inner').fadeOut(function(){
							$(this).html(data.content).fadeIn();
						});
						$('#gdlr-booking-process-bar').children('[data-process=4]').addClass('gdlr-active').siblings().removeClass('gdlr-active');
					}else{
						form.find('.gdlr-form-loading').slideUp();
						form.find('.gdlr-form-notice').removeClass('success failed')
							.addClass(data.status).html(data.message).slideDown();
						
						if( data.status == 'failed' ){
							form.find('input[type="submit"]').prop('disabled', false);
						}
					}
				}
			});	
		};
		
		form.submit(function(event){
		
			var req = false;
			form.find('input').each(function(){
				if( !$(this).val() ){
					req = true;
				}
			});

			if( req ){
				form.find('.payment-errors').text('<?php _e('Please fill all required fields', 'gdlr-hotel'); ?>').slideDown();
			}else{
				form.find('input[type="submit"]').prop('disabled', true);
				form.find('.payment-errors, .gdlr-form-notice').slideUp();
				form.find('.gdlr-form-loading').slideDown();

				goodlayersAuthorizeCharge();
			}

			return false;
		});
	})(jQuery);
</script>
<?php	
			$authorize_form = ob_get_contents();
			ob_end_clean();
			return $authorize_form;
		}
	}
	
	add_action( 'wp_ajax_gdlr_hotel_authorize_payment', 'gdlr_hotel_authorize_payment' );
	add_action( 'wp_ajax_nopriv_gdlr_hotel_authorize_payment', 'gdlr_hotel_authorize_payment' );
	if( !function_exists('gdlr_hotel_authorize_payment') ){
		function gdlr_hotel_authorize_payment(){

			global $hotel_option, $wpdb;

			$ret = array();

			if( !empty($_POST['tid']) && !empty($_POST['form']) ){

				// prepare data
				$form = stripslashes_deep($_POST['form']);

				$api_id = trim($hotel_option['authorize-api-id']);
				$transaction_key = trim($hotel_option['authorize-transaction-key']);
				
				$live_mode = empty($hotel_option['authorize-live-mode'])? 'enable': $hotel_option['authorize-live-mode']; 
				if( empty($live_mode) || $live_mode == 'enable' ){
					$environment = \net\authorize\api\constants\ANetEnvironment::PRODUCTION;
				}else{
					$environment = \net\authorize\api\constants\ANetEnvironment::SANDBOX;
				}

				$temp_sql  = "SELECT * FROM " . $wpdb->prefix . "gdlr_hotel_payment ";
				$temp_sql .= "WHERE id = " . $_POST['tid'];	
				$result = $wpdb->get_row($temp_sql);

				if( empty($result->pay_amount) ){
					$ret['status'] = 'failed';
					$ret['message'] = esc_html__('Cannot retrieve pricing data, please try again.', 'gdlr-hotel');
				
				// Start the payment process
				}else{

					$price = intval(floatval($result->pay_amount) * 100) / 100;

					try{
						// Common setup for API credentials
						$merchantAuthentication = new net\authorize\api\contract\v1\MerchantAuthenticationType();
						$merchantAuthentication->setName(trim($api_id));
						$merchantAuthentication->setTransactionKey(trim($transaction_key));

						// Create the payment data for a credit card
						$creditCard = new net\authorize\api\contract\v1\CreditCardType();
						$creditCard->setCardNumber($form['number']);
						$creditCard->setExpirationDate($form['exp-year'] . '-' . $form['exp-month']);
						$creditCard->setCardCode($form['cvc']);
						$paymentOne = new net\authorize\api\contract\v1\PaymentType();
						$paymentOne->setCreditCard($creditCard);

						// Create transaction
						$transactionRequestType = new net\authorize\api\contract\v1\TransactionRequestType();
						$transactionRequestType->setTransactionType("authCaptureTransaction"); 
						$transactionRequestType->setAmount($price);
						$transactionRequestType->setPayment($paymentOne);

						// Send request
						$request = new net\authorize\api\contract\v1\CreateTransactionRequest();
						$request->setMerchantAuthentication($merchantAuthentication);
						$request->setTransactionRequest($transactionRequestType);
						$controller = new net\authorize\api\controller\CreateTransactionController($request);
						$response = $controller->executeWithApiResponse($environment);
						
						if( $response != null ){
						    $tresponse = $response->getTransactionResponse();

						    if( ($tresponse != null) && ($tresponse->getResponseCode() == '1') ){
						      	
						      	$payment_info = array(
									'payment_method' => 'authorize',
									'amount' => $price,
									'transaction_id' => $tresponse->getTransId()
								);
								$wpdb->update( $wpdb->prefix . 'gdlr_hotel_payment', 
									array('payment_status'=>'paid', 'payment_info'=>serialize($payment_info), 'payment_date'=>date('Y-m-d H:i:s')), 
									array('id'=>$_POST['tid']), 
									array('%s', '%s', '%s'), 
									array('%d')
								);
								do_action('gdlr_update_transaction_availability', $_POST['tid']);
								
								$contact_info = unserialize($result->contact_info);
								$data = unserialize($result->booking_data);
								$mail_content = gdlr_hotel_mail_content($contact_info, $data, $charge, array(
									'total_price'=>$result->total_price, 'pay_amount'=>$result->pay_amount, 'booking_code'=>$result->customer_code)
								);
								gdlr_hotel_mail($contact_info['email'], __('Thank you for booking the room with us.', 'gdlr-hotel'), $mail_content);
								gdlr_hotel_mail($hotel_option['recipient-mail'], __('New room booking received', 'gdlr-hotel'), $mail_content);

								$ret['status'] = 'success';
								$ret['message'] = __('Payment complete.', 'gdlr-hotel');
								$ret['content'] = gdlr_booking_complete_message();
						    }else{
						        $ret['status'] = 'failed';
						    	$ret['message'] = esc_html__('Cannot charge credit card, please check your card credentials again.', 'gdlr-hotel');

						    	$error = $tresponse->getErrors();
						    	if( !empty($error[0]) ){
							    	$ret['message'] = $error[0]->getErrorText();
						    	}

						   	}
						}else{
						    $ret['status'] = 'failed';
						    $ret['message'] = esc_html__('No response returned, please try again.', 'gdlr-hotel');
						}
						$ret['data'] = $_POST;

					}catch( Exception $e ){
						$ret['status'] = 'failed';
						$ret['message'] = $e->getMessage();
					}
				}
			}

			die(json_encode($ret));
		}
	}
	
?>