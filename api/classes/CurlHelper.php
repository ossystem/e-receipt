<?php

namespace Helpers;

/**CurlHelper Класс для работы с CURL */

class CurlHelper{

    /** @var string $fixalServer - url сервера налоговой */
    private static $fixalServer = "http://80.91.165.208/er";
    /** @var string $cryptServerPort - порт сервера ЕЦП */
    private static $cryptServerPort = 3100;
    /** @var string $fixalServer - url сервера ЕЦП */
    private static $cryptServer = "http://192.168.1.172";
    /** @var int $connectTimeout - ожидание сервера */
    private static $connectTimeout = 20;

    /**send Отправляет запрос в налоговую
     * @param $signedData
     * @param $path
     * @return string xml
     */
    public static function send($signedData, $path = "cmd"){

        $request = curl_init();
        curl_setopt_array($request, [
            CURLOPT_URL => self::$fixalServer."/$path",
            CURLOPT_POST => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => array('Content-Type: application/octet-stream', "Content-Length: ".strlen($signedData)),
            CURLOPT_ENCODING => "",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => self::$connectTimeout,
            CURLOPT_VERBOSE => 1,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POSTFIELDS => $signedData
        ]);

        $return = curl_exec($request);

        if(curl_errno($request) > 0)
            $return = json_encode(["error" => 'Curl error: ' . curl_error($request)]);

        curl_close($request);

        return $return;
    }

    /**sign Отправляет запрос на сервер ЕЦП для подписи данных
     * @param $data
     * @return string подписаный xml
     */
    public static function sign($data){

        $request = curl_init();

        curl_setopt_array($request, [
            CURLOPT_PORT => self::$cryptServerPort,
            CURLOPT_URL => self::$cryptServer.":".self::$cryptServerPort."/sign",
            CURLOPT_POST => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => self::$connectTimeout,
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

        $request = curl_init();

        curl_setopt_array($request, [
            CURLOPT_PORT => self::$cryptServerPort,
            CURLOPT_URL => self::$cryptServer.":".self::$cryptServerPort."/decrypt",
            CURLOPT_POST => true,
            CURLOPT_ENCODING => "",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => self::$connectTimeout,
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