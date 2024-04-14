<?php

if ($chat_id == ANALYTICS_CHAT_ID) {

    if ($atext[0] == "/1") {
        $template = new Template("analytics/help/help");
        $template = $template->Load();
        $template->LoadButtons();

        $this->sendMessage($chat_id, $template->text, $template->buttons);
        return;
    }

    if ($atext[0] == "/enter_expenses") {
        $this->DelMessageText($chat_id, $message_id);

        $template = new Template("analytics/enter_expenses/select_type");
        $template = $template->Load();

        foreach ($template->buttons as $key => $button) {
            $template->buttons[$key] = $button->PrepareToSend();
        }

        $this->sendMessage($chat_id, $template->text, $template->buttons);
        return;
    }

    if ($atext[0] == "/select_expenses_type") {
        $this->DelMessageText($chat_id, $message_id);

        $expensesType = (int)$atext[1];

        $template = new Template("analytics/enter_expenses/enter");
        $template = $template->Load();
        $template->LoadButtons();

        $response = $this->sendMessage($chat_id, $template->text, $template->buttons);

        $this->set_action($chat_id, "enter_expenses&{$response["result"]["message_id"]}&$expensesType");
        return;
    }

    if ($atext[0] == "/select_expenses_type_cancel") {
        $this->DelMessageText($chat_id, $message_id);

        $template = new Template("analytics/enter_expenses/select_type_cancel");
        $template = $template->Load();

        $this->sendMessage($chat_id, $template->text);
        return;
    }

    if ($atext[0] == "/enter_expenses_cancel") {
        $this->DelMessageText($chat_id, $message_id);

        $this->del_action($chat_id);

        $template = new Template("analytics/enter_expenses/select_type");
        $template = $template->Load();
        $template->LoadButtons();

        $this->sendMessage($chat_id, $template->text, $template->buttons);
        return;
    }

    if ($atext[0] == "/enter_expenses_confirmation" && $atext[1]) {
        $this->DelMessageText($chat_id, $message_id);
        $answer = (int)$atext[1];

        switch ($answer) {
            case 1:
                $expenses = R::dispense("expenses");
                $expenses["timestamp"] = time();
                $expenses["type"] = (int)$atext[2];

                $explodedExpensesData = explode("&", $atext[3]);
                $expenses["amount"] = $explodedExpensesData[0];
                $expenses["comment"] = implode(" ", explode("^", $explodedExpensesData[1]));

                R::store($expenses);

                $this->sendMessage($chat_id, "Строка расходов добавлена");
                return;
            case 2:
                $response = $this->sendMessage($chat_id, "Введите сумму и детали расходов через пробел");

                $this->set_action($chat_id, "enter_expenses&{$response["result"]["message_id"]}&$atext[1]");

                return;
        }

        return;
    }

    if ($get_action[0] == "enter_expenses" && count($atext) >= 1) {
        $this->DelMessageText($chat_id, $get_action[1]);
        $this->DelMessageText($chat_id, $message_id);

        $this->del_action($chat_id);

        $template = new Template("analytics/enter_expenses/confirmation", null, [
            new TemplateData(":enteredExpenses", implode(" ", $atext)),
            new TemplateData(":data", "$atext[0]&" . implode("^", array_slice($atext, 1))),
            new TemplateData(":expensesType", $get_action[2]),
        ]);
        $template = $template->Load();


        foreach ($template->buttons as $key => $button) {
            $template->buttons[$key] = $button->PrepareToSend();
        }

        $this->sendMessage($chat_id, $template->text, $template->buttons);
        return;
    }

    if ($atext[0] == "/make_expenses_report") {
        $this->DelMessageText($chat_id, $message_id);
        $this->del_action($chat_id);

        $template = new Template("analytics/expenses_report/set_dates");
        $template = $template->Load();
        $template->LoadButtons();

        $response = $this->sendMessage($chat_id, $template->text, $template->buttons);

        $this->set_action($chat_id, "expenses_report_set_dates&{$response["result"]["message_id"]}");
        return;
    }

    if ($atext[0] == "/expenses_report_set_dates_cancel") {
        $this->DelMessageText($chat_id, $message_id);
        $this->del_action($chat_id);

        $template = new Template("analytics/expenses_report/set_dates_cancel");
        $template = $template->Load();

        $this->sendMessage($chat_id, $template->text);
        return;
    }

    if ($get_action[0] == "expenses_report_set_dates" && $atext[0] && $atext[1]) {
        $this->DelMessageText($chat_id, $message_id);
        $this->DelMessageText($chat_id, $get_action[1]);
        $this->del_action($chat_id);

        $template = new Template("analytics/expenses_report/confirmation", null, [
            new TemplateData(":dateStartText", str_replace("_", ".", $atext[0])),
            new TemplateData(":dateEndText", str_replace("_", ".", $atext[1])),
            new TemplateData(":dateStart", $atext[0]),
            new TemplateData(":dateEnd", $atext[1]),
        ]);
        $template = $template->Load();
        $template->LoadButtons();

        $this->sendMessage($chat_id, $template->text, $template->buttons);
        return;
    }

    if ($atext[0] == "/expenses_report_success" && $atext[1] && $atext[2]) {
        $this->DelMessageText($chat_id, $message_id);

        $startDate = str_replace("_", ".", $atext[1]);
        $endDate = str_replace("_", ".", $atext[2]);

        // время данных начала отчёта
        $start = strtotime(trim($startDate));

        // время данных окончания отчёта, прибавляю 23 часа и 59 минут
        $end = strtotime(trim($endDate)) + (23 * (60 * 60)) + (60 * 59);

        $expensesArray = R::findAll("expenses", " timestamp >= $start AND $end >= timestamp");

        $totalExpenses = 0;
        $totalVariableExpenses = 0;
        $totalBasicExpenses = 0;

        $totalVariableExpenses1 = 0;
        $totalVariableExpenses2 = 0;
        $totalVariableExpenses3 = 0;
        $totalVariableExpenses4 = 0;
        $totalVariableExpenses5 = 0;
        $totalVariableExpenses6 = 0;
        $totalVariableExpenses7 = 0;
        $totalVariableExpenses8 = 0;
        $totalVariableExpenses9 = 0;
        $totalVariableExpenses10 = 0;
        $totalVariableExpenses11 = 0;
        $totalVariableExpenses12 = 0;

        $variableExpenses1 = "";
        $variableExpenses2 = "";
        $variableExpenses3 = "";
        $variableExpenses4 = "";
        $variableExpenses5 = "";
        $variableExpenses6 = "";
        $variableExpenses7 = "";
        $variableExpenses8 = "";
        $variableExpenses9 = "";
        $variableExpenses10 = "";
        $variableExpenses11 = "";
        $variableExpenses12 = "";
        $basicExpenses13 = "";
        foreach ($expensesArray as $expenses) {
            $totalExpenses += $expenses["amount"];

            $expenses["type"] < 13 ? $totalVariableExpenses += $expenses["amount"] : $totalBasicExpenses += $expenses["amount"];

            switch ($expenses["type"]) {
                case 1:
                    $totalVariableExpenses1 += $expenses["amount"];

                    $variableExpenses1 .= date("d.m.Y", $expenses["timestamp"]) . " - " . number_format($expenses["amount"], 0, "", ".") . " " . $expenses["comment"] . "\n";
                    break;
                case 2:
                    $totalVariableExpenses2 += $expenses["amount"];

                    $variableExpenses2 .= date("d.m.Y", $expenses["timestamp"]) . " - " . number_format($expenses["amount"], 0, "", ".") . " " . $expenses["comment"] . "\n";
                    break;
                case 3:
                    $totalVariableExpenses3 += $expenses["amount"];

                    $variableExpenses3 .= date("d.m.Y", $expenses["timestamp"]) . " - " . number_format($expenses["amount"], 0, "", ".") . " " . $expenses["comment"] . "\n";
                    break;
                case 4:
                    $totalVariableExpenses4 += $expenses["amount"];

                    $variableExpenses4 .= date("d.m.Y", $expenses["timestamp"]) . " - " . number_format($expenses["amount"], 0, "", ".") . " " . $expenses["comment"] . "\n";
                    break;
                case 5:
                    $totalVariableExpenses5 += $expenses["amount"];

                    $variableExpenses5 .= date("d.m.Y", $expenses["timestamp"]) . " - " . number_format($expenses["amount"], 0, "", ".") . " " . $expenses["comment"] . "\n";
                    break;
                case 6:
                    $totalVariableExpenses6 += $expenses["amount"];

                    $variableExpenses6 .= date("d.m.Y", $expenses["timestamp"]) . " - " . number_format($expenses["amount"], 0, "", ".") . " " . $expenses["comment"] . "\n";
                    break;
                case 7:
                    $totalVariableExpenses7 += $expenses["amount"];

                    $variableExpenses7 .= date("d.m.Y", $expenses["timestamp"]) . " - " . number_format($expenses["amount"], 0, "", ".") . " " . $expenses["comment"] . "\n";
                    break;
                case 8:
                    $totalVariableExpenses8 += $expenses["amount"];

                    $variableExpenses8 .= date("d.m.Y", $expenses["timestamp"]) . " - " . number_format($expenses["amount"], 0, "", ".") . " " . $expenses["comment"] . "\n";
                    break;
                case 9:
                    $totalVariableExpenses9 += $expenses["amount"];

                    $variableExpenses9 .= date("d.m.Y", $expenses["timestamp"]) . " - " . number_format($expenses["amount"], 0, "", ".") . " " . $expenses["comment"] . "\n";
                    break;
                case 10:
                    $totalVariableExpenses10 += $expenses["amount"];

                    $variableExpenses10 .= date("d.m.Y", $expenses["timestamp"]) . " - " . number_format($expenses["amount"], 0, "", ".") . " " . $expenses["comment"] . "\n";
                    break;
                case 11:
                    $totalVariableExpenses11 += $expenses["amount"];

                    $variableExpenses11 .= date("d.m.Y", $expenses["timestamp"]) . " - " . number_format($expenses["amount"], 0, "", ".") . " " . $expenses["comment"] . "\n";
                    break;
                case 12:
                    $totalVariableExpenses12 += $expenses["amount"];

                    $variableExpenses12 .= date("d.m.Y", $expenses["timestamp"]) . " - " . number_format($expenses["amount"], 0, "", ".") . " " . $expenses["comment"] . "\n";
                    break;
                case 13:
                    $basicExpenses13 .= date("d.m.Y", $expenses["timestamp"]) . " - " . number_format($expenses["amount"], 0, "", ".") . " " . $expenses["comment"] . "\n";
                    break;
            }
        }

        $template = new Template("analytics/expenses_report/report", null, [
            new TemplateData(":startTime", date("d.m.Y", $start)),
            new TemplateData(":endTime", date("d.m.Y", $end)),
            new TemplateData(":totalExpenses", number_format($totalExpenses, 0, "", ".")),

            new TemplateData(":totalVariableExpenses10Percent", round($totalVariableExpenses10 * 100 / $totalVariableExpenses, 2)),
            new TemplateData(":totalVariableExpenses10", number_format($totalVariableExpenses10, 0, "", ".")),

            new TemplateData(":totalVariableExpenses11Percent", round($totalVariableExpenses11 * 100 / $totalVariableExpenses, 2)),
            new TemplateData(":totalVariableExpenses11", number_format($totalVariableExpenses11, 0, "", ".")),

            new TemplateData(":totalVariableExpenses12Percent", round($totalVariableExpenses12 * 100 / $totalVariableExpenses, 2)),
            new TemplateData(":totalVariableExpenses12", number_format($totalVariableExpenses12, 0, "", ".")),

            new TemplateData(":totalVariableExpenses1Percent", round($totalVariableExpenses1 * 100 / $totalVariableExpenses, 2)),
            new TemplateData(":totalVariableExpenses1", number_format($totalVariableExpenses1, 0, "", ".")),

            new TemplateData(":totalVariableExpenses2Percent", round($totalVariableExpenses2 * 100 / $totalVariableExpenses, 2)),
            new TemplateData(":totalVariableExpenses2", number_format($totalVariableExpenses2, 0, "", ".")),

            new TemplateData(":totalVariableExpenses3Percent", round($totalVariableExpenses3 * 100 / $totalVariableExpenses, 2)),
            new TemplateData(":totalVariableExpenses3", number_format($totalVariableExpenses3, 0, "", ".")),

            new TemplateData(":totalVariableExpenses4Percent", round($totalVariableExpenses4 * 100 / $totalVariableExpenses, 2)),
            new TemplateData(":totalVariableExpenses4", number_format($totalVariableExpenses4, 0, "", ".")),

            new TemplateData(":totalVariableExpenses5Percent", round($totalVariableExpenses5 * 100 / $totalVariableExpenses, 2)),
            new TemplateData(":totalVariableExpenses5", number_format($totalVariableExpenses5, 0, "", ".")),

            new TemplateData(":totalVariableExpenses6Percent", round($totalVariableExpenses6 * 100 / $totalVariableExpenses, 2)),
            new TemplateData(":totalVariableExpenses6", number_format($totalVariableExpenses6, 0, "", ".")),

            new TemplateData(":totalVariableExpenses7Percent", round($totalVariableExpenses7 * 100 / $totalVariableExpenses, 2)),
            new TemplateData(":totalVariableExpenses7", number_format($totalVariableExpenses7, 0, "", ".")),

            new TemplateData(":totalVariableExpenses8Percent", round($totalVariableExpenses8 * 100 / $totalVariableExpenses, 2)),
            new TemplateData(":totalVariableExpenses8", number_format($totalVariableExpenses8, 0, "", ".")),

            new TemplateData(":totalVariableExpenses9Percent", round($totalVariableExpenses9 * 100 / $totalVariableExpenses, 2)),
            new TemplateData(":totalVariableExpenses9", number_format($totalVariableExpenses9, 0, "", ".")),

            new TemplateData(":totalVariableExpensesPercent", round($totalVariableExpenses * 100 / $totalExpenses, 2)),
            new TemplateData(":totalVariableExpenses", number_format($totalVariableExpenses, 0, "", ".")),
            new TemplateData(":totalBasicExpensesPercent", round($totalBasicExpenses * 100 / $totalExpenses, 2)),
            new TemplateData(":totalBasicExpenses", number_format($totalBasicExpenses, 0, "", ".")),

            new TemplateData(":variableExpenses10", $variableExpenses10),
            new TemplateData(":variableExpenses11", $variableExpenses11),
            new TemplateData(":variableExpenses12", $variableExpenses12),
            new TemplateData(":variableExpenses1", $variableExpenses1),
            new TemplateData(":variableExpenses2", $variableExpenses2),
            new TemplateData(":variableExpenses3", $variableExpenses3),
            new TemplateData(":variableExpenses4", $variableExpenses4),
            new TemplateData(":variableExpenses5", $variableExpenses5),
            new TemplateData(":variableExpenses6", $variableExpenses6),
            new TemplateData(":variableExpenses7", $variableExpenses7),
            new TemplateData(":variableExpenses8", $variableExpenses8),
            new TemplateData(":variableExpenses9", $variableExpenses9),
            new TemplateData(":basicExpenses13", $basicExpenses13),
        ]);
        $template = $template->Load();
        $this->sendMessage($chat_id, $template->text);
        return;
    }

    if ($atext[0] == "/make_revenue_report") {
        $this->DelMessageText($chat_id, $message_id);
        $this->del_action($chat_id);

        $template = new Template("analytics/revenue_report/set_dates");
        $template = $template->Load();
        $template->LoadButtons();

        $response = $this->sendMessage($chat_id, $template->text, $template->buttons);

        $this->set_action($chat_id, "revenue_report_set_dates&{$response["result"]["message_id"]}");
        return;
    }

    if ($atext[0] == "/revenue_report_set_dates_cancel") {
        $this->DelMessageText($chat_id, $message_id);
        $this->del_action($chat_id);

        $template = new Template("analytics/revenue_report/set_dates_cancel");
        $template = $template->Load();

        $this->sendMessage($chat_id, $template->text);
        return;
    }

    if ($get_action[0] == "revenue_report_set_dates" && $atext[0] && $atext[1]) {
        $this->DelMessageText($chat_id, $message_id);
        $this->DelMessageText($chat_id, $get_action[1]);
        $this->del_action($chat_id);

        $template = new Template("analytics/revenue_report/confirmation", null, [
            new TemplateData(":dateStartText", str_replace("_", ".", $atext[0])),
            new TemplateData(":dateEndText", str_replace("_", ".", $atext[1])),
            new TemplateData(":dateStart", $atext[0]),
            new TemplateData(":dateEnd", $atext[1]),
        ]);
        $template = $template->Load();
        $template->LoadButtons();

        $this->sendMessage($chat_id, $template->text, $template->buttons);
        return;
    }


    if ($atext[0] == "/revenue_report_success" && $atext[1] && $atext[2]) {
        $this->DelMessageText($chat_id, $message_id);

        $startDate = str_replace("_", ".", $atext[1]);
        $endDate = str_replace("_", ".", $atext[2]);

        // время данных начала отчёта
        $start = strtotime(trim($startDate));

        // время данных окончания отчёта, прибавляю 23 часа и 59 минут
        $end = strtotime(trim($endDate)) + (23 * (60 * 60)) + (60 * 59);

        // получаю количество дней между датами
        $daysCount = round(($end / 60 / 60 / 24) - ($start / 60 / 60 / 24));

        // возвращаю ошибку при некорректном заполнении дат
        if ($start <= 0 || $end <= 0 || $daysCount < 1) {
            $this->sendMessage(ANALYTICS_CHAT_ID, "Ошибка отчёта! Некорректные даты");
            return;
        }
        $orders = R::findAll("orders", " time_start >= $start AND $end >= time_end AND LOWER(address_2) != 'test' AND LOWER(address_2) != 'тест'");

        $totalWeight = 0;
        $totalShoes = 0;
        $totalOrganic = 0;
        $totalBedLinen = 0;
        $totalRating = 0;
        $totalRatingCount = 0;
        $countSecondOrders = 0;
        $countFirstOrders = 0;
        $countPaidBonuses = 0;
        $countReferalsBonuses = 0;
        $countDenyOrders = 0;
        $totalBonusesPaid = 0;

        // Просчитываю общее количество кг. одежды, пар обуви, выставленных оценок
        foreach ($orders as $order) {
            if ($order["status"] == 0) {
                $countDenyOrders++;
            }

            $totalWeight += (int)$order["wt"];
            $totalShoes += (int)$order["shoes"];
            $totalOrganic += (int)$order["organic"];
            $totalBedLinen += (int)$order["bed_linen"];

            if ($order["otziv"]) {
                $totalRatingCount++;
                $totalRating += (int)$order["otziv"];
            }

            // если количество заказов пользователя превышает единицу, то прибавляю количество повторных заказов, иначе
            // прибавляю количество первых заказов
            $userOrdersCount = R::count("orders", "chat_id = {$order["chat_id"]}");
            $userOrdersCount > 1 ? $countSecondOrders++ : $countFirstOrders++;

            // прибавляю количество заказов, оплаченных бонусами
            if ($order["payment"] == 4) {
                $countPaidBonuses++;
                $totalBonusesPaid += $order["bonus_payed"];
            }


            // прибавляю количество бонусов, начисляемых, если пользователь является рефералом
            $referal = R::findOne('referal', "chat_id = {$order["chat_id"]}");
            if ($referal) $countReferalsBonuses += ((int)str_replace(".", "", $order["price"]) * 10) / 100;
        }

        $totalWeightPrice = $totalWeight * 80000;
        $totalShoesPrice = $totalShoes * 120000;
        $totalBedLinenPrice = $totalBedLinen * 50000;
        $totalOrganicPrice = $totalOrganic * 120000;
        $totalPrice = $totalWeightPrice + $totalShoesPrice + $totalBedLinenPrice + $totalOrganicPrice;

        $averagePrice = round($totalPrice / count($orders));
        $averageWeight = round($totalWeight / count($orders), 2);
        $averageShoes = round($totalShoes / count($orders), 2);
        $averageBedLinen = round($totalBedLinen / count($orders), 2);
        $averageOrganic =  round($totalOrganic / count($orders, 2));

        $averageDayOrdersCount = round(count($orders) / (($end / 60 / 60 / 24) - ($start / 60 / 60 / 24)), 2);
        $averageDayPrice = round($totalPrice / (($end / 60 / 60 / 24) - ($start / 60 / 60 / 24)));

        $averageRating = round($totalRating / $totalRatingCount, 2);

        // получаю список абонементов и по логам покупок просчитываю данные
        $wholesaleLaundries = R::findAll("wholesale_laundry");
        $wholesaleLaundriesContent = "";

        // загружаю и подготавливаю шаблон
        foreach ($wholesaleLaundries as $wholesaleLaundry) {
            $countLogWholesaleLaundries = R::findAll("logwholesalelaundrypayment",
                " timestamp >= $start AND timestamp <= $end AND status = 2 AND wholesale_laundry_id = {$wholesaleLaundry["id"]} ");

            $wholesaleLaundry["count"] = count($countLogWholesaleLaundries);
            $wholesaleLaundry["total_price"] = count($countLogWholesaleLaundries) * $wholesaleLaundry["price_idr"];

            $formattedWholeSaleLaundryTotalPrice = number_format($wholesaleLaundry["total_price"], 0, "", ".");

            $formattedWholesaleLaundryText = "Subscription: {$wholesaleLaundry["weight"]} кг.\n";
            $formattedWholesaleLaundryText .= "Total: <b>{$wholesaleLaundry["count"]}</b>\n";
            $formattedWholesaleLaundryText .= "Revenue: <b>$formattedWholeSaleLaundryTotalPrice IDR</b>\n\n";

            $wholesaleLaundriesContent .= $formattedWholesaleLaundryText;
        }

        $template = new Template("analytics/revenue_report/report", null, [
            new TemplateData(":startDate", $startDate),
            new TemplateData(":endDate", $endDate),
            new TemplateData(":daysCount", $daysCount),
            new TemplateData(":totalWeightPrice", number_format($totalWeightPrice, 0, "", ".")),
            new TemplateData(":totalShoesPrice", number_format($totalShoesPrice, 0, "", ".")),
            new TemplateData(":totalBedLinenPrice", number_format($totalBedLinenPrice, 0, "", ".")),
            new TemplateData(":totalOrganicPrice", number_format($totalOrganicPrice, 0, "", ".")),
            new TemplateData(":totalWeight", $totalWeight),
            new TemplateData(":totalShoes", $totalShoes),
            new TemplateData(":totalBedLinen", $totalBedLinen),
            new TemplateData(":totalOrganic", $totalOrganic),
            new TemplateData(":totalPrice", number_format($totalPrice, 0, "", ".")),
            new TemplateData(":averagePrice", number_format($averagePrice, 0, "", ".")),
            new TemplateData(":averageWeight", $averageWeight),
            new TemplateData(":averageShoes", $averageShoes),
            new TemplateData(":averageBedLinen", $averageBedLinen),
            new TemplateData(":averageOrganic", $averageOrganic),
            new TemplateData(":averageDayOrdersCount", $averageDayOrdersCount),
            new TemplateData(":averageDayPrice", number_format($averageDayPrice, 0, "", ".")),
            new TemplateData(":averageRating", $averageRating),
            new TemplateData(":wholesaleLaundriesContent", $wholesaleLaundriesContent),
            new TemplateData(":countOrders", count($orders) - $countDenyOrders),
            new TemplateData(":countSecondOrders", $countSecondOrders),
            new TemplateData(":countFirstOrders", $countFirstOrders),
            new TemplateData(":percentSecondOrders", round($countSecondOrders * 100 / count($orders), 2)),
            new TemplateData(":percentFirstOrders", round($countFirstOrders * 100 / count($orders), 2)),
            new TemplateData(":countReferalsBonuses", number_format($countReferalsBonuses, 0, "", ".")),
            new TemplateData(":totalBonusesPaid", number_format($totalBonusesPaid, 0, "", ".")),
            new TemplateData(":countPaidBonuses", $countPaidBonuses),
        ]);

        $template = $template->Load();

        $this->sendMessage($chat_id, $template->text);
        return;
    }

    if ($atext[0] == "/make_status_report") {
        $this->DelMessageText($chat_id, $message_id);
        $this->del_action($chat_id);

        $template = new Template("analytics/status_report/set_dates");
        $template = $template->Load();
        $template->LoadButtons();

        $response = $this->sendMessage($chat_id, $template->text, $template->buttons);

        $this->set_action($chat_id, "status_report_set_dates&{$response["result"]["message_id"]}");
        return;
    }

    if ($atext[0] == "/status_report_set_dates_cancel") {
        $this->DelMessageText($chat_id, $message_id);
        $this->del_action($chat_id);

        $template = new Template("analytics/status_report/set_dates_cancel");
        $template = $template->Load();

        $this->sendMessage($chat_id, $template->text);
        return;
    }

    if ($get_action[0] == "status_report_set_dates" && $atext[0] && $atext[1]) {
        $this->DelMessageText($chat_id, $message_id);
        $this->DelMessageText($chat_id, $get_action[1]);
        $this->del_action($chat_id);

        $template = new Template("analytics/status_report/confirmation", null, [
            new TemplateData(":dateStartText", str_replace("_", ".", $atext[0])),
            new TemplateData(":dateEndText", str_replace("_", ".", $atext[1])),
            new TemplateData(":dateStart", $atext[0]),
            new TemplateData(":dateEnd", $atext[1]),
        ]);
        $template = $template->Load();
        $template->LoadButtons();

        $this->sendMessage($chat_id, $template->text, $template->buttons);
        return;
    }
    
    
    /*
      if ($atext[0] == "/status_report_set_laundry") {
        $this->DelMessageText($chat_id, $message_id);
        $this->del_action($chat_id);

        $template = new Template("analytics/status_report/set_laundry_cancel");
        $template = $template->Load();

        $this->sendMessage($chat_id, $template->text);
        return;
    }
    
    
    if ($get_action[0] == "status_report_set_laundry" && $atext[0] && $atext[1]) {
        $this->DelMessageText($chat_id, $message_id);
        $this->DelMessageText($chat_id, $get_action[1]);
        $this->del_action($chat_id);

        $template = new Template("analytics/status_report/laundry_confirmation", null, [
            new TemplateData(":dateStartText", str_replace("_", ".", $atext[0])),
            new TemplateData(":dateEndText", str_replace("_", ".", $atext[1])),
            new TemplateData(":dateStart", $atext[0]),
            new TemplateData(":dateEnd", $atext[1]),
        ]);
        $template = $template->Load();
        $template->LoadButtons();

        $this->sendMessage($chat_id, $template->text, $template->buttons);
        return;
    }
    */
    

    if ($atext[0] == "/status_report_success" && $atext[1] && $atext[2]) {
        $this->DelMessageText($chat_id, $message_id);

        $startDate = str_replace("_", ".", $atext[1]);
        $endDate = str_replace("_", ".", $atext[2]);

        $start = strtotime(trim($startDate));

        $end = strtotime(trim($endDate)) + (23 * (60 * 60)) + (60 * 59);

        $daysCount = round(($end / 60 / 60 / 24) - ($start / 60 / 60 / 24));

        if ($start <= 0 || $end <= 0 || $daysCount < 1) {
            $this->sendMessage(ANALYTICS_CHAT_ID, "Ошибка отчёта! Некорректные даты");
            return;
        }

        $orders = R::findAll("orders", " timestamp_create >= $start AND $end >= time_end AND LOWER(address_2) != 'test' AND LOWER(address_2) != 'тест'");

        $newOrders = [];
        $placedInLaundry = [];
        $washingStarted = [];
        $washingFinished = [];
        $pickupOrders = [];
        $weightOrders = [];
        $deliveredOrders = [];
        $paidOrders = [];
        $denyOrders = [];
        foreach ($orders as $order) {
            $user = R::findOne("users", " chat_id = '{$order["chat_id"]}' ");
            $order["username"] = $user["username"];

            if ((int)$order["status"] == 1 && !$order['photo_before']) {
                $newOrders[] = $order;
                continue;
            }

            if ((int)$order['status'] == 1 && $order['washing_status'] == 0 && $order['photo_in_laundry']) {
                $placedInLaundry[] = $order;
                continue;
            }

            if ($order['washing_status'] == 1 && $order['status'] == 1) {
                $washingStarted[] = $order;
                continue;
            }

            if ($order['washing_status'] == 2 && $order['status'] == 2) {
                $washingFinished[] = $order;
                continue;
            }

            if ($order["price"] && (int)$order["status"] == 3) {
                $weightOrders[] = $order;
                continue;
            }

            if ($order["status"] == 1 && !$order['photo_in_laundry'] && $order['photo_before']) {
                $pickupOrders[] = $order;
                continue;
            }

            if ((int)$order["status"] == 5) {
                $deliveredOrders[] = $order;
                continue;
            }

            if ((int)$order["status"] == 6) {
                $paidOrders[] = $order;
                continue;
            }

            if ((int)$order["status"] == 0) {
                switch ((int)$order["title_cancel"]) {
                    case 1:
                        $order["deny_text"] = "Просто решил проверить Бот";
                        break;
                    case 2:
                        $order["deny_text"] = "Передумал стирать";
                        break;
                    case 3:
                        $order["deny_text"] = "Переживаю за качество стирки";
                        break;
                    case 4:
                        $order["deny_text"] = "Дорого";
                        break;
                }

                $denyOrders[] = $order;
            }

            if ($order['status'] == -1) {
                $notReachedOrders[] = $order;
            }
        }

        $newOrdersUsernamesArray = "";
        foreach ($newOrders as $newOrder) {
            $newOrdersUsernamesArray .= "<b>#{$newOrder["id"]} @{$newOrder["username"]}</b>\n";
        }

        $pickupOrdersUsernamesArray = "";
        foreach ($pickupOrders as $pickupOrder) {
            $pickupOrdersUsernamesArray .= "<b>#{$pickupOrder["id"]} {$pickupOrder["laundry_name"]} @{$pickupOrder["username"]}</b>\n";
        }

        $inLaundryOrdersUsernamesArray = "";
        foreach ($placedInLaundry as $placedInLaundryOrder) {
            $inLaundryOrdersUsernamesArray .= "<b>#{$placedInLaundryOrder["id"]} {$placedInLaundryOrder["laundry_name"]} @{$placedInLaundryOrder["username"]}</b>\n";
        }

        $weightOrdersUsernamesArray = "";
        foreach ($weightOrders as $weightOrder) {
            $weightOrdersUsernamesArray .= "<b>#{$weightOrder["id"]} {$weightOrder["laundry_name"]} @{$weightOrder["username"]}</b>\n";
        }

        $deliveredOrdersUsernamesArray = "";
        foreach ($deliveredOrders as $deliveredOrder) {
            $deliveredOrdersUsernamesArray .= "<b>#{$deliveredOrder["id"]} {$deliveredOrder["laundry_name"]} @{$deliveredOrder["username"]}</b>\n";
        }

        $paidOrdersUsernamesArray = "";
        foreach ($paidOrders as $paidOrder) {
            $paidOrdersUsernamesArray .= "<b>#{$paidOrder["id"]} {$paidOrder["laundry_name"]} @{$paidOrder["username"]}</b>\n";
        }

        $denyOrdersUsernamesArray = "";
        foreach ($denyOrders as $denyOrder) {
            $denyOrdersUsernamesArray .= "<b>#{$denyOrder["id"]} {$denyOrder["laundry_name"]} @{$denyOrder["username"]}</b> - <b>{$denyOrder["deny_text"]}</b>\n";
        }

        $notReachedUsernamesArray = "";
        foreach ($notReachedOrders as $notReachedOrder) {
            $notReachedUsernamesArray .= "<b>#{$notReachedOrder["id"]} @{$notReachedOrder["username"]}</b>\n";
        }

        $startedOrdersUsernamesArray = "";
        foreach ($washingStarted as $washingStartedOrder) {
            $startedOrdersUsernamesArray .= "<b>#{$washingStartedOrder["id"]} {$washingStartedOrder["laundry_name"]} @{$washingStartedOrder["username"]}</b>\n";
        }

        $finishedOrdersUsernamesArray = "";
        foreach ($washingFinished as $washingFinishedOrder) {
            $finishedOrdersUsernamesArray .= "<b>#{$washingFinishedOrder["id"]} {$washingFinishedOrder["laundry_name"]} @{$washingFinishedOrder["username"]}</b>\n";
        }

        $template = new Template("analytics/status_report/report", null, [
            new TemplateData(":startDate", $startDate),
            new TemplateData(":endDate", $endDate),
            new TemplateData(":daysCount", $daysCount),
            new TemplateData(":countOrders", count($orders) - count($denyOrders) - count($notReachedOrders)),
            new TemplateData(":newOrdersCount", count($newOrders)),
            new TemplateData(":newOrdersUsernamesArray", $newOrdersUsernamesArray),
            new TemplateData(":pickupOrdersCount", count($pickupOrders)),
            new TemplateData(":pickupOrdersUsernamesArray", $pickupOrdersUsernamesArray),
            new TemplateData(":placedInLaundry", count($placedInLaundry)),
            new TemplateData(":inLaundryUsernamesArray", $inLaundryOrdersUsernamesArray),
            new TemplateData(":weightOrdersCount", count($weightOrders)),
            new TemplateData(":weightOrdersUsernamesArray", $weightOrdersUsernamesArray),
            new TemplateData(":washingStarted", count($washingStarted)),
            new TemplateData(":startedOrdersUsernamesArray", $startedOrdersUsernamesArray),
            new TemplateData(":washingFinished", count($washingFinished)),
            new TemplateData(":finishedOrdersUsernamesArray", $finishedOrdersUsernamesArray),
            new TemplateData(":deliveredOrdersCount", count($deliveredOrders)),
            new TemplateData(":deliveredOrdersUsernamesArray", $deliveredOrdersUsernamesArray),
            new TemplateData(":paidOrdersCount", count($paidOrders)),
            new TemplateData(":paidOrdersUsernamesArray", $paidOrdersUsernamesArray),
            new TemplateData(":denyOrdersCount", count($denyOrders)),
            new TemplateData(":denyOrdersUsernamesArray", $denyOrdersUsernamesArray),
            new TemplateData(":notReachedOrders", count($notReachedOrders)),
            new TemplateData(":notReachedUsernamesArray", $notReachedUsernamesArray),
        ]);
        $template = $template->Load();

        $this->sendMessage($chat_id, $template->text);
        return;
    }

    if ($atext[0] == "/make_rating_report") {
        $this->DelMessageText($chat_id, $message_id);
        $this->del_action($chat_id);

        $template = new Template("analytics/rating_report/set_dates");
        $template = $template->Load();
        $template->LoadButtons();

        $response = $this->sendMessage($chat_id, $template->text, $template->buttons);

        $this->set_action($chat_id, "rating_report_set_dates&{$response["result"]["message_id"]}");
        return;
    }

    if ($atext[0] == "/rating_report_set_dates_cancel") {
        $this->DelMessageText($chat_id, $message_id);
        $this->del_action($chat_id);

        $template = new Template("analytics/rating_report/set_dates_cancel");
        $template = $template->Load();

        $this->sendMessage($chat_id, $template->text);
        return;
    }

    if ($get_action[0] == "rating_report_set_dates" && $atext[0] && $atext[1]) {
        $this->DelMessageText($chat_id, $message_id);
        $this->DelMessageText($chat_id, $get_action[1]);
        $this->del_action($chat_id);

        $template = new Template("analytics/rating_report/confirmation", null, [
            new TemplateData(":dateStartText", str_replace("_", ".", $atext[0])),
            new TemplateData(":dateEndText", str_replace("_", ".", $atext[1])),
            new TemplateData(":dateStart", $atext[0]),
            new TemplateData(":dateEnd", $atext[1]),
        ]);
        $template = $template->Load();
        $template->LoadButtons();

        $this->sendMessage($chat_id, $template->text, $template->buttons);
        return;
    }

    if ($atext[0] == "/rating_report_success" && $atext[1] && $atext[2]) {
        $this->DelMessageText($chat_id, $message_id);

        $startDate = str_replace("_", ".", $atext[1]);
        $endDate = str_replace("_", ".", $atext[2]);

        // время данных начала отчёта
        $start = strtotime(trim($startDate));

        // время данных окончания отчёта, прибавляю 23 часа и 59 минут
        $end = strtotime(trim($endDate)) + (23 * (60 * 60)) + (60 * 59);

        // записываю логи запроса
        file_put_contents("analytics.log", "$atext[0] $startDate($start) - $endDate($end)" . PHP_EOL, FILE_APPEND);

        // получаю количество дней между датами
        $daysCount = round(($end / 60 / 60 / 24) - ($start / 60 / 60 / 24));

        // возвращаю ошибку при некорректном заполнении дат
        if ($start <= 0 || $end <= 0 || $daysCount < 1) {
            $this->sendMessage(ANALYTICS_CHAT_ID, "Ошибка отчёта! Некорректные даты");
            return;
        }

        // получаю список выполненных заказов в указанном промежутке времени
        $orders = R::findAll("orders", " time_start >= $start AND $end >= time_end AND LOWER(address_2) != 'test' AND LOWER(address_2) != 'тест'");

        $ratingOrdersCount = 0;
        $totalRating = 0;
        $ordersRating1 = [];
        $ordersRating2 = [];
        $ordersRating3 = [];
        $ordersRating4 = [];
        $ordersRating5 = [];
        foreach ($orders as $order) {
            if (!$order["otziv"]) {
                continue;
            }

            $user = R::findOne("users", " chat_id = '{$order["chat_id"]}' ");
            $order["username"] = $user["username"];

            $ratingOrdersCount++;
            $totalRating += (int)$order["otziv"];

            switch ($order["otziv"]) {
                case 1:
                    $ordersRating1[] = $order;
                    break;
                case 2:
                    $ordersRating2[] = $order;
                    break;
                case 3:
                    $ordersRating3[] = $order;
                    break;
                case 4:
                    $ordersRating4[] = $order;
                    break;
                case 5:
                    $ordersRating5[] = $order;
                    break;
            }
        }

        // загружаю и подготавливаю шаблон
        $ordersRating1UsernamesArray = "";
        foreach ($ordersRating1 as $order) {
            $ordersRating1UsernamesArray .= "<b>#{$order["id"]} @{$order["username"]}</b>\n";
        }

        $ordersRating2UsernamesArray = "";
        foreach ($ordersRating2 as $order) {
            $ordersRating2UsernamesArray .= "<b>#{$order["id"]} @{$order["username"]}</b>\n";
        }

        $ordersRating3UsernamesArray = "";
        foreach ($ordersRating3 as $order) {
            $ordersRating3UsernamesArray .= "<b>#{$order["id"]} @{$order["username"]}</b>\n";
        }

        $ordersRating4UsernamesArray = "";
        foreach ($ordersRating4 as $order) {
            $ordersRating4UsernamesArray .= "<b>#{$order["id"]} @{$order["username"]}</b>\n";
        }

        $ordersRating5UsernamesArray = "";
        foreach ($ordersRating5 as $order) {
            $ordersRating5UsernamesArray .= "<b>#{$order["id"]} @{$order["username"]}</b>\n";
        }

        $template = new Template("analytics/rating_report/report", null, [
            new TemplateData(":startDate", $startDate),
            new TemplateData(":endDate", $endDate),
            new TemplateData(":daysCount", $daysCount),
            new TemplateData(":orderCount", count($orders)),
            new TemplateData(":ratingOrderCount", $ratingOrdersCount),
            new TemplateData(":averageRating", round($totalRating / $ratingOrdersCount, 2) ?: 0),
            new TemplateData(":countOrdersRating1", count($ordersRating1)),
            new TemplateData(":ordersRating1UsernamesArray", $ordersRating1UsernamesArray),
            new TemplateData(":countOrdersRating2", count($ordersRating2)),
            new TemplateData(":ordersRating2UsernamesArray", $ordersRating2UsernamesArray),
            new TemplateData(":countOrdersRating3", count($ordersRating3)),
            new TemplateData(":ordersRating3UsernamesArray", $ordersRating3UsernamesArray),
            new TemplateData(":countOrdersRating4", count($ordersRating4)),
            new TemplateData(":ordersRating4UsernamesArray", $ordersRating4UsernamesArray),
            new TemplateData(":countOrdersRating5", count($ordersRating5)),
            new TemplateData(":ordersRating5UsernamesArray", $ordersRating5UsernamesArray),
        ]);

        $template = $template->Load();

        $this->sendMessage($chat_id, $template->text);
        return;
    }

    if ($atext[0] == "/make_wallet_report") {
        $this->DelMessageText($chat_id, $message_id);
        $this->del_action($chat_id);

        $template = new Template("analytics/wallet_report/set_dates");
        $template = $template->Load();
        $template->LoadButtons();

        $response = $this->sendMessage($chat_id, $template->text, $template->buttons);

        $this->set_action($chat_id, "wallet_report_set_dates&{$response["result"]["message_id"]}");
        return;
    }

    if ($atext[0] == "/wallet_report_set_dates_cancel") {
        $this->DelMessageText($chat_id, $message_id);
        $this->del_action($chat_id);

        $template = new Template("analytics/wallet_report/set_dates_cancel");
        $template = $template->Load();

        $this->sendMessage($chat_id, $template->text);
        return;
    }

    if ($get_action[0] == "wallet_report_set_dates" && $atext[0] && $atext[1]) {
        $this->DelMessageText($chat_id, $message_id);
        $this->DelMessageText($chat_id, $get_action[1]);
        $this->del_action($chat_id);

        $template = new Template("analytics/wallet_report/confirmation", null, [
            new TemplateData(":dateStartText", str_replace("_", ".", $atext[0])),
            new TemplateData(":dateEndText", str_replace("_", ".", $atext[1])),
            new TemplateData(":dateStart", $atext[0]),
            new TemplateData(":dateEnd", $atext[1]),
        ]);
        $template = $template->Load();
        $template->LoadButtons();

        $this->sendMessage($chat_id, $template->text, $template->buttons);
        return;
    }

    if ($atext[0] == "/wallet_report_success" && $atext[1] && $atext[2]) {
        $this->DelMessageText($chat_id, $message_id);

        $startDate = str_replace("_", ".", $atext[1]);
        $endDate = str_replace("_", ".", $atext[2]);

        // время данных начала отчёта
        $start = strtotime(trim($startDate));

        // время данных окончания отчёта, прибавляю 23 часа и 59 минут
        $end = strtotime(trim($endDate)) + (23 * (60 * 60)) + (60 * 59);

        $cashAmount = 0;
        $tinkoffAmountIDR = 0;
        $tinkoffAmountRUB = 0;
        $BRIAmount = 0;
        $bonusAmount = 0;
        $totalAmountWithBonuses = 0;
        $totalAmount = 0;

        // получаю список выполненных заказов в указанном промежутке времени
        $orders = R::findAll("orders", " time_start >= $start AND $end >= time_end AND status =4 AND LOWER(address_2) != 'test' AND LOWER(address_2) != 'тест'");

        foreach ($orders as $order) {
            $price = str_replace(".", "", $order["price"]);
            $totalAmountWithBonuses += $price;

            switch ($order["paid"]) {
                case 1:
                    $cashAmount += $price;
                    break;
                case 2:
                    $BRIAmount += $price;
                    break;
                case 3:
                    $tinkoffAmountIDR += $price;
                    break;
            }

            if ($order["bonus_payed"] > 0) {
                $bonusAmount += $order["bonus_payed"];
            }
        }

        // считаю оплату по Тинькоффу в рублях
        $tinkoffAmountRUB = $tinkoffAmountIDR / 1000 * 5.8;

        $totalAmount = $totalAmountWithBonuses - $bonusAmount;

        $template = new Template("analytics/wallet_report/report", null, [
            new TemplateData(":startTime", date("d.m.Y", $start)),
            new TemplateData(":endTime", date("d.m.Y", $end)),
            new TemplateData(":cashAmount", number_format($cashAmount, 0, "", ".")),
            new TemplateData(":tinkoffAmountIDR", number_format($tinkoffAmountIDR, 0, "", ".")),
            new TemplateData(":tinkoffAmountRUB", number_format($tinkoffAmountRUB, 0, "", ".")),
            new TemplateData(":BRIAmount", number_format($BRIAmount, 0, "", ".")),
            new TemplateData(":bonusAmount", number_format($bonusAmount, 0, "", ".")),
            new TemplateData(":totalAmountWithBonuses", number_format($totalAmountWithBonuses, 0, "", ".")),
            new TemplateData(":totalAmount", number_format($totalAmount, 0, "", ".")),
        ]);

        $template = $template->Load();

        $this->sendMessage($chat_id, $template->text);
        return;
    }

    if ($atext[0] == "/make_pnl_report") {
        $this->DelMessageText($chat_id, $message_id);
        $this->del_action($chat_id);

        $template = new Template("analytics/pnl_report/set_dates");
        $template = $template->Load();
        $template->LoadButtons();

        $response = $this->sendMessage($chat_id, $template->text, $template->buttons);

        $this->set_action($chat_id, "pnl_report_set_dates&{$response["result"]["message_id"]}");
        return;
    }

    if ($atext[0] == "/pnl_report_set_dates_cancel") {
        $this->DelMessageText($chat_id, $message_id);
        $this->del_action($chat_id);

        $template = new Template("analytics/pnl_report/set_dates_cancel");
        $template = $template->Load();

        $this->sendMessage($chat_id, $template->text);
        return;
    }

    if ($get_action[0] == "pnl_report_set_dates" && $atext[0] && $atext[1]) {
        $this->DelMessageText($chat_id, $message_id);
        $this->DelMessageText($chat_id, $get_action[1]);
        $this->del_action($chat_id);

        $template = new Template("analytics/pnl_report/confirmation", null, [
            new TemplateData(":dateStartText", str_replace("_", ".", $atext[0])),
            new TemplateData(":dateEndText", str_replace("_", ".", $atext[1])),
            new TemplateData(":dateStart", $atext[0]),
            new TemplateData(":dateEnd", $atext[1]),
        ]);
        $template = $template->Load();
        $template->LoadButtons();

        $this->sendMessage($chat_id, $template->text, $template->buttons);
        return;
    }

    if ($atext[0] == "/pnl_report_success" && $atext[1] && $atext[2]) {
        $this->DelMessageText($chat_id, $message_id);

        $startDate = str_replace("_", ".", $atext[1]);
        $endDate = str_replace("_", ".", $atext[2]);

        // время данных начала отчёта
        $start = strtotime(trim($startDate));

        // время данных окончания отчёта, прибавляю 23 часа и 59 минут
        $end = strtotime(trim($endDate)) + (23 * (60 * 60)) + (60 * 59);

        $expensesArray = R::findAll("expenses", " timestamp >= $start AND $end >= timestamp");

        $proceeds = 0;
        $totalExpenses = 0;
        $variableExpenses = 0;
        $fixedAssets = 0;
        $operatingProfit = 0;
        $netProfit = 0;
        $yield = 0;
        $costOf1KgOfThings = 0;
        $costOf1Order = 0;
        $totalCostOf1Order = 0;

        foreach ($expensesArray as $expenses) {
            $totalExpenses += $expenses["amount"];

            if ($expenses["type"] < 13) $variableExpenses += $expenses["amount"];
            else $fixedAssets += $expenses["amount"];
        }

        $orders = R::findAll("orders", " time_start >= $start AND $end >= time_end AND status >=3 AND LOWER(address_2) != 'test' AND LOWER(address_2) != 'тест'");

        $wtPerPeriod = 0;

        foreach ($orders as $order) {
            $proceeds += str_replace(".", "", $order["price"]);
            $wtPerPeriod += $order["wt"];
        }

        $operatingProfit = $proceeds - $variableExpenses;
        $netProfit = $proceeds - $totalExpenses;
        $yield = $netProfit / $proceeds * 100;

        $costOf1KgOfThings = $variableExpenses / $wtPerPeriod;
        $totalCostOf1Order = $variableExpenses / count($orders);
        $totalCostOf1Order = $totalExpenses / count($orders);

        $template = new Template("analytics/pnl_report/report", null, [
            new TemplateData(":startTime", date("d.m.Y", $start)),
            new TemplateData(":endTime", date("d.m.Y", $end)),
            new TemplateData(":proceeds", number_format($proceeds, 0, "", ".")),
            new TemplateData(":totalExpenses", number_format($totalExpenses, 0, "", ".")),
            new TemplateData(":variableExpenses", number_format($variableExpenses, 0, "", ".")),
            new TemplateData(":fixedAssets", number_format($fixedAssets, 0, "", ".")),
            new TemplateData(":operatingProfit", number_format($operatingProfit, 0, "", ".")),
            new TemplateData(":netProfit", number_format($netProfit, 0, "", ".")),
            new TemplateData(":yield", round($yield, 2)),
            new TemplateData(":costOf1KgOfThings", number_format($costOf1KgOfThings, 0, "", ".")),
            new TemplateData(":costOf1Order", number_format($costOf1Order, 0, "", ".")),
            new TemplateData(":totalCostOf1Order", number_format($totalCostOf1Order, 0, "", ".")),
        ]);

        $template = $template->Load();

        $this->sendMessage($chat_id, $template->text);
        return;
    }

    // Users report

    if ($atext[0] == '/make_users_report') {
        $this->DelMessageText($chat_id, $message_id);
        $this->del_action($chat_id);

        $template = new Template("analytics/users_report/set_dates");
        $template = $template->Load();
        $template->LoadButtons();

        $response = $this->sendMessage($chat_id, $template->text, $template->buttons);

        $this->set_action($chat_id, "users_report_set_dates&{$response["result"]["message_id"]}");
        return;
    }


    if ($atext[0] == "/users_report_set_dates_cancel") {
        $this->DelMessageText($chat_id, $message_id);
        $this->del_action($chat_id);

        $template = new Template("analytics/users_report/set_dates_cancel");
        $template = $template->Load();

        $this->sendMessage($chat_id, $template->text);
        return;
    }

    if ($get_action[0] == "users_report_set_dates" && $atext[0] && $atext[1]) {
        $this->DelMessageText($chat_id, $message_id);
        $this->DelMessageText($chat_id, $get_action[1]);
        $this->del_action($chat_id);

        $template = new Template("analytics/users_report/confirmation", null, [
            new TemplateData(":dateStartText", str_replace("_", ".", $atext[0])),
            new TemplateData(":dateEndText", str_replace("_", ".", $atext[1])),
            new TemplateData(":dateStart", $atext[0]),
            new TemplateData(":dateEnd", $atext[1]),
        ]);
        $template = $template->Load();
        $template->LoadButtons();

        $this->sendMessage($chat_id, $template->text, $template->buttons);
        return;
    }

    if ($atext[0] == '/users_report_success') {
        $this->DelMessageText($chat_id, $message_id);

        $startDate = str_replace("_", ".", $atext[1]);
        $endDate = str_replace("_", ".", $atext[2]);

        $start = strtotime(trim($startDate));

        $end = strtotime(trim($endDate)) + (23 * (60 * 60)) + (60 * 59);

        $daysCount = round(($end / 60 / 60 / 24) - ($start / 60 / 60 / 24));

        if ($start <= 0 || $end <= 0 || $daysCount < 1) {
            $this->sendMessage(ANALYTICS_CHAT_ID, "Ошибка отчёта! Некорректные даты");
            return;
        }

        $users = R::find('users', 'date_reg >= :start_date AND date_reg <= :end_date',
            [
                ':start_date' => $start,
                ':end_date' => $end
            ]
        );

        $loggedInToTheBot = "";
        $loggedInToTheBotArray = [];
        $madeAnOrder = "";
        $madeAnOrderArray = [];
        $unfinished = "";
        $unfinishedArray = [];
        foreach ($users as $user) {
            $loggedInToTheBotArray[] = $user;

            if ($user->orders_count == 0) {
                $unfinishedArray[] = $user;
            }

            if ($user->orders_count > 0) {
                $madeAnOrderArray[] = $user;
            }
        }
        
        foreach ($loggedInToTheBotArray as $user) {
            $loggedInToTheBot .= "<b>ID{$user["id"]} @{$user["username"]}</b>\n";
        }
        
        foreach ($unfinishedArray as $user) {
            $unfinished .= "<b>ID{$user["id"]} @{$user["username"]}</b>\n";
        }
        
        
        foreach ($madeAnOrderArray as $user) {
            $madeAnOrder .= "<b>ID{$user["id"]} @{$user["username"]}</b>\n";
        }
        
        $template = new Template("analytics/users_report/report", null, [
            new TemplateData(":dateStart", date("d.m.Y H:i", $start)),
            new TemplateData(":dateEnd", date("d.m.Y H:i", $end)),
            new TemplateData(":daysCount", $daysCount),
            new TemplateData(":countUsers", count($loggedInToTheBotArray)),
            new TemplateData(":loggedInToTheBotCount", count($loggedInToTheBotArray)),
            new TemplateData(":loggedInToTheBot", $loggedInToTheBot),
            new TemplateData(":unfinishedCount", count($unfinishedArray)),
            new TemplateData(":unfinished", $unfinished),
            new TemplateData(":madeAnOrderCount", count($madeAnOrderArray)),
            new TemplateData(":madeAnOrder", $madeAnOrder),
        ]);

        $template = $template->Load();

        $this->sendMessage($chat_id, $template->text);

        return;
    }
}