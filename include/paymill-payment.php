<?php

	add_action( 'wp_enqueue_scripts', 'gdlr_include_paymill_payment_script' );
	if( !function_exists('gdlr_include_paymill_payment_script') ){
		function gdlr_include_paymill_payment_script(){
			global $hotel_option;
			if( isset($_GET[$hotel_option['booking-slug']]) ){
				wp_enqueue_script('paymill', 'https://bridge.paymill.com/');
			}
		}
	}
	
	if( !function_exists('gdlr_get_paymill_form') ){
		function gdlr_get_paymill_form($option){
			global $hotel_option;

			ob_start();
?>
<form action="" method="POST" class="gdlr-payment-form" id="payment-form" data-ajax="<?php echo AJAX_URL; ?>" data-invoice="<?php echo $option['invoice']; ?>" >
	<p class="gdlr-form-half-left">
		<label><span><?php _e('Card Number', 'gdlr-hotel'); ?></span></label>
		<input type="text" size="20" class="card-number" />
	</p>
	<div class="clear" ></div>
	
	<p class="gdlr-form-half-left">
		<label><span><?php _e('CVC', 'gdlr-hotel'); ?></span></label>
		<input type="text" size="4" class="card-cvc" />
	</p>
	<div class="clear" ></div>

	<p class="gdlr-form-half-left gdlr-form-expiration">
		<label><span><?php _e('Expiration (MM/YYYY)', 'gdlr-hotel'); ?></span></label>
		<input type="text" size="2" class="card-expiry-month" />
		<span class="gdlr-separator" >/</span>
		<input type="text" size="4" class="card-expiry-year" />
	</p>
	<div class="clear" ></div>
	<div class="gdlr-form-error payment-errors" style="display: none;"></div>
	<div class="gdlr-form-loading gdlr-form-instant-payment-loading"><?php _e('loading', 'gdlr-hotel'); ?></div>
	<div class="gdlr-form-notice gdlr-form-instant-payment-notice"></div>
	<input type="submit" class="gdlr-form-button cyan" value="<?php _e('Submit Payment', 'gdlr-hotel'); ?>" >
</form>
<script type="text/javascript">
var PAYMILL_PUBLIC_KEY = '<?php echo $hotel_option['paymill-public-key']; ?>';

jQuery(function($){
	function PaymillResponseHandler(error, result) {
		var form = $('#payment-form');

		if(error){
			form.find('.payment-errors').text(error.apierror).slideDown();
			form.find('input[type="submit"]').prop('disabled', false);
			form.find('.gdlr-form-loading').slideUp();
		}else{
			// response contains id and card, which contains additional card details
			$.ajax({
				type: 'POST',
				url: form.attr('data-ajax'),
				data: {'action':'gdlr_hotel_paymill_payment','token': result.token, 'invoice': form.attr('data-invoice')},
				dataType: 'json',
				error: function(a, b, c){ 
					console.log(a, b, c); 
					form.find('.gdlr-form-loading').slideUp(); 
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
		}
	}	

	$('#payment-form').submit(function(event){
		var form = $(this);

		// Disable the submit button to prevent repeated clicks
		form.find('input[type="submit"]').prop('disabled', true);
		form.find('.payment-errors, .gdlr-form-notice').slideUp();
		form.find('.gdlr-form-loading').slideDown();
		
		paymill.createToken({
			number: $('.card-number').val(), 
			exp_month: $('.card-expiry-month').val(),   
			exp_year: $('.card-expiry-year').val(),     
			cvc: $('.card-cvc').val()   
		}, PaymillResponseHandler);                 

		// Prevent the form from submitting with the default action
		return false;
	});
});
</script>
<?php	
			$paymill_form = ob_get_contents();
			ob_end_clean();
			return $paymill_form;
		}
	}
	
	add_action( 'wp_ajax_gdlr_hotel_paymill_payment', 'gdlr_hotel_paymill_payment' );
	add_action( 'wp_ajax_nopriv_gdlr_hotel_paymill_payment', 'gdlr_hotel_paymill_payment' );
	if( !function_exists('gdlr_hotel_paymill_payment') ){
		function gdlr_hotel_paymill_payment(){	
			global $hotel_option;
		
			$ret = array();
			
			if( !empty($_POST['token']) && !empty($_POST['invoice']) ){
				global $wpdb;

				$temp_sql  = "SELECT * FROM " . $wpdb->prefix . "gdlr_hotel_payment ";
				$temp_sql .= "WHERE id = " . $_POST['invoice'];	
				$result = $wpdb->get_row($temp_sql);

				$contact_info = unserialize($result->contact_info);
				
				$apiKey = $hotel_option['paymill-private-key'];
				$request = new Paymill\Request($apiKey);
				
				$payment = new Paymill\Models\Request\Payment();
				$payment->setToken($_POST['token']);
				
				try{
					$response  = $request->create($payment);
					$paymentId = $response->getId();
					
					$transaction = new Paymill\Models\Request\Transaction();
					$transaction->setAmount(floatval($result->pay_amount) * 100)
								->setCurrency($hotel_option['paymill-currency-code'])
								->setPayment($paymentId)
								->setDescription($payment_info['email']);

					$response = $request->create($transaction);
					
					$wpdb->update( $wpdb->prefix . 'gdlr_hotel_payment', 
						array('payment_status'=>'paid', 'payment_info'=>serialize($response), 'payment_date'=>date('Y-m-d H:i:s')), 
						array('id'=>$_POST['invoice']), 
						array('%s', '%s', '%s'), 
						array('%d')
					);

					do_action('gdlr_update_transaction_availability', $_POST['invoice']);
					
					$data = unserialize($result->booking_data);
					$mail_content = gdlr_hotel_mail_content($contact_info, $data, $response, array(
						'total_price'=>$result->total_price, 'pay_amount'=>$result->pay_amount, 'booking_code'=>$result->customer_code)
					);
					gdlr_hotel_mail($contact_info['email'], __('Thank you for booking the room with us.', 'gdlr-hotel'), $mail_content);
					gdlr_hotel_mail($hotel_option['recipient-mail'], __('New room booking received', 'gdlr-hotel'), $mail_content);

					$ret['status'] = 'success';
					$ret['message'] = __('Payment complete', 'gdlr-hotel');
					$ret['content'] = gdlr_booking_complete_message();
				}catch(PaymillException $e) {
					$ret['status'] = 'failed';
					$ret['message'] = $e->getErrorMessage();
				}
			}else{
				$ret['status'] = 'failed';
				$ret['message'] = __('Failed to proceed, please try again.', 'gdlr-hotel');	
			}
			
			die(json_encode($ret));
		}
	}
?>