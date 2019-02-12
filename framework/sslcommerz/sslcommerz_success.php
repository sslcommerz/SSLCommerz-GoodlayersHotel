<?php get_header(); ?>
<div class="gdlr-content">
<?php
/*
**Template Name: SSLCommerz Success
*/ 
global $hotel_option, $wpdb;

$merchant_id = trim($hotel_option['sslcommerz-merchant-id']);
$merchant_password = trim($hotel_option['sslcommerz-merchant-password']);

$live_mode = empty($hotel_option['sslcommerz-live-mode'])? 'enable': $hotel_option['sslcommerz-live-mode']; 
if( empty($live_mode) || $live_mode == 'enable' )
{
	$environment = 'https://sandbox.sslcommerz.com/gwprocess/v3/api.php';
	$validation = 'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php';
}
else
{
	$environment = 'https://securepay.sslcommerz.com/gwprocess/v3/api.php';
	$validation = 'https://securepay.sslcommerz.com/validator/api/validationserverAPI.php';
}
//exit;
function _SSLCOMMERZ_hash_varify($store_passwd = "") 
{
    if (isset($_POST) && isset($_POST['verify_sign']) && isset($_POST['verify_key'])) 
    {
        # NEW ARRAY DECLARED TO TAKE VALUE OF ALL POST
        $pre_define_key = explode(',', $_POST['verify_key']);
        $new_data = array();
        if (!empty($pre_define_key)) 
        {
            foreach ($pre_define_key as $value) 
            {
                if (isset($_POST[$value])) 
                {
                    $new_data[$value] = ($_POST[$value]);
                }
            }
        }
        # ADD MD5 OF STORE PASSWORD
        $new_data['store_passwd'] = md5($store_passwd);

        # SORT THE KEY AS BEFORE
        ksort($new_data);
        $hash_string = "";
        foreach ($new_data as $key => $value) 
        {
            $hash_string .= $key . '=' . ($value) . '&';
        }

        $hash_string = rtrim($hash_string, '&');

        if (md5($hash_string) == $_POST['verify_sign']) 
        {
            return true;
        } 
        else 
        {
            return false;
        }
    } 
    else
    {
        return false;
    }
}


if (_SSLCOMMERZ_hash_varify($merchant_password) && !empty($_POST)) 
{
    $val_id = urlencode($_POST['val_id']);
    $store_id = urlencode($merchant_id);
    $store_passwd = urlencode($merchant_password);
    $requested_url = ($validation . "?val_id=" . $val_id . "&store_id=" . $store_id . "&store_passwd=" . $store_passwd . "&v=1&format=json");
    $handle = curl_init();
    curl_setopt($handle, CURLOPT_URL, $requested_url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($handle);

    $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

    if ($code == 200 && !( curl_errno($handle))) 
    {
        # TO CONVERT AS ARRAY
        # $result = json_decode($result, true);
        # $status = $result['status'];	
        # TO CONVERT AS OBJECT

        $result = json_decode($result);
        
        // echo "<pre>";
        // print_r($result);

        # TRANSACTION INFO

        $status = $result->status;
        $tran_date = $result->tran_date;
        $tran_id = $result->tran_id;
        $val_id = $result->val_id;
        $amount = $result->amount;
        $store_amount = $result->store_amount;
        $bank_tran_id = $result->bank_tran_id;
        $card_type = $result->card_type;

        # ISSUER INFO

        $card_no = $result->card_no;
        $card_issuer = $result->card_issuer;
        $card_brand = $result->card_brand;
        $card_issuer_country = $result->card_issuer_country;
        $card_issuer_country_code = $result->card_issuer_country_code;
        
        # API AUTHENTICATION

        $APIConnect = $result->APIConnect;
        $validated_on = $result->validated_on;
        $gw_version = $result->gw_version;
        $payment_info = array(
			'payment_method' => 'sslcommerz',
			'amount' => $amount,
			'transaction_id' => $teanid
		);
		$wpdb->update( $wpdb->prefix . 'gdlr_hotel_payment', 
			array('payment_status'=>'processing', 'payment_info'=>serialize($payment_info), 'payment_date'=>date('Y-m-d H:i:s')), 
			array('id'=>$tran_id), 
			array('%s', '%s', '%s'), 
			array('%d')
		);
		do_action('gdlr_update_transaction_availability', $tran_id);
		$temp_sql  = "SELECT * FROM " . $wpdb->prefix . "gdlr_hotel_payment ";
		$temp_sql .= "WHERE id = " . $tran_id;	
		$result = $wpdb->get_row($temp_sql);
		// print_r($result);

		// $result->pay_amount) 
		?>
		<table>
			<tr>
				<th colspan="5" align="center">We have successfully received your payment</th>
			</tr>
			<tr>
				<th>Transaction ID</th>
				<th>Amount</th>
				<th>Booking Date</th>
				<th>Checkin Date</th>
				<th>Customer Code</th>
			</tr>
			<tr>
				<td><?php echo $result->id; ?></td>
				<td><?php echo $amount; ?></td>
				<td><?php echo $result->booking_date; ?></td>
				<td><?php echo $result->checkin_date; ?></td>
				<td><?php echo $result->customer_code; ?></td>
			</tr>
		</table>
		<?php
    }
    else 
    {
        echo "Failed to connect with SSLCOMMERZ";
    }
} 
else 
{
    echo "Hash validation failed.";
    }
?>

</div><!-- gdlr-content -->
<?php get_footer(); ?>