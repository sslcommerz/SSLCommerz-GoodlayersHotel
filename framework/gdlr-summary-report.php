<?php
	if( !function_exists('gdlr_hotel_summary_report') ){
		function gdlr_hotel_summary_report(){
			global $hotel_option;
			
			$filter_val = empty($_GET['filter'])? '30days': $_GET['filter'];

			$date_from = empty($_GET['date-from'])? '': $_GET['date-from'];
			$date_to = empty($_GET['date-to'])? '': $_GET['date-to'];

			if( !empty($date_from) || !empty($date_to) ){
				$query_date_from = $date_from;
				$query_date_to = $date_to;
			}else if( $filter_val == '30days' ){
				$query_date_from = date('Y-m-d', strtotime('-31 day'));
				$query_date_to = date('Y-m-d', strtotime('-1 day'));
			}else if( $filter_val == '7days' ){
				$query_date_from = date('Y-m-d', strtotime('-8 day'));
				$query_date_to = date('Y-m-d', strtotime('-1 day'));
			}else if( strlen($filter_val) == 7 ){
				$query_date_from = date('Y-m-d', strtotime($filter_val . '-01'));
				$query_date_to = date('Y-m-t', strtotime($filter_val . '-01'));
			}

			
?>
<div class="gdlr-transaction-wrapper">
	<h3><?php _e('Summary Report', 'gdlr-hotel'); ?></h3>
	<form class="gdlr-transaction-form" method="GET" action="">
		<div class="gdlr-transaction-row">
			<span class="gdlr-transaction-head"><?php _e('Date Filter :', 'gdlr-hostel'); ?></span>
			<span class="gdlr-transaction-sep" ><?php _e('From', 'gdlr-hostel'); ?></span>
			<input class="gdlr-transaction-date datepicker" type="text" name="date-from" value="<?php echo esc_attr($date_from); ?>" />
			<span class="gdlr-transaction-sep" ><?php _e('To', 'gdlr-hostel'); ?></span>
			<input class="gdlr-transaction-date datepicker" type="text" name="date-to" value="<?php echo esc_attr($date_to); ?>" />
			<input type="hidden" name="page" value="hotel-summary-report" />
			<input type="submit" class="gdlr-with-margin" value="<?php _e('Filter!', 'gdlr-hostel'); ?>" />
		</div>
	</form>

	<form class="gdlr-transaction-table" method="post" >
		<input type="hidden" name="transaction-type" value="" />

		<div class="transaction-filter">
			<span class="transaction-filter-title"><?php _e('Quick Filter :', 'gdlr-hotel'); ?></span>
			<?php
				$filters = array(
					'30days' => __('Show Last 30 Days', 'gdlr-hotel'),
					'7days' => __('Show Last 7 Days', 'gdlr-hotel'),
				);

				$tmp_time = strtotime('-1 month');
				$filters[date('Y-m', $tmp_time)] = date_i18n('M Y', $tmp_time);

				$tmp_time = strtotime('-2 month');
				$filters[date('Y-m', $tmp_time)] = date_i18n('M Y', $tmp_time);

				$tmp_time = strtotime('-3 month');
				$filters[date('Y-m', $tmp_time)] = date_i18n('M Y', $tmp_time);

				$query_url = remove_query_arg(array('date-from', 'date-to'));

				$count = 0;
				foreach( $filters as $filter_key => $filter_value ){
					echo empty($count)? '': '<span class="gdlr-hotel-sep" >|</span>';
					echo '<a class="' . ($filter_val == $filter_key? 'gdlr-active': '') . '" href="' . add_query_arg(array('filter'=>$filter_key), $query_url) . '" >' . esc_html($filter_value) . '</a>';

					$count++;
				}
			?>
		</div>
<?php
	// query post list
	$args = array('post_type' => 'room', 'suppress_filters' => false);
	$args['posts_per_page'] = '999';
	$args['orderby'] = (empty($settings['orderby']))? 'post_date': $settings['orderby'];
	$args['order'] = (empty($settings['order']))? 'desc': $settings['order'];
	$args['paged'] = 1;		
	$query = new WP_Query( $args );

	$rooms = array();
	while( $query->have_posts() ){ $query->the_post();
		$rooms[get_the_ID()] = 0; 
	}
	wp_reset_postdata();

	// checking the room date
	global $wpdb, $sitepress;

	$sql  = "SELECT booking_table.room_id, booking_table.start_date, booking_table.end_date ";
	$sql .= "FROM {$wpdb->prefix}gdlr_hotel_booking AS booking_table, {$wpdb->prefix}gdlr_hotel_payment AS payment_table WHERE ";
	$sql .= "payment_table.id = booking_table.payment_id AND ";
	if( $hotel_option['preserve-booking-room'] == 'paid' ){ 
		$sql .= "payment_table.payment_status = 'paid' AND ";
	}else{
		$sql .= "payment_table.payment_status != 'pending' AND ";
	}

	$sql .= "(booking_table.end_date >= '{$query_date_from}' AND ";
	$sql .= "booking_table.start_date <= '{$query_date_to}') ";

	$results = $wpdb->get_results($sql);
	foreach( $rooms as $room_id => $room_num ){
		$room_group = array();

		if( function_exists('pll_get_post_translations') ){
			$pll_translations = pll_get_post_translations($room_id);
			if( !empty($pll_translations) ){
				$room_group = $pll_translations;
			}

		// wpml is enable
		}else if( !empty($sitepress) ){
			$trid = $sitepress->get_element_trid($room_id, 'post_room');
			$translations = $sitepress->get_element_translations($trid,'post_room');
			if( !empty($translations) ){
				foreach( $translations as $translation ){
					$room_temp[] = $translation->element_id; 
				}
			}
		}

		if( empty($room_group) ){
			$room_group[] = $room_id;
		}

		foreach( $results as $result ){
			if( in_array($result->room_id, $room_group) ){
				if( strtotime($result->start_date) >= strtotime($query_date_from) ){
					$temp_start_date = $result->start_date;
				}else{
					$temp_start_date = $query_date_from;
				}
				if( strtotime($result->end_date) >= strtotime($query_date_to) ){
					$temp_end_date = $query_date_to;
				}else{
					$temp_end_date = $result->end_date;
				}

				if( strtotime($temp_start_date) == strtotime($temp_end_date) ){
					$rooms[$room_id] += 1;
				}else{
					$date_list = gdlr_split_date($temp_start_date, $temp_end_date);
					$rooms[$room_id] += sizeof($date_list);
				}
			}
		}
	}
?>

		<table>
			<tr>
				<th><?php _e('Room', 'gdlr-hotel'); ?></th>
				<?php if( !empty($hotel_option['enable-hotel-branch']) && $hotel_option['enable-hotel-branch'] == 'enable' ){ ?>
					<th><?php _e('Branch', 'gdlr-hotel'); ?></th>
				<?php } ?>
				<th><?php _e('#Night x Room', 'gdlr-hotel'); ?></th>
				<th><?php _e('Occupancy Rate', 'gdlr-hotel'); ?></th>
			</tr>

			<?php
				$date_list = gdlr_split_date($query_date_from, $query_date_to);
				$date_count = sizeof($date_list);
				foreach( $rooms as $room_id => $occupy_times ){
					$room_amount = get_post_meta($room_id, 'gdlr_room_amount', true);
					$room_amount = empty($room_amount)? 1: $room_amount;

					echo '<tr>';
					echo '<td>' . get_the_title($room_id);
					if( $room_amount > 1 ){
						echo ' (' . $room_amount . ' ' . __('Rooms', 'gdlr-hotel') . ')';
					} 
					echo '</td>';
					if( !empty($hotel_option['enable-hotel-branch']) && $hotel_option['enable-hotel-branch'] == 'enable' ){
						$room_terms = get_the_terms($room_id, 'room_category');
						echo '<td>';
						$count = 0;
						foreach( $room_terms as $room_term ){
							echo empty($count)? '': ', ';
							echo $room_term->name;

							$count++;
						}
						echo '</td>';

						echo '<td>' . $occupy_times . '</td>';
						echo '<td>' . number_format_i18n(100 * $occupy_times / ($date_count * $room_amount), 2) . '%</td>';
					}
					echo '</tr>';
				}
			?>
		</table>
	</form>
</div>
<?php	
		}
	}