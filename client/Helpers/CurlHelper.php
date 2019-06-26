<?php

namespace Helpers;

/**CurlHelper Класс для работы с CURL */

class CurlHelper{

    /** @var string $apiServer - url api сервера */
    private static $apiServer = "http://seleznyov9300.ossystem.ua/";

    /**send Отправляет запрос на api сервер
     * @param $params
     * @return string xml
     */
    public static function send($params){

        $apiServerName = self::$apiServer;

        $ch = curl_init($apiServerName);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

}