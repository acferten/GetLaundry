<?php


# Реферальная ссылка
if (preg_match("~^\/start (join[\d]+|ref[\d]+)$~", $text, $matches)) {
    $command = preg_replace('/[^a-z]/', '', $matches[1]);
    file_put_contents("test1", $command);
    if ($command == "ref") {

        $un_text = substr($matches[1], 3);
        $id_ref = $un_text;

        if ($id_ref == $chat_id) {

        } else {

            # Проверяем регистрировавывались уже
            $get_user_ref = R::findOne('referal', 'chat_id = :chat_id AND ref_id_user = :ref_id_user', [':chat_id' => $chat_id, ':ref_id_user' => $id_ref]);
            if (!$get_user_ref) {


                $r_user = R::findOne('users', 'chat_id = :chat_id', [':chat_id' => $chat_id]);

                # Если мы есть в базе, то прекращаем действия
                if ($r_user) {

                } else {

                    $info_user = R::findOne('users', 'chat_id = :chat_id', [':chat_id' => $id_ref]);
                    $info_user_nik = $info_user->first_name;

                    # Если есть в базе кто пригласил, если нет, то прекращаем действия
                    if (!$info_user) {

                    } else {

                        //реферальная ссылка
                        $save = R::dispense('referal');
                        $save->chat_id = $chat_id; // ид кто зарегистрировался
                        $save->ref_id_user = $id_ref; // ид кто пригласил
                        $save->nik = $info_user_nik; // ид кто пригласил
                        $save->status = 0; // ид кто пригласил
                        // Сохраняем объект
                        R::store($save);

                    }
                }
            }
        }
    }
}

if ($atext[0] == "/franshiza") {
    $user = R::findOne('users', 'chat_id = ?', [$chat_id]);
    $template = new Template("franshiza", $user['lang']);
    $template = $template->Load();
    $template->LoadButtons();

    $this->sendMessage($chat_id, $template->text, $template->buttons);
    return;
}

if ($atext[0] == "/start wholesalelaundries") {
    // получаю список пакетов оптовых стирок
    $wholesaleLaundries = R::findAll('wholesale_laundry');

    // формирую сообщение пользователю
    $content = "💣 Мы подготовили выгодное предложение для всех, кто чаще хочет заботиться о чистоте своих вещей. Покупая абонемент, вы получаете скидку до 20%. Абонемент действует целый год c момента покупки.\n\n";
    foreach ($wholesaleLaundries as $wholesaleLaundry) {
        $icon = "";
        switch ($wholesaleLaundry["weight"]) {
            case 20:
                $icon = "🥉";
                break;
            case 40:
                $icon = "🥈";
                break;
            case 60:
                $icon = "🥇";
                break;
        }

        $content .= "<i>$icon {$wholesaleLaundry["name"]}</i>\n";

        // разделяю цену точками по разрядам
        $price_idr = number_format($wholesaleLaundry["price_idr"], 0, "", ".");
        $content .= "<b>Цена $price_idr IDR</b>\n";

        // разделяю количество бонусов точками по разрядам
        $bonus_count = number_format($wholesaleLaundry["bonus_count"], 0, "", ".");
        $content .= "Вы получаете на баланс $bonus_count IDR\n";

        // считаю количество IDR в зависимости от получаемых бонусов и процента экономии и разделяю точками по разрядам
        $saving_count = number_format($wholesaleLaundry["bonus_count"] * $wholesaleLaundry["saving_percent"] / 100, 0, "", ".");;
        $content .= "Экономите {$wholesaleLaundry["saving_percent"]}% или $saving_count\n";

        // разделяю цену за киллограм точками по разрядам
        $price_per_kg = number_format($wholesaleLaundry["price_per_kg"], 0, "", ".");
        $content .= "Цена за 1кг будет $price_per_kg\n\n";

        // добавляю кнопку покупки пакета оптовых стирок
        $buttons[] = [
            $this->buildInlineKeyBoardButton($icon . " " . $wholesaleLaundry["buy_button_text"], "/wholesale_laundry_select_pay_type {$wholesaleLaundry["id"]}")
        ];
    }

    // отправляю сообщение пользователю
    $this->sendMessage($chat_id, $content, $buttons);
}
if ($atext[0] == '/start') {

    # Кол-во нажатий метрика
    $this->set_metrika($chat_id, 1);

    // если это кнопка нажата в рассылке
    if ($atext[2] == "mailing") {
        $this->DelMessageText($chat_id, $message_id);
    }

    if ($atext[1] == '1') {
        # Кол-во нажатий метрика
        $this->set_metrika($chat_id, 5);
    }

    # Регистрируем пользователя в бд
    regusers($chat_id, $first_name, $last_name, $username);

    $user = R::findOne('users', 'chat_id = ?', [$chat_id]);
    $user->lang = 'ru';
    R::store($user);
    $template = new Template("start", $user['lang']);

    $template->Load();
    $template->LoadButtons();

    $this->sendMessage($chat_id, $template->text, $template->buttons);

    return;
}

if ($atext[0] == '/user_lang' && $atext[1]) {
    $user = R::findOne('users', 'chat_id = ?', [$chat_id]);
    $user->lang = $atext[1];
    R::store($user);

    $this->DelMessageText($chat_id, $message_id);

    $lang = $user['lang'];

    $template = new Template("start", $lang);
    $template = $template->Load();
    $template->LoadButtons();

    $this->sendMessage($chat_id, $template->text, $template->buttons);

    return;
}


# nachalo
if ($atext[0] == '/nachalo') {

    #############
    $time = strtotime(date("d.m.Y H:i")); # перевод время в UNIX

    $users = R::findOne('users', 'chat_id = ?', [$chat_id]);
    $users->time_nachalo = $time + 600;
    R::store($users);
    #############

    # Кол-во нажатий метрика
    $this->set_metrika($chat_id, 2);

    switch ($users['lang']) {
        case 'ru':
            $buttons[] = [
                $this->buildKeyboardButton("📍Отправить геолокацию"),
            ];
            break;
        case 'eng':
            $buttons[] = [
                $this->buildKeyboardButton("📍Send geolocation"),
            ];
    }

    $template = new Template("order/step_1_in_5", $users['lang']);
    $template->Load();

    # Записываем команду
    $this->set_action($chat_id, "address");

    $this->sendMessage($chat_id, $template->text, $buttons, 0);

    return;
}

# Записываем
if ($atext[0] == '/orders_users_adress_2' || $atext[0] && $get_action[0] == 'whatsapp' || $atext[0] && $get_action[0] == 'address_2' || $atext[0] && $get_action[0] == 'phone') {

    if ($get_action[0] == 'address_2') {
        $orders = R::findOne('orders', "id = $get_action[1]");
        $orders->address_2 = $text;
        $orders->timestamp_create = time();
        R::store($orders);
    }

    # WhatsApp
    if ($get_action[0] == 'whatsapp') {
        $set_users = R::findOne('users', "chat_id = $chat_id");
        $set_users->whatsapp = $text;
        R::store($set_users);
    }

    # Выводим свои данные пользователя
    $users = R::findOne('users', "chat_id = $chat_id");

    if (!trim($users['phone'])) {

        switch ($users['lang']) {
            case 'ru':
                $buttons[] = [
                    $this->buildKeyboardButton("☎️ Отправить номер", true, false),
                ];
                break;
            case 'eng':
                $buttons[] = [
                    $this->buildKeyboardButton("☎️ Send number", true, false),
                ];
        }

        $template = new Template("order/step_3_in_5", $users['lang']);
        $template = $template->Load();

        $this->sendMessage($chat_id, $template->text, $buttons, 0);

        # Записываем команду
        $this->set_action($chat_id, "phone&$get_action[1]");
    } else if (!trim($users['whatsapp'])) {

        $buttons12[] = [
            $this->buildInlineKeyBoardButton("/", "/"),
        ];
        # Отправка смс

        switch ($users['lang']) {
            case 'ru':
                $send = $this->sendMessage($chat_id, "Ваше сообщение", $buttons12, 2);
                break;
            case 'eng':
                $send = $this->sendMessage($chat_id, "Your message", $buttons12, 2);
        }

        $mess = $send['result']['message_id'];

        # Удаляем последнее сообщение
        $this->DelMessageText($chat_id, $mess);

        $template = new Template("order/step_4_in_5", $users['lang']);
        $template = $template->Load();

        $this->sendMessage($chat_id, $template->text);

        # Записываем команду
        $this->set_action($chat_id, "whatsapp&$get_action[1]");

    } else {
        $template = new Template("order/step_5_in_5", $users['lang'], [
            new TemplateData(":getAction", $get_action[1])
        ]);

        $template = $template->Load();
        $template->LoadButtons();

        $this->sendMessage($chat_id, $template->text, $template->buttons);

        # Записываем команду
        $this->del_action($chat_id);
    }

    return;
}

