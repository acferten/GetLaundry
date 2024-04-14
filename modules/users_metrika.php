<?php

echo "gf";


# Курьер вернул заказ
if ($atext[0] == '/orders_back_kurer') {
    $wapp_prefix = "WA";

    if (substr($atext[1], 0, strlen($wapp_prefix)) == $wapp_prefix) {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://whatsapp.laundrybot.online/bot/webhook.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                "data" => [
                    "type" => "delivered",
                    "order_id" => substr_replace($atext[1], "", 0, 2),
                    "message_id" => $message_id,
                ]
            ]),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return;
    }

    $this->DelMessageText($chat_id, $message_id);


    # Информация о заявке
    $orders = R::findOne('orders', "id = $atext[1]");
    $user = R::findOne('users', "chat_id = {$orders["chat_id"]}");
    
    if ($user['lang'] == 'ru'){
        $buttons[] = [
            $this->buildInlineKeyBoardButton("5 ❄️ - супер", "/otziv $atext[1] 5"),
        ];
        $buttons[] = [
            $this->buildInlineKeyBoardButton("4 ❄️ - хорошо", "/otziv $atext[1] 4"),
        ];
        $buttons[] = [
            $this->buildInlineKeyBoardButton("3 ❄️ - нормально", "/otziv $atext[1] 3"),
        ];
        $buttons[] = [
            $this->buildInlineKeyBoardButton("2 ❄️ - плохо", "/otziv $atext[1] 2"),
        ];
        $buttons[] = [
            $this->buildInlineKeyBoardButton("1 ❄️ - ужасно", "/otziv $atext[1] 1"),
        ];
    } else {
        $buttons[] = [
             $this->buildInlineKeyBoardButton("5 ❄️ - super", "/otziv $atext[1] 5"),
         ];
         $buttons[] = [
             $this->buildInlineKeyBoardButton("4 ❄️ - good", "/otziv $atext[1] 4"),
         ];
         $buttons[] = [
             $this->buildInlineKeyBoardButton("3 ❄️ - OK", "/otziv $atext[1] 3"),
         ];
         $buttons[] = [
             $this->buildInlineKeyBoardButton("2 ❄️ - bad", "/otziv $atext[1] 2"),
         ];
         $buttons[] = [
             $this->buildInlineKeyBoardButton("1 ❄️ - terrible", "/otziv $atext[1] 1"),
         ];
    }

    $template = new Template("order/courier_delivered", $user['lang'], [
        new TemplateData(":chatId", $orders['chat_id']),
    ]);
    $template = $template->Load();

    $this->sendMessage($orders['chat_id'], $template->text, $buttons);

    # Когда курьер вернул заказ
    $set_orders = R::findOne('orders', "id = $atext[1]");
    $set_orders->time_end = time();
    $set_orders->timestamp_end = time();
    $set_orders->status = "4";
    
    R::store($set_orders);

    $this->DelMessageText($set_orders['temp_chat_id'], $set_orders['mess_id']);
    $this->sendOrdersAdmin($set_orders['temp_chat_id'], $atext[1], $user['username'], False);

    return;
}

