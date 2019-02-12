<?php
	/* transaction page */
	if( !function_exists('gdlr_apply_hotel_transaction') ){
		function gdlr_apply_hotel_transaction(){
			global $wpdb;
			
			if( !empty($_POST['tid']) ){
				foreach($_POST['tid'] as $id){
					if($_POST['transaction-type'] == 'cancel'){
						
						$sql  = "SELECT DISTINCT room_id ";
						$sql .= "FROM {$wpdb->prefix}gdlr_hotel_booking WHERE ";
						$sql .= "payment_id = {$id}";
						$results = $wpdb->get_results($sql);

						$wpdb->delete( 
							$wpdb->prefix . 'gdlr_hotel_payment',
							array('id'=>$id), 
							array('%d') 
						);
						$wpdb->delete( 
							$wpdb->prefix . 'gdlr_hotel_booking',
							array('payment_id'=>$id), 
							array('%d') 
						);

						gdlr_update_transaction_availability($results, 'room_ids');


					}else if( $_POST['transaction-type'] == 'read' || $_POST['transaction-type'] == 'unread' ){
						if($_POST['transaction-type'] == 'read'){
							$status = 'read';
						}else if($_POST['transaction-type'] == 'unread'){
							$status = '';
						}

						$wpdb->update(
							$wpdb->prefix . 'gdlr_hotel_payment',
							array('read_status'=>$status),
							array('id'=>$id),
							array('%s'),
							array('%d')
						);
					}else{

						// send email to user
						$temp_sql  = "SELECT * FROM " . $wpdb->prefix . "gdlr_hotel_payment ";
						$temp_sql .= "WHERE id = " . $id;	
						$result = $wpdb->get_row($temp_sql);

						if($_POST['transaction-type'] == 'paid'){
							$status = 'paid';
							$pay_amount = $result->total_price;
						}else if($_POST['transaction-type'] == 'booking'){
							$status = 'booking';
							$pay_amount = 0;
						}

						$contact_info = unserialize($result->contact_info);
						$data = unserialize($result->booking_data);
						$mail_content = gdlr_hotel_mail_content($contact_info, $data, '', array(
							'total_price'=>$result->total_price, 'pay_amount'=>$pay_amount, 'booking_code'=>$result->customer_code)
						);
						gdlr_hotel_mail($contact_info['email'], __('Thank you for booking the room with us.', 'gdlr-hotel'), $mail_content);
						
						$wpdb->update(
							$wpdb->prefix . 'gdlr_hotel_payment',
							array('payment_status'=>$status, 'pay_amount'=>$pay_amount),
							array('id'=>$id),
							array('%s', '%s'),
							array('%d')
						);

						do_action('gdlr_update_transaction_availability', $id);
					}
				}
			}
		}
	}
	
	if( !function_exists('gdlr_hotel_transaction_option') ){
		function gdlr_hotel_transaction_option(){
			global $hotel_option;
			
			gdlr_apply_hotel_transaction();
			
			$keyword = empty($_GET['keyword'])? '': $_GET['keyword'];
			$checkin_from = empty($_GET['checkin-from'])? '': $_GET['checkin-from'];
			$checkin_to = empty($_GET['checkin-to'])? '': $_GET['checkin-to'];
			$search_by = empty($_GET['search-by'])? '': $_GET['search-by'];
			$sel_branch = empty($_GET['hotel-branches'])? '': $_GET['hotel-branches'];
			$status = empty($_GET['type'])? 'new': $_GET['type'];
			$orderby = empty($_GET['orderby'])? 'id': $_GET['orderby'];
			$order = empty($_GET['order'])? 'desc': $_GET['order'];
			$t_type = empty($_GET['t-type'])? 'all': $_GET['t-type'];
?>
<div class="gdlr-transaction-wrapper">
	<h3><?php _e('Booking List : Deposit Paid', 'gdlr-hotel'); ?></h3>
	<form class="gdlr-transaction-form" method="GET" action="">
		<div class="gdlr-transaction-row">
			<span class="gdlr-transaction-head"><?php _e('Search Transaction By :', 'gdlr-lms'); ?></span>
			<div class="gdlr-combobox-wrapper">
				<select name="search-by" >
					<option value="contact" <?php echo ($search_by=='contact')? 'selected': ''; ?> ><?php _e('Contact', 'gdlr-hotel'); ?></option>
					<option value="room" <?php echo ($search_by=='room')? 'selected': ''; ?> ><?php _e('Room ID', 'gdlr-hotel'); ?></option>
					<option value="code" <?php echo ($search_by=='code')? 'selected': ''; ?> ><?php _e('Code', 'gdlr-hotel'); ?></option>
				</select>
			</div>
		</div>
		<?php if( !empty($hotel_option['enable-hotel-branch']) && $hotel_option['enable-hotel-branch'] == 'enable' ){ ?>
		<div class="gdlr-transaction-row">
			<span class="gdlr-transaction-head"><?php _e('Hotel Branches :', 'gdlr-lms'); ?></span>
			<div class="gdlr-combobox-wrapper">
				<select name="hotel-branches" >
					<option value=""><?php _e('All', 'gdlr-hotel') ?></option>
					<?php
						$branches = gdlr_get_term_id_list('room_category');
						foreach( $branches as $slug => $branch ){
							echo '<option value="' . $slug . '" ' . (($slug == $sel_branch)? 'selected': '') . '>' . $branch . '</option>';
						}
					?>
				</select>
			</div>
		</div>		
		<?php } ?>
		<div class="gdlr-transaction-row">
			<input type="hidden" name="page" value="hotel-transaction" />
			<span class="gdlr-transaction-head"><?php _e('Keywords :', 'gdlr-hotel'); ?></span>
			<input class="gdlr-transaction-keyword" type="text" name="keyword" value="<?php echo esc_attr($keyword); ?>" />
			<input type="submit" value="<?php _e('Search!', 'gdlr-hotel'); ?>" />
		</div>
		<div class="gdlr-transaction-row">
			<span class="gdlr-transaction-head"><?php _e('Check-in Date Filter :', 'gdlr-hostel'); ?></span>
			<span class="gdlr-transaction-sep" ><?php _e('From', 'gdlr-hostel'); ?></span>
			<input class="gdlr-transaction-checkin datepicker" type="text" name="checkin-from" value="<?php echo esc_attr($checkin_from); ?>" />
			<span class="gdlr-transaction-sep" ><?php _e('To', 'gdlr-hostel'); ?></span>
			<input class="gdlr-transaction-checkin datepicker" type="text" name="checkin-to" value="<?php echo esc_attr($checkin_to); ?>" />
			<input type="submit" class="gdlr-with-margin" value="<?php _e('Filter!', 'gdlr-hostel'); ?>" />
		</div>
	</form>
	
	<?php $query_arg = $_GET; ?>
	<div class="gdlr-transaction-type">
		<a class="gdlr-button <?php echo ($status=='new')? 'gdlr-active':''; ?>" href="<?php $query_arg['type'] = 'new'; echo esc_url(add_query_arg($query_arg)) ?>" ><?php _e('Show new booking', 'gdlr-hotel'); ?></a>
		<a class="gdlr-button <?php echo ($status=='read')? 'gdlr-active':''; ?>" href="<?php $query_arg['type'] = 'read'; echo esc_url(add_query_arg($query_arg)) ?>" ><?php _e('Show read booking', 'gdlr-hotel'); ?></a>
	</div>

	<?php $query_arg = $_GET; ?>
	<div class="gdlr-transaction-type">
		<a class="gdlr-button <?php echo (empty($t_type) || $t_type=='all')? 'gdlr-active':''; ?>" href="<?php $query_arg['t-type'] = 'all'; echo esc_url(add_query_arg($query_arg)) ?>" ><?php _e('Transaction Type: All', 'gdlr-hostel'); ?></a>
		<a class="gdlr-button <?php echo ($t_type=='booking')? 'gdlr-active':''; ?>" href="<?php $query_arg['t-type'] = 'booking'; echo esc_url(add_query_arg($query_arg)) ?>" ><?php _e('Pending', 'gdlr-hostel'); ?></a>
		<a class="gdlr-button <?php echo ($t_type=='deposit')? 'gdlr-active':''; ?>" href="<?php $query_arg['t-type'] = 'deposit'; echo esc_url(add_query_arg($query_arg)) ?>" ><?php _e('Deposit Paid', 'gdlr-hostel'); ?></a>
		<a class="gdlr-button <?php echo ($t_type=='full-amount')? 'gdlr-active':''; ?>" href="<?php $query_arg['t-type'] = 'full-amount'; echo esc_url(add_query_arg($query_arg)) ?>" ><?php _e('Paid', 'gdlr-hostel'); ?></a>
		<a class="gdlr-button <?php echo ($t_type=='pending')? 'gdlr-active':''; ?>" href="<?php $query_arg['t-type'] = 'pending'; echo esc_url(add_query_arg($query_arg)) ?>" ><?php _e('Paypal Pending', 'gdlr-hostel'); ?></a>
	</div>

	<form class="gdlr-transaction-table" method="post" >
		<input type="hidden" name="transaction-type" value="" />

		<div class="transaction-bulk">
			<span class="transaction-bulk-title"><?php _e('Bulk Action :', 'gdlr-hotel'); ?></span>
			<?php
				if( $status == 'new' ){
					echo '<span class="transaction-bulk-read">' . __('Mark as read') . '</span>';
				}else{
					echo '<span class="transaction-bulk-unread">' . __('Mark as unread') . '</span>';
				}
			?>|
			<span class="transaction-bulk-cancel"><?php _e('Cancel Booking', 'gdlr-hotel'); ?></span>
			
		</div>

		<?php $query_arg = $_GET; ?>
		<div class="transaction-sort">
			<span class="transaction-sort-title"><?php _e('Order :', 'gdlr-hostel'); ?></span>
			<a class="<?php echo (empty($order) || $order=='desc')? 'gdlr-active':''; ?>" href="<?php $query_arg['order'] = 'desc'; echo esc_url(add_query_arg($query_arg)) ?>" ><?php _e('desc', 'gdlr-hostel'); ?></a>
			<a class="<?php echo (empty($order) || $order=='asc')? 'gdlr-active':''; ?>" href="<?php $query_arg['order'] = 'asc'; echo esc_url(add_query_arg($query_arg)) ?>" ><?php _e('asc', 'gdlr-hostel'); ?></a>
		</div>

		<?php $query_arg = $_GET; ?>
		<div class="transaction-sort">
			<span class="transaction-sort-title"><?php _e('Sort By :', 'gdlr-hostel'); ?></span>
			<a class="<?php echo (empty($orderby) || $orderby=='id')? 'gdlr-active':''; ?>" href="<?php $query_arg['orderby'] = 'id'; echo esc_url(add_query_arg($query_arg)) ?>" ><?php _e('ID', 'gdlr-hostel'); ?></a>
			<a class="<?php echo (empty($orderby) || $orderby=='booking_date')? 'gdlr-active':''; ?>" href="<?php $query_arg['orderby'] = 'booking_date'; echo esc_url(add_query_arg($query_arg)) ?>" ><?php _e('Booking Date', 'gdlr-hostel'); ?></a>
			<a class="<?php echo (empty($orderby) || $orderby=='checkin_date')? 'gdlr-active':''; ?>" href="<?php $query_arg['orderby'] = 'checkin_date'; echo esc_url(add_query_arg($query_arg)) ?>" ><?php _e('Check In Date', 'gdlr-hostel'); ?></a>
		</div>

		<table>
			<tr>
				<th><input id="bulk-select" type="checkbox" /><?php _e('id', 'gdlr-hotel'); ?></th>
				<th><?php _e('Name', 'gdlr-hotel'); ?></th>
				<th><?php _e('Booking Date', 'gdlr-hotel'); ?></th>
				<th><?php _e('Check In Date', 'gdlr-hotel'); ?></th>
				<th><?php _e('Contact', 'gdlr-hotel'); ?></th>
				<th><?php _e('Room', 'gdlr-hotel'); ?></th>
				<th><?php _e('Payment', 'gdlr-hotel'); ?></th>
				<th><?php _e('Status', 'gdlr-hotel'); ?></th>
				<th><?php _e('Action', 'gdlr-hotel'); ?></th>
			</tr>
			<?php
				global $wpdb;

				$temp_sql  = "SELECT * FROM {$wpdb->prefix}gdlr_hotel_payment ";
				if( $t_type == 'pending' ){
					$temp_sql .= "WHERE payment_status = 'pending' ";
				}else{
					$temp_sql .= "WHERE payment_status != 'pending' ";	
				}

				if( !empty($keyword) ){
					if( $search_by == 'contact' ){
						$temp_sql .= 'AND contact_info LIKE \'%' . $keyword . '%\' ';
					}else if( $search_by == 'room' ){
						$temp_sql .= 'AND booking_data LIKE \'%' . $keyword . '%\' ';
					}else if( $search_by == 'code' ){
						$temp_sql .= 'AND customer_code LIKE \'%' . $keyword . '%\' ';
					}
				}
				if( $status == 'read' ){
					$temp_sql .= 'AND read_status = \'read\' ';
				}else{
					$temp_sql .= 'AND (read_status = \'\' OR read_status IS NULL) ';
				}
				if( $t_type == 'booking' ){
					$temp_sql .= 'AND payment_status = \'booking\' ';
				}else if( $t_type == 'deposit' ){
					$temp_sql .= 'AND payment_status = \'paid\' ';
					$temp_sql .= 'AND total_price != pay_amount ';
				}else if( $t_type == 'full-amount' ){
					$temp_sql .= 'AND payment_status = \'paid\' ';
					$temp_sql .= 'AND total_price = pay_amount ';
				}

				// checkin
				if( !empty($checkin_from) ){
					$temp_sql .= 'AND checkin_date >= \'' . $checkin_from . '\' ';
				}
				if( !empty($checkin_to) ){
					$temp_sql .= 'AND checkin_date <= \'' . $checkin_to . '\' ';
				}
				$temp_sql .= "ORDER BY {$orderby} {$order}";

				$results = $wpdb->get_results($temp_sql);
				
				// filter result for hotel branches
				if( !empty($sel_branch) ){
					foreach( $results as $slug => $result ){
						$data = unserialize($result->booking_data);
						if( empty($data['gdlr-hotel-branches']) || $data['gdlr-hotel-branches'] != $sel_branch ){
							unset($results[$slug]);
						}
					}
				}
				$results = array_values($results);
				
				global $hotel_option;
				$record_num = count($results);
				$current_page = empty($_GET['paged'])? 1: intval($_GET['paged']);
				$record_per_page = empty($hotel_option['transaction-per-page'])? 30: intval($hotel_option['transaction-per-page']);
				$max_num_page = ceil($record_num/$record_per_page);				
				
				for($i=($record_per_page*($current_page - 1)); $i<$record_num && $i<($record_per_page * $current_page); $i++){ $result = $results[$i];
					$data = unserialize($result->booking_data);
					$contact = unserialize($result->contact_info);
					
					echo '<tr>';
					
					// col 1234
					echo '<td><input type="checkbox" name="tid[]" value="' . $result->id . '" >' . $result->id . '</td>';
					echo '<td>' . esc_html($contact['first_name']) . ' ' . esc_html($contact['last_name']) . '</td>';
					echo '<td>' . gdlr_hotel_date_format($result->booking_date, '', true) . '</td>';
					echo '<td>' . gdlr_hotel_date_format($data['gdlr-check-in']) . '</td>';
					
					// col 567
					echo '<td><a href="#" class="transaction-open-detail">' . __('Detail', 'gdlr-hotel') . '</a>' . get_transaction_contact($contact) . '</td>';
					echo '<td><a href="#" class="transaction-open-detail">' . __('Detail', 'gdlr-hotel') . '</a>' . get_transaction_room($data) . '</td>';
					echo '<td><a href="#" class="transaction-open-detail">' . __('Detail', 'gdlr-hotel') . '</a>' . get_transaction_payment($result, $contact) . '</td>';
					
					// col 8
					echo '<td>';
					if( $result->payment_status == 'booking' ){
						echo '<span class="transaction-type-booking" >';
						_e('Pending', 'gdlr-hotel');
						echo '</span>';
					}else if( $result->payment_status == 'paid' ){
						if( $result->total_price == $result->pay_amount ){
							echo '<span class="transaction-type-full-amount" >';
							if( $contact['payment-method'] == 'email' ){
								echo '<span class="transaction-paid-normal" >' . __('Paid', 'gdlr-hotel') . '</span>';
							}else{
								echo '<span class="transaction-paid-online" >' . __('Paid(Online)', 'gdlr-hotel') . '</span>';
							}
							echo '</span>';
						}else{
							echo '<span class="transaction-type-deposit" >' . __('Paid(Deposit)', 'gdlr-hotel') . '</span>';
						}
					}else if( $result->payment_status == 'pending' ){
						echo '<span class="transaction-type-paypal" >' . __('Paypal', 'gdlr-hotel') . '</span>';
					}else{
						echo $result->payment_status;
					}
					echo '</td>';
					
					// col 9
					echo '<td>';
					if($status == 'new'){
						echo '<a href="#" class="gdlr-mark-as-read" title="' . __('Mark as read', 'gdlr-hotel') . '"><i class="fa fa-eye" ></i></a>';
					}else{
						echo '<a href="#" class="gdlr-mark-as-unread" title="' . __('Mark as unread', 'gdlr-hotel') . '" ><i class="fa fa-eye-slash" ></i></a>';
					}
					if( $result->payment_status == 'booking' || $result->payment_status == 'pending' ){
						echo '<a href="#" class="gdlr-mark-as-paid" title="' . __('Mark as paid', 'gdlr-hotel') . '" ><i class="fa fa-check" ></i></a>';
					}else{
						echo '<a href="#" class="gdlr-mark-as-booking" title="' . __('Mark as pending', 'gdlr-hotel') . '" ><i class="fa fa-ellipsis-h" ></i></a>';
					}
					echo '<a href="#" class="gdlr-mark-as-remove" title="' . __('Remove', 'gdlr-hotel') . '" ><i class="fa fa-remove" ></i></a>';
					echo '</td>';
					echo '</tr>';
				}
			?>
		</table>
		<?php
			// print pagination
			if( $max_num_page > 1 ){
				$page_var = $_GET;

				echo '<div class="gdlr-transaction-pagination">';
				if($current_page > 1){
					$page_var['paged'] = intval($current_page) - 1;
					echo '<a class="prev page-numbers" href="' . esc_url(add_query_arg($page_var)) . '" >';
					echo __('&lsaquo; Previous', 'gdlr-lms') . '</a>';
				}
				for($i=1; $i<=$max_num_page; $i++){
					$page_var['paged'] = $i;
					if( $i == $current_page ){
						echo '<span class="page-numbers current" href="' . esc_url(add_query_arg($page_var)) . '" >' . $i . '</span>';
					}else{
						echo '<a class="page-numbers" href="' . esc_url(add_query_arg($page_var)) . '" >' . $i . '</a>';
					}
				}
				if($current_page < $max_num_page){
					$page_var['paged'] = intval($current_page) + 1;
					echo '<a class="next page-numbers" href="' . esc_url(add_query_arg($page_var)) . '" >';
					echo __('Next &rsaquo;', 'gdlr-lms') . '</a>';
				}
				echo '</div>';
			}		
		?>
	</form>
</div>
<?php	
		}
	}
	
	if( !function_exists('get_transaction_contact') ){
		function get_transaction_contact($contact){
			$ret  = '<div class="transaction-description-wrapper">';
			$ret .= '<i class="close-transaction-description fa fa-remove icon-remove"></i>';
			
			$ret .= '<div class="transaction-description">';
			$ret .= '<span class="gdlr-head">' . __('Name :', 'gdlr-hotel') . '</span>';
			$ret .= '<span class="gdlr-tail">' . esc_html($contact['first_name']) . '</span>';
			$ret .= '</div>';

			$ret .= '<div class="transaction-description">';
			$ret .= '<span class="gdlr-head">' . __('Last Name :', 'gdlr-hotel') . '</span>';
			$ret .= '<span class="gdlr-tail">' . esc_html($contact['last_name']) . '</span>';
			$ret .= '</div>';
			
			$ret .= '<div class="transaction-description">';
			$ret .= '<span class="gdlr-head">' . __('Email :', 'gdlr-hotel') . '</span>';
			$ret .= '<span class="gdlr-tail"><a href="mailto:' . esc_attr($contact['email']) . '" >' . $contact['email'] . '</a></span>';
			$ret .= '</div>';
			
			$ret .= '<div class="transaction-description">';
			$ret .= '<span class="gdlr-head">' . __('Phone :', 'gdlr-hotel') . '</span>';
			$ret .= '<span class="gdlr-tail">' . esc_html($contact['phone']) . '</span>';
			$ret .= '</div>';
			
			$ret .= '<div class="transaction-description">';
			$ret .= '<span class="gdlr-head">' . __('Address :', 'gdlr-hotel') . '</span>';
			$ret .= '<span class="gdlr-tail">' . esc_html($contact['address']) . '</span>';
			$ret .= '</div>';
			
			$ret .= '<div class="transaction-description">';
			$ret .= '<span class="gdlr-head">' . __('Additional Note :', 'gdlr-hotel') . '</span>';
			$ret .= '<span class="gdlr-tail">' . esc_html($contact['additional-note']) . '</span>';
			$ret .= '</div>';

			$ret .= '</div>';
			return $ret;
			
		}
	}
	
	if( !function_exists('get_transaction_payment') ){
		function get_transaction_payment($result, $contact){
			$payment_info = unserialize($result->payment_info);
			$transaction_id = '';
			if( !empty($contact['payment-method']) ){
				if( $contact['payment-method'] == 'stripe' ){
					$transaction_id = $payment_info['balance_transaction'];
				}else if( $contact['payment-method'] == 'paypal' ){
					$transaction_id = $payment_info['txn_id'];
				}else if( $contact['payment-method'] == 'paymill' ){
					$transaction_id = $payment_info->getId();
				}else if( $contact['payment-method'] == 'authorize' ){
					if( isset($payment_info['transaction_id']) ){
						$transaction_id = $payment_info['transaction_id'];
					}else{
						$transaction_id = $payment_info->transaction_id;
					}
				}
			}else{
				$contact['payment-method'] = '';
			}
			
			$ret  = '<div class="transaction-description-wrapper">';
			$ret .= '<i class="close-transaction-description fa fa-remove icon-remove"></i>';
			
			$ret .= '<div class="transaction-description">';
			$ret .= '<span class="gdlr-head">' . __('Payment Date :', 'gdlr-hotel') . '</span>';
			$ret .= '<span class="gdlr-tail">' . gdlr_hotel_date_format($result->payment_date, '', true) . '</span>';
			$ret .= '</div>';

			$ret .= '<div class="transaction-description">';
			$ret .= '<span class="gdlr-head">' . __('Payment Channel :', 'gdlr-hotel') . '</span>';
			$ret .= '<span class="gdlr-tail">' . $contact['payment-method'] . '</span>';
			$ret .= '</div>';
			
			if( !empty($transaction_id) ){
				$ret .= '<div class="transaction-description">';
				$ret .= '<span class="gdlr-head">' . __('Payment Transaction ID :', 'gdlr-hotel') . '</span>';
				$ret .= '<span class="gdlr-tail">' . $transaction_id . '</span>';
				$ret .= '</div>';
			}
			
			$ret .= '<div class="transaction-description gdlr-large">';
			$ret .= '<span class="gdlr-head">' . __('Pay Amount :', 'gdlr-hotel') . '</span>';
			$ret .= '<span class="gdlr-tail">' . gdlr_hotel_money_format($result->pay_amount) . '</span>';
			$ret .= '</div>';
			
			$ret .= '<div class="transaction-description gdlr-large">';
			$ret .= '<span class="gdlr-head">' . __('Pay on Arrival :', 'gdlr-hotel') . '</span>';
			$ret .= '<span class="gdlr-tail">' . gdlr_hotel_money_format($result->total_price - $result->pay_amount) . '</span>';
			$ret .= '</div>';
			
			if( !empty($contact['coupon']) ){
				$posts = get_posts(array('post_type'=>'coupon', 'posts_per_page'=>1, 'meta_key'=>'gdlr-coupon-code', 'meta_value'=>$contact['coupon']));
				$ret .= '<div class="transaction-description gdlr-large">';
				$ret .= '<span class="gdlr-head">' . __('Coupon Code :', 'gdlr-hotel') . '</span>';
				$ret .= '<span class="gdlr-tail"><a target="_blank" href="' . get_edit_post_link($posts[0]->ID) . '" >' . $contact['coupon'] . '</a></span>';
				$ret .= '</div>';
			}	

			if( !empty($result->customer_code) ){
				$ret .= '<div class="transaction-description gdlr-large">';
				$ret .= '<span class="gdlr-head">' . __('Confirmation Code :', 'gdlr-hotel') . '</span>';
				$ret .= '<span class="gdlr-tail">' . $result->customer_code . '</span>';
				$ret .= '</div>';
			}
			
			$ret .= '</div>';
			return $ret;
		}	
	}
	
	if( !function_exists('get_transaction_room') ){
		function get_transaction_room($data){

			$ret  = '<div class="transaction-description-wrapper">';
			$ret .= '<i class="close-transaction-description fa fa-remove icon-remove"></i>';
			
			// display branches if exists
			if( !empty($data['gdlr-hotel-branches']) ){
				$term = get_term_by('id', $data['gdlr-hotel-branches'], 'room_category');
				$ret .= '<div class="transaction-branches">';
				$ret .= $term->name;
				$ret .= '</div>';
			}
			
			$ret .= '<div class="transaction-description gdlr-large">';
			$ret .= '<span class="gdlr-head">' . __('Arrival Date :', 'gdlr-hotel') . '</span>';
			$ret .= '<span class="gdlr-tail">' . gdlr_hotel_date_format($data['gdlr-check-in']) . '</span>';
			$ret .= '</div>';

			if( !empty($data['gdlr-checkin-hour']) && !empty($data['gdlr-checkin-min']) ){
				$ret .= '<div class="transaction-description gdlr-large">';
				$ret .= '<span class="gdlr-head">' . __('Check In Time :', 'gdlr-hotel') . '</span>';
				$ret .= '<span class="gdlr-tail">' . $data['gdlr-checkin-hour']  . ':' . $data['gdlr-checkin-min'] . '</span>';
				$ret .= '</div>';			
			}

			$ret .= '<div class="transaction-description gdlr-large">';
			$ret .= '<span class="gdlr-head">' . __('Check Out Date :', 'gdlr-hotel') . '</span>';
			$ret .= '<span class="gdlr-tail">' . gdlr_hotel_date_format($data['gdlr-check-out']) . '</span>';
			$ret .= '</div>';
			
			for( $i=0; $i<intval($data['gdlr-room-number']); $i++){
				$ret .= '<div class="transaction-description gdlr-room">';
				$ret .= '<span class="gdlr-head">' . __('Room', 'gdlr-hotel') . ' ' . ($i + 1) . ' :</span>';
				$ret .= '<span class="gdlr-tail"><a target="_blank" href="' . get_edit_post_link($data['gdlr-room-id'][$i]) . '">' . get_the_title($data['gdlr-room-id'][$i]) . '</a></span>';
				$ret .= '<div class="gdlr-desc">';
				$ret .= '<span class="gdlr-desc-adult">' . __('Adult :', 'gdlr-hotel') . ' ' . $data['gdlr-adult-number'][$i] . '</span>';
				$ret .= '<span class="gdlr-desc-child">' . __('Children :', 'gdlr-hotel') . ' ' . $data['gdlr-children-number'][$i] . '</span>';
				$ret .= '</div>';
				$ret .= '</div>';
			}
			
			if( !empty($data['service']) ){
				$services_price = gdlr_calculate_service_price($data);
				$ret .= '<div class="transaction-description gdlr-room">';
				$ret .= '<span class="gdlr-head">' . __("Additional Services", "gdlr-hotel") . ' :</span>';
				foreach( $services_price as $key => $service_price ){
					if( $key == 'total' ) continue;
					$ret .= '<div class="gdlr-desc">';
					$ret .= $service_price['title'];
					$ret .= '</div>';
				}
			}
				
			$ret .= '</div>';
			return $ret;
		}		
	}