# Отменить заказ
if ($atext[0] == '/cancel_orders') {
    $user = R::findOne('users', 'chat_id = ?', [$chat_id]);

    $template = new Template("order/cancel", $user['lang'], [
        new TemplateData(":orderId", $atext[1]),
    ]);
    $template = $template->Load();
    $template->LoadButtons();

    $this->editMessageText($chat_id, $message_id, $template->text, $template->buttons);

    return;
}

# Заказ главная
if ($atext[0] == '/back_orders' || $text == '⬅️  Вернуться в заказ.') {

    if ($atext[2] == 'success') {
        $users = R::findOne('users', 'chat_id = ?', [$chat_id]);
        $users["time_nachalo"] = 0;
        $users["orders_count"] += 1;
        R::store($users);

        $order = R::findOne('orders', 'id = ?', [$atext[1]]);
        $order->status = 1;
        R::store($order);

        # Отправка заявки в чат админа
        //$this->sendOrdersAdmin(ID_CHAT, $atext[1], $username);
        $this->sendOrdersAdmin(GROUP_COURIER_CHAT_ID, $atext[1], $username);

        //$this->sendOrderCourierGroup($atext[1], $username, 0);

//        sleep(1);
//        $this->sendOrderCourier($atext[1]);
    }

    # Вернуться в заказ
    if ($text == '⬅️  Вернуться в заказ.') {

        $this->DelMessageText($chat_id, $message_id);
        $this->DelMessageText($chat_id, $message_id - 1);

        $orders = R::findOne('orders', "id = $get_action[1]");
    } else {
        $orders = R::findOne('orders', "id = $atext[1]");
    }
    $user = R::findOne('users', 'chat_id = ?', [$chat_id]);
    $template = new Template("order_is_accepted", $user['lang'], [
        new TemplateData(":orderId", $orders["id"]),
    ]);

    $template = $template->Load();
    $template->LoadButtons();

    # Вернуться в заказ
    if ($text == '⬅️  Вернуться в заказ.') {
        $this->sendMessage($chat_id, $template->text, $template->buttons);

        # Отправка смс
        $send = $this->sendMessage($chat_id, "Ваше сообщение", $buttons, 2);
        $mess = $send['result']['message_id'];

        # Удаляем последнее сообщение
        $this->DelMessageText($chat_id, $mess);
    } else {
        $this->editMessageText($chat_id, $message_id, $template->text, $template->buttons);
    }

    $this->del_action($chat_id);

    return;
}

# Особые пожелания по стирке1
if ($atext[0] == '/osob_po1') {

    $this->DelMessageText($chat_id, $message_id);

    $buttons[] = [
        $this->buildInlineKeyBoardButton("⬅️  Вернуться в заказ."),
    ];

    $content = "Оставьте ваши пожелания по стирке конкретных вещей.
 
Прямо здесь: напишите текстом, отправьте голосовое, снимите фото проблемных мест на вещах или запишите видео. 
Мы обязательно учтём ваши пожелания и постараемся сделать вещи счастливее.";

    $this->sendMessage($chat_id, $content, $buttons, 0);

    # Записываем команду
    $this->set_action($chat_id, "photo&$atext[1]");

    return;
}

# отмена заказа
if ($atext[0] == '/cancel_orders_pochemy') {

    $this->DelMessageText($chat_id, $message_id);

    $orders = R::findOne('orders', "chat_id = $chat_id ORDER BY id DESC");
    $orders->status = 0;
    $orders->title_cancel = $atext[1];
    $user = R::findOne('users', 'chat_id = ?', [$chat_id]);
    R::store($orders);

    $this->DelMessageText(ID_CHAT, $orders['mess_id']);
    $this->DelMessageText(GROUP_COURIER_CHAT_ID, $orders['mess_id']);

    $this->sendOrdersAdmin(ID_CHAT, $orders['id'], $username);

    if ($user['lang'] == 'ru') {
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Заказать стирку", "/start 1"),
        ];
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Получить бесплатную стирку", "/set_free_orders /cancel_orders_pochemy 1"),
        ];

        $content = "😔Мы отменили ваш заказ и уже тоскуем!

Вы можете заказать стирку в любое время.
Наши курьеры и администраторы работают каждый день с 09:00 до 20:00.

❗️Заказ, сделанный <b>до 14:00</b>, заберём в течение 2-х часов и вернём обратно завтра днём.
❗️Заказ, сделанный <b>после 14:00</b>, заберём сегодня вечером и вернём обратно завтра вечером.
❗️Заказ, сделанный <b>после 18:00</b>, заберём завтра до обеда и вернём обратно послезавтра днём.";
    } else {
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Order laundry", "/start 1"),
        ];
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Get free laundry", "/set_free_orders /cancel_orders_pochemy 1"),
        ];

        $content = "😔We have canceled your order and are already missing you!

You can order laundry at any time.
Our couriers and administrators work from 09:00 to 20:00.

❗️Orders placed <b>before 14:00</b> will be picked up within 2 hours and returned back tomorrow afternoon.
❗️Orders placed <b>after 14:00</b> will be picked up tonight and returned back tomorrow evening.
❗️Orders placed <b>after 18:00</b> will be picked up before lunch tomorrow and returned back the day after tomorrow.";

    }
    $this->sendMessage($chat_id, $content, $buttons);

    return;
}

# Получить бесплатную стирку
if ($atext[0] == '/set_free_orders') {

    $this->set_metrika($chat_id, 7);

    /*$buttons[] = [
        $this->buildInlineKeyBoardButton("Пригласить друга", " ", "https://t.me/share/url?url=https://t.me/LaundryGo_bot?start=ref$chat_id"),
    ];

    $content = "<b>Вы можете приглашать друзей и получать за это килограммы бесплатных стирок или живые деньги 🤝.</b>

1.	Отправляйте ссылку друзьям и знакомым.
2.	КАЖДЫЙ РАЗ, когда ваши друзья будут стирать вещи у нас, вы будете получать кэшбэк 10% с суммы их заказа.
3.	Количество, приглашённых друзей не ограничено.
4.	Свой баланс бонусных IDR всегда можно посмотреть в разделе «Меню» (синяя кнопка в нижнем левом углу экрана) - 💰 Баланс бонусов
5.	В любой момент вы можете оплатить стирку Бонусными IDR как частично, так и полностью.
6.  Когда на вашем балансе больше 300.000, вы можете вывести эти деньги себе на карту. О том как это сделать, просто напишите в чат менеджеру.

Отправив ссылку однажды, вы сможете обеспечить себя бесплатными стирками или получать пассивный доход.

Вот ваша уникальная ссылка 👇
 https://t.me/LaundryGo_bot?start=ref$chat_id";

    $buttons[] = [
        $this->buildInlineKeyBoardButton("⬅️ Назад", "$atext[1] $atext[2] $atext[3]"),
    ];

    $this->editMessageText($chat_id, $message_id, $content, $buttons);

    return;*/

    // формирую сообщение пользователю
    // объявляю шаблон
    $user = R::findOne('users', 'chat_id = ?', [$chat_id]);
    $template = new Template("referal", $user['lang'], [
        new TemplateData(":chatId", $chat_id)
    ]);

    // загружаю шаблон
    $template = $template->Load();

    // загружаю кнопки шаблона
    $template->LoadButtons();
//    $buttons = $template->buttons;
    /*foreach ($buttons as $key => $button) {
        $buttons[$key] = $button->PrepareToSend();
    }*/

//    $buttons[] = [
//        $this->buildInlineKeyBoardButton("⬅️ Назад", "$atext[1] $atext[2] $atext[3]"),
//    ];

    // генерирую qr код, если его нет в папке
    if (!file_exists(__DIR__ . "/../img/qr/$chat_id.png")) {
        QRcode::png("https://t.me/LaundryGo_bot?start=ref$chat_id", __DIR__ . "/../img/qr/$chat_id.png", "M", 10, 2);

        $im = imagecreatefrompng(__DIR__ . "/../img/qr/$chat_id.png");
        $width = imagesx($im);
        $height = imagesy($im);

        // изменяю цвет пикселей qr кода
        $fg_color = imageColorAllocate($im, 0, 101, 209);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $color = imagecolorat($im, $x, $y);
                if ($color == 1) {
                    imageSetPixel($im, $x, $y, $fg_color);
                }
            }
        }

        // выставляю логотип для qr кода
        $dst = imagecreatetruecolor($width, $height);
        imagecopy($dst, $im, 0, 0, 0, 0, $width, $height);
        imagedestroy($im);

        $logo = imagecreatefrompng(__DIR__ . "/../assets/img/laundry_logo.png");
        $logo_width = imagesx($logo);
        $logo_height = imagesy($logo);

        $new_width = $width / 3;
        $new_height = $logo_height / ($logo_width / $new_width);

        $x = ceil(($width - $new_width) / 2);
        $y = ceil(($height - $new_height) / 2);

        imagecopyresampled($dst, $logo, $x, $y, 0, 0, $new_width, $new_height, $logo_width, $logo_height);

        // сохраняю изменённый qr код
        imagepng($dst, __DIR__ . "/../img/qr/$chat_id.png");
    }

    // отправляю сообщение пользователю
