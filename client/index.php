<?php

//$_SERVER['SERVER_NAME'] = "seleznyov9200.ossystem.ua";
// Kickstart the framework
$f3=require('lib/base.php');

use models\ItemModel;
use models\ItemListModel;
use models\OrderItemsModel;
use models\OrdersModel;
use Helpers\FormatHelper;
use Helpers\CurlHelper;
use Helpers\CacheHelper;

$f3->set('CACHE',FALSE);
$cache = \Cache::instance();
$cache->reset();
$f3->set('DEBUG',1);

if ((float)PCRE_VERSION < 7.9)
    trigger_error('PCRE version is out of date');


// Load configuration
$f3->config('config.ini');
libxml_use_internal_errors(true);

$envErrors=[];

if(!(array_key_exists('API_SERVER', $_ENV) || array_key_exists('API_SERVER', $_SERVER)))
    $envErrors[] = "API_SERVER";

if(!(array_key_exists('MYSQL_HOST', $_ENV) || array_key_exists('MYSQL_HOST', $_SERVER)))
    $envErrors[] = "MYSQL_HOST";

if(!(array_key_exists('MYSQL_PORT', $_ENV) || array_key_exists('MYSQL_PORT', $_SERVER)))
    $envErrors[] = "MYSQL_PORT";

if(!(array_key_exists('MYSQL_DB', $_ENV) || array_key_exists('MYSQL_DB', $_SERVER)))
    $envErrors[] = "MYSQL_DB";

if(!(array_key_exists('MYSQL_USER', $_ENV) || array_key_exists('MYSQL_USER', $_SERVER)))
    $envErrors[] = "MYSQL_USER";

if(!(array_key_exists('MYSQL_PASS', $_ENV) || array_key_exists('MYSQL_PASS', $_SERVER)))
    $envErrors[] = "MYSQL_PASS";

if($envErrors && count($envErrors) > 0) {

        $f3->set('errors', array_map(
            function($el){
                return "Змінна оточення $el не існує або пуста.";
            },$envErrors));

        echo View::instance()->render('layout.htm');
        die();
}
//header('Content-Type: text/html; charset=windows-1251');

$f3->route('GET /',
    function($f3) {
        $obj = CacheHelper::responseCache(["Command" => "Objects"], "./json/responses/objects.json");

        if(!$obj)
            $f3->set('errors', ["Неможливо отримати точки продажу"]);

        if($obj->error)
            $f3->set('errors', [$obj->error]);

        $f3->set('isMain', true);
        $f3->set('title', "Точки продажу");
        $f3->set('taxObjects', $obj->TaxObjects);
        $f3->set('content', 'main.htm');

        echo View::instance()->render('layout.htm');
    }
);

$f3->route('GET /@guid/cashRegisters',
    function($f3) {
        $guid = $f3->PARAMS['guid'];

        $obj = CacheHelper::getObjectByGUID($guid);

        if($obj->error)
            $f3->set('errors', [$obj->error]);
        else {
            $f3->set('cashRegisters', $obj->CashRegisters);
            $f3->set('content', 'cashRegisters.htm');
        }

        $f3->set('title', "Каси");
        $f3->set('guid', $guid);
        $f3->set("backHref", "/");
        echo View::instance()->render('layout.htm');
    }
);

$f3->route('GET /items',
    function($f3) {
        $items = ItemModel::get();

        $f3->set('title', "Товари");
        $f3->set('items', $items);
        $f3->set("backHref", "/");
        $f3->set('content','items.htm');
        echo View::instance()->render('layout.htm');
    }
);

$f3->route('GET|POST /items/add',
    function($f3) {

        if($f3->POST['params']){
            ItemModel::addItem($f3->POST['params']);
            $f3->reroute('/items');
        } else {
            $f3->set('title', "Додати товар");
            $f3->set('action', 'add');
            $f3->set("backHref", "/items");
            $f3->set('content', 'itemForm.htm');
            echo View::instance()->render('layout.htm');
        }
    }
);

