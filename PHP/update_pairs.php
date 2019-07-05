<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

/* Подключаем файлы работы с базой данных */
require_once('mysql7.php');
require_once('dbconfig.php');

/* Обновление правил торговли */
$result = $db->query("DELETE FROM `pairs` WHERE 1");

$url = "http://localhost/bot/api_binance.php?mode=getRules";
$fcontents = implode ('', file ($url));
$fcontents = json_decode($fcontents, true);
$rules = $fcontents['data'];
$query = "INSERT INTO `pairs`(`symbol`, `base`, `quote`, `min_price`, `max_price`, `step_price`, `min_qty`, `max_qty`, `step_qty`) VALUES ";

for ($i=0;$i<count($rules);$i++) {
	$symbol = $rules[$i]['symbol'];
	$base = $rules[$i]['base'];
	$quote = $rules[$i]['quote'];
	$min_price = $rules[$i]['min_price'];
	$max_price = $rules[$i]['max_price'];
	$step_price = $rules[$i]['step_price'];
	$min_qty = $rules[$i]['min_qty'];
	$max_qty = $rules[$i]['max_qty'];
	$step_qty = $rules[$i]['step_qty'];
	$query .= "('$symbol','$base','$quote',$min_price,$max_price,$step_price,$min_qty,$max_qty,$step_qty),";
}
$query = substr($query,0,-1);
$db->query($query);

?>
