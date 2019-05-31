<?php

$orderDate = '31052019';
$orderTime = '143106';
$orderNum = '1693';

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Step 1 --------------------------------------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$curl1 = curl_init();

curl_setopt_array($curl1, array(
    CURLOPT_PORT => "3100",
    CURLOPT_URL => "http://192.168.1.172:3100/sign",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => '<?xml version="1.0" encoding="windows-1251"?>
<CHECK xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="check01.xsd">
    <CHECKHEAD>
        <DOCTYPE>0</DOCTYPE>
        <DOCSUBTYPE>0</DOCSUBTYPE>
        <VER>1</VER>
        <UID>75CE5D3E05B400A0E053C0A8016D7FEE</UID>
        <TIN>1010101017</TIN>
        <INN>123456789012</INN>
        <ORGNAME>КIОСК</ORGNAME>
        <POINTNAME>КIОСК</POINTNAME>
        <POINTADDR>УКРАЇНА, ЧЕРНIВЕЦЬКА ОБЛАСТЬ, М.НОВОДНІСТРОВСЬК апр</POINTADDR>
        <ORDERDATE>' . $orderDate . '</ORDERDATE>
        <ORDERTIME>' . $orderTime . '</ORDERTIME>
        <ORDERNUM>' . $orderNum .  '</ORDERNUM>
        <ORDERTAXNUM>101234567890123</ORDERTAXNUM>
        <CASHDESKNUM>1</CASHDESKNUM>
        <CASHREGISTERNUM>7000000322</CASHREGISTERNUM>
        <CASHIER>Семко А.М.</CASHIER>
    </CHECKHEAD>

    <CHECKTOTAL>
        <TOTALSUM>293.00</TOTALSUM>
    </CHECKTOTAL>

    <CHECKPAY>
        <ROW ROWNUM="1">
            <PAYMENTFORM>Наличные</PAYMENTFORM>
            <SUM>213.00</SUM>
        </ROW>
    </CHECKPAY>

    <CHECKTAX>
        <ROW ROWNUM="1">
            <TAXCODE>A</TAXCODE>
            <TAXPRC>20.00</TAXPRC>
            <TAXSUM>80.00</TAXSUM>
        </ROW>
    </CHECKTAX>

    <CHECKBODY>
        <ROW ROWNUM="1">
            <CODE>ewrwerwe</CODE>
            <UKTZED>sdfs</UKTZED>
            <NAME>всякие слова кирилицей!!!211</NAME>
            <UNITCODE>7</UNITCODE>
            <UNITNAME>грн</UNITNAME>
            <AMOUNT>4</AMOUNT>
            <PRICE>19.00</PRICE>
            <LETTER>A</LETTER>
            <LETTEREXCISE>B</LETTEREXCISE>
            <COST>76.00</COST>
        </ROW>
    </CHECKBODY>
</CHECK>',
));

$response = curl_exec($curl1);
$err = curl_error($curl1);

curl_close($curl1);

if ($err) {
    echo "cURL Error 1:" . $err;
    die();
}

$jsonDecoded = json_decode($response);

if (!$jsonDecoded->success) {
    echo "cURL Error 2";
    die();
}

$base64Decoded = base64_decode($jsonDecoded->data);

// var_dump($base64Decoded);
// echo strlen($base64Decoded);
// file_put_contents('signedData.p7s', $base64Decoded);

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Step 2 --------------------------------------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$curl2 = curl_init();

curl_setopt_array($curl2, array(
    CURLOPT_URL => "http://80.91.165.208/er/doc",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => $base64Decoded,
    CURLOPT_HTTPHEADER => array(
        "Content-Type: application/octet-stream",
        "Content-Length: " . strlen($base64Decoded)
    ),
));

$response = curl_exec($curl2);
$err = curl_error($curl2);

curl_close($curl2);

if ($err) {
    echo "cURL Error 3:" . $err;
    die();
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Step 3 --------------------------------------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$curl3 = curl_init();

curl_setopt_array($curl3, array(
    CURLOPT_PORT => "3100",
    CURLOPT_URL => "http://192.168.1.172:3100/decrypt",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => base64_encode($response),
));

$response = curl_exec($curl3);
$err = curl_error($curl3);

curl_close($curl3);

if ($err) {
    echo "cURL Error 5:" . $err;
    die();
}

$jsonDecoded = json_decode($response);

if (!$jsonDecoded->success) {
    echo "cURL Error 6";
    die();
}

$base64Decoded = base64_decode($jsonDecoded->data);

echo "-------> Decoded response after creating item:\n\n";
echo $base64Decoded;

$hasMatches = preg_match('/<ORDERTAXNUM>(.+)<\/ORDERTAXNUM>/', $base64Decoded, $matches);

if (!$hasMatches) {
    echo "cURL Error 7";
    die();
}

$numFiscal = $matches[1];

echo "\n\n-------> NumFiscal: $numFiscal\n";

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Step 4 --------------------------------------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$curl4 = curl_init();

curl_setopt_array($curl4, array(
    CURLOPT_PORT => "3100",
    CURLOPT_URL => "http://192.168.1.172:3100/sign",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => '{"Command":"Check","NumFiscal":"' . $numFiscal . '"}',
));

$response = curl_exec($curl4);
$err = curl_error($curl4);

curl_close($curl4);

if ($err) {
    echo "cURL Error 8:" . $err;
    die();
}

$jsonDecoded = json_decode($response);

if (!$jsonDecoded->success) {
    echo "cURL Error 9";
    die();
}

$base64Decoded = base64_decode($jsonDecoded->data);

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Step 5 --------------------------------------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


$curl5 = curl_init();

curl_setopt_array($curl5, array(
    CURLOPT_URL => "http://80.91.165.208/er/cmd",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => $base64Decoded,
    CURLOPT_HTTPHEADER => array(
        "Content-Type: application/octet-stream",
        "Content-Length: " . strlen($base64Decoded)
    ),
));

$response = curl_exec($curl5);
$err = curl_error($curl5);

curl_close($curl5);

if ($err) {
    echo "cURL Error 10:" . $err;
    die();
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Step 6 --------------------------------------------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$curl6 = curl_init();

curl_setopt_array($curl6, array(
    CURLOPT_PORT => "3100",
    CURLOPT_URL => "http://192.168.1.172:3100/decrypt",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => base64_encode($response),
));

$response = curl_exec($curl6);
$err = curl_error($curl6);

curl_close($curl6);

if ($err) {
    echo "cURL Error 11:" . $err;
    die();
}

$jsonDecoded = json_decode($response);

if (!$jsonDecoded->success) {
    echo "cURL Error 12";
    die();
}

$base64Decoded = base64_decode($jsonDecoded->data);

echo "\n-------> Decoded response after requesting item data:\n\n";
echo $base64Decoded . "\n";
