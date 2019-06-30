<?php

if(isset($_GET['debug'])) { $debug = 1; } else { $debug = 0; }

/* Получение номера набора настроек */
if(isset($_GET['set_id'])) { $set_id = $_GET['set_id']; } else { $set_id = 0; }
$set_id = htmlspecialchars(strip_tags(trim($set_id)));

/* Если параметр равен 0, завершение программы */
if (!$set_id) {
	echo 'Необходим параметр set_id';
	exit();
}

/* Теперь все запросы в хранилище производим с параметром set_id */
/* Программа будет работать с определенным набором настроек, */
/* и мы сможем контролировать - как они работают */

if ($debug) {
	ini_set('error_reporting', E_ALL);
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
} else {
	ini_set('error_reporting', 0);
}

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

/* Получение из хранилища настроек пользователя */
$result = $db->query("SELECT * FROM `settings` WHERE `id` = '$set_id'");
$row = $db->get_object($result);
$api_key = $row->api_key;
$api_secret = $row->api_secret;
$open_url = $row->open_url;
$amount = $row->amount;
$pause = $row->pause;
$delta = $row->delta;
$fee = $row->fee;
$close_url = $row->close_url;
$close_time = $row->close_time;
$close_lose = $row->close_lose;
$close_exit = $row->close_exit;
$current_time = time();

/* Получение параметра временной переменной для биржи */
$url = $ex_api . "?mode=getTime";
$fcontents = implode ('', file ($url));
$fcontents = json_decode($fcontents, true);
$arg = $fcontents['delta_time'];
if ($debug) {echo 'prepare: --Временная переменная = '.$arg.'<br>';}

/* Перед началом обработки получаем все сигналы, ордера, и цены из хранилища и биржи */
/* Получаем сигналы открытия */
if ($open_url) {
	if ($debug) {echo 'prepare: --Получаем сигналы открытия<br>';}
	$fcontents = implode ('', file ($open_url));
	$fcontents = json_decode($fcontents, true);
	if ($fcontents['status'] == 1) {
		if ($debug) {echo 'prepare: --Сигналы открытия получены<br>';}
		$open_signals = $fcontents['data']; 
	} else {
		if ($debug) {echo 'prepare: --Сигналы открытия не обнаружены<br>';}
		$open_signals = [];
	}
} else {
	$open_signals = [];
}

/* Получаем сигналы закрытия */
if ($close_url) {
	if ($debug) {echo 'prepare: --Получаем сигналы закрытия<br>';}
	$fcontents = implode ('', file ($close_url));
	$fcontents = json_decode($fcontents, true);
	if ($fcontents['status'] == 1) {
		if ($debug) {echo 'prepare: --Сигналы закрытия получены<br>';}
		$close_signals = $fcontents['data']; 
	} else {
		if ($debug) {echo 'prepare: --Сигналы закрытия не обнаружены<br>';}
		$close_signals = [];
	}
} else {
	$close_signals = [];
}

/* Получаем ордера из хранилища */
$result = $db->query("SELECT * FROM `orders` WHERE `set_id` = '$set_id'");
if ($debug) {echo 'prepare: --Получаем ордера из хранилища<br>';}
if ($db->num_rows($result) > 0) {
	if ($debug) {echo 'prepare: --Ордера из хранилища получены<br>';}
	$i = 0;
	while($row = $db->get_array($result)) {
		$db_orders[$i] = $row;
		$i++;
	}
} else {
	if ($debug) {echo 'prepare: --Ордера из хранилища не обнаружены<br>';}
	$db_orders = [];
}

/* Получаем позиции из хранилища */
$result = $db->query("SELECT * FROM `positions` WHERE `set_id` = '$set_id'");
if ($debug) {echo 'prepare: --Получаем позиции из хранилища<br>';}
if ($db->num_rows($result) > 0) {
	if ($debug) {echo 'prepare: --Позиции из хранилища получены<br>';}
	$i = 0;
	while($row = $db->get_array($result)) {
		$db_positions[$i] = $row;
		$i++;
	}
} else {
	if ($debug) {echo 'prepare: --Позиции из хранилища не обнаружены<br>';}
	$db_positions = [];
}

