<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

/* Подключаем файлы работы с базой данных */
require_once('mysql7.php');
require_once('dbconfig.php');

/* Получение необходимых параметров */
/* Номер настроек */
if(isset($_POST['set_id'])) { $set_id = $_POST['set_id']; } elseif(isset($_GET['set_id'])) { $set_id = $_GET['set_id']; } else { $set_id = 0; }
$set_id = htmlspecialchars(strip_tags(trim($set_id)));

/* API ключ */
if(isset($_POST['api_key'])) { $api_key = $_POST['api_key']; } elseif(isset($_GET['api_key'])) { $api_key = $_GET['api_key']; } else { $api_key = ""; }
$api_key = htmlspecialchars(strip_tags(trim($api_key)));

/* API секретный ключ */
if(isset($_POST['api_secret'])) { $api_secret = $_POST['api_secret']; } elseif(isset($_GET['api_secret'])) { $api_secret = $_GET['api_secret']; } else { $api_secret = ""; }
$api_secret = htmlspecialchars(strip_tags(trim($api_secret)));

/* ссылка открытия позиции */
if(isset($_POST['open_url'])) { $open_url = $_POST['open_url']; } elseif(isset($_GET['open_url'])) { $open_url = $_GET['open_url']; } else { $open_url = 0; }
$open_url = htmlspecialchars(strip_tags(trim($open_url)));

/* Дельта */
if(isset($_POST['delta'])) { $delta = $_POST['delta']; } elseif(isset($_GET['delta'])) { $delta = $_GET['delta']; } else { $delta = 0; }
$delta = htmlspecialchars(strip_tags(trim($delta)));

/* Комиссия биржи */
if(isset($_POST['fee'])) { $fee = $_POST['fee']; } elseif(isset($_GET['fee'])) { $fee = $_GET['fee']; } else { $fee = 0; }
$fee = htmlspecialchars(strip_tags(trim($fee)));

/* Сумма */
if(isset($_POST['amount'])) { $amount = $_POST['amount']; } elseif(isset($_GET['amount'])) { $amount = $_GET['amount']; } else { $amount = 0; }
$amount = htmlspecialchars(strip_tags(trim($amount)));

/* Пауза */
if(isset($_POST['pause'])) { $pause = $_POST['pause']; } elseif(isset($_GET['pause'])) { $pause = $_GET['pause']; } else { $pause = 0; }
$pause = htmlspecialchars(strip_tags(trim($pause)));

/* Ссылка закрытия позиции */
if(isset($_POST['close_url'])) { $close_url = $_POST['close_url']; } elseif(isset($_GET['close_url'])) { $close_url = $_GET['close_url']; } else { $close_url = ""; }
$close_url = htmlspecialchars(strip_tags(trim($close_url)));

/* Время закрытия */
if(isset($_POST['close_time'])) { $close_time = $_POST['close_time']; } elseif(isset($_GET['close_time'])) { $close_time = $_GET['close_time']; } else { $close_time = 0; }
$close_time = htmlspecialchars(strip_tags(trim($close_time)));

/* Потеря профита */
if(isset($_POST['close_lose'])) { $close_lose = $_POST['close_lose']; } elseif(isset($_GET['close_lose'])) { $close_lose = $_GET['close_lose']; } else { $close_lose = 0; }
$close_lose = htmlspecialchars(strip_tags(trim($close_lose)));

/* Снижение цены */
if(isset($_POST['close_exit'])) { $close_exit = $_POST['close_exit']; } elseif(isset($_GET['close_exit'])) { $close_exit = $_GET['close_exit']; } else { $close_exit = 0; }
$close_exit = htmlspecialchars(strip_tags(trim($close_exit)));

if(isset($_POST['del'])) { $del = $_POST['del']; } elseif(isset($_GET['del'])) { $del = $_GET['del']; } else { $del = 0; }
$del = htmlspecialchars(strip_tags(trim($del)));

if(isset($_POST['send'])) { $send = $_POST['send']; } elseif(isset($_GET['send'])) { $send = $_GET['send']; } else { $send = 0; }
$send = htmlspecialchars(strip_tags(trim($send)));
$error = "";

if ($send) {
	if ($set_id) {
		$result = $db->query("SELECT * FROM `settings` WHERE `id` = '$set_id'");
		if ($db->num_rows($result)) {
			if (!$api_key) {$error .= "Вы не отправили API-ключ от биржи<br>";}
			if (!$api_secret) {$error .= "Вы не отправили API-ключ от биржи<br>";}
			if (!$open_url) {$error .= "Вы не отправили ссылку открытия позиции<br>";}
			if (!$amount) {$error .= "Вы не отправили размер открываемой позиции<br>";}
			if (!$close_exit) {$error .= "Вы не отправили значение экстренного выхода<br>";}
			if (!$error) {
				$db->query("UPDATE `settings` SET `api_key`='$api_key',`api_secret`='$api_secret',`open_url`='$open_url',`amount`='$amount',`fee`='$fee',`delta`='$delta',`pause`='$pause',`close_url`='$close_url',`close_time`='$close_time',`close_lose`='$close_lose',`close_exit`='$close_exit' WHERE `id` = '$set_id'");
			}
		} else {
			if (!$api_key) {$error .= "Вы не отправили API-ключ от биржи<br>";}
			if (!$api_secret) {$error .= "Вы не отправили API-ключ от биржи<br>";}
			if (!$open_url) {$error .= "Вы не отправили ссылку открытия позиции<br>";}
			if (!$amount) {$error .= "Вы не отправили размер открываемой позиции<br>";}
			if (!$close_exit) {$error .= "Вы не отправили значение экстренного выхода<br>";}
			if (!$error) {
				$db->query("INSERT INTO `settings`(`id`, `api_key`, `api_secret`, `open_url`, `amount`, `fee`, `delta`, `pause`, `close_url`, `close_time`, `close_lose`, `close_exit`) VALUES ('$set_id','$api_key', '$api_secret', '$open_url', '$amount', '$fee', '$delta', '$pause', '$close_url', '$close_time', '$close_lose', '$close_exit')");
			}
		}
	} else {
		$error = "Вы не отправили номер сета<br>";
	}
}
if ($del) {
	if ($set_id) {
		$db->query("DELETE FROM `settings` WHERE `id` = $set_id");
	} else {
		$error = "Вы не отправили номер сета<br>";
	}
}

