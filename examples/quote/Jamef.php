<?php

require __DIR__ . '/../../vendor/autoload.php';

use Trixpua\Shipping\Jamef\Quote\Jamef;
use Trixpua\Shipping\ShippingInfo;

$jamef = new Jamef('yourUser', '00.000.000.0000-00', 'YOUR STATE', 'YOUR CITY NAME', '00');

$shippingInfo = new ShippingInfo('03047-000', '2', '500', '0.05', true, '10', '10', '2');
$jamef->setData($shippingInfo);

$jamef->makeRequest();

$return = $jamef->getResult();

var_dump($return);

if ($return->status === 'OK') {
    echo "<p>The shipping cost is R$" . number_format($return->shippingCost, '2', ',',
            '') . " and the delivery time is {$return->deliveryTime} bussiness days</p>";
}