/* Получаем ордера из биржи */
$url = $ex_api . "?mode=getOrders&api_key=$api_key&api_secret=$api_secret&arg=$arg";
if ($debug) {echo 'prepare: --Получаем ордера из биржи<br>';}
$fcontents = implode ('', file ($url));
$fcontents = json_decode($fcontents, true);
if ($fcontents['status'] == 1) {
	if ($debug) {echo 'prepare: --Ордера из биржи получены<br>';}
	$ex_orders = $fcontents['data'];
} else {
	if ($debug) {echo 'prepare: --Ордера из биржи не обнаружены<br>';}
	$ex_orders = [];
}

/* Получаем балансы пользователя */
$url = $ex_api . "?mode=getBalances&api_key=$api_key&api_secret=$api_secret&arg=$arg";
if ($debug) {echo 'prepare: --Получаем балансы из биржи<br>';}
$fcontents = implode ('', file ($url));
$fcontents = json_decode($fcontents, true);
if ($fcontents['status'] == 1) {
	if ($debug) {echo 'prepare: --Балансы из биржи получены<br>';}
	$balances = $fcontents['data'];
} else {
	if ($debug) {echo 'prepare: --Балансы из биржи не обнаружены<br>';}
	$balances = [];
	exit();
}

/* Получаем котировки биржи */
$url = $ex_api . "?mode=getPrices";
if ($debug) {echo 'prepare: --Получаем котировки из биржи<br>';}
$fcontents = implode ('', file ($url));
$fcontents = json_decode($fcontents, true);
if ($fcontents['status'] == 1) {
	if ($debug) {echo 'prepare: --Котировки из биржи получены<br>';}
	$prices = $fcontents['data'];
} else {
	if ($debug) {echo 'prepare: --Котировки из биржи не обнаружены<br>';}
	$prices = [];
	exit();
}

/* Отправка ордеров на покупку */
function send_buy_order($symbol,$price,$qty) {
	global $db;
	global $arg;
	global $debug;
	global $ex_api;
	global $api_key;
	global $api_secret;
	global $balances;
	/* Получаем правила биржи для заданной торгуемой пары */
	$result = $db->query("SELECT * FROM `pairs` WHERE `symbol` = '$symbol'");
	$row = $db->get_object($result);
	/* Получаем округление по условиям биржи */
	$around_price = abs(log10($row->step_price));
	$around_qty = abs(log10($row->step_qty));
	/* Считаем необходимую сумму */
	$sum = $price * $qty;
	/* Проверяем, что баланса достаточно: */
	$funds = $balances[$row->quote];
	/* Если средств недостаточно, завершаем функцию */
	if ($funds > $sum) {
		/* Подготавливаем ссылку */
		$send_url = $ex_api . "?mode=sendOrder&api_key=$api_key&api_secret=$api_secret&symbol=$symbol&side=buy&qty=".number_format($qty,$around_qty,'.','')."&price=".number_format($price,$around_price,'.','')."&arg=$arg";
		if ($debug) {echo 'send_buy: ссылка '.$send_url.'<br>';}
		$fcontents = implode ('', file ($send_url));
		$fcontents = json_decode($fcontents, true);
		if ($fcontents['status'] == 1) {
			return $fcontents['order_id'];
		} else {
			return 0;
		}
	} else {
		if ($debug) {echo 'send_buy: баланса не хватает<br>';}
		return 0;
	}
}

function send_sell_order($symbol,$price,$qty) {
	global $db;
	global $arg;
	global $debug;
	global $ex_api;
	global $api_key;
	global $api_secret;
	global $balances;
	global $fee;
	/* Получаем правила биржи для заданной торгуемой пары */
	$result = $db->query("SELECT * FROM `pairs` WHERE `symbol` = '$symbol'");
	$row = $db->get_object($result);
	/* Проводим округление по условиям биржи */
	$around_price = abs(log10($row->step_price));
	$around_qty = abs(log10($row->step_qty));
	$qty = $qty*(1-$fee/100);
	/* Проверяем, что баланса достаточно: */
	$funds = $balances[$row->base];
	/* Если средств недостаточно, продаем все что есть */
	if ($funds < $qty) {
		if ($debug) {echo 'send_sell: баланса недостаточно, продаем все что есть<br>';}
		$qty = $funds;
	}
	/* Подготавливаем ссылку */
	$send_url = $ex_api . "?mode=sendOrder&api_key=$api_key&api_secret=$api_secret&symbol=$symbol&side=sell&qty=".number_format($qty,$around_qty,'.','')."&price=".number_format($price,$around_price,'.','')."&arg=$arg";
	if ($debug) {echo 'send_sell: ссылка '.$send_url.'<br>';}
	$fcontents = implode ('', file ($send_url));
	$fcontents = json_decode($fcontents, true);
	if ($fcontents['status'] == 1) {
		return $fcontents['order_id'];
	} else {
		return 0;
	}		
}