$f3->route('GET|POST /items/@id/update',
    function($f3) {
        $id = $f3->PARAMS['id'];

        $item = ItemModel::getById($id);

        if($f3->POST['params']){
            ItemModel::updateItem($id, $f3->POST['params']);
            $f3->reroute("/items");
        } else {
            $f3->set('title', "Змінити товар");
            $f3->set('action', 'update');
            $f3->set('item', $item);
            $f3->set("backHref", "/items");
            $f3->set('content', 'itemForm.htm');
            echo View::instance()->render('layout.htm');
        }
    }
);

$f3->route('GET /items/@id/remove',
    function($f3) {
        $id = $f3->PARAMS['id'];

        ItemModel::remove($id);
        $f3->reroute("/items");
    }
);

$f3->route('GET /@guid/cash/@id',
    function($f3) {
        $id = $f3->PARAMS['id'];
        $guid = $f3->PARAMS['guid'];

        $cash = CacheHelper::responseCache([
            "Command" => "CashRegisterState",
            "NumFiscal" => $id
        ], "./json/responses/cash$id.json");

        if($cash->error)
            $f3->set('errors', [$cash->error]);
        else{
            $f3->set('cash', $cash);
            $f3->set('content', 'cashMenu.htm');
        }

        $f3->set('title', "Каса: ".($cash->State > 0 ? 'Зміна відкрита' : 'Зміна закрита'));
        $f3->set('id', $id);
        $f3->set('guid', $guid);
        $f3->set("backHref", "/$guid/cashRegisters");
        echo View::instance()->render('layout.htm');

    }
);

$f3->route('GET /@guid/cash/@id/shift/open',
    function($f3) {
        $id = $f3->PARAMS['id'];
        $guid = $f3->PARAMS['guid'];

        list($obj, $cash, $cashRegister) = CacheHelper::getAllParams($guid, $id);

        $params = [
            'DOCTYPE' => 1,
            'DOCSUBTYPE' => 0,
            'VER' => 1,
            'UID' => $guid,
            'TIN' => $obj->Tin,
            'INN' => "123456789012"/*$cashRegister->LastFiscalNum*/,
//            'ORDERTAXNUM' => "101234567890123"/*$cashRegister->LastFiscalNum*/,
            //'INN' => $cashRegister->LastFiscalNum,
            'ORGNAME' => $obj->Name,
            'POINTNAME' => $obj->Name,
            'POINTADDR' => $obj->Address,
            'ORDERNUM' => $cashRegister->NextLocalNum,
            'CASHDESKNUM' => $cash->NumLocal,
            'CASHREGISTERNUM' => $cash->NumFiscal,
            'CASHIER' => 'Семко А.М.'
        ];


        $response = CurlHelper::send([
            "Command" => "ShiftOpen",
            "params" => json_encode(["CHECKHEAD" => $params])
        ]);

        $errors = [];

        $response = json_decode($response, TRUE);

        if(!$response['error']) {
            OrdersModel::clear();
            $f3->reroute("/$guid/cash/$id");

        }elseif($response['error'])
            $errors[] = $response['error'];

        if($errors && count($errors) > 0){
            $f3->set('errors', $errors);
            echo View::instance()->render('layout.htm');
        }
    }
);

