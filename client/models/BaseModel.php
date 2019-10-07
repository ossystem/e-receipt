<?php

namespace Models;

abstract class BaseModel{

    private static $db;

//    private function __construct()
//    {
//        self::$db = new \DB\SQL('mysql:host=localhost;port=3306;dbname=ereceipt','root','123456');
//    }

    public static function db(){
        if(!self::$db) {
            $mysqlHost = array_key_exists('MYSQL_HOST', $_ENV) ? $_ENV['MYSQL_HOST'] : $_SERVER['MYSQL_HOST'];
            $mysqlPort = array_key_exists('MYSQL_PORT', $_ENV) ? $_ENV['MYSQL_PORT'] : $_SERVER['MYSQL_PORT'];
            $mysqlDb   = array_key_exists('MYSQL_DB',   $_ENV) ? $_ENV['MYSQL_DB'] : $_SERVER['MYSQL_DB'];
            $mysqlUser = array_key_exists('MYSQL_USER', $_ENV) ? $_ENV['MYSQL_USER'] : $_SERVER['MYSQL_USER'];
            $mysqlPass = array_key_exists('MYSQL_PASS', $_ENV) ? $_ENV['MYSQL_PASS'] : $_SERVER['MYSQL_PASS'];

            try {
                self::$db = new \DB\SQL("mysql:host=$mysqlHost;port=$mysqlPort;dbname=$mysqlDb", $mysqlUser, $mysqlPass);

            } catch(\Exception $e){
                var_dump($e->getTrace());
            }
        }
        return self::$db;
    }

}
