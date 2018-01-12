<?php

/**
 * Клиент к веб-сервису SMSDelivery.ru
 * http://www.smsdelivery.ru
 * @copyright ЗАО "ВендоСофт"
 * @version 1.0
 * @since 2009-12-18
 * @author Евгений Богданов
 *
 */
class SMSDelivery {

    /**
     * Имя пользователя
     *
     * @var string
     */
    var $username;

    /**
     * Пароль
     *
     * @var string
     */
    var $password;

    /**
     * Текст сообщения об ошибке
     *
     * @var string
     */
    var $errorMsg;

    /**
     * Пояснения статуса сообщения
     *
     * @var array
     */
    var $status;

    /**
     * Конструктор класса.
     * В качестве параметров нужно указать имя пользователя и пароль
     *
     * @param string $username Имя пользователя
     * @param string $password Пароль
     * @return SMSDelivery
     */
    function SMSDelivery($username, $password){
        $this->username = $username;
        $this->password = $password;
        // Статусы сообщения
        $this->status = array('EnQueue'  => 'Добавлено в очередь на обработку',
                              'EnRoute'  => 'Отправлено',
                              'Delivered' => 'Доставлено',
                              'Deleted'  => 'Удалено',
                              'Expired'  => 'Недоставлено. Превышен TTL сообщения',
                              'Rejected' => 'Отклонено',
                              'UnDeliverable' => 'Невозможно доставить',
                              'Unknown'  => 'Невозможно получить статус сообщения. Попробуйте повторить попытку позже',
                              'Error'    => 'Внутренняя ошибка сервиса');
    }

    /**
     * Задает имя пользователя
     *
     * @param string $username Имя пользователя в системе
     * @return bool
     */
    function setUsername($username){
        return (bool) $this->username = $username;
    }

    /**
     * Устаналивает пароль для авторизации в сервисе
     *
     * @param string $password Пароль
     * @return bool
     */
    function setPassword($password){
        return (bool) $this->password = $username;
    }

    /**
     * Отсылает СМС-сообщение
     * При отсылке сообщения кириллицей текс сообщения должен быть в кодировке UTF-8
     * Обратите внимание что Flash-сообщение не можуть быть длиной больше одного СМС,
     * т.е. или 70 символов для кириллицы, или 160 для латиницы
     *
     * @param string $destNumber Номер получателя
     * @param string $senderAddr Номер отправителя (выдается сервисом)
     * @param string $text       Текст сообщения
     * @param bool $isFlash      Является ли сообщение Flash-сообщением
     * @param int $lifeTime      Максимальный период доставки, измеряется в часах.
     * @return array             false в случае ошибки
     */
    function SendMessage($destNumber, $senderAddr, $text, $isFlash = false, $lifeTime = 144){
        // Удаляем из номера назначения все не цифровые символы
        if (function_exists('preg_replace')){
            $destNumber = preg_replace('#[^0-9]#', '', $destNumber);
        } else {
            $numbers = '0123456789';
            $tmp = '';
            for($i=0,$length=strlen($destNumber);$i<$length;$i++){
                if (false !== strpos($numbers, $destNumber[$i])){
                    $tmp .= $destNumber[$i];
                }
            }
            $destNumber = $tmp;
        }
        // Преобразуем текст
        $result = '';
        for($i=0, $length=strlen($text);$i<$length;$i++){
            $tmp = $text[$i];
            switch($text[$i]){
                case '"':
                    $tmp = '&quot;';
                break;

                case '&':
                    $tmp = '&amp;';
                break;

                case chr(39):
                    $tmp = '&apos;';
                break;

                case '<':
                    $tmp = '&lt;';
                break;

                case '>':
                    $tmp = '&gt;';
                break;
            }
            $result .= $tmp;
        }
        $text = $result;

        // Формируем запрос
        $soapBody =
        '<?xml version="1.0" encoding="utf-8"?>
                <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                    <soap:Body>
                        <SendMessage xmlns="http://smsdelivery.ru/">
                            <userName>'.$this->username.'</userName>
                            <password>'.$this->password.'</password>
                            <isFlash>'.(!$isFlash ? 'false' : 'true').'</isFlash>
                            <lifeTime>'.(int) $lifeTime.'</lifeTime>
                            <destNumber>'.$destNumber.'</destNumber>
                            <senderAddr>'.$senderAddr.'</senderAddr>
                            <text>'.$text.'</text>
                        </SendMessage>
                    </soap:Body>
                </soap:Envelope>';

        // Отсылаем запрос и возвращаем ответ
        $reply = $this->SendRequest($soapBody, 'SendMessage');
        return $reply;
    }

