<?php
	/*	
	*	Goodlayers Utility File
	*/	

	// retrieve all posts as a list
	if( !function_exists('gdlr_hotel_get_post_list') ){	
		function gdlr_hotel_get_post_list( $post_type ){
			$post_list = get_posts(array('post_type' => $post_type, 'numberposts'=>1000));

			$ret = array();
			if( !empty($post_list) ){
				foreach( $post_list as $post ){
					$ret[$post->ID] = $post->post_title;
				}
			}
				
			return $ret;
		}	
	}	

	// format date
	if( !function_exists('gdlr_hotel_date_format') ){
		function gdlr_hotel_date_format( $date, $format = '', $time = false ){
			if( empty($format) ){
				$format = get_option('date_format');
				if( !empty($time) ){
					$format .= ' ' . get_option('time_format');
				}
			}
			
			$date = is_numeric($date)? $date: strtotime($date);
			return date_i18n($format, $date);
		}
	}
	
	// send the mail
	if( !function_exists('gdlr_hotel_mail') ){
		function gdlr_hotel_mail($recipient, $title, $message, $reply_to = ''){
			global $hotel_option;

			$headers = 'From: ' . $hotel_option['recipient-name'] . ' <' . $hotel_option['recipient-mail'] . '>' . "\r\n";
			if( !empty($reply_to) ){
				$headers = $headers . 'Reply-To: ' . $reply_to . ' <' . $reply_to . '>' . "\r\n";
			}
			$headers = $headers . 'Content-Type: text/plain; charset=UTF-8 ' . " \r\n";
			wp_mail($recipient, $title, $message, $headers);		
		}
	}
	
	if( !function_exists('gdlr_hotel_mail_content') ){
		function gdlr_hotel_mail_content($contact, $data, $payment_info, $price){
			$content  = __("Contact Info", "gdlr-hotel") . " \n";
			$content .= __("Name :", "gdlr-hotel") . " {$contact['first_name']}\n";
			$content .= __("Last Name :", "gdlr-hotel") . " {$contact['last_name']}\n";
			$content .= __("Phone :", "gdlr-hotel") . " {$contact['phone']}\n";
			$content .= __("Email :", "gdlr-hotel") . " {$contact['email']}\n";
			$content .= __("Address :", "gdlr-hotel") . " {$contact['address']}\n";
			$content .= __("Additional Note :", "gdlr-hotel") . " {$contact['additional-note']}\n";
			$content .= __("Coupon :", "gdlr-hotel") . " {$contact['coupon']}\n";
			if( !empty($data['gdlr-hotel-branches']) ){
				$term = get_term_by('id', $data['gdlr-hotel-branches'], 'room_category');
				$content .= "Branches : {$term->name}\n";
				
				$category_meta = get_option('gdlr_hotel_branch', array());
				if( !empty($category_meta[$term->slug]['content']) ){
					$content .= "Location : {$category_meta[$term->slug]['content']}\n";
				}
			}
			$content .= "\n";
			
			$content .= __("Room Information", "gdlr-hotel") . "\n";
			for( $i=0; $i<intval($data['gdlr-room-number']); $i++){
				$room_num = $i+1;
				
				$content .= __("Room", "gdlr-hotel") . " {$room_num} : " . get_the_title($data['gdlr-room-id'][$i]) . " \n";
				$content .= __("Adult :", "gdlr-hotel") . " {$data['gdlr-adult-number'][$i]}\n";
				$content .= __("Children :", "gdlr-hotel") . " {$data['gdlr-children-number'][$i]}\n";
			}			
			$content .= __("Check In :", "gdlr-hotel") . " {$data['gdlr-check-in']} \n";
			if( !empty($data['gdlr-checkin-hour']) && !empty($data['gdlr-checkin-min']) ){
				$content .= __("Check In Time:", "gdlr-hotel") . " {$data['gdlr-checkin-hour']}:{$data['gdlr-checkin-min']} \n";
			}
			$content .= __("Check Out :", "gdlr-hotel") . " {$data['gdlr-check-out']} \n";
			$content .= "\n";
			
			if( !empty($data['service']) ){
				$content .= __("Additional Services", "gdlr-hotel") . "\n";
				$services_price = gdlr_calculate_service_price($data);
				foreach( $services_price as $key => $service_price ){
					if( $key == 'total' ) continue;
					$service_title = str_replace('<span class="gdlr-sep">/</span>', ' ', $service_price['title']);
					$content .= $service_title . "\n";
				}

				$content .= "\n";
			}
			
			$content .= __("Payment Information", "gdlr-hotel") . " \n";
			$content .= __("Total Price :", "gdlr-hotel") . " " . gdlr_hotel_money_format($price['total_price']) . " \n";
			$content .= __("Paid Amount :", "gdlr-hotel") . " " . gdlr_hotel_money_format($price['pay_amount']) . " \n";

			if( !empty($price['booking_code']) ){
				$content .= __("Booking Code :", "gdlr-hotel") . " {$price['booking_code']} \n";
			}
			if( !empty($contact['payment-method']) && !empty($payment_info) ){
				if( $contact['payment-method'] == 'stripe' ){
					$content .= __("Payment Method : Stripe", "gdlr-hotel") . " \n";
					$content .= __("Transaction ID :", "gdlr-hotel") . " {$payment_info['balance_transaction']} \n";
				}else if( $contact['payment-method'] == 'paypal' ){
					$content .= __("Payment Method : Paypal", "gdlr-hotel") . " \n";
					$content .= __("Transaction ID :", "gdlr-hotel") . " {$payment_info['txn_id']} \n";
				}else if( $contact['payment-method'] == 'paymill' ){
					$content .= __("Payment Method : Paymill", "gdlr-hotel") . " \n";
					$content .= __("Transaction ID :", "gdlr-hotel") . " {$payment_info->getId()} \n";
				}else if( $contact['payment-method'] == 'authorize' ){
					$content .= __("Payment Method : Authorize", "gdlr-hotel") . " \n";
					$content .= __("Transaction ID :", "gdlr-hotel") . " {$payment_info->transaction_id} \n";
				}
			}
			
			return $content;
		}
	}	
	
	// format the currency
	if( !function_exists('gdlr_hotel_money_format') ){
		function gdlr_hotel_money_format($amount, $format = ''){
			if( empty($format) ){
				global $hotel_option;
				$format = $hotel_option['booking-money-format'];
			}
			if( strpos($format, 'NUMBER') === false ){
				$format .= 'NUMBER';
			}
			return str_replace('NUMBER', number_format_i18n($amount, 2), $format);
		}
	}
	
	// validate the contact form fields
	if( !function_exists('gdlr_validate_contact_form') ){
		function gdlr_validate_contact_form( $contact ){
			if( empty($contact['first_name']) || empty($contact['last_name']) || 
				empty($contact['email']) || empty($contact['phone']) ){
				return __('Please fill all required fields.', 'gdlr-hotel');
			}
			if( !is_email($contact['email']) ){
				return __('Email is invalid.', 'gdlr-hotel');
			}
			return false;
		}
	}
	
	// save the booking any payment to database
	if( !function_exists('gdlr_insert_booking_db') ){
		function gdlr_insert_booking_db($options){
			global $wpdb, $hotel_option;
			$pricing = gdlr_get_booking_total_price($options['data'], $options['contact']['coupon']);
			if( $pricing['total_price'] == 0 ){
				$options['payment_status'] = 'paid';
			}
			if( $options['payment_status'] == 'booking' ){
				$pricing['pay_amount'] = 0;
			}

			$customer_code  = $hotel_option['booking-code-prefix'];
			$customer_code .= mb_substr($options['contact']['first_name'], 0, 1);
			$customer_code .= mb_substr($options['contact']['last_name'], 0, 1);
			$code_count = get_option('gdlr-customer-code-count', 0);
			update_option('gdlr-customer-code-count', $code_count+1);
			$customer_code  = strtoupper($customer_code . $code_count);
			
			$result = $wpdb->insert( $wpdb->prefix . 'gdlr_hotel_payment',
				array(
					'total_price'=>$pricing['total_price'], 
					'pay_amount'=>$pricing['pay_amount'], 
					'booking_date'=>current_time('mysql'),
					'checkin_date'=>$options['data']['gdlr-check-in'],
					'booking_data'=>serialize($options['data']), 
					'contact_info'=>serialize($options['contact']), 
					'payment_status'=>$options['payment_status'], 
					'customer_code'=>$customer_code
				),
				array('%f', '%f', '%s', '%s', '%s', '%s', '%s', '%s')
			);

			if( $result > 0 ){
				$payment_id = $wpdb->insert_id;
				
				for( $i=0; $i<$options['data']['gdlr-room-number']; $i++ ){
					$wpdb->insert($wpdb->prefix . 'gdlr_hotel_booking',
						array(
							'payment_id'=>$payment_id, 
							'room_id'=>$options['data']['gdlr-room-id'][$i], 
							'start_date'=>$options['data']['gdlr-check-in'], 
							'end_date'=>$options['data']['gdlr-check-out'],
							'date_list'=>implode(',', gdlr_hotel_list_dates($options['data']['gdlr-check-in'], $options['data']['gdlr-check-out']))
						),
						array('%s', '%s', '%s', '%s')
					);

					do_action('gdlr_update_room_availability', $options['data']['gdlr-room-id'][$i]);
				}
			}
			
			return array(
				'invoice' => $payment_id, 
				'total-price' => $pricing['total_price'],
				'pay-amount' => $pricing['pay_amount'],
				'code' => $customer_code
			);
		}
	}
	
?>