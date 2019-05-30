<?php

namespace Helpers;

class CurlHelper{

    private static $fixalServer = "http://80.91.165.208/er";
    private static $cryptServer = "http://192.168.1.172:3100";

    public static function getCurlResponse($signedData, $path = "cmd"){

        $request = curl_init(self::$fixalServer."/$path");
        curl_setopt($request, CURLOPT_POST, true);
        curl_setopt($request, CURLOPT_HEADER, false);
        curl_setopt($request, CURLOPT_HTTPHEADER, array('Content-Type: application/octet-stream'));
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($request, CURLOPT_VERBOSE, 1);
        curl_setopt(
            $request,
            CURLOPT_POSTFIELDS,
            $signedData//file_get_contents($file)
        );

        $return = curl_exec($request);

        if(curl_errno($request))
            $return = json_encode(["error" => 'Curl error: ' . curl_error($request)]);

        curl_close($request);

        return $return;
    }

    public static function sign($data){

        $request = curl_init(self::$cryptServer."/sign");
        curl_setopt($request, CURLOPT_POST, true);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($request, CURLOPT_VERBOSE, 1);
        curl_setopt(
            $request,
            CURLOPT_POSTFIELDS,
            $data
        );

        $return = json_decode(curl_exec($request));

        if(curl_errno($request))
            $return = json_encode(["error" => 'Curl error: ' . curl_error($request)]);
        else
            $return = base64_decode($return->data);

        curl_close($request);

        return $return;
    }

    public static function decrypt($data){

        $request = curl_init(self::$cryptServer."/decrypt");
        curl_setopt($request, CURLOPT_POST, true);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($request, CURLOPT_VERBOSE, 1);
        curl_setopt(
            $request,
            CURLOPT_POSTFIELDS,
            base64_encode($data)
        );

        $return = json_decode(curl_exec($request));

        if(curl_errno($request))
            $return = json_encode(["error" => 'Curl error: ' . curl_error($request)]);
        else
            $return = base64_decode($return->data);

        curl_close($request);


        return $return;

    }
}