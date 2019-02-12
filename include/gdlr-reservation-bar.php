<?php
	/*	
	*	Goodlayers Reservation Bar File
	*/

	if( !function_exists('gdlr_get_reservation_bar') ){
		function gdlr_get_reservation_bar($single_form = false){
			global $hotel_option;
			
			$ret  = '<form class="gdlr-reservation-bar" id="gdlr-reservation-bar" data-action="gdlr_hotel_booking" ';
			$ret .= ($single_form)? 'method="post" action="' . esc_url(add_query_arg(array($hotel_option['booking-slug']=>''), home_url('/'))) . '" ': '';
			$ret .= ' >';
			$ret .= '<div class="gdlr-reservation-bar-title">' . __('Your Reservation', 'gdlr-hotel') . '</div>';
			
			if( !empty($_GET['state']) && $_GET['state'] == 4 && !empty($_GET['invoice']) ){
				global $wpdb;
				$temp_sql  = "SELECT contact_info, booking_data FROM " . $wpdb->prefix . "gdlr_hotel_payment ";
				$temp_sql .= "WHERE id = " . $_GET['invoice'];	
				$result = $wpdb->get_row($temp_sql);
				$data = unserialize($result->booking_data);
				$contact = unserialize($result->contact_info);
				
				$ret .= '<div class="gdlr-reservation-bar-summary-form" id="gdlr-reservation-bar-summary-form" style="display: block;">';
				$ret .= gdlr_get_summary_form($data, false, $contact['coupon']);
				$ret .= '</div>';
			}else{
				$ret .= '<div class="gdlr-reservation-bar-summary-form" id="gdlr-reservation-bar-summary-form"></div>';
				
				if( !empty($_POST['hotel_data']) ){
					$ret .= '<div class="gdlr-reservation-bar-room-form gdlr-active" id="gdlr-reservation-bar-room-form" style="display: block;">';
					$ret .= gdlr_get_reservation_room_form($_POST, 0);
					$ret .= '</div>';
				}else{
					$ret .= '<div class="gdlr-reservation-bar-room-form" id="gdlr-reservation-bar-room-form"></div>';
				}
				
				$ret .= '<div class="gdlr-reservation-bar-date-form" id="gdlr-reservation-bar-date-form">';
				$ret .= gdlr_get_reservation_date_form($single_form);
				$ret .= '</div>';
				
				$ret .= '<div class="gdlr-reservation-bar-service-form" id="gdlr-reservation-bar-service-form"></div>';
			}
			
			if( $single_form ){
				$ret .= '<input type="hidden" name="single-room" value="' . get_the_ID() . '" />';
			}else if( !empty($_POST['single-room']) ){
				$ret .= '<input type="hidden" name="single-room" value="' . $_POST['single-room'] . '" />';
			}
			$ret .= '</form>';
			return $ret;
		}
	}	
	
	if( !function_exists('gdlr_get_summary_form') ){
		function gdlr_get_summary_form($data, $with_form = true, $coupon = ''){
			global $hotel_option;
			$total_price = 0;

			$ret  = '<div class="gdlr-price-summary-wrapper" >';
			
			// display branches if exists
			if( !empty($data['gdlr-hotel-branches']) ){
				$term = get_term_by('id', $data['gdlr-hotel-branches'], 'room_category');
				$ret .= '<div class="gdlr-price-summary-hotel-branches gdlr-title-font">';
				$ret .= $term->name;
				$ret .= '</div>';
			}else{
				$ret .= '<div class="gdlr-price-summary-head">' . __('Price Breakdown', 'gdlr-hotel') . '</div>';
			}
			
			for($i=0; $i<intval($data['gdlr-room-number']); $i++){
				$post_option = json_decode(gdlr_decode_preventslashes(get_post_meta($data['gdlr-room-id'][$i], 'post-option', true)), true);
				$post_option['data'] = array(
					'check-in'=> $data['gdlr-check-in'],
					'check-out'=> $data['gdlr-check-out'],
					'adult'=> $data['gdlr-adult-number'][$i], 
					'children'=> $data['gdlr-children-number'][$i],
					'gdlr-night'=> $data['gdlr-night']
				);
				$price = gdlr_get_booking_price($post_option);
				
				$ret .= '<div class="gdlr-price-room-summary">';
				$ret .= '<div class="gdlr-price-room-summary-title">';
				$ret .= __('Room', 'gdlr-hotel') . ' ' . ($i + 1) . ' : ' . get_the_title($data['gdlr-room-id'][$i]);
				$ret .= '</div>';

				$ret .= '<div class="gdlr-price-room-summary-info gdlr-title-font" >';
				$ret .= '<span>' . __('Adult', 'gdlr-hotel') . ' : ' . $data['gdlr-adult-number'][$i] . '</span>';
				$ret .= '<span>' . __('Children', 'gdlr-hotel') . ' : ' . $data['gdlr-children-number'][$i] . '</span>';
				$ret .= '<span class="gdlr-price-room-summary-price" href="#" >' . gdlr_hotel_money_format($price['sub-total']) . '</span>';
				$ret .= '</div>';		

				if( !empty($price['cnd']) ){
					$ret .= '<div class="gdlr-price-room-summary-info gdlr-title-font" >';
					$ret .= '<span>' . sprintf(__('%d Nights, %d%% Discount', 'gdlr-hotel'), $price['cnd-night'], $price['cnd-discount']) . '</span>';
					$ret .= '<span class="gdlr-price-room-summary-price" href="#" >-' . gdlr_hotel_money_format($price['cnd']) . '</span>';
					$ret .= '</div>';
				}	
				$ret .= '</div>';
				
				$total_price += $price['total'];
			}
			
			// service
			if( !empty($data['service']) ){
				$services_price = gdlr_calculate_service_price($data);
				$ret .= '<div class="gdlr-service-price-summary">';
				$ret .= '<div class="gdlr-service-price-summary-head" >' . __('Additional Services', 'gdlr-hotel') . '</div>';
				
				foreach( $services_price as $key => $service_price ){
					if( $key == 'total' ) continue;
					
					$ret .= '<div class="gdlr-service-price-summary-item">';
					$ret .= '<span class="gdlr-head">' . $service_price['title'] . '</span>';
					$ret .= '<span class="gdlr-tail">' . gdlr_hotel_money_format($service_price['price']) . '</span>';					
					$ret .= '<div class="clear"></div>';
					$ret .= '</div>';
				}
				$ret .= '</div>';
				
				$total_price += $services_price['total'];
			}
			
			// vat
			if( !empty($hotel_option['booking-vat-amount']) ){
				$ret .= '<div class="gdlr-price-summary-vat" >';
				$ret .= '<div class="gdlr-price-summary-vat-total" >';
				$ret .= '<span class="gdlr-head">' . __('Total', 'gdlr-hotel') . '</span>';
				$ret .= '<span class="gdlr-tail">' . gdlr_hotel_money_format($total_price) . '</span>';
				$ret .= '<div class="clear"></div>';
				$ret .= '</div>'; // vat-total
				
				if( !empty($coupon) ){
					$discount = gdlr_get_coupon_discount($data, $coupon, false);
					$total_price -= $discount;
					if( $total_price < 0 ) $total_price = 0;
					
					$ret .= '<div class="gdlr-price-summary-vat-discount" >';
					$ret .= '<span class="gdlr-head">' . __('Coupon Discount', 'gdlr-hotel') . '</span>';
					$ret .= '<span class="gdlr-tail">' . gdlr_hotel_money_format($discount) . '</span>';
					$ret .= '<div class="clear"></div>';
					$ret .= '</div>';
				}

				$vat_amount = ($total_price * floatval($hotel_option['booking-vat-amount'])) / 100;
				$total_price += $vat_amount;
				$ret .= '<div class="gdlr-price-summary-vat-amount" >';
				$ret .= '<span class="gdlr-head">' . __('Vat', 'gdlr-hotel') . ' ' . $hotel_option['booking-vat-amount'] . '%</span>';
				$ret .= '<span class="gdlr-tail">' . gdlr_hotel_money_format($vat_amount) . '</span>';
				$ret .= '<div class="clear"></div>';
				$ret .= '</div>'; // vat-amount
				$ret .= '</div>';
			}else{
				if( !empty($coupon) ){
					$discount = gdlr_get_coupon_discount($data, $coupon, false);
					$total_price -= $discount;
					$ret .= '<div class="gdlr-price-summary-vat" >';
					$ret .= '<div class="gdlr-price-summary-vat-discount" >';
					$ret .= '<span class="gdlr-head">' . __('Coupon Discount', 'gdlr-hotel') . '</span>';
					$ret .= '<span class="gdlr-tail">' . gdlr_hotel_money_format($discount) . '</span>';
					$ret .= '<div class="clear"></div>';
					$ret .= '</div>';
					$ret .= '</div>';
				}
			}

			// deposit
			if( $with_form && !empty($hotel_option['booking-deposit-amount']) ){
				// grand total
				$ret .= '<div class="gdlr-price-summary-grand-total gdlr-active" >';
				$ret .= '<span class="gdlr-head">' . __('Grand Total', 'gdlr-hotel') . '</span>';
				$ret .= '<span class="gdlr-tail">' . gdlr_hotel_money_format($total_price) . '</span>';
				$ret .= '</div>';
				
				$deposit_text = $hotel_option['booking-deposit-amount'] . '% ' . __('Deposit', 'gdlr-hotel');
				$deposit_amount = ($total_price * floatval($hotel_option['booking-deposit-amount'])) / 100;
				
				$ret .= '<div class="gdlr-price-deposit-wrapper">';
				$ret .= '<div class="gdlr-price-deposit-input" >';
				$ret .= '<span class="gdlr-active" ><label class="gdlr-radio-input"><input type="radio" name="pay_deposit" value="false" checked ></label>' . __('Pay Full Amount', 'gdlr-hotel') . '</span>';
				$ret .= '<span><label class="gdlr-radio-input"><input type="radio" name="pay_deposit" value="true" ></label>'  . __('Pay', 'gdlr-hotel') . ' ' . $deposit_text . '</span>';
				$ret .= '</div>';
				
				$ret .= '<div class="gdlr-price-deposit-inner-wrapper">';
				$ret .= '<div class="gdlr-price-deposit-title">' . $deposit_text . '</div>';
				$ret .= '<div class="gdlr-price-deposit-caption">' . __('*Pay the rest on arrival', 'gdlr-hotel') . '</div>';
				$ret .= '<div class="gdlr-price-deposit-amount">' . gdlr_hotel_money_format($deposit_amount) . '</div>';
				$ret .= '</div>';
				$ret .= '</div>';
				
				$ret .= '<a id="gdlr-edit-booking-button" class="gdlr-edit-booking-button gdlr-button with-border" href="#">' . __('Edit Booking', 'gdlr-hotel') . '</a>';
			}else{ 
				$ret .= '<div class="gdlr-price-summary-grand-total-wrapper-2" >';
				$ret .= '<div class="gdlr-price-summary-grand-total ';
				$ret .= (empty($data['pay_deposit']) || $data['pay_deposit'] == 'false')? 'gdlr-active': '';
				$ret .= '" >';
				$ret .= '<span class="gdlr-head">' . __('Grand Total', 'gdlr-hotel') . '</span>';
				$ret .= '<span class="gdlr-tail">' . gdlr_hotel_money_format($total_price) . '</span>';
				$ret .= '</div>';
				
				if( !empty($data['pay_deposit']) && $data['pay_deposit'] == 'true' ){
					$deposit_text = $hotel_option['booking-deposit-amount'] . '% ' . __('Deposit', 'gdlr-hotel');
					$deposit_amount = ($total_price * floatval($hotel_option['booking-deposit-amount'])) / 100;
					
					$ret .= '<div class="gdlr-price-deposit-wrapper">';
					$ret .= '<div class="gdlr-price-deposit-inner-wrapper">';
					$ret .= '<div class="gdlr-price-deposit-title">' . $deposit_text . '</div>';
					$ret .= '<div class="gdlr-price-deposit-caption">' . __('*Pay the rest on arrival', 'gdlr-hotel') . '</div>';
					$ret .= '<div class="gdlr-price-deposit-amount">' . gdlr_hotel_money_format($deposit_amount) . '</div>';
					$ret .= '</div>';
					$ret .= '</div>';
					
					$ret .= '<div class="gdlr-pay-on-arrival" >';
					$ret .= '<span class="gdlr-head">' . __('Pay on arrival', 'gdlr-hotel') . '</span>';
					$ret .= '<span class="gdlr-tail">' . gdlr_hotel_money_format($total_price - $deposit_amount) . '</span>';
					$ret .= '</div>';
				}
				$ret .= '</div>';
				
				
			}

			$ret .= '</div>'; // gdlr-price-summary-wrapper
			
			return $ret;
		}
	}
	
	if( !function_exists('gdlr_get_reservation_room_form') ){
		function gdlr_get_reservation_room_form($data, $selected_room){
			$ret  = ''; $active = false;
			
			if( !empty($data['gdlr-room-id']) ){
				for( $i=0; $i<sizeOf($data['gdlr-room-id']) && $i<$data['gdlr-room-number']; $i++ ){
					$options = array(
						'room-number'=>$i + 1, 
						'room-id'=>$data['gdlr-room-id'][$i], 
						'adult'=>$data['gdlr-adult-number'][$i], 
						'children'=>$data['gdlr-children-number'][$i],
						'already_active'=>$active
					);
					if( $selected_room == $i || empty($data['gdlr-room-id'][$i]) ){
						$active = true;
						$options['room-id'] = '';
					}
					$ret .= gdlr_get_reservation_room($options);					
				}
			}
			
			if( empty($data['gdlr-room-id']) || 
				(!$active && $selected_room >= sizeOf($data['gdlr-room-id']) && $selected_room < intval($data['gdlr-room-number'])) ){
				$ret .= gdlr_get_reservation_room(array(
					'room-number'=>intval($selected_room) + 1, 
					'room-id'=>'', 
					'adult'=>$data['gdlr-adult-number'][$selected_room], 
					'children'=>$data['gdlr-children-number'][$selected_room]
				));
			}
			return $ret;
		}
	}

	if( !function_exists('gdlr_get_reservation_room') ){
		function gdlr_get_reservation_room($option){
			global $hotel_option;

			$option['room-id'] = empty($option['room-id'])? '': $option['room-id'];
			
			$ret  = '<div class="gdlr-reservation-room gdlr-title-font ';
			$ret .= (empty($option['room-id']) && empty($option['already_active']))? 'gdlr-active': ''; 
			$ret .= '">';
			$ret .= '<i class="fa fa-angle-double-right icon-double-angle-right" ></i>';
			
			$ret .= '<div class="gdlr-reservation-room-content" >';
			$ret .= '<div class="gdlr-reservation-room-title">';
			$ret .= __('Room', 'gdlr-hotel') . ' ' . $option['room-number'] . ' : ';
			$ret .= empty($option['room-id'])? '': get_the_title($option['room-id']);
			$ret .= '</div>';

			$ret .= '<div class="gdlr-reservation-room-info" >';

			if( empty($hotel_option['enable-adult-child-option']) || $hotel_option['enable-adult-child-option'] == 'enable' ){
				$ret .= '<span>' . __('Adult', 'gdlr-hotel') . ' : ' . $option['adult'] . '</span>';
				$ret .= '<span>' . __('Children', 'gdlr-hotel') . ' : ' . $option['children'] . '</span>';
			}else{
				$ret .= '<span>' . __('Selected room', 'gdlr-hotel') . '</span>';
			}
			$ret .= empty($option['room-id'])? '': '<a data-room="' . $option['room-number'] . '" class="gdlr-reservation-change-room" href="#" >' . __('Change Room', 'gdlr-hotel') . '</a>';
			$ret .= '</div>';
			$ret .= '</div>';
			
			$ret .= '<input type="text" name="gdlr-room-id[]" value="' . $option['room-id'] . '" /></span>';
			$ret .= '</div>';
			
			return $ret;
		}
	}	
	
	if( !function_exists('gdlr_get_reservation_date_form') ){
		function gdlr_get_reservation_date_form($single_form = false, $data = array()){
			global $hotel_option;
			$minimum_night = empty($hotel_option['minimum-night'])? 1: intval($hotel_option['minimum-night']);

			$ret  = '';
			if( !empty($_POST['hotel_data']) ){
				$value = $_POST;
			}else{
				$current_date = current_time('Y-m-d');
				$next_date = date('Y-m-d', strtotime($current_date . "+{$minimum_night} days"));
				
				$value = array(
					'gdlr-check-in' => $current_date,
					'gdlr-night' => $minimum_night,
					'gdlr-check-out' => $next_date,
					'gdlr-room-number' => 1,
					'gdlr-adult-number' => array(2),
					'gdlr-children-number' => array(0)
				);
				
				// for single room page
				global $gdlr_post_option;
				if( !empty($gdlr_post_option['max-people']) && intval($gdlr_post_option['max-people']) < 2 ){
					$value['gdlr-adult-number'] = 1;
				} 
			}
			
			// branch (if enable)
			if( !empty($hotel_option['enable-hotel-branch']) && $hotel_option['enable-hotel-branch'] == 'enable' ){
				if( is_single() ){
					$term = get_the_terms(get_the_ID(), 'room_category');
					if( !empty($term) ){
						$term = reset($term);
						$value['gdlr-hotel-branches'] = $term->term_id;
					}else{
						$value['gdlr-hotel-branches'] = '';
					}
				}else if( empty($value['gdlr-hotel-branches']) ){ 
					$value['gdlr-hotel-branches'] = ''; 
				}
					
				$ret .= gdlr_get_reservation_branch_combobox(array(
					'title'=>__('Hotel Branches', 'gdlr-hotel'),
					'slug'=>'gdlr-hotel-branches',
					'id'=>'gdlr-hotel-branches',
					'value'=>$value['gdlr-hotel-branches']
				));
				$ret .= '<div class="clear"></div>';
			}
			
			
			// date
			$ret .= gdlr_get_reservation_datepicker(array(
				'title'=>__('Check In', 'gdlr-hotel'),
				'slug'=>'gdlr-check-in',
				'id'=>'gdlr-check-in',
				'value'=>$value['gdlr-check-in']
			));
			$ret .= gdlr_get_reservation_combobox(array(
				'title'=>__('Night', 'gdlr-hotel'),
				'slug'=>'gdlr-night',
				'id'=>'gdlr-night',
				'value'=>$value['gdlr-night']
			), $minimum_night);
			$ret .= '<div class="clear"></div>';

			$ret .= gdlr_get_reservation_datepicker(array(
				'title'=>__('Check Out', 'gdlr-hotel'),
				'slug'=>'gdlr-check-out',
				'id'=>'gdlr-check-out',
				'minimum-night'=>$minimum_night,
				'value'=>$value['gdlr-check-out']
			));
			$ret .= '<div class="clear"></div>';

			if( !empty($hotel_option['enable-checkin-time']) && $hotel_option['enable-checkin-time'] == 'yes' ){
				$ret .= '<div class="gdlr-reservation-field gdlr-resv-time" >';
				$ret .= '<span class="gdlr-reservation-field-title" >' . __('Check In Time', 'gdlr-hotel') . '</span>';
				
				$checkin_hour = empty($hotel_option['checkin-time-hour'])? '15': $hotel_option['checkin-time-hour'];
				$checkin_hours = apply_filters('gdlr-hotel-reservation-hours', array(
					'01' => '01', '02' => '02', '03' => '03', '04' => '04', '05' => '05', 
					'06' => '06', '07' => '07', '08' => '08', '09' => '09', '10' => '10', 
					'11' => '11', '12' => '12', '13' => '13', '14' => '14', '15' => '15', 
					'16' => '16', '17' => '17', '18' => '18', '19' => '19', '20' => '20', 
					'21' => '21', '22' => '22', '23' => '23', '24' => '24', 
				));
				foreach( $checkin_hours as $hour_key => $hour ){ 
					if( $hour < $checkin_hour){ 
						unset($checkin_hours[$hour_key]); 
					}else{
						break;
					}
				}
				$ret .= '<span class="gdlr-reservation-time-title" >' . __('Hour', 'gdlr-hotel') . '</span>';
				$ret .= gdlr_get_reservation_combobox2(array(
					'slug'=>'gdlr-checkin-hour',
					'id'=>'gdlr-checkin-hour',
					'value'=>$checkin_hour
				), $checkin_hours);

				$checkin_min = empty($hotel_option['checkin-time-min'])? '00': $hotel_option['checkin-time-min'];
				$ret .= '<span class="gdlr-reservation-time-sep" >:</span>';
				$ret .= '<span class="gdlr-reservation-time-title" >' . __('Min', 'gdlr-hotel') . '</span>';
				$ret .= gdlr_get_reservation_combobox2(array(
					'slug'=>'gdlr-checkin-min',
					'id'=>'gdlr-checkin-min',
					'value'=>$checkin_min
				), apply_filters('gdlr-hotel-reservation-mins', array(
					'00' => '00', '15' => '15', '30' => '30', '45' => '45',
				)));

				$ret .= '</div>';
				$ret .= '<div class="clear" ></div>';
			}
			
			// room
			$enable_multi_room = empty($hotel_option['enable-multi-room-selection'])? 'enable': $hotel_option['enable-multi-room-selection'];
			$max_room = empty($hotel_option['maximum-room-selected'])? 10: intval($hotel_option['maximum-room-selected']) + 1;
			if( $enable_multi_room == 'enable' ){
				$ret .= gdlr_get_reservation_combobox(array(
					'title'=>__('Rooms', 'gdlr-hotel'),
					'slug'=>'gdlr-room-number',
					'id'=>'gdlr-room-number',
					'class'=>'gdlr-reservation-bar-room-number',
					'value'=>$value['gdlr-room-number']
				), 1, $max_room);
				$ret .= '<div class="clear"></div>';
			}else{
				$ret .= '<input type="hidden" name="gdlr-room-number" id="gdlr-room-number" value="1" />';
			}
			if( !empty($hotel_option['enable-adult-child-option']) && $hotel_option['enable-adult-child-option'] == 'disable' ){
				$value['gdlr-adult-number'] = array(1);
				$value['gdlr-child-number'] = array(0);
				$ret .= '<div class="gdlr-reservation-people-amount-wrapper gdlr-reservation-people-amount-hide" id="gdlr-reservation-people-amount-wrapper" >';
			}else{
				$ret .= '<div class="gdlr-reservation-people-amount-wrapper" id="gdlr-reservation-people-amount-wrapper" >';
			}
			
			for($i=0; $i<$value['gdlr-room-number']; $i++){	
				$ret .= '<div class="gdlr-reservation-people-amount">';
				if( $enable_multi_room == 'enable' ){
					$ret .= '<div class="gdlr-reservation-people-title" >' . __('Room', 'gdlr-hotel') .  ' <span>' . ($i+1) . '</span></div>';
				}
				$ret .= gdlr_get_reservation_combobox(array(
					'title'=>__('Adults', 'gdlr-hotel'),
					'slug'=>'gdlr-adult-number',
					'id'=>'',
					'value'=>$value['gdlr-adult-number'][$i],
					'multiple'=>true
				), 1);
				$ret .= gdlr_get_reservation_combobox(array(
					'title'=>__('Children', 'gdlr-hotel'),
					'slug'=>'gdlr-children-number',
					'id'=>'',
					'value'=>$value['gdlr-children-number'][$i],
					'multiple'=>true
				));
				$ret .= '<div class="clear"></div>';
				$ret .= '</div>';
			}
			$ret .= '</div>'; // gdlr-reservation-people-amount-wrapper
			$ret .= '<div class="clear"></div>';
			
			if( $single_form ){
				$ret .= '<input type="hidden" name="hotel_data" value="1" >';
				$ret .= '<input type="submit" class="gdlr-reservation-bar-button gdlr-button with-border" value="' . __('Check Availability', 'gdlr-hotel') . '" >';
			}else if( empty($_POST['hotel_data']) ){
				$ret .= '<a id="gdlr-reservation-bar-button" class="gdlr-reservation-bar-button gdlr-button with-border" href="#" >' . __('Check Availability', 'gdlr-hotel') . '</a>';
			}
			$ret .= '<div class="clear"></div>';
			return $ret;
		}
	}
	
	if( !function_exists('gdlr_get_reservation_datepicker') ){
		function gdlr_get_reservation_datepicker($option){
			global $theme_option, $hotel_option;
			
			$ret  = '<div class="gdlr-reservation-field gdlr-resv-datepicker">';
			$ret .= '<span class="gdlr-reservation-field-title">' . $option['title']  . '</span>';
			$ret .= '<div class="gdlr-datepicker-wrapper">';
			$ret .= '<input type="text"  id="' . $option['id'] . '" class="gdlr-datepicker" data-current-date="' . esc_attr(current_time('Y-m-d')) . '" autocomplete="off" ';
			$ret .= (empty($option['minimum-night']))? '': 'data-min-night="' . $option['minimum-night'] . '" ';
			$ret .= (empty($theme_option['datepicker-format']))? '': 'data-dfm="' . $theme_option['datepicker-format'] . '" ';
			if( $hotel_option['preserve-booking-room'] == 'paid' ){ 
				$data_block = get_option('gdlr-hotel-unavailable-room-paid', array());
			}else{
				$data_block = get_option('gdlr-hotel-unavailable-room-booking', array());
			}
			$ret .= 'data-block="' . esc_attr(json_encode(array_values($data_block))) . '" ';
			$ret .= (empty($option['value'])? '': 'value="' . $option['value'] . '" ') . '/>';
			
			$ret .= '<input type="hidden" class="gdlr-datepicker-alt" name="' . $option['slug'] . '" autocomplete="off" ';
			$ret .= (empty($option['value'])? '': 'value="' . $option['value'] . '" ') . '/>';
			$ret .= '</div>'; // gdlr-datepicker-wrapper
			$ret .= '</div>';
			return $ret;
		}
	}
	
	if( !function_exists('gdlr_get_reservation_combobox') ){
		function gdlr_get_reservation_combobox($option, $min_num = 0, $max_num = 10){
			$ret  = '<div class="gdlr-reservation-field gdlr-resv-combobox ' . (empty($option['class'])? '': $option['class']) . '">';
			$ret .= '<span class="gdlr-reservation-field-title">' . $option['title'] . '</span>';
			$ret .= '<div class="gdlr-combobox-wrapper">';
			$ret .= '<select name="' . $option['slug'] . (empty($option['multiple'])? '': '[]') . '" ';
			$ret .= !empty($option['id'])? 'id="' . $option['id'] . '" >': '>';
			for( $i=$min_num; $i<$max_num; $i++ ){
				$ret .= '<option value="' . $i . '" ' . ((!empty($option['value']) && $i==$option['value'])? 'selected':'') . ' >' . $i . '</option>';
			}
			if( !empty($option['value']) && $option['value'] >= $max_num ){
				$ret .= '<option value="' . $option['value'] . '" selected >' . $option['value'] . '</option>';
			}
			$ret .= '</select>';
			$ret .= '</div>'; // gdlr-combobox-wrapper
			$ret .= '</div>';			
			return $ret;
		}
	}
	
	if( !function_exists('gdlr_get_reservation_combobox2') ){
		function gdlr_get_reservation_combobox2($settings, $options){
			$ret  = '<div class="gdlr-combobox-wrapper">';
			$ret .= '<select name="' . $settings['slug'] . (empty($settings['multiple'])? '': '[]') . '" ';
			$ret .= !empty($settings['id'])? 'id="' . $settings['id'] . '" >': '>';
			foreach( $options as $option_key => $option_val ){
				$ret .= '<option value="' . esc_attr($option_key) . '" ' . ($option_key == $settings['value']? 'selected': '') . ' >' . esc_html($option_val) . '</option>';
			}
			$ret .= '</select>';
			$ret .= '</div>'; // gdlr-combobox-wrapper
			return $ret;
		}
	}		
	
	if( !function_exists('gdlr_get_reservation_branch_combobox') ){
		function gdlr_get_reservation_branch_combobox($option, $min_num = 0, $max_num = 10){
			$branches = gdlr_get_term_id_list('room_category');

			$ret  = '<div class="gdlr-reservation-field gdlr-resv-branches-combobox">';
			$ret .= '<span class="gdlr-reservation-field-title">' . $option['title'] . '</span>';
			$ret .= '<div class="gdlr-combobox-wrapper">';
			$ret .= '<select name="' . $option['slug'] . '" ';
			$ret .= !empty($option['id'])? 'id="' . $option['id'] . '" >': '>';
			$ret .= '<option value="" >' . __('Please select hotel branch', 'gdlr-hotel') . '</option>';
			foreach( $branches as $slug => $branch ){
				$ret .= '<option value="' . $slug . '" ' . ((!empty($option['value']) && $slug==$option['value'])? 'selected':'') . ' >' . $branch . '</option>';
			}
			$ret .= '</select>';
			$ret .= '</div>'; // gdlr-combobox-wrapper
			$ret .= '<div id="please-select-branches" >' . __('* Please select branch', 'gdlr-hotel') . '</div>';
			$ret .= '</div>';			
			return $ret;
		}
	}	
	
?>