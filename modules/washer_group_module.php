<?php

if ($chat_id == GROUP_WASHERS_CHAT_ID) {
    if(isset($data['callback_query']['from']['id'])) @$get_action_group = explode("&", ($this->get_action($chat_id.'_'.$data['callback_query']['from']['id'])));
    else @$get_action_group = explode("&", ($this->get_action($chat_id.'_'.$data['message']['from']['id'])));

    if ($atext[0] == "/order_washer_group_video_before") {
        
        $orderId = (int)$atext[1];

        if (!$orderId) return;


        $order = R::findOne('orders', "id = $orderId");

        $user_chat_id = $data['callback_query']['from']['id'];

        $template = new Template("order/pickup/send_video_group", null, [
            new TemplateData(":orderId", $orderId)
        ]);
        $template = $template->Load();

        $response = $this->sendMessage($chat_id , $template->text, $template->buttons);
        $msg_id = $response['result']['message_id'];

        $this->set_action($chat_id.'_'.$user_chat_id, "pickup_order_send_video_group&$orderId&$msg_id");

        return;
    }

    if (isset($data['message']['photo']) && $get_action_group[0] == "pickup_order_send_video_group") {
        $orderId = (int)$get_action_group[1];
        $user_chat_id = $data['message']['from']['id'];

        if (!$orderId) {
            $this->del_action($chat_id."_".$user_chat_id);
            return;
        }

        $order = R::findOne("orders", "id = $orderId");

        $newPhotoName = $this->saveFileGroup($data, $order);

        $order["video_before_washing"] = $newPhotoName;
        R::store($order);

        $template = new Template("order/pickup/send_video_group_confirmation", null, [
            new TemplateData(":orderId", $orderId),
        ]);

        $template = $template->Load();
        $template->LoadButtons();

        $this->DelMessageText($chat_id, $get_action_group[2]);
        $this->DelMessageText($chat_id, $message_id);

        //$fileIdVideo = $data["message"]["photo"]["file_id"];

        $response = $this->sendPhoto($chat_id, "https://laundrybot.online/GetLaundry/" . $newPhotoName, $template->text, $template->buttons);
        $msg_id = $response['result']['message_id'];
        $this->set_action($chat_id.'_'.$user_chat_id, "pickup_order_send_video_group&$orderId&$msg_id");

        return;
    }

    if ($atext[0] == "/order_pickup_order_send_video_group_deny") {
        $orderId = (int)$atext[1];

        if (!$orderId) return;

        $order = R::findOne("orders", "id = $orderId");
        unlink($order['video_before_washing']);

        $template = new Template("order/pickup/send_video_group", null, [
            new TemplateData(":orderId", $orderId)
        ]);
        $template = $template->Load();

        $user_chat_id = $data['callback_query']['from']['id'];

        $this->DelMessageText($chat_id, $get_action_group[2]);
        $response = $this->sendMessage($chat_id, $template->text, $template->buttons);
        $msg_id = $response['result']['message_id'];
        $this->set_action($chat_id.'_'.$user_chat_id, "pickup_order_send_video_group&$orderId&$msg_id");
    }

    if ($atext[0] == "/order_pickup_order_send_video_group_success") {
        $orderId = (int)$atext[1];

        if (!$orderId) return;

        $user_chat_id = $data['callback_query']['from']['id'];

        $this->del_action($chat_id.'_'.$user_chat_id);

        $order = R::findOne("orders", "id = $orderId");
        $user = R::findOne("users", "chat_id = {$order["chat_id"]}");
        
        $order->washing_started = date("d.m.Y H:i");
        $order->washing_status = 1;
        R::store($order);

        $this->DelMessageText($chat_id, $get_action_group[2]);
        $this->DelMessageText($chat_id, $order["courier_group_message_id"]);

        $this->sendOrderWasher($orderId, $user["username"], 1);

        return;
    }

    if ($atext[0] == "/order_washer_group_video_after") {
        
        $orderId = (int)$atext[1];

        if (!$orderId) return;


        $order = R::findOne('orders', "id = $orderId");

        $user_chat_id = $data['callback_query']['from']['id'];

        $template = new Template("order/pickup/send_video_group_after", null, [
            new TemplateData(":orderId", $orderId)
        ]);
        $template = $template->Load();

        $response = $this->sendMessage($chat_id , $template->text, $template->buttons);
        $msg_id = $response['result']['message_id'];
        $this->set_action($chat_id.'_'.$user_chat_id, "pickup_order_send_video_after_group&$orderId&$msg_id");
        
        return;
    }

    if (isset($data['message']['photo']) && $get_action_group[0] == "pickup_order_send_video_after_group") {
        $orderId = (int)$get_action_group[1];
        $user_chat_id = $data['message']['from']['id'];

        if (!$orderId) {
            $this->del_action($chat_id."_".$user_chat_id);
            return;
        }

        $order = R::findOne("orders", "id = $orderId");

        $newPhotoName = $this->saveFileGroup($data, $order);

        $order["video_after_washing"] = $newPhotoName;
        R::store($order);

        $template = new Template("order/pickup/send_video_group_after_confirmation", null, [
            new TemplateData(":orderId", $orderId),
        ]);

        $template = $template->Load();
        $template->LoadButtons();

        $this->DelMessageText($chat_id, $get_action_group[2]);
        $this->DelMessageText($chat_id, $message_id);

        $fileIdVideo = $data["message"]["photo"]["file_id"];

        $response =$this->sendPhoto($chat_id, "https://laundrybot.online/GetLaundry/" . $newPhotoName, $template->text, $template->buttons);
        $msg_id = $response['result']['message_id'];
        $this->set_action($chat_id.'_'.$user_chat_id, "pickup_order_send_video_after_group&$orderId&$msg_id");

        return;
    }

    if ($atext[0] == "/order_pickup_order_send_video_after_group_deny") {
        $orderId = (int)$atext[1];

        if (!$orderId) return;

        $order = R::findOne("orders", "id = $orderId");
        unlink($order['video_after_washing']);

        $template = new Template("order/pickup/send_video_group_after", null, [
            new TemplateData(":orderId", $orderId)
        ]);
        $template = $template->Load();

        $user_chat_id = $data['callback_query']['from']['id'];

        $this->DelMessageText($chat_id, $get_action_group[2]);

        $response = $this->sendMessage($chat_id, $template->text, $template->buttons);
        $msg_id = $response['result']['message_id'];
        $this->set_action($chat_id.'_'.$user_chat_id, "pickup_order_send_video_after_group&$orderId&$msg_id");
    }

    if ($atext[0] == "/order_pickup_order_send_video_after_group_success") {
        $orderId = (int)$atext[1];

        if (!$orderId) return;

        $user_chat_id = $data['callback_query']['from']['id'];

        $this->del_action($chat_id.'_'.$user_chat_id);

        $order = R::findOne("orders", "id = $orderId");
        $user = R::findOne("users", "chat_id = {$order["chat_id"]}");
        $order->washing_status = 2;
        R::store($order);

        $this->DelMessageText($chat_id, $get_action_group[2]);
        $this->DelMessageText($chat_id, $order["courier_group_message_id"]);

        $this->sendOrderWasher($orderId, $user["username"], 2);

        return;
    }

    if($atext[0] == "/order_send_courier_group") {
        $orderId = (int)$atext[1];

        if (!$orderId) return;

        $order = R::findOne("orders", "id = $orderId");
        $user = R::findOne("users", "chat_id = {$order["chat_id"]}");
        
        $order->washed = date("d.m.Y H:i");
        $order->status = 2;
        R::store($order);
        
        $this->DelMessageText($chat_id, $order["courier_group_message_id"]);
        $this->sendOrdersAdmin(GROUP_COURIER_CHAT_ID, $orderId, $user["username"]);
    }
}