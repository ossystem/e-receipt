<?php


// Kickstart the framework
$f3=require('lib/base.php');

use models\ItemModel;
use models\ItemListModel;

$f3->set('DEBUG',1);
if ((float)PCRE_VERSION<7.9)
	trigger_error('PCRE version is out of date');


// Load configuration
$f3->config('config.ini');


function getCurlResponse($params){

    $apiServerName = 'http://api.'.$_SERVER['SERVER_NAME']."";

    $ch = curl_init($apiServerName);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function getObjectByGUID($guid){
    $obj = json_decode(file_get_contents("./json/responses/objects.json"));
    $arr = array_shift(array_filter($obj->TaxObjects, function($v) use ($guid) {return $v->Guid == $guid;}));
    return $arr;
}


$f3->route('GET /',
	function($f3) {
//		$classes=array(
//			'Base'=>
//				array(
//					'hash',
//					'json',
//					'session',
//					'mbstring'
//				),
//			'Cache'=>
//				array(
//					'apc',
//					'apcu',
//					'memcache',
//					'memcached',
//					'redis',
//					'wincache',
//					'xcache'
//				),
//			'DB\SQL'=>
//				array(
//					'pdo',
//					'pdo_dblib',
//					'pdo_mssql',
//					'pdo_mysql',
//					'pdo_odbc',
//					'pdo_pgsql',
//					'pdo_sqlite',
//					'pdo_sqlsrv'
//				),
//			'DB\Jig'=>
//				array('json'),
//			'DB\Mongo'=>
//				array(
//					'json',
//					'mongo'
//				),
//			'Auth'=>
//				array('ldap','pdo'),
//			'Bcrypt'=>
//				array(
//					'openssl'
//				),
//			'Image'=>
//				array('gd'),
//			'Lexicon'=>
//				array('iconv'),
//			'SMTP'=>
//				array('openssl'),
//			'Web'=>
//				array('curl','openssl','simplexml'),
//			'Web\Geo'=>
//				array('geoip','json'),
//			'Web\OpenID'=>
//				array('json','simplexml'),
//			'Web\Pingback'=>
//				array('dom','xmlrpc')
//		);
//		$f3->set('classes',$classes);

        $response = getCurlResponse(["Command" => "Objects"]);


        file_put_contents("./json/responses/objects.json", $response);

        $obj = json_decode($response);

        $f3->set('error', $obj->error);
        $f3->set('isMain', true);
        $f3->set('title', "Точки продаж");
        $f3->set('taxObjects', $obj->TaxObjects);
		$f3->set('content','main.htm');
		echo View::instance()->render('layout.htm');
	}
);



$f3->route('GET /@guid/cashRegisters',
    function($f3) {
        $guid = $f3->PARAMS['guid'];

        $obj = getObjectByGUID($guid);

        $f3->set('error', $obj->error);
        $f3->set('title', "Кассы");
        $f3->set('guid', $guid);
        $f3->set('cashRegisters', $obj->CashRegisters);
        $f3->set('content','cashRegisters.htm');
        echo View::instance()->render('layout.htm');
    }
);

$f3->route('GET /items',
    function($f3) {

        $items = ItemModel::get();
        /*$obj = json_decode(file_get_contents('json/responses/objects.json'));
        $arr = array_shift(array_filter($obj->TaxObjects, function($v) use ($f3) {return $v->Guid == $f3->PARAMS['id'];}));
        $f3->set('panel', "cashRegisters");*/
        $f3->set('title', "Номенклатура");
        $f3->set('items', $items);
        $f3->set('content','items.htm');
        echo View::instance()->render('layout.htm');
    }
);

$f3->route('GET|POST /items/add',
    function($f3) {

        /*$obj = json_decode(file_get_contents('json/responses/objects.json'));
        $arr = array_shift(array_filter($obj->TaxObjects, function($v) use ($f3) {return $v->Guid == $f3->PARAMS['id'];}));
        $f3->set('panel', "cashRegisters");*/
//        $f3->set('items', $items);
        if($f3->POST['params']){
            ItemModel::addItem($f3->POST['params']);
            $f3->reroute('/items');
        } else {
            $f3->set('title', "Добавить товар");
            $f3->set('action', 'add');
            $f3->set('content', 'itemForm.htm');
            echo View::instance()->render('layout.htm');
        }
    }
);

$f3->route('GET|POST /items/@id/update',
    function($f3) {
        $id = $f3->PARAMS['id'];

        $item = ItemModel::getById($id);


        /*$obj = json_decode(file_get_contents('json/responses/objects.json'));
        $arr = array_shift(array_filter($obj->TaxObjects, function($v) use ($f3) {return $v->Guid == $f3->PARAMS['id'];}));
        $f3->set('panel', "cashRegisters");*/
//        $f3->set('items', $items);
        if($f3->POST['params']){
//            var_dump($f3->POST['params']);
//            die();
            ItemModel::updateItem($id, $f3->POST['params']);
            $f3->reroute();
        } else {
            $f3->set('title', "Изменить товар");
            $f3->set('action', 'update');
            $f3->set('item', $item);
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

        $response = getCurlResponse([
            "Command" => "CashRegisterState",
            "NumFiscal" => $id
        ]);

        file_put_contents("./json/responses/cash$id.json", $response);

        $cash = json_decode($response);
        $f3->set('error', $cash->error);
        $f3->set('title', "Касса: ".($cash->State > 0 ? 'Смена открыта' : 'Смена закрыта'));
        $f3->set('id', $id);
        $f3->set('guid', $guid);
        $f3->set('cash', $cash);
        $f3->set('content', 'cashMenu.htm');
        echo View::instance()->render('layout.htm');
//        ItemModel::remove($id);
//        $f3->reroute("/items");
    }
);

function getAllParams($guid, $id){

    $obj = getObjectByGUID($guid);

    $cash = array_shift(array_filter($obj->CashRegisters, function($v) use ($id) { return $v->NumFiscal == $id; }))/*json_decode(file_get_contents("./json/responses/cash$id.json"))*/;

    $cashRegister = json_decode(file_get_contents("./json/responses/cash$id.json"));

    return [$obj, $cash, $cashRegister];
}

$f3->route('GET /@guid/cash/@id/shift/open',
    function($f3) {
        $id = $f3->PARAMS['id'];
        $guid = $f3->PARAMS['guid'];

        list($obj, $cash, $cashRegister) = getAllParams($guid, $id);

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


        $response = getCurlResponse([
            "Command" => "ShiftOpen",
            "params" => json_encode(["CHECKHEAD" => $params])
        ]);


//        $shift = json_decode($response);
//        var_dump($response);
//        die();
        $f3->reroute("/$guid/cash/$id");
    }
);

$f3->route('GET /@guid/cash/@id/shift/close',
    function($f3) {
        $id = $f3->PARAMS['id'];
        $guid = $f3->PARAMS['guid'];

        list($obj, $cash, $cashRegister) = getAllParams($guid, $id);

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

        $responseZClose = getCurlResponse([
            "Command" => "zForm",
            "params" => json_encode([
                "ZFORMHEAD" => array_merge($params, ['ORDERTAXNUM' => "101234567890123"/*$cashRegister->LastFiscalNum*/]),
                "ZFORMPAY" => [
                    "SUMREAL" => "00.00",
                    "ZFORMREALIZ" => [
                        [
                            "FORMPAYREAL" => "Наличные",
                            "SUMPAYREAL" => "00.00"
                        ]
                    ],
                    "SUMRET" => "00.00",
                    "ZFORMRETURN" => [
                        [
                            "FORMPAYRET" => "Наличные",
                            "SUMPAYRET" => "00.00"
                        ]
                    ]
                ],
                "ZFORMSUMREAL" => [
                    "ZFORMTURNOVER" => [
                        [
                            "TURNOVERNAME" => "Обіг А",
                            "TURNOVERTOTAL" => "00.00"
                        ]
                    ],
                    "ZFORMTAX" => [
                        [
                            "TAXNAME" => "ПДВ А=0%",
                            "TAXTOTAL" => "00.00"
                        ]
                    ],
                    "TOTALSUMREAL" => "00.00",
                    "TAXSUMREAL" => "00.00",
                    "FEESUMREAL" => "00.00",
                ],

                "ZFORMSUMRETUNRN" =>[
                    "ZFORMTURNOVERRET" => [
                        [
                           "TURNOVERNAMERET" => "Обіг А",
                           "TURNOVERTOTALRET" => "00.00",
                        ]
                    ],
                    "ZFORMTAXRET" => [
                        [
                            "TAXNAMERET" => "ПДВ А=0%",
                            "TAXTOTALRET" => "00.00"
                        ]
                    ],
                    "TOTALSUMRET" => "00.00",
                    "TAXSUMRET" => "00.00",
                    "FEESUMRET" => "00.00"
                ],

                "ZFORMBODY" => [
                    "SERVICEINPUT" => "00.00",
                    "SERVICEOUTPUT" => "00.00",
                    "ORDERCOUNT" => "0",
                    "ORDEREXPCOUNT" => "0",
                    "ORDERLAST" => "0",
                    "REPORTZERO" => "0"
                ]
            ])
        ]);

        $responseCashRegister = getCurlResponse([
            "Command" => "CashRegisterState",
            "NumFiscal" => $id
        ]);

        $cashRegister = json_decode($responseCashRegister);

        $params = array_merge($params,
            [
                'DOCTYPE' => 2,
                'DOCSUBTYPE' => 0,
                'ORDERNUM' => $cashRegister->NextLocalNum
            ]
        );

        $responseShiftClose = getCurlResponse([
            "Command" => "ShiftClose",
            "params" => json_encode(["CHECKHEAD" => $params])
        ]);

        //header('Content-Type: text/html; charset=windows-1251');
//        var_dump($responseShiftClose, $responseZClose);
//        die();
//        $shift = json_decode($responseShiftClose);
//        var_dump($responseZClose, $responseShiftClose/*, $responseShiftClose, $params*/);
//        die();
        $f3->reroute("/$guid/cash/$id");

    }
);

$f3->route('GET /@guid/cash/@id/zform',
    function($f3) {
        $id = $f3->PARAMS['id'];
        $guid = $f3->PARAMS['guid'];

        list($obj, $cash, $cashRegister) = getAllParams($guid, $id);

        $params = [
//            'DOCTYPE' => 1,
//            'DOCSUBTYPE' => 0,
            'VER' => 1,
            'UID' => $guid,
            'TIN' => $obj->Tin,
            'INN' => $cashRegister->LastFiscalNum,
            'ORGNAME' => $obj->Name,
            'POINTNAME' => $obj->Name,
            'POINTADDR' => $obj->Address,
            'ORDERNUM' => $cashRegister->NextLocalNum,
            'CASHDESKNUM' => $cash->NumLocal,
            'CASHREGISTERNUM' => $cash->NumFiscal,
            'CASHIER' => 'Семко А.М.'
        ];

        $response = getCurlResponse([
            "Command" => "zForm",
            "params" => json_encode(["CHECKHEAD" => $params])
        ]);

        $shift = json_decode($response);
        var_dump($response);
        die();
        $f3->reroute("/$guid/cash/$id");
    }
);

$f3->route('GET /@guid/cash/@id/documents',
    function($f3) {
        $id = $f3->PARAMS['id'];
        $guid = $f3->PARAMS['guid'];

        $response = json_decode(getCurlResponse([
            "Command" => "CashRegisterState",
            "NumFiscal" => $id
        ]));

        $documents = getCurlResponse([
            "Command" => "Documents",
            "ShiftId" => $response->ShiftId,
        ]);

        var_dump($documents);

//
//        //$shift = json_decode($response);
//        $f3->reroute("/cash/$id");
    }
);

$f3->route('GET /testxml',
    function($f3){

        $datetime = new DateTime();

        $params = [
            "CHECKHEAD" => [
                'DOCTYPE' => 0,
                'DOCSUBTYPE' => 0,
                'VER' => 1,
                'UID' => "UIUIUIIUIUIUIUIUIIUI",
                'TIN' => "878970909090",
                //'INN' => $cashRegister->LastFiscalNum,
                'ORGNAME' => "Киоск",
                'POINTNAME' => "Киоск",
                'POINTADDR' => "Черноморск",
                'ORDERDATE' => $datetime->format("dmY"),
                'ORDERTIME' => $datetime->format("His"),
                'ORDERNUM' => "78980809899",
                'ORDERTAXNUM' => "78980809899",
                'CASHDESKNUM' => "8787899",
                'CASHREGISTERNUM' => "88995544",
                'CASHIER' => 'Семко А.М.'
            ],
            "CHECKBODY" => [
                    [
                        "CODE" => "777",
                        "UKTZED" => "777",
                        "NAME" => "NAME",
                        "UNITCODE" => "yyy",
                        "UNITNAME" => "title",
                        "AMOUNT" => 20,
                        "PRICE" => number_format(20, 2),
                        "LETTER" => "A",
                        "LETTEREXCISE" => "a1",
                        "COST" => number_format(10, 2)
                    ],
                    [
                        "CODE" => "888",
                        "UKTZED" => "777",
                        "NAME" => "NAME",
                        "UNITCODE" => "yyy",
                        "UNITNAME" => "title",
                        "AMOUNT" => 20,
                        "PRICE" => number_format(20, 2),
                        "LETTER" => "A",
                        "LETTEREXCISE" => "a1",
                        "COST" => number_format(10, 2)
                    ],
            ]
        ];

        $check = getCurlResponse([
            "Command" => "Check",
            "params" => json_encode($params)
        ]);
        header('Content-Type: application/xml; charset=windows-1251');
        echo $check;
    }
);


$f3->route('GET /@guid/cash/@id/check/@fNum',
    function($f3) {
        $fNum = $f3->PARAMS['fNum'];
        $guid = $f3->PARAMS['guid'];

        $check = getCurlResponse([
            "Command" => "CheckShow",
            "NumFiscal" => $fNum
        ]);

        header('Content-Type: text/html; charset=windows-1251');
        var_dump($check);

//
//        //$shift = json_decode($response);
//        $f3->reroute("/cash/$id");
    }
);

$f3->route('GET /@guid/cash/@id/shifts',
    function($f3) {
        $id = $f3->PARAMS['id'];
        $guid = $f3->PARAMS['guid'];

        $datetime = new DateTime();
        $dateFrom = new DateTime('-1 days');

        $shifts = getCurlResponse([
            "Command" => "Shifts",
            "NumFiscal" => $id,
            "From" => $dateFrom->format("Y-m-d H:i:s"),
            "To" => $datetime->format("Y-m-d H:i:s")
        ]);

//        $datefrom = $dateFrom->format("Y-m-d H:i:s");
//        $dateto = $datetime->format("Y-m-d H:i:s");

        $shifts = json_decode($shifts);

//        var_dump($shifts);
//        die();

        foreach($shifts->Shifts as $k=> $v){

            $documents = getCurlResponse([
                "Command" => "Documents",
                "ShiftId" => $v->ShiftId
            ]);

            $docs = json_decode($documents);

            $v->documents = array_reverse($docs->Documents);
        }

        $f3->set('id', $id);
        $f3->set('guid', $guid);
        $f3->set("shifts", array_reverse($shifts->Shifts));
        $f3->set('content', 'shifts.htm');
        echo View::instance()->render('layout.htm');

//        var_dump($check, $datefrom, $dateto);
//        die();

//
//        //$shift = json_decode($response);
//        $f3->reroute("/cash/$id");
    }
);

$f3->route('GET /@guid/cash/@cashid/shifts/check/@id',
    function($f3) {
        $cashid = $f3->PARAMS['cashid'];
        $guid = $f3->PARAMS['guid'];
        $id = $f3->PARAMS['id'];

        $check = getCurlResponse([
            "Command" => "CheckShow",
            "NumFiscal" => $id,
        ]);

//        header('Content-Type: text/html; charset=windows-1251');

        $xml = new \SimpleXMLElement($check);
//        var_dump($xml->CHECKTOTAL);

//        $datetime = new DateTime();
//        $dateFrom = new DateTime('-1 days');
//
//        $shifts = getCurlResponse([
//            "Command" => "Shifts",
//            "NumFiscal" => $id,
//            "From" => $dateFrom->format("Y-m-d H:i:s"),
//            "To" => $datetime->format("Y-m-d H:i:s")
//        ]);
//
//        $datefrom = $dateFrom->format("Y-m-d H:i:s");
//        $dateto = $datetime->format("Y-m-d H:i:s");
//
//        $shifts = json_decode($shifts);
//
//        foreach($shifts->Shifts as $k=> $v){
//
//            $documents = getCurlResponse([
//                "Command" => "Documents",
//                "ShiftId" => $v->ShiftId
//            ]);
//
//            $docs = json_decode($documents);
//
//            $v->documents = array_reverse($docs->Documents);
//        }
//

        $check = json_decode(json_encode($xml), TRUE);

        $datestr = preg_replace("/(\d{2})(\d{2})(\d{4})/", "$1.$2.$3", $check["CHECKHEAD"]["ORDERDATE"]);
        $datestr.= " ". preg_replace("/(\d{2})(\d{2})(\d{2})/", "$1:$2:$3", $check["CHECKHEAD"]["ORDERTIME"]);

        $f3->set('date',  $datestr);
        $f3->set('id', $id);
        $f3->set('guid', $guid);
        $f3->set("check", $check);
        $f3->set('content', 'check.htm');
        echo View::instance()->render('layout.htm');

    }
);

$f3->route('GET /shifts/check',
    function($f3) {
//        $cashid = $f3->PARAMS['cashid'];
//        $guid = $f3->PARAMS['guid'];
//        $id = $f3->PARAMS['id'];

        $datetime = new \DateTime('now');

        $params = [
            "CHECKHEAD" => [
                'DOCTYPE' => 0,
                'DOCSUBTYPE' => 0,
                'VER' => 1,
                'UID' => "UIUIUIIUIUIUIUIUIIUI",
                'TIN' => "878970909090",
                //'INN' => $cashRegister->LastFiscalNum,
                'ORGNAME' => "Киоск",
                'POINTNAME' => "Киоск",
                'POINTADDR' => "Черноморск",
                'ORDERDATE' => $datetime->format("dmY"),
                'ORDERTIME' => $datetime->format("His"),
                'ORDERNUM' => "78980809899",
                'ORDERTAXNUM' => "78980809899",
                'CASHDESKNUM' => "8787899",
                'CASHREGISTERNUM' => "88995544",
                'CASHIER' => 'Семко А.М.'
            ],
            "CHECKBODY" => [
                "ROW" => [
                    [
                        "CODE" => "777",
                        "UKTZED" => "777",
                        "NAME" => "NAME",
                        "UNITCODE" => "yyy",
                        "UNITNAME" => "title",
                        "AMOUNT" => 20,
                        "PRICE" => number_format(20, 2),
                        "LETTER" => "A",
                        "LETTEREXCISE" => "a1",
                        "COST" => number_format(10, 2)
                    ],
                    [
                        "CODE" => "888",
                        "UKTZED" => "777",
                        "NAME" => "NAME",
                        "UNITCODE" => "yyy",
                        "UNITNAME" => "title",
                        "AMOUNT" => 20,
                        "PRICE" => number_format(20, 2),
                        "LETTER" => "A",
                        "LETTEREXCISE" => "a1",
                        "COST" => number_format(10, 2)
                    ],
                ]
            ]
        ];



        $params["CHECKTOTAL"] = [
            "TOTALSUM" => number_format(480 ,2)
        ];

        $params["CHECKPAY"] = [
            [
                "PAYMENTFORM" => "Наличные",
                "SUM" => number_format(480, 2)
            ]
        ];

        $params["CHECKTAX"] = [
            [
                "TAXCODE" => "A",
                "TAXPRC" => number_format(20.00, 2),
                "TAXSUM" => number_format(80.00, 2)
            ]
        ];

//        $check = getCurlResponse([
//            "Command" => "CheckShow",
//            "NumFiscal" => $id,
//        ]);
//
//        $xml = new \SimpleXMLElement($check);

//        $f3->set('id', $id);
        $datestr = preg_replace("/(\d{2})(\d{2})(\d{4})/", "$1.$2.$3",$params["CHECKHEAD"]["ORDERDATE"]);
        $datestr.= " ". preg_replace("/(\d{2})(\d{2})(\d{2})/", "$1:$2:$3",$params["CHECKHEAD"]["ORDERTIME"]);

        $f3->set('check', $params);
        $f3->set('date',  $datestr);
//        $f3->set("check", json_decode(json_encode($xml), TRUE));
        $f3->set('content', 'check.htm');
        echo View::instance()->render('layout.htm');

    }
);

$f3->route('GET /@guid/cash/@cashid/shifts/zform/@id',
    function($f3) {

        $cashid = $f3->PARAMS['cashid'];
        $guid = $f3->PARAMS['guid'];
        $id = $f3->PARAMS['id'];

        $check = getCurlResponse([
            "Command" => "zFormShow",
            "NumFiscal" => $id,
        ]);

//        var_dump($check);
//        die();
        $xml = new \SimpleXMLElement($check);

//        die();
//        $f3->set('check', ["xml"=>$xml]);
        $f3->set("check", json_decode(json_encode($xml), TRUE));
        $f3->set('content', 'zform.htm');
        echo View::instance()->render('layout.htm');

    }
);

$f3->route('GET /@guid/cash/@id/ticket/new',
    function($f3) {
        $id = $f3->PARAMS['id'];
        $guid = $f3->PARAMS['guid'];

        ItemListModel::clear();
        $f3->reroute("/$guid/cash/$id/ticket/item/select");
    }
);

$f3->route('GET|POST /@guid/cash/@id/ticket/item/select',
    function($f3) {
        $id = $f3->PARAMS['id'];
        $guid = $f3->PARAMS['guid'];

        $items = ItemModel::get();

        $f3->set('isMain', false);
        $f3->set('title', "Добавить товар");
        $f3->set('items', $items);
        $f3->set('id', $id);
        $f3->set('guid', $guid);
        $f3->set('content','addItem.htm');
        echo View::instance()->render('layout.htm');
    }
);

$f3->route('GET|POST /@guid/cash/@id/ticket/item/add',
    function($f3) {
        $id = $f3->PARAMS['id'];
        $guid = $f3->PARAMS['guid'];
        $items = $f3->POST['items'];
        $counts = $f3->POST['counts'];

        if($items){
              //var_dump($f3->POST['items'], $f3->POST['counts']);
            foreach($items as $k=>$v) {
                ItemListModel::addItem([":guid" => "", ":item_id" => $k, ":count" => intval($counts[$k])]);
            }
            $f3->reroute();
        }else{
            $id = $f3->PARAMS['id'];
            $items = ItemListModel::get();

            $f3->set('isMain', false);
            $f3->set('title', "Список товаров");
            $f3->set('items', $items);
            $f3->set('id', $id);
            $f3->set('guid', $guid);
            $f3->set('content','itemsList.htm');
            echo View::instance()->render('layout.htm');

        }
    }
);

$f3->route('GET|POST /@guid/cash/@id/ticket/pay',
    function($f3) {
        $id = $f3->PARAMS['id'];
        $guid = $f3->PARAMS['guid'];
        $total = $f3->POST['total'];

        $f3->set('isMain', false);
        $f3->set('title', "Оплата чека");
        $f3->set('total', $total?$total:0);
        $f3->set('id', $id);
        $f3->set('guid', $guid);
        $f3->set('content','ticketPay.htm');

        echo View::instance()->render('layout.htm');
    }
);

$f3->route('GET|POST /@guid/cash/@id/ticket/fixal',
    function($f3) {

        $id = $f3->PARAMS['id'];
        $guid = $f3->PARAMS['guid'];
        $total = $f3->POST['total'];


        list($obj, $cash, $cashRegister) = getAllParams($guid, $id);

        $items = ItemListModel::get();

        $params = [
            "CHECKHEAD" => [
                'DOCTYPE' => 0,
                'DOCSUBTYPE' => 0,
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
            foreach($items as $k=>$v){
                $total += ($v->price * $v->count);

                $params["CHECKBODY"][] = [
                    "CODE" => $v->code,
                    "UKTZED" => $v->code_uktz,
                    "NAME" => $v->title,
                    "UNITCODE" => $v->em_code,
                    "UNITNAME" => $v->em_title,
                    "AMOUNT" => $v->count,
                    "PRICE" => number_format($v->price, 2),
                    "LETTER" => $v->pdv_litera,
                    "LETTEREXCISE" => $v->action_litera,
                    "COST" => number_format(($v->price * $v->count), 2)
                ];
            }

            $params["CHECKTOTAL"] = [
                "TOTALSUM" => number_format((floatval($total) + 80),2)
            ];

            $params["CHECKPAY"] = [
                [
                    "PAYMENTFORM" => "Наличные",
                    "SUM" => number_format($total, 2)
                ]
            ];

            $params["CHECKTAX"] = [
                [
                    "TAXCODE" => "A",
                    "TAXPRC" => number_format(20.00, 2),
                    "TAXSUM" => number_format(80.00, 2)
                ]
            ];

        $responseCheck = getCurlResponse([
            "Command" => "Check",
            "params" => json_encode($params)
        ]);

        $responseCheckXml = new \SimpleXMLElement($responseCheck);

        $f3->reroute("/$guid/cash/$id/shifts/check/{$responseCheckXml->ORDERTAXNUM}");

//        $check = getCurlResponse([
//            "Command" => "CheckShow",
//            "NumFiscal" => $responseCheckXml->ORDERTAXNUM,
//        ]);
//
//        $checkXml = new \SimpleXMLElement($check);
//
//        $check = json_decode(json_encode($checkXml), TRUE);
//
//        $datestr = preg_replace("/(\d{2})(\d{2})(\d{4})/", "$1.$2.$3", $check["CHECKHEAD"]["ORDERDATE"]);
//        $datestr.= " ". preg_replace("/(\d{2})(\d{2})(\d{2})/", "$1:$2:$3", $check["CHECKHEAD"]["ORDERTIME"]);
//
//
//        $f3->set('date',  $datestr);
//        $f3->set('id', $responseCheckXml->ORDERTAXNUM);
//        $f3->set('guid', $guid);
//        $f3->set("check", $check);
//        $f3->set('content', 'check.htm');
//        echo View::instance()->render('layout.htm');

//        $shift = json_decode($responseShiftClose);
//        var_dump($responseCheck);
//        die();
//        $f3->reroute("/$guid/cash/$id");
//        $f3->set('isMain', false);
//        $f3->set('title', "Оплата чека");
//        $f3->set('total', $total?$total:0);
//        $f3->set('id', $id);
//        $f3->set('content','ticketPay.htm');
//
//        echo View::instance()->render('layout.htm');
    }
);

//$f3->route('GET /userref',
//	function($f3) {
//		$f3->set('content','userref.htm');
//		echo View::instance()->render('layout.htm');
//	}
//);


$f3->run();
