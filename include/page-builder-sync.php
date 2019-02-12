<?php
	
	// add hotel availability item
	add_filter('gdlr_page_builder_option', 'gdlr_register_hotel_availability_item');
	if( !function_exists('gdlr_register_hotel_availability_item') ){
		function gdlr_register_hotel_availability_item( $page_builder = array() ){
			global $gdlr_spaces;
		
			$page_builder['content-item']['options']['hotel-availability'] = array(
				'title'=> __('Hotel & Apartment Room Availability', 'gdlr_translate'), 
				'type'=>'item',
				'options'=> array_merge(gdlr_page_builder_title_option(true), array(	
					'margin-bottom' => array(
						'title' => __('Margin Bottom', 'gdlr_translate'),
						'type' => 'text',
						'default' => $gdlr_spaces['bottom-item'],
						'description' => __('Spaces after ending of this item', 'gdlr_translate')
					),														
				))
			);
			
			return $page_builder;
		}
	}
	add_action('gdlr_print_item_selector', 'gdlr_check_hotel_availability_item', 10, 2);
	if( !function_exists('gdlr_check_hotel_availability_item') ){
		function gdlr_check_hotel_availability_item( $type, $settings = array() ){
			if($type == 'hotel-availability'){
				echo gdlr_print_hotel_availability_item( $settings );
			}
		}
	}
	
	// print room item
	if( !function_exists('gdlr_print_hotel_availability_item') ){
		function gdlr_print_hotel_availability_item( $settings = array() ){	

			$item_id = empty($settings['page-item-id'])? '': ' id="' . $settings['page-item-id'] . '" ';

			global $gdlr_spaces, $hotel_option;
			$margin = (!empty($settings['margin-bottom']) && 
				$settings['margin-bottom'] != $gdlr_spaces['bottom-blog-item'])? 'margin-bottom: ' . $settings['margin-bottom'] . ';': '';
			$margin_style = (!empty($margin))? ' style="' . $margin . '" ': '';
			
			$current_date = current_time('Y-m-d');
			$minimum_night = empty($hotel_option['minimum-night'])? 1: intval($hotel_option['minimum-night']);
			$next_date = date('Y-m-d', strtotime($current_date . "+{$minimum_night} days"));
			$value = array(
				'gdlr-check-in' => $current_date,
				'gdlr-night' => $minimum_night,
				'gdlr-check-out' => $next_date,
				'gdlr-room-number' => 1,
				'gdlr-adult-number' => 2,
				'gdlr-children-number' => 0
			);
			
			$ret  = gdlr_get_item_title($settings);	

			$ret .= '<div class="gdlr-hotel-availability-wrapper';
			if( !empty($hotel_option['enable-hotel-branch']) && $hotel_option['enable-hotel-branch'] == 'enable' ){
				$ret .= ' gdlr-hotel-branches-enable';
			}
			if( !empty($hotel_option['enable-adult-child-option']) && $hotel_option['enable-adult-child-option'] == 'disable' ){
				$ret .= ' gdlr-hotel-client-disable';
			}
			$ret .= '" ' . $margin_style . $item_id . ' >';
			$ret .= '<form class="gdlr-hotel-availability gdlr-item" id="gdlr-hotel-availability" method="post" action="' . esc_url(add_query_arg(array($hotel_option['booking-slug']=>''), home_url('/'))) . '" >';
			if( !empty($hotel_option['enable-hotel-branch']) && $hotel_option['enable-hotel-branch'] == 'enable' ){
				$ret .= gdlr_get_reservation_branch_combobox(array(
					'title'=>__('Hotel Branches', 'gdlr-hotel'),
					'slug'=>'gdlr-hotel-branches',
					'id'=>'gdlr-hotel-branches',
					'value'=>''
				));
			}
			
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
			$ret .= gdlr_get_reservation_datepicker(array(
				'title'=>__('Check Out', 'gdlr-hotel'),
				'slug'=>'gdlr-check-out',
				'id'=>'gdlr-check-out',
				'minimum-night'=>$minimum_night,
				'value'=>$value['gdlr-check-out']
			));

			if( empty($hotel_option['enable-adult-child-option']) || $hotel_option['enable-adult-child-option'] == 'enable' ){
				$ret .= gdlr_get_reservation_combobox(array(
					'title'=>__('Adults', 'gdlr-hotel'),
					'slug'=>'gdlr-adult-number',
					'id'=>'',
					'value'=>$value['gdlr-adult-number'],
					'multiple'=>true
				));
				$ret .= gdlr_get_reservation_combobox(array(
					'title'=>__('Children', 'gdlr-hotel'),
					'slug'=>'gdlr-children-number',
					'id'=>'',
					'value'=>$value['gdlr-children-number'],
					'multiple'=>true
				));
			}else{
				$ret .= '<input type="hidden" name="gdlr-adult-number[]" value="1" />'; 
				$ret .= '<input type="hidden" name="gdlr-children-number[]" value="0" />'; 
			}
			$ret .= '<div class="gdlr-hotel-availability-submit" >';
			$ret .= '<input type="hidden" name="hotel_data" value="1" >';
			$ret .= '<input type="hidden" name="gdlr-room-number" value="1" />';
			$ret .= '<input type="submit" class="gdlr-reservation-bar-button gdlr-button with-border" value="' . __('Check Availability', 'gdlr-hotel') . '" >';
			$ret .= '</div>';
			
			$ret .= '<div class="clear"></div>';
			$ret .= '</form>';
			$ret .= '</div>';
			
			return $ret;
		}
	}
	
?>