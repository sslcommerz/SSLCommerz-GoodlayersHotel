<?php get_header(); ?>
<div class="gdlr-content">
<?php
/*
**Template Name: SSLCommerz Cancel
*/ 
if (!empty($_POST)) 
{
	// print_r($_POST);exit;
	 $payment_info = array(
			'payment_method' => 'sslcommerz',
			'amount' => $_POST['amount'],
			'transaction_id' => $_POST['tran_id']
		);
		$wpdb->update( $wpdb->prefix . 'gdlr_hotel_payment', 
			array('payment_status'=>'cancel', 'payment_info'=>serialize($payment_info), 'payment_date'=>date('Y-m-d H:i:s')), 
			array('id'=> $_POST['tran_id']), 
			array('%s', '%s', '%s'), 
			array('%d')
		);
		do_action('gdlr_update_transaction_availability', $_POST['tran_id']);
		$temp_sql  = "SELECT * FROM " . $wpdb->prefix . "gdlr_hotel_payment ";
		$temp_sql .= "WHERE id = " . $_POST['tran_id'];	
		$result = $wpdb->get_row($temp_sql);
		// print_r($result);

		// $result->pay_amount) 
		?>
		<table>
			<tr>
				<th colspan="4" align="center">Payment Canceled</th>
			</tr>
			<tr>
				<th>Transaction ID</th>
				<th>Amount</th>
				<th>Customer Code</th>
				<th>Status</th>
			</tr>
			<tr>
				<td><?php echo $result->id; ?></td>
				<td><?php echo $_POST['amount']; ?></td>
				<td><?php echo $result->customer_code; ?></td>
				<td><?php echo 'Canceled'; ?></td>
			</tr>
		</table>
		<?php
}
?>
</div><!-- gdlr-content -->
<?php get_footer(); ?>