//////////////////////////
/* Основной код расчета */
//////////////////////////

/* Обработка сигналов */
if ($debug) {
	echo 'signals: START<br>';
	echo 'signals: --Сравниваем сигналы и позиции<br>';
}
$num = 0;
if (count($open_signals) > 0) {
	$num = count($open_signals);
	/* Сравниваем сигналы и открытые позиции в базе */
	if (count($db_positions) > 0) {
		/* Перебираем все позиции */
		for ($i=0;$i<count($db_positions);$i++) {
			/* Перебираем все сигналы */
			for ($j=0;$j<count($open_signals);$j++) {
				/* Если в сигналах присутствует пара, по которой открыта позиция, проверяем стоит ли на ней пауза */
				if ($db_positions[$i]['symbol'] == $open_signals[$j]['symbol'] && ($current_time - $db_positions[$i]['open_time']) < $pause) {
					/* Удаляем ее из списка сигналов */
					array_splice($open_signals,$j,1);
				}
			}
		}
	}
}
if ($debug) {
	echo 'signals: --Поступило '.$num.' сигналов<br>';
	echo 'signals: --Перебрано '.count($db_positions).' позиций<br>';
	echo 'signals: --Вышло '.count($open_signals).' сигналов<br>';
	echo 'signals: --Сравниваем сигналы и ордера<br>';
}
$num = 0;
/* Если в массиве остались данные, проверяем есть ли у нас отправленные ордера */
if (count($open_signals) > 0) {
	$num = count($open_signals);
	/* Сравниваем сигналы и открытые позиции в базе */
	if (count($db_orders) > 0) {
		/* Перебираем все ордера */
		for ($i=0;$i<count($db_orders);$i++) {
			/* Перебираем все сигналы */
			for ($j=0;$j<count($open_signals);$j++) {
				/* Если в сигналах присутствует пара, по которой отправлен ордер */
				if ($db_orders[$i]['symbol'] == $open_signals[$j]['symbol']) {
					/* Удаляем ее из списка сигналов */
					array_splice($open_signals,$j,1);
				}
			}
		}
	}	
}
if ($debug) {
	echo 'signals: --Поступило '.$num.' сигналов<br>';
	echo 'signals: --Перебрано '.count($db_orders).' ордеров<br>';
	echo 'signals: --Вышло '.count($open_signals).' сигналов<br>';
}

/* Если сигналы остались, берем первый по списку и отправляем */							
/* Остальные сигналы отправятся при последующих прогонах программы */						
/* Не будем посылать сразу несколько ордеров, чтобы не получить бан от биржи */		
if (count($open_signals) > 0) {
	$order = array_shift($open_signals);
	if ($debug) {echo 'signals: Подготовка к отправке ордера на покупку '.$order['symbol'].'<br>';}	
	/* Определяем размер позиции */															
	$qty = $amount / $order['eq_usdt'];
	/* Цену повышаем на величину delta%, чтоб не упустить позицию */						
	$price = $order['price']*(1 + $delta/100);
	$symbol = $order['symbol'];
	/* Отправляем ордер */																	
	$order_id = send_buy_order($symbol,$price,$qty);
	if ($order_id) {
		if ($debug) {echo 'signals: --Ордер успешно отправлен<br>';}	
		/* Записываем в хранилище отправленный ордер */										
		$db->query("INSERT INTO `orders`(`symbol`, `order_id`, `price`, `qty`, `side`, `open_time`, `position`, `close_res`, `set_id`) VALUES  ('$symbol','$order_id','$price','$qty','BUY','$current_time','0','','$set_id')");
		/* И завершаем операцию */															
	} else {
		if ($debug) {echo 'signals: --Ошибка! Сбой отправки ордера<br>';}	
		
	}
}

