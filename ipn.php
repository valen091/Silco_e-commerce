<?php
// PayPal IPN (Instant Payment Notification) Handler

// Configuration
$paypal_sandbox = true; // Set to false for production
$paypal_email = 'tucorreo@tudominio.com'; // Replace with your PayPal email

// Read the POST data from PayPal
$raw_post_data = file_get_contents('php://input');
$raw_post_array = explode('&', $raw_post_data);
$myPost = array();

foreach ($raw_post_array as $keyval) {
    $keyval = explode('=', $keyval);
    if (count($keyval) == 2) {
        $myPost[$keyval[0]] = urldecode($keyval[1]);
    }
}

// Build the verification request
$req = 'cmd=_notify-validate';
$get_magic_quotes_exists = function_exists('get_magic_quotes_gpc') ? true : false;

foreach ($myPost as $key => $value) {
    if ($get_magic_quotes_exists && get_magic_quotes_gpc() == 1) {
        $value = urlencode(stripslashes($value));
    } else {
        $value = urlencode($value);
    }
    $req .= "&$key=$value";
}

// Post back to PayPal to validate
$paypal_url = $paypal_sandbox ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';
$ch = curl_init($paypal_url);

if ($ch == false) {
    // Log error: Unable to initialize cURL
    file_put_contents('ipn_errors.log', date('[Y-m-d H:i:s] ') . "ERROR: Unable to initialize cURL\n", FILE_APPEND);
    exit(0);
}

curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));

// Execute the cURL request
$res = curl_exec($ch);

// Check for cURL errors
if (curl_errno($ch) != 0) {
    $error_msg = curl_error($ch);
    file_put_contents('ipn_errors.log', date('[Y-m-d H:i:s] ') . "cURL error: $error_msg\n", FILE_APPEND);
    curl_close($ch);
    exit(0);
}

curl_close($ch);

// Inspect IPN validation result and act accordingly
if (strcmp($res, "VERIFIED") == 0) {
    // The IPN is verified, process it
    $receiver_email = $_POST['receiver_email'];
    $txn_id = $_POST['txn_id'];
    $payment_status = $_POST['payment_status'];
    $payment_amount = $_POST['mc_gross'];
    $payment_currency = $_POST['mc_currency'];
    $payer_email = $_POST['payer_email'];
    $custom = isset($_POST['custom']) ? $_POST['custom'] : '';
    
    // Check that the payment status is Completed
    if ($payment_status == 'Completed') {
        // Check that receiver_email is your Primary PayPal email
        if (strtolower($receiver_email) == strtolower($paypal_email)) {
            // Check that payment amount and currency are correct
            // You should have these values stored in your database
            
            // Process the payment
            // TODO: Add your order processing logic here
            // Example: Update order status in database
            // $order_id = (int)$_POST['custom'];
            // markOrderAsPaid($order_id, $txn_id);
            
            // Log successful payment
            $log_message = "Verified IPN: $txn_id | Status: $payment_status | Amount: $payment_amount $payment_currency | Payer: $payer_email\n";
            file_put_contents('ipn_success.log', date('[Y-m-d H:i:s] ') . $log_message, FILE_APPEND);
        } else {
            // Log error: Invalid receiver email
            file_put_contents('ipn_errors.log', date('[Y-m-d H:i:s] ') . "ERROR: Invalid receiver email ($receiver_email)\n", FILE_APPEND);
        }
    } else if ($payment_status == 'Pending') {
        // Payment is pending (e.g., eCheck)
        $pending_reason = $_POST['pending_reason'];
        $log_message = "Pending Payment: $txn_id | Reason: $pending_reason | Amount: $payment_amount $payment_currency\n";
        file_put_contents('ipn_pending.log', date('[Y-m-d H:i:s] ') . $log_message, FILE_APPEND);
    }
} else if (strcmp($res, "INVALID") == 0) {
    // Log invalid IPN
    file_put_contents('ipn_errors.log', date('[Y-m-d H:i:s] ') . "INVALID IPN: " . print_r($_POST, true) . "\n", FILE_APPEND);
}

// Reply with an empty 200 response to indicate to paypal the IPN was received correctly
header("HTTP/1.1 200 OK");
?>
