<?php

require __DIR__ . '/../../vendor/autoload.php';

use Trixpua\Shipping\Correios\Tracking\Correios;

$correios = new Correios();

//$correios->setData('LB370015668SE');
//$correios->setData('PM839201376BR');
$correios->setData(['LB370015668SE', 'PM839201376BR', 'PM002309934BR']);
//$correios->setData(['PM002309903BR', 'PM002309934BR'], 'F', 'U');

$correios->makeRequest();

$return = $correios->getResult();

var_dump($return);


if ($return->status === 'OK') {
foreach ($return->objects as $object) {
    if($object->status === 'ERROR'){
        echo "<table>";
        echo "<tr><td><b>{$object->number}</b></td><td><b>{$object->error}</b></td></tr>";
        echo "</table><br>";
        continue;
    }

    echo "<table>";
    echo "<tr><td><b>{$object->number}</b></td><td><b>{$object->category}</b></td></tr>";
    foreach ($object->events as $event) {
        echo "<tr>";
        echo "<td>{$event->date} {$event->hour}<br><small>{$event->city} / {$event->state} ({$event->originBranch->local})</small></td>";
        echo "<td>{$event->description}";
        if ($event->detail) {
            echo "<br><small>{$event->detail}</small>";
        }
        echo "</td></tr>";
    }
    echo "</table><br>";
}
}










?>

<style>
    table {
        width: 30%
    }

    table, th, td {
        border: 1px solid black;
        border-collapse: collapse;
    }

    th, td {
        padding: 15px;
    }

    th {
        text-align: left;
    }
</style>