print "
<html>
<head>
<title>Funny Bot Config Page</title>
</head>
<body>
<center>
<h1>Конфигурация Funny Bot</h1>
$error
<form action='config.php' method='post'>
<input type='hidden' name='send' value='1'>

<table width='50%'>
<tr><th>Параметр</th><th>Значение</th><th>Описание</th></tr>
<tr><td>Номер набора</td><td><input type='number' step='1' name='set_id' placeholder='1' value=''></td><td>Под данным номером сохраняются настройки в хранилище</td></tr>
<tr><td>API-ключ</td><td><input type='text' name='api_key' placeholder='API key'></td><td>Ключ, который выдают на бирже API-KEY</td></tr>
<tr><td>API-секретный ключ</td><td><input type='text' name='api_secret' placeholder='API key'></td><td>Ключ, который выдают на бирже API-SECRET</td></tr>
<tr><td colspan='3'><hr></td></tr>
<tr><td>Ссылка открытия</td><td><input type='text' name='open_url' placeholder='http://'></td><td>Ссылка, по которой получаем сигналы на покупку</td></tr>
<tr><td>Дельта</td><td><input type='number' step='any' name='delta' placeholder='0.5' value='0.5'></td><td>Корректировка цены в % в худшую сторону при отправке ордера. Необходима чтобы не упустить позицию при волатильном рынке.</td></tr>
<tr><td>Размер позиции</td><td><input type='number' step='any' name='amount' placeholder='20' value='20'></td><td>Размер позиции эквивалентная USDT</td></tr>
<tr><td>Комиссия биржи</td><td><input type='number' step='any' name='fee' placeholder='0.2' value='0.2'></td><td>Комиссия в %, которую берет биржа за совершение операции</td></tr>
<tr><td>Пауза</td><td><input type='number' step='1' name='pause' placeholder='86400' value=''></td><td>Количество секунд, которое будет заблокирована позиция до начала проверок выходов</td></tr>
<tr><td colspan='3'><hr></td></tr>
<tr><td>Ссылка закрытия</td><td><input type='text' name='close_url' placeholder='http://'></td><td>Ссылка, по которой получаем сигналы на продажу</td></tr>
<tr><td>Время закрытия</td><td><input type='number' step='1' name='close_time' placeholder='604800' value='0'></td><td>Количество секунд через которое позиция закрывается</td></tr>
<tr><td>Потеря профита</td><td><input type='number' step='any' name='close_lose' placeholder='20.0' value='0'></td><td>Значение в %, которое мы готовы потерять от максимального профита, после чего закрываем позицию</td></tr>
<tr><td>Экстренный выход<br>по снижению цены</td><td><input type='number' step='any' name='close_exit' placeholder='2.0' value='2.0'></td><td>Значение в % на которое просядет цена и позиция зактроется.</td></tr>
<tr><td colspan='3'><hr></td></tr>
<tr><td colspan='3'><input type='submit' value='Отправить'></td></tr>
</table>
</form>

<br><br>
";

$result = $db->query("SELECT * FROM `settings` WHERE 1");
print "<table>
<tr>
<th>Номер</th>
<th>API-ключ</th>
<th>API-секрет</th>
<th>Ссылка открытия</th>
<th>Дельта</th>
<th>Комиссия</th>
<th>Пауза</th>
<th>Ссылка закрытия</th>
<th>Время закрытия</th>
<th>Потери профита</th>
<th>Экстремальные потери</th>
<th></th>
</tr>
";
while ($row = $db->get_object($result)) {
print "
<tr>
<td>".$row->id."</td>
<td>".substr($row->api_key,0,16)."...</td>
<td>".substr($row->api_secret,0,16)."...</td>
<td>".$row->open_url."</td>
<td>".$row->delta."</td>
<td>".$row->fee."</td>
<td>".$row->pause."</td>
<td>".$row->close_url."</td>
<td>".$row->close_time."</td>
<td>".$row->close_lose."</td>
<td>".$row->close_exit."</td>
<td><a href='config.php?set_id=".$row->id."&del=1'>Удалить</a></td>
</tr>
";
}

print "</table>
</center></body>
</html>";

?>
