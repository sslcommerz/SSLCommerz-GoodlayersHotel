<?php
	/*	
	*	Payment Plugin
	*	---------------------------------------------------------------------
	*	creating the authorize payment option
	*	---------------------------------------------------------------------
	*/

	add_filter('goodlayers_credit_card_payment_gateway_options', 'goodlayers_authorize_payment_gateway_options');
	if( !function_exists('goodlayers_authorize_payment_gateway_options') ){
		function goodlayers_authorize_payment_gateway_options( $options ){
			$options['authorize'] = esc_html__('Authorize', 'tourmaster'); 

			return $options;
		}
	}		

	// init the script on payment page head
	add_filter('goodlayers_plugin_payment_option', 'goodlayers_authorize_payment_option');
	if( !function_exists('goodlayers_authorize_payment_option') ){
		function goodlayers_authorize_payment_option( $options ){

			$options['authorize'] = array(
				'title' => esc_html__('Authorize', 'tourmaster'),
				'options' => array(
					'authorize-live-mode' => array(
						'title' => __('Live Mode ', 'tourmaster'),
						'type' => 'checkbox',
						'default' => 'disable',
						'description' => __('Please turn this option off when you\'re on test mode.','tourmaster')
					),
					'authorize-api-id' => array(
						'title' => __('Authorize API Login ID', 'tourmaster'),
						'type' => 'text'
					),
					'authorize-transaction-key' => array(
						'title' => __('Authorize Transaction Key', 'tourmaster'),
						'type' => 'text'
					),
				)
			);

			return $options;
		} // goodlayers_authorize_payment_option
	}

	$current_payment_gateway = apply_filters('goodlayers_payment_get_option', '', 'credit-card-payment-gateway');
	if( $current_payment_gateway == 'authorize' ){
		include_once(TOURMASTER_LOCAL . '/include/authorize/autoload.php');

		add_filter('goodlayers_plugin_payment_attribute', 'goodlayers_authorize_payment_attribute');	
		add_filter('goodlayers_authorize_payment_form', 'goodlayers_authorize_payment_form', 10, 2);

		add_action('wp_ajax_authorize_payment_charge', 'goodlayers_authorize_payment_charge');
		add_action('wp_ajax_nopriv_authorize_payment_charge', 'goodlayers_authorize_payment_charge');
	}

	// add attribute for payment button
	if( !function_exists('goodlayers_authorize_payment_attribute') ){
		function goodlayers_authorize_payment_attribute( $attributes ){
			return array('method' => 'ajax', 'type' => 'authorize');
		}
	}


	// payment form
	if( !function_exists('goodlayers_authorize_payment_form') ){
		function goodlayers_authorize_payment_form( $ret = '', $tid = '' ){
			ob_start();
?>
<div class="goodlayers-payment-form goodlayers-with-border" >
	<form action="" method="POST" id="goodlayers-authorize-payment-form" data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" >
		<div class="goodlayers-payment-form-field">
			<label>
				<span class="goodlayers-payment-field-head" ><?php esc_html_e('Card Number', 'tourmaster'); ?></span>
				<input type="text" data-authorize="number">
			</label>
		</div>
		<div class="goodlayers-payment-form-field">
			<label>
				<span class="goodlayers-payment-field-head" ><?php esc_html_e('Expiration (MM/YY)', 'tourmaster'); ?></span>
				<input class="goodlayers-size-small" type="text" size="2" data-authorize="exp_month" />
			</label>
			<span class="goodlayers-separator" >/</span>
			<input class="goodlayers-size-small" type="text" size="2" data-authorize="exp_year" />
		</div>
		<div class="goodlayers-payment-form-field">
			<label>
				<span class="goodlayers-payment-field-head" ><?php esc_html_e('CVC', 'tourmaster'); ?></span>
				<input class="goodlayers-size-small" type="text" size="4" data-authorize="cvc" />
			</label>
		</div>
		<div class="now-loading"></div>
		<div class="payment-errors"></div>
		<div class="goodlayers-payment-req-field" ><?php esc_html_e('Please fill all required fields', 'tourmaster'); ?></div>
		<input type="hidden" name="tid" value="<?php echo esc_attr($tid) ?>" />
		<input class="goodlayers-payment-button submit" type="submit" value="<?php esc_html_e('Submit Payment', 'tourmaster'); ?>" />
		
		<!-- for proceeding to last step -->
		<div class="goodlayers-payment-plugin-complete" ></div>
	</form>
</div>
<script type="text/javascript">
	(function($){
		var form = $('#goodlayers-authorize-payment-form');

		function goodlayersAuthorizeCharge(){

			var tid = form.find('input[name="tid"]').val();
			var form_value = {};
			form.find('[data-authorize]').each(function(){
				form_value[$(this).attr('data-authorize')] = $(this).val(); 
			});

			$.ajax({
				type: 'POST',
				url: form.attr('data-ajax-url'),
				data: { 'action':'authorize_payment_charge', 'tid': tid, 'form': form_value },
				dataType: 'json',
				error: function(a, b, c){ 
					console.log(a, b, c); 

					// display error messages
					form.find('.payment-errors').text('<?php echo esc_html__('An error occurs, please refresh the page to try again.', 'tourmaster'); ?>').slideDown(200);
					form.find('.submit').prop('disabled', false).removeClass('now-loading'); 
				},
				success: function(data){
					if( data.status == 'success' ){
						form.find('.goodlayers-payment-plugin-complete').trigger('click');
					}else if( typeof(data.message) != 'undefined' ){
						form.find('.payment-errors').text(data.message).slideDown(200);
					}

					form.find('.submit').prop('disabled', false).removeClass('now-loading'); 
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
				form.find('.goodlayers-payment-req-field').slideDown(200)
			}else{
				form.find('.submit').prop('disabled', true).addClass('now-loading');
				form.find('.payment-errors, .goodlayers-payment-req-field').slideUp(200);
				goodlayersAuthorizeCharge();
			}

			return false;
		});
	})(jQuery);
</script>
<?php
			$ret = ob_get_contents();
			ob_end_clean();
			return $ret;
		}
	}

	// ajax for payment submission
	if( !function_exists('goodlayers_authorize_payment_charge') ){
		function goodlayers_authorize_payment_charge(){

			$ret = array();

			if( !empty($_POST['tid']) && !empty($_POST['form']) ){

				// prepare data
				$form = stripslashes_deep($_POST['form']);

				$api_id = apply_filters('goodlayers_payment_get_option', '', 'authorize-api-id');
				$transaction_key = trim(apply_filters('goodlayers_payment_get_option', '', 'authorize-transaction-key'));
				
				$live_mode = apply_filters('goodlayers_payment_get_option', '', 'authorize-live-mode');
				if( empty($live_mode) || $live_mode == 'enable' ){
					$environment = \net\authorize\api\constants\ANetEnvironment::PRODUCTION;
				}else{
					$environment = \net\authorize\api\constants\ANetEnvironment::SANDBOX;
				}

				$t_data = apply_filters('goodlayers_payment_get_transaction_data', array(), $_POST['tid'], array('price', 'email'));

				if( empty($t_data['price']) ){
					$ret['status'] = 'failed';
					$ret['message'] = esc_html__('Cannot retrieve pricing data, please try again.', 'tourmaster');
				
				// Start the payment process
				}else{

					$price = intval(floatval($t_data['price']) * 100) / 100;

					try{
						// Common setup for API credentials
						$merchantAuthentication = new net\authorize\api\contract\v1\MerchantAuthenticationType();
						$merchantAuthentication->setName(trim($api_id));
						$merchantAuthentication->setTransactionKey(trim($transaction_key));

						// Create the payment data for a credit card
						$creditCard = new net\authorize\api\contract\v1\CreditCardType();
						$creditCard->setCardNumber($form['number']);
						$creditCard->setExpirationDate($form['exp_year'] . '-' . $form['exp_month']);
						$creditCard->setCardCode($form['ccv']);
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
								$ret['status'] = 'failed';
								$ret['message'] = $tresponse->getTransId();
								do_action('goodlayers_set_payment_complete', $_POST['tid'], $payment_info);

								$ret['status'] = 'success';
						    }else{
						        $ret['status'] = 'failed';
						    	$ret['message'] = esc_html__('Cannot charge credit card, please check your card credentials again.', 'tourmaster');
						    }
						}else{
						    $ret['status'] = 'failed';
						    $ret['message'] = esc_html__('No response returned, please try again.', 'tourmaster');
						}
						$ret['data'] = $_POST;

					}catch( Exception $e ){
						$ret['status'] = 'failed';
						$ret['message'] = $e->getMessage();
					}
				}
			}

			die(json_encode($ret));

		} // goodlayers_authorize_payment_charge
	}
