<?php

if ($atext[0] == '/2') {
    $buttons[] = [
        $this->buildInlineKeyBoardButton("Отмена", "/send_user_message_deny"),
    ];
    $response = $this->sendMessage($chat_id, "Введите ID клиента", $buttons);
    $msg_id = $response['result']['message_id'];
    $this->set_action($chat_id, "request_user_id&$msg_id");
    return;
}

if ($text && $get_action[0] == "request_user_id") {
    $this->DelMessageText($chat_id, $get_action[1]);
    $this->DelMessageText($chat_id, $message_id);
    $this->del_action($chat_id);
    $user = R::findOne("users", "id = $atext[0]");
    $chat = $user['chat_id'];
    if (isset($user)) {
        $buttons[] = [
            $this->buildInlineKeyBoardButton("Отмена", "/send_user_message_deny $user[id]"),
        ];
        $response = $this->sendMessage($chat_id, "Напишите сообщение пользователю $user[id]", $buttons);
        $msg_id = $response['result']['message_id'];
        $this->set_action($chat_id, "request_message_to_user&$chat&$msg_id");
    } else {
        $this->sendMessage($chat_id, 'Пользователь с заданным ID не найден.');
    }
    return;
}

if ($atext[0] == "/request_message_to_user") {
    $chat = (int)$atext[1];
    $user = R::findOne("users", "chat_id = $chat");
    unlink(__DIR__ . "/test_templates/templates/chat_with_user/$user[id]_message_text.txt");
    unlink(__DIR__ . "/test_templates/templates/chat_with_user/$user[id]_message_photo.txt");
    
    $buttons[] = [
        $this->buildInlineKeyBoardButton("Отмена", "/send_user_message_deny $user[id]"),
    ];
    $response = $this->sendMessage($chat_id, "Напишите сообщение пользоветелю ID $user[id]");
    $msg_id = $response['result']['message_id'];
    $this->set_action($chat_id, "request_message_to_user&$chat&$msg_id");

    if ($atext[2]){
        $this->DelMessageText($chat_id, $message_id);
    }
    return;
}


if (($text && !preg_match('/^\/(.*)$/', $atext[0]) && $get_action[0] == "request_message_to_user")) {
    $chat = (int)$get_action[1];
    $user = R::findOne("users", "chat_id = $chat");
    $user_chat = $user['chat_id'];
    $this->DelMessageText($chat_id, $get_action[2]);
    $this->DelMessageText($chat_id, $message_id);
    $this->del_action($chat_id);
    $message = implode(' ', $atext);
    $message = str_replace("\n", "<:n>", $message);
    file_put_contents(__DIR__ . "/test_templates/templates/chat_with_user/$user[id]_message_text.txt", $message);

    $buttons[] = [
        $this->buildInlineKeyBoardButton("Изменить", "/request_message_to_user $user_chat edit"),
        $this->buildInlineKeyBoardButton("Добавить кнопки", "/add_buttons_to_text_message $user_chat"),
        $this->buildInlineKeyBoardButton("Отправить", "/send_user_message_success $user_chat"),
        $this->buildInlineKeyBoardButton("Отмена", "/send_user_message_deny $user[id]"),
    ];
    $template = new Template("chat_with_user/{$user['id']}_message_text");
    $template = $template->Load();

    $send = $this->sendMessage($chat_id, $template->text, $buttons);

    return;
}

if ($data['message']['photo'] && $get_action[0] == "request_message_to_user") {
    $this->DelMessageText($chat_id, $get_action[2]);
    $this->DelMessageText($chat_id, $message_id);
    $this->del_action($chat_id);
    $chat = (int)$get_action[1];
    $user = R::findOne("users", "chat_id = $chat");
    $user_chat = $user['chat_id'];
    $photo = $this->getFileId($data);
    file_put_contents(__DIR__ . "/test_templates/templates/chat_with_user/$user[id]_message_photo.txt", $photo);
    $message = null;
    if ($data['message']['caption']) {
        $message = $data['message']['caption'];
        $message = str_replace("\n", "<:n>", $message);
        file_put_contents(__DIR__ . "/test_templates/templates/chat_with_user/$user[id]_message_text.txt", $message);
    }

    $buttons[] = [
        $this->buildInlineKeyBoardButton("Изменить", "/request_message_to_user $user_chat edit"),
        $this->buildInlineKeyBoardButton("Добавить кнопки", "/add_buttons_to_photo_message $user_chat"),
        $this->buildInlineKeyBoardButton("Отправить", "/send_user_message_success $user_chat"),
        $this->buildInlineKeyBoardButton("Отмена", "/send_user_message_deny $user[id]"),
    ];

    $template = new Template("chat_with_user/{$user['id']}_message_text");
    $template = $template->Load();

    $this->sendOnlyPhoto($chat_id, $photo, $template->text, $buttons);

    return;
}