    /**
     * Получает статус сообщения
     *
     * @param int $messageId ID сообщения, возвращенное функцией SendMessage
     * @return string        false в случае ошибки
     */
    function GetMessageStatus($messageId){
        // Формируем запрос
        $soapBody =
        '<?xml version="1.0" encoding="utf-8"?>
                <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                    <soap:Body>
                        <GetMessageStatus xmlns="http://smsdelivery.ru/">
                            <userName>'.$this->username.'</userName>
                            <password>'.$this->password.'</password>
                            <messageID>'.(int) $messageId.'</messageID>
                        </GetMessageStatus>
                    </soap:Body>
                </soap:Envelope>';

        // Отсылаем запрос и возвращаем ответ
        $reply = $this->SendRequest($soapBody, 'GetMessageStatus');
        return $reply;
    }

    /**
     * Возвращает текущий баланс пользователя
     *
     * @return float
     */
    function GetBalance(){
        // Формируем запрос
        $soapBody = '<?xml version="1.0" encoding="utf-8"?>
         <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
            <soap:Body>
                <GetBalance xmlns="http://smsdelivery.ru/">
                    <userName>'.$this->username.'</userName>
                    <password>'.$this->password.'</password>
                </GetBalance>
            </soap:Body>
        </soap:Envelope>';

        // Отсылаем  запрос и возвращаем ответ
        $reply = $this->SendRequest($soapBody, 'GetBalance');
        return $reply;
    }

    /**
     * Отсылает сформированный запрос к веб-сервису.
     * Определяет была ли операция успешной.
     *
     * @param string $body   Тело сообщения
     * @param string $action Тип запроса
     * @return mixed
     */
    function SendRequest($body, $action){
        $this->errorMsg = null;
        $reply = $result = $part = '';

        /**
         * Строим HTTP-заголовки, которые нужно будет передать
         */
        $headers = array(
        "POST /SMSWebservice.asmx HTTP/1.1",
        "Host: ws1.smsdelivery.ru",
        "Connection: close",
        "Content-Type: text/xml; charset=utf-8",
        "Content-length: ".strlen($body),
        "SOAPAction: http://smsdelivery.ru/".$action);

        /**
         * Если у нас есть возможность пользоваться cURL
         */
        if (extension_loaded('curl')){
            $ch = curl_init('http://ws1.smsdelivery.ru/SMSWebservice.asmx');
            // Задаем конфигурацию транспорта
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            curl_setopt($ch, CURLOPT_USERAGENT, 'SMSDelivery.ru (PHP class v.0.1)');
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

            /**
             * Выполняем запрос с веб-сервису
             */
            $reply = curl_exec($ch);

            // Если была ошибка, сохраняем ее и выходим
            if (curl_errno($ch)) {
                $this->errorMsg = curl_error($ch);
                return false;
            } else {
                curl_close($ch);
            }
        } else {
            /**
             * Если нет транспорта в виде cURL, используем сокеты
             */
            if($fp = fsockopen('ws1.smsdelivery.ru',	80,	$errno,	$this->errorMsg, 20)){
                // удачно подключились, продолжаем работу
                socket_set_timeout($fp, 20);
                // Строим из массива заголовков строку, с разделителями "перевод строки"
                $header = implode($headers, "\r\n");
                // Дополняем тело запроса заголовками
                $body = $header."\r\n\r\n".$body;
                // Отсылаем запрос на сервер
                fputs($fp, $body);
                $reply = "";
                // Получаем ответ с сервера
                do {
                    $part = fgets($fp, 4096);
                    if ('' == $part) break;
                    $reply .= $part;
                } while(!feof($fp));
                fclose($fp);
                // Удаляем HTTP-заголовки из тела ответа
                $reply = substr($reply, strpos($reply, '<?xml'));
            } else {
                // Что-то не так, говорим об ошибке
                if (!$this->errorMsg){
                    switch($errno){
                        case -3:
                            $this->errorMsg='Socket creation failed (-3)';
                        break;
                        case -4:
                            $this->errorMsg='DNS lookup failure (-4)';
                        break;
                        case -5:
                            $this->errorMsg='Connection refused or timed out (-5)';
                        break;
                        default:
                            $this->errorMsg='Connection failed ('.$errno.')';
                    }
                }
                return false;
            }
        }
        // Парсим ответ от сервера
        $result = $this->ParseReply($reply, $action);
        // Разобранный ответ возвращаем
        return $result;
    }

    /**
     * Возвращает текст ошибки
     *
     * @return string
     */
    function getError(){
        return $this->errorMsg;
    }

