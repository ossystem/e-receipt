<?php

namespace Helpers;

/**CurlHelper Класс для работы с CURL */

class CurlHelper{

    /**send Отправляет запрос в налоговую
     * @param $signedData
     * @param $path
     * @return string xml
     */
    public static function send($signedData, $command, $path = "cmd"){

        $request = curl_init();

        $fixalServer = array_key_exists('FISCAL_SERVER', $_ENV) ? $_ENV['FISCAL_SERVER'] : $_SERVER['FISCAL_SERVER'];
        $connectTimeout = array_key_exists('CONNECTION_TIMEOUT', $_ENV) ? $_ENV['CONNECTION_TIMEOUT'] : $_SERVER['CONNECTION_TIMEOUT'];

        curl_setopt_array($request, [
            CURLOPT_URL => $fixalServer."/$path",
            CURLOPT_POST => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => array('Content-Type: application/octet-stream', "Content-Length: ".strlen($signedData)),
            CURLOPT_ENCODING => "",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_VERBOSE => 1,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POSTFIELDS => $signedData
        ]);

        $return = curl_exec($request);

        if(curl_errno($request) > 0)
            $return = json_encode(["error" => 'Curl error: ' . curl_error($request)]);

        curl_close($request);

        if($return && mb_substr($return, 0,11) == 'Код помилки')
            $return = json_encode(["error" => "[ " . date("Y-m-d") . " - ". $command. " ] : " . $return]);

        return $return;
    }

    /**sign Отправляет запрос на сервер ЕЦП для подписи данных
     * @param $data
     * @return string подписаный xml
     */
    public static function sign($data){

        $connectTimeout = array_key_exists('CONNECTION_TIMEOUT', $_ENV) ? $_ENV['CONNECTION_TIMEOUT'] : $_SERVER['CONNECTION_TIMEOUT'];
        $cryptServerPort = array_key_exists('CRYPT_SERVER_PORT',$_ENV) ? $_ENV['CRYPT_SERVER_PORT'] : $_SERVER['CRYPT_SERVER_PORT'];
        $cryptServer = array_key_exists('CRYPT_SERVER', $_ENV) ? $_ENV['CRYPT_SERVER'] : $_SERVER['CRYPT_SERVER'];

        $request = curl_init();

        curl_setopt_array($request, [
            CURLOPT_PORT => $cryptServerPort,
            CURLOPT_URL => $cryptServer.":".$cryptServerPort."/sign",
            CURLOPT_POST => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POSTFIELDS => $data
        ]);

        $return = json_decode(curl_exec($request));

        if(curl_errno($request) > 0)
            $return = json_encode(["error" => 'Curl error: ' . curl_error($request)]);
        else
            $return = base64_decode($return->data);

        curl_close($request);

        return $return;
    }

    /**sign Отправляет запрос на сервер ЕЦП для расшифровки данных
     * @param $data - подписаный xml
     * @return string xml
     */
    public static function decrypt($data){

        $connectTimeout = array_key_exists('CONNECTION_TIMEOUT', $_ENV) ? $_ENV['CONNECTION_TIMEOUT'] : $_SERVER['CONNECTION_TIMEOUT'];
        $cryptServerPort = array_key_exists('CRYPT_SERVER_PORT',$_ENV) ? $_ENV['CRYPT_SERVER_PORT'] : $_SERVER['CRYPT_SERVER_PORT'];
        $cryptServer = array_key_exists('CRYPT_SERVER',$_ENV) ? $_ENV['CRYPT_SERVER'] : $_SERVER['CRYPT_SERVER'];

        $request = curl_init();

        curl_setopt_array($request, [
            CURLOPT_PORT => $cryptServerPort,
            CURLOPT_URL => $cryptServer.":".$cryptServerPort."/decrypt",
            CURLOPT_POST => true,
            CURLOPT_ENCODING => "",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_VERBOSE => 1,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POSTFIELDS => base64_encode($data)
        ]);

        $return = json_decode(curl_exec($request));

        if(curl_errno($request) > 0)
            $return = json_encode(["error" => 'Curl error: ' . curl_error($request)]);
        else
            $return = base64_decode($return->data);

        curl_close($request);

        return $return;
    }
}