if ($atext[0] == '/send_user_message_deny') {
    $this->del_action($chat_id);
    $this->DelMessageText($chat_id, $message_id);
    unlink(__DIR__ . "/test_templates/templates/chat_with_user/$atext[1]_message_text.txt");
    unlink(__DIR__ . "/test_templates/templates/chat_with_user/$atext[1]_message_photo.txt");
    return;
}

if ($atext[0] == '/add_buttons_deny') {
    $this->del_action($chat_id);
    $this->DelMessageText($chat_id, $message_id);
    return;
}

if ($atext[0] == '/add_buttons_to_photo_message') {
    $this->DelMessageText($chat_id, $message_id);
    $user_chat_id = $atext[1];
    $user = R::findOne("users", "chat_id = $user_chat_id");
    $text = file_get_contents(__DIR__ . "/test_templates/templates/chat_with_user/$user[id]_message_text.txt");

    $buttons = [];
    if (preg_match('#<:buttons>🧺Заказать стирку;button;\/start<\/:buttons>#', $text)) {
        $buttons[] = [ 
            $this->buildInlineKeyBoardButton('✅🧺Заказать стирку', "/add_buttons_choose create_order $user_chat_id photo")
        ];
    } else {
        $buttons[] = [ 
            $this->buildInlineKeyBoardButton('🧺Заказать стирку', "/add_buttons_choose create_order $user_chat_id photo")
        ];
    }

    if (preg_match('#<:buttons>👩🏻‍💻Чат с менеджером;link;https:\/\/t.me\/LaundryGoBot<\/:buttons>#', $text)){
        $buttons[] = [ 
            $this->buildInlineKeyBoardButton('✅👩🏻‍💻Чат с менеджером', "/add_buttons_choose chat_manager $user_chat_id photo"),
        ];
    } else {
        $buttons[] = [ 
            $this->buildInlineKeyBoardButton('👩🏻‍💻Чат с менеджером', "/add_buttons_choose chat_manager $user_chat_id photo"),
        ];
    }

    if (preg_match('#<:buttons>👫Рекомендовать друзьям;button;\/set_free_orders<\/:buttons>#', $text)){
        $buttons[] = [ 
            $this->buildInlineKeyBoardButton('✅👫Рекомендовать друзьям', "/add_buttons_choose recommend $user_chat_id photo"),
        ];
    } else {
        $buttons[] = [ 
            $this->buildInlineKeyBoardButton('👫Рекомендовать друзьям', "/add_buttons_choose recommend $user_chat_id photo"),
        ];
    }

    $buttons[] = [
        $this->buildInlineKeyBoardButton("Отмена", "/back_to_user_message_menu $user_chat_id photo"),
    ];

    $photo = file_get_contents(__DIR__ . "/test_templates/templates/chat_with_user/$user[id]_message_photo.txt");
    $send = $this->sendPhoto($chat_id, $photo, 'Выберите кнопки', $buttons);

    return;
}


