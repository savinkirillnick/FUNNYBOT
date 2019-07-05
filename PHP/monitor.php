<?php

/* Подключаем файлы работы с базой данных */
require_once('mysql7.php');
require_once('dbconfig.php');

/* Настройки торговой программы */
/* Биржа, с которой будет работать программа через API */
$exchange = "binance";
/* Папка в которой лежит бот */
$dir = "http://localhost/bot/";

/* Надстройки торговой программы. Менять запрещено. */
$ex_api = $dir . "api_" . $exchange . ".php";

print "
<html>
<head>
<title>
Monitor
</title>
</head>
<body>
<center>
<h1>Мониторинг Funny Bot</h1>
<table>
<tr>
<th>№</th>
<th>Пара</th>
<th>Цена<br>открытия</th>
<th>Количество</th>
<th>Сумма</th>
<th>Макс.<br>цена</th>
<th>Посл.<br>цена</th>
<th>Изм.</th>
<th>Макс.<br>профит</th>
<th>Тек.<br>профит</th>
<th>Настройки</th>
</tr>
";

/* Получаем цены биржи по ссылке и расшифровываем JSON */
$url = $ex_api . "?mode=getPrices";
$fcontents = implode ('', file ($url));
$fcontents = json_decode($fcontents, true);
$prices = $fcontents['data'];

/* Получаем позиции из хранилища */
$result = $db->query("SELECT * FROM `positions` WHERE 1");

while ($row = $db->get_object($result)){
	print "
	<tr>
	<td>".$row->id."</td>
	<td>".$row->symbol."</td>
	<td>".number_format($row->price,8,'.','')."</td>
	<td>".number_format($row->qty,8,'.','')."</td>
	<td>".number_format(($row->price*$row->qty),8,'.','')."</td>
	<td>".number_format($row->max_price,8,'.','')."</td>
	<td>".number_format($prices[$row->symbol],8,'.','')."</td>
	<td>".round((100*($prices[$row->symbol]-$row->price)/$row->price),2)."</td>
	<td>".number_format((($row->max_price-$row->price)*$row->qty),8,'.','')."</td>
	<td>".number_format((($prices[$row->symbol]-$row->price)*$row->qty),8,'.','')."</td>
	<td>".$row->set_id."</td>
	</tr>";
}

print "
</table>
</center>
</body>
</html>
";

?>