//    $this->sendPhoto($chat_id, "https://laundrybot.online/bot/img/qr/$chat_id.png?version=" . time(), $template->text, $buttons);
    $this->sendPhoto($chat_id, "https://laundrybot.online/bot/img/qr/$chat_id.png?version=" . time(), $template->text, $template->buttons);
    return;
}

# Особые пожелания по стирке
if ($atext[0] == '/osob_po') {


    $this->DelMessageText($chat_id, $message_id);

    $buttons[] = [
        $this->buildInlineKeyBoardButton("⬅️ Вернуться в заказ"),
    ];

    $content = "Оставьте ваши пожелания по стирке конкретных вещей. 
Пришлите текст, голосовое, фото или видео. 
Дайте нам знать о проблемных местах на вещах и мы постараемся сделать ваши вещи счастливее.";

    $this->sendMessage($chat_id, $content, $buttons, 0);

    # Записываем команду
    $this->set_action($chat_id, "photo&$atext[1]");

    return;
}


# Курьер забрал заказ
// TODO: как закончится задача с чатом курьеров, убрать!
if ($atext[0] == '/orders_ok_kurer') {
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
                    "type" => "pickup_order",
                    "order_id" => substr_replace($atext[1], "", 0, 2),
                    "timestamp_start" => time(),
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

    $this->get_orders($atext[1], "status", 2);


    $orders = R::findOne('orders', "id = $atext[1]");

    if ($atext[2] != 1) {

        $this->DelMessageText($chat_id, $message_id);

        $time = time();
        $time1 = time() + 10800;
        $time3 = date("H:i", time());

        $this->get_orders($atext[1], "time_start", $time);

        $user = R::findOne('users', 'chat_id = ?', [$chat_id]);
        $template = new Template("pickup_order", $user['lang'], [
            new TemplateData(":time", $time3),
            new TemplateData(":orderId", $atext[1]),
        ]);

        $template = $template->Load();
        $template->LoadButtons();

        $this->sendMessage($orders['chat_id'], $template->text, $template->buttons);

        $content_admin = "<b>ℹ️ Заказ: #$atext[1]</b>
			
	Курьер забрал вещи в $time";

        $this->sendOrdersAdmin(GROUP_COURIER_CHAT_ID, $atext[1], $username);
    } else {
        $user = R::findOne('users', 'chat_id = ?', [$chat_id]);
        $template = new Template("pickup_order", $user['lang'], [
            new TemplateData(":time", $orders["time_start"]),
            new TemplateData(":orderId", $atext[1]),
        ]);

        $template = $template->Load();
        $template->LoadButtons();

        $this->editMessageText($orders['chat_id'], $message_id, $template->text, $template->buttons);
    }

    return;
}


# Вернуться в заказ
if ($text == '⬅️ Вернуться в заказ' || $atext[0] == '/back_v') {

    if ($atext[1] == 1) {
        $this->DelMessageText($chat_id, $message_id);
        $orders = R::findOne('orders', "id = $atext[2]");
        $get_action[1] = $atext[2];
    } else {

        $this->DelMessageText($chat_id, $message_id);
        $this->DelMessageText($chat_id, $message_id - 1);

        $orders = R::findOne('orders', "id = $get_action[1]");
    }


    /*$buttons[] = [
        $this->buildInlineKeyBoardButton("Особые пожелания по стирке", " ", "https://t.me/LaundryGoBot"),
    ];
    $buttons[] = [
        $this->buildInlineKeyBoardButton("Получить бесплатную стирку", "/set_free_orders /back_v 1 $get_action[1]"),
    ];
    $buttons[] = [
        $this->buildInlineKeyBoardButton("Заказать стирку", "/start 1"),
    ];


    $content = "😃 Курьер успешно забрал ваши вещи для стирки в $orders[time_start]

Как только вещи будут готовы, бот отправит вам информацию о весе и стоимости заказа.

Затем вы сможете выбрать удобный способ оплаты:
📎наличными курьеру в рупиях.
📎перевод на карту индонезийского банка в рупиях.
📎перевод на карту Тинькофф в рублях.
📎оплата бонусами.

";*/
    $user = R::findOne('users', 'chat_id = ?', [$chat_id]);
    $template = new Template("pickup_order", $user['lang'], [
        new TemplateData(":time", $orders["time_start"]),
        new TemplateData(":orderId", $atext[1]),
    ]);

    $template = $template->Load();
    $template->LoadButtons();
    /*foreach ($template->buttons as $key => $button) {
        $template->buttons[$key] = $button->PrepareToSend();
    }*/

//    $this->sendMessage($chat_id, $content, $buttons);
    $this->sendMessage($chat_id, $template->text, $template->buttons);

    # Отправка смс
//    $send = $this->sendMessage($chat_id, "Ваше сообщение", $buttons, 2);
    $send = $this->sendMessage($chat_id, "Ваше сообщение", $template->buttons, 2);
    $mess = $send['result']['message_id'];

    # Удаляем последнее сообщение
    $this->DelMessageText($chat_id, $mess);

    $this->del_action($chat_id);

    return;
}


# О НАС
if ($atext[0] == '/onas') {
    $user = R::findOne('users', 'chat_id = ?', [$chat_id]);
    $template = new Template("about_us", $user['lang']);
    $template = $template->Load();
    $template->LoadButtons();

    $this->sendMessage($chat_id, $template->text, $template->buttons);

    return;
}


