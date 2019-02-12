<?php 
	/////////////////////////////////
	/* init plugin for version 3.0 */
	/////////////////////////////////
	if( !function_exists('gdlr_hotel_event_init') ){
		function gdlr_hotel_event_init(){
			gdlr_hotel_set_list_dates();

			gdlr_hotel_set_block_dates();
			gdlr_hotel_set_hotel_availability();
		}
	}

	if( !function_exists('gdlr_hotel_set_list_dates') ){
		function gdlr_hotel_set_list_dates(){
			global $wpdb;

			$sql = "SELECT id, start_date, end_date from {$wpdb->prefix}gdlr_hotel_booking WHERE date_list IS NULL";
			$results = $wpdb->get_results($sql);

			foreach( $results as $result ){
				$wpdb->update($wpdb->prefix . 'gdlr_hotel_booking',
					array('date_list' => implode(',', gdlr_hotel_list_dates($result->start_date, $result->end_date))), 
					array('id' => $result->id),
					array('%s'), 
					array('%d')
				);
			}
		}
	}
	if( !function_exists('gdlr_hotel_list_dates') ){
		function gdlr_hotel_list_dates( $checkin, $checkout ){
			$ret = array();

			$from = new DateTime($checkin);
			$to = new DateTime($checkout);
			$interval = new DateInterval('P1D');
			$periods = new DatePeriod($from, $interval, $to);

			foreach($periods as $period){
				$ret[] = $period->format('Y-m-d');
			}

			return $ret;
		}
	}
	//////////////////////////////////////////
	//////////////////////////////////////////
	
	// check the hotel block dates
	add_action('gdlr_save_hotel_option', 'gdlr_hotel_set_block_dates');
	if( !function_exists('gdlr_hotel_set_block_dates') ){
		function gdlr_hotel_set_block_dates(){
			$hotel_option = get_option('gdlr_hotel_option', array());

			// assign the block date first
			if( !empty($hotel_option['block-date']) ){
				$block_dates = gdlr_hotel_list_date_format($hotel_option['block-date']);	
				
				update_option('gdlr-hotel-block-dates', $block_dates);

				gdlr_hotel_set_hotel_availability('saved');
			}

			return array();

		} // gdlr_hotel_get_block_dates
	}

	// set all hotel availability 
	if( !function_exists('gdlr_hotel_set_hotel_availability') ){
		function gdlr_hotel_set_hotel_availability( $action = 'new' ){

			// query post list
			$args = array('post_type' => 'room', 'suppress_filters' => false);
			$args['posts_per_page'] = '999';
			$args['orderby'] = (empty($settings['orderby']))? 'post_date': $settings['orderby'];
			$args['order'] = (empty($settings['order']))? 'desc': $settings['order'];
			$args['paged'] = 1;		
			$query = new WP_Query( $args );

			$count = 1;
			$room_paid = array();
			$room_book = array();
			while( $query->have_posts() ){ $query->the_post();

				if( $action == 'new' ){
					$temp_room_paid = gdlr_set_room_availability(get_the_ID(), 'paid');
					$temp_room_book = gdlr_set_room_availability(get_the_ID(), 'booking');
				}else{
					$temp_room_paid = get_post_meta(get_the_ID(), 'hotel_paid_not_available', true);
					$temp_room_paid = is_array($temp_room_paid)? $temp_room_paid: array();
					$temp_room_book = get_post_meta(get_the_ID(), 'hotel_booking_not_available', true);
					$temp_room_book = is_array($temp_room_book)? $temp_room_book: array();
				}

				if( $count == 1 ){
					$room_paid = $temp_room_paid;
					$room_book = $temp_room_book;
				}else{
					$room_paid = array_intersect($room_paid, $temp_room_paid);
					$room_book = array_intersect($room_book, $temp_room_book);

					if( empty($room_paid) && empty($room_book) ){
						break;
					}
				}
				
				$count ++;
			}
			wp_reset_postdata();

			$block_room = get_option('gdlr-hotel-block-dates', array());
			$room_paid = array_merge($block_room, $room_paid);
			$room_paid = array_unique($room_paid);
			update_option('gdlr-hotel-unavailable-room-paid', $room_paid);

			$room_book = array_merge($block_room, $room_book);
			$room_book = array_unique($room_book);
			update_option('gdlr-hotel-unavailable-room-booking', $room_book);

		}
	}

	// list date from the date format ( * and to )
	if( !function_exists('gdlr_hotel_list_date_format') ){
		function gdlr_hotel_list_date_format( $date_set, $calculated_year = 2 ){
			$date_list = array();
			$current_date = date('Y-m-d');

			$date_set = array_map('trim', explode(',', $date_set));
			
			// split the "to" criteria 
			foreach( $date_set as $key => $date_format ){
				if( strpos($date_format, 'to') !== false ){
					unset($date_set[$key]);

					$date_array = array_map('trim', explode('to', $date_format));
					
					$from_date = explode('-', $date_array[0]);
					$to_date = explode('-', $date_array[1]);

					$date_set[] = "{$from_date[0]}-{$from_date[1]}-{$from_date[2]}";
					while( ($from_date[0] < $to_date[0]) || ($from_date[1] < $to_date[1]) || ($from_date[2] < $to_date[2]) ){	
						
						if( $from_date[2] == '*' || $from_date[2] == '31' ){
							$from_date[2] = ($from_date[2] == '31')? '01': $from_date[2];

							if( $from_date[1] == '*' || $from_date[1] == '12' ){
								$from_date[1] = ($from_date[1] == '12')? '01': $from_date[1];

								if( $from_date[0] != '*' ){
									$from_date[0] = sprintf("%04s", intval($from_date[0]) + 1);
								}
							}else{
								$from_date[1] = sprintf("%02s", intval($from_date[1]) + 1);
							}
						}else{
							$from_date[2] = sprintf("%02s", intval($from_date[2]) + 1);
						}

						$date_set[] = "{$from_date[0]}-{$from_date[1]}-{$from_date[2]}";
					}
				}
			}

			foreach( $date_set as $date_format ){

				// change * to date list
				if( strpos($date_format, '*') !== false ){

					$date_array = explode('-', $date_format);

					// list all years
					if( $date_array[0] == '*' ){
						$date_array[0] = array();
						$current_year = date('Y');
						for( $i = 0; $i<=$calculated_year; $i++ ){
							$date_array[0][] = $current_year + $i;
						}
					}else{
						$date_array[0] = array($date_array[0]);
					}

					// list all month
					if( $date_array[1] == '*' ){
						$date_array[1] = array();
						for( $i = 1; $i<=12; $i++ ){
							$date_array[1][] = $i;
						}
					}else{
						$date_array[1] = array($date_array[1]);
					}

					// list all dates
					if( $date_array[2] == '*' ){
						$date_array[2] = array();
						for( $i = 1; $i<=31; $i++ ){
							$date_array[2][] = $i;
						}
					}else{
						$date_array[2] = array($date_array[2]);
					}

					foreach( $date_array[0] as $year ){
						foreach( $date_array[1] as $month ){
							$month = sprintf("%02s", $month);
							foreach( $date_array[2] as $day ){
								$day = sprintf("%02s", $day);
								$temp_date =  "{$year}-{$month}-{$day}";
								if( $current_date <= $temp_date ){
									$date_list[] = $temp_date;
								} 
							}
						}
					}

				}else{
					if( $current_date <= $date_format ){
						$date_list[] = $date_format;
					}
				}

			}
			$date_list = array_unique($date_list);

			return $date_list;
		} // gdlr_hotel_list_date_format
	}

	// set the single room availability 
	if( !function_exists('gdlr_set_room_availability') ){
		function gdlr_set_room_availability( $room_id, $preserve_after = 'paid' ){
			global $wpdb, $hotel_option;

			$not_avail_dates = array();
			$current_date = date('Y-m-d');
			$room_amount = get_post_meta($room_id, 'gdlr_room_amount', true);

			// block dates
			$block_room = get_post_meta($room_id, 'gdlr_block_date', true);
			$not_avail_dates = gdlr_hotel_list_date_format($block_room);
			
			// get room id ( for multi lingual )
			$room_ids = '';
			if( function_exists('pll_get_post_translations') ){
				$pll_translations = pll_get_post_translations($room_id);
				foreach( $pll_translations as $translation ){
					$room_ids .= empty($room_ids)? '': ',';
					$room_ids .= '\'' . $translation . '\'';
				}
			}else if( !empty($sitepress) ){
				$trid = $sitepress->get_element_trid($room_id, 'post_room');
				$translations = $sitepress->get_element_translations($trid,'post_room');
				foreach( $translations as $translation ){
					$room_ids .= empty($room_ids)? '': ',';
					$room_ids .= '\'' . $translation->element_id . '\'';
				}
			}else{
				$room_ids = '\'' . $room_id . '\'';
			}

			// check the date which the room is full
			$sql  = "SELECT payment_table.id, booking_table.date_list ";
			$sql .= "FROM {$wpdb->prefix}gdlr_hotel_booking AS booking_table, {$wpdb->prefix}gdlr_hotel_payment AS payment_table WHERE ";
			$sql .= "payment_table.id = booking_table.payment_id AND ";
			if( $preserve_after == 'paid' ){ 
				$sql .= "payment_table.payment_status = 'paid' AND ";
			}else{
				$sql .= "payment_table.payment_status != 'pending' AND ";
			}
			$sql .= "booking_table.room_id IN ({$room_ids}) AND ";
			$sql .= "booking_table.end_date > '{$current_date}' ";

			$results = $wpdb->get_results($sql);

			$booked_room = array();
			foreach( $results as $result ){
				$date_list = explode(',', $result->date_list);
				foreach( $date_list as $booked_date ){
					$booked_room[$booked_date] = empty($booked_room[$booked_date])? 1: $booked_room[$booked_date]+1;

					if( $booked_room[$booked_date] >= $room_amount ){
						$not_avail_dates[] = $booked_date;
					} 
				}
			}

			// for ical
			if( $room_amount == 1 ){
				$file_url = get_post_meta($room_id, 'gdlr_ical_sync_url', true);

				if( !empty($file_url) ){
					$ical_room_list = get_post_meta($room_id, 'gdlr_ical_sync_date_list', true);
					if( !empty($ical_room_list) ){
						$not_avail_dates = array_merge($not_avail_dates, $ical_room_list);
					}
				}
			}

			$not_avail_dates = array_unique($not_avail_dates);
			update_post_meta($room_id, 'hotel_' . $preserve_after . '_not_available', $not_avail_dates);

			return $not_avail_dates;
		}
	}
	
	// update single room availability 
	add_action('gdlr_update_room_availability', 'gdlr_update_room_availability');
	if( !function_exists('gdlr_update_room_availability') ){
		function gdlr_update_room_availability( $room_id ){

			// query post list
			$args = array('post_type' => 'room', 'suppress_filters' => false);
			$args['posts_per_page'] = '999';
			$args['post__not_in'] = array($room_id);
			$args['orderby'] = (empty($settings['orderby']))? 'post_date': $settings['orderby'];
			$args['order'] = (empty($settings['order']))? 'desc': $settings['order'];
			$args['paged'] = 1;		
			$query = new WP_Query( $args );

			$room_paid = gdlr_set_room_availability($room_id, 'paid');
			$room_book = gdlr_set_room_availability($room_id, 'booking');
			while( $query->have_posts() ){ $query->the_post();
				$temp_paid = get_post_meta(get_the_ID(), 'hotel_paid_not_available', true);
				$temp_paid = is_array($temp_paid)? $temp_paid: array();
				$temp_book = get_post_meta(get_the_ID(), 'hotel_booking_not_available', true);
				$temp_book = is_array($temp_book)? $temp_book: array();

				$room_paid = array_intersect($room_paid, $temp_paid);
				$room_book = array_intersect($room_book, $temp_book);

				if( empty($room_paid) && empty($room_book) ){
					break;
				}
			}
			wp_reset_postdata();

			$block_room = get_option('gdlr-hotel-block-dates', array());
			$room_paid = array_merge($block_room, $room_paid);
			$room_paid = array_unique($room_paid);
			update_option('gdlr-hotel-unavailable-room-paid', $room_paid);

			$room_book = array_merge($block_room, $room_book);
			$room_book = array_unique($room_book);
			update_option('gdlr-hotel-unavailable-room-booking', $room_book);

		}
	}
	add_action('save_post', 'gdlr_hotel_save_room_option_event', 999);
	add_action('pre_post_update', 'gdlr_hotel_save_room_option_event', 999);
	if( !function_exists('gdlr_hotel_save_room_option_event') ){
		function gdlr_hotel_save_room_option_event( $post_id ){
			$post_type = get_post_type($post_id);

			if( $post_type == 'room' ){
				gdlr_update_room_availability($post_id);
			}
		}
	}	

	add_action('gdlr_update_transaction_availability', 'gdlr_update_transaction_availability', 10, 2);
	if( !function_exists('gdlr_update_transaction_availability') ){
		function gdlr_update_transaction_availability( $data, $type = 'tid' ){
			global $wpdb;

			if( $type == 'tid' ){
				$tid = $data;
				$sql  = "SELECT DISTINCT room_id ";
				$sql .= "FROM {$wpdb->prefix}gdlr_hotel_booking WHERE ";
				$sql .= "payment_id = {$tid}";

				$results = $wpdb->get_results($sql);
			}else{
				$results = $data;
			}

			if( !empty($results) ){
				$count = sizeof($results);
				foreach( $results as $result ){
					if( $count == 1 ){
						gdlr_update_room_availability($result->room_id);
					}else{
						gdlr_set_room_availability($result->room_id, 'paid');
						gdlr_set_room_availability($result->room_id, 'booking');
					}

					$count--;
				}
			}

		}
	}
	
	////////// ICAL ////////////////
	if( !class_exists('gdlr_hotel_ics') ){
		class gdlr_hotel_ics{
		    
		    /* Function is to get all the contents from ics and explode all the datas according to the events and its sections */
		    function getIcsEventsAsArray($file) {
		        $icsDates = array();
		        $retDates = array();

		        $icalString = wp_remote_get($file);

		        /* Explode the ICs Data to get datas as array according to string 'BEGIN:' */
		        if( is_wp_error($icalString) ){
		        	// print_r($icalString);
		        }else if( !empty($icalString['body']) ){
		        	$icsData = explode("BEGIN:", $icalString['body']);
		    	}

		        /* Iterating the icsData value to make all the start end dates as sub array */
		        if( !empty($icsData) ){
			        foreach( $icsData as $key => $value){
			            $icsDatesMeta[$key] = explode ( "\n", $value );
			        }
			    }

		        /* Itearting the Ics Meta Value */
		        if( !empty($icsDatesMeta) ){
			        foreach( $icsDatesMeta as $key => $value ) {
			            foreach ( $value as $subKey => $subValue ){
			                $icsDates = $this->getICSDates($key, $subKey, $subValue, $icsDates);
			            }

			            if( !empty($icsDates[$key]['DTSTART']) && !empty($icsDates[$key]['DTEND']) ){
			            	$retDates[] = array(
			            		'check-in' => $icsDates[$key]['DTSTART'],
			            		'check-out' => $icsDates[$key]['DTEND']
			            	);
			            }
			        }
		        }

		        return $retDates;
		    }

		    /* funcion is to avaid the elements wich is not having the proper start, end  and summary informations */
		    function getICSDates($key, $subKey, $subValue, $icsDates) {
		      
		       if( $key != 0 && $subKey == 0 ){
		            $icsDates[$key]["BEGIN"] = $subValue;
		       }else{
		            $subValueArr = explode(":", $subValue, 2);
		            if( isset($subValueArr[1]) ){
		            	if( strpos($subValueArr[0], 'DTSTART') !== false ){
		            		$subValueArr[0] = 'DTSTART';
		            	}else if( strpos($subValueArr[0], 'DTEND') !== false ){
		            		$subValueArr[0] = 'DTEND';
		            	}

		            	if( $subValueArr[0] == 'DTSTART' || $subValueArr[0] == 'DTEND' ){
		            		$subValueArr[1] = date('Y-m-d', strtotime($subValueArr[1]));
		            	}

		                $icsDates[$key][$subValueArr[0]] = $subValueArr[1];
		            }
		        }

		        return $icsDates;
		    }
		}
	}
	if( !function_exists('gdlr_hotel_set_ical') ){
		function gdlr_hotel_set_ical( $post_id, $file_url ){

			$old_data = get_post_meta($post_id, 'gdlr_ical_sync_data', true);
			$ical_data = array();
			$ics = new gdlr_hotel_ics();

			// check if there're multiple files
			if( strpos($file_url, "\r\n") !== false ){
				$files = explode("\r\n", $file_url);
			}else{
				$files = explode("\n", $file_url);
			}
			foreach( $files as $file ){
				if( !empty($file) ){
					$ical_data = array_merge($ical_data, $ics->getIcsEventsAsArray($file));
				}
			}

			// if the data is new, save it
			if( $old_data != $ical_data ){
				update_post_meta($post_id, 'gdlr_ical_sync_data', $ical_data);

				// list date
				$ical_date_list = array();
				foreach( $ical_data as $ical_date ){
					$ical_date_list = array_merge($ical_date_list, gdlr_hotel_list_dates($ical_date['check-in'], $ical_date['check-out']));
				}
				$ical_date_list = array_unique($ical_date_list);
				update_post_meta($post_id, 'gdlr_ical_sync_date_list', $ical_date_list);

				gdlr_set_room_availability( $post_id, 'paid' );
				gdlr_set_room_availability( $post_id, 'booking' );

				return true;
			}

			return false;
		}

	}

	add_action('init', 'gdlr_hotel_ical_routine');
	if( !function_exists('gdlr_hotel_ical_routine') ){
		function gdlr_hotel_ical_routine(){
			global $hotel_option, $wpdb;
			
			$current_time = strtotime('now');

			$timestamp = get_option('gdlr_ical_sync_timestamp', 0);
			$cache_time = empty($hotel_option['ical-cache-time'])? 300: (intval($hotel_option['ical-cache-time']) * 60);
			
			if( empty($timestamp) || $timestamp + $cache_time < $current_time ){
				$sql  = "SELECT post_id, meta_value FROM {$wpdb->postmeta} ";
				$sql .= "WHERE meta_key = 'gdlr_ical_sync_url' AND ";
				$sql .= "meta_value IS NOT NULL AND meta_value <> '' ";

				$updated = false;
				$results = $wpdb->get_results($sql);
				if( !empty($results) ){
					foreach( $results as $result ){
						$updated = $updated || gdlr_hotel_set_ical($result->post_id, $result->meta_value);
					} 

					if( $updated ){
						gdlr_hotel_set_hotel_availability('saved');
					}
				} 

				update_option('gdlr_ical_sync_timestamp', $current_time);
			}
		} // gdlr_hotel_ical_routine
	}

	// return true if the room is booked
	if( !function_exists('gdlr_hotel_ical_booked') ){
		function gdlr_hotel_ical_booked( $post_id, $check_in, $check_out ){
			$file_url = get_post_meta($post_id, 'gdlr_ical_sync_url', true);

			if( !empty($file_url) ){
				$ical_data = get_post_meta($post_id, 'gdlr_ical_sync_data', true);

				if( !empty($ical_data) ){
					foreach( $ical_data as $data ){
						if( $check_in < $data['check-out'] && $check_out > $data['check-in'] ){
							return true;
						}
					} 
				}
			}

			return false;
		}
	}

	add_action('init', 'gdlr_hotel_generate_ical_content');
	if( !function_exists('gdlr_hotel_generate_ical_content') ){
		function gdlr_hotel_generate_ical_content(){
			
			if( isset($_GET['hotelmaster_ical']) && !empty($_GET['room_id']) && is_numeric($_GET['room_id']) ){
				global $wpdb, $hotel_option;

				$content  = "BEGIN:VCALENDAR\n";
				$content .= "VERSION:2.0\n";
				//$content .= "PRODID:-//hacksw/handcal//NONSGML v1.0//EN\n"	

				$hotel_option['ical-start-time'] = empty($hotel_option['ical-start-time'])? 2: $hotel_option['ical-start-time'];
				$ical_start_time = date('Y-m-d', strtotime("-{$hotel_option['ical-start-time']} month"));

				$sql  = "SELECT booking_table.start_date, booking_table.end_date ";
				$sql .= "FROM {$wpdb->prefix}gdlr_hotel_booking AS booking_table, {$wpdb->prefix}gdlr_hotel_payment AS payment_table ";
				$sql .= "WHERE payment_table.id = booking_table.payment_id ";
				$sql .= "AND booking_table.room_id = {$_GET['room_id']} ";
				if( empty($hotel_option['preserve-booking-room']) || $hotel_option['preserve-booking-room'] == 'paid' ){ 
					$sql .= "AND payment_table.payment_status = 'paid' ";
				}else{
					$sql .= "AND payment_table.payment_status != 'pending' ";
				}

				$results = $wpdb->get_results($sql);

		        if( !empty($results) ){
			        foreach( $results as $result ){
			        	$start_time = str_replace('-', '', $result->start_date);
			        	$start_time = str_replace(' 00:00:00', '', $start_time);
			        	$end_time = str_replace('-', '', $result->end_date);
			        	$end_time = str_replace(' 00:00:00', '', $end_time);

			        	if( $ical_start_time > $result->end_date ){
			        		continue;
			        	}

			            $content .= "BEGIN:VEVENT\n";
			            $content .= "UID:" . $start_time . get_option('admin_email', 'wordpress@hotelmaster.com') . "\n";
			            $content .= "DTSTAMP:" . $start_time . "T000000Z\n";
			            $content .= "DTSTART;VALUE=DATE:" . $start_time . "\n";
			            $content .= "DTEND;VALUE=DATE:" . $end_time . "\n";
			            $content .= "SUMMARY:" . get_the_title($_GET['room_id']) . "\n";
			            $content .= "END:VEVENT\n";
			        }
		        }

		        $content .= "END:VCALENDAR";

		        header("Content-type:text/calendar");
		        header('Content-Disposition: attachment; filename="hotelmaster_ical.ics"');
		        header('Content-Length: '.strlen($content));
		        header('Connection: close');
		        echo $content;
		        exit();
			}
		}
	}
