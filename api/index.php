<?php

header('Content-Type: application/json; charset=windows-1251');

require "./Helpers/XMLHelper.php";
require "./Helpers/CurlHelper.php";

use Helpers\XMLHelper;
use Helpers\CurlHelper;

if(!$_POST['Command'])
    echo "api";

switch($_POST['Command']) {
    case 'Objects' :
        {
            $return = CurlHelper::sign(json_encode(["Command" => "Objects"]));
            if(!$return['error'])
                $return = CurlHelper::getCurlResponse($return);
            echo $return;
        };
        break;
    case 'CashRegisterState':
        {
            $return = CurlHelper::sign(json_encode(["Command" => "CashRegisterState", "NumFiscal" => $_POST['NumFiscal']]))/*file_get_contents("./bin/cashRegister".$_POST['NumFiscal'].".json.p7s")*/;

            if(!$return['error'])
                $return = CurlHelper::getCurlResponse($return);

            echo $return;
        }
        break;
    case 'Documents':
    {
        $return = CurlHelper::sign(json_encode([
            "Command" => "Documents",
            "ShiftId" => $_POST['ShiftId']
        ]));

        if(!$return['error'])
            $return = CurlHelper::getCurlResponse($return);

        echo $return;
    }
        break;
    case 'Check':
    {
        $params = json_decode(iconv("UTF-8", "CP1251", $_POST['params'])/*, TRUE*/);

        $xml = XMLHelper::makeCheckXML($params);
//        $return = $xml;
        $return = CurlHelper::sign($xml);
        if(!$return['error'])
            $return = CurlHelper::getCurlResponse($return, "doc");

        if(!$return['error'])
            $return = CurlHelper::decrypt($return);

        echo $return;

    }
        break;
    case 'Shifts':
    {
        $return = CurlHelper::sign(json_encode([
            "Command" => "Shifts",
            "NumFiscal" => $_POST['NumFiscal'],
            "From" => $_POST['From'],
            "To" => $_POST['To']
        ]));

        if(!$return['error'])
            $return = CurlHelper::getCurlResponse($return);
        if(!$return['error'])
            $return = CurlHelper::decrypt($return);

        echo $return;
    }
        break;
    case 'CheckShow':{

        $return = CurlHelper::sign(json_encode([
            "Command" => "Check",
            "NumFiscal" => $_POST['NumFiscal'],
        ]));

        if(!$return['error'])
            $return = CurlHelper::getCurlResponse($return);

        file_put_contents("./bin/checkRaw.xml", $return);

        if(!$return['error'])
            $return = CurlHelper::decrypt($return);

        file_put_contents("./bin/checkDecr.xml", $return);

//        file_put_contents("./bin/pf1.xml",$response);
        echo $return;
    }
        break;
    case 'zFormShow':{

        $return = CurlHelper::sign(json_encode([
            "Command" => "zForm",
            "NumFiscal" => $_POST['NumFiscal'],
        ]));

        if(!$return['error'])
            $return = CurlHelper::getCurlResponse($return);
        if(!$return['error'])
            $return = CurlHelper::decrypt($return);

//        file_put_contents("./bin/pf1.xml",$response);
        echo $return;
    }
        break;
    case 'ShiftOpen':
    {
        $params = json_decode($_POST['params']);
//
        $xml = XMLHelper::makeShiftXML($params);

        $return = CurlHelper::sign($xml);

        if(!$return['error'])
            $return = CurlHelper::getCurlResponse($return, "doc");

        echo $return;
    }
    break;

    case 'zForm':
    {
        $params = json_decode($_POST['params']);

        $xml = XMLHelper::makeZFormXML($params);
        $return = CurlHelper::sign($xml);
//        file_put_contents("./bin/shiftOpen.xml.p7s", $signedData);
        if(!$return['error'])
            $return = CurlHelper::getCurlResponse($return, "doc");
        if(!$return['error'])
            $return = CurlHelper::decrypt($return);

        echo $return;
    }
        break;

    case 'ShiftClose':
    {
        $params = json_decode($_POST['params']);

        $xml = XMLHelper::makeShiftXML($params);
        $return = CurlHelper::sign($xml);
        if(!$return['error'])
            $return = CurlHelper::getCurlResponse($return, "doc");
        if(!$return['error'])
            $return = CurlHelper::decrypt($return);

        echo $return;
    }
        break;
}