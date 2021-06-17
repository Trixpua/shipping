<?php

require __DIR__ . '/../../vendor/autoload.php';

use Trixpua\Shipping\Tnt\Quote\Tnt;
use Trixpua\Shipping\ShippingInfo;

//$tnt = new Tnt('95054-620', 'yourlogin@email.com', 'yourPassword', '0', 'YOUR-TAX-SITUATION', '00.000.000.0000-00', 'YOUR-STATE-REGISTRATION-NUMBER');
$tnt = new Tnt('95054-620', 'contato@revoled.com.br', 'DKQD61SJ', '2', 'ME', '14.958.472/0001-66', '029/0553270');

$shippingInfo = new ShippingInfo('03047-000', '2', '500', '0.05', true, '10', '10', '2');

$tnt->setData($shippingInfo);

$tnt->makeRequest();

$return = $tnt->getResult();

var_dump($return);

if ($return->status === 'OK') {
    echo "<p>The shipping cost is R$" . number_format($return->shippingCost, '2', ',',
            '') . " and the delivery time is {$return->deliveryTime} bussiness days</p>";
}