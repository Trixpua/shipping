<?php

require __DIR__ . '/../../vendor/autoload.php';

use Trixpua\Shipping\Tnt\Tracking\Tnt;

$tnt = new Tnt('yourlogin@email.com', '00.000.000.0000-00');

$tnt->setData(10456);

$tnt->makeRequest();

$return = $tnt->getResult();

var_dump($return);

if ($return->status === 'OK') {
    if (!$return->deliveryTime) {
        echo "<p>The current status is {$return->location} and the estimated delivery time is {$return->deliveryForecast}</p>";
    } else {
        echo "<p>The current status is {$return->location} the item was delivery in {$return->deliveryTime}</p>";
    }

}