if ($debug) {
	echo 'signals: END<br>';
	echo 'orders: START<br>';
	echo 'orders: --Сравниваем ордера хранилища и ордера биржи<br>';
}
/* Обработка ордеров */
$open_num = 0;
$close_num = 0;
$upd_num = 0;
if (count($db_orders) > 0) {
	/* Создаем очередь ордеров */
	$queue_orders = [];
	if (count($ex_orders) > 0) {
		/* Сравниваем ордера биржи и хранилища */
		for ($i=0;$i<count($db_orders);$i++) {
			/* Совпадение */
			$num = 0;
			for ($j=0;$j<count($ex_orders);$j++) {
					/* Если такой ордер был отправлен, плюсуем совпадения */
				if ($db_orders[$i]['symbol'] == $ex_orders[$j]['symbol']) {
					$num = 1;
					break;
				}
			}
			/* Если совпадение не нашлось, значит ордер исполнился */
			if (!$num) {
				if ($debug) {echo 'orders: --Обнаружен исполнившийся ордер<br>';}
				/* Запоминаем ордер */
				$queue_orders[] = $db_orders[$i];
			}
		}

	} else {
		/* Переводим все ордера из хранилища в позиции */
		for ($i=0;$i<count($db_orders);$i++) {
			if ($debug) {echo 'orders: --Обнаружен исполнившийся ордер<br>';}
			/* Запоминаем ордер */
			$queue_orders[] = $db_orders[$i];
		}
	}
	
	/* Проверяем, что очередь ордеров не пустая */
	if (count($queue_orders) > 0) {
		if ($debug) {echo 'orders: --Перебираем исполнившиеся ордера<br>';}
		for ($i=0;$i<count($queue_orders);$i++) {
			/* Если ордер на покупку, то открываем позицию */
			if ($queue_orders[$i]['side'] == 'BUY') {
				if ($debug) {echo 'orders: --Обнаружен ордер на покупку<br>';}
				/* Удаляем ордер из хранилища ордеров и делаем запись в хранилище позиций */
				$id = $queue_orders[$i]['id'];
				$db->query("DELETE FROM `orders` WHERE `id` = '$id'");
				$symbol = $queue_orders[$i]['symbol'];
				$price = $queue_orders[$i]['price'];
				$qty = $queue_orders[$i]['qty'];
				/* Прежде чем сделать запись в хранилище position проверяем есть у нас позиция с данной парой */
				$n=-1;
				/* Перебираем все позиции и ищем с нашей торговой парой */
				for ($j=0;$j<count($db_positions);$j++) {
					/* Если пара найдена, запоминаем ее индекс */
					if ($queue_orders[$i]['symbol'] == $db_positions[$j]['symbol']) {
						/* Запоминаем индекс позиции */
						$n = $j;
					}
				}
				/* Если индекс найден, обновляем позицию, если не найден, создаем позицию */
				if ($n != -1) {
					if ($debug) {echo 'orders: --Обновляем позицию<br>';}	
					$id = $db_positions[$n]['id'];
					/* Вычисляем новый объем, новую цену */
					$new_qty = $qty + $db_positions[$n]['qty'];
					$new_price = ($price*$qty + $db_positions[$n]['price']*$db_positions[$n]['qty'])/($new_qty);
					/* Обновляем позицию в хранилище */
					$db->query("UPDATE `positions` SET `price`='$new_price',`qty`='$new_qty',`open_time`='$current_time',`max_price`='$new_price' WHERE `id` = '$id'");
					/* Обновляем позицию в переменной */
					$db_positions[$n]['qty'] = $new_qty;
					$db_positions[$n]['price'] = $new_price;
					$db_positions[$n]['max_price'] = $new_price;
					$db_positions[$n]['open_time'] = $current_time;
					$upd_num++;
				} else {
					if ($debug) {echo 'orders: --Добавляем позицию<br>';}
					/* Если позиции с такой парой у нас нет, создаем новую позицию */
					$result = $db->query("SHOW TABLE STATUS LIKE 'positions'");
					$db->query("INSERT INTO `positions`(`symbol`, `price`, `qty`, `open_time`, `max_price`, `set_id`) VALUES ('$symbol','$price','$qty','$current_time','$price','$set_id')");
					/* Узнаем индекс под которым будет произведена запись */
					$row = $db->get_array($result);
					$new_id = $row['Auto_increment'];
					$n = count($db_positions);
					/* Добавляем позицию в переменную */
					$db_positions[$n]['symbol'] = $symbol;
					$db_positions[$n]['qty'] = $qty;
					$db_positions[$n]['price'] = $price;
					$db_positions[$n]['max_price'] = $price;
					$db_positions[$n]['open_time'] = $current_time;
					$db_positions[$n]['id'] = $new_id;
					$db_positions[$n]['set_id'] = $set_id;
					$open_num++;
				}
			}
			/* Если ордер на продажу, то закрываем позицию */
			if ($queue_orders[$i]['side'] == 'SELL') {
				if ($debug) {echo 'orders: --Обнаружен ордер на продажу<br>';}
				/* Удаляем ордер из ханилища ордеров и переводим позицию в историю */
				$id = $queue_orders[$i]['id'];
				$db->query("DELETE FROM `orders` WHERE `id` = '$id'");
				$symbol = $queue_orders[$i]['symbol'];
				$close_price = $queue_orders[$i]['price'];
				$position_id = $queue_orders[$i]['position'];
				$close_res = $queue_orders[$i]['close_res'];
				$n=-1;
				/* Перебираем все позиции и ищем с нашей торговой парой */
				for ($j=0;$j<count($db_positions);$j++) {
					/* Если пара найдена, запоминаем ее индекс */
					if ($queue_orders[$i]['symbol'] == $db_positions[$j]['symbol']) {
						/* Запоминаем индекс позиции */
						$n = $j;
					}
				}
				/* Если индекс найден, удаляем позицию и переводим ее в историю, если не найден выводим ошибку */
				if ($n != -1) {
					if ($debug) {echo 'orders: --Удаляем позицию<br>';}	
					$db->query("DELETE FROM `positions` WHERE `id` = '$position_id'");
					$open_price = $db_positions[$n]['price'];
					$qty = $db_positions[$n]['qty'];
					$open_time = $db_positions[$n]['open_time'];
					$close_time = time();
					$qty = number_format($qty,8,'.','');
					$open_price = number_format($open_price,8,'.','');
					$close_price = number_format($close_price,8,'.','');
					if ($debug) {echo 'orders: --Переводим позицию в историю<br>';}	
					$db->query("INSERT INTO `history`( `symbol`, `open_price`, `close_price`, `qty`, `open_time`, `close_time`, `close_res`, `set_id`) VALUES ('$symbol','$open_price','$close_price','$qty','$open_time','$close_time','$close_res','$set_id')");
					/* Удаляем позицию из переменной */
					array_splice($db_positions,$n,1);
				} else {
					if ($debug) {echo 'orders: --Ошибка! Позиция не обнаружена<br>';}	
				}
				$close_num++;
			}
		}
	}
}

