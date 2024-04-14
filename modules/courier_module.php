<?php

if ($chat_id == COURIER_CHAT_ID) {
    // курьер подобрал заказ и должен прикрепить его фото
    if ($atext[0] == "/order_courier_pickup") {
        $orderId = (int)$atext[1];

        if (!$orderId) return;


        $order = R::findOne('orders', "id = $orderId");

        // Изменяю сообщение пользователю
        /*$template = new Template("pickup_order", [
            new TemplateData(":time", date("H:i", $order["timestamp_create"])),
            new TemplateData(":orderId", $orderId),
        ]);

        $template = $template->Load();
        $template->LoadButtons();

        $this->editMessageText($orders['chat_id'], $message_id, $template->text, $template->buttons);*/

        // Отправляю сообщение администратору
        $template = new Template("order/pickup/send_photo");
        $template = $template->Load();

        $this->DelMessageText($chat_id, $message_id);
        $this->sendMessage($chat_id, $template->text, $template->buttons);
        $this->set_action($chat_id, "pickup_order_send_photo&$orderId");
        return;
    }

    // курьер отправил фото заказа
    if (isset($data['message']['photo']) && $get_action[0] == "pickup_order_send_photo") {
        $orderId = (int)$get_action[1];

        if (!$orderId) {
            $this->del_action($chat_id);
            return;
        }

        $order = R::findOne("orders", "id = $orderId");
        $user = R::findOne("users", "chat_id = {$order["chat_id"]}");

        $newPhotoName = $this->saveFile($data, $user);

        $order["pickup_photo"] = $newPhotoName;
        R::store($order);

        $template = new Template("order/pickup/send_photo_confirmation", [
            new TemplateData(":orderId", $orderId),
        ]);

        $template = $template->Load();
        $template->LoadButtons();

//        https://laundrybot.online/bot/img/qr/$chat_id.png?version=" . time()
        $response = $this->sendPhoto($chat_id, "https://laundrybot.online/bot/" . $newPhotoName, $template->text, $template->buttons);

        return;
    }

    if ($atext[0] == "/order_pickup_order_send_photo_deny") {
        $orderId = (int)$atext[1];

        if (!$orderId) return;

        $template = new Template("order/pickup/send_photo");
        $template = $template->Load();

        $this->DelMessageText($chat_id, $message_id);
        $this->sendMessage($chat_id, $template->text, $template->buttons);
        $this->set_action($chat_id, "pickup_order_send_photo&$orderId");
    }

    if ($atext[0] == "/order_pickup_order_send_photo_success") {
        $orderId = (int)$atext[1];

        if (!$orderId) return;

        $this->del_action($chat_id);

        $this->DelMessageText($chat_id, $message_id);

        $order = R::findOne("orders", "id = $orderId");
        $user = R::findOne("users", "chat_id = {$order["chat_id"]}");

        $this->sendOrderCourier($orderId, $user["username"]);

        return;
    }
}