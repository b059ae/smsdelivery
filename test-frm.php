<?php
if (!empty($_POST)){

require_once('smsdelivery.class.php');
$sms = new SMSDelivery($_POST['username'], $_POST['password']);
$text = $_POST['text'];

/**
 * Проверяем текущий баланс
 */
echo 'Текущий баланс: '.$sms->GetBalance().'<br/>';

/**
 * Отправляем СМС-сообщение на указанный номер
 * 1-ый параметр - номер абонента
 * 2-ой параметр - имя отправителя (выдается службой поддержки)
 * 3-ий параметр - текст сообщения. Обратите внимание что для передачи СМС
 *                 кириллицей необходимо чтобы текст был в UTF-8 кодировке
 * 4-ый параметр - является ли сообщение Flash-сообщением (т.е. не сохраняется
 *                 во Входящих, а просто появляется на экране телефона).
 *                 Длина Flash сообщения не может быть больше длины одного СМС
 *                 (160 символов - латиница, 70 - кириллица)
 * 5-ый параметр - максимальный период доставки сообщения (в часах)
 */
$res = $sms->SendMessage($_POST['recepient'], $_POST['sender'], $text, !empty($_POST['flash']), 120);

/**
 * Если была ошибка, выдаем ее текст
 */
if (!$res){
    echo 'Ошибка: '.$sms->getError().'<br/>';
} else {
    /**
     * Если все удачно, выводим ID сообщения
     */
    echo 'Id сообщения: '.$res['messageId'].'<br/>';

    /**
     * И получаем его статус
     */
    $res = $sms->GetMessageStatus($res['messageId']);

    /**
     * Если все было успешно
     */
    if (false !== $res){
        echo 'Статус сообщения: '.$res.'<br/>';
        /**
         * Проверям баланс еще один раз
         */
        echo 'Осталось: '.$sms->GetBalance().'<br/>';
    } else {
        echo 'Ошибка: '.$sms->getError();
    }
}

echo '<br/><a href="">Повторить</a>';
} else {
?>

<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title> Отсылка СМС через SMSDelivery.ru </title>
</head>
<body>
        <h3 id="header">Отсылка СМС через Веб-сервис SMSDelivery.ru</h3>
        <form action="" method="post" id="form">
        <fieldset id="login">
            <legend id="login_legend">Введите следующие данные</legend>
                <label for="username">Имя пользователя:</label> <input type="text" name="username" id="username"/><br/>
                <label for="password">Пароль:</label> <input type="text" name="password" id="password"/><br/>
                <label for="sender">Отправитель:</label> <input type="text" name="sender" id="sender"/><br/>
                <label for="recepient">Получатель (номер):</label><input type="text" name="recepient" id="recepient"><br/>
                <label for="flash">Flash-сообщение?</label> <input type="checkbox" name="flash" id="flash"/><br/>
                <label for="text">Текст сообщения:</label><br/><textarea name="text" id="text"></textarea><br/>
                <input type="submit" value="Отправить"/>
        </fieldset>
        </form>
</body>
</html>
<? } ?>