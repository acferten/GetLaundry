<?php

$env_variables = parse_ini_file("core/configs.ini", true);

$secretKey = $env_variables["system"]["lifepay_secret_key"];

$my_key = rawurlencode(md5($_POST['tid'] . $_POST['name'] . $_POST['comment'] . $_POST['partner_id'] . $_POST['service_id'] . $_POST['order_id'] . $_POST['type'] . $_POST['cost'] . $_POST['income_total'] . $_POST['income'] . $_POST['partner_income'] . $_POST['system_income'] . $_POST['command'] . $_POST['phone_number'] . $_POST['email'] . $_POST['result'] . $_POST['resultStr'] . $_POST['date_created'] . $_POST['version'] . $secretKey));

if ($_POST['check'] != $my_key) {
    throw new Exception('Invalid key');
}
if ($_POST['command'] != 'success') {
    throw new Exception('Payment failed');
}

require __DIR__ . '/bot.php';

$order_id = (int)$_POST['order_id'];

$order = R::findOne('orders', "lifepay_order_id = {$order_id}");

$bot = new Bot();

$message = $bot->sendMessage($order->chat_id, 'Оплата прошла успешно');
$bot->sendMessage($env_variables['system']['id_chat'], 'Оплата прошла успешно');

$order->payment = 5;
$order->paid = 5;

R::store($order);

$bot->DelMessageText($env_variables['system']['id_chat'], $order->admin_message_id);

$bot->sendOrdersAdmin($env_variables['system']['id_chat'], $order->id);