$f3->route('GET /@guid/cash/@id/shift/close',
    function($f3) {
        $id = $f3->PARAMS['id'];
        $guid = $f3->PARAMS['guid'];

        list($obj, $cash, $cashRegister) = CacheHelper::getAllParams($guid, $id);

        $params = [
            'VER' => 1,
            'UID' => $guid,
            'TIN' => $obj->Tin,
            'INN' => "123456789012",
            //'INN' => $cashRegister->LastFiscalNum,
            'ORGNAME' => $obj->Name,
            'POINTNAME' => $obj->Name,
            'POINTADDR' => $obj->Address,
            'ORDERNUM' => $cashRegister->NextLocalNum,
            'CASHDESKNUM' => $cash->NumLocal,
            'CASHREGISTERNUM' => $cash->NumFiscal,
            'CASHIER' => 'Семко А.М.'
        ];

        $orderItems = OrdersModel::getOrdersWithItems();
        $orders = OrdersModel::get();

        $ordersParams = [];

        $ordersParams['count'] = 0;

        $ordersParams['lastOrder'] = $orderItems ? $orderItems[0]->fixal_num : 0;

        foreach ($orders as $k => $v) {

            if ($v->order_type == 1)
                $ordersParams['orderExpCount']++;
            else
                $ordersParams['orderCount']++;

            $ordersParams[$v->order_type]['sumReal'] += $v->sum_real;
            $ordersParams[$v->order_type]['sumCard'] += $v->sum_card;
            $ordersParams[$v->order_type]['sum'] += $v->sum;

        }

        foreach ($orderItems as $k => $v) {
            $postfix = "";
            switch ($v->order_type) {
                case 0:
                    $postfix = "";
                    break;
                case 1:
                    $postfix = "RET";
                    break;
            }

            $ordersParams[$v->order_type]['tax']['turnover'] = [];
            if (!$ordersParams[$v->order_type]['tax']['tax'])
                $ordersParams[$v->order_type]['tax']['tax'] = [];

            if(!$ordersParams[$v->order_type]['tax']['tax']['exc'])
                $ordersParams[$v->order_type]['tax']['tax']['exc'] =[];

            $exc = $ordersParams[$v->order_type]['tax']['tax']['exc']['exc_'.$v->action_litera. $v->action_stavka];

            $exc["TAXNAME$postfix"] = "Збір " . $v->action_litera . "=" . $v->action_stavka . "%";
            $exc["TAXTOTAL$postfix"] = FormatHelper::format_dec(floatval($exc["TAXTOTAL$postfix"]) + $v->excise_sum);

            $ordersParams[$v->order_type]['tax']['tax']['exc']['exc_'.$v->action_litera. $v->action_stavka] = $exc;

            if(!$ordersParams[$v->order_type]['tax']['tax']['tax'])
                $ordersParams[$v->order_type]['tax']['tax']['tax'] =[];

            $tax = $ordersParams[$v->order_type]['tax']['tax']['tax']['tax_'.$v->pdv_litera. $v->pdv_stavka];

            $tax["TAXNAME$postfix"] = "ПДВ " . $v->pdv_litera . "=" . $v->pdv_stavka . "%";
            $tax["TAXTOTAL$postfix"] = FormatHelper::format_dec(floatval($tax["TAXTOTAL$postfix"]) + $v->pdv_sum);

            $ordersParams[$v->order_type]['tax']['tax']['tax']['tax_'.$v->pdv_litera. $v->pdv_stavka] = $tax;
            $ordersParams[$v->order_type]['tax']['total'] += $v->pdv_sum;
            $ordersParams[$v->order_type]['fee']['total'] += $v->excise_sum;

            $ordersParams[$v->order_type]['turnover'][$v->pdv_litera] += $v->cost;

            if($v->pdv_litera != $v->action_litera)
                $ordersParams[$v->order_type]['turnover'][$v->action_litera] += $v->cost;
        }

        $getTurnover = function ($ordersParams, $orderType) {

            $return = [];
            $postfix = "";

            switch ($orderType) {
                case 0:
                    $postfix = "";
                    break;
                case 1:
                    $postfix = "RET";
                    break;
            }

            if ($ordersParams[$orderType])
                foreach ($ordersParams[$orderType]['turnover'] as $k => $v) {
                    $return[] = [
                        "TURNOVERNAME$postfix" => "Обіг $k",
                        "TURNOVERTOTAL$postfix" => FormatHelper::format_dec($v)
                    ];
                }

            return $return;
        };

        $zparams = [
            "ZFORMHEAD" => array_merge($params, ['ORDERTAXNUM' => "101234567890123"/*$cashRegister->LastFiscalNum*/]),
            "ZFORMPAY" => [],
            "ZFORMBODY" => [
                "SERVICEINPUT" => FormatHelper::format_dec($ordersParams[2]['sum']),
                "SERVICEOUTPUT" => FormatHelper::format_dec($ordersParams[3]['sum']),
                "ORDERCOUNT" => intval($ordersParams['orderCount']),
                "ORDEREXPCOUNT" => intval($ordersParams['orderExpCount']),
                "ORDERLAST" => $ordersParams['lastOrder'],
                "REPORTZERO" => "0"
            ]
        ];

        if ($ordersParams[1]) {

            $taxRet = array_merge($ordersParams[1]['tax']['tax']['exc'], $ordersParams[1]['tax']['tax']['tax']);

            $zparams["ZFORMSUMRETUNRN"] = [
                "ZFORMTURNOVERRET" => $getTurnover($ordersParams, 1),
                "ZFORMTAXRET" => $taxRet,
                "TOTALSUMRET" => FormatHelper::format_dec($ordersParams[1]['sum']),
                "TAXSUMRET" => FormatHelper::format_dec($ordersParams[1]['tax']['total']),
                "FEESUMRET" => FormatHelper::format_dec($ordersParams[1]['fee']['total'])
            ];

            $zparams["ZFORMPAY"] = array_merge($zparams["ZFORMPAY"],
                ["SUMRET" => FormatHelper::format_dec($ordersParams[1]['sum']),
                    "ZFORMRETURN" => [
                        [
                            "FORMPAYRET" => "Готівка",
                            "SUMPAYRET" => FormatHelper::format_dec($ordersParams[1]['sumReal'])
                        ],
                        [
                            "FORMPAYRET" => "Картка",
                            "SUMPAYRET" => FormatHelper::format_dec($ordersParams[1]['sumCard'])
                        ]
                    ]
                ]
            );
        }

        if ($ordersParams[0]) {

            $tax = array_merge($ordersParams[0]['tax']['tax']['exc'], $ordersParams[0]['tax']['tax']['tax']);

            $zparams["ZFORMSUMREAL"] = [
                "ZFORMTURNOVER" => $getTurnover($ordersParams, 0),
                "ZFORMTAX" => $tax,
                "TOTALSUMREAL" => FormatHelper::format_dec($ordersParams[0]['sum']),
                "TAXSUMREAL" => FormatHelper::format_dec($ordersParams[0]['tax']['total']),
                "FEESUMREAL" => FormatHelper::format_dec($ordersParams[0]['fee']['total']),
            ];

            $zparams["ZFORMPAY"] = array_merge($zparams["ZFORMPAY"],
                [
                    "SUMREAL" => FormatHelper::format_dec($ordersParams[0]['sum']),
                    "ZFORMREALIZ" => [
                        [
                            "FORMPAYREAL" => "Готівка",
                            "SUMPAYREAL" => FormatHelper::format_dec($ordersParams[0]['sumReal'])
                        ],
                        [
                            "FORMPAYREAL" => "Картка",
                            "SUMPAYREAL" => FormatHelper::format_dec($ordersParams[0]['sumCard'])
                        ]
                    ]
                ]);

        }

        $errors = [];

        $responseZClose = CurlHelper::send([
            "Command" => "zForm",
            "params" => json_encode($zparams)
        ]);

        $responseZClose = json_decode($responseZClose, TRUE);

        if($responseZClose['error'])
            $errors[] = $responseZClose['error'];

        if (!$responseZClose['error']) {

            OrdersModel::clear();

            $responseCashRegister = CurlHelper::send([
                "Command" => "CashRegisterState",
                "NumFiscal" => $id
            ]);

            $cashRegister = json_decode($responseCashRegister);

            if($cashRegister->error)
                $errors[] = $cashRegister->error;

            $params = array_merge($params,
                [
                    'DOCTYPE' => 2,
                    'DOCSUBTYPE' => 0,
                    'ORDERNUM' => $cashRegister->NextLocalNum
                ]
            );

            $responseShiftClose = CurlHelper::send([
                "Command" => "ShiftClose",
                "params" => json_encode(["CHECKHEAD" => $params])
            ]);

            $responseShiftClose = json_decode($responseShiftClose, TRUE);

            if($responseShiftClose['error'])
                $errors[] = $responseShiftClose['error'];

            if(!$responseZClose['error'])
                $f3->reroute("/$guid/cash/$id/shifts/zform/{$responseZClose["ORDERTAXNUM"]}");

        }

        if($errors){
            $f3->set('errors', $errors);
            echo View::instance()->render('layout.htm');
        }

    }
);