if ($atext[0] == '/add_buttons_to_text_message') {
    $user_chat_id = $atext[1];
    $user = R::findOne("users", "chat_id = $user_chat_id");
    $text = file_get_contents(__DIR__ . "/test_templates/templates/chat_with_user/$user[id]_message_text.txt");
    
    $buttons = [];
    if (preg_match('#<:buttons>🧺Заказать стирку;button;\/start<\/:buttons>#', $text)) {
        $buttons[] = [ 
            $this->buildInlineKeyBoardButton('✅🧺Заказать стирку', "/add_buttons_choose create_order $user_chat_id text")
        ];
    } else {
        $buttons[] = [ 
            $this->buildInlineKeyBoardButton('🧺Заказать стирку', "/add_buttons_choose create_order $user_chat_id text")
        ];
    }

    if (preg_match('#<:buttons>👩🏻‍💻Чат с менеджером;link;https:\/\/t.me\/LaundryGoBot<\/:buttons>#', $text)){
        $buttons[] = [ 
            $this->buildInlineKeyBoardButton('✅👩🏻‍💻Чат с менеджером', "/add_buttons_choose chat_manager $user_chat_id text"),
        ];
    } else {
        $buttons[] = [ 
            $this->buildInlineKeyBoardButton('👩🏻‍💻Чат с менеджером', "/add_buttons_choose chat_manager $user_chat_id text"),
        ];
    }

    if (preg_match('#<:buttons>👫Рекомендовать друзьям;button;\/set_free_orders<\/:buttons>#', $text)){
        $buttons[] = [ 
            $this->buildInlineKeyBoardButton('✅👫Рекомендовать друзьям', "/add_buttons_choose recommend $user_chat_id text"),
        ];
    } else {
        $buttons[] = [ 
            $this->buildInlineKeyBoardButton('👫Рекомендовать друзьям', "/add_buttons_choose recommend $user_chat_id text"),
        ];
    }

    $buttons[] = [
        $this->buildInlineKeyBoardButton("Отмена", "/back_to_user_message_menu $user_chat_id text"),
    ];

    $this->editMessageText($chat_id, $message_id, 'Какие добавить кнопки?', $buttons);
    return;
}

if ($atext[0] == '/add_buttons_choose') {
    $user_chat_id = $atext[2];
    $user = R::findOne("users", "chat_id = $user_chat_id");
    $user_id = $user['id'];

    if ($atext[1]) {
        switch ($atext[1]) {
            case 'create_order':
                $message_text = file_get_contents(__DIR__ . "/test_templates/templates/chat_with_user/{$user_id}_message_text.txt");
                
                if (!preg_match('#<:buttons>🧺Заказать стирку;button;\/start<\/:buttons>#', $message_text)){
                    $message_text .= '<:buttons>🧺Заказать стирку;button;/start</:buttons>';
                    file_put_contents(__DIR__ . "/test_templates/templates/chat_with_user/{$user_id}_message_text.txt", $message_text);
                }

                $buttons[] = [
                    $this->buildInlineKeyBoardButton("Изменить", "/request_message_to_user $user[chat_id] edit"),
                    $this->buildInlineKeyBoardButton("Добавить кнопки", "/add_buttons_to_$atext[3]_message $user[chat_id]"),
                    $this->buildInlineKeyBoardButton("Отправить", "/send_user_message_success $user[chat_id]"),
                    $this->buildInlineKeyBoardButton("Отмена", "/send_user_message_deny $user[id]"),
                ];
                
                if (checkPhoto($user['id'])) {
                    $this->DelmessageText($chat_id, $message_id);
                    $photo = file_get_contents(__DIR__ . "/test_templates/templates/chat_with_user/{$user_id}_message_photo.txt");
                    $this->sendPhoto($chat_id, $photo, null, $buttons);
                } else {
                    $template = getUserMessageTemplate($user_id);
                    $this->editMessageText($chat_id, $message_id, $template->text, $buttons);
                }

                break;
            case 'chat_manager':
                $message_text = file_get_contents(__DIR__ . "/test_templates/templates/chat_with_user/{$user_id}_message_text.txt");
                if (!preg_match('#<:buttons>👩🏻‍💻Чат с менеджером;link;https:\/\/t.me\/LaundryGoBot<\/:buttons>#', $message_text)){
                    $message_text .= '<:buttons>👩🏻‍💻Чат с менеджером;link;https://t.me/LaundryGoBot</:buttons>';
                    file_put_contents(__DIR__ . "/test_templates/templates/chat_with_user/{$user_id}_message_text.txt", $message_text);
                }
                $buttons[] = [
                    $this->buildInlineKeyBoardButton("Изменить", "/request_message_to_user $user[chat_id] edit"),
                    $this->buildInlineKeyBoardButton("Добавить кнопки", "/add_buttons_to_$atext[3]_message $user[chat_id]"),
                    $this->buildInlineKeyBoardButton("Отправить", "/send_user_message_success $user[chat_id]"),
                    $this->buildInlineKeyBoardButton("Отменить", "/send_user_message_deny $user[id]"),
                ];

                if (checkPhoto($user['id'])) {
                    $this->DelmessageText($chat_id, $message_id);
                    $photo = file_get_contents(__DIR__ . "/test_templates/templates/chat_with_user/{$user_id}_message_photo.txt");
                    $this->sendPhoto($chat_id, $photo, null, $buttons);
                } else {
                    $template = getUserMessageTemplate($user_id);
                    $this->editMessageText($chat_id, $message_id, $template->text, $buttons);
                }
                break;
            case 'recommend':
                $message_text = file_get_contents(__DIR__ . "/test_templates/templates/chat_with_user/{$user_id}_message_text.txt");
                if (!preg_match('#<:buttons>👫Рекомендовать друзьям;button;\/set_free_orders<\/:buttons>#', $message_text)){
                    $message_text .= '<:buttons>👫Рекомендовать друзьям;button;/set_free_orders</:buttons>';
                    file_put_contents(__DIR__ . "/test_templates/templates/chat_with_user/{$user_id}_message_text.txt", $message_text);
                }
                $buttons[] = [
                    $this->buildInlineKeyBoardButton("Изменить", "/request_message_to_user $user[chat_id] edit"),
                    $this->buildInlineKeyBoardButton("Добавить кнопки", "/add_buttons_to_$atext[3]_message $user[chat_id]"),
                    $this->buildInlineKeyBoardButton("Отправить", "/send_user_message_success $user[chat_id]"),
                    $this->buildInlineKeyBoardButton("Отмена", "/send_user_message_deny $user[id]"),
                ];

                if (checkPhoto($user['id'])) {
                    $this->DelmessageText($chat_id, $message_id);
                    $photo = file_get_contents(__DIR__ . "/test_templates/templates/chat_with_user/{$user_id}_message_photo.txt");
                    $this->sendPhoto($chat_id, $photo, null, $buttons);
                } else {
                    $template = getUserMessageTemplate($user_id);
                    $this->editMessageText($chat_id, $message_id, $template->text, $buttons);
                }
                break;
        }
    }
    return;
}

