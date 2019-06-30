<?php

/* Приводим все к одному часовому поясу */
date_default_timezone_set(DateTimeZone::listIdentifiers(DateTimeZone::UTC)[0]);

/* Получение парамера типа озапроса */
if(isset($_POST['mode'])) { $mode = $_POST['mode']; } elseif(isset($_GET['mode'])) { $mode = $_GET['mode']; } else { $mode = ''; }
$mode = htmlspecialchars(strip_tags(trim($mode)));

/* Функция передачи запроса на биржу */
function binance_query($path, $method, array $req = array()) {

	if(isset($_POST['api_key'])) { $api_key = $_POST['api_key']; } elseif(isset($_GET['api_key'])) { $api_key = $_GET['api_key']; } else { $api_key = 0; }
	if(isset($_POST['api_secret'])) { $api_secret = $_POST['api_secret']; } elseif(isset($_GET['api_secret'])) { $api_secret = $_GET['api_secret']; } else { $api_secret = 0; }
	if(isset($_POST['arg'])) { $arg = $_POST['arg']; } elseif(isset($_GET['arg'])) { $arg = $_GET['arg']; } else { $arg = 0; }
	
  $api_key = htmlspecialchars(strip_tags(trim($api_key)));
	$api_secret = htmlspecialchars(strip_tags(trim($api_secret)));
	$arg = htmlspecialchars(strip_tags(trim($arg)));
	
  $req['recvWindow'] = 5000;
	
  /* Корректировка времени сервера и биржи */
	$correctTime = time()*1000 - $arg;
	
  $req['timestamp'] = $correctTime;
	$post_data = http_build_query($req, '', '&');
  $sign = hash_hmac("sha256", $post_data, $api_secret);
  $req['signature'] = $sign;
	$post_data = http_build_query($req, '', '&');
	
  $ch = null;
	if (is_null($ch)) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; binance PHP client; '.php_uname('s').'; PHP/'.phpversion().')');
	}
	if ($method == 'GET') {
		$headers = array(
			'X-MBX-APIKEY: '.$api_key,
		);
		$url = 'https://api.binance.com'.$path."?".$post_data;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPGET, 1);
	}
	if ($method == 'POST') {
		$headers = array(
			'X-MBX-APIKEY: '.$api_key,
			'Content-Type: application/x-www-form-urlencoded',
		);
		$url = 'https://api.binance.com'.$path;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	}
	if ($method == 'DELETE') {
		$headers = array(
			'X-MBX-APIKEY: '.$api_key,
			'Content-Type: application/x-www-form-urlencoded',
		);
		$url = 'https://api.binance.com'.$path;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
	}
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

	$res = curl_exec($ch);

	if ($res === false) {
		print "{\"status\":0}";
		exit;
	}

	$dec = json_decode($res, true);
	if (!$dec) {
		print "{\"status\":0}";
		exit;
	}
	return $dec;
}

/* Получение балансов пользователя */
if ($mode == 'getBalances'){
	$result = binance_query("/api/v3/account","GET");
	if ($result['updateTime'] != null) {
		$balances = "{";
		$balances .= "\"status\":1,";
		$balances .= "\"data\":{";
		$count = count($result['balances']);
		for ($i = 0; $i < $count; $i++) {
			$balances .= "\"".strtoupper($result['balances'][$i]['asset'])."\":".$result['balances'][$i]['free'].",";
		}
		$balances = substr($balances, 0, -1) . "}}";
	} else {
		$balances = "{\"status\":0}";
	}
	echo $balances;
	exit;
}

/* Получение ордеров */
if ($mode == 'getOrders') {
	$result = binance_query("/api/v3/openOrders","GET", array());
	$count = count($result);
		if ($count) {
			$return = "{";
			$return .= "\"status\":1,";
			$return .= "\"data\":[";
				for ($i = 0; $i < $count; $i++) {
					$return .= "{\"id\":".$result[$i]['orderId'].",";
					$return .= "\"symbol\":\"".$result[$i]['symbol']."\",";
					$return .= "\"side\":\"".strtolower($result[$i]['side'])."\",";
					$return .= "\"qty\":".$result[$i]['origQty'].",";
					$return .= "\"price\":".$result[$i]['price'].",";
					$return .= "\"time\":".round($result[$i]['time']/1000,0)."},";
				}
			$return = substr($return, 0, -1) . "]}";
		} else {
			$return = "{\"status\":0}";
		}
	echo $return;
	exit;
}

/* Отмена ордера */
if ($mode == 'cancelOrder') {
	if(isset($_POST['order_id'])) { $order_id = $_POST['order_id']; } elseif(isset($_GET['order_id'])) { $order_id = $_GET['order_id']; } else { $order_id = 0; }
	$order_id = htmlspecialchars(strip_tags(trim($order_id)));
	
  if(isset($_POST['symbol'])) { $symbol = $_POST['symbol']; } elseif(isset($_GET['symbol'])) { $symbol = $_GET['symbol']; } else { $symbol = 0; }
	$symbol = htmlspecialchars(strip_tags(trim($symbol)));
	
  $result = binance_query("/api/v3/order","DELETE", array("symbol" => "$symbol", "orderId" => "$order_id", ));
	if ($result['orderId'] != null) {
		$return = "{\"status\":1}";
	} else {
		$return = "{\"status\":0}";
	}
	echo $return;
	exit;
}