    /**
     * Разбирает ответ от веб-сервиса
     *
     * @param string $replyBody XML-тело ответа от веб-сервиса
     * @param string $type      Тип запроса к серверу
     * @return mixed
     */
    function ParseReply($replyBody, $type){
        $return = false;
        $result = $values = $values1 = array();
        if (!function_exists('xml_parser_create')){
            // Если есть поддержка xml
            $parser = xml_parser_create();
            // Разбираем ответ в структуру
            xml_parse_into_struct($parser, $replyBody, $result);
            // проверям на ошибку
            $return = $this->setError($result[4]['value']);
            if ($return){
                switch ($type){
                    // Запрос баланса
                    case 'GetBalance':
                        $return = floatval($result[5]['value']);
                    break;

                    // Отправление СМС
                    case 'SendMessage':
                        $return = array('messageId' => (int) $result[5]['value'],
                                        'segments'  => (int) $result[6]['value']);
                    break;

                    // Получение статуса сообщения
                    case 'GetMessageStatus':
                         $return = (empty($this->status[$result[5]['value']])) ? $result[5]['value']
                                                                               : $this->status[$result[5]['value']];
                    break;

                    // Неизвестный тип сообщения
                    default:
                        $this->errorMsg = 'Неизвестный тип сообщения';
                        $return = false;
                }
            }
        } else {
            // Если нет поддержки XML, работаем с регулярными выражениями
            // Первым делом выбираем статус команды
            preg_match('#<Result>([^<]+)#', $replyBody, $result);
            $return = $this->setError($result[1]);
            // Если это не ошибка
            if ($return){
                switch ($type){
                    // Запрос баланса
                    case 'GetBalance':
                        preg_match('#<Balance>([^<]+)#', $replyBody, $values);
                        $return = floatval($values[1]);
                        break;

                    // Отправление СМС
                    case 'SendMessage':
                        preg_match('#<MessageID>([^<]+)#', $replyBody, $values);
                        preg_match('#<SegmentsNumber>([^<]+)#', $replyBody, $values1);
                        $return = array('messageId' => (int) $values[1],
                                        'segments'  => (int) $values1[1]);
                        break;

                    // Получение статуса сообщения
                    case 'GetMessageStatus':
                        preg_match('#<MessageStatus>([^<]+)#', $replyBody, $values);
                        $return = (empty($this->status[$values[1]]) ? $values[1]
                                                                    : $this->status[$values[1]]);
                        break;

                    // Неизвестный формат пакета
                    default:
                        $this->errorMsg = 'Неизвестный тип сообщения';
                        $return = false;
                }
            }
        }

        return $return;
    }

    /**
     * Проверяет является ли ответ от сервера успешным, и если нет - задает ошибку
     *
     * @param string $msg
     * @return bool
     */
    function setError($msg){
        $isNotError = false;
        switch($msg){
            /**
             * Нет ошибки
             */
            case 'OK':
                $this->errorMsg = null;
                $isNotError = true;
                break;

            /**
             * Неверное имя пользователя или пароль
             */
            case 'InvalidCredentials':
                $this->errorMsg = 'Неверное имя пользователя или пароль';
                break;

            /**
             * Неверный номер отправителя
             */
            case 'InvalidSenderAddress':
                $this->errorMsg = 'Неверный номер отправителя';
                break;

            /**
             * Неверный номер получателя
             */
            case 'InvalidReceiverAddress':
                $this->errorMsg = 'Неверный номер получателя';
                break;

            /**
             * Неверное значение параметра Flash
             */
            case 'InvalidFlashMessage':
                $this->errorMsg = 'Неверное значение параметра Flash';
                break;

            /**
             * Сообщение заблокировано
             */
            case 'MessageBlocked':
                $this->errorMsg = 'Сообщение заблокировано';
                break;

            /**
             * Недостаточно средств на лицевом счете
             */
            case 'InvalidBalance':
                $this->errorMsg = 'Недостаточно средств на лицевом счете';
                break;

            /**
             * Аккаунт отключен
             */
            case 'UserDisabled':
                $this->errorMsg = 'Аккаунт отключен';
                break;

            /**
             * Ошибка хранилища данных.
             */
            case 'DatabaseOffline':
                $this->errorMsg = 'Ошибка БД. Попробуйте повторить запрос позже';
                break;

            /**
             * Незнакомая ошибка
             */
            case 'UnKnown':
                $this->errorMsg = 'Незнакомая ошибка. Свяжитесь со службой поддержки';
                break;

            /**
             * Ошибка сервиса
             */
            case 'Error':
                $this->errorMsg = 'Внутренняя ошибка сервиса. Свяжитесь со службой поддержки';
                break;

           /**
            * Неизвестный нам ответ
            */
            default:
                $this->errorMsg = 'Неверный ответ от сервера';
                break;
        }
        // возвращаем значение
        return $isNotError;
    }
}
?>