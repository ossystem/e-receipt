<?php

require "./lib/libApi.php";

use Api\libApi;

if(!$_POST['Command'])
    echo "api";

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