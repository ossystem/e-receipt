<?php
namespace Api;

require_once "./classes/CurlHelper.php";
require_once "./classes/XMLHelper.php";

use Helpers\CurlHelper;
use Helpers\XMLHelper;

class libApi{

    /**@Objects Запрос доступных объектов (точек продажи)
     * @return string json
     */
    public static function objects(){
        $return = CurlHelper::sign(json_encode(["Command" => "Objects"]));
        if(!isset($return['error']))
            $return = CurlHelper::send($return, "objects");
        return $return;
    }

    /**cashRegisterState Запрос состояния кассы (PPO)
     * @param $numFiscal
     * @return string json
     */
    public static function cashRegisterState($numFiscal){
        $return = CurlHelper::sign(json_encode(["Command" => "CashRegisterState", "NumFiscal" => $numFiscal]));

        if(!isset($return['error']))
            $return = CurlHelper::send($return, "CashRegisterState");

        return $return;
    }

    /**documents Запрос перечня документов по смене
     * @param $shiftId
     * @return string json
     */
    public static function documents($shiftId){

        $return = CurlHelper::sign(json_encode([
            "Command" => "Documents",
            "ShiftId" => $shiftId
        ]));

        if(!isset($return['error']))
            $return = CurlHelper::send($return, "Documents");

        return $return;
    }

    /**check Создание чека
     * @param $params
     * @return string json
     */
    public static function check($params){

        $xml = XMLHelper::makeCheckXML($params);

        $return = CurlHelper::sign($xml);

        if(!isset($return['error']))
            $return = CurlHelper::send($return, "check.xml", "doc");

        if(!isset($return['error']))
            $return = CurlHelper::decrypt($return);

        if(!isset($return['error']))
            $return = XMLHelper::xmlToJson($return);

        return $return;
    }

    /**shifts Получить список смен за период
     * @param $numFiscal
     * @param fromDate
     * @param $toDate
     * @return string json
     */
    public static function shifts($numFiscal, $fromDate, $toDate){

        $return = CurlHelper::sign(json_encode([
            "Command" => "Shifts",
            "NumFiscal" => $numFiscal,
            "From" => $fromDate,
            "To" => $toDate
        ]));

        if(!isset($return['error']))
            $return = CurlHelper::send($return, "Shifts");

        if(!isset($return['error']))
            $return = CurlHelper::decrypt($return);

        return $return;

    }

    /**checkShow получить существующий чек
     * @param $numFiscal
     * @return string json
     */
    public static function checkShow($numFiscal){

        $return = CurlHelper::sign(json_encode([
            "Command" => "Check",
            "NumFiscal" => $numFiscal,
        ]));

        if(!isset($return['error']))
            $return = CurlHelper::send($return, "Check");

        if(!isset($return['error']))
            $return = CurlHelper::decrypt($return);

        if(!isset($return['error']))
            $return = XMLHelper::xmlToJson($return);

        return  $return;
    }

    /**zFormShow получить существующий z-отчёт
     * @param $numFiscal
     * @return string json
     */
    public static function zFormShow($numFiscal){

        $return = CurlHelper::sign(json_encode([
            "Command" => "zForm",
            "NumFiscal" => $numFiscal,
        ]));

        if(!isset($return['error']))
            $return = CurlHelper::send($return, "zForm");

        if(!isset($return['error']))
            $return = CurlHelper::decrypt($return);

        if(!isset($return['error']))
            $return = XMLHelper::xmlToJson($return);

        return $return;
    }

    /**shiftOpen Открытие смены
     * @param $params
     * @return string json
     */
    public static function shiftOpen($params){

        $xml = XMLHelper::makeShiftXML($params);

        $return = CurlHelper::sign($xml);

        if(!isset($return['error']))
            $return = CurlHelper::send($return, "shift.xml",  "doc");

        if(!isset($return['error']))
            $return = CurlHelper::decrypt($return);

        if(!isset($return['error']))
            $return = XMLHelper::xmlToJson($return);


        return $return;

    }

    /**zForm Создание z-отчёта
     * @param $params
     * @return string json
     */
    public static function zForm($params){

        $xml = XMLHelper::makeZFormXML($params);
        $return = CurlHelper::sign($xml);

        if(!isset($return['error']))
            $return = CurlHelper::send($return, "zform.xml", "doc");

        if(!isset($return['error']))
            $return = CurlHelper::decrypt($return);

        if(!isset($return['error']))
            $return = XMLHelper::xmlToJson($return);

        return $return;

    }

    /**shiftClose Закрытие смены
     * @param $params
     * @return string json
     */
    public static function shiftClose($params){

        $xml = XMLHelper::makeShiftXML($params);
        $return = CurlHelper::sign($xml);
        if(!isset($return['error']))
            $return = CurlHelper::send($return, "shift.xml", "doc");

        if(!isset($return['error']))
            $return = CurlHelper::decrypt($return);

        if(!isset($return['error']))
            $return = XMLHelper::xmlToJson($return);

        return $return;
    }

}
