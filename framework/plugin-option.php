<?php
	/*	
	*	Goodlayers Plugin Option File
	*/
	
	// create admin menu
	add_action('admin_menu', 'gdlr_hotel_add_admin_menu', 99);
	if( !function_exists('gdlr_hotel_add_admin_menu') ){
		function gdlr_hotel_add_admin_menu(){
			$page = add_submenu_page('hotel_option', __('Transaction', 'gdlr-hotel'), __('Transaction', 'gdlr-hotel'), 
				'manage_hotel', 'hotel-transaction' , 'gdlr_hotel_transaction_option');			
			add_action('admin_print_styles-' . $page, 'gdlr_transaction_option_style');	
			add_action('admin_print_scripts-' . $page, 'gdlr_transaction_option_script');

			$page = add_submenu_page('hotel_option', __('Summary Report', 'gdlr-hotel'), __('Summary Report', 'gdlr-hotel'), 
				'manage_hotel', 'hotel-summary-report' , 'gdlr_hotel_summary_report');			
			add_action('admin_print_styles-' . $page, 'gdlr_transaction_option_style');	
			add_action('admin_print_scripts-' . $page, 'gdlr_transaction_option_script');	
		}	
	}
	if( !function_exists('gdlr_transaction_option_style') ){
		function gdlr_transaction_option_style(){
			wp_enqueue_style('gdlr-alert-box', plugins_url('transaction-style.css', __FILE__));		
			wp_enqueue_style('font-awesome', GDLR_PATH . '/plugins/font-awesome-new/css/font-awesome.min.css');		
		}
	}
	if( !function_exists('gdlr_transaction_option_script') ){
		function gdlr_transaction_option_script(){
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-datepicker');
			wp_enqueue_script('gdlr-alert-box', plugins_url('transaction-script.js', __FILE__));
		}
	}
	
	add_action('after_setup_theme', 'gdlr_create_hotel_admin_option', 99);
	if( !function_exists('gdlr_create_hotel_admin_option') ){
	
		function gdlr_create_hotel_admin_option(){
			global $hotel_option, $gdlr_sidebar_controller;
		
			new gdlr_admin_option( 
				
				// admin option attribute
				array(
					'page_title' => __('Hotel Option', 'gdlr-hotel'),
					'menu_title' => __('Hotel Option', 'gdlr-hotel'),
					'menu_slug' => 'hotel_option',
					'save_option' => 'gdlr_hotel_option',
					'role' => 'edit_theme_options',
					'position' => 83,
				),
					  
				// admin option setting
				array(
					// general menu
					'general' => array(
						'title' => __('General', 'gdlr-hotel'),
						'icon' => GDLR_PATH . '/include/images/icon-general.png',
						'options' => array(
							
							'general-option' => array(
								'title' => __('General Option', 'gdlr-hotel'),
								'options' => array(
									'booking-money-format' => array(
										'title' => __('Money Display Format', 'gdlr-hotel'),
										'type' => 'text',	
										'default' => '$NUMBER',
									),
									'enable-hotel-branch' => array(
										'title' => __('Enable Hotel Branch ( Using Category )', 'gdlr-hotel'),
										'type' => 'checkbox',	
										'default' => 'disable'
									),
									'enable-adult-child-option' => array(
										'title' => __('Enable Adult - Children Option', 'gdlr-hotel'),
										'type' => 'checkbox',	
										'default' => 'enable'
									),
									'preserve-booking-room' => array(
										'title' => __('Preserve The Room After', 'gdlr-hotel'),
										'type' => 'combobox',	
										'options' => array(
											'paid' => __('Paid for room', 'gdlr-hotel'),
											'booking' => __('Booking for room', 'gdlr-hotel')
										)
									),
									'booking-price-display' => array(
										'title' => __('Booking Price Display', 'gdlr-hotel'),
										'type' => 'combobox',	
										'options' => array(
											'start-from' => __('Start From', 'gdlr-hotel'),
											'full-price' => __('Full Price', 'gdlr-hotel')
										)
									),
									'booking-vat-amount' => array(
										'title' => __('Vat Amount', 'gdlr-hotel'),
										'type' => 'text',	
										'default' => '8',
										'description' => __('Input only number ( as percent )', 'gdlr-hotel') . 
											__('Filling 0 to disable this option out.', 'gdlr-hotel'),
									),
									'block-date' => array(
										'title' => __('Block Date', 'gdlr-hotel'),
										'type' => 'textarea',	
										'default' => '',
										'description' => __('Fill the date in yyyy-mm-dd format. Use * for recurring date, separated each date using comma, use the word \'to\' for date range. Ex. *-12-25 to *-12-31 means special season is running every Christmas to New Year\'s Eve every year.', 'gdlr-hotel')
									),
									'booking-deposit-amount' => array(
										'title' => __('Deposit Amount', 'gdlr-hotel'),
										'type' => 'text',	
										'default' => '20',
										'description' => __('Allow customer to pay part of price for booking the room ( as percent ).', 'gdlr-hotel') . 
											__('Filling 0 to disable this option out.', 'gdlr-hotel'),
									),
									'payment-method' => array(
										'title' => __('Payment Method', 'gdlr-hotel'),
										'type' => 'combobox',	
										'options' => array(
											'contact' =>  __('Only Contact Form', 'gdlr-hotel'),
											'instant' =>  __('Include Instant Payment', 'gdlr-hotel'),
										)
									),
									'instant-payment-method' => array(
										'title' => __('Instant Payment Method', 'gdlr-hotel'),
										'type' => 'multi-combobox',	
										'options' => array(
											'paypal' =>  __('Paypal', 'gdlr-hotel'),
											'stripe' =>  __('Stripe', 'gdlr-hotel'),
											'paymill' =>  __('Paymill', 'gdlr-hotel'),
											'authorize' =>  __('Authorize.Net', 'gdlr-hotel'),
											'sslcommerz' =>  __('SSLCommerz', 'gdlr-hotel'),
										),
										'wrapper-class' => 'payment-method-wrapper instant-wrapper',
										'description' => __('Leaving this field blank will display all available payment method.', 'gdlr-hotel')
									),	
								)
							),
							'booking-settings' => array(
								'title' => __('Booking Page Settings', 'gdlr-hotel'),
								'options' => array(
									'transaction-per-page' => array(
										'title' => __('Admin Transaction Per Page', 'gdlr-hotel'),
										'type' => 'text',	
										'default' => '30',
									),
									'booking-slug' => array(
										'title' => __('Booking Page Slug', 'gdlr-hotel'),
										'type' => 'text',	
										'default' => 'booking',
										'description' => __('Please only fill lower case character with no special character here.', 'gdlr-hotel')
									),
									'enable-multi-room-selection' => array(
										'title' => __('Enable Multi Room Selection', 'gdlr-hotel'),
										'type' => 'checkbox',
										'default' => 'enable'
									),
									'enable-checkin-time' => array(
										'title' => __('Enable Checkin Time', 'gdlr-hotel'),
										'type' => 'combobox',
										'options' => array(
											'yes' => __('Yes', 'gdlr-hotel'), 
											'no' => __('No', 'gdlr-hotel') 
										),	
										'default' => 'no',
									),
									'checkin-time-hour' => array(
										'title' => __('Start Checkin Time ( HOURS )', 'gdlr-hotel'),
										'type' => 'combobox',
										'options' => apply_filters('gdlr-hotel-reservation-hours', array(
											'01' => '01', '02' => '02', '03' => '03', '04' => '04', '05' => '05', 
											'06' => '06', '07' => '07', '08' => '08', '09' => '09', '10' => '10', 
											'11' => '11', '12' => '12', '13' => '13', '14' => '14', '15' => '15', 
											'16' => '16', '17' => '17', '18' => '18', '19' => '19', '20' => '20', 
											'21' => '21', '22' => '22', '23' => '23', '24' => '24', 
										)),
										'default' => '15',
										'wrapper-class' => 'enable-checkin-time-wrapper yes-wrapper'
									),
									// 'checkin-time-min' => array(
									// 	'title' => __('Checkin Time ( MINS )', 'gdlr-hotel'),
									// 	'type' => 'combobox',	
									// 	'options' => apply_filters('gdlr-hotel-reservation-mins', array(
									// 		'00' => '00', '15' => '15', '30' => '30', '45' => '45',
									// 	)),
									// 	'default' => '00',
									// 	'wrapper-class' => 'enable-checkin-time-wrapper yes-wrapper'
									// ),
									'enable-booking-term-and-condition' => array(
										'title' => __('Enable Booking Term And Condition', 'gdlr-hotel'),
										'type'=> 'combobox',
										'options'=> array(
											'yes' => __('Yes', 'gdlr-hotel'),
											'no' => __('No', 'gdlr-hotel'),
										),
										'default' => 'no'
									),
									'booking-term-and-condition' => array(
										'title' => __('Booking Term And Condition Page', 'gdlr-hotel'),
										'type'=> 'combobox',
										'options'=> gdlr_hotel_get_post_list('page'),
										'wrapper-class' => 'enable-booking-term-and-condition-wrapper yes-wrapper'
									),						
									'booking-item-style' => array(
										'title' => __('Booking Item Style', 'gdlr-hotel'),
										'type'=> 'combobox',
										'options'=> array(
											'medium' => esc_html('Medium Thumbnail', 'gdlr-hotel'),
											'medium-new' => esc_html('New Medium Thumbnail', 'gdlr-hotel'),
										),
										'default'=> 'medium'
									),						
									'booking-thumbnail-size' => array(
										'title' => __('Booking Thumbnail Size', 'gdlr-hotel'),
										'type'=> 'combobox',
										'options'=> gdlr_get_thumbnail_list(),
										'default'=> 'small-grid-size'
									),
									'booking-num-fetch' => array(
										'title' => __('Booking Num Fetch', 'gdlr-hotel'),
										'type' => 'text',	
										'default' => '5',
									),
									'booking-num-excerpt' => array(
										'title' => __('Booking Num Excerpt', 'gdlr-hotel'),
										'type' => 'text',	
										'default' => '34',
									),
								)
							),
							'booking-mail' => array(
								'title' => __('Booking Mail', 'gdlr-hotel'),
								'options' => array(
									'recipient-name' => array(
										'title' => __('Recipient Name', 'gdlr-hotel'),
										'type' => 'text'
									),
									'recipient-mail' => array(
										'title' => __('Recipient Email', 'gdlr-hotel'),
										'type' => 'text'
									),
									'booking-complete-contact' => array(
										'title' => __('Booking Complete Contact', 'gdlr-hotel'),
										'type' => 'textarea'
									),
									'booking-code-prefix' => array(
										'title' => __('Booking Code Prefix', 'gdlr-hotel'),
										'type' => 'text',
										'default' => 'GDLR'
									),
								)
							),
								
							'room-style' => array(
								'title' => __('Room Style', 'gdlr-hotel'),
								'options' => array(			
									'maximum-room-selected' => array(
										'title' => __('Maximum Room Selected', 'gdlr-hotel'),
										'type'=> 'text',
										'default'=> '9'
									),	
									'minimum-night' => array(
										'title' => __('Minimum Night to Stay', 'gdlr-hotel'),
										'type'=> 'text',
										'default'=> '1'
									),
									'room-thumbnail-size' => array(
										'title' => __('Single Room Thumbnail Size', 'gdlr-hotel'),
										'type'=> 'combobox',
										'options'=> gdlr_get_thumbnail_list(),
										'default'=> 'post-thumbnail-size'
									),
									'ical-cache-time' => array(
										'title' => __('Ical Cache Time ( Mins )', 'gdlr-hotel'),
										'type'=> 'text',
										'default'=> '5'
									),
									'ical-start-time' => array(
										'title' => __('Ical File Start Time ( Months )', 'gdlr-hotel'),
										'type'=> 'text',
										'default'=> '2'
									),
								)
							),
							
							'paypal-payment-info' => array(
								'title' => __('Paypal Info', 'gdlr-hotel'),
								'options' => array(	
									'paypal-recipient-email' => array(
										'title' => __('Paypal Recipient Email', 'gdlr-hotel'),
										'type' => 'text',	
										'default' => 'testmail@test.com'
									),
									'paypal-action-url' => array(
										'title' => __('Paypal Action URL', 'gdlr-hotel'),
										'type' => 'text',
										'default' => 'https://www.paypal.com/cgi-bin/webscr'
									),
									'paypal-currency-code' => array(
										'title' => __('Paypal Currency Code', 'gdlr-hotel'),
										'type' => 'text',	
										'default' => 'USD'
									),						
								)
							),
							
							'stripe-payment-info' => array(
								'title' => __('Stripe Info', 'gdlr-hotel'),
								'options' => array(	
									'stripe-secret-key' => array(
										'title' => __('Stripe Secret Key', 'gdlr-hotel'),
										'type' => 'text'
									),
									'stripe-publishable-key' => array(
										'title' => __('Stripe Publishable Key', 'gdlr-hotel'),
										'type' => 'text'
									),	
									'stripe-currency-code' => array(
										'title' => __('Stripe Currency Code', 'gdlr-hotel'),
										'type' => 'text',	
										'default' => 'usd'
									),	
								)
							),
							
							'paymill-payment-info' => array(
								'title' => __('Paymill Info', 'gdlr-hotel'),
								'options' => array(	
									'paymill-private-key' => array(
										'title' => __('Paymill Private Key', 'gdlr-hotel'),
										'type' => 'text'
									),
									'paymill-public-key' => array(
										'title' => __('Paymill Public Key', 'gdlr-hotel'),
										'type' => 'text'
									),	
									'paymill-currency-code' => array(
										'title' => __('Paymill Currency Code', 'gdlr-hotel'),
										'type' => 'text',	
										'default' => 'usd'
									),
								)
							),
							
							'authorize-payment-info' => array(
								'title' => __('Authorize Info', 'gdlr-hotel'),
								'options' => array(	
									'authorize-live-mode' => array(
										'title' => __('Live Mode ', 'gdlr-hotel'),
										'type' => 'checkbox',
										'default' => 'disable',
										'description' => __('Please turn this option off when you\'re on test mode.','gdlr-hotel')
									),
									'authorize-api-id' => array(
										'title' => __('Authorize API Login ID ', 'gdlr-hotel'),
										'type' => 'text'
									),
									'authorize-transaction-key' => array(
										'title' => __('Authorize Transaction Key', 'gdlr-hotel'),
										'type' => 'text'
									),
									'authorize-md5-hash' => array(
										'title' => __('Authorize MD5 Hash', 'gdlr-hotel'),
										'type' => 'text'
									),
								)
							),	
							'sslcommerz-payment-info' => array(
								'title' => __('SSLCommerz', 'gdlr-hotel'),
								'options' => array(	
									'sslcommerz-live-mode' => array(
										'title' => __('Live Mode ', 'gdlr-hotel'),
										'type' => 'checkbox',
										'default' => 'disable',
										'description' => __('Please turn this option off when you\'re on test mode.','gdlr-hotel')
									),
									'sslcommerz-merchant-id' => array(
										'title' => __('Merchant ID', 'gdlr-hotel'),
										'type' => 'text'
									),
									'sslcommerz-merchant-password' => array(
										'title' => __('Merchant Password', 'gdlr-hotel'),
										'type' => 'text'
									),
									'sslcommerz-currency' => array(
										'title' => __('Currency', 'gdlr-hotel'),
										'type'=> 'combobox',
										'options'=> array('BDT','USD','EUR')
									),
									'sslcommerz-success-page' => array(
										'title' => __('Success Page', 'gdlr-hotel'),
										'type'=> 'combobox',
										'options'=> gdlr_hotel_get_post_list('page')
									),
									'sslcommerz-fail-page' => array(
										'title' => __('Fail Page', 'gdlr-hotel'),
										'type'=> 'combobox',
										'options'=> gdlr_hotel_get_post_list('page')
									),
									'sslcommerz-cancel-page' => array(
										'title' => __('Cancel Page', 'gdlr-hotel'),
										'type'=> 'combobox',
										'options'=> gdlr_hotel_get_post_list('page')
									)
								)
							),					
						)
					)
				),
				
				$hotel_option
			);
		}
	}

	if(!file_exists(get_template_directory()."/sslcommerz_success.php"))
	{
	    copy(plugin_dir_path( __FILE__ )."sslcommerz/sslcommerz_success.php", get_template_directory()."/sslcommerz_success.php");
	}
	if(!file_exists(get_template_directory()."/sslcommerz_fail.php"))
	{
	    copy(plugin_dir_path( __FILE__ )."sslcommerz/sslcommerz_fail.php", get_template_directory()."/sslcommerz_fail.php");
	}
	if(!file_exists(get_template_directory()."/sslcommerz_cancel.php"))
	{
	    copy(plugin_dir_path( __FILE__ )."sslcommerz/sslcommerz_cancel.php", get_template_directory()."/sslcommerz_cancel.php");
	}
	if(!file_exists(get_template_directory()."/sslcommerz_ipn.php"))
	{
	    copy(plugin_dir_path( __FILE__ )."sslcommerz/sslcommerz_ipn.php", get_template_directory()."/sslcommerz_ipn.php");
	}
?>