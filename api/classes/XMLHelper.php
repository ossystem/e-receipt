<?php

namespace Helpers;

/**XMLHelper Класс для работы с xml */

class XMLHelper{

    /* Путь к xml шаблонам */
    public static $xmlPath = './xml';

    /**fillCategory Заполняет указанную категорию в шаблонах
     * @param $xmlDoc
     * @param $params
     * @param $field
     * @param $createElement = false
     * @param $index = 0
     * @return string xml
     */
    private static function fillCategory($xmlDoc, $params, $field, $createElement = false, $index=0){
        $category = $xmlDoc->{$field};

        if($createElement) {
            foreach($params as $k=>$v){
                $cat = $category->addChild($createElement);
                $cat->addAttribute("ROWNUM", $k+1);
                foreach ($v as $k1=>$v1) {
                    $cat->addChild($k1, $v1);
                }
            }
        }else{
            foreach($params as $k=>$v){
                $category->{$k} = $v;
            }
        }

        return $category;
    }

    /**fillCheckHead Заполняет секцию CHECKHEAD в шаблонах
     * @param $xmlDoc
     * @param $params
     * @param $headField = 'CHECKHEAD'
     * @return string xml
     */
    private static function fillCheckHead($xmlDoc, $params, $headField = 'CHECKHEAD'){

        $checkHead = self::fillCategory($xmlDoc, $params, $headField);

        $datetime = new \DateTime('now');

        $checkHead->ORDERDATE = $datetime->format("dmY");
        $checkHead->ORDERTIME = $datetime->format("His");

        return $xmlDoc;
    }

    /**makeShiftXML Генерирует xml для открытия смены и закрытия смены на основании шаблона shift.xml
     * @param $params
     * @return xml
     */
    public static function makeShiftXML($params){

        $file = realpath(self::$xmlPath."/shift.xml");
        try {

            $xmlDoc = new \SimpleXMLElement(file_get_contents($file));
            $xmlDoc = self::fillCheckHead($xmlDoc, $params->CHECKHEAD);
            return $xmlDoc->asXML();

        }catch(\Exception $e){
            return $e->getTrace();
        }
    }

    /**makeZFormXML Генерирует xml для z-отчёта на основании шаблона zform.xml
     * @param $params
     * @return xml
     */
    public static function makeZFormXML($params){

        $file = realpath(self::$xmlPath."/zform.xml");

        try {
            $xmlDoc = new \SimpleXMLElement(file_get_contents($file));
            $xmlDoc = self::fillCheckHead($xmlDoc, $params->ZFORMHEAD, 'ZFORMHEAD');

            if ($params->ZFORMSUMREAL > 0) {

                $xmlDoc->ZFORMPAY->SUMREAL = $params->ZFORMPAY->SUMREAL;
                $xmlDoc->ZFORMPAY->ZFORMREALIZ = "";
                self::fillCategory($xmlDoc->ZFORMPAY, $params->ZFORMPAY->ZFORMREALIZ, "ZFORMREALIZ", "ROW", 1);

                $xmlDoc->ZFORMSUMREAL->ZFORMTAX = "";
                self::fillCategory($xmlDoc->ZFORMSUMREAL, $params->ZFORMSUMREAL->ZFORMTAX, "ZFORMTAX", "ROW", 1);

                $xmlDoc->ZFORMSUMREAL->ZFORMTURNOVER = "";
                self::fillCategory($xmlDoc->ZFORMSUMREAL, $params->ZFORMSUMREAL->ZFORMTURNOVER, "ZFORMTURNOVER", "ROW", 1);

                $xmlDoc->ZFORMSUMREAL->TOTALSUMREAL = $params->ZFORMSUMREAL->TOTALSUMREAL;
                $xmlDoc->ZFORMSUMREAL->TAXSUMREAL = $params->ZFORMSUMREAL->TAXSUMREAL;
                $xmlDoc->ZFORMSUMREAL->FEESUMREAL = $params->ZFORMSUMREAL->FEESUMREAL;
            }

            if ($params->ZFORMPAY->SUMRET > 0) {
                $xmlDoc->ZFORMPAY->SUMRET = $params->ZFORMPAY->SUMRET;
                $xmlDoc->ZFORMPAY->ZFORMRETURN = "";
                self::fillCategory($xmlDoc->ZFORMPAY, $params->ZFORMPAY->ZFORMRETURN, "ZFORMRETURN", "ROW", 1);
                $xmlDoc->ZFORMSUMRETUNRN->ZFORMTAXRET = "";
                self::fillCategory($xmlDoc->ZFORMSUMRETUNRN, $params->ZFORMSUMRETUNRN->ZFORMTAXRET, "ZFORMTAXRET", "ROW", 1);
                $xmlDoc->ZFORMSUMRETUNRN->ZFORMTURNOVERRET = "";
                self::fillCategory($xmlDoc->ZFORMSUMRETUNRN, $params->ZFORMSUMRETUNRN->ZFORMTURNOVERRET, "ZFORMTURNOVERRET", "ROW", 1);
                $xmlDoc->ZFORMSUMRETUNRN->TOTALSUMRET = $params->ZFORMSUMRETUNRN->TOTALSUMRET;
                $xmlDoc->ZFORMSUMRETUNRN->TAXSUMRET = $params->ZFORMSUMRETUNRN->TAXSUMRET;
                $xmlDoc->ZFORMSUMRETUNRN->FEESUMRET = $params->ZFORMSUMRETUNRN->FEESUMRET;
            }

            self::fillCategory($xmlDoc, $params->ZFORMBODY, "ZFORMBODY");

            return $xmlDoc->asXML();

        }catch(\Exception $e){

            return $e->getTrace();
        }
    }