/* Отправка ордера */
if ($mode == 'sendOrder') {
	if(isset($_POST['symbol'])) { $symbol = $_POST['symbol']; } elseif(isset($_GET['symbol'])) { $symbol = $_GET['symbol']; } else { $symbol = 0; }
	$symbol = htmlspecialchars(strip_tags(trim($symbol)));

	if(isset($_POST['side'])) { $side = $_POST['side']; } elseif(isset($_GET['side'])) { $side = $_GET['side']; } else { $side = ""; }
	$side = htmlspecialchars(strip_tags(trim($side)));

	if(isset($_POST['qty'])) { $qty = $_POST['qty']; } elseif(isset($_GET['qty'])) { $qty = $_GET['qty']; } else { $qty = 0; }
	$qty = htmlspecialchars(strip_tags(trim($qty)));

	if(isset($_POST['price'])) { $price = $_POST['price']; } elseif(isset($_GET['price'])) { $price = $_GET['price']; } else { $price = 0; }
	$price = htmlspecialchars(strip_tags(trim($price)));

	$result = binance_query("/api/v3/order","POST", array("symbol" => "$symbol", "type" => "LIMIT", "side" => "$side", "timeInForce" => "GTC", "quantity" => $qty, "price" => $price));
	if ($result['orderId'] != null) {
		$time = time();
		$order = "{";
		$order .= "\"status\":1,";
		$order .= "\"order_id\":".$result['orderId']."";
		$order .= "}";
	} else {
		$order = "{\"status\":0}";
	}
	echo $order;
	exit;
}

/* Получение цен торговых пар */
if ($mode == 'getPrices') {
	$link = "https://api.binance.com/api/v3/ticker/price";
	$fcontents = implode ('', file ($link));
	$fcontents = json_decode($fcontents, true);
	$count = count($fcontents);
	if ($count > 0) {
		$price = "{";
		$price .= "\"status\":1,";
		$price .= "\"data\":{";
		for ($i = 0; $i < $count; $i++) {
			$price .= "\"".$fcontents[$i]['symbol']."\":".$fcontents[$i]['price'].",";
		}
		$price = substr($price, 0, -1) . "}}";
	} else {
		$price = "{\"status\":0}";
	}
	echo $price;
	exit;
}

/* Получение правил для торговых пар */
if ($mode == 'getRules') {
	$link = "https://api.binance.com/api/v1/exchangeInfo";
	$fcontents = implode ('', file ($link));
	$fcontents = json_decode($fcontents, true);

	$rules = "{";
	$rules .= "\"status\":1,";

	$count = count($fcontents['symbols']);

	$rules .= "\"data\":[";

	for ($i = 0; $i < $count; $i++) {
		if ($fcontents['symbols'][$i]['baseAsset'] != 123) {
			$rules .= "{";
			$rules .= "\"symbol\":\"".$fcontents['symbols'][$i]['symbol']."\",";
			$rules .= "\"base\":\"".$fcontents['symbols'][$i]['baseAsset']."\",";
			$rules .= "\"quote\":\"".$fcontents['symbols'][$i]['quoteAsset']."\",";
			$rules .= "\"min_price\":".$fcontents['symbols'][$i]['filters'][0]['minPrice'].",";
			$rules .= "\"max_price\":".$fcontents['symbols'][$i]['filters'][0]['maxPrice'].",";
			$rules .= "\"step_price\":".$fcontents['symbols'][$i]['filters'][0]['tickSize'].",";
			$rules .= "\"min_qty\":".$fcontents['symbols'][$i]['filters'][2]['minQty'].",";
			$rules .= "\"max_qty\":".$fcontents['symbols'][$i]['filters'][2]['maxQty'].",";
			$rules .= "\"step_qty\":".$fcontents['symbols'][$i]['filters'][2]['stepSize']."";
			$rules .= "},";
		}
	}
	$rules = substr($rules, 0, -1) . "]}";
	echo $rules;
	exit;
}

/* Корректировка времени сервера и биржи */
if ($mode == 'getTime') {
	$link = "https://api.binance.com/api/v1/time";
	$fcontents = implode ('', file ($link));
	$fcontents = json_decode($fcontents, true);
	$serverTime = $fcontents["serverTime"];
	$deltaTime = time()*1000 - $serverTime;
	$delta = "{";
	$delta .= "\"server_time\": $serverTime,";
	$delta .= "\"delta_time\": $deltaTime";
	$delta .= "}";
	print $delta;
	exit;
}

print "{\"status\":0}";
exit;

?>