// Информация по оптовым стиркм
if ($atext[0] == '/wholesale_laundries') {
    // если это кнопка нажата в рассылке
    if ($atext[1] != "mailing") {
        $this->DelMessageText($chat_id, $message_id);
    }

    // получаю список пакетов оптовых стирок
    $wholesaleLaundries = R::findAll('wholesale_laundry');

    // формирую сообщение пользователю
    $content = "💣 Мы подготовили выгодное предложение для всех, кто чаще хочет заботиться о чистоте своих вещей. Покупая абонемент, вы получаете скидку до 20%. Абонемент действует целый год c момента покупки.\n\n";
    foreach ($wholesaleLaundries as $wholesaleLaundry) {
        $icon = "";
        switch ($wholesaleLaundry["weight"]) {
            case 20:
                $icon = "🥉";
                break;
            case 40:
                $icon = "🥈";
                break;
            case 60:
                $icon = "🥇";
                break;
        }

        $content .= "<i>$icon {$wholesaleLaundry["name"]}</i>\n";

        // разделяю цену точками по разрядам
        $price_idr = number_format($wholesaleLaundry["price_idr"], 0, "", ".");
        $content .= "<b>Цена $price_idr IDR</b>\n";

        // разделяю количество бонусов точками по разрядам
        $bonus_count = number_format($wholesaleLaundry["bonus_count"], 0, "", ".");
        $content .= "Вы получаете на баланс $bonus_count IDR\n";

        // считаю количество IDR в зависимости от получаемых бонусов и процента экономии и разделяю точками по разрядам
        $saving_count = number_format($wholesaleLaundry["bonus_count"] * $wholesaleLaundry["saving_percent"] / 100, 0, "", ".");;
        $content .= "Экономите {$wholesaleLaundry["saving_percent"]}% или $saving_count\n";

        // разделяю цену за киллограм точками по разрядам
        $price_per_kg = number_format($wholesaleLaundry["price_per_kg"], 0, "", ".");
        $content .= "Цена за 1кг будет $price_per_kg\n\n";

        // добавляю кнопку покупки пакета оптовых стирок
        $buttons[] = [
            $this->buildInlineKeyBoardButton($icon . " " . $wholesaleLaundry["buy_button_text"], "/wholesale_laundry_select_pay_type {$wholesaleLaundry["id"]}")
        ];
    }

    // удаляю предыдущее сообщение
//    $this->DelMessageText($chat_id, $message_id);

    // отправляю сообщение пользователю
    $this->sendMessage($chat_id, $content, $buttons);

    return;
}


// Выбор способа оплаты одной из оптовых стирок
if ($atext[0] == "/wholesale_laundry_select_pay_type" && $atext[1]) {
    // получаю пакет оптовых стирок по id
    $wholesale_laundry = R::findOne('wholesale_laundry', "id = {$atext[1]}");

    // формирую сообщение пользователю
    $content = "<i>{$wholesale_laundry["name"]}.</i>\n\n";

    // разделяю цену точками по разрядам
    $price_idr = number_format($wholesale_laundry["price_idr"], 0, "", ".");
    $content .= "<b>Цена $price_idr IDR</b>\n\n";

    // разделяю количество бонусов точками по разрядам
    $bonus_count = number_format($wholesale_laundry["bonus_count"], 0, "", ".");
    $content .= "Вы получаете на баланс $bonus_count IDR\n";

    // считаю количество IDR в зависимости от получаемых бонусов и процента экономии и разделяю точками по разрядам
    $saving_count = number_format($wholesale_laundry["bonus_count"] * $wholesale_laundry["saving_percent"] / 100, 0, "", ".");;
    $content .= "Экономите {$wholesale_laundry["saving_percent"]}% или $saving_count IDR\n";

    // разделяю цену за киллограм точками по разрядам
    $price_per_kg = number_format($wholesale_laundry["price_per_kg"], 0, "", ".");
    $content .= "Цена за 1 кг будет $price_per_kg IDR\n\n";

    $content .= "Как вам удобно сделать оплату?";

    // формирую кнопки для сообщения пользователю
    $buttons = [
        [
            $this->buildInlineKeyBoardButton("Перевод на карту индонезийского банка", "/buy_wholesale_laundry 1 {$wholesale_laundry["id"]}")
        ],
        [
            $this->buildInlineKeyBoardButton("Перевод на Тинькофф", "/buy_wholesale_laundry 2 {$wholesale_laundry["id"]}")
        ],
        [
            $this->buildInlineKeyBoardButton("Вернуться назад", "/wholesale_laundries")
        ]
    ];

    // удаляю предыдущее сообщение
    $this->DelMessageText($chat_id, $message_id);

    // отправляю сообщение пользователю
    $this->sendMessage($chat_id, $content, $buttons);

    return;
}

// Данные для выбранного способа оплаты одной из оптовых стирок
if ($atext[0] == "/buy_wholesale_laundry" && $atext[1] && $atext[2]) {
    $pay_type = $atext[1];
    $wholesale_laundry_id = $atext[2];

    // получаю пользователя по chat_id
    $user = R::findOne("users", "chat_id = $chat_id");

    // получаю пакет оптовых стирок по id
    $wholesale_laundry = R::findOne('wholesale_laundry', "id = $wholesale_laundry_id");

    // формирую сообщение пользователю
    switch ($pay_type) {
        case 1: // Перевод на BRI bank
            $content = "💸 Вот данные для перевода на карту индонезийского банка BRI в рупиях.\n";
            $content .= "После того как переведёте, пришлите, пожалуйста, чек об оплате в чат менеджеру. Как только мы получим деньги, сразу активируем абонемент и деньги зачислятся на ваш бонусный счёт.\n\n";
            $content .= "462 801 004 036 508\n";
            $content .= "Anak Agung Gede Adi Semara\n\n";

            // Разделяю цену точками по разрядам
            $price_idr = number_format($wholesale_laundry["price_idr"], 0, "", ".");

            $content .= "<b>Сумма для перевода $price_idr IDR.</b>";
            break;
        case 2: // Перевод на Тинькофф
            $content = "💸 Вот данные для перевода на карту Тинькофф в рублях.\n";
            $content .= "После того как переведёте, пришлите, пожалуйста, чек об оплате в чат менеджеру. Как только мы получим деньги, сразу активируем абонемент и деньги зачислятся на ваш бонусный счёт.\n\n";
            $content .= "2200 7007 7932 1818\n";
            $content .= "Olga G.\n\n";

            // Перевожу цену в рубли и разделяю точками по разрядам
            $price_idr = number_format($wholesale_laundry["price_idr"] / 1000 * 6.2, 0, "", ".");

            $content .= "<b>Сумма для перевода $price_idr рублей.</b>";
            break;
    }

    // формирую кнопки для сообщения пользователю
    $buttons = [
        /*[
            $this->buildInlineKeyBoardButton("Подтвердить выбор", "/wholesale_laundry_pay_type_selected $wholesale_laundry_id $pay_type")
        ],*/
        [
            $this->buildInlineKeyBoardButton("Отправить чек менеджеру", " ", "https://t.me/LaundryGoBot"),
        ],
        [
            $this->buildInlineKeyBoardButton("Вернуться назад", "/wholesale_laundry_select_pay_type $wholesale_laundry_id")
        ],
    ];

    // удаляю предыдущее сообщение
    $this->DelMessageText($chat_id, $message_id);

    // отправляю сообщение пользователю
    $this->sendMessage($chat_id, $content, $buttons);


    $log_wholesale_laundry_payment = R::findOne('logwholesalelaundrypayment', "user_id = {$user["id"]} AND wholesale_laundry_id = $wholesale_laundry_id");
    if ($log_wholesale_laundry_payment) {
        $log_wholesale_laundry_payment->pay_type = $pay_type;
        $send = $this->DelMessageText(ID_CHAT, $log_wholesale_laundry_payment["message_id"]);
        R::store($log_wholesale_laundry_payment);
    } else {
        // формирую оплату пакета оптовых стирок пользователем и сохраняю в базе данных
        $log_wholesale_laundry_payment = R::dispense('logwholesalelaundrypayment');

        $log_wholesale_laundry_payment->user_id = $user["id"];
        $log_wholesale_laundry_payment->wholesale_laundry_id = $wholesale_laundry_id;
        $log_wholesale_laundry_payment->pay_type = $pay_type;
        $log_wholesale_laundry_payment->timestamp = time();

        R::store($log_wholesale_laundry_payment);
    }

    $pay_type_content_admin = "";
    switch ($pay_type) {
        case 1:
            $pay_type_content_admin = "transfer to bank BRI card.";
            break;
        case 2:
            $pay_type_content_admin = "transfer to Tinkoff card";
            break;
    }

    $timestamp_date = date("d.m.Y", $log_wholesale_laundry_payment["timestamp"]);
    $timestamp_time = date("H:i", $log_wholesale_laundry_payment["timestamp"]);

    $bonus_count = number_format($wholesale_laundry["bonus_count"], 0, "", ".");

    $content_admin = "User id: <b>{$user["id"]}</b>\n";
    $content_admin .= "<b>Created: $timestamp_date $timestamp_time</b>\n";
    $content_admin .= "User login: <b>@{$user["username"]}</b>\n";
    $content_admin .= "Number: <b>{$user["phone"]}</b>\n";
    $content_admin .= "Whatsapp: <b>{$user["whatsapp"]}</b>\n";
    $content_admin .= "Number of kilograms: <b>{$wholesale_laundry["weight"]} kg\n</b>";
    $content_admin .= "<b>Payment amount: $bonus_count IDR\n</b>";
    $content_admin .= "Pay type: <b>$pay_type_content_admin</b>";

    $buttons_admin = [
        [
            $this->buildInlineKeyBoardButton("Top up balance", "/wholesale_payment {$user["id"]} {$log_wholesale_laundry_payment["id"]}"),
        ],
    ];
    $buttons_admin[] = [
        $this->buildInlineKeyBoardButton("Back", "/order_report_back"),
    ];

    $send = $this->sendMessage(ID_CHAT, $content_admin, $buttons_admin);
    $message_id = $send['result']['message_id'];
    $log_wholesale_laundry_payment["message_id"] = $message_id;
    R::store($log_wholesale_laundry_payment);
    return;
}

