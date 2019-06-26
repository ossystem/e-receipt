<?php

namespace Models;

use models\BaseModel;

class TaxModel extends BaseModel {

    public static function addItem($params){

        self::db()->exec("INSERT INTO `tax` (
            `pdv_sum`,
            `excise_sum`,
            ) VALUES (:pdv_sum, :excise_sum)", $params);

        return self::db()->lastInsertId();
    }

    public static function updateFixalNum($fixalNum){
        self::db()->exec("UPDATE tax SET fixal_num=:fixalNum WHERE fixal_num=0 ", [":fixalNum" => $fixalNum]);
    }

    public static function get(){
        $items = self::db()->exec("SELECT * FROM tax");
        return json_decode(json_encode($items));
    }

}
