<?php

// TEST CHAT ID
const TEST_CHAT_ID = "880886695";

if (1 > $chat_id) {
    if ($atext[0] == "/mailing_v2") {
        $this->DelMessageText($chat_id, $message_id);

        $template = new Template("mailing_v2");
        $templateData = $template->Load();

        $mailing = R::dispense("usermailing");
        $mailing["type"] = 4; // вторая версия рассылки
        R::store($mailing);

        $contentAdmin = "Рассылка (<b>#{$mailing["id"]}</b>):\n\n";
        $contentAdmin .= "Текст рассылки:\n";
        $contentAdmin .= "$templateData->text\n\n";
        $contentAdmin .= "Кнопки рассылки:\n";
        foreach ($templateData->buttons as $key => $button) {
            $contentAdmin .= "<b>{$button->GetText()}</b>\n";
        }

        $buttonsAdmin = [
            [
                $this->buildInlineKeyBoardButton("Начать рассылку", "/mailing_success {$mailing["id"]} $atext[1]"),
            ],
            [
                $this->buildInlineKeyBoardButton("Отменить", "/mailing_deny {$mailing["id"]}"),
            ],
        ];

        // отправляю сообщение админу и записываю message_id в рассылку
        $send = $this->sendMessage($chat_id, $contentAdmin, $buttonsAdmin);

        // сохраняю рассылку в базу данных
        $message_id = $send['result']['message_id'];
        $mailing["message_id"] = $message_id;
        $mailing["timestamp_start"] = time();
        $mailing["buttons"] = json_encode($templateData->buttons);
        $mailing["content"] = $templateData->text;
        R::store($mailing);

        return;
    }

    if ($atext[0] == "/mailing_success" && $atext[1]) {
        $mailing_id = (int)$atext[1];
        $mailing = R::findOne("usermailing", "id = $mailing_id");

        $this->DelMessageText($chat_id, $mailing["message_id"]);

        $content_admin = "Рассылка (<b>#{$mailing["id"]}</b>) запущена!";

        $buttons_admin = [
            [
                $this->buildInlineKeyBoardButton("Посмотреть ответы", "/usermailing_check_answers {$mailing["id"]}"),
            ],
        ];

        $send = $this->sendMessage($chat_id, $content_admin, $buttons_admin);
        $message_id = $send['result']['message_id'];
        $mailing["message_id"] = $message_id;
        R::store($mailing);

        $template = new Template("mailing_v2");
        $template = $template->Load();

        foreach ($template->buttons as $key => $button) {
            $template->buttons[$key]->SetMailingId($mailing["id"]);
            $template->buttons[$key] = $template->buttons[$key]->PrepareToSend();

        }

//        $users = $atext[2] == 1 ? $users = R::findAll('users', "chat_id = " . TEST_CHAT_ID. " AND chat_id = 331381609") : R::findAll('users', "status = 1 AND chat_id > 0");
        $users = $atext[2] == 1 ? $users = R::findAll('users', "chat_id IN ('".TEST_CHAT_ID."', '331381609')") : R::findAll('users', "status = 1 AND chat_id > 0");
        //  . " AND chat_id = 331381609"
        $sendMessagesArray = [];

        foreach ($users as $user) {
            sleep(1);
            $response = $this->sendMessage($user["chat_id"], $template->text, $template->buttons);
            if (!$response["ok"]) {
                file_put_contents("mailing_logs/{$mailing["id"]}_error_log.txt", json_encode($response) . PHP_EOL, FILE_APPEND);
            }/* else {
                file_put_contents("mailing_logs/{$mailing["id"]}_success_log.txt", json_encode($response) . PHP_EOL, FILE_APPEND);
            }*/

            $sendMessagesArray[] = [
                "chat_id" => $user["chat_id"],
                "message_id" => $response["result"]["message_id"],
            ];
        }

        return;
    }

    if ($atext[0] == "/mailing_deny" && $atext[1]) {
        $mailing_id = (int)$atext[1];
        $mailing = R::findOne("usermailing", "id = $mailing_id");

        $this->DelMessageText($chat_id, $mailing["message_id"]);

        $content_admin = "Рассылка (<b>#{$mailing["id"]}</b>) отменена!";

        $this->sendMessage($chat_id, $content_admin);

        R::trash($mailing);
    }

    if ($atext[0] == "/usermailing_check_answers" && $atext[1]) {
        $mailing_id = (int)$atext[1];

        // получаю админа по chat_id
        $user = R::findOne("users", "chat_id = $chat_id");

        // получаю список пользователей по id рассылки
        $res = R::findMulti("users,usermailinganswers", "SELECT users.*, usermailinganswers.* FROM users JOIN usermailinganswers ON usermailinganswers.user_id = users.id WHERE usermailinganswers.mailing_id = ?", [$mailing_id]);
        $users = $res["users"];
        $usermailinganswers = $res["usermailinganswers"];

        // формирую сообщения для админа
        $content_answers = "";

        $mailing = R::findOne("usermailing", "id = $mailing_id");

        $template = new Template("mailing_v2");
        $templateData = $template->Load();
        foreach ($users as $user) {
            $content_answers .= "User id: <b>{$user["id"]}</b>\n";
            $content_answers .= "User Login: <b>@{$user["username"]}</b>\n";
            foreach ($usermailinganswers as $usermailinganswer) {
                if ($usermailinganswer["user_id"] = $user["id"]) {
                    $answer = "";
                    foreach ($templateData->buttons as $button) {
                        if ($button->GetType() == Button::LinkType) continue;

                        if ($button->GetData() == $usermailinganswer["answer"]) {
                            $answer = $button->GetText();
                            break;
                        }
                    }

                    $content_answers .= "User Answer: <b>$answer</b>\n\n";
                    break;
                }
            }
        }

        $content = "Рассылка (<b>#{$mailing["id"]}</b>) Ответы:\n\n";
        $count_answers_all = count($usermailinganswers);
        $content .= "Всего ответов: <b>$count_answers_all</b>\n\n";
        $content .= $content_answers;

        // отправляю сообщение админу в личку
        $this->sendMessage($user["chat_id"], $content);

        return;
    }

    if ($atext[0] == '/mailing' && $atext[1]) {
        $mailing_type = (int)$atext[1];

        if (!in_array($mailing_type, [1, 2, 3])) {
            return;
        }

        $mailing = R::dispense("usermailing");
        $mailing["type"] = $mailing_type;
        R::store($mailing);

        $content_users = "";

        $content_admin = "Рассылка (<b>#{$mailing["id"]}</b>):\n\n";

        switch ($mailing_type) {
            case 1: // да нет не знаю
                $content_users = $this->_loadTemplate("mailing_1");

                // формирую кнопки пользователям
                $buttons_users = [
                    [
                        $this->buildInlineKeyBoardButton("Да, круто", "/usermailing_answer {$mailing["id"]} 1"),
                    ],
                    [
                        $this->buildInlineKeyBoardButton("Нет, не интересно", "/usermailing_answer {$mailing["id"]} 2"),
                    ],
                    [
                        $this->buildInlineKeyBoardButton("Супер, но дорого", "/usermailing_answer {$mailing["id"]} 3"),
                    ],
                ];

                break;
            case 2:
                // формирую сообщение пользователям
                $content_users = $this->_loadTemplate("mailing_2");

                $mailing["answers"] = $atext[2];

                // формирую кнопки пользователям
                $buttons_users = [];

                $users_buttons = "";
                // разделяю кнопки по ":"
                $buttons = explode(":", $atext[2]);
                foreach ($buttons as $button) {
                    // разделяю текст кнопки и её значение по ","
                    $button_data = explode(",", $button);
                    // Заменяю "_" на пробелы в тексте кнопки
                    $button_data[0] = str_replace("_", " ", $button_data[0]);

                    if ($button_data[2]) {
                        $buttons_users[] = [
                            $this->buildInlineKeyBoardButton($button_data[0], "", $button_data[2]),
                        ];
                    } else {
                        $users_buttons .= "$button_data[0]: <b>0</b>\n";

                        $buttons_users[] = [
                            $this->buildInlineKeyBoardButton($button_data[0], "/usermailing_answer {$mailing["id"]} $button_data[1]"),
                        ];
                    }
                }

                // добавляю кнопку "Написать менеджеру"
                $buttons_users[] = [
                    $this->buildInlineKeyBoardButton("Написать менеджеру", " ", "https://t.me/LaundryGoBot"),
                ];

                // формирую сообщение админу
                $content_admin .= $users_buttons;

                break;
            case 3:
                $content_users = $this->_loadTemplate("mailing_3");
                $answer_type = (int)$atext[2];

                if (!in_array($answer_type, [1, 2, 3])) {
                    return;
                }

                $mailing["answer_type"] = $answer_type;

                switch ($answer_type) {
                    case 1:
                        $buttons_users = [
                            [
                                $this->buildInlineKeyBoardButton("Заказать стирку", "/start 1"),
                            ],
                        ];
                        break;
                    case 3:
                        $buttons_users = [
                            [
                                $this->buildInlineKeyBoardButton("Узнать подробнее", "", "https://t.me/LaundryRussia_bot"),
                            ],
                        ];
                        break;
                }
                break;
        }

        $content_admin .= "Текст рассылки:\n";
        $content_admin .= "$content_users\n\n";
        $content_admin .= "Кнопки рассылки:\n";
        foreach ($buttons_users as $buttons_user) {
            $content_admin .= "<b>{$buttons_user[0]["text"]}</b>\n";
        }

        $buttons_admin = [
            [
                $this->buildInlineKeyBoardButton("Начать рассылку", "/mailing_success {$mailing["id"]}"),
            ],
            [
                $this->buildInlineKeyBoardButton("Отменить", "/mailing_deny {$mailing["id"]}"),
            ],
        ];

        // отправляю сообщение админу и записываю message_id в рассылку
        $send = $this->sendMessage($chat_id, $content_admin, $buttons_admin);
        $message_id = $send['result']['message_id'];
        $mailing["message_id"] = $message_id;
        $mailing["timestamp_start"] = time();
        if ($mailing["type"] != 3) {
            $mailing["buttons"] = json_encode($buttons_users);
        }

        $mailing["content"] = $content_users;
        R::store($mailing);

        $this->set_action($chat_id, "mailing");

        return;
    }
}