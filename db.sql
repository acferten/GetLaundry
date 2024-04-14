-- phpMyAdmin SQL Dump
-- version 4.4.15.10
-- https://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Июл 24 2022 г., 16:32
-- Версия сервера: 5.5.68-MariaDB-cll-lve
-- Версия PHP: 5.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `sbmwishh_fit`
--

-- --------------------------------------------------------

--
-- Структура таблицы `logoplata`
--

CREATE TABLE IF NOT EXISTS `logoplata` (
  `id` int(11) NOT NULL,
  `user_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `number` int(11) NOT NULL,
  `money` varchar(11) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_id` varchar(11) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` int(64) NOT NULL,
  `status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `settings`
--

CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL,
  `content` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `settings`
--

INSERT INTO `settings` (`id`, `content`) VALUES
(1, 'Вас приветствует');

-- --------------------------------------------------------

--
-- Структура таблицы `stavki`
--

CREATE TABLE IF NOT EXISTS `stavki` (
  `id` int(11) NOT NULL,
  `title` text NOT NULL,
  `content` text NOT NULL,
  `price` varchar(64) NOT NULL,
  `date_reg` varchar(64) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  `about` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `stavki`
--

INSERT INTO `stavki` (`id`, `title`, `content`, `price`, `date_reg`, `status`, `about`) VALUES
(1, 'Ставка 1', '', '', '', 0, ''),
(2, 'тест1', '', '', '', 0, ''),
(3, 'тест5', '23.07.2022 14:00', '250', '', 0, ''),
(4, 'тек=', 'опис', '23.07.2022 15:10', '', 0, ''),
(20, '2222', '3333', '4444', '0', 1, '6666'),
(26, '111', '222', '2', '1658829600', 1, 'результат'),
(27, 'dsds', 'dsds', 'ds', '0', 1, ''),
(28, 'Кеш х3', 'Прпп', '1000', '1658757000', 1, '');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) unsigned NOT NULL,
  `chat_id` int(11) unsigned DEFAULT NULL,
  `first_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_reg` int(11) unsigned DEFAULT NULL,
  `status` int(11) unsigned DEFAULT NULL,
  `balance` int(11) unsigned DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `chat_id`, `first_name`, `last_name`, `username`, `date_reg`, `status`, `balance`) VALUES
(1, 2136511333, 'Андрей', 'Разработчик', 'r_devshop', 1658607847, 1, 0),
(2, 1463952808, 'Mafusail', '', 'mafussaill', 1658610133, 1, 0);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `logoplata`
--
ALTER TABLE `logoplata`
  ADD PRIMARY KEY (`id`),
  ADD KEY `index_foreignkey_logoplata_user` (`user_id`),
  ADD KEY `index_foreignkey_logoplata_order` (`order_id`);

--
-- Индексы таблицы `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `stavki`
--
ALTER TABLE `stavki`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `index_foreignkey_users_chat` (`chat_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `logoplata`
--
ALTER TABLE `logoplata`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT для таблицы `stavki`
--
ALTER TABLE `stavki`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=29;
--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
