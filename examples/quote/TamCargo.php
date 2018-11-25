<?php

require __DIR__ . '/../../vendor/autoload.php';

use Trixpua\Shipping\ShippingInfo;
use Trixpua\Shipping\TamCargo\Quote\TamCargo;

$tam = new TamCargo('95054-620', 'yourlogin@email.com', 'yourPassword');

$shippingInfo = new ShippingInfo('03047-000', '2', '500', '0.05', true, '10', '10', '2');
$tam->setData($shippingInfo);

$shippingInfo->setVolume(0.5);

$tam->makeRequest();

$return = $tam->getResult();

var_dump($return);

if ($return->status === 'OK') {
    foreach ($return->modals as $modal) {
        if ($modal->status === 'OK') {
            echo "<p>The shipping cost for modal $modal->modal is R$" . number_format($modal->shippingCost, '2', ',',
                    '') . " and the delivery time is {$modal->deliveryTime} bussiness day(s)</p>";
        }
    }
}