# contacst
if ($atext[0] == '/contacst') {
    $user = R::findOne('users', 'chat_id = ?', [$chat_id]);
    $template = new Template("contacts", $user['lang']);
    $template = $template->Load();
    $template->LoadButtons();

    $this->sendMessage($chat_id, $template->text, $template->buttons);

    return;
}

# Получить бесплатную стирку
if ($atext[0] == '/set_free_orders_menu') {

    # Кол-во нажатий метрика
    $this->set_metrika($chat_id, 7);

    // формирую сообщение пользователю
    // объявляю шаблон
    $user = R::findOne('users', 'chat_id = ?', [$chat_id]);
    $template = new Template("referal", $user['lang'], [
        new TemplateData(":chatId", $chat_id)
    ]);

    // загружаю шаблон
    $template = $template->Load();

    // загружаю кнопки шаблона
    $template->LoadButtons();

    // генерирую qr код, если его нет в папке
    if (!file_exists(__DIR__ . "/../img/qr/$chat_id.png")) {
        QRcode::png("https://t.me/LaundryGo_bot?start=ref$chat_id", __DIR__ . "/../img/qr/$chat_id.png", "M", 10, 2);

        $im = imagecreatefrompng(__DIR__ . "/../img/qr/$chat_id.png");
        $width = imagesx($im);
        $height = imagesy($im);

        // изменяю цвет пикселей qr кода
        $fg_color = imageColorAllocate($im, 0, 101, 209);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $color = imagecolorat($im, $x, $y);
                if ($color == 1) {
                    imageSetPixel($im, $x, $y, $fg_color);
                }
            }
        }

        // выставляю логотип для qr кода
        $dst = imagecreatetruecolor($width, $height);
        imagecopy($dst, $im, 0, 0, 0, 0, $width, $height);
        imagedestroy($im);

        $logo = imagecreatefrompng(__DIR__ . "/../assets/img/laundry_logo.png");
        $logo_width = imagesx($logo);
        $logo_height = imagesy($logo);

        $new_width = $width / 3;
        $new_height = $logo_height / ($logo_width / $new_width);

        $x = ceil(($width - $new_width) / 2);
        $y = ceil(($height - $new_height) / 2);

        imagecopyresampled($dst, $logo, $x, $y, 0, 0, $new_width, $new_height, $logo_width, $logo_height);

        // сохраняю изменённый qr код
        imagepng($dst, __DIR__ . "/../img/qr/$chat_id.png");
    }

    // отправляю сообщение пользователю
//    $this->sendPhoto($chat_id, "https://laundrybot.online/bot/img/qr/$chat_id.png?version=" . time(), $template->text, $buttons);
    $this->sendPhoto($chat_id, "https://laundrybot.online/bot/img/qr/$chat_id.png?version=" . time(), $template->text, $template->buttons);
    return;
}

#  cancel_osob_end
if ($atext[0] == '/cancel_osob_end' && $atext[1]) {

    if ($chat_id == GROUP_WASHERS_CHAT_ID) {
        $order = R::findOne('orders', "id = $atext[1]");
        $user = R::findOne('users', "chat_id = {$order["chat_id"]}");
        $this->DelMessageText($chat_id, $message_id);
        $this->DelMessageText($chat_id, $order['mess_id']);
        $this->sendOrderWasher($atext[1], $user['username'], $order['washing_status'], False);
    } elseif ($chat_id == ID_CHAT || $chat_id == GROUP_COURIER_CHAT_ID) {
        $order = R::findOne('orders', "id = $atext[1]");
        $this->DelMessageText($chat_id, $order['mess_id']);
        $this->DelMessageText($chat_id, $message_id);
        $this->sendOrdersAdmin($chat_id, $atext[1], $message_id, False);
    }

    return;
}

# 1 Отправка веса заказа
if ($atext[0] == '/orders_ves_kurer') {

    $this->DelMessageText($chat_id, $message_id);

    $buttons[] = [
        $this->buildInlineKeyBoardButton("🚫 NO", "/cancel_osob_end $atext[1]"),
    ];

    $template = new Template("order/pickup/order_weight", null, [
        new TemplateData(":orderId", $atext[1])
    ]);
    $template = $template->Load();

    $send = $this->sendMessage(GROUP_COURIER_CHAT_ID, $template->text, $buttons);

    $mess_id = $send['result']['message_id'];

    # Записываем команду
    $this->set_action($chat_id, "orders_ves_kurer&$mess_id&$atext[1]");

    return;
}


# 2 Отправка веса заказа
if (isset($atext[0]) && $get_action[0] == 'orders_ves_kurer') {

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
        'organic' => $weight_all['organic'] * 150000
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
        $this->buildInlineKeyBoardButton("✅ YES", "/orders_ves_kurer_ok success $get_action[2]"),
    ];
    $buttons[] = [
        $this->buildInlineKeyBoardButton("🚫 NO", "/cancel_osob_end $get_action[2]"),
    ];

    $this->editMessageText($chat_id, $get_action[1], $template->text, $buttons);

    # Записываем команду
    $this->set_action($chat_id, "$get_action[2]&$get_action[1]&$text");

    return;
}