$f3->route('GET /@guid/cash/@id/documents',
    function($f3) {
        $id = $f3->PARAMS['id'];
        $guid = $f3->PARAMS['guid'];

        $response = json_decode(CurlHelper::send([
            "Command" => "CashRegisterState",
            "NumFiscal" => $id
        ]));

        $documents = CurlHelper::send([
            "Command" => "Documents",
            "ShiftId" => $response->ShiftId,
        ]);

    }
);

$f3->route('GET /@guid/cash/@id/shifts',
    function($f3) {
        $id = $f3->PARAMS['id'];
        $guid = $f3->PARAMS['guid'];

        $datetime = new DateTime();
        $dateFrom = new DateTime('-6 hours');

        $errors = [];

        $shifts = CurlHelper::send([
            "Command" => "Shifts",
            "NumFiscal" => $id,
            "From" => $dateFrom->format("Y-m-d H:i:s"),
            "To" => $datetime->format("Y-m-d H:i:s")
        ]);

        $shifts = json_decode($shifts);

        if($shifts->error)
            $errors[] = $shifts->error;
        else {
            foreach ($shifts->Shifts as $k => $v) {

                $documents = CurlHelper::send([
                    "Command" => "Documents",
                    "ShiftId" => $v->ShiftId
                ]);

                $docs = json_decode($documents);

                if($docs->error)
                    $errors[] = $docs->error;

                $v->documents = array_reverse($docs->Documents);

                foreach ($v->documents as $k1 => $v1) {

                    $check = CurlHelper::send([
                        "Command" => "CheckShow",
                        "NumFiscal" => $v1->NumFiscal
                    ]);

                    if ($check) {
                        $check = json_decode($check);
                        $v1->CheckDocSubType = $check->CHECKHEAD->DOCSUBTYPE;

                        if(count($shifts->Shifts) <=0 )
                            $errors[] = "За последние 6 часов не было никакой активности.";
                    }

                }
            }

            $f3->set("shifts", array_reverse($shifts->Shifts));
            $f3->set('content', 'shifts.htm');
        }

        $f3->set('id', $id);
        $f3->set('guid', $guid);
        $f3->set("backHref", "/$guid/cashRegisters");
        $f3->set('errors', $errors);
        echo View::instance()->render('layout.htm');

    }
);

