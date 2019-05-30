<?php

namespace Models;

use models\BaseModel;

class ItemModel extends BaseModel {

    public static function addItem($params){

//        foreach ($params as $k=>$v){
//            $params[":$k"] =$v;
//            unset($params[$k]);
//        }
//        var_dump(self::db(), $params);
//        die();
        self::db()->exec("INSERT INTO `items` (
            `code`,
            `title`,
            `price`,
            `code_uktz`,
            `em_code`,
            `em_title`,
            `pdv_litera`,
            `pdv_stavka`,
            `action_litera`,
            `action_stavka`
            ) VALUES (:code, :title, :price, :code_uktz, :em_code, :em_title, :pdv_litera, :pdv_stavka, :action_litera, :action_stavka)",
                $params
            );
    }

    public static function get(){
        $items = self::db()->exec("SELECT * FROM items");
        return json_decode(json_encode($items));
    }

    public static function getById($id){
        $items = self::db()->exec("SELECT * FROM items WHERE id=:id", [":id" => $id]);
        return json_decode(json_encode($items[0]));
    }

    public static function updateItem($id, $params){
        $setParams = "";
        foreach($params as $k=>$p){
            $field = ltrim($k, ":");
            $setParams.= ($setParams?",":"")."$field=$k";
        }
        return self::db()->exec("UPDATE `items` 
              SET $setParams 
              WHERE id=:id", array_merge($params, [":id"=>$id]));
    }

    public static function remove($id){
        return self::db()->exec("DELETE FROM `items` 
              WHERE id=:id", [":id"=>$id]);
    }

}