#
if ($atext[0] == '/orders_orders_card_kurer') {
    $wapp_prefix = "WA";

    if (substr($atext[1], 0, strlen($wapp_prefix)) == $wapp_prefix) {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://whatsapp.laundrybot.online/bot/webhook.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                "data" => [
                    "type" => "payed",
                    "order_id" => substr_replace($atext[1], "", 0, 2),
                    "message_id" => $message_id,
                    "pay_type" => $atext[2],
                ]
            ]),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return;
    }

    $this->DelMessageText($chat_id, $message_id);

    # Когда курьер вернул заказ
    $set_orders = R::findOne('orders', "id = $atext[1]");
    $set_orders->paid = $atext[2];
    $set_orders->status = "6";
    R::store($set_orders);

    $this->sendOrdersAdmin($chat_id, $atext[1], $message_id);

    if ($atext[2] == 1) {
        $content_admin = "<b>💵Order #$atext[1] paid to the courier.</b> \nОrder completed 👍";
    } else if ($atext[2] == 2) {
        $content_admin = "<b>💳Order #$atext[1] paid by PermataBank.</b> \nОrder completed 👍";
    } else if ($atext[2] == 3) {
        $content_admin = "<b>💳Order #$atext[1] paid by Tinkoff.</b> \nОrder completed 👍";
    }

    # $this->sendMessage(ID_CHAT, $content_admin);


    # $this->sendMessage(ID_CHAT, "<b>👍 Order:</b> #$atext[1] completed");

    return;
}
# Отзыв о заказе
if ($atext[0] == '/otziv') {

    # Кол-во нажатий метрика
    $this->set_metrika($chat_id, 6);

    # Устанавливаем отзыв заказу
    # $this->get_orders($atext[1], "otziv", $atext[2])

    $orders = R::findOne('orders', "id = $atext[1]");
    $orders->otziv = $atext[2];
    $user = R::findOne("users", "chat_id = {$orders["chat_id"]}");
    R::store($orders);

    if ($atext[2] == '5') {
        if ($user['lang'] == 'ru'){
            $content1 = "🤩 Спасибо за высокую оценку нашей работы!

💰<b>Получите 2 кг или 1.600 рублей</b> на следующую стирку. 
🤳Выложите в Telegram Stories честный отзыв о нас.
👩🏻‍💻Отправьте менеджеру скриншот.

";
            $buttons[] = [
                $this->buildInlineKeyBoardButton("Написать менеджеру", " ", "https://t.me/LaundryBot_Russia"),
                # $this->buildInlineKeyBoardButton("Написать комментарий", "/otziv_comments $atext[1] $atext[2]"),
            ];
            
            $buttons[] = [
                $this->buildInlineKeyBoardButton("Рекомендовать другу", " ", "https://t.me/share/url?url=https://t.me/LaundryGo_bot?start=ref$chat_id"),
            ];
    
            $buttons[] = [
                $this->buildInlineKeyBoardButton("Заказать стирку", "/start 1"),
            ];
        } else {
            $content1 = "Thank you for appreciating and trusting LaundryBot.";
            
            $buttons[] = [
                $this->buildInlineKeyBoardButton("Recommend to a Friend", " ", "https://t.me/share/url?url=https://t.me/LaundryGo_bot?start=ref$chat_id"),
            ];
    
            $buttons[] = [
                $this->buildInlineKeyBoardButton("Order Laundry", "/start 1"),
            ];
        }

        

        $this->editMessageText($chat_id, $message_id, $content1, $buttons);
        
        $this->sendOrdersAdmin($orders['temp_chat_id'], $atext[1], $username, False);
        # $this->sendMessage(ID_CHAT, "<b>ℹ️ Заказ #$atext[1] </b>\nОценка клиента $atext[2]❄️");
    } else {

        
        
        if ($user['lang'] == 'ru'){
            $content1 = "😱 Это ужасно и не позволительно с нашей стороны.
Подскажите, пожалуйста, что конкретно вас не устроило в качестве стирки или в работе сервиса. 
🚨 Срочно сообщите об этом менеджеру.  
            
😇<b>Как мы можем исправить ситуацию?</b>";
            
            $buttons[] = [
                $this->buildInlineKeyBoardButton("Написать менеджеру", " ", "https://t.me/LaundryBot_Russia"),
                # $this->buildInlineKeyBoardButton("Написать комментарий", "/otziv_comments $atext[1] $atext[2]"),
            ];
    
            $buttons[] = [
                $this->buildInlineKeyBoardButton("Заказать стирку", "/start 1"),
            ];
        } else {
            $content1 = "Please tell me what did not suit you in the laundry and in the work of the service. How can we fix the situation?";
            
            $buttons[] = [
                 $this->buildInlineKeyBoardButton("Write a comment", " ", "https://t.me/LaundryGoBot"),
                 # $this->buildInlineKeyBoardButton("Write a comment", "/otziv_comments $atext[1] $atext[2]"),
             ];
    
            $buttons[] = [
                $this->buildInlineKeyBoardButton("Order Laundry", "/start 1"),
            ];
        }

        $this->editMessageText($chat_id, $message_id, $content1, $buttons);
        
        $this->sendOrdersAdmin($orders['temp_chat_id'], $atext[1], $username, False);
        # $this->sendMessage(ID_CHAT, "<b>ℹ️ Заказ #$atext[1] </b>\nОценка клиента $atext[2]❄️");
    }

    $this->DelMessageText($orders['temp_chat_id'], $orders['mess_id']);
   

    return;
}


#
if ($atext[0] == '/orders_oplata_kurer') {

    $this->DelMessageText($chat_id, $message_id);

    # Когда курьер вернул заказ
    $set_orders = R::findOne('orders', "id = $atext[1]");
    $set_orders->status = "4";
    R::store($set_orders);

    $this->sendOrdersAdmin($chat_id, $atext[1], "edit", $message_id);

    if ($atext[2] == 1) {
        $content_admin = "<b>👍 Order:</b> #$atext[1] paid to the courier. \n Оrder completed 👍";
    } else if ($atext[2] == 2) {
        $content_admin = "<b>👍 Order:</b> #$atext[1] paid by card. \n Оrder completed 👍";
    } else if ($atext[2] == 3) {
        $content_admin = "<b>💳 Order #$atext[1] paid by Tinkoff.</b> \nОrder completed 👍";
    }

    $this->sendMessage(ID_CHAT, $content_admin);

    return;
}

/*
# Кнопка написать комментарий, Отзыв о заказе
if($atext[0] == '/otziv_comments'){

    $this->DelMessageText($chat_id, $message_id);

    # Записываем команду
    $this->set_action($chat_id, "comments&$atext[1]&$atext[2]");

    $content = "Напишите комментарий";

    #$buttons[] = [
    #	$this->buildInlineKeyBoardButton("Рекомендовать другу", "/cancel"),
    #];

    $this->sendMessage($chat_id, $content, $buttons);

    return;
}


if($atext[0] && $get_action[0] == "comments"){

    # Устанавливаем отзыв заказу
    #$this->get_orders($get_action[1], "comments", $text)

    $orders = R::findOne('orders', "id = $get_action[1]");
    $orders->comments = $text;
    R::store($orders);

    $content = "Благодарим за оценку и доверие к сервису LaundryBot.";

    $buttons[] = [
        $this->buildInlineKeyBoardButton("Рекомендовать другу", " ", "https://t.me/share/url?url=https://t.me/LaundryGo_bot?start=ref$chat_id"),
    ];

    $buttons[] = [
        $this->buildInlineKeyBoardButton("Заказать стирку", "/start 1"),
    ];

    $this->sendMessage($chat_id, $content, $buttons);

    $this->sendMessage(ID_CHAT, "<b>ℹ️ Заказ #$get_action[1] </b> \nОценка клиента $get_action[2]❄️ \n\nКомментарий: $text");

    $this->del_action($chat_id);

    return;
}


*/


?>