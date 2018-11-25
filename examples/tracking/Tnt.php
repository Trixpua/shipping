<?php

require __DIR__ . '/../../vendor/autoload.php';

use Trixpua\Shipping\Tnt\Tracking\Tnt;

$tnt = new Tnt('yourlogin@email.com', '00.000.000.0000-00');

$tnt->setData(4541);

$tnt->makeRequest();

$return = $tnt->getResult();

var_dump($return);

if ($return->status === 'OK') {
    if (!$return->dataEntrega) {
        echo "<p>The current status is {$return->localizacao} and the estimated delivery time is {$return->previsaoEntrega}</p>";
    }else {
        echo "<p>The current status is {$return->localizacao} the item was delivery in {$return->dataEntrega}</p>";
    }

}
