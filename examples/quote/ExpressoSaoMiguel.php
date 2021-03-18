<?php

require __DIR__ . '/../../vendor/autoload.php';


use Trixpua\Shipping\ExpressoSaoMiguel\Quote\ExpressoSaoMiguel;
use Trixpua\Shipping\ShippingInfo;

$expressoSaoMiguel = new ExpressoSaoMiguel('95054-620','yourUser', 'yourPassword');

$shippingInfo = new ShippingInfo('88828-000', '2', '500', '0.05', true, '10', '10', '2');

$expressoSaoMiguel->setData($shippingInfo);

$expressoSaoMiguel->makeRequest();

$return = $expressoSaoMiguel->getResult();

var_dump($return);

if ($return->status === 'OK') {
    echo "<p>The shipping cost is R$" . number_format($return->shippingCost, '2', ',',
            '') . " and the delivery time is {$return->deliveryTime} bussiness days</p>";
}