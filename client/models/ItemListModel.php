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

}
