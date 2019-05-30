<?php

namespace Helpers;

class XMLHelper{

    private static function filler($xmlDoc, $params){

            foreach($params as $k => $v){
                if(!is_array($params[$k]))
                    $xmlDoc->{$k} = $params[$k];
                else
                    $xmlDoc->{$k} = self::filler($xmlDoc->{$k}, $params[$k]);
            }

        return $xmlDoc;
    }

    private static function fillCategory($xmlDoc, $params, $field, $createElement = false, $index=0){
        $category = $xmlDoc->{$field};

        if($createElement) {
            foreach($params as $k=>$v){
                $cat = $category->addChild($createElement);
                $cat->addAttribute("ROWNUM", $k+1);
                foreach ($v as $k1=>$v1)
                    $cat->addChild($k1,$v1);
            }
        }else{
            foreach($params as $k=>$v){
                $category->{$k} = $v;
            }
        }

        return $category;
    }

    private static function fillCheckHead($xmlDoc, $params, $headField = 'CHECKHEAD'){

        $checkHead = self::fillCategory($xmlDoc, $params, $headField);

        $datetime = new \DateTime('now');

        $checkHead->ORDERDATE = $datetime->format("dmY");
        $checkHead->ORDERTIME = $datetime->format("His");

        return $xmlDoc;
    }

    public static function makeShiftXML($params){

        $file = realpath("./xml/shiftOpen.xml");
        $xmlDoc = new \SimpleXMLElement(file_get_contents($file));
        $xmlDoc = self::fillCheckHead($xmlDoc, $params->CHECKHEAD);
        //$xmlDoc->CHECKHEAD = $CheckHead;
        file_put_contents("./xml/shiftOpenCopy.xml",$xmlDoc->asXML());
        return file_get_contents("./xml/shiftOpenCopy.xml");
    }

    public static function makeZFormXML($params){

        $file = realpath("./xml/zform.xml");
        $xmlDoc = new \SimpleXMLElement(file_get_contents($file));
        $xmlDoc = self::fillCheckHead($xmlDoc, $params->ZFORMHEAD, 'ZFORMHEAD');

       // $zFormPay  = self::fillCategory($xmlDoc, $params->ZFORMPAY, "ZFORMPAY");
//        $zFormPay  = self::fillCategory($xmlDoc, $params->ZFORMPAY->ZFORMREALIZ, "ZFORMPAY->ZFORMREALIZ");

//        $checkPay = self::fillCategory($xmlDoc, $params->CHECKPAY, "CHECKPAY","ROW", 1);
//        $checkTax = self::fillCategory($xmlDoc, $params->CHECKTAX, "CHECKTAX","ROW", 1);
//        $checkExcise = self::fillCategory($xmlDoc, $params->CHECKEXCISE, "CHECKEXCISE","ROW",1);
//        $checkBody = self::fillCategory($xmlDoc, $params->CHECKBODY, "CHECKBODY","ROW",1);

        file_put_contents("./xml/zFormCopy.xml",$xmlDoc->asXML());
        return file_get_contents("./xml/zFormCopy.xml");
    }

    public static function makeCheckXML($params){

        $file = realpath("./xml/check.xml");
        $xmlDoc = new \SimpleXMLElement(file_get_contents($file));

        //$xmlDoc = self::filler($xmlDoc, $params);
        $xmlDoc = self::fillCheckHead($xmlDoc, $params->CHECKHEAD);

        $checkTotal  = self::fillCategory($xmlDoc, $params->CHECKTOTAL, "CHECKTOTAL");
        $checkPay = self::fillCategory($xmlDoc, $params->CHECKPAY, "CHECKPAY","ROW", 1);
        $checkTax = self::fillCategory($xmlDoc, $params->CHECKTAX, "CHECKTAX","ROW", 1);
        $checkExcise = self::fillCategory($xmlDoc, $params->CHECKEXCISE, "CHECKEXCISE","ROW",1);
        $checkBody = self::fillCategory($xmlDoc, $params->CHECKBODY, "CHECKBODY","ROW",1);


//        var_dump($xmlDoc);
//        die();
        file_put_contents("./xml/checkCopy.xml",$xmlDoc->asXML());
        return file_get_contents("./xml/checkCopy.xml");
    }
}