<?php
$starting_after = $_GET['starting_after'];

if(!isset($_GET['apikey'])){
	echo "you must add an access apikey in the URL (eg ?apikey=abc123)";
	exit;
} else {
	$apikey = base64_encode($_GET['apikey']);
}

function set_default($cust_id, $pm_id)
{	
	global $apikey;
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => "https://api.stripe.com/v1/customers/" . $cust_id,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_POSTFIELDS => "invoice_settings%5Bdefault_payment_method%5D=" . $pm_id,
		CURLOPT_HTTPHEADER => array(
			"Authorization: Basic ".$apikey,
			"Content-Type: application/x-www-form-urlencoded"
		),
	));

	$response = json_decode(curl_exec($curl), true);
	curl_close($curl);
}

function get_payment_method($cust_id)
{
	global $apikey;
	$curl = curl_init();

	curl_setopt_array($curl, array(
		CURLOPT_URL => "https://api.stripe.com/v1/payment_methods?customer=" . $cust_id . "&type=card",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_POSTFIELDS => "customer=" . $cust_id . "&type=card",
		CURLOPT_HTTPHEADER => array(
			"Authorization: Basic ".$apikey,
			"Content-Type: application/x-www-form-urlencoded"
		),
	));

	$response = json_decode(curl_exec($curl), true);
	return $response;
}

function get_customers()
{
	global $apikey;
	$batch_size = 25;
	$customers = [];
	global $starting_after;
	$url = "https://api.stripe.com/v1/customers?limit=" . $batch_size;
	if ($starting_after) {
		$url .= "&starting_after=" . $starting_after;
	}

	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_HTTPHEADER => array(
			"Authorization: Basic ".$apikey
		),
	));

	$response = json_decode(curl_exec($curl), true);
	curl_close($curl);
	$customers = array_merge($customers, $response['data']);

	foreach ($customers as $k => $cust) {
		if (!$cust['sources']['total_count']) {
			$payment_methods = get_payment_method($cust['id']);
			if ($payment_methods['data']) {
				$card = $payment_methods['data'][0];
				set_default($cust['id'], $card['id']);
			}
		}
	}

	if ($response['has_more']) {
		$starting_after = $customers[count($customers) - 1]['id'];
		header("location:/portfolio/truthbrushstock/updatecards.php?starting_after=" . $starting_after. "&apikey=".$_GET['apikey']);
	}
}

get_customers();
