<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
  
# [Подключаем БД] 
require __DIR__ . '/core/RB/rb.php';
 
 
# Подключаем файл конфигураций 
$base = parse_ini_file("core/configs.ini", true);
		
# Токен бота телеграмм
define('TOKEN', $base['system']['telegram_token']);
# Подключение к бд
$mysql_status = $base['mysql']['status'];
$mysql_ip = $base['mysql']['ip'];
$mysql_dbname = $base['mysql']['dbname'];
$mysql_dbuser = $base['mysql']['dbuser'];
$mysql_password = $base['mysql']['password'];
		
$rb = R::setup( "mysql:host=$mysql_ip;dbname=$mysql_dbname", $mysql_dbuser, $mysql_password); 
  

$orders_del = R::findAll('orders', 'status > 3 and checked = 0');
foreach($orders_del as $orders_del_o){
	$dir = 'img/orders_group/'.$orders_del_o['id'];
	array_map('unlink', glob("$dir/*.*"));
	rmdir($dir);

	$orders_del_o['check_order'] = "";
	$orders_del_o['photo_before'] = "";
	$orders_del_o['video_before_washing'] = "";
	$orders_del_o['video_after_washing'] = "";
	$orders_del_o['photo_on_the_scales'] = "";
	$orders_del_o['delivered_photo'] = "";
	$orders_del_o['checked'] = 1;

	R::store($orders_del_o);
}
?>