# 3 Отправка веса заказа
if ($atext[0] == '/orders_ves_kurer_ok') {

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
        'organic' => $weight_all['organic'] * 150000
    ];

    // Стоимость за все вещи
    $total_price = number_format(array_sum($prices), 0, "", ".");

    // Нужна для расчетов
    $unformatted_total_price = array_sum($prices);

    $wapp_prefix = "WA";

    if (substr($get_action[0], 0, strlen($wapp_prefix)) == $wapp_prefix) {
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
    $user = R::findOne('users', 'chat_id = ?', [$orders['chat_id']]);

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

    $template = new Template("order/pickup/order_weight_2", $user['lang'], [
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

    $lifepay_order_id = (int)($orders->id . $user->id . (time() % 1000));
    $orders->lifepay_order_id = $lifepay_order_id;
    R::store($orders);

    switch ($user['lang']) {
        case 'ru':
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
                $this->buildInlineKeyBoardButton("Оплата по СБП", "", "https://partner.life-pay.ru/alba/input/?name=%D0%9E%D0%BF%D0%BB%D0%B0%D1%82%D0%B0+%D1%83%D1%81%D0%BB%D1%83%D0%B3+%D0%BF%D1%80%D0%B0%D1%87%D0%B5%D1%87%D0%BD%D0%BE%D0%B9+LaundryBot&cost={$total_price}&key=KFBsJSEbBdjuZM4r4u9HpMTYWE%2FvPpBNAAN6%2FYJgl5w%3D&default_email=&prepayment_page=0&order_id={$lifepay_order_id}"),
            ];
            $buttons[] = [
                $this->buildInlineKeyBoardButton("Оплатить бонусами", "/sposob_pay 4 $unformatted_total_price $get_action[0]"),
            ];
            break;
        case 'eng':
            $buttons[] = [
                $this->buildInlineKeyBoardButton("Cash to courier", "/sposob_pay 1 test $get_action[0]"),
            ];
            $buttons[] = [
                $this->buildInlineKeyBoardButton("Transfer to local BRI bank", "/sposob_pay 2 $unformatted_total_price $get_action[0]"),
            ];
            $buttons[] = [
                $this->buildInlineKeyBoardButton("Pay with bonuses", "/sposob_pay 4 $unformatted_total_price $get_action[0]"),
            ];
    }

    $this->sendMessage($orders['chat_id'], $template->text, $buttons);

    $courier_chat_buttons = [
        $this->buildInlineKeyBoardButton("Add photo", "/order_courier_group_scales $get_action[2]"),
    ];
    // закончила здесь сделать чтобы отправлялась фотка на весах и потом фотка доставленных шмоток

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

        $formatted_ref_user_balance = number_format($ref_user["balance"], 0, "", ".");


        $templateUser = new Template("referal_notification", $ref_user['lang'], [
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


# 4 Отправка вес заказа
if ($atext[0] == '/sposob_pay') {
    $pay_type = (int)$atext[1];
    $order_id = $atext[3];
    $content1 = "";
    $content_admin = "";

    $order = R::findOne('orders', "id = $order_id");
    $user = R::findOne("users", "chat_id = $chat_id");

    switch ($pay_type) {
        case 1:
            if ($user['lang'] == 'ru') {
                $content1 .= "🙏Благодарим за выбранный способ оплаты!\n\n
💸Передайте данную сумму нашему курьеру или оставьте стаффу на ресепшене. У курьера всегда имеется с собой сдача.";
            } else {
                $content1 .= "🙏Thank you for choosing the payment method!\n\n
💸Give this amount to our courier or leave the staff at the reception. The courier always has change with him.";
            }

            $content_admin .= "<b>ℹ️ Order: #$atext[3]</b> \n\n";
            $content_admin .= "Payment: <b>courier</b> \n";

            break;
        case 2:
            # Отправляем смс
            if ($user['lang'] == 'ru') {
                $content1 .= "💳 Вот данные для перевода на карту индонезийского банка BRI в рупиях.\n\n";
                $content1 .= "4628 0100 4036 508 \n";
                $content1 .= "Anak Agung Gede Adi Semara \n\n";
                $content1 .= "🧾 После того как переведёте, пришлите, пожалуйста, чек об оплате в чат менеджеру.\n\n";

                $content_sum = number_format($atext[2], 0, "", ".");

                $content1 .= "<b>Сумма для перевода $content_sum рупий</b>";
            } else {
                $content1 .= "💳 Here is the data for the transfer to the card of the Indonesian bank BRI in IDR.\n\n";
                $content1 .= "If you are transferring from an Indonesian bank card, then use <b>the account number</b>.\n";
                $content1 .= "4628 0100 4036 508 \n\n";
                $content1 .= "If you have a card of another country, then transfer by <b>card number</b>\n";
                $content1 .= "6013 0111 3096 4124 \n\n";
                $content1 .= "Card and account in the name:\n";
                $content1 .= "<b>Anak Agung Gede Adi Semara</b>\n\n";
                $content_sum = number_format($atext[2], 0, "", ".");

                $content1 .= "<b>Amount to transfer $content_sum IDR</b>\n\n";
                $content1 .= "🧾 After you transfer the money, please send a payment receipt to the chat manager.\n\n";
            }

            $content_admin .= "<b>ℹ️ Order: #$atext[3]</b> \n\n";
            $content_admin .= "Payment: <b>transfer to Agung card</b> \n";
            /*$buttons[] = [
                $this->buildInlineKeyBoardButton("🧾Прикрепите, пожалуйста, чек об оплате", "/send_check $order_id"),
            ];*/
            break;
        case 3:
            $sum = number_format($atext[2] / 1000 * 6.2, 0, "", ".");

            # Отправляем смс
            $content1 .= "Вот данные для перевода на карту Тинькофф в рублях. 
После того как переведёте, пришлите, пожалуйста, чек об оплате в чат менеджеру.\n\n";
            $content1 .= "2200 7007 7932 1818 \n";
            $content1 .= "Olga G. \n\n";
            $content1 .= "<b>Сумма для перевода $sum рублей.</b>";

            $content_admin .= "<b>ℹ️ Order: #$atext[3]</b> \n\n";
            $content_admin .= "Payment: <b>transfer to Tinkoff card</b> \n";
            /*$buttons[] = [
                $this->buildInlineKeyBoardButton("🧾Прикрепите, пожалуйста, чек об оплате", "/send_check $order_id"),
            ];*/
            break;
        case 4:
            $user = R::findOne("users", "chat_id = $chat_id");
            if ($user) {
                if ($user['lang'] == 'ru') {
                    $user_balance = number_format($user["balance"], 0, "", ".");
                    $content_user = "<b>Ваш бонусный баланс равен $user_balance IDR</b>\n\n";

                    $order_sum = number_format($atext[2], 0, "", ".");
                    $content_user .= "Сумма заказа (<b>#$order_id</b>): $order_sum IDR\n";

                    if ($atext[2] > $user["balance"]) {
                        $content_user .= "Вы можете оплатить бонусами {$user["balance"]} IDR\n\n";
                        $order_remaining_sum = $atext[2] - $user["balance"];
                    } else {
                        $content_user .= "Вы можете оплатить бонусами $order_sum IDR\n\n";
                        $order_remaining_sum = 0;
                    }

                    $order_remaining_sum_content = number_format($order_remaining_sum, 0, "", ".");
                    $content_user .= "<b>Остаток к оплате будет $order_remaining_sum_content IDR</b>\n\n";

                    $content_user .= "Подтверждаете списание бонусов за данный заказ?";

                    $buttons_user = [
                        [
                            $this->buildInlineKeyBoardButton("Да", "/pay_type_bonus_success $order_id"),
                        ],
                        [
                            $this->buildInlineKeyBoardButton("Нет", "/pay_type_bonus_deny $order_id"),
                        ],
                    ];
                } else {
                    $user_balance = number_format($user["balance"], 0, "", ".");
                    $content_user = "<b>Your bonus balance equals $user_balance IDR</b>\n\n";

                    $order_sum = number_format($atext[2], 0, "", ".");
                    $content_user .= "Order amount (<b>#$order_id</b>): $order_sum IDR\n";

                    if ($atext[2] > $user["balance"]) {
                        $content_user .= "You can pay with {$user["balance"]} IDR bonuses\n\n";
                        $order_remaining_sum = $atext[2] - $user["balance"];
                    } else {
                        $content_user .= "You can pay with $order_sum IDR\n\n";
                        $order_remaining_sum = 0;
                    }

                    $order_remaining_sum_content = number_format($order_remaining_sum, 0, "", ".");
                    $content_user .= "<b>The balance due will be $order_remaining_sum_content IDR</b>\n\n";

                    $content_user .= "Are you sure you want to redeem points for this order?";

                    $buttons_user = [
                        [
                            $this->buildInlineKeyBoardButton("Yes", "/pay_type_bonus_success $order_id"),
                        ],
                        [
                            $this->buildInlineKeyBoardButton("No", "/pay_type_bonus_deny $order_id"),
                        ],
                    ];
                }

                $this->sendMessage($chat_id, $content_user, $buttons_user);
                return;
            }
            break;
    }

    if ($user['lang'] == 'ru') {
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Написать менеджеру", " ", "https://t.me/LaundryGoBot"),
        ];
    } else {
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Message to manager", " ", "https://t.me/LaundryGoBot"),
        ];
    }

    $this->sendMessage($chat_id, $content1, $buttons);
    $order->payment = $atext[1];
    R::store($order);

    $this->DelMessageText($order['temp_chat_id'], $order['mess_id']);
    $this->sendOrdersAdmin($order['temp_chat_id'], $atext[3], $username);

    return;
}

if ($atext[0] == "/send_check" && $atext[1] && $atext[3]) {
    #atext[2] - id чата

    $orderId = (int)$atext[1];
    $order = R::findOne("orders", "id = $orderId");

    $content_user = "Загрузите фото чека:";

    $this->sendMessage($chat_id, $content_user);
    $this->set_action($chat_id, "send_check&$orderId");

    return;
}


if ($atext[0] == "/pay_type_bonus_deny" && $atext[1]) {
    $this->DelMessageText($chat_id, $message_id);
    $order_id = (int)$atext[1];
    $order = R::findOne("orders", "id = $order_id");
    $user = R::findOne('users', 'chat_id = ?', $order['chat_id']);


    $template = new Template("order/pickup/order_weight_2", $user['lang'], [
        new TemplateData(":orderId", $order_id),
        // Информация о весе одежды
        new TemplateData(":clothesWeight", $order["wt"]),
        new TemplateData(":clothesPrice", $order["price_wt"]),
        // Информация о количестве пар обуви
        new TemplateData(":pairOfShoes", $order["shoes"]),
        new TemplateData(":shoesPrice", $order["price_shoes"]),
        // Информация о весе постельного белья
        new TemplateData(":badLinenWeight", $order['bed_linen']),
        new TemplateData(":badLinenPrice", $order['bed_linen_price']),
        // Информация о вещах для органической стирки
        new TemplateData(":organicWeight", $order['organic']),
        new TemplateData(":organicPrice", $order['organic_price']),
        // Итоговая стоимость
        new TemplateData(":totalPrice", $order['price']),
    ]);
    $template = $template->Load();

    $price = (int)str_replace(".", "", $order["price"]);

    if ($user['lang'] == 'ru') {
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Наличные курьеру в рупиях", "/sposob_pay 1 test $order_id"),
        ];
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Перевод на BRI в рупиях", "/sposob_pay 2 $price $order_id"),
        ];
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Перевод на Тинькофф в рублях", "/sposob_pay 3 $price $order_id"),
        ];
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Оплатить бонусами", "/sposob_pay 4 $price $order_id"),
        ];
    } else {
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Cash to courier", "/sposob_pay 1 test $order_id"),
        ];
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Transfer to local BRI bank", "/sposob_pay 2 $price $order_id"),
        ];
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Pay with bonuses", "/sposob_pay 4 $price $order_id"),
        ];
    }

    $this->sendMessage($order['chat_id'], $template->text, $buttons);
    return;
}

