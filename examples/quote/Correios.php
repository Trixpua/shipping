<?php

require __DIR__ . '/../../vendor/autoload.php';

use Trixpua\Shipping\Correios\Quote\Correios;
use Trixpua\Shipping\ShippingInfo;

$correios = new Correios('95054-620');

$shippingInfo = new ShippingInfo('03047-000', '2', '500', '0.05', true, '10', '10', '2');
$correios->setData($shippingInfo);

$correios->makeRequest();


//$correios->setShippingModal('04014');
//$correios->makeRequest();


//$correios->setShippingModal('04014, 04510, 40169, 40215, 40290');
//$correios->makeRequest();

$return = $correios->getResult();


var_dump($return);

$modals = [
    '04014' => 'SEDEX',
    '04510' => 'PAC',
    '40169' => 'SEDEX 12',
    '40215' => 'SEDEX 10',
    '40290' => 'SEDEX Hoje',
    '04065' => 'SEDEX à cobrar',
    '04707' => 'PAC à cobrar'
];

if ($return->status === 'OK') {
    foreach ($return->modals as $modal) {
        if ($modal->status === 'OK') {
            echo "<p>The shipping cost for modal " . $modals[$modal->shipCode] . " is R$" . number_format($modal->shippingCost, '2', ',',
                    '') . " and the delivery time is {$modal->deliveryTime} bussiness day(s)" . ($modal->remarks ? " (Remarks:
                $modal->remarks)" : '') . "</p>";
        }
    }
}