$f3->route('GET /@guid/cash/@cashid/shifts/check/@id',
    function($f3) {
        $cashid = $f3->PARAMS['cashid'];
        $guid = $f3->PARAMS['guid'];
        $id = $f3->PARAMS['id'];

        $check = CurlHelper::send([
            "Command" => "CheckShow",
            "NumFiscal" => $id,
        ]);

        $check = json_decode($check ,TRUE);

        $errors = [];

        if(!$check['error']) {

            $datestr = preg_replace("/(\d{2})(\d{2})(\d{4})/", "$1.$2.$3", $check["CHECKHEAD"]["ORDERDATE"]);
            $datestr .= " " . preg_replace("/(\d{2})(\d{2})(\d{2})/", "$1:$2:$3", $check["CHECKHEAD"]["ORDERTIME"]);

            $f3->set('date', $datestr);
            $f3->set('id', $id);
            $f3->set('guid', $guid);
            $f3->set("check", $check);
            $f3->set('content', 'check.htm');
        }elseif($check['error'])
            $errors[] = $check['error'];

        $f3->set("errors", $errors);
        $f3->set("backHref", "/$guid/cash/$cashid/shifts/");

        echo View::instance()->render('layout.htm');

    }
);

$f3->route('GET /@guid/cash/@cashid/shifts/zform/@id',
    function($f3) {

        $cashid = $f3->PARAMS['cashid'];
        $guid = $f3->PARAMS['guid'];
        $id = $f3->PARAMS['id'];

        $errors = [];

        $check = CurlHelper::send([
            "Command" => "zFormShow",
            "NumFiscal" => $id,
        ]);

        $check = json_decode($check, TRUE);

        if(!$check["error"]) {
            $f3->set("id", $id);
            $f3->set("check", $check);
            $f3->set('content', 'zform.htm');

        }else
            $errors[] = $check["error"];

        $f3->set("errors", $errors);
        $f3->set("backHref", "/$guid/cash/$cashid/shifts/");
        echo View::instance()->render('layout.htm');

    }
);