if ($debug) {
	echo 'orders: --Позиций открыто '.$open_num.'<br>';
	echo 'orders: --Позиций обновлено '.$upd_num.'<br>';
	echo 'orders: --Позиций закрыто '.$close_num.'<br>';
	echo 'orders: END<br>';
	echo 'positions: START<br>';
}

/* Обработка позиций */
if (count($db_positions) > 0) {
	/* Создаем очередь ордеров */
	$queue_orders = [];
	$temp_order = [];
	if ($debug) {echo 'position: --Позиции в базе получены<br>';}
	/* Перебираем все позиции и проверяем условия выхода */
	for ($i=0;$i<count($db_positions);$i++) {
		if ($debug) {echo 'position: --Обрабатываем данные '.$i.' позиции<br>';}
		$position_id = $db_positions[$i]['id'];
		$symbol = $db_positions[$i]['symbol'];
		$open_price = $db_positions[$i]['price'];
		$open_time = $db_positions[$i]['open_time'];
		$qty = $db_positions[$i]['qty'];
		/* Проверяем обновилась ли максимальная цена */
		if ($db_positions[$i]['max_price'] > $prices[$symbol]) {
			$max_price = $db_positions[$i]['max_price'];
		} else {																
			if ($debug) {echo 'position: --Максимальная цена обновилась<br>';}
			$max_price = $prices[$symbol];
			$max_price = number_format($max_price,8,'.','');
			/* Обновляем максимальную цену в хранилище */
			$db->query("UPDATE `positions` SET `max_price` = '$max_price' WHERE `id` = '$position_id'");
		}
		/* Проверяем позиции на соответствие условиям выхода */
		/* Счетчик сигналов на продажу и причина продажи */
		$res = "";
		/* Проверка на аварийный выход, не зависящий от паузы */
		if ($close_exit) {
			/* Проверяем, что цена упала ниже цены открытия позиции на величину close_exit в % */
			if (($prices[$symbol]/$open_price) < (1 - $close_exit/100)) {
				if ($debug) {echo 'position: --Выход по close_exit<br>';}
				$res = "EXIT";
			}
		}
		/* Проверяем остальные критерии */
		if (!$res) {
			/* Проверяем, стоят ли позиции на паузе */
			if ($current_time > ($open_time + $pause)) {
				/* Выход по сигналам */
				if (count($close_signals) > 0) {
					for ($j=0;$j<count($close_signals);$j++) {
						/* Проверяем совпадение пары */
						if ($symbol == $close_signals[$j]['symbol']) {
							if ($debug) {echo 'position: --Выход по close_url<br>';}
							/* Правим причину */
							$res = "URL";
							/* Прерываем цикл поиска по сигналам */
							break;
						}
					}
				}
				/* Выход по времени */
				if ($close_time && !$res) {
					/* Проверяем прошло ли время жизни позиции */
					if ($current_time > ($open_time + $close_time)) {
						if ($debug) {echo 'position: --Выход по close_time<br>';}
						/* Правим причину */
						$res = "TIME";
					}
				}
				/* Выход по потере профита */
				if ($close_lose && !$res) {
					/* Проверяем что профит положителен */
					$profit = $prices[$symbol] - $open_price;
					if ($profit > 0) {
						/* Проверяем падение профита на величину close_lose в % */
						if (($prices[$symbol]-$open_price)/($max_price-$open_price) < (1 - $close_lose/100)) {
							if ($debug) {echo 'position: --Выход по close_lose<br>';}
							/* Если профит упал ниже доступной границы, правим причину */
							$res = "LOSE";
						}
					} else {
						if ($debug) {echo 'position: --Профит меньше ноля<br>';}
						/* Если профит отрицателен, сразу правим причину */
						$res = "LOSE";
					}
				}
			} else {
				if ($debug) {echo 'position: --Пауза на паузе<br>';}

			}
		}
		/* Если мы нашли причину выхода, ставим ордер в очередь */
		if ($res) {
			$temp_order['symbol'] = $symbol;
			$temp_order['position'] = $position_id;
			$temp_order['res'] = $res;
			/* Понижаем цену на величину delta */
			$temp_order['price'] = $prices[$symbol]*(1 - $delta/100);
			$temp_order['qty'] = $qty*(1-$fee/100);
			$queue_orders[] = $temp_order;
		}
	}
	/* Если очередь не пуста отправляем ордера */
	if (count($queue_orders) > 0) {
		/* Проверяем все ордера и ищем, были ли отправлен ордер ранее */
		/* Совпадение */
		$n = 0;
		for($i=0;$i<count($queue_orders);$i++) {
			for ($j=0;$j<count($db_orders);$j++) {
				if ($queue_orders[$i][['symbol']] == $db_orders[$j]['symbol'] && $db_orders[$j]['side'] == "SELL") {
					/* Если данная пара присутствует в сигналах, запоминаем ее */
					$n = $j;
					break;
				}
			}
			/* Если ордер был отправлен ранее, удаляем его и отправляем новый */
			if ($n) {
				$id = $db_orders[$n]['id'];
				$order_id = $db_orders[$n]['order_id'];
				/* Если данная пара присутствует в сигналах, удаляем ордер */
				$url = $ex_api . "?mode=cancelOrder&api_key=$api_key&api_secret=$api_secret&order_id=$order_id&arg=$arg";
				$fcontents = implode ('', file ($url));
				$fcontents = json_decode($fcontents, true);
				if ($fcontents['status'] == 1) {
					if ($debug) {echo 'position: --Ордер удален с биржи<br>';}
					$db->query("DELETE FROM `orders` WHERE `id` = '$id'");
					
				} else {
					if ($debug) {echo 'position: --Ошибка! Ордер не был удален<br>';}
					
				}
			}
			$symbol = $queue_orders[$i]['symbol'];
			$price = $queue_orders[$i]['price'];
			$qty = $queue_orders[$i]['qty'];
			$res = $queue_orders[$i]['res'];
			$position_id = $queue_orders[$i]['position'];
			
			/* Отправляем новый ордер */
			$order_id = send_sell_order($symbol,$price,$qty);
			if ($order_id) {
				if ($debug) {echo 'position: --Ордер отправлен по '.$res.'<br>';}
				$qty = number_format($qty,8,'.','');
				$price = number_format($price,8,'.','');
				/* Записываем в хранилище отправленный ордер */
				$db->query("INSERT INTO `orders`(`symbol`, `order_id`, `price`, `qty`, `side`, `open_time`, `position`, `close_res`, `set_id`) VALUES ('$symbol','$order_id','$price','$qty','SELL','$current_time','$position_id','$res','$set_id')");

			}
		}
	}
}

if ($debug) {echo 'positions: END<br>';}

?>
