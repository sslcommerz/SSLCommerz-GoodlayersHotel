<?php

	add_action( 'wp_enqueue_scripts', 'gdlr_include_stripe_payment_script' );
	if( !function_exists('gdlr_include_stripe_payment_script') ){
		function gdlr_include_stripe_payment_script(){
			global $hotel_option;
			if( isset($_GET[$hotel_option['booking-slug']]) ){
				wp_enqueue_script('stripe', 'https://js.stripe.com/v2/');
			}
		}
	}
	
	if( !function_exists('gdlr_get_stripe_form') ){
		function gdlr_get_stripe_form($option){
			global $hotel_option;
			
			ob_start();
?>
<form action="" method="POST" class="gdlr-payment-form" id="payment-form" data-ajax="<?php echo AJAX_URL; ?>" data-invoice="<?php echo $option['invoice']; ?>" >
	<p class="gdlr-form-half-left">
		<label><span><?php _e('Card Holder Name', 'gdlr-hotel'); ?></span></label>
		<input type="text" size="20" data-stripe="name"/>
	</p>
	<div class="clear" ></div>

	<p class="gdlr-form-half-left">
		<label><span><?php _e('Card Number', 'gdlr-hotel'); ?></span></label>
		<input type="text" size="20" data-stripe="number"/>
	</p>
	<div class="clear" ></div>
	
	<p class="gdlr-form-half-left">
		<label><span><?php _e('CVC', 'gdlr-hotel'); ?></span></label>
		<input type="text" size="4" data-stripe="cvc"/>
	</p>
	<div class="clear" ></div>

	<p class="gdlr-form-half-left gdlr-form-expiration">
		<label><span><?php _e('Expiration (MM/YYYY)', 'gdlr-hotel'); ?></span></label>
		<input type="text" size="2" data-stripe="exp-month"/>
		<span class="gdlr-separator" >/</span>
		<input type="text" size="4" data-stripe="exp-year"/>
	</p>
	<div class="clear" ></div>
	<div class="gdlr-form-error payment-errors" style="display: none;"></div>
	<div class="gdlr-form-loading gdlr-form-instant-payment-loading"><?php _e('loading', 'gdlr-hotel'); ?></div>
	<div class="gdlr-form-notice gdlr-form-instant-payment-notice"></div>
	<input type="submit" class="gdlr-form-button cyan" value="<?php _e('Submit Payment', 'gdlr-hotel'); ?>" >
</form>
<script type="text/javascript">
Stripe.setPublishableKey('<?php echo trim($hotel_option['stripe-publishable-key']); ?>');

jQuery(function($){
	function stripeResponseHandler(status, response) {
		var form = $('#payment-form');

		if (response.error) {
			var error_message = '';
			switch(response.error.code) {
				case 'incorrect_number': error_message = "<?php _e('The card number is incorrect.', 'gdlr-hotel'); ?>"; break;
				case 'invalid_number': error_message = "<?php _e('The card number is not a valid credit card number.', 'gdlr-hotel'); ?>"; break;
				case 'invalid_expiry_month': error_message = "<?php _e('The card\'s expiration month is invalid.', 'gdlr-hotel'); ?>"; break;
				case 'invalid_expiry_year': error_message = "<?php _e('The card\'s expiration year is invalid.', 'gdlr-hotel'); ?>"; break;
				case 'invalid_cvc': error_message = "<?php _e('The card\'s security code is invalid.', 'gdlr-hotel'); ?>"; break;
				case 'expired_card': error_message = "<?php _e('The card has expired.', 'gdlr-hotel'); ?>"; break;
				case 'incorrect_cvc': error_message = "<?php _e('The card\'s security code is incorrect.', 'gdlr-hotel'); ?>"; break;
				case 'incorrect_zip': error_message = "<?php _e('The card\'s zip code failed validation.', 'gdlr-hotel'); ?>"; break;
				case 'card_declined': error_message = "<?php _e('The card was declined.', 'gdlr-hotel'); ?>"; break;
				case 'missing': error_message = "<?php _e('There is no card on a customer that is being charged.', 'gdlr-hotel'); ?>"; break;
				case 'processing_error': error_message = "<?php _e('An error occurred while processing the card.', 'gdlr-hotel'); ?>"; break;
				case 'rate_limit':  error_message = "<?php _e('An error occurred due to requests hitting the API too quickly. Please let us know if you\'re consistently running into this error.', 'gdlr-hotel'); ?>"; break;
			    default: error_message = response.error.message;
			}

			form.find('.payment-errors').text(error_message).slideDown();
			form.find('input[type="submit"]').prop('disabled', false);
			form.find('.gdlr-form-loading').slideUp();
		}else{
			// response contains id and card, which contains additional card details
			$.ajax({
				type: 'POST',
				url: form.attr('data-ajax'),
				data: {'action':'gdlr_hotel_stripe_payment','token': response.id, 'invoice': form.attr('data-invoice')},
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

		if( $(this).find('[data-stripe="name"]').val() == "" ){
			form.find('.payment-errors').text('<?php _e('Please fill the card holder name', 'gdlr-lms'); ?>').slideDown();
			return false;
		}		
		
		// Disable the submit button to prevent repeated clicks
		form.find('input[type="submit"]').prop('disabled', true);
		form.find('.payment-errors, .gdlr-form-notice').slideUp();
		form.find('.gdlr-form-loading').slideDown();
		
		Stripe.card.createToken(form, stripeResponseHandler);

		// Prevent the form from submitting with the default action
		return false;
	});
});
</script>
<?php	
			$stripe_form = ob_get_contents();
			ob_end_clean();
			return $stripe_form;
		}
	}
	
	add_action( 'wp_ajax_gdlr_hotel_stripe_payment', 'gdlr_hotel_stripe_payment' );
	add_action( 'wp_ajax_nopriv_gdlr_hotel_stripe_payment', 'gdlr_hotel_stripe_payment' );
	if( !function_exists('gdlr_hotel_stripe_payment') ){
		function gdlr_hotel_stripe_payment(){
			global $hotel_option;
		
			$ret = array();
			Stripe::setApiKey(trim($hotel_option['stripe-secret-key']));
			
			if( !empty($_POST['token']) && !empty($_POST['invoice']) ){
				global $wpdb;

				$temp_sql  = "SELECT * FROM " . $wpdb->prefix . "gdlr_hotel_payment ";
				$temp_sql .= "WHERE id = " . $_POST['invoice'];	
				$result = $wpdb->get_row($temp_sql);
				
				$contact_info = unserialize($result->contact_info);
				
				try{
					$charge = Stripe_Charge::create(array(
					  "amount" => intval(floatval($result->pay_amount) * 100),
					  "currency" => $hotel_option['stripe-currency-code'],
					  "card" => $_POST['token'],
					  "description" => $contact_info['email']
					));
					
					$wpdb->update( $wpdb->prefix . 'gdlr_hotel_payment', 
						array('payment_status'=>'paid', 'payment_info'=>serialize($charge), 'payment_date'=>date('Y-m-d H:i:s')), 
						array('id'=>$_POST['invoice']), 
						array('%s', '%s', '%s'), 
						array('%d')
					);

					do_action('gdlr_update_transaction_availability', $_POST['invoice']);
					
					$data = unserialize($result->booking_data);
					$mail_content = gdlr_hotel_mail_content($contact_info, $data, $charge, array(
						'total_price'=>$result->total_price, 'pay_amount'=>$result->pay_amount, 'booking_code'=>$result->customer_code)
					);
					gdlr_hotel_mail($contact_info['email'], __('Thank you for booking the room with us.', 'gdlr-hotel'), $mail_content);
					gdlr_hotel_mail($hotel_option['recipient-mail'], __('New room booking received', 'gdlr-hotel'), $mail_content);

					$ret['status'] = 'success';
					$ret['message'] = __('Payment complete.', 'gdlr-hotel');
					$ret['content'] = gdlr_booking_complete_message();
				}catch(Stripe_CardError $e) {
					$ret['status'] = 'failed';
					$ret['message'] = $e->getMessage();
				}
			}else{
				$ret['status'] = 'failed';
				$ret['message'] = __('Failed to proceed, please try again.', 'gdlr-hotel');	
			}
			
			die(json_encode($ret));
		}
	}
	
?>