$f3->route('GET /@guid/cash/@id/ticket/@type/new',
    function($f3) {
        $id = $f3->PARAMS['id'];
        $guid = $f3->PARAMS['guid'];
        $type = $f3->PARAMS['type'];

        ItemListModel::clear();
        $f3->reroute("/$guid/cash/$id/ticket/$type/item/select");
    }
);


$f3->route('POST /@guid/cash/@cashid/money/@type/add',
    function($f3) {
        $cashid = $f3->PARAMS['cashid'];
        $guid = $f3->PARAMS['guid'];
        $type = $f3->PARAMS['type'];

        if($f3->POST['sum']){
            $sum = $f3->POST['sum'];

            list($obj, $cash, $cashRegister) = CacheHelper::getAllParams($guid, $cashid);

            $params = [
                'DOCTYPE' => 0,
                'DOCSUBTYPE' => $type,
                'VER' => 1,
                'UID' => $guid,
                'TIN' => $obj->Tin,
                'INN' => "123456789012",
                'ORDERTAXNUM' => "101234567890123",
                //'INN' => $cashRegister->LastFiscalNum,
                'ORGNAME' => $obj->Name,
                'POINTNAME' => $obj->Name,
                'POINTADDR' => $obj->Address,
                'ORDERNUM' => $cashRegister->NextLocalNum,
                'CASHDESKNUM' => $cash->NumLocal,
                'CASHREGISTERNUM' => $cash->NumFiscal,
                'CASHIER' => 'Семко А.М.'
            ];

            $responseCheck = CurlHelper::send([
                "Command" => "Check",
                "params" => json_encode([
                    "CHECKHEAD" => $params,
                    "CHECKTOTAL" => [
                        "TOTALSUM" => FormatHelper::format_dec($sum)
                    ],
                    "CHECKPAY" => [
                        [
                            "PAYMENTFORM" => "Готівка",
                            "SUM" => FormatHelper::format_dec($sum)
                        ]
                    ]
                ])
            ]);

             $errors = [];

             $responseCheck = json_decode($responseCheck, TRUE);

             if(!$responseCheck["error"]) {
                     OrdersModel::addItem([
                         ":guid" => $guid,
                         ":fixal_num" => $responseCheck["ORDERTAXNUM"],
                         ":sum" => $sum,
                         ":sum_real" => $sum,
                         ":sum_card" => 0,
                         ":order_type" => $type
                     ]);

                     $f3->reroute("/$guid/cash/$cashid/shifts/check/{$responseCheck["ORDERTAXNUM"]}");
             }else
                 $errors[] = $responseCheck["error"];


             if($errors && count($errors) > 0){
                 $f3->set("errors", $errors);
                 echo View::instance()->render('layout.htm');
             }

        }

    });

$f3->route('GET|POST /@guid/cash/@id/money/put',
    function($f3) {
        $cashid = $f3->PARAMS['id'];
        $guid = $f3->PARAMS['guid'];

            $f3->set("cashid", $cashid);
            $f3->set("guid", $guid);
            $f3->set("type", 2); // служебный взнос
            $f3->set("backHref", "/$guid/cash/$cashid");
            $f3->set('content', 'moneyForm.htm');
            echo View::instance()->render('layout.htm');
    }
);

$f3->route('GET|POST /@guid/cash/@id/money/get',
    function($f3) {
        $cashid = $f3->PARAMS['id'];
        $guid = $f3->PARAMS['guid'];

        $f3->set("cashid", $cashid);
        $f3->set("guid", $guid);
        $f3->set("type", 3); // служебный взнос
        $f3->set("backHref", "/$guid/cash/$cashid");
        $f3->set('content', 'moneyForm.htm');
        echo View::instance()->render('layout.htm');
    }
);

