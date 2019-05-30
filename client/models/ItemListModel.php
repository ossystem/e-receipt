<?php

namespace Models;

use models\BaseModel;

class ItemListModel extends BaseModel {

    public static function addItem($params){

        self::db()->exec("INSERT INTO `item_list` (
            `guid`,
            `item_id`,
            `count`
            ) VALUES (:guid, :item_id, :count) ON DUPLICATE KEY UPDATE count=count+:count", $params);
    }

    public static function get(){
        $items = self::db()->exec("SELECT * FROM item_list AS il LEFT JOIN items AS i ON il.item_id = i.id");
        return json_decode(json_encode($items));
    }

    public static function clear(){
        $items = self::db()->exec("TRUNCATE TABLE item_list");
        return json_decode(json_encode($items));
    }
//
//    public static function getById($id){
//        $items = self::db()->exec("SELECT * FROM items WHERE id=:id", [":id" => $id]);
//        return json_decode(json_encode($items[0]));
//    }
//
//    public static function updateItem($id, $params){
//        $setParams = "";
//        foreach($params as $k=>$p){
//            $field = ltrim($k, ":");
//            $setParams.= ($setParams?",":"")."$field=$k";
//        }
//        return self::db()->exec("UPDATE `items`
//              SET $setParams
//              WHERE id=:id", array_merge($params, [":id"=>$id]));
//    }
//
//    public static function remove($id){
//        return self::db()->exec("DELETE FROM `items`
//              WHERE id=:id", [":id"=>$id]);
//    }

}
