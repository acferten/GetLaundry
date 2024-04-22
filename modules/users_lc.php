<?php


# 1 рџ‘¤ Р›РёС‡РЅС‹Р№ РєР°Р±РёРЅРµС‚
if ($atext[0] == "/menu_lc") {

    $this->del_action($chat_id);

    $referal = R::count('referal', "ref_id_user = $chat_id");

    $users = R::findOne('users', "chat_id = $chat_id");

    $balance = $users['balance'];

    $price = $balance ? number_format($balance, 0, '', '.') : 0;

    $template = new Template("menu_lc", $users['lang'], [
        new TemplateData(":referal", $referal),
        new TemplateData(":price", $price),
    ]);
    $template = $template->Load(); 
    
    $this->sendMessage($chat_id, $template->text);

    return;
}


?>