$f3->route('GET|POST /@guid/cash/@id/ticket/@type/item/select',
    function($f3) {
        $id = $f3->PARAMS['id'];
        $guid = $f3->PARAMS['guid'];
        $type = $f3->PARAMS['type'];

        $items = ItemModel::get();

        $f3->set('isMain', false);
        $f3->set('title', "Додати товар");
        $f3->set('items', $items);
        $f3->set('id', $id);
        $f3->set('guid', $guid);
        $f3->set('type', $type);
        $f3->set("backHref", "/$guid/cash/$id");
        $f3->set('content','addItem.htm');
        echo View::instance()->render('layout.htm');
    }
);

$f3->route('GET|POST /@guid/cash/@id/ticket/@type/item/add',
    function($f3) {
        $id = $f3->PARAMS['id'];
        $guid = $f3->PARAMS['guid'];
        $items = $f3->POST['items'];
        $counts = $f3->POST['counts'];
        $type = $f3->PARAMS['type'];

        if($items){
            foreach($items as $k=>$v) {
                ItemListModel::addItem([":guid" => "", ":item_id" => $k, ":count" => intval($counts[$k])]);
            }
            $f3->reroute();
        }else{
            $id = $f3->PARAMS['id'];
            $items = ItemListModel::get();

            $f3->set('isMain', false);
            $f3->set('title', "Список товарів");
            $f3->set('items', $items);
            $f3->set('id', $id);
            $f3->set('guid', $guid);
            $f3->set('type', $type);
            $f3->set("backHref", "/$guid/cash/$id/ticket/$type/item/select");
            $f3->set('content','itemsList.htm');
            echo View::instance()->render('layout.htm');

        }
    }
);

$f3->route('GET|POST /@guid/cash/@cashid/ticket/@type/pay',
    function($f3) {
        $cashid = $f3->PARAMS['cashid'];
        $guid = $f3->PARAMS['guid'];
        $total = $f3->POST['total'];
        $type = $f3->PARAMS['type'];

        $f3->set('isMain', false);
        $f3->set('title', "Оплата чека");
        $f3->set('total', $total?$total:0);
        $f3->set('cashid', $cashid);
        $f3->set('guid', $guid);
        $f3->set('type', $type);
        $f3->set("backHref", "/$guid/cash/$cashid/ticket/$type/new");
        $f3->set('content','ticketPay.htm');

        echo View::instance()->render('layout.htm');
    }
);

