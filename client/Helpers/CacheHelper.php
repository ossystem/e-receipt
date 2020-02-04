<?php

namespace Helpers;

/**CacheHelper Класс для работы с закешироваными данными */

class CacheHelper{

    private static $pathToCachedResponses = './json/responses';

    public static function responseCache($params, $fileName){
        $response = CurlHelper::send($params);
        file_put_contents($fileName, $response);
        $obj = json_decode($response);
        return $obj;
    }

    public static function getObjectByGUID($guid){

        $obj = null;

        $fileName = self::$pathToCachedResponses . "/objects.json";

        if(is_file($fileName))
            $obj = json_decode(file_get_contents($fileName));

        if(!$obj || !$obj->TaxObjects)
            $obj = self::responseCache(["Command" => "Objects"], $fileName);

        $arr = [];

        if($obj && $obj->TaxObjects)
            $arr = array_shift(array_filter($obj->TaxObjects, function($v) use ($guid) {return $v->Guid == $guid;}));
        return $arr;
    }

    public static function getAllParams($guid, $id){

        $obj = self::getObjectByGUID($guid);

        $cash = [];

        if($obj->CashRegisters) {
            $cash = array_shift(array_filter($obj->CashRegisters, function ($v) use ($id) {
                return $v->NumFiscal == $id;
            }));
        }

        $cashRegister = null;

        $fileName = self::$pathToCachedResponses. "/cash$id.json";

        if(is_file($fileName))
            $cashRegister = json_decode(file_get_contents($fileName));

        if(!$cashRegister || !$cashRegister->NextLocalNum)
            $cashRegister = self::responseCache([
                "Command" => "TransactionsRegistrarState",
                "NumFiscal" => $id
            ], $fileName);

        return [$obj, $cash, $cashRegister];
    }

}