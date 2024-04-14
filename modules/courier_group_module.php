<?php

if ($chat_id == GROUP_COURIER_CHAT_ID) {
    $courier_card;
    if (isset($data['callback_query']['from']['id'])) @$get_action_group = explode("&", ($this->get_action($chat_id . '_' . $data['callback_query']['from']['id'])));
    else @$get_action_group = explode("&", ($this->get_action($chat_id . '_' . $data['message']['from']['id'])));

    //file_put_contents("DD.txt", json_encode($data)."\n\n", FILE_APPEND);

    // курьер подобрал заказ и должен прикрепить его фото
    if ($atext[0] == "/order_courier_group_pickup") {

        $orderId = (int)$atext[1];

        if (!$orderId) return;
        
        //$user_chat_id = $data['callback_query']['from']['id'];
        //$user = R::findOne("users", "chat_id = $user_chat_id");

        $order = R::findOne('orders', "id = $orderId");
        $user = R::findOne("users", "chat_id = {$order[chat_id]} ");
        $user_chat_id = $user['chat_id'];
        
        $order->time_start =  time();
        $order->laundry_name = $atext[2];
        R::store($order);

        // Отправляю сообщение администратору
        $template = new Template("order/pickup/send_photo_group", null, [
            new TemplateData(":orderId", $orderId)
        ]);
        $template = $template->Load();
        
        $user_msg = new Template("pickup_order", $user['lang'], [
            new TemplateData(":time", date("d.m.Y H:i"))  
        ]);
        $user_msg = $user_msg->Load();
        $user_msg->LoadButtons();
        
        $this->sendMessage($user_chat_id, $user_msg->text, $user_msg->buttons);

        $response = $this->sendMessage($chat_id, $template->text, $template->buttons);
        $msg_id = $response['result']['message_id'];

        $this->set_action($chat_id . '_' . $user_chat_id, "pickup_order_send_photo_group&$orderId&$msg_id");
        $courier_card = $massage_id;
        //$this->DelMessageText($chat_id, $message_id);
        return;
    }

    // курьер отправил фото заказа
    if (isset($data['message']['photo']) && $get_action_group[0] == "pickup_order_send_photo_group") {
        $orderId = (int)$get_action_group[1];
        $user_chat_id = $data['message']['from']['id'];

        if (!$orderId) {
            $this->del_action($chat_id . "_" . $user_chat_id);
            return;
        }

        $order = R::findOne("orders", "id = $orderId");

        $newPhotoName = $this->saveFileGroup($data, $order);

        $order["photo_before"] = $newPhotoName;
        R::store($order);

        $template = new Template("order/pickup/send_photo_group_confirmation", null, [
            new TemplateData(":orderId", $orderId),
        ]);

        $template = $template->Load();
        $template->LoadButtons();

        $this->DelMessageText($chat_id, $get_action_group[2]);
        $this->DelMessageText($chat_id, $message_id);

        $response = $this->sendPhoto($chat_id, "https://laundrybot.online/GetLaundry/" . $newPhotoName, $template->text, $template->buttons);
        $msg_id = $response['result']['message_id'];
        $this->set_action($chat_id . '_' . $user_chat_id, "pickup_order_send_photo_group&$orderId&$msg_id");

        $this->DelMessageText($chat_id, $data['message']['message_id']);

        return;
    }

    if ($atext[0] == "/order_pickup_order_send_photo_group_deny") {
        $orderId = (int)$atext[1];

        if (!$orderId) return;

        $order = R::findOne("orders", "id = $orderId");
        unlink($order['photo_before']);

        $template = new Template("order/pickup/send_photo_group", null, [
            new TemplateData(":orderId", $orderId)
        ]);
        $template = $template->Load();

        $user_chat_id = $data['callback_query']['from']['id'];

        $this->DelMessageText($chat_id, $get_action_group[2]);

        $response = $this->sendMessage($chat_id, $template->text, $template->buttons);
        $msg_id = $response['result']['message_id'];
        $this->set_action($chat_id . '_' . $user_chat_id, "pickup_order_send_photo_group&$orderId&$msg_id");
    }

    if ($atext[0] == "/order_pickup_order_send_photo_group_success") {
        $orderId = (int)$atext[1];

        if (!$orderId) return;

        $user_chat_id = $data['callback_query']['from']['id'];

        $this->del_action($chat_id . '_' . $user_chat_id);

        $order = R::findOne("orders", "id = $orderId");
       
        $user = R::findOne("users", "chat_id = {$order["chat_id"]}");

        $this->DelMessageText($chat_id, $get_action_group[2]);
        $this->DelMessageText($chat_id, $order["courier_group_message_id"]);

        $this->DelMessageText($order['temp_chat_id'], $order['mess_id']);
        $this->sendOrdersAdmin($order['temp_chat_id'], $orderId, $username);

        return;
    }

    if ($atext[0] == "/order_courier_group_send_washers") {
        $orderId = (int)$atext[1];

        if (!$orderId) return;

        $order = R::findOne("orders", "id = $orderId");
        $user = R::findOne("users", "chat_id = {$order["chat_id"]}");

        $order->in_laundry = date("d.m.Y H:i");
        R::store($order);

        // $this->DelMessageText($chat_id, $order["courier_group_message_id"]);
        $this->DelMessageText($order['temp_chat_id'], $order['mess_id']);
        $this->DelMessageText($chat_id, $message_id);
        $this->sendOrderWasher($orderId, $user["username"], 0);
    }

    if ($atext[0] == '/cancel_osob_end_group') {

        $orderId = (int)$atext[1];

        if (!$orderId) return;

        $order = R::findOne("orders", "id = $orderId");
        $user = R::findOne("users", "chat_id = {$order["chat_id"]}");

        $this->DelMessageText($chat_id, $message_id);
        $this->sendOrderCourierGroup($orderId, $user["username"], 2);

        return;
    }

    # 1 Отправка вес заказа
    if ($atext[0] == '/orders_ves_kurer_group') {
        $this->DelMessageText($chat_id, $message_id);

        $buttons[] = [
            $this->buildInlineKeyBoardButton("🚫 NO", "/cancel_osob_end $atext[1]"),
        ];

        $template = new Template('order/pickup/order_weight');

        $send = $this->sendMessage($chat_id, $template->text, $buttons);

        $mess_id = $send['result']['message_id'];

        # Записываем команду
        $this->set_action($chat_id, "orders_ves_kurer_group&$mess_id&$atext[1]");

        return;
    }

    # 2 Отправка вес заказа
    if (isset($atext[0]) && $get_action_group[0] == 'orders_ves_kurer_group') {

        # $get_action[2] - номер заказа
        # $get_action[1] - ид сообщения
        # $get_action[0] - текст команды

        $this->DelMessageText($chat_id, $message_id);

        $atext_ves = explode(" ", $text);

        // Записываем вес каждой позиции
        $weight_all = [
            'closes' => trim($atext_ves[0]),
            'shoes' => trim($atext_ves[1]),
            'bed_linen' => trim($atext_ves[2]),
            'organic' => trim($atext_ves[3])
        ];

        // Расчет стоимости
        $prices = [
            'closes' => $weight_all['closes'] * 80000,
            'shoes' => $weight_all['shoes'] * 120000,
            'bed_linen' => $weight_all['bed_linen'] * 50000,
            'organic' => $weight_all['organic'] * 120000
        ];

        // Стоимость за все вещи
        $price = number_format(array_sum($prices), 0, "", ".");

        $template = new Template("order/pickup/order_weight_1", null, [
            new TemplateData(":orderId", $get_action[2]),

            // Информация о весе одежды
            new TemplateData(":clothesWeight", $weight_all['closes']),
            new TemplateData(":clothesPrice", number_format($prices['closes'], 0, '', '.')),

            // Информация о количестве пар обуви
            new TemplateData(":pairOfShoes", $weight_all['shoes']),
            new TemplateData(":shoesPrice", number_format($prices['shoes'], 0, '', '.')),

            // Информация о весе постельного белья
            new TemplateData(":badLinenWeight", $weight_all['bed_linen']),
            new TemplateData(":badLinenPrice", number_format($prices['bed_linen'], 0, '', '.')),

            // Информация о вещах для органической стирки
            new TemplateData(":organicWeight", $weight_all['organic']),
            new TemplateData(":organicPrice", number_format($prices['organic'], 0, '', '.')),

            // Итоговая стоимость
            new TemplateData(":totalPrice", $price),
        ]);

        $template->Load();

        $buttons[] = [
            $this->buildInlineKeyBoardButton("✅ YES", "/orders_ves_kurer_ok_group success $get_action[2]"),
        ];
        $buttons[] = [
            $this->buildInlineKeyBoardButton("🚫 NO", "/cancel_osob_end $get_action[2]"),
        ];

        $this->editMessageText($chat_id, $get_action[1], $template->text, $buttons);

        # Записываем команду
        $this->set_action($chat_id, "$get_action[2]&$get_action[1]&$text");

        return;
    }

    # 3 Отправка вес заказа
    if ($atext[0] == '/orders_ves_kurer_ok_group') {

        $this->DelMessageText($chat_id, $message_id);

        $atext_ves = explode(" ", $get_action[2]);

        // Записываем вес каждой позиции
        $weight_all = [
            'closes' => trim($atext_ves[0]),
            'shoes' => trim($atext_ves[1]),
            'bed_linen' => trim($atext_ves[2]),
            'organic' => trim($atext_ves[3])
        ];

        // Рассчет стоимости
        $prices = [
            'closes' => $weight_all['closes'] * 80000,
            'shoes' => $weight_all['shoes'] * 120000,
            'bed_linen' => $weight_all['bed_linen'] * 50000,
            'organic' => $weight_all['organic'] * 120000
        ];

        // Стоимость за все вещи
        $total_price = number_format(array_sum($prices), 0, "", ".");

        // Нужна для расчетов
        $unformatted_total_price = array_sum($prices);

        $wapp_prefix = "WA";

        if (substr($get_action[0], 0, strlen($wapp_prefix)) == $wapp_prefix) {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://whatsapp.laundrybot.online/GetLaundry/webhook.php',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode([
                    "data" => [
                        "type" => "weight",
                        "weight" => $atext_ves[0],
                        "order_id" => substr_replace($get_action[0], "", 0, 2),
                        "message_id" => $message_id,
                        "price_weight" => $prices['closes'],
                        "shoes" => $weight_all['shoes'],
                        "price_shoes" => $prices['shoes'],
                        "total_price" => $total_price,
                        "bed_linen" => $weight_all['bed_linen'],
                        "bed_linen_price" => $prices['bed_linen'],
                        "organic" => $weight_all['organic'],
                        "organic_price" => $prices['organic']
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

        # Записываем расчеты в БД
        $orders = R::findOne('orders', "id = $get_action[0]");

        $orders->wt = $weight_all['closes']; # Вес одежды
        $orders->price_wt = number_format($prices['closes'], 0, '', '.');  # Цена за одежду

        // TODO: Когда-нибудь нормально переписать на цикл с ключами

        if ($weight_all['shoes'] != 0) {
            $orders->shoes = $weight_all['shoes'];
            $orders->price_shoes = number_format($prices['shoes'], 0, '', '.');
        }

        if ($weight_all['bed_linen'] != 0) {
            $orders->bed_linen = $weight_all['bed_linen'];
            $orders->bed_linen_price = number_format($prices['bed_linen'], 0, '', '.');
        }

        if ($weight_all['organic'] != 0) {
            $orders->organic = $weight_all['organic'];
            $orders->organic_price = number_format($prices['organic'], 0, '', '.');
        }

        $orders->price = $total_price;
        R::store($orders);

        $this->sendOrdersAdmin($chat_id, $get_action[0], 1);

        $template = new Template("order/pickup/order_weight_2", null, [
            new TemplateData(":orderId", $get_action[2]),

            // Информация о весе одежды
            new TemplateData(":clothesWeight", $weight_all['closes']),
            new TemplateData(":clothesPrice", number_format($prices['closes'], 0, '', '.')),

            // Информация о количестве пар обуви
            new TemplateData(":pairOfShoes", $weight_all['shoes']),
            new TemplateData(":shoesPrice", number_format($prices['shoes'], 0, '', '.')),

            // Информация о весе постельного белья
            new TemplateData(":badLinenWeight", $weight_all['bed_linen']),
            new TemplateData(":badLinenPrice", number_format($prices['bed_linen'], 0, '', '.')),

            // Информация о вещах для органической стирки
            new TemplateData(":organicWeight", $weight_all['organic']),
            new TemplateData(":organicPrice", number_format($prices['organic'], 0, '', '.')),

            // Итоговая стоимость
            new TemplateData(":totalPrice", $total_price),
        ]);

        $template->Load();


        $buttons[] = [
            $this->buildInlineKeyBoardButton("Наличные курьеру в рупиях", "/sposob_pay 1 test $get_action[0]"),
        ];
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Перевод на BRI в рупиях", "/sposob_pay 2 $unformatted_total_price $get_action[0]"),
        ];
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Перевод на Тинькофф в рублях", "/sposob_pay 3 $unformatted_total_price $get_action[0]"),
        ];
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Оплатить бонусами", "/sposob_pay 4 $unformatted_total_price $get_action[0]"),
        ];

        $this->sendMessage($orders['chat_id'], $template->text, $buttons);

        $referal = R::findOne('referal', "chat_id = {$orders["chat_id"]}");

        if ($referal) {
            // Считаю бонусы для реферала
            $percent = 10;
            $price_balance = ($unformatted_total_price * $percent) / 100;
            $formatted_price_balance = number_format($price_balance, 0, "", ".");

            // Добавляю бонусы к балансу реферала
            $ref_user = R::findOne('users', "chat_id = {$referal["ref_id_user"]}");
            $ref_user["balance"] += $price_balance;
            R::store($ref_user);
            
            $user = R::findOne('users', "chat_id = {$orders["chat_id"]}");

            $formatted_ref_user_balance = number_format($ref_user["balance"], 0, "", ".");

            $templateUser = new Template("referal_notification", $user['lang'], [
                new TemplateData(":formattedPriceBalance", $formatted_price_balance),
                new TemplateData(":formattedRefUserBalance", $formatted_ref_user_balance),
                new TemplateData(":chatId", $ref_user["chat_id"]),
            ]);

            $templateUser = $templateUser->Load();
            $templateUser->LoadButtons();

            // Отправляю сообщение рефералу
            $this->sendMessage($ref_user["chat_id"], $templateUser->text, $templateUser->buttons);
        }

        return;
    }

    if ($atext[0] == '/order_courier_group_scales') {
        $orderId = (int)$atext[1];

        if (!$orderId) return;

        $order = R::findOne('orders', "id = $orderId");

        $user_chat_id = $data['callback_query']['from']['id'];

        $template = new Template("order/pickup/send_photo_group_scales", null, [
            new TemplateData(":orderId", $orderId)
        ]);
        $template = $template->Load();

        $response = $this->sendMessage($chat_id, $template->text, $template->buttons);
        $msg_id = $response['result']['message_id'];

        $this->set_action($chat_id . '_' . $user_chat_id, "order_courier_group_scales&$orderId&$msg_id");

        return;
    }

    if (isset($data['message']['photo']) && $get_action_group[0] == "order_courier_group_scales") {
        $orderId = (int)$get_action_group[1];
        $user_chat_id = $data['message']['from']['id'];

        if (!$orderId) {
            $this->del_action($chat_id."_".$user_chat_id);
            return;
        }

        $order = R::findOne("orders", "id = $orderId");

        $newPhotoName = $this->saveFileGroup($data, $order);

        $photo_array = json_decode($order["photo_on_the_scales"], true);

        $photo_array[count($photo_array)]['photo'] = $newPhotoName;

        $order["photo_on_the_scales"] = json_encode($photo_array);
        R::store($order);

        $template = new Template("order/pickup/send_photo_group_scales_confirmation", null,[
            new TemplateData(":orderId", $orderId),
        ]);

        $template = $template->Load();
        $template->LoadButtons();

        $this->DelMessageText($chat_id, $get_action_group[2]);
        $this->DelMessageText($chat_id, $message_id);

        $response = $this->sendPhoto($chat_id, "https://laundrybot.online/GetLaundry/" . $newPhotoName, $template->text, $template->buttons);
        $msg_id = $response['result']['message_id'];
        $this->set_action($chat_id.'_'.$user_chat_id, "order_courier_group_scales&$orderId&$msg_id");

        return;
    }

    if ($atext[0] == "/order_pickup_order_send_photo_scales_group_deny") {
        $orderId = (int)$atext[1];

        if (!$orderId) return;

        $order = R::findOne("orders", "id = $orderId");
        $photo_array = json_decode($order["photo_on_the_scales"], true);

        unlink($photo_array[count($photo_array) - 1]['photo']);
        unset($photo_array[count($photo_array) - 1]);

        $order["photo_on_the_scales"] = json_encode($photo_array);
        R::store($order);

        $template = new Template("order/pickup/send_photo_group_scales", null, [
            new TemplateData(":orderId", $orderId)
        ]);
        $template = $template->Load();

        $user_chat_id = $data['callback_query']['from']['id'];

        $this->DelMessageText($chat_id, $get_action_group[2]);

        $response = $this->sendMessage($chat_id, $template->text, $template->buttons);
        $msg_id = $response['result']['message_id'];
        $this->set_action($chat_id . '_' . $user_chat_id, "order_courier_group_scales&$orderId&$msg_id");
    }

    if ($atext[0] == "/order_pickup_order_send_photo_scales_group_success") {
        $orderId = (int)$atext[1];

        if (!$orderId) return;

        $user_chat_id = $data['callback_query']['from']['id'];

        $template = new Template("order/pickup/send_photo_group_scales_more_confirmation", null, [
            new TemplateData(":orderId", $orderId),
        ]);

        $template = $template->Load();
        $template->LoadButtons();

        $this->DelMessageText($chat_id, $get_action_group[2]);
        $this->DelMessageText($chat_id, $message_id);

        $response = $this->sendMessage($chat_id, $template->text, $template->buttons);
        $msg_id = $response['result']['message_id'];
        $this->set_action($chat_id . '_' . $user_chat_id, "order_courier_group_scales&$orderId&$msg_id");

        return;
    }

    if ($atext[0] == "/order_pickup_order_send_photo_scales_more_group_deny") {
        $orderId = (int)$atext[1];

        if (!$orderId) return;

        $user_chat_id = $data['callback_query']['from']['id'];

        $this->del_action($chat_id . '_' . $user_chat_id);

        $order = R::findOne("orders", "id = $orderId");
        $user = R::findOne("users", "chat_id = {$order["chat_id"]}");

        $this->DelMessageText($chat_id, $get_action_group[2]);
        $this->DelMessageText($chat_id, $order["courier_group_message_id"]);

        $order->waighed = date("d.m.Y H:i");
        $order->status = 3;
        R::store($order);
        $this->DelMessageText($order['temp_chat_id'], $order['mess_id']);
        $this->sendOrdersAdmin($order['temp_chat_id'], $orderId, $username);

        return;
    }

    if ($atext[0] == "/order_pickup_order_send_photo_scales_more_group_success") {
        $orderId = (int)$atext[1];

        if (!$orderId) return;

        $order = R::findOne('orders', "id = $orderId");

        $user_chat_id = $data['callback_query']['from']['id'];

        $template = new Template("order/pickup/send_photo_group_scales", null, [
            new TemplateData(":orderId", $orderId)
        ]);
        $template = $template->Load();

        $this->DelMessageText($chat_id, $get_action_group[2]);

        $response = $this->sendMessage($chat_id, $template->text, $template->buttons);
        $msg_id = $response['result']['message_id'];

        $this->set_action($chat_id . '_' . $user_chat_id, "order_courier_group_scales&$orderId&$msg_id");
        return;
    }

    if ($atext[0] == '/order_courier_group_photo_delivered') {
        $orderId = (int)$atext[1];

        if (!$orderId) return;

        $order = R::findOne('orders', "id = $orderId");

        $user_chat_id = $data['callback_query']['from']['id'];

        $template = new Template("order/pickup/send_photo_group_delivered", null, [
            new TemplateData(":orderId", $orderId)
        ]);
        $template = $template->Load();

        $response = $this->sendMessage($chat_id, $template->text, $template->buttons);
        $msg_id = $response['result']['message_id'];

        $this->set_action($chat_id . '_' . $user_chat_id, "order_courier_group_photo_delivered&$orderId&$msg_id");
        
        return;
    }

    if (isset($data['message']['photo']) && $get_action_group[0] == "order_courier_group_photo_delivered") {
        $orderId = (int)$get_action_group[1];
        $user_chat_id = $data['message']['from']['id'];

        if (!$orderId) {
            $this->del_action($chat_id . "_" . $user_chat_id);
            return;
        }

        $order = R::findOne("orders", "id = $orderId");

        $newPhotoName = $this->saveFileGroup($data, $order);

        $photo_array = json_decode($order["delivered_photo"], true);

        $photo_array[count($photo_array)]['photo'] = $newPhotoName;

        $order["delivered_photo"] = json_encode($photo_array);
        R::store($order);

        $template = new Template("order/pickup/send_photo_group_delivered_confirmation", null, [
            new TemplateData(":orderId", $orderId),
        ]);

        $template = $template->Load();
        $template->LoadButtons();

        $this->DelMessageText($chat_id, $get_action_group[2]);
        $this->DelMessageText($chat_id, $message_id);

        $response = $this->sendPhoto($chat_id, "https://laundrybot.online/GetLaundry/" . $newPhotoName, $template->text, $template->buttons);
        $msg_id = $response['result']['message_id'];
        $this->set_action($chat_id . '_' . $user_chat_id, "order_courier_group_photo_delivered&$orderId&$msg_id");

        return;
    }

    if ($atext[0] == "/order_pickup_order_send_photo_delivered_group_deny") {
        $orderId = (int)$atext[1];

        if (!$orderId) return;

        $order = R::findOne("orders", "id = $orderId");
        $photo_array = json_decode($order["delivered_photo"], true);

        unlink($photo_array[count($photo_array) - 1]['photo']);
        unset($photo_array[count($photo_array) - 1]);

        $order["delivered_photo"] = json_encode($photo_array);
        R::store($order);

        $template = new Template("order/pickup/send_photo_group_delivered", null, [
            new TemplateData(":orderId", $orderId)
        ]);
        $template = $template->Load();

        $user_chat_id = $data['callback_query']['from']['id'];

        $this->DelMessageText($chat_id, $get_action_group[2]);

        $response = $this->sendMessage($chat_id, $template->text, $template->buttons);
        $msg_id = $response['result']['message_id'];
        $this->set_action($chat_id . '_' . $user_chat_id, "order_courier_group_photo_delivered&$orderId&$msg_id");
    }

    if ($atext[0] == "/order_pickup_order_send_photo_delivered_group_success") {
        $orderId = (int)$atext[1];

        if (!$orderId) return;

        $user_chat_id = $data['callback_query']['from']['id'];

        $template = new Template("order/pickup/send_photo_group_delivered_more_confirmation", null, [
            new TemplateData(":orderId", $orderId),
        ]);

        $template = $template->Load();
        $template->LoadButtons();

        $this->DelMessageText($chat_id, $get_action_group[2]);
        $this->DelMessageText($chat_id, $message_id);

        $response = $this->sendMessage($chat_id, $template->text, $template->buttons);
        $msg_id = $response['result']['message_id'];
        $this->set_action($chat_id . '_' . $user_chat_id, "order_courier_group_photo_delivered&$orderId&$msg_id");

        return;
    }

    if ($atext[0] == "/order_pickup_order_send_photo_delivered_more_group_deny") {
        $orderId = (int)$atext[1];

        if (!$orderId) return;

        $user_chat_id = $data['callback_query']['from']['id'];

        $this->del_action($chat_id . '_' . $user_chat_id);

        $order = R::findOne("orders", "id = $orderId");
        $user = R::findOne("users", "chat_id = {$order["chat_id"]}");

        $order->status = 5;
        R::store($order);

        $this->DelMessageText($chat_id, $get_action_group[2]);
        $this->DelMessageText($chat_id, $order["courier_group_message_id"]);

        $this->DelMessageText($order['temp_chat_id'], $order['mess_id']);
        $this->sendOrdersAdmin(ID_CHAT, $orderId, $user["username"]);

        return;
    }

    if ($atext[0] == "/order_pickup_order_send_photo_delivered_more_group_success") {
        $orderId = (int)$atext[1];

        if (!$orderId) return;

        $order = R::findOne('orders', "id = $orderId");

        $user_chat_id = $data['callback_query']['from']['id'];

        $template = new Template("order/pickup/send_photo_group_delivered", null, [
            new TemplateData(":orderId", $orderId)
        ]);
        $template = $template->Load();

        $this->DelMessageText($chat_id, $get_action_group[2]);

        $response = $this->sendMessage($chat_id, $template->text, $template->buttons);
        $msg_id = $response['result']['message_id'];

        $this->set_action($chat_id . '_' . $user_chat_id, "order_courier_group_photo_delivered&$orderId&$msg_id");
        return;
    }
    
    // Кнопка Photo in laundry
    
    if ($atext[0] == "/order_courier_group_laundry_photo") {

        $orderId = (int)$atext[1];

        if (!$orderId) return;

        $order = R::findOne('orders', "id = $orderId");
        $order->in_laundry = date("d.m.Y H:i");
        R::store($order);
        
        $user_chat_id = $data['callback_query']['from']['id'];

        // Отправляю сообщение администратору
        $template = new Template("order/pickup/send_photo_group", null, [
            new TemplateData(":orderId", $orderId)
        ]);
        $template = $template->Load();

        $response = $this->sendMessage($chat_id, $template->text, $template->buttons);
        $msg_id = $response['result']['message_id'];

        $this->set_action($chat_id . '_' . $user_chat_id, "in_laundry_order_send_photo_group&$orderId&$msg_id");
        $courier_card = $massage_id;
        //$this->DelMessageText($chat_id, $message_id);
        return;
    }

    
    if (isset($data['message']['photo']) && $get_action_group[0] == "in_laundry_order_send_photo_group") {
        $orderId = (int)$get_action_group[1];
        $user_chat_id = $data['message']['from']['id'];

        if (!$orderId) {
            $this->del_action($chat_id . "_" . $user_chat_id);
            return;
        }

        $order = R::findOne("orders", "id = $orderId");

        $newPhotoName = $this->saveFileGroup($data, $order);

        $order["photo_in_laundry"] = $newPhotoName;
        R::store($order);

        $template = new Template("order/pickup/send_to_laundry_confirmation", null, [
            new TemplateData(":orderId", $orderId),
        ]);

        $template = $template->Load();
        $template->LoadButtons();

        $this->DelMessageText($chat_id, $get_action_group[2]);
        $this->DelMessageText($chat_id, $message_id);

        $response = $this->sendPhoto($chat_id, "https://laundrybot.online/GetLaundry/" . $newPhotoName, $template->text, $template->buttons);
        $msg_id = $response['result']['message_id'];
        $this->set_action($chat_id . '_' . $user_chat_id, "in_laundry_order_send_photo_group&$orderId&$msg_id");

        $this->DelMessageText($chat_id, $data['message']['message_id']);

        return;
    }

    if ($atext[0] == "/in_laundry_order_send_photo_group_deny") {
        $orderId = (int)$atext[1];

        if (!$orderId) return;

        $order = R::findOne("orders", "id = $orderId");
        unlink($order['photo_in_laundry']);

        $template = new Template("order/pickup/send_photo_group", null, [
            new TemplateData(":orderId", $orderId)
        ]);
        $template = $template->Load();

        $user_chat_id = $data['callback_query']['from']['id'];

        $this->DelMessageText($chat_id, $get_action_group[2]);

        $response = $this->sendMessage($chat_id, $template->text, $template->buttons);
        $msg_id = $response['result']['message_id'];
        $this->set_action($chat_id . '_' . $user_chat_id, "in_laundry_order_send_photo_group&$orderId&$msg_id");
    }

    if ($atext[0] == "/in_laundry_order_send_photo_group_success") {
        $orderId = (int)$atext[1];

        if (!$orderId) return;

        $user_chat_id = $data['callback_query']['from']['id'];

        $this->del_action($chat_id . '_' . $user_chat_id);

        $order = R::findOne("orders", "id = $orderId");
        $user = R::findOne("users", "chat_id = {$order["chat_id"]}");

        $this->DelMessageText($chat_id, $get_action_group[2]);
        $this->DelMessageText($chat_id, $order["courier_group_message_id"]);

        $this->DelMessageText($order['temp_chat_id'], $order['mess_id']);
        $this->sendOrderWasher($orderId, $user["username"], 0);

        return;
    }
    
    //
}