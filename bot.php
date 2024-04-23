<?php
// ВЕРСИЯ: v2.0
// Автор: Андрей Петров
// ВК: https://vk.com/id622116412
// Магазин: https://vk.com/sshop_m
// https://api.telegram.org/bot5636842306:AAGBRzGrwLR3XPP1AgEYokvkFuIzN2kwHJU/setWebhook?url=https://bot.r-devshop.online/ord/bot.php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
header("HTTP/2.0 200 OK");

require __DIR__ . '/phpqrcode/qrlib.php';

# [Подключаем БД]
require __DIR__ . "/core/RB/rb.php";
require __DIR__ . "/template.php";

/* [Создаем объект бота] */
$bot = new Bot();
/* [Обрабатываем пришедшие данные] */
$bot->init();

/**
 * Class Bot
 */
class Bot
{
    public function init()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->router($data);

        // в любом случае вернем true для бот апи
        return true;
    }

    public function router($data)
    {
        # Подключаем класс архиватор
        $zip = new ZipArchive();
        # Подключаем файл конфигураций
        $base = parse_ini_file("core/configs.ini", true);

        # Токен бота телеграмм
        define('TOKEN', $base['system']['telegram_token']);

        # Чат регистраций
        define('ID_CHAT', $base['system']['id_chat']);

        // чат курьеров
        define("COURIER_CHAT_ID", $base["system"]["courier_chat_id"]);

        // чат аналитики
        define("ANALYTICS_CHAT_ID", $base["system"]["analytics_chat_id"]);

        // группа курьеров
        define("GROUP_COURIER_CHAT_ID", $base["system"]["group_courier_chat_id"]);

        // группа прачек
        define("GROUP_WASHERS_CHAT_ID", $base["system"]["group_washers_chat_id"]);

        # Яндекс.Кошелек для приема оплаты
        define('YANDEX_MONEY', '915243:test_05DTE-_W7weZhSDqmdLIISB23Hwai0FaDgpVfoM6WoY');

        # Адрес, на который переадресует пользователя в случае успешного платежа
        define('PAY_SUCCESS', 't.me/devshoptaksi_bot');

        # Название магазина
        define('NAME_SHOP', 'Магазин SSHOP | Боты | Сайты');

        # Логи запросов
        $base_logs = $base['system']['logs'];
        # Подключение к бд
        $mysql_status = $base['mysql']['status'];
        $mysql_ip = $base['mysql']['ip'];
        $mysql_dbname = $base['mysql']['dbname'];
        $mysql_dbuser = $base['mysql']['dbuser'];
        $mysql_password = $base['mysql']['password'];

        # Проверка и подключение к бд
        $this->dbconnect($mysql_status, $mysql_ip, $mysql_dbname, $mysql_dbuser, $mysql_password);

        # Устанавливаем временную зону
        date_default_timezone_set($base['system']['timezone']);
        switch (isset($data)) {
            case 'message':
                $id = $data['message']['from']['id'];
                $first_name = $data['message']['from']['first_name'];
                $last_name = $data['message']['from']['last_name'];
                $username = $data['message']['from']['username'];
                $chat_id = $data['message']['chat']['id'];
                $message_id = $data['message']['message_id'];
                $text = $data['message']['text'];


                if (isset($data['message']['photo'])) { # При отправке фото в чат

                    $photo = $data['message']['photo'];
                    $photo_caption = $data['message']['caption'];

                    # Команда пользователя из файла
                    @$get_action = explode("&", ($this->get_action($chat_id)));

                    if ($get_action[2] != "orders_ok_kurer") {

                        # Добавлям фото объявления
                        if ($photo && $get_action[0] == 'photo') {

                            # Отменяем в созданий объявления
                            if ($atext[1] == 'cancel_orders') {
                                return;
                            }

                            # Кнопка
                            $buttons[] = [
                                $this->buildInlineKeyBoardButton("✅ YES", "/send_osob $get_action[1]"),
                            ];
                            # Кнопка
                            $buttons[] = [
                                $this->buildInlineKeyBoardButton("🚫 NO", "/cancel_osob_end $get_action[1]"),
                            ];

                            # Выводим инфорацию о объявлении
                            $orders = R::findOne('orders', "id = $get_action[1]");

                            $this->getPhoto($data['message']['photo'], $photo_caption, $chat_id, $get_action[1]);

                            # Текст
                            if ($photo_caption) {
                                $this->get_orders($get_action[1], "about_p", $photo_caption);
                                $content .= "Текст: <b>$photo_caption</b>\n";
                            }

                            $content .= "Изображение: <b>добавлено</b>";

                            if ($get_action[2]) {
                                $this->editMessageText($chat_id, $get_action[2], $content, $buttons);

                                # Записываем команду
                                $this->set_action($chat_id, "photo&$get_action[1]&$get_action[2]&$get_action[3]");
                            } else {
                                $send = $this->sendMessage($chat_id, $content, $buttons);
                                $mess = $send['result']['message_id'];

                                # Записываем команду
                                $this->set_action($chat_id, "photo&$get_action[1]&$mess&$photo_caption");
                            }

                            return;
                        }

                        if ($photo && $get_action[0] == 'send_check') {
                            $orderId = (int)$get_action[1];

                            $order = R::findOne("orders", "id = $orderId");

                            $newPhotoName = $this->saveFileGroup($data, $order);

                            $order["check_order"] = $newPhotoName;
                            R::store($order);

                            $this->del_action($chat_id);
                            $content = "Изображение: <b>добавлено</b>";

                            $buttons[] = [
                                $this->buildInlineKeyBoardButton("Отправить чек на проверку", "/print_check_admin $orderId"),
                            ];

                            $this->sendMessage($chat_id, $content, $buttons);

                            return;
                        }

                        if ($photo && $get_action[0] == 'check_load_photo_success') {
                            $orderId = (int)$get_action[1];

                            $order = R::findOne("orders", "id = $orderId");

                            $newPhotoName = $this->saveFileGroup($data, $order);

                            $order["check_order"] = $newPhotoName;
                            $order['check_admin'] = 1;
                            R::store($order);

                            $this->del_action($chat_id);
                            $content = "Изображение: <b>добавлено</b>";

                            $this->sendMessage($chat_id, $content);

                            return;
                        }

                    }

                } else if (isset($data['message']['new_chat_participant'])) { # Событие "приглашение в группу"
                    $first_name_chat = $data['message']['new_chat_participant']['first_name'];
                    $last_name_chat = $data['message']['new_chat_participant']['last_name'];
                } elseif (isset($data['message']['document'])) { # При отправке документа в чат
                    return $data['message']['document']['file_id'];
                } elseif (isset($data['message']['sticker'])) { # При отправке стикера в чат
                    return $data['message']['sticker']['file_id'];
                } elseif (isset($data['message']['voice'])) { # При отправке

                    # Команда пользователя из файла
                    @$get_action = explode("&", ($this->get_action($chat_id)));

                    # Добавлям
                    if ($data['message']['voice']['file_id'] && $get_action[0] == 'photo') {

                        $content .= " 📩 Cообщение отправлено.";

                        $this->sendMessage($chat_id, $content);

                        $caption = "<b>ℹ️ Заказ: #$get_action[1]</b>";

                        $this->sendVoice(ID_CHAT, $caption, $data['message']['voice']['file_id']);

                    }

                    return $data['message']['voice']['file_id'];


                } elseif (isset($data['message']['video'])) { # При отправки видео в чат
                    # return $data['message']['video']['file_id'];

                    $photo = $data['message']['video']['file_id'];
                    $photo_caption = $data['message']['caption'];

                    # Команда пользователя из файла
                    @$get_action = explode("&", ($this->get_action($chat_id)));

                    if ($get_action[2] == "orders_ok_kurer") {

                    } else {

                        # Добавлям фото объявления
                        if ($photo && $get_action[0] == 'photo') {

                            # Отменяем в созданий объявления
                            if ($atext[1] == 'cancel_orders') {
                                return;
                            }

                            # Удаляем прошлое смс
                            # $this->DelMessageText($chat_id, $message_id);

                            # Кнопка
                            $buttons[] = [
                                $this->buildInlineKeyBoardButton("✅ YES", "/send_osob1 $get_action[1]"),
                            ];
                            # Кнопка
                            $buttons[] = [
                                $this->buildInlineKeyBoardButton("🚫 NO", "/cancel_osob_end $get_action[1]"),
                            ];

                            # Выводим информацию об объявлении
                            $orders = R::findOne('orders', "id = $get_action[1]");

                            $this->getPhoto1($data['message']['video']['file_id'], $chat_id, $photo_caption, $get_action[1]);

                            # Текст
                            if ($photo_caption) {
                                $this->get_orders($get_action[1], "about_p", $photo_caption);
                                $content .= "Текст: <b>$photo_caption</b>\n";
                            }

                            $content .= "Видео: <b>добавлено</b>";

                            if ($get_action[2]) {
                                $this->editMessageText($chat_id, $get_action[2], $content, $buttons);

                                # Записываем команду
                                $this->set_action($chat_id, "photo&$get_action[1]&$get_action[2]&$get_action[3]");
                            } else {
                                $send = $this->sendMessage($chat_id, $content, $buttons);
                                $mess = $send['result']['message_id'];

                                # Записываем команду
                                $this->set_action($chat_id, "photo&$get_action[1]&$mess&$photo_caption");
                            }


                            return;
                        }
                    }


                } elseif (isset($data['message']['contact'])) { # При отправке номера телефона в чат

                    # Команда пользователя из файла
                    $get_action_phone = explode("&", ($this->get_action($chat_id)));
                    $phone_user = $data['message']['contact']['phone_number'];

                    #
                    if ($phone_user && $get_action_phone[0] == 'phone') {

                        # Кол-во нажатий метрика
                        $this->set_metrika($chat_id, 4);

                        $users = R::findOne('users', "chat_id = $chat_id");
                        $users->phone = $phone_user;
                        R::store($users);

                        $this->get_orders($get_action_phone[1], "phone", $phone_user);

                        if (!trim($users['whatsapp'])) {

                            $buttons12[] = [
                                $this->buildInlineKeyBoardButton("/", "/"),
                            ];
                            # Отправка смс
                            $send = $this->sendMessage($chat_id, "Ваше сообщение", $buttons12, 2);
                            $mess = $send['result']['message_id'];

                            # Удаляем последнее сообщение
                            $this->DelMessageText($chat_id, $mess);

                            # Удаляем прошлое сообщение
                            # $this->DelMessageText($chat_id, $message_id);

                            $content .= "Пришлите дополнительно номер WhatsApp, чтобы мы точно с вами связались";

                            $this->sendMessage($chat_id, $content);

                            # Записываем команду
                            $this->set_action($chat_id, "whatsapp&$get_action_phone[1]");

                        }
                    }

                } elseif (isset($data['message']['location'])) { # При отправке локаций в чат

                    $get_action_geo = explode("&", ($this->get_action($chat_id)));
                    $x = $data['message']['location']['longitude'];
                    $y = $data['message']['location']['latitude'];

                    $obj = $this->GeoMaps($y, $x);

                    $send1 = $obj[0]['local_names']['ru'];
                    if ($y && $get_action_geo[0] == "address") {
                        $buttons[] = [
                            $this->buildInlineKeyBoardButton("не нужна", "/"),
                        ];

                        # Отправка смс
                        $send = $this->sendMessage($chat_id, "Ваше сообщение", $buttons, 2);
                        $mess = $send['result']['message_id'];
                        $this->DelMessageText($chat_id, $mess);
                        # Кол-во нажатий метрика
                        $this->set_metrika($chat_id, 3);

                        $get_id = $this->get_orders(9999, "ghgh", "hgh");

                        $this->get_orders($get_id, "chat_id", $chat_id);
                        $this->get_orders($get_id, "maps", "$y,$x");
                        $this->get_orders($get_id, "status", '-1');

                        $users = R::findOne('users', "chat_id = $chat_id");

//                        $content = $this->_loadTemplate("step_2_in_5");
                        $template = new Template("order/step_2_in_5", $users['lang']);
                        $template = $template->Load();
                        $this->sendMessage($chat_id, $template->text);

                        # Записываем команду
                        $this->set_action($chat_id, "address_2&$get_id");
                    }

                } elseif (isset($data['message']['reply_to_message'])) { # При отправке пересылке смс
                    $reply_to_id = $data['message']['reply_to_message']['chat']['id'];
                    $reply_to_id_support = $data['message']['reply_to_message']['entities']['2']['user']['id'];
                    $reply_to_text = $data['message']['reply_to_message']['text'];
                }

                if (array_key_exists('callback_query', $data)) {
                    $id = $data['callback_query']['from']['id'];
                    $first_name = $data['callback_query']['from']['first_name'];
                    $last_name = $data['callback_query']['from']['last_name'];
                    $username = $data['callback_query']['from']['username'];
                    $chat_id = $data['callback_query']['message']['chat']['id'];
                    $message_id = $data['callback_query']['message']['message_id'];
                    $text = $data['callback_query']['data'];
                    $chat_username = $data['callback_query']['message']['from']['username'];
                    $callback_query_id = $data['callback_query']['id'];
                }
                break;

            default:
                # Крон
                echo "ok";
                foreach (glob(__DIR__ . '/cron/*.php') as $file) {
                    if (is_file($file)) {
                        include_once $file;
                    } else {
                        return 0;
                    }
                }
                break;
        }

        if ($base_logs == 1) {
            # Введем логи в файл
            $this->setFileLog($data);
        }

        # Команда пользователя из файла
        @$get_action = explode("&", ($this->get_action($chat_id)));
        # Команда пользователя из чата
        @$atext = explode(" ", $text);

        ###########################################################################

        # Загружаем модули
        foreach (glob(__DIR__ . '/modules/*.php') as $file) {
            if (is_file($file)) {
                include_once $file;
            } else {
                return 0;
            }
        }

        #  send_osob1 video
        if ($atext[0] == '/send_osob1') {

            $this->DelMessageText($chat_id, $message_id);

            $orders = R::findOne('orders', "id = $atext[1]");
            $caption = "<b>ℹ️ Заказ: #$orders[id]</b>\n\n$orders[about_p]";

            $content .= "📩 Cообщение отправлено.";

            $this->sendMessage($chat_id, $content);

            $dir = __DIR__ . "/img/orders/video_$atext[1]/*";


            foreach (glob($dir) as $file1) {

                $rr = basename($file1); // file.png

                # $this->sendMessage($chat_id, $rr ." | fdfd");
                $path1 = $_SERVER['PHP_SELF'];
                $path_len1 = mb_strripos($_SERVER['PHP_SELF'], "/");
                $path_new1 = mb_strcut($path1, 0, $path_len1 + 1);

                $url = "https://" . $_SERVER['SERVER_NAME'] . "/ord/img/orders/video_$atext[1]/$rr";

                $key++;

                if ($key >= 2) {
                    $btn[] = ['type' => 'video', 'media' => $url];
                } else {
                    $btn[] = ['type' => 'video', 'caption' => $caption, 'media' => $url, 'parse_mode' => 'html'];
                }

            }

            $this->sendMediaGroup(ID_CHAT, "fddf", $btn);

            # После отправки удаляем картинки и дирректорию
            $this->remove_dir(__DIR__ . "/img/orders/video_$atext[1]");

            # Записываем команду
            $this->set_action($chat_id, "photo&$atext[1]");
            return;
        }

        #  send_osob
        if ($atext[0] == '/send_osob') {

            $this->DelMessageText($chat_id, $message_id);

            $orders = R::findOne('orders', "id = $atext[1]");
            $caption = "<b>ℹ️ Заказ: #$orders[id]</b>\n\n$orders[about_p]";

            $content .= "📩 Cообщение отправлено.";

            $this->sendMessage($chat_id, $content);

            $dir = __DIR__ . "/img/orders/$atext[1]/*";


            foreach (glob($dir) as $file1) {

                $rr = basename($file1); // file.png

                # $this->sendMessage($chat_id, $rr ." | fdfd");
                $path1 = $_SERVER['PHP_SELF'];
                $path_len1 = mb_strripos($_SERVER['PHP_SELF'], "/");
                $path_new1 = mb_strcut($path1, 0, $path_len1 + 1);

                $url = "https://" . $_SERVER['SERVER_NAME'] . "/ord/img/orders/$atext[1]/$rr";

                $key++;

                if ($key >= 2) {
                    $btn[] = ['type' => 'photo', 'media' => $url];
                } else {
                    $btn[] = ['type' => 'photo', 'caption' => $caption, 'media' => $url, 'parse_mode' => 'html'];
                }

            }

            $this->sendMediaGroup(ID_CHAT, "fddf", $btn);

            # После отправки удаляем картинки и дирректорию
            $this->remove_dir(__DIR__ . "/img/orders/$atext[1]");

            # Записываем команду
            $this->set_action($chat_id, "photo&$atext[1]");
            return;
        }


        # текстовое сообщение
        if ($atext[0] && $get_action[0] == "photo") {

            if ($atext[0] == "/orders_ok_kurer" || $atext[0] == "/back_orders" || $text == "⬅️ Вернуться в заказ" || $text == "⬅️  Вернуться в заказ." || $atext[0] == "/cancel_osob_end" || $atext[0] == "/send_osob1" || $atext[0] == "/back_orders_n") {
                #$this->del_action($chat_id);

            } else {

                # $this->DelMessageText($chat_id, $message_id);

                $content .= " 📩 Cообщение отправлено.";

                $this->sendMessage($chat_id, $content);

                $caption = "<b>ℹ️ Заказ: #$get_action[1]</b>\n\n$text";

                $this->sendMessage(ID_CHAT, $caption);

                # $this->del_action($chat_id);

                return;

            }

        }
        return true;

    }


    function set_metrika($chat_id, $count_n)
    {
        $metrika = R::dispense('metrika');
        // Заполняем объект свойствами
        $metrika->chat_id = $chat_id;
        $metrika->count_n = $count_n;
        // Сохраняем объект
        R::store($metrika);
    }

    function sendVoice($chat_id, $caption, $text)
    {
        $content = [
            'chat_id' => $chat_id,
            'caption' => $caption,
            'parse_mode' => 'html',
            'voice' => $text
        ];

        return $send = $this->requestToTelegram($content, "sendVoice");
    }


    function remove_dir($dir)
    {
        if ($objs = glob($dir . '/*')) {
            foreach ($objs as $obj) {
                is_dir($obj) ? $this->remove_dir($obj) : unlink($obj);
            }
        }
        rmdir($dir);
    }

    function sendMediaGroup($chat_id, $caption, array $btn)
    {
        $content = [
            'chat_id' => $chat_id,
            'media' => json_encode($btn, true),
        ];

        // отправляем запрос на удаление
        return $send = $this->requestToTelegram($content, "sendMediaGroup");
    }

    # Отправка заказа в чат
    function sendOrdersAdmin($chat, $ids_number)
    {
        $orders = R::findOne('orders', "id = $ids_number");

        if ($orders['paid'] == 1) {
            $paid = "<b>наличные</b>";
        } else if ($orders['paid'] == 2) {
            $paid = "<b>paid by BRI Bank card.</b>";
        } else if ($orders['paid'] == 3) {
            $paid = "<b>на Тинькофф</b>";
        } else if ($orders["paid"] == 5) {
            $payment = "sbp";
            $paid = "<b>Оплачено по СБП.</b>";
        }

        if ($orders['payment'] == 1) {
            $payment = "Оплачено наличными";
        } else if ($orders['payment'] == 2) {
            $payment = "transfer to BRI Bank card";
        } else if ($orders['payment'] == 3) {
            $payment = "Перевод на Тинькофф";
        } else if ($orders["payment"] == 4) {
            $payment = "bonuses";
            $paid = "<b>Оплачено бонусами.</b>";
        } else if ($orders["payment"] == 5) {
            $payment = "sbp";
            $paid = "<b>Оплачено по СБП.</b>";
        }

        if ($orders['otziv'] == 1) {
            $otziv = "1 ❄️ ";
        } else if ($orders['otziv'] == 2) {
            $otziv = "2 ❄️ ";
        } else if ($orders['otziv'] == 3) {
            $otziv = "3 ❄️ ";
        } else if ($orders['otziv'] == 4) {
            $otziv = "4 ❄️ ";
        } else if ($orders['otziv'] == 5) {
            $otziv = "5 ❄️ ";
        }

        $created_at = "";
        if ($orders["timestamp_create"]) {
            $created_at_date = date("d.m.Y", $orders["timestamp_create"]);
            $created_at_time = date("H:i", $orders["timestamp_create"]);
        }

        if ($orders['laundry_name']) {
            $laundry_name = $orders['laundry_name'];
            $content .= "<b>Laundry</b>: $laundry_name \n\n";
        }

        if ($orders['status'] == 0) { # Заказ отменен

            if ($orders['title_cancel'] == 1) {
                $prichina = "Просто решил проверить Бот";
            } else if ($orders['title_cancel'] == 2) {
                $prichina = "Передумал стирать";
            } else if ($orders['title_cancel'] == 3) {
                $prichina = "Переживаю за качество стирки";
            } else if ($orders['title_cancel'] == 4) {
                $prichina = "Дорого";
            }


            $content .= "<b>❌ Заказ: #$orders[id] отменен!</b>\n";
            $content .= "<b>Создан: $created_at_date в $created_at_time</b>\n\n";
            $content .= "Причина: <b>$prichina</b>\n\n";
        } else if ($orders['status'] == 1) {
            if (!$orders['photo_before']) {
                $buttons[] = [
                    $this->buildInlineKeyBoardButton("Родионова", "/order_courier_group_pickup $ids_number $canggu_name"),
                ];
            } else {
                $buttons[] = [
                    $this->buildInlineKeyBoardButton("В лаундри🧼", "/order_courier_group_laundry_photo $ids_number"),
                ];
            }

            $content .= "✅ <b>Заказ: #$orders[id]</b>\n";
            $content .= "<b>Создан: $created_at_date в $created_at_time</b>\n\n";
        } else if ($orders['status'] == 2) {
            if (trim($orders['wt']) || trim($orders["shoes"]) || trim($orders['bed_linen']) || trim($orders['organic'])) {
                if ($chat == GROUP_COURIER_CHAT_ID) {
                    $buttons[] = [
                        $this->buildInlineKeyBoardButton("Фото на весах", "/order_courier_group_scales $ids_number"),
                    ];
                }
            } else {
                $buttons[] = [
                    $this->buildInlineKeyBoardButton("💯Взвесить", "/orders_ves_kurer $ids_number"),
                ];
            }

            $content .= "✅ <b>Заказ: #$orders[id]</b>\n";
            $content .= "<b>Создан: $created_at_date в $created_at_time</b>\n\n";

        } else if ($orders['status'] == 3) {
            if (trim($orders['delivered_photo']) == null) {
                $buttons[] = [
                    $this->buildInlineKeyBoardButton("📤Заказ доставлен", "/orders_back_kurer $ids_number"),
                ];
            }

            $content .= "✅ <b>Заказ: #$orders[id]</b>\n";
            $content .= "<b>Создан: $created_at_date в $created_at_time</b>\n\n";

        } elseif ($orders['status'] == 4) {
            if ($chat == GROUP_COURIER_CHAT_ID) {
                $buttons[] = [
                    $this->buildInlineKeyBoardButton("Фото доставленного заказа", "/order_courier_group_photo_delivered $ids_number"),
                ];
            }

            $content .= "✅ <b>Заказ: #$orders[id]</b>\n";
            $content .= "<b>Создан: $created_at_date в $created_at_time</b>\n\n";

        } else if ($orders['status'] == 5) {
            if ($orders["payment"] != 4) {
                $buttons[] = [
                    $this->buildInlineKeyBoardButton("💵Оплачено наличными", "/orders_orders_card_kurer $ids_number 1"),
                ];
                $buttons[] = [
                    $this->buildInlineKeyBoardButton("🪪Перевод на Тинькофф", "/orders_orders_card_kurer $ids_number 3"),
                ];
            }

            $content .= "✅ <b>Заказ: #$orders[id]</b>\n";
            $content .= "<b>Создан: $created_at_date в $created_at_time</b>\n\n";
        } else if ($orders['status'] == 6) {
            $content .= "✅ <b>Заказ: #$orders[id]</b>\n";
            $content .= "<b>Создан: $created_at_date в $created_at_time</b>\n\n";
        }

        $users = R::findOne('users', "chat_id = $orders[chat_id]");

        $content .= "ID: <b>{$users["id"]}</b> \n";
        if (!$users['phone']) {
            $phone = $orders['phone'];
        } else {
            $phone = $users['phone'];
        }

        $content .= "Login telegram: <b>@$users[username]</b> \n";
        $content .= "Количество заказов: <b>{$users["orders_count"]}</b> \n";
        $content .= "Номер Telegram: <b>$phone</b>\n";
        $content .= "📍Локация: https://www.google.com/maps/place/$orders[maps] \n";
        $content .= "Детали места: <b>$orders[address_2]</b>\n";
        if (trim($orders['time_start'])) {
            $time = date('d.m.Y H:i', $orders['time_start']);
            $content .= "📥Курьер забрал вещи у клиента: <b>$time</b>\n";
        }
        if (trim($orders['in_laundry'])) {
            $time = $orders['in_laundry'];
            $content .= "🗺Курьер доставил вещи в лаундри: <b>$time</b>\n";
        }
        if (trim($orders['washing_started'])) {
            $time = $orders['washing_started'];
            $content .= "🧼Начали стирку: <b>$time</b>\n";
        }
        if (trim($orders['washed'])) {
            $time = $orders['washed'];
            $content .= "💪Закончили стирку: <b>$time</b>\n";
        }
        if (trim($orders['waighed'])) {
            $time = $orders['waighed'];
            $content .= "🛒Курьер взвесил. На доставке: <b>$time</b>\n";
        }
        if (trim($orders['time_end'])) {
            $time_end = date("d/m/Y H:i", $orders['time_end']);
            $content .= "🛵Курьер доставил: <b>$time_end</b>\n";
        }


        if (trim($orders['wt'])) {
            $content .= "\n👕Вес одежды: <b>$orders[wt]</b> \n";
            $content .= "Цена за одежду: <b>$orders[price_wt] руб</b> \n\n";
        }
        if (trim($orders['shoes'])) {
            $content .= "👟Обувь: <b>$orders[shoes]</b> \n";
            $content .= "Цена за обувь: <b>$orders[price_shoes] руб</b> \n\n";
        }
        if (trim($orders['bed_linen'])) {
            $content .= "🛏Bed linen and towels: <b>$orders[bed_linen]</b> \n";
            $content .= "Price for bed linen and towels: <b>$orders[bed_linen_price] IDR</b> \n\n";
        }
        if (trim($orders['organic'])) {
            $content .= "🧑‍🚀Weight of Helmet:<b>$orders[organic]</b> \n";
            $content .= "Price of Helmet: <b>$orders[organic_price] IDR</b> \n\n";
        }


        if (trim($orders['wt'] || trim($orders['shoes']) || trim($orders['bed_linen']) || trim($orders['organic']))) {
            $content .= "💰Сумма заказа: <b>$orders[price] руб </b> \n";
        }

        if ($orders["bonus_payed"]) {
            $bonus_payed = number_format($orders["bonus_payed"], 0, "", ".");
            $customer_must_pay = number_format(str_replace(".", "", $orders["price"]) - $orders["bonus_payed"], 0, "", ".");
            $content .= "Оплачено бонусами: <b>$bonus_payed руб</b>\n";
            $content .= "Клиент должен еще оплатить: <b>$customer_must_pay руб</b>\n";
        }

        if (trim($orders['payment'])) {
            $content .= "Способ оплаты: <b>$payment </b>\n";
        }
        if (trim($orders['paid']) || $orders["payment"] == 4) {
            $content .= "Оплачено: <b>$paid </b>\n";
            $content .= "Заказ завершен 👍 \n";
        }
        if (trim($orders['otziv'])) {
            $content .= "Рейтинг: <b>$otziv </b>";
        }

        if (!$order_report) {
            $buttons[] = [
                $this->buildInlineKeyBoardButton("📥Отчет по заказу", "/orders_report $orders[id]"),
                $this->buildInlineKeyBoardButton("Написать пользователю", "/request_message_to_user $orders[chat_id]"),
            ];
        }

        switch ($orders['status']) {
            case 1:
                if ($orders['photo_before']) {
                    $send = $this->sendPhoto($chat, "https://laundrybot.online/GetLaundry/" . $orders["photo_before"], $content, $buttons);
                } else {
                    $send = $this->sendMessage($chat, $content, $buttons);
                }
                break;
            case 2:
                if ($orders['photo_before']) {
                    $send = $this->sendPhoto($chat, "https://laundrybot.online/GetLaundry/" . $orders["video_after_washing"], $content, $buttons);
                } else {
                    $send = $this->sendMessage($chat, $content, $buttons);
                }
                break;
            case 3:
                if ($orders['photo_on_the_scales']) {
                    $photo_array = json_decode($orders["photo_on_the_scales"], true);
                    $photo = $photo_array[0];
                    $send = $this->sendPhoto($chat, "https://laundrybot.online/GetLaundry/" . $photo['photo'], $content, $buttons);
                } else {
                    $send = $this->sendMessage($chat, $content, $buttons);
                }
                break;
            case 4:
                if ($orders['photo_on_the_scales']) {
                    $photo_array = json_decode($orders["photo_on_the_scales"], true);
                    $photo = $photo_array[0];
                    $send = $this->sendPhoto($chat, "https://laundrybot.online/GetLaundry/" . $photo['photo'], $content, $buttons);
                } else {
                    $send = $this->sendMessage($chat, $content, $buttons);
                }
                break;
            case 5:
                if ($orders['delivered_photo']) {
                    $photo_array = json_decode($orders["delivered_photo"], true);
                    $photo = $photo_array[0];
                    $send = $this->sendPhoto($chat, "https://laundrybot.online/GetLaundry/" . $photo['photo'], $content, $buttons);
                } else {
                    $send = $this->sendMessage($chat, $content, $buttons);
                }
                break;
            case 6:
                if ($orders['delivered_photo']) {
                    $photo_array = json_decode($orders["delivered_photo"], true);
                    $photo = $photo_array[0];
                    $send = $this->sendPhoto($chat, "https://laundrybot.online/GetLaundry/" . $photo['photo'], $content, $buttons);
                } else {
                    $send = $this->sendMessage($chat, $content, $buttons);
                }
                break;
            default:
                $send = $this->sendMessage($chat, $content, $buttons);
                break;
        }

        $mess_id = $send['result']['message_id'];
        $set_orders = R::findOne('orders', "id = $orders[id]");
        $set_orders->mess_id = $mess_id;
        $set_orders->temp_chat_id = $chat;
        $set_orders["admin_message_id"] = $mess_id;
        R::store($set_orders);
    }


    function sendOrderWasher($ids_number, $username, $status, $order_report = False)
    {
        $order = R::findOne('orders', "id = $ids_number");
        $user = R::findOne('users', "chat_id = {$order["chat_id"]}");
        $photo = "photo_before";

        if (!$user['phone']) {
            $phone = $order['phone'];
        } else {
            $phone = $user['phone'];
        }

        if ($order['laundry_name']) {
            $laundry_name = $order['laundry_name'];
            $content = "<b>Laundry</b>: $laundry_name \n\n";
        }

        $content .= "✅ Заказ: <b>#{$order["id"]}</b>\n\n";

        $createdAtDate = date("d.m.Y", $order["timestamp_create"]);
        $createdAtTime = date("H:i", $order["timestamp_create"]);
        $content .= "Создан: <b>$createdAtDate at $createdAtTime</b>\n\n";

        $content .= "ID: <b>{$user["id"]}</b> \n";
        $content .= "Login telegram: <b>@$username</b>\n";
        $content .= "Количество заказов: <b>{$user["orders_count"]}</b> \n";
        $content .= "Номер telegram: <b>$phone</b>\n";

        $content .= "📍Локация: https://www.google.com/maps/place/{$order["maps"]}\n";
        $content .= "Детали места: <b>{$order["address_2"]}</b>\n";
        if (trim($order['time_start'])) {
            $time_start = date('d.m.Y H:i', $order['time_start']);
            $content .= "📥Курьер забрал вещи у клиента: <b>$time_start</b>\n";
        }
        if (trim($order['in_laundry'])) {
            $time_start = $order['in_laundry'];
            $content .= "🗺Курьер доставил вещи в лаундри: <b>$time_start</b>\n";
        }
        if (trim($order['washing_started'])) {
            $time_start = $order['washing_started'];
            $content .= "🧼Начали стирку: <b>$time_start</b>\n";
        }
        if (trim($order['washed'])) {
            $time_start = $order['washed'];
            $content .= "💪Закончили стирку: <b>$time_start</b>\n";
        }

        if ($status == 0) {
            $photo = "photo_in_laundry";
            $buttons[] = [
                $this->buildInlineKeyBoardButton("Фото перед стиркой", "/order_washer_group_video_before $ids_number"),
            ];
        } elseif ($status == 1) {
            $photo = "video_before_washing";
            $buttons[] = [
                $this->buildInlineKeyBoardButton("Фото после стирки", "/order_washer_group_video_after $ids_number"),
            ];
        } elseif ($status == 2) {
            $photo = "video_after_washing";
            $buttons[] = [
                $this->buildInlineKeyBoardButton("Готово к взвешиванию", "/order_send_courier_group $ids_number"),
            ];
        }


        $buttons[] = [
            $this->buildInlineKeyBoardButton("📥Отчет по заказу", "/orders_report $order[id]"),
            $this->buildInlineKeyBoardButton("Написать пользователю", "/request_message_to_user $order[chat_id]")
        ];

        # Отправить смс
        $response = $this->sendPhoto(GROUP_WASHERS_CHAT_ID, "https://laundrybot.online/GetLaundry/" . $order[$photo], $content, $buttons);

        $set_order = R::findOne('orders', "id = {$order["id"]}");
        $set_order["courier_group_message_id"] = $response['result']['message_id'];
        R::store($set_order);
    }

    function ban($chat_id)
    {
        $users = R::findOne('users', 'chat_id = ?', [$chat_id]);

        if ($users['ban'] == 2) {
            $this->sendMessage($chat_id, "<b>Вы заблокированы.</b>");
            exit;
        }
    }

    function getUrl($sum, $user_id, $order_id = "Личный кабинет")
    {
        return "https://money.yandex.ru/quickpay/confirm.xml?receiver=" . YANDEX_MONEY
            . "&quickpay-form=shop&targets=" . urlencode(NAME_SHOP)
            . "&paymentType=AC&sum=" . $sum
            . "&label=" . $user_id . ":" . $order_id . ":" . md5(rand(0, 1000))
            . "&comment=" . urlencode("Оплата заказа #" . $order_id)
            . "&successURL=" . PAY_SUCCESS;
    }

    function set_log_oplata($chat_id, $money, $order_id)
    {
        $time = strtotime(date("d.m.Y H:i")); # перевод время в UNIX

        $params_q = R::findOne('logoplata', 'ORDER BY id DESC');

        $con = $params_q->number;
        $t = $con + 1;

        $log_oplata = R::dispense('logoplata');
        $log_oplata->user_id = $chat_id;
        $log_oplata->number = $t;
        $log_oplata->money = "$money.00";
        $log_oplata->order_id = $order_id;
        $log_oplata->data = $time;
        $log_oplata->status = 0;
        R::store($log_oplata);

        return $t;
    }

    # Проверка на админа
    public function isAdmin($chat_id)
    {
        $users_admin = R::findOne('users', 'chat_id = ?', [$chat_id]);

        if ($users_admin['status'] == "0" || $users_admin['status'] == "1") {
            return exit;
        }
    }

    # Добавляем
    public function get_orders($ids, $command, $text)
    {
        $seach_orders = R::findOne('orders', "id = $ids");

        if (!$seach_orders) {
            $set_orders = R::dispense('orders');
            $set_orders->chat_id = " ";
            $set_orders->maps = " ";
            $set_orders->phone = " ";
            $set_orders->about_p = " ";
            $set_orders->time_start = " ";
            $set_orders->time_end = " ";
            $set_orders->otziv = 0;
            $set_orders->comments = " ";
            $set_orders->status = 0;
            $get_id = R::store($set_orders);
        } else {

            $orders = R::findOne('orders', "id = $ids");

            if ($command == "chat_id") {
                $orders->chat_id = $text;
            } else if ($command == "maps") {
                $orders->maps = $text;
            } else if ($command == "phone") {
                $orders->phone = $text;
            } else if ($command == "about_p") {
                $orders->about_p = $text;
            } else if ($command == "time_start") {
                $orders->time_start = $text;
            } else if ($command == "time_end") {
                $orders->time_end = $text;
            } else if ($command == "otziv") {
                $orders->otziv = $text;
            } else if ($command == "comments") {
                $orders->comments = $text;
            } else if ($command == "status") {
                $orders->status = $text;
            }
            R::store($orders);

        }

        return $get_id;
    }


    # 1 Регистрирует
    function reg_customer($chat_id)
    {
        $time = strtotime(date("d.m.Y H:i")); # перевод время в UNIX

        $params_q = R::findOne('orders', 'ORDER BY id DESC');

        $con = $params_q->number;
        $t = $con + 1;

        # Создаем запись заявки в бд
        $orders = R::dispense("orders");
        $orders->number = $t;
        $orders->chat_id = $chat_id;
        $orders->address_n = "unknown";
        $orders->address = "unknown";
        $orders->people = "unknown";
        $orders->time_a = "unknown";
        $orders->money = "unknown";
        $orders->comment = "unknown";
        $orders->data_reg = $time;
        $orders->status = "0";

        R::store($orders);


        return $t;
    }


    # Проверка и подключение к бд
    function dbconnect($mysql_status, $mysql_ip, $mysql_dbname, $mysql_dbuser, $mysql_password)
    {
        if ($mysql_status == 1) {
            $rb = R::setup("mysql:host=$mysql_ip;dbname=$mysql_dbname", $mysql_dbuser, $mysql_password);

            return 1;
        }
    }

    function action($text, $action)
    {
        if ($text[0] == "/") {
            if ($text == $action) {
                return true;
            }
        } else if (preg_match("/^$action/", $text)) {
            return true;
        }
        return false;
    }

    # Удаляем команду пользователя
    function del_action($chat_id)
    {
        if (file_exists(__DIR__ . '/action/' . $chat_id . '.txt')) {
            $data = unlink(__DIR__ . '/action/' . $chat_id . '.txt');
            return $data;
        } else {
            return '';
        }
    }

    # Записываем команду пользователя
    function set_action($chat_id, $data)
    {
        file_put_contents(__DIR__ . '/action/' . $chat_id . '.txt', $data);
    }

    # Получаем команду пользователя из файла
    function get_action($chat_id)
    {
        if (file_exists(__DIR__ . '/action/' . $chat_id . '.txt')) {
            $data = file_get_contents(__DIR__ . '/action/' . $chat_id . '.txt');
            return $data;
        } else {
            return '';
        }
    }

    function setserver($command)
    {
        $response = file_get_contents('https://sshop-m.ru/api/key/' . TOKEN_CS . '/action/' . $command);
        return $obj = json_decode($response, true);
    }

    /** кнопка клавиатуры номер телефона и геолокация
     * @param $text
     * @param bool $request_contact
     * @param bool $request_location
     * @return array
     * Пример:
     * $buttons_phone[] = [
     * $this->buildKeyboardButton("☎️ Отправить номер"),
     * ];
     * $this->sendMessage($chat_id, "Отправьте номер телефона", $buttons_phone, 0);
     */
    function buildKeyboardButton($text, $request_contact = false, $request_location = true)
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

    function ReplyKeyboardRemove()
    {
        // собираем кнопки
        $replyMarkup = [
            'remove_keyboard' => true,
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
            #$this->sendMessage(2136511333, "fvdfv $params ");
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
            'parse_mode' => 'html',
            'disable_web_page_preview' => 'true'
        ];

        // Если переданы кнопки, то добавляем их к сообщению
        if (!is_null($buttons) && is_array($buttons)) {
            if ($params == 1) {
                $content['reply_markup'] = $this->buildInlineKeyBoard($buttons);
            } else if ($params == 2) {
                $content['reply_markup'] = $this->ReplyKeyboardRemove();
            } else {
                $content['reply_markup'] = $this->buildKeyBoard($buttons);
            }
        }

        return $this->requestToTelegram($content, "sendMessage");
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

        // Если переданы кнопки, то добавляем их к сообщению
        if (!is_null($buttons) && is_array($buttons)) {
            if ($params == 1) {
                $content['reply_markup'] = $this->buildInlineKeyBoard($buttons);
            } else {
                $content['reply_markup'] = $this->buildKeyBoard($buttons);
            }
        }

        return $this->requestToTelegram($content, "editMessageText");
    }


    function editPhoto($chat_id, $message_id, $text, $buttons = NULL, $params = 1)
    {
        $content = [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'caption' => $text,
            'parse_mode' => 'html'
        ];
        // Если переданы кнопки, то добавляем их к сообщению
        if (!is_null($buttons) && is_array($buttons)) {
            if ($params == 1) {
                $content['reply_markup'] = $this->buildInlineKeyBoard($buttons);
            } else {
                $content['reply_markup'] = $this->buildKeyBoard($buttons);
            }
        }

        return $this->requestToTelegram($content, "editMessageMedia");
    }


    /*
     Редактирует только кнопки у сообщения
        * $chat_id - ID пользователя
        * $message_id - ID сообщения
        * $params - 1 = нахождение кнопки вместе с текстом. 0 = клавиатура снизу
    Пример:
    $buttons[] = [
        $this->buildInlineKeyBoardButton("Ваш текст на кнопке", "команда кнопки]"),
    ];
        $this->editMessageReplyMarkup($chat_id, $message_id, $buttons);
    */
    private function editMessageReplyMarkup($chat_id, $message_id, $buttons = NULL, $params = 1)
    {
        $content = [
            'chat_id' => $chat_id,
            'message_id' => $message_id
        ];
        // Если переданы кнопки, то добавляем их к сообщению
        if (!is_null($buttons) && is_array($buttons)) {
            if ($params == 1) {
                $content['reply_markup'] = $this->buildInlineKeyBoard($buttons);
            } else {
                $content['reply_markup'] = $this->buildKeyBoard($buttons);
            }
        }
        return $this->requestToTelegram($content, "editMessageReplyMarkup");
    }

    /*
    Удаляет сообщение
    * $chat_id - ID пользователя
    * $message_id - ID сообщения

    Пример:
        $this->DelMessageText($chat_id, $message_id);
    */
    function DelMessageText($chat_id, $message_id)
    {
        // готовим данные
        $content = [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
        ];
        // отправляем запрос на удаление
        $this->requestToTelegram($content, "deleteMessage");
    }


    /* Ответ на сообщение по ID
        * $chat_id - ID пользователя
        * $replyid - ID сообщения
        * $text - Ваш текст
        * $buttons - название кнопки
        * $params - 1 = нахождение кнопки вместе с текстом. 0 = клавиатура снизу

        Пример:
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Кнопка", "команда кнопки"),
        ];
        $this->sendMessageForce($chat_id, ID-смс, "Текст", $buttons);
    */
    private function sendMessageForce($chat_id, $replyid, $text, $buttons = NULL, $params = 1)
    {
        $content = [
            'chat_id' => $chat_id,
            'reply_to_message_id' => $replyid,
            'text' => $text,
            'reply_markup' => json_encode(['force_reply' => true], ['selective' => '2']),
            'parse_mode' => 'Markdown'
        ];
        // Если переданы кнопки, то добавляем их к сообщению
        if (!is_null($buttons) && is_array($buttons)) {
            if ($params == 1) {
                $content['reply_markup'] = $this->buildInlineKeyBoard($buttons);
            } else {
                $content['reply_markup'] = $this->buildKeyBoard($buttons);
            }
        }

        return $send = $this->requestToTelegram($content, "sendMessage");
    }

    /* Push-уведомления
        * $callback_query_id - оставляем также.
        * $text
        * Работает только с кнопками (нажимать на кнопку)
        Пример:
        $this->answerCallbackQuery($callback_query_id, "Ваш текст");
    */
    private function answerCallbackQuery($callback_query_id, $text)
    {
        $content = [
            'callback_query_id' => $callback_query_id,
            'text' => $text,
            'cache_time' => 3,
        ];
        // отправляем запрос
        $this->requestToTelegram($content, "answerCallbackQuery");

    }

    private function sendPhoto($chat_id, $photo, $caption, $buttons = NULL)
    {
        $content = [
            'chat_id' => $chat_id,
            'photo' => $photo,
            'caption' => $caption,
            'parse_mode' => 'html',
        ];

        // Если переданы кнопки, то добавляем их к сообщению
        if (!is_null($buttons) && is_array($buttons)) {
            $content['reply_markup'] = $this->buildInlineKeyBoard($buttons);
        }

        return $send = $this->requestToTelegram($content, "sendPhoto");
    }

    private function sendVideo($chat_id, $video, $caption, $buttons = NULL)
    {
        $content = [
            'chat_id' => $chat_id,
            'video' => $video,
            'caption' => $caption,
            'parse_mode' => 'html',
        ];

        // Если переданы кнопки, то добавляем их к сообщению
        if (!is_null($buttons) && is_array($buttons)) {
            $content['reply_markup'] = $this->buildInlineKeyBoard($buttons);
        }

        return $send = $this->requestToTelegram($content, "sendVideo");
    }

    // общая функция загрузки картинки
    private function getPhoto($data, $chat_id, $photo_caption, $id_number)
    {
        // берем последнюю картинку в массиве
        $file_id = $data[count($data) - 1]['file_id'];
        // получаем file_path
        $file_path = $this->getPhotoPath($file_id);
        // возвращаем результат загрузки фото
        return $this->copyPhoto($file_path, $chat_id, $photo_caption, $id_number);
    }

    // функция получения метонахождения файла
    private function getPhotoPath($file_id)
    {
        // получаем объект File
        $array = $this->requestToTelegram(['file_id' => $file_id], "getFile");
        // возвращаем file_path
        return $array['result']['file_path'];
    }

    // копируем фото к себе
    function copyPhoto($file_path, $caption, $chat_id, $ids_orders)
    {

        # ссылка на файл в телеграме
        $file_from_tgrm = "https://api.telegram.org/file/bot" . TOKEN . "/" . $file_path;
        # достаем расширение файла
        $ext = end(explode(".", $file_path));
        # назначаем свое имя здесь время_в_секундах.расширение_файла
        $rand = rand(1, 9999999);
        $name_our_new_file = $rand . "." . $ext;

        if (!file_exists("img/orders/$ids_orders")) {
            mkdir("img/orders/$ids_orders", 0777, true);
        }

        # Считаем кол-во загруженных картинок
        $dir = opendir("img/orders/$ids_orders");
        $count = 0;
        while ($file = readdir($dir)) {
            if ($file == '.' || $file == '..' || is_dir("img/orders/$ids_orders" . $file)) {
                continue;
            }
            $count++;
        }

        if ($count >= 10) {
            return;
        } else {
            # Копируем картинки на фтп
            $r = copy($file_from_tgrm, "img/orders/$ids_orders/" . $name_our_new_file);
        }
    }


    // функция получения метонахождения файла
    function getPhotoPath1($file_id)
    {
        // получаем объект File
        $array = $this->requestToTelegram(['file_id' => $file_id], "getFile");
        // возвращаем file_path
        return $array['result']['file_path'];
    }

    function getPhoto1($data, $caption, $chat_id, $ids_orders)
    {
        // берем последнюю картинку в массиве
        //$file_id = $data[count($data) - 1]['file_id'];
        // получаем file_path
        $file_path = $this->getPhotoPath1($data);
        // возвращаем результат загрузки фото

        return $this->copyPhoto1($file_path, $caption, $chat_id, $ids_orders);
    }


    // копируем фото к себе
    function copyPhoto1($file_path, $chat_id, $caption, $ids_orders)
    {
        # ссылка на файл в телеграме
        $file_from_tgrm = "https://api.telegram.org/file/bot" . TOKEN . "/" . $file_path;
        # достаем расширение файла
        $ext = end(explode(".", $file_path));
        # назначаем свое имя здесь время_в_секундах.расширение_файла
        $rand = rand(1, 9999999);
        $name_our_new_file = $rand . "." . $ext;

        if (!file_exists("img/orders/video_$ids_orders")) {
            mkdir("img/orders/video_$ids_orders", 0777, true);
        }

        # Считаем кол-во загруженных
        $dir = opendir("img/orders/video_$ids_orders");
        $count = 0;
        while ($file = readdir($dir)) {
            if ($file == '.' || $file == '..' || is_dir("img/orders/video_$ids_orders" . $file)) {
                continue;
            }
            $count++;
        }

        if ($count >= 10) {
            return;
        } else {
            # Копируем картинки на фтп
            $r = copy($file_from_tgrm, "img/orders/video_$ids_orders/" . $name_our_new_file);
        }
        # $this->sendMessage($chat_id, "$file_path | $name_our_new_file");
    }

    function gen_uuid()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    function UrlYookassa($chat_id, $number, $rub)
    {
        $data = array(
            'amount' => array(
                'value' => $rub,
                'currency' => 'RUB',
            ),
            'capture' => true,
            'confirmation' => array(
                'type' => 'redirect',
                'return_url' => 'https://t.me/devshoptaksi_bot',
            ),
            'description' => "$chat_id",
            'metadata' => array(
                'order_id' => $number,
            )
        );

        $data = json_encode($data, JSON_UNESCAPED_UNICODE);

        $ch = curl_init('https://api.yookassa.ru/v3/payments');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_USERPWD, '915243:test_xcHNs0NxJUoSdC4b9f9hQau5FmxpgZHUj9iwn1QK49g');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Idempotence-Key: ' . $this->gen_uuid()));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $res = curl_exec($ch);
        curl_close($ch);

        $res = json_decode($res, true);
        return $res['confirmation']['confirmation_url'];

    }

    function GeoMaps($x, $y)
    {
        $parameters = array(
            'appid' => "479002a52d8ce84289974ff185e353e3",
            'lat' => "$x",
            'lon' => "$y",
            'limit' => '5'
        );
        $response = file_get_contents('https://api.openweathermap.org/geo/1.0/reverse?' . http_build_query($parameters));
        return $obj = json_decode($response, true);

    }


    private function setGeoMaps($x, $y)
    {
        $parameters = array(
            //'apikey' => GEO_TOKEN,
            'geocode' => "$x,$y",
            //'format' => 'json'
        );

        $response = file_get_contents('https://geocode-maps.yandex.ru/1.x/?format=json&apikey=' . GEO_TOKEN . '&' . http_build_query($parameters));
        return $obj = json_decode($response, true);

    }

    function getMe()
    {
        $response = file_get_contents('https://api.telegram.org/bot' . TOKEN . '/getMe');
        return $obj = json_decode($response, true);
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


    /* Функция записи логов в фай /log.txt */
    private function setFileLog($data)
    {
        $fh = fopen('log.txt', 'a') or die('can\'t open file');
        ((is_array($data)) || (is_object($data))) ? fwrite($fh, print_r($data, TRUE) . "\n") : fwrite($fh, $data . "\n");
        fclose($fh);
    }

    private function _loadTemplate($templateName)
    {
        $templateText = file_get_contents(__DIR__ . "/modules/templates/$templateName.txt");
        $templateText = str_replace("\n", "", $templateText);
        $templateText = str_replace("<:n>", "\n", $templateText);

        return $templateText;
    }

    private function saveFile($data, $user)
    {
        $fileId = $data["message"]["photo"][count($data["message"]["photo"]) - 1]["file_id"];
        $response = $this->requestToTelegram(["file_id" => $fileId], "getFile");
        if (!$response["ok"]) return;

        $filePath = $response['result']['file_path'];
        # ссылка на файл в телеграме
        $fileFromTelegram = "https://api.telegram.org/file/bot" . TOKEN . "/" . $filePath;
        $explodedFilePath = explode(".", $filePath);
        $fileExtension = end($explodedFilePath);
        if (!file_exists("img/orders/" . $user["id"])) {
            mkdir("img/orders/" . $user["id"], 0777, true);
        }

        $newFileName = time() . "." . $fileExtension;
        copy($fileFromTelegram, "img/orders/{$user["id"]}/$newFileName");

        return "img/orders/{$user["id"]}/$newFileName";
    }

    private function saveFileGroup($data, $order)
    {
        $fileId = $data["message"]["photo"][count($data["message"]["photo"]) - 1]["file_id"];
        $response = $this->requestToTelegram(["file_id" => $fileId], "getFile");
        if (!$response["ok"]) return;

        $filePath = $response['result']['file_path'];
        # ссылка на файл в телеграме
        $fileFromTelegram = "https://api.telegram.org/file/bot" . TOKEN . "/" . $filePath;
        $explodedFilePath = explode(".", $filePath);
        $fileExtension = end($explodedFilePath);
        if (!file_exists("img/orders_group/" . $order["id"])) {
            mkdir("img/orders_group/" . $order["id"], 0777, true);
        }

        $newFileName = "photo-" . rand(1, 999999) . "-" . time() . "." . $fileExtension;
        copy($fileFromTelegram, "img/orders_group/{$order["id"]}/$newFileName");

        return "img/orders_group/{$order["id"]}/$newFileName";
    }

    private function saveVideoGroup($data, $order)
    {
        $fileId = $data["message"]["video"]["file_id"];
        $response = $this->requestToTelegram(["file_id" => $fileId], "getFile");
        if (!$response["ok"]) return;

        $filePath = $response['result']['file_path'];
        # ссылка на файл в телеграме
        $fileFromTelegram = "https://api.telegram.org/file/bot" . TOKEN . "/" . $filePath;
        $explodedFilePath = explode(".", $filePath);
        $fileExtension = end($explodedFilePath);
        if (!file_exists("img/orders_group/" . $order["id"])) {
            mkdir("img/orders_group/" . $order["id"], 0777, true);
        }

        $newFileName = "video-" . time() . "." . mb_strtolower($fileExtension, "UTF-8");
        copy($fileFromTelegram, "img/orders_group/{$order["id"]}/$newFileName");

        return "img/orders_group/{$order["id"]}/$newFileName";
    }

    private function getFileId($data)
    {
        return $data["message"]["photo"][count($data["message"]["photo"]) - 1]["file_id"];
    }


    private function sendOnlyPhoto(int $chat_id, string $photo, string $caption = null, array $buttons = null)
    {
        $content = [
            'chat_id' => $chat_id,
            'photo' => $photo,
            'parse_mode' => 'html',
        ];

        if (!is_null($caption)) {
            $content['caption'] = $caption;
        }
        if (!is_null($buttons) && is_array($buttons)) {
            $content['reply_markup'] = $this->buildInlineKeyBoard($buttons);
        }

        return $this->requestToTelegram($content, "sendPhoto");
    }


    private function editMessageCaption(array $data): Bot
    {
        $request = [
            'chat_id' => $data['chat_id'],
            'message_id' => $data['message_id'],
            'caption' => $data['caption'],
            'reply_markup' => $data['reply_markup'],
            'parse_mode' => 'html'
        ];

        return $this->requestToTelegram($request, 'editMessageCaption');
    }
}


