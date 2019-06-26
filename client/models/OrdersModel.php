<?php

namespace Models;

use models\BaseModel;

class OrdersModel extends BaseModel {

    public static function addItem($params){

        self::db()->exec("INSERT INTO `orders` (
            `guid`,
            `fixal_num`,
            `sum`,
            `sum_real`,
            `sum_card`,
            `order_type`
            ) VALUES (:guid, :fixal_num, :sum, :sum_real, :sum_card, :order_type)", $params);

        return self::db()->lastInsertId();
    }

    public static function updateFixalNum($fixalNum){
        self::db()->exec("UPDATE orders SET fixal_num=:fixalNum WHERE fixal_num=0 ", [":fixalNum" => $fixalNum]);
    }


    public static function getOrdersWithItems(){
        $items = self::db()->exec("SELECT *, o.fixal_num AS fixal_num FROM (orders AS o LEFT JOIN order_items AS oi ON oi.order_id = o.id) LEFT JOIN items AS i ON oi.item_id = i.id GROUP BY oi.id");
        return json_decode(json_encode($items));
    }

    public static function get(){
        $items = self::db()->exec("SELECT * FROM orders");
        return json_decode(json_encode($items));
    }

    public static function clear(){
        self::db()->exec("TRUNCATE TABLE order_items");
        self::db()->exec("TRUNCATE TABLE orders");
    }

}
