<?php

namespace Helpers;

/**CurlHelper Класс для работы с CURL */

class CurlHelper{

    /**send Отправляет запрос на api сервер
     * @param $params
     * @return string xml
     */
    public static function send($params){

        $apiServerName = array_key_exists('API_SERVER', $_ENV) ? $_ENV['API_SERVER'] : $_SERVER['API_SERVER'];

        $ch = curl_init($apiServerName);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        if(curl_errno($ch) > 0)
            $response = json_encode(["error" => 'Curl error: ' . curl_error($ch)]);

        curl_close($ch);

        $obj = json_decode($response);

        if($obj->error) {
            echo $obj->error;
            die();
        }

        return $response;
    }

}