$f3->route('POST /@guid/cash/@id/ticket/@type/fixal',
    function($f3) {

        $id = $f3->PARAMS['id'];
        $guid = $f3->PARAMS['guid'];
        $total = $f3->POST['total'];
        $sumNal = $f3->POST['sumNal'];
        $sumCard = $f3->POST['sumCard'];
        $type = $f3->PARAMS['type'];

        list($obj, $cash, $cashRegister) = CacheHelper::getAllParams($guid, $id);
        
        $errors = [];

        $items = ItemListModel::get();

        $params = [
            "CHECKHEAD" => [
                'DOCTYPE' => 0,
                'DOCSUBTYPE' => $type,
                'VER' => 1,
                'UID' => $guid,
                'TIN' => $obj->Tin,
                'INN' => "123456789012"/*$cashRegister->LastFiscalNum*/,
                'ORDERTAXNUM' => "101234567890123"/*$cashRegister->LastFiscalNum*/,
                'ORGNAME' => $obj->Name,
                'POINTNAME' => $obj->Name,
                'POINTADDR' => $obj->Address,
                'ORDERNUM' => $cashRegister->NextLocalNum,
                'CASHDESKNUM' => $cash->NumLocal,
                'CASHREGISTERNUM' => $cash->NumFiscal,
                'CASHIER' => 'Семко А.М.'
            ]];

        $total = 0;

        $params["CHECKEXCISE"] = [];
        $params["CHECKTAX"] = [];

        $checkExcise = [];
        $checkTax = [];

        foreach($items as $k=>$v){
            $cost = $v->price * $v->count;
            $total += $cost;

            $pdv_litera = $v->pdv_litera;
            $action_litera = $v->action_litera;

            $params["CHECKBODY"][] = [
                "CODE" => $v->code,
                "UKTZED" => $v->code_uktz,
                "NAME" => $v->title,
                "UNITCODE" => $v->em_code,
                "UNITNAME" => $v->em_title,
                "AMOUNT" => $v->count,
                "PRICE" => FormatHelper::format_dec($v->price),
                "LETTER" => $pdv_litera,
                "LETTEREXCISE" => $action_litera,
                "COST" => FormatHelper::format_dec($cost)
            ];

            $exciseSum = (($cost * $v->action_stavka) / (100 + $v->action_stavka));
            $taxSum = (($cost - $exciseSum) *  $v->pdv_stavka)/ (100 + $v->pdv_stavka);

            if(!$checkExcise[$action_litera.$v->action_stavka])
                $checkExcise[$action_litera.$v->action_stavka] = [];

            $exc = $checkExcise[$action_litera.$v->action_stavka];

            $exc["EXCISECODE"] = $action_litera;
            $exc["EXCISEPRC"] = FormatHelper::format_dec($v->action_stavka);

            $exc["EXCISESUM"] = FormatHelper::format_dec(floatval($exc["EXCISESUM"]) + $exciseSum);

            $checkExcise[$action_litera.$v->action_stavka] = $exc;

            if(!$checkTax[$pdv_litera.$v->pdv_stavka])
                $checkTax[$pdv_litera.$v->pdv_stavka] = [];

            $tax = $checkTax[$pdv_litera.$v->pdv_stavka];

            $tax["TAXCODE"] = $pdv_litera;
            $tax["TAXPRC"] = FormatHelper::format_dec($v->pdv_stavka);

            $tax["TAXSUM"] = FormatHelper::format_dec(floatval($tax["TAXSUM"]) + $taxSum);

            $checkTax[$pdv_litera.$v->pdv_stavka] = $tax;

            OrderItemsModel::addItem([
                ":item_id" => $v->item_id,
                ":count" => intval($v->count),
                ":order_id" => 0,
                ":pdv_sum" => $taxSum,
                ":excise_sum" => $exciseSum,
                ":cost" => $cost
                ]);
        }

        $params["CHECKEXCISE"] = array_values($checkExcise);
        $params["CHECKTAX"] = array_values($checkTax);


        $params["CHECKTOTAL"] = [
            "TOTALSUM" => FormatHelper::format_dec((floatval($total)))
        ];

        $params["CHECKPAY"] = [
            [
                "PAYMENTFORM" => "Готівка",
                "SUM" => FormatHelper::format_dec($sumNal)
            ],
            [
                "PAYMENTFORM" => "Картка",
                "SUM" => FormatHelper::format_dec($sumCard)
            ]
        ];

        $responseCheck = CurlHelper::send([
            "Command" => "Check",
            "params" => json_encode($params, JSON_UNESCAPED_UNICODE)
        ]);

        $responseCheck = json_decode($responseCheck, TRUE);

        if(!$responseCheck["error"]) {

            $orderId = OrdersModel::addItem([
                ":guid" => $guid,
                ":fixal_num" => $responseCheck["ORDERTAXNUM"],
                ":sum" => $total,
                ":sum_real" => $sumNal,
                ":sum_card" => $sumCard,
                ":order_type" => $type
            ]);
            OrderItemsModel::updateFixalNum($responseCheck["ORDERTAXNUM"]);
            OrderItemsModel::updateOrderId($orderId);
            $f3->reroute("/$guid/cash/$id/shifts/check/{$responseCheck["ORDERTAXNUM"]}");

        }else{
            $errors[] = $responseCheck["error"];
        }

        if($errors && count($errors) > 0){
            $f3->set('errors', $errors);
            echo View::instance()->render('layout.htm');
        }

    }
);

$f3->run();
