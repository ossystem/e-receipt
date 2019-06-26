<?php

namespace Models;

use models\BaseModel;

class OrderItemsModel extends BaseModel {

    public static function addItem($params){

        self::db()->exec("INSERT INTO `order_items` (
            `item_id`,
            `count`,
            `order_id`,
            `pdv_sum`,
            `excise_sum`,
            `cost`
            ) VALUES (:item_id, :count, :order_id, :pdv_sum, :excise_sum, :cost)", $params);
    }

    public static function updateExciseAndSum($params){
        self::db()->exec("UPDATE `order_items` SET `pdv_sum` = :pdv_sum, `excise_sum` = :excise_sum WHERE `id` = :id", $params);
    }

    public static function updateFixalNum($fixalNum){
        self::db()->exec("UPDATE order_items SET fixal_num=:fixalNum WHERE fixal_num=0 ", [":fixalNum" => $fixalNum]);
    }

    public static function updateOrderId($orderId){
        self::db()->exec("UPDATE order_items SET order_id=:order_id WHERE order_id=0 ", [":order_id" => $orderId]);
    }

    public static function get(){
        $items = self::db()->exec("SELECT * FROM order_items AS oi LEFT JOIN items AS i ON oi.item_id = i.id");
        return json_decode(json_encode($items));
    }

    public static function clear(){
        self::db()->exec("TRUNCATE TABLE orders");
        self::db()->exec("TRUNCATE TABLE order_items");
    }
}