if ($atext[0] == "/pay_type_bonus_success" && $atext[1]) {
    $order_id = (int)$atext[1];

    $order = R::findOne("orders", "id = $order_id");

    $price = (int)str_replace(".", "", $order["price"]);

    $user = R::findOne("users", "chat_id = {$order["chat_id"]}");

    if ($price > $user["balance"]) {
        $sum_can_pay_bonus = $price - $user["balance"];
        $sum_can_pay_bonus_content = number_format($price - $user["balance"], 0, "", ".");

        $template = new Template("order/payment/partial_bonuses", $user['lang'], [
            new TemplateData(":orderId", $order_id),
            new TemplateData(":sumCanPayBonusContent", $sum_can_pay_bonus_content)
        ]);
        $template->Load();

        switch ($user['lang']) {
            case 'ru':
                $buttons = [
                    [
                        $this->buildInlineKeyBoardButton("Наличные курьеру в рупиях", "/sposob_pay 1 test $order_id"),
                    ],
                    [
                        $this->buildInlineKeyBoardButton("Перевод на индонезийскую карту в рупиях", "/sposob_pay 2 $sum_can_pay_bonus $order_id"),
                    ],
                    [
                        $this->buildInlineKeyBoardButton("Перевод на Тинькофф в рублях", "/sposob_pay 3 $sum_can_pay_bonus $order_id"),
                    ],
                ];
                break;
            case 'eng':
                $buttons = [
                    [
                        $this->buildInlineKeyBoardButton("Cash to courier", "/sposob_pay 1 test $order_id"),
                    ],
                    [
                        $this->buildInlineKeyBoardButton("Transfer to local BRI bank", "/sposob_pay 2 $sum_can_pay_bonus $order_id"),
                    ],
                ];
        }

        $order->bonus_payed = $user["balance"];
    } else {
        $remaining_user_bonus_count = number_format($user["balance"] - $price, 0, "", ".");

        $template = new Template('order/payment/bonuses', $user['lang'], [
            new TemplateData(":orderId", $order_id),
            new TemplateData(':remainingUserBonusCount', $remaining_user_bonus_count)
        ]);
        $template->Load();

        switch ($user['lang']) {
            case 'ru':
                $buttons_user = [
                    [
                        $this->buildInlineKeyBoardButton("Написать менеджеру", " ", "https://t.me/LaundryGoBot"),
                    ],
                ];
                break;
            case 'eng':
                $buttons_user = [
                    [
                        $this->buildInlineKeyBoardButton("Write to the manager", " ", "https://t.me/LaundryGoBot"),
                    ],
                ];
        }

        $order->bonus_payed = $price;
        $order->payment = 4;
    }

    R::store($order);
    $user->balance -= $order->bonus_payed;
    R::store($user);

    $this->DelMessageText($order['temp_chat_id'], $order["mess_id"]);
    $this->sendOrdersAdmin($order['temp_chat_id'], $order_id, $username);
    $this->DelMessageText($order['chat_id'], $message_id);
    $this->sendMessage($order['chat_id'], $template->text, $buttons);
}

if ($atext[0] == "/usermailing_answer" && $atext[1] && $atext[2]) {
    $mailing_id = (int)$atext[1];
    $answer = (int)$atext[2];

    // получаю пользователя по chat_id
    $user = R::findOne("users", "chat_id = $chat_id");

    // получаю рассылку по id
    $mailing = R::findOne("usermailing", "id = $mailing_id");

    // удаляю предыдущее сообщение пользователя
    $this->DelMessageText($user["chat_id"], $message_id);

    // сохраняю в базу данных ответ пользователя
    $mailinganswer = R::dispense("usermailinganswers");
    $mailinganswer["user_id"] = $user["id"];
    $mailinganswer["mailing_id"] = $mailing_id;
    $mailinganswer["answer"] = $answer;
    R::store($mailinganswer);

    // формирую сообщение пользователю
    $template = new Template('usermailing_answer', $user['lang']);
    $template->Load();

    // отправляю сообщение пользователю
    $this->sendMessage($chat_id, $template->text);
}


if ($atext[0] == "/orders_report" && $atext[1]) {

    $orderId = (int)$atext[1];

    $order = R::findOne("orders", "id = $orderId");


    if ($order['photo_before']) {
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Pickup photo", "", "https://laundrybot.online/bot/" . $order['photo_before']),
        ];
    }

    if ($order['photo_in_laundry']) {
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Photo in laundry", "", "https://laundrybot.online/bot/" . $order['photo_in_laundry']),
        ];
    }

    if ($order['video_before_washing']) {
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Photo before washing", "", "https://laundrybot.online/bot/" . $order['video_before_washing']),
        ];
    }/* else {
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Video before washing", "/video_none $orderId"),
        ];
    }*/

    if ($order['video_after_washing']) {
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Photo after washing", "", "https://laundrybot.online/bot/" . $order['video_after_washing']),
        ];
    }/* else {
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Video after washing", "/video_none $orderId"),
        ];
    }*/

    if ($order['photo_on_the_scales']) {
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Weight", "/photo_on_the_scales $orderId"),
        ];
    }

    if ($order['delivered_photo']) {
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Delivered", "/photo_of_the_delivered $orderId"),
        ];
    }

    /*
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Сheck", "/print_check_admin $orderId"),
        ];
    */

    $buttons[] = [
        $this->buildInlineKeyBoardButton("Back", "/cancel_osob_end $orderId"),
    ];

    $this->editMessageReplyMarkup($chat_id, $message_id, $buttons);

    return;
}

if ($atext[0] == "/order_report_back") {

    $this->DelMessageText($chat_id, $message_id);
    return;
}


if ($atext[0] == "/video_none" && $atext[1]) {
    $orderId = (int)$atext[1];

    $content = "Order video <b>#" . $orderId . "</b> not loaded.";

    $buttons[] = [
        $this->buildInlineKeyBoardButton("Back", "/cancel_osob_end $orderId"),
    ];

    $this->sendMessage($chat_id, $content, $buttons);

    return;
}

if ($atext[0] == "/photo_on_the_scales" && $atext[1]) {
    $orderId = (int)$atext[1];

    $order = R::findOne("orders", "id = $orderId");

    $photos = json_decode($order['photo_on_the_scales'], true);

    $buttons[] = [
        $this->buildInlineKeyBoardButton("Back", "/cancel_osob_end $orderId"),
    ];

    $content = '';
    $content .= "<b>Order #$order[id] - photo on the scales.</b>\n";

    if (count($photos) > 0) {
        foreach ($photos as $key => $photo) {
            $content .= "<a href='https://laundrybot.online/bot/$photo[photo]'>Photo #" . ($key + 1) . "</a>\n";
            /*
            $content = "Фото #".($key+1);
            $this->sendPhoto($chat_id, "https://laundrybot.online/bot/" . $photo['photo'], $content, $buttons);
            */
        }
    } else {
        $content = "Order photo <b>#" . $orderId . "</b> not loaded.";
        $this->sendMessage($chat_id, $content, $buttons);
    }

    $this->sendMessage($chat_id, $content, $buttons);

    return;
}


if ($atext[0] == "/photo_of_the_delivered" && $atext[1]) {
    $orderId = (int)$atext[1];

    $order = R::findOne("orders", "id = $orderId");

    $photos = json_decode($order['delivered_photo'], true);

    $buttons[] = [
        $this->buildInlineKeyBoardButton("Back", "/cancel_osob_end $orderId"),
    ];

    $content = '';
    $content .= "<b>Order #$order[id] - photo of the delivered.</b>\n";

    if (count($photos) > 0) {
        foreach ($photos as $key => $photo) {
            $content .= "<a href='https://laundrybot.online/bot/$photo[photo]'>Photo #" . ($key + 1) . "</a>\n";
            /*
            $content = "Фото #".($key+1);
            $this->sendPhoto($chat_id, "https://laundrybot.online/bot/" . $photo['photo'], $content, $buttons);
            */
        }
    } else {
        $content = "Order photo <b>#" . $orderId . "</b> not loaded.";
        $this->sendMessage($chat_id, $content, $buttons);
    }

    $this->sendMessage($chat_id, $content, $buttons);

    return;
}


if ($atext[0] == "/print_check_admin" && $atext[1]) {

    $this->DelMessageText($chat_id, $message_id);

    $orderId = (int)$atext[1];

    $order = R::findOne("orders", "id = $orderId");

    if ($order['check_order']) {
        $content = "Фото чека заказа <b>#" . $orderId . "</b>";

        if ($order['check_admin'] == null) {
            $buttons[] = [
                $this->buildInlineKeyBoardButton("OK", "/check_success $orderId $message_id"),
            ];

            $buttons[] = [
                $this->buildInlineKeyBoardButton("CANCEL", "/check_cancel $orderId"),
            ];


            $this->sendPhoto(ID_CHAT, "https://laundrybot.online/bot/" . $order['check_order'], $content, $buttons);

        } else {
            if ($chat_id == ID_CHAT || $chat_id == GROUP_COURIER_CHAT_ID || $chat_id == GROUP_WASHERS_CHAT_ID) {
                $this->sendPhoto($chat_id, "https://laundrybot.online/bot/" . $order['check_order'], $content);
            }
        }
    } else {
        $content = "Фото чека заказа <b>#" . $orderId . "</b> не загружено. Загрузить?";

        $buttons[] = [
            $this->buildInlineKeyBoardButton("👍Yes", "/sendCheck $orderId $chat_id"),
        ];

        $buttons[] = [
            $this->buildInlineKeyBoardButton("👎No", "/check_load_photo_no $orderId"),
        ];

        if ($chat_id == ID_CHAT || $chat_id == GROUP_COURIER_CHAT_ID || $chat_id == GROUP_WASHERS_CHAT_ID) {
            $this->sendMessage($chat_id, $content, $buttons);
        }
    }
    return;
}


if ($atext[0] == "/check_load_photo_success" && $atext[1]) {
    $orderId = (int)$atext[1];

    $content = "Загрузите фото чека:";

    $buttons[] = [
        $this->buildInlineKeyBoardButton("Back", "/order_report_back"),
    ];

    $this->sendMessage(ID_CHAT, $content, $buttons);

    $this->set_action($chat_id, "check_load_photo_success&$orderId");

    return;
}

if ($atext[0] == "/check_load_photo_no" && $atext[1]) {
    $orderId = (int)$atext[1];
    $this->DelMessageText($chat_id, $message_id);
    return;
}


if ($atext[0] == "/check_success" && $atext[1]) {

    $orderId = (int)$atext[1];

    $order = R::findOne("orders", "id = $orderId");

    $order['check_admin'] = 1;

    R::store($order);
    $content = "✅ Заказ № $orderId оплачен.
Спасибо огромное.
Если у вас есть вопросы или комментарии по заказу напишите менеджеру.
";
    $buttons[] = [
        $this->buildInlineKeyBoardButton("Написать менеджеру", " ", "https://t.me/LaundryGoBot"),
    ];
    $buttons[] = [
        $this->buildInlineKeyBoardButton("Заказать стирку", "/nachalo"),
    ];


    $this->sendMessage($order['chat_id'], $content, $buttons);
    $this->DelMessageText($chat_id, $message_id);

}


if ($atext[0] == "/check_cancel" && $atext[1]) {

    $this->DelMessageText($chat_id, $message_id);

    $orderId = (int)$atext[1];

    $order = R::findOne("orders", "id = $orderId");

    unlink($order['check_order']);

    $order['check_order'] = "";
    R::store($order);

    $content = "Чек отменен";


    $buttons[] = [
        $this->buildInlineKeyBoardButton("Загрузить фото чека", "/send_check $orderId  $chat_id"),
    ];

    $buttons[] = [
        $this->buildInlineKeyBoardButton("Написать менеджеру", " ", "https://t.me/LaundryGoBot"),
    ];

    $this->sendMessage($order['chat_id'], $content, $buttons);
}


if ($atext[0] == "/order_report" && $atext[1]) {

    if ($chat_id == GROUP_WASHERS_CHAT_ID) {
        $order = R::findOne('orders', "id = $atext[1]");
        $user = R::findOne('users', "chat_id = {$order["chat_id"]}");

        $this->DelMessageText($chat_id, $message_id);
        $this->sendOrderWasher($atext[1], $user['username'], $order['status'], True);
    } elseif ($chat_id == ID_CHAT || $chat_id == GROUP_COURIER_CHAT_ID) {
        $this->DelMessageText($chat_id, $message_id);
        $this->sendOrdersAdmin($chat_id, $atext[1], $message_id, True);
    }
}


# Регистрируем в бд пользователя
function regusers($chat_id, $first_name, $last_name, $username)
{
    $users = R::findOne('users', 'chat_id = ?', [$chat_id]);
    $getchat_id = $users->chat_id;

    if ($chat_id == $getchat_id) {

    } else {
        $save = R::dispense('users');
        $save->chat_id = $chat_id;
        $save->first_name = "$first_name";
        $save->last_name = "$last_name";
        $save->username = "$username";
        $save->balance = 0;
        $save->phone = " ";
        $save->date_reg = time();
        $save->status = 1;
        // Сохраняем объект
        R::store($save);
    }
}


if ($atext[0] == '/return_to_admin' && $atext[1]) {
    $this->sendOrdersAdmin(ID_CHAT, $atext[1], $username);
}


if ($atext[0] == '/return_to_courier' && $atext[1]) {
    $this->sendOrdersAdmin(GROUP_COURIER_CHAT_ID, $atext[1], $username);
}


if ($atext[0] == '/change_lang') {
    $user = R::findOne('users', 'chat_id = ?', [$chat_id]);
    $template = new Template("lang");
    $template->Load();
    $template->LoadButtons();

    $this->sendMessage($chat_id, $template->text, $template->buttons);
}
    
    
    