<?php

require __DIR__ . '/../../vendor/autoload.php';

use Trixpua\Shipping\Jadlog\Quote\Jadlog;
use Trixpua\Shipping\ShippingInfo;

$jadlog = new Jadlog('13025-242', 'yourPassword', '00.000.000.0000-00');

$shippingInfo = new ShippingInfo('03047-000', '2', '500', '0.05', true, '10', '10', '2');
$jadlog->setData($shippingInfo);

$jadlog->makeRequest();

$return = $jadlog->getResult();

var_dump($return);

$modals = [
    '0' => 'EXPRESSO',
    '3' => '.PACKAGE',
    '4' => 'RODOVIÁRIO',
    '5' => 'ECONÔMICO',
    '6' => 'DOC',
    '7' => 'CORPORATE',
    '9' => '.COM',
    '10' => 'INTERNACIONAL',
    '12' => 'CARGO',
    '14' => 'EMERGÊNCIAL'
];

if ($return->status === 'OK') {
    echo "<p>The shipping cost for modal " . $modals[$return->shipCode] . " is R$" . number_format($return->shippingCost,
            '2', ',', '') . "</p>";
}