if ($atext[0] == '/back_to_user_message_menu') {
    $this->DelMessageText($chat_id, $message_id);
    $user = R::findOne("users", "chat_id = $atext[1]");
    $photo = file_get_contents(__DIR__ . "/test_templates/templates/chat_with_user/$user[id]_message_photo.txt");
    $buttons[] = [
        $this->buildInlineKeyBoardButton("Изменить", "/request_message_to_user $user[chat_id] edit"),
        $this->buildInlineKeyBoardButton("Добавить кнопки", "/add_buttons_to_photo_message $user[chat_id]"),
        $this->buildInlineKeyBoardButton("Отправить", "/send_user_message_success $user[chat_id]"),
        $this->buildInlineKeyBoardButton("Отмена", "/send_user_message_deny $user[id]")
    ];
    $message = new Template("chat_with_user/{$user['id']}_message_text");
    $message = $message->Load();
    $message->LoadButtons();

    if ($atext[2] == 'text') {
        $this->sendMessage($chat_id, $message->text, $buttons);
    }
    if ($atext[2] == 'photo') {
        $this->sendOnlyPhoto($chat_id, $photo, null, $buttons);
    }
}


if ($atext[0] == '/send_user_message_success') {
    $this->DelMessageText($chat_id, $message_id);
    $chat = $atext[1];
    $user = R::findOne("users", "chat_id = $chat");
    $message = file_get_contents(__DIR__ . "/test_templates/templates/chat_with_user/$user[id]_message_text.txt");
    $photo = file_get_contents(__DIR__ . "/test_templates/templates/chat_with_user/$user[id]_message_photo.txt");
    $message = new Template("chat_with_user/{$user['id']}_message_text");
    $message = $message->Load();
    $message->LoadButtons();

    if ($message->text && $photo) {
        $this->sendPhoto($chat, $photo, $message->text, $message->buttons);
    } elseif ($photo && !$message->text) {
        $this->sendOnlyPhoto($chat, $photo, $message->buttons);
    } elseif ($message->text) {
        $this->sendMessage($chat, $message->text, $message->buttons);
    }

    unlink(__DIR__ . "/test_templates/templates/chat_with_user/$user[id]_message_text.txt");
    unlink(__DIR__ . "/test_templates/templates/chat_with_user/$user[id]_message_photo.txt");

    $this->sendMessage($chat_id, "Сообщение отправлено пользователю ID$user[id]");

    return;
}

function checkPhoto($user_id)
{
    return file_get_contents(__DIR__ . "/test_templates/templates/chat_with_user/{$user_id}_message_photo.txt");
}

function getUserMessageTemplate($user_id): Template
{
    $message = new Template("chat_with_user/{$user_id}_message_text");
    $message = $message->Load();
    $message->LoadButtons();

    return $message;
}

