<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
  
# [Подключаем БД] 
require __DIR__ . '/core/RB/rb.php';
 
 
# Подключаем файл конфигураций 
$base = parse_ini_file("core/configs.ini", true);
		
# Токен бота телеграмм
define('TOKEN', $base['system']['telegram_token']);
# Подключение к бд
$mysql_status = $base['mysql']['status'];
$mysql_ip = $base['mysql']['ip'];
$mysql_dbname = $base['mysql']['dbname'];
$mysql_dbuser = $base['mysql']['dbuser'];
$mysql_password = $base['mysql']['password'];
		
$rb = R::setup( "mysql:host=$mysql_ip;dbname=$mysql_dbname", $mysql_dbuser, $mysql_password); 
  
	
# Время, которое сейчас 
$time = strtotime(date("d.m.Y H:i")); # перевод время в UNIX
# Прибавляем к нашему времени 1 минуту +60
# $get_time = $time + 7260;

# $date = $db_time - 1500; # отнимаем 25 минуту
# $time4 = date('H:i', $date); # преобразовываем в стандартное вренмя часы и минуты
			
# $date = date('H:i:m', $time); 
# $date1 = date('H:i:m', $get_time);
 #sendMessage(2136511333, "Крон работает" );
 
###  МОДУЛЬ ЗА 25 МИНУТ ДО ЗАЯВКИ (КОНТАКТНАЯ ИНФОРМАЦИЯ) ###
# sendMessage(2136511333, "Крон работает 444" );
# Выводим актуальные заявки
$cron_orders = R::findAll('users', 'status = 1');
			
	foreach($cron_orders as $cron_orders_o){
		# Выбираем время из бд в формате 10:00
		# $command = preg_replace('~[^\d\:]+~', '', $cron_orders_o['start_job']);
		# 1 переведем время из бд в unix
		$db_time = $cron_orders_o['time_nachalo']; #  
		 
		if($db_time == $time){
			$buttons[] = [
				buildInlineKeyBoardButton("Написать менеджеру", " ", "https://t.me/LaundryGoBot"),
			];
		
			sendMessage($cron_orders_o['chat_id'], 'Вы хотели заказать стирку, но что-то пошло не так. 
Если это техническая причина или у вас есть сомнения и вопросы, то мы с радостью подскажем. 

<b>Нажмите "Написать менеджеру" и расскажите, чем мы сможем помочь вам.</b>', $buttons);
			
		}
		 
	}
	 
	 
### КОНЕЦ МОДУЛЬ ЗА 25 МИНУТ ДО ЗАЯВКИ (КОНТАКТНАЯ ИНФОРМАЦИЯ) ###	
/*	
###  МОДУЛЬ ПОДТВЕРДИТЬ ГОТОВНОСТЬ ДО ЗАЯВКИ 2 ЧАСА ###
 		
	foreach($cron_orders as $cron_orders_o){
		
		$start_job = $cron_orders_o['start_job'] - 7200; # отнимаем 2 часа
		
		if($start_job == $time){
			$buttons[] = [
				$this->buildInlineKeyBoardButton("✅ Подтвердить", "confirm_orders $cron_orders_o[admin_ids] $cron_orders_o[number]"),
			];
			
			$this->sendMessage($cron_orders_o['user_ids'], "Подтвердите готовность по заявке <b>#$cron_orders_o[number]</b>\n<b>• Адрес:</b> $cron_orders_o[address]", $buttons);
	
		}
	}
### КОНЕЦ МОДУЛЬ ПОДТВЕРДИТЬ ГОТОВНОСТЬ ДО ЗАЯВКИ 2 ЧАСА ###		
	
	*/
	
/*	
switch($time){
	
	case '1646129280':
		$this->sendMessage(2136511333, "1<b>Крон работает:</b> $time | $date" );
	break;
	
	case '1643320681':
		$this->sendMessage(2136511333, "2<b>Крон работает:</b> $time | $date" );
	break;
}

*/

	
/** кнопка клавиатуры номер телефона и геолокация
     * @param $text
     * @param bool $request_contact
     * @param bool $request_location
     * @return array
	Пример:
	$buttons_phone[] = [
		$this->buildKeyboardButton("☎️ Отправить номер"),
	];
		$this->sendMessage($chat_id, "Отправьте номер телефона", $buttons_phone, 0);
    */
    function buildKeyboardButton($text, $request_contact = true, $request_location = true)
    {
        $replyMarkup = [
            'text' => $text,
            'request_contact' => $request_contact,
            'request_location' => $request_location,
        ];
        return $replyMarkup;
    }
	
	
	/** готовим набор кнопок клавиатуры
     * @param array $options
     * @param bool $onetime
     * @param bool $resize
     * @param bool $selective
     * @return string
     */
    function buildKeyBoard(array $options, $onetime = false, $resize = true, $selective = true)
    {
        $replyMarkup = [
            'keyboard' => $options,
            'one_time_keyboard' => $onetime,
            'resize_keyboard' => $resize,
            'selective' => $selective,
        ];
        $encodedMarkup = json_encode($replyMarkup, true);
        return $encodedMarkup;
    }
	
	/** набор кнопок inline
     * @param array $options
     * @return string
     */
    function buildInlineKeyBoard(array $options)
    {
        // собираем кнопки
        $replyMarkup = [
            'inline_keyboard' => $options,
        ];
        // преобразуем в JSON объект
        $encodedMarkup = json_encode($replyMarkup, true);
        // возвращаем клавиатуру
        return $encodedMarkup;
    }
	
	/** Кнопка inline
     * @param $text
     * @param string $callback_data
     * @param string $url
     * @return array
     */
    function buildInlineKeyboardButton($text, $callback_data = '', $url = '')
    {
        // рисуем кнопке текст
        $replyMarkup = [
            'text' => $text,
        ];
        // пишем одно из обязательных дополнений кнопке
        if ($url != '') {
            $replyMarkup['url'] = $url;
        } elseif ($callback_data != '') {
            $replyMarkup['callback_data'] = $callback_data;
        }
        // возвращаем кнопку
        return $replyMarkup;
    }
	
	
	/*
 	Отправляет сообщение
		* $chat_id - ID пользователя
		* $text - ваше сообщение
		* $params - 1 = нахождение кнопки вместе с текстом. 0 = клавиатура снизу
	Пример:
	$buttons[] = [
		$this->buildInlineKeyBoardButton("Ваш текст на кнопке", "команда кнопки]"),
	];
		$this->sendMessage($chat_id, $text, $buttons);
	*/
    function sendMessage($chat_id, $text, $buttons = NULL, $params = 1)
    {
		$content = [
            'chat_id' => $chat_id,
            'text' => $text,
			'parse_mode' => 'html'
        ];
		// если переданны кнопки то добавляем их к сообщению
        if (!is_null($buttons) && is_array($buttons)) {
			if($params == 1){
				$content['reply_markup'] = $this->buildInlineKeyBoard($buttons);
			}else{
				$content['reply_markup'] = $this->buildKeyBoard($buttons);	
			}
        }
		
       return $send = requestToTelegram($content, "sendMessage");
    }
	
	/*
 	Редактирует текст сообщения и кнопки
		* $chat_id - ID пользователя
		* $message_id - ID сообщения
		* $text - ваше сообщение
		* $params - 1 = нахождение кнопки вместе с текстом. 0 = клавиатура снизу
	Пример:
	$buttons[] = [
		$this->buildInlineKeyBoardButton("Ваш текст на кнопке", "команда кнопки]"),
	];
		$this->editMessageText($chat_id, $message_id, $text, $buttons);
	*/
    function editMessageText($chat_id, $message_id, $text, $buttons = NULL, $params = 1)
    {
		$content = [
            'chat_id' => $chat_id,
			'message_id' => $message_id,
            'text' => $text,
			'parse_mode' => 'html'
        ];
		// если переданны кнопки то добавляем их к сообщению
        if (!is_null($buttons) && is_array($buttons)) {
			if($params == 1){
				$content['reply_markup'] = $this->buildInlineKeyBoard($buttons);
			}else{
				$content['reply_markup'] = $this->buildKeyBoard($buttons);	
			}
        }
		
        return $send = requestToTelegram($content, "editMessageText");
    }
	
	/** Отправляем запрос в Телеграмм
     * @param $data
     * @param string $type
     * @return mixed
     */
   function requestToTelegram($data, $type)
    {
        $result = null;
		
        if (is_array($data)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.telegram.org/bot' . TOKEN . '/' . $type);
            curl_setopt($ch, CURLOPT_POST, count($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            $result = curl_exec($ch);
            curl_close($ch);
        }
        return $result1 = json_decode($result, true);
    }
	
	
	function setFileLog($time) {
        $fh = fopen('log_cron.txt', 'a') or die('can\'t open file');
        ((is_array($time)) || (is_object($time))) ? fwrite($fh, print_r($time, TRUE)."\n") : fwrite($fh, $time . "\n");
        fclose($fh);
    }
	
	
	