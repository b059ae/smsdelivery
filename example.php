﻿<?php

require_once('smsdelivery.class.php');

/**
 * Тест
 */
$username = 'USERNAME';
$password = 'PASSWORD';
$destNumber = '+7(911)222333222';
$sender   = 'smsdelivery';

$sms_text = 'Это "тестовое СМС"! Спасибо за выбор нашего сервиса <<&SMSDelivery.ru&>>';


/**
 * Создаем объект, указав авторизационные данные - логин и пароль
 */
$sms = new SMSdelivery($username, $password);

/**
 * Проверяем текущий баланс
 */
echo 'Текущий баланс: '.$sms->GetBalance().PHP_EOL;

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
$res = $sms->SendMessage($destNumber, $sender, $sms_text, false, 120);

/**
 * Если была ошибка, выдаем ее текст
 */
if (!$res){
    echo $sms->getError().PHP_EOL;
} else {
    /**
     * Если все удачно, выводим ID сообщения
     */
    echo 'Id сообщения: '.$res['messageId'].PHP_EOL;

    /**
     * И получаем его статус
     */
    $res = $sms->GetMessageStatus($res['messageId']);

    /**
     * Если все было успешно
     */
    if (false !== $res){
        echo 'Статус сообщения: '.$res.PHP_EOL;
    } else {
        echo $sms->getError();
    }
    /**
     * Проверям баланс еще один раз
     */
    echo 'Баланс после отправления СМС: '.$sms->GetBalance().PHP_EOL;
}
?>