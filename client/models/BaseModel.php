<?php

namespace Models;

abstract class BaseModel{

    private static $db;

//    private function __construct()
//    {
//        self::$db = new \DB\SQL('mysql:host=localhost;port=3306;dbname=ereceipt','root','123456');
//    }

    public static function db(){
        if(!self::$db)
            self::$db = new \DB\SQL('mysql:host=localhost;port=3306;dbname=ereceipt','root','123456');
        return self::$db;
    }

}
