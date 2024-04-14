<?php
# Время, которое сейчас
$time = strtotime(date("d.m.Y H:i")); # перевод время в UNIX
# Прибавляем к нашему времени 1 минуту +60
# $get_time = $time + 7260;

# $date = $db_time - 1500; # отнимаем 25 минуту
# $time4 = date('H:i', $date); # преобразовываем в стандартное вренмя часы и минуты
			
# $date = date('H:i:m', $time); 
# $date1 = date('H:i:m', $get_time);
$this->sendMessage(2136511333, "$get_time 44 $date1" );
 
###  МОДУЛЬ ЗА 25 МИНУТ ДО ЗАЯВКИ (КОНТАКТНАЯ ИНФОРМАЦИЯ) ###
 /*
# Выводим актуальные заявки
$cron_orders = R::findAll('orders', 'status = 1');
			
	foreach($cron_orders as $cron_orders_o){
		# Выбираем время из бд в формате 10:00
		# $command = preg_replace('~[^\d\:]+~', '', $cron_orders_o['start_job']);
		# 1 переведем время из бд в unix
		$db_time = $cron_orders_o['data_reg']; # перевод время в UNIX
		 
		$start_job = $db_time + 300; #  
		
		if($start_job == $time){
			$buttons[] = [
				$this->buildInlineKeyBoardButton("✅ Ожидать свободную машину", "/to_wait $cron_orders_o[chat_id] $cron_orders_o[number]"),
			];
			$buttons[] = [
				$this->buildInlineKeyBoardButton("❌ Отменить поездку", "/cancel_orders $cron_orders_o[number]"),
			];
			
			$this->sendMessage($cron_orders_o['chat_id'], "Приносим свои извинения, в настоящее время свободных машин нет.", $buttons);
	
		}
		
	 
	}
	
	$users_cron = R::findAll('users');
			
	foreach($users_cron as $users_cron_o){
		# Выбираем время из бд в формате 10:00
		# $command = preg_replace('~[^\d\:]+~', '', $users_cron_o['start_job']);
		# 1 переведем время из бд в unix
		$db_time = $users_cron_o['time_pod']; # перевод время в UNIX
 
		if($db_time == $time){
 
			$this->sendMessage($users_cron_o['chat_id'], "Ваша подписка на время закончилась.\nДля продления можете вновь нажать кнопку <b>«стать водителем»</b>, оплатить подписку и продолжить работу!" );
	
		}
		
	 
	} */
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