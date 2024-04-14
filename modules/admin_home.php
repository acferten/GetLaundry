<?php

//if (1 > $chat_id) {
if ($chat_id == ID_CHAT) {
    # admin
    if ($atext[0] == '/admin') {

        $orders = R::count('orders');

        $buttons[] = [
            $this->buildInlineKeyBoardButton("📊 Аналитика", "/metrika"),
        ];

        $content = "<b>Админ панель:</b>\n\n";
        $content .= "<b>Кол-во заказов: $orders</b>";

        if ($atext[1] == 'mess_id') {
            $this->editMessageText($chat_id, $message_id, $content, $buttons);
        } else {
            $this->sendMessage($chat_id, $content, $buttons);
        }

        return;
    }

    if ($atext[0] == '/metrika') {

        $buttons[] = [
            $this->buildInlineKeyBoardButton("⬅️ Назад", "/admin mess_id"),
        ];

        $metrika1_users = R::count('users');
        $metrika1 = R::count('metrika', "count_n = 1");
        $metrika2 = R::count('metrika', "count_n = 2");
        $metrika3 = R::count('metrika', "count_n = 3");
        $metrika4 = R::count('metrika', "count_n = 4");
        $metrika5 = R::count('metrika', "count_n = 5");
        $metrika6 = R::count('metrika', "count_n = 6");
        $metrika7 = R::count('metrika', "count_n = 7");

        $content = "<b>📊 Аналитика:</b>\n\n";
        $content .= "<b>Сколько всего пользователей в боте:</b> $metrika1_users\n";
        $content .= "<b>/start:</b> $metrika1\n";
        $content .= "<b>Начать:</b> $metrika2\n";
        $content .= "<b>Отправить геолокацию:</b> $metrika3\n";
        $content .= "<b>Отправить номер:</b> $metrika4\n";
        $content .= "<b>Заказать стирку:</b> $metrika5\n";
        $content .= "<b>Оценить стирку:</b> $metrika6\n";
        $content .= "<b>Получить бесплатную стирку:</b> $metrika7\n";

        $this->editMessageText($chat_id, $message_id, $content, $buttons);

        return;
    }

    // увеличить количество бонусов пользователя
    if ($atext[0] == "/set_balance") {
        $this->DelMessageText($chat_id, $message_id);

        $template = new Template("admin/set_balance/set_balance");
        $template = $template->Load();
        $template->LoadButtons();

        $response = $this->sendMessage($chat_id, $template->text, $template->buttons);
        if ($response["ok"]) {
            $this->set_action($chat_id, "set_balance&{$response["result"]["message_id"]}");
        }

        return;
    }

    if ($atext[0] == "/set_balance_success" && $atext[1] && $atext[2]) {
        $this->DelMessageText($chat_id, $message_id);

        $userId = (int)$atext[1];
        $user = R::findOne("users", "id = $userId");
        if (!$user) {
            return;
        }

        // прибавляю пользователю заданное количество бонусов
        $user["balance"] += $atext[2];

        // обновляю данные пользователя
        R::store($user);

        $templateAdmin = new Template("admin/set_balance/success_admin", null, [
            new TemplateData(":userId", $userId),
            new TemplateData(":userUsername", "@{$user["username"]}"),
            new TemplateData(":userPhone", $user["phone"]),
            new TemplateData(":userWhatsapp", $user["whatsapp"]),
            new TemplateData(":addBalance", number_format($atext[2], 0, "", ".")),
            new TemplateData(":totalBalance", number_format($user["balance"], 0, "", ".")),
        ]);

        $templateAdmin = $templateAdmin->Load();

        $this->sendMessage($chat_id, $templateAdmin->text);
    
        $formattedRefUserBalance = number_format($user["balance"], 0, "", ".");
        $formattedPriceBalance = number_format($atext[2], 0, "", ".");

        $templateUser = new Template("admin/set_balance/success_user", $user->lang, [
            new TemplateData(":addBalance", number_format($atext[2], 0, "", ".")),
            new TemplateData(":totalBalance", number_format($user["balance"], 0, "", ".")),
            new TemplateData(":chatId", $user->chat_id), 
            new TemplateData(":formattedRefUserBalance", $formattedRefUserBalance), 
            new TemplateData(":formattedPriceBalance", $formattedPriceBalance), 
        ]);

        $templateUser = $templateUser->Load();
        $templateUser->LoadButtons();

        $this->sendMessage($user["chat_id"], $templateUser->text, $templateUser->buttons);

        return;
    }

    if ($atext[0] == "/set_balance_deny" && $atext[1]) {
        $this->DelMessageText($chat_id, $message_id);

        switch ($atext[1]) {
            case 1:
                $this->del_action($chat_id);

                $template = new Template("admin/set_balance/deny");
                $template = $template->Load();


                $this->sendMessage($chat_id, $template->text);
                return;
            case 2:
                $template = new Template("admin/set_balance/set_balance");
                $template = $template->Load();
                $template->LoadButtons();

                $response = $this->sendMessage($chat_id, $template->text, $template->buttons);
                if ($response["ok"]) {
                    $this->set_action($chat_id, "set_balance&{$response["result"]["message_id"]}");
                }
                return;
        }

        return;
    }

    if ($get_action[0] == "set_balance" && $atext[0] && $atext[1]) {

        $userId = (int)$atext[0];
        $user = R::findOne("users", "id = $userId");
        if (!$user) {
            return;
        }

        $template = new Template("admin/set_balance/confirmation", null, [
            new TemplateData(":userId", $userId),
            new TemplateData(":userUsername", "@{$user["username"]}"),
            new TemplateData(":userPhone", $user["phone"]),
            new TemplateData(":userWhatsapp", $user["whatsapp"]),
            new TemplateData(":addBalanceButton", $atext[1]),
            new TemplateData(":addBalance", number_format($atext[1], 0, "", ".")),
            new TemplateData(":totalBalance", number_format($user["balance"] + $atext[1], 0, "", ".")),
        ]);

        $template = $template->Load();
        $template->LoadButtons();

        $this->sendMessage($chat_id, $template->text, $template->buttons);

        $this->del_action($chat_id);

        $this->DelMessageText($chat_id, $get_action[1]);
        $this->DelMessageText($chat_id, $message_id);
        return;
    }

    // уменьшить количество бонусов пользователя
    if ($atext[0] == "/del_balance") {
        $this->DelMessageText($chat_id, $message_id);

        $template = new Template("admin/del_balance/del_balance");
        $template = $template->Load();
        $template->LoadButtons();

        $response = $this->sendMessage($chat_id, $template->text, $template->buttons);
        if ($response["ok"]) {
            $this->set_action($chat_id, "del_balance&{$response["result"]["message_id"]}");
        }

        return;
    }

    if ($atext[0] == "/del_balance_success" && $atext[1] && $atext[2]) {
        $this->DelMessageText($chat_id, $message_id);

        $userId = (int)$atext[1];
        $user = R::findOne("users", "id = $userId");
        if (!$user) {
            return;
        }

        file_put_contents("del_balance.txt", $atext[2]);

        // прибавляю пользователю заданное количество бонусов
        $user["balance"] -= $atext[2];

        // обновляю данные пользователя
        R::store($user);

        $templateAdmin = new Template("admin/del_balance/success", null, [
            new TemplateData(":userId", $userId),
            new TemplateData(":userUsername", "@{$user["username"]}"),
            new TemplateData(":userPhone", $user["phone"]),
            new TemplateData(":userWhatsapp", $user["whatsapp"]),
            new TemplateData(":delBalance", number_format($atext[2], 0, "", ".")),
            new TemplateData(":totalBalance", number_format($user["balance"], 0, "", ".")),
        ]);

        $templateAdmin = $templateAdmin->Load();

        $this->sendMessage($chat_id, $templateAdmin->text);

        return;
    }

    if ($atext[0] == "/del_balance_deny" && $atext[1]) {
        $this->DelMessageText($chat_id, $message_id);

        switch ($atext[1]) {
            case 1:
                $this->del_action($chat_id);

                $template = new Template("admin/del_balance/deny");
                $template = $template->Load();


                $this->sendMessage($chat_id, $template->text);
                return;
            case 2:
                $template = new Template("admin/del_balance/del_balance");
                $template = $template->Load();
                $template->LoadButtons();

                $response = $this->sendMessage($chat_id, $template->text, $template->buttons);
                if ($response["ok"]) {
                    $this->set_action($chat_id, "del_balance&{$response["result"]["message_id"]}");
                }
                return;
        }

        return;
    }

    if ($get_action[0] == "del_balance" && $atext[0] && $atext[1]) {

        $userId = (int)$atext[0];
        $user = R::findOne("users", "id = $userId");
        if (!$user) {
            return;
        }

        $template = new Template("admin/del_balance/confirmation", null, [
            new TemplateData(":userId", $userId),
            new TemplateData(":userUsername", "@{$user["username"]}"),
            new TemplateData(":userPhone", $user["phone"]),
            new TemplateData(":userWhatsapp", $user["whatsapp"]),
            new TemplateData(":delBalanceButton", $atext[1]),
            new TemplateData(":delBalance", number_format($atext[1], 0, "", ".")),
            new TemplateData(":totalBalance", number_format($user["balance"] - $atext[1], 0, "", ".")),
        ]);

        $template = $template->Load();
        $template->LoadButtons();

        $this->sendMessage($chat_id, $template->text, $template->buttons);

        $this->del_action($chat_id);

        $this->DelMessageText($chat_id, $get_action[1]);
        $this->DelMessageText($chat_id, $message_id);
        return;
    }

    // получить количество бонусов пользователя
    if ($atext[0] == "/get_balance") {
        $this->DelMessageText($chat_id, $message_id);

        $template = new Template("admin/get_balance/get_balance");
        $template = $template->Load();
        $template->LoadButtons();
        $response = $this->sendMessage($chat_id, $template->text, $template->buttons);
        if ($response["ok"]) {
            $this->set_action($chat_id, "get_balance&{$response["result"]["message_id"]}");
        }
        return;
    }

    if ($atext[0] == "/get_balance_deny") {
        $this->DelMessageText($chat_id, $message_id);
        $this->del_action($chat_id);
        $template = new Template("admin/get_balance/deny");
        $template = $template->Load();

        $this->sendMessage($chat_id, $template->text);
        return;
    }

    if ($get_action[0] == "get_balance" && $atext[0]) {
        $user_id = trim(strtolower($atext[0]));
        $user = R::findOne("users", "id = $user_id");
        if ($user) {
            $template = new Template("admin/get_balance/success", null, [
                new TemplateData(":userId", $user_id),
                new TemplateData(":userUsername", "@{$user["username"]}"),
                new TemplateData(":userPhone", $user["phone"]),
                new TemplateData(":userWhatsapp", $user["whatsapp"]),
                new TemplateData(":balance", number_format($user["balance"], 0, "", ".")),
            ]);

            $template = $template->Load();

            // отправляю собраное сообщение в админский чат
            $this->sendMessage($chat_id, $template->text);
        }

        $this->DelMessageText($chat_id, $get_action[1]);
        $this->DelMessageText($chat_id, $message_id);

        $this->del_action($chat_id);
    }

    // начисление пользователю бонусов с купленного пакета оптовых стирок
    if ($atext[0] == "/wholesale_payment" && $atext[1] && $atext[2]) {
        $user_id = (int)$atext[1];
        $log_wholesale_laundry_payment_id = (int)$atext[2];

        // получаю пользователя по id
        $user = R::findOne("users", "id = $user_id");
        if ($user) {
            // получаю оплату пакета оптовых стирок по user_id
            $log_wholesale_laundry_payment = R::findOne("logwholesalelaundrypayment", "id = $log_wholesale_laundry_payment_id");
            if ($log_wholesale_laundry_payment) {
                // получаю пакет оптовых стирок по id
                $wholesale_laundry = R::findOne("wholesale_laundry", "id = {$log_wholesale_laundry_payment["wholesale_laundry_id"]}");

                // обновляю баланс пользователя
                $user["balance"] += $wholesale_laundry["bonus_count"];
                R::store($user);

                // обновляю статус оплаты пакета оптовых стирок
                $log_wholesale_laundry_payment["status"] = 2;
                R::store($log_wholesale_laundry_payment);

                // формирую сообщение в админский чат
                // разделяю количество бонусов пользователя по разрядам точками
                $balance = number_format($user["balance"], 0, "", ".");
//                $admin_content = "Количество бонусов пользователя: <b>$balance IDR</b>.";

                $timestamp_date = date("d.m.Y", $log_wholesale_laundry_payment["timestamp"]);
                $timestamp_time = date("H:i", $log_wholesale_laundry_payment["timestamp"]);

                $pay_type_content_admin = "";
                switch ($log_wholesale_laundry_payment["pay_type"]) {
                    case 1:
                        $pay_type_content_admin = "transfer to bank BRI card.";
                        break;
                    case 2:
                        $pay_type_content_admin = "transfer to Tinkoff card";
                        break;
                }

                $bonus_count = number_format($wholesale_laundry["bonus_count"], 0, "", ".");

                $content_admin = "🔥 bonuses credited successfully\n\n";

                $content_admin = "User id: <b>{$user["id"]}</b>\n";
                $content_admin .= "<b>Created: $timestamp_date $timestamp_time</b>\n";
                $content_admin .= "User login: <b>@{$user["username"]}</b>\n";
                $content_admin .= "Number: <b>{$user["phone"]}</b>\n";
                $content_admin .= "Whatsapp: <b>{$user["whatsapp"]}</b>\n";
                $content_admin .= "Number of kilograms: <b>{$wholesale_laundry["weight"]} kg\n</b>";
                $content_admin .= "<b>Payment amount: $bonus_count IDR\n</b>";
                $content_admin .= "Pay type: <b>$pay_type_content_admin</b>";

                $this->DelMessageText($chat_id, $message_id);

                // отправляю собраное сообщение в админский чат
                $this->sendMessage($chat_id, $content_admin);

                // формирую сообщение для пользователя
                // разделяю количество бонусов пакета оптовых стирок по разрядам точками
                $bonus_count = number_format($wholesale_laundry["bonus_count"], 0, "", ".");
                $user_content = "🍾 Поздравляем!\n";
                $user_content .= "Вы успешно купили абонемент на {$wholesale_laundry["weight"]} кг.\n";
                $user_content .= "На ваш баланс зачислено $bonus_count IDR.\n\n";

                $user_content .= "🧮 Сейчас на вашем балансе итого $balance IDR.\n\n";

                $user_content .= "В дальнейшем при оплате стирок выбирайте  кнопку Оплатить бонусами. Сумма заказа будет списываться с вашего бонусного счета.";

                // отправляю сообщение пользователю
                $this->sendMessage($user["chat_id"], $user_content);
            }
        }
    }

    // удаление заказа
    if ($atext[0] == "/del_order") {
        $this->DelMessageText($chat_id, $message_id);

        $template = new Template("admin/del_order/del_order");
        $template = $template->Load();
        $template->LoadButtons();
        $response = $this->sendMessage($chat_id, $template->text, $template->buttons);
        if ($response["ok"]) {
            $this->set_action($chat_id, "del_order&{$response["result"]["message_id"]}");
        }
        return;
    }

    // подтверждение удаления заказа
    if ($atext[0] == "/del_order_success" && $atext[1]) {
        // удаляю предыдущее сообщение
        $this->DelMessageText($chat_id, $message_id);

        $orderId = (int)$atext[1];

        $order = R::findOne("orders", "id = $orderId");
        if (!$order) {
            $this->sendMessage($chat_id, "Ошибка: заказа с таким id($orderId) не существует!");
            return;
        }

        $templateAdmin = new Template("admin/del_order/success_admin", null, [
            new TemplateData(":orderId", $orderId),
        ]);

        $templateAdmin = $templateAdmin->Load();

        $this->sendMessage($chat_id, $templateAdmin->text);

        $templateUser = new Template("admin/del_order/success_user", null, [
            new TemplateData(":orderId", $orderId),
        ]);

        $templateUser = $templateUser->Load();
        $templateUser->LoadButtons();

        $this->sendMessage($order["chat_id"], $templateUser->text, $templateUser->buttons);

        R::trash("orders", $orderId);
        return;
    }

    // отказ оз удаления заказа
    if ($atext[0] == "/del_order_deny" && $atext[1]) {
        $this->DelMessageText($chat_id, $message_id);

        switch ($atext[1]) {
            case 1:
                $this->del_action($chat_id);

                $template = new Template("admin/del_order/deny");
                $template = $template->Load();


                $this->sendMessage($chat_id, $template->text);
                return;
            case 2:
                $template = new Template("admin/del_order/del_order");
                $template = $template->Load();
                $template->LoadButtons();

                $response = $this->sendMessage($chat_id, $template->text, $template->buttons);
                if ($response["ok"]) {
                    $this->set_action($chat_id, "del_order&{$response["result"]["message_id"]}");
                }
                return;
        }

        return;
    }

    if ($get_action[0] == "del_order" && $atext[0]) {
        $orderId = (int)$atext[0];
        $order = R::findOne("orders", "id = $orderId");
        if (!$order) {
            $this->sendMessage($chat_id, "Ошибка: заказа с таким id($orderId) не существует!");
            return;
        }

        $template = new Template("admin/del_order/confirmation", null, [
            new TemplateData(":orderId", $orderId),
        ]);

        $template = $template->Load();
        $template->LoadButtons();

        $this->sendMessage($chat_id, $template->text, $template->buttons);

        $this->del_action($chat_id);

        $this->DelMessageText($chat_id, $get_action[1]);
        $this->DelMessageText($chat_id, $message_id);
        return;
    }

    if ($atext[0] == "/1") {
        $template = new Template("admin/help/help");
        $template = $template->Load();
        $template->LoadButtons();

        $this->sendMessage($chat_id, $template->text, $template->buttons);
        return;
    }
}
?>