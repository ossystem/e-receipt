<?php

require "./lib/libApi.php";

use Api\libApi;

if(!$_POST['Command'])
    echo "api";

$envErrors=[];

if(!(array_key_exists('FISCAL_SERVER', $_ENV) || array_key_exists('FISCAL_SERVER', $_SERVER)))
    $envErrors[] = 'FISCAL_SERVER';

if(!(array_key_exists('CRYPT_SERVER', $_ENV) || array_key_exists('CRYPT_SERVER', $_SERVER)))
    $envErrors[] = 'CRYPT_SERVER';

if(!(array_key_exists('CRYPT_SERVER_PORT', $_ENV) || array_key_exists('CRYPT_SERVER_PORT', $_SERVER)))
    $envErrors[] = 'CRYPT_SERVER_PORT';

if(!(array_key_exists('CONNECTION_TIMEOUT', $_ENV) || array_key_exists('CONNECTION_TIMEOUT', $_SERVER)))
    $envErrors[] = 'CONNECTION_TIMEOUT';

if($envErrors && count($envErrors) > 0) {
    $response = json_encode([
        "error" => (count($envErrors) > 1 ?
            "Змінні оточення ".implode(", ", $envErrors)." не існують або пусті.":
            "Змінна оточення ".$envErrors[0]." не існує або пуста."
        )]);
    echo $response;
    die();
}


switch($_POST['Command']) {
    // return json
    case 'Objects' :
    {
        echo libApi::objects();
    };
    break;
    // return json
    case 'CashRegisterState':
    {
        echo libApi::CashRegisterState($_POST['NumFiscal']);
    }
    break;
    // return json
    case 'Documents':
    {

        echo libApi::documents($_POST['ShiftId']);
    }
        break;
    // return json
    case 'Check':
    {
        $params = json_decode($_POST['params']);

        echo libApi::check($params);

    }
        break;
    // return json
    case 'Shifts':
    {

        echo libApi::shifts($_POST['NumFiscal'], $_POST['From'], $_POST['To']);
    }
        break;
    // return json
    case 'CheckShow':{

        echo libApi::checkShow($_POST['NumFiscal']);
    }
        break;
    // return json
    case 'zFormShow':{

        echo libApi::zFormShow($_POST['NumFiscal']);
    }
        break;
    // return json
    case 'ShiftOpen':
    {
        $params = json_decode($_POST['params']);

        echo libApi::shiftOpen($params);
    }
    break;
    // return json
    case 'zForm':
    {
        $params = json_decode($_POST['params']);

        echo libApi::zForm($params);
    }
        break;
    // return json
    case 'ShiftClose':
    {
        $params = json_decode($_POST['params']);

        echo libApi::shiftClose($params);
    }
        break;
}