<?php

namespace Helpers;

class CurlHelper{

    private static $fixalServer = "http://80.91.165.208/er";
    private static $cryptServer = "http://192.168.1.172:3100";

    public static function getCurlResponse($signedData, $path = "cmd"){

        $request = curl_init();
        curl_setopt_array($request, [
            CURLOPT_URL => self::$fixalServer."/$path",
            CURLOPT_POST => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => array('Content-Type: application/octet-stream', "Content-Length: ".strlen($signedData)),
            CURLOPT_ENCODING => "",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_VERBOSE => 1,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POSTFIELDS => $signedData
        ]);

        $return = curl_exec($request);

        if(curl_errno($request))
            $return = json_encode(["error" => 'Curl error: ' . curl_error($request)]);

        curl_close($request);

        return $return;
    }

    public static function sign($data){

        file_put_contents("./bin/fp".time().".txt", $data);

        $request = curl_init();

        curl_setopt_array($request, [
            CURLOPT_PORT => "3100",
            CURLOPT_URL => self::$cryptServer."/sign",
            CURLOPT_POST => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 20,
//            CURLOPT_VERBOSE => 1,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POSTFIELDS => $data
        ]);

        $return = json_decode(curl_exec($request));

        if(curl_errno($request))
            $return = json_encode(["error" => 'Curl error: ' . curl_error($request)]);
        else
            $return = base64_decode($return->data);

        curl_close($request);

        return $return;
    }

    public static function decrypt($data){

        $request = curl_init();

        curl_setopt_array($request, [
            CURLOPT_URL => self::$cryptServer."/decrypt",
            CURLOPT_POST => true,
            CURLOPT_ENCODING => "",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_VERBOSE => 1,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POSTFIELDS => base64_encode($data)
        ]);

        $return = json_decode(curl_exec($request));

        if(curl_errno($request))
            $return = json_encode(["error" => 'Curl error: ' . curl_error($request)]);
        else
            $return = base64_decode($return->data);

        curl_close($request);


        return $return;

    }
}