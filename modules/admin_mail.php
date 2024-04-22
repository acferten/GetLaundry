<?php

	# 1 Рассылка меню
	if($atext[0] == "/mail_menu_laundrybot" || preg_match('/^📧 Рассылка/', $text)){
 
		# удаляем команду
		$this->del_action($chat_id);
			
		$buttons[] = [
			$this->buildInlineKeyBoardButton("Отправить рассылку", "sendmail"),
		];
		 
		$this->sendMessage($chat_id, "<b>📧 Рассылка:</b>\n\n", $buttons);

	}

	# 2 Рассылка пишем сообщение
	if($atext[0] == "sendmail"){
  
		$this->DelMessageText($chat_id, $message_id); 
		
		# записываем команду
		$this->set_action($chat_id, "sendmail"); 
			
		$content = "<b>Напишите сообщение:</b>";
			
		$buttons[] = [
			$this->buildInlineKeyBoardButton("Отменить", "cancel"),
		];
			
		$this->sendMessage($chat_id, "<b>📧 Рассылка:</b>\n\n$content", $buttons);
	}

	# 3 Рассылка пользователям
	if($atext[0] && $get_action[0] == "sendmail"){
		
		# return exit;
		if($atext[0] == "cancel"){
			# удаляем команду
			$this->del_action($chat_id);
			$this->DelMessageText($chat_id, $message_id); 
			return;
		}
 
		# Блокируем кнопки
		if($callback_query_buttons){
			return exit;
		} 
		 
		$this->DelMessageText($chat_id, $message_id - 1);
		  
		$this->sendMessage($chat_id, "✅ Сообщение отправленно");
		
		$mess = "<b>Сообщение от администрации.</b>\n\n";
		
		# удаляем команду
		$this->del_action($chat_id);
		
		$sendmail = R::findAll('users', "status = 1");
		foreach($sendmail as $id_sendmail){
			// return exit;
			$this->sendMessage($id_sendmail['chat_id'], "$mess $text");
			#$key++;
			#$this->sendMessage(2136511333, "[$key] $id_sendmail[chat_id] - $id_sendmail[lastname] - $id_sendmail[username]");
		}
		
		return exit;
	}	










?>