    /**makeCheckXML Генерирует xml для чека на основании шаблона check.xml
     * @param $params
     * @return xml
     */
    public static function makeCheckXML($params){

        $file = realpath(self::$xmlPath."/check.xml");

        try {
            $xmlDoc = new \SimpleXMLElement(file_get_contents($file));

            $xmlDoc = self::fillCheckHead($xmlDoc, $params->CHECKHEAD);

                $checkTotal = self::fillCategory($xmlDoc, $params->CHECKTOTAL, "CHECKTOTAL");
            if ($params->CHECKPAY)
                $checkPay = self::fillCategory($xmlDoc, $params->CHECKPAY, "CHECKPAY", "ROW", 1);
            if ($params->CHECKTAX)
                $checkTax = self::fillCategory($xmlDoc, $params->CHECKTAX, "CHECKTAX", "ROW", 1);
            if ($params->CHECKEXCISE)
                $checkExcise = self::fillCategory($xmlDoc, $params->CHECKEXCISE, "CHECKEXCISE", "ROW", 1);
            if ($params->CHECKBODY)
                $checkBody = self::fillCategory($xmlDoc, $params->CHECKBODY, "CHECKBODY", "ROW", 1);

            return $xmlDoc->asXML();

        }catch(\Exception $e){
            return $e->getTrace();
        }

    }

    /**xmlToJson Конвертирует xml в json
     * @param $xmlString
     * @return json
     */
    public static function xmlToJson($xmlString){

        $return = [];

        if(mb_detect_encoding($xmlString, "UTF-8", true))
            $xmlString = mb_convert_encoding($xmlString, "windows-1251", "utf-8");

        try {
            $return['xml'] = new \SimpleXMLElement($xmlString);
            if($return['xml']->ERRORCODE > 0)
                $return['error'] = $return['xml']->ERRORTEXT;
            else
                $return = json_decode(json_encode($return['xml'], JSON_UNESCAPED_UNICODE), TRUE);
        }catch(Exception $e){
            $errMessage = $e->getMessage();

            if($errMessage == 'String could not be parsed as XML') {
                if($xmlString)
                    $return['error'] = $xmlString;
                else
                    $return['error'] = $errMessage;
            }
        }

        return json_encode($return,JSON_UNESCAPED_UNICODE);
    }

}