/*
	Получение данных исходящее запроса телеграмм
	$send = $this->sendMessage($chat_id, "Ваше сообщение");
	$send['result']['message_id'];
	$send['result']['chat']['id'];
*/

/*
	Помощь по БД запросам

	 *** Create (Создание записи) ***
	// Указываем, что будем работать с таблицей book
	$book = R::dispense('book');
	// Заполняем объект свойствами
	$book->title = "ваш текст";
	// Сохраняем объект
	R::store($book);

	 *** (Поиск и вывод данных) ***
	$book = R::findOne('test', 'chat_id = :chat_id', [':chat_id' => $chat_id]);
	$title = $book->title;

	 *** (Поиск и вывод данных несколько) ***
	$book = R::findAll('test', 'chat_id = :chat_id', [':chat_id' => $chat_id]);

	foreach($book as $book_all){
		$book_all['title'];
	}

	 *** (Обновление записи) ***
	// Загружаем объект
	$book = R::findOne('test', 'chat_id = :chat_id', [':chat_id' => $chat_id]);
	// Обращаемся к свойству объекта и назначаем ему новое значение
	$book->title = "ваш текст";
	// Сохраняем объект
	R::store($book);


	 *** (Удаляет запись) ***
	$item = R::findOne('test', 'chat_id = :chat_id', [':chat_id' => $chat_id]);
    // возвращаем результат запроса
	R::trash($item);


	 *** (Кол-во записей) ***
	// Сколько записей (элементов) в таблице book
	$books = R::count('book');

	// Сколько записей (элементов) в таблице book, у которых поле status = 1
	$status = 1;
	$books = R::count('book', 'status = :status', [':status' => $status]);

*/