<?php

require __DIR__ . '/../../vendor/autoload.php';

use Trixpua\Shipping\Jamef\Tracking\Jamef;

$jamef = new Jamef('00.000.000.0000-00');

$jamef->setData('00.000.000.0000-00', '7823');

$jamef->makeRequest();

$return = $jamef->getResult();

var_dump($return);

if ($return->status === 'OK') {
        echo "<table>";
        echo "<tr><td><b>NFe: {$return->nfe}</b></td><td><b>CTRC: {$return->ctrc}</b></td></tr>";
        echo "<tr><td><b>Origin: {$return->originClient} - {$return->originCity} / {$return->originState}</b></td><td><b>Destiny: {$return->destinyClient} - {$return->destinyCity} / {$return->destinyState}</b></td></tr>";
        echo "<tr><th><b>Status</b></th><th><b>Update</b></th><th><b>Origin</b></th><th><b>Destiny</b></th></tr>";
        foreach ($return->histories as $history) {
            echo "<tr>";
            echo "<td>{$history->status}</td>";
            echo "<td>{$history->updateDate}</td>";
            echo "<td>{$history->locatedCity}  {$history->locatedState}</td>";
            echo "<td>{$history->manifestCity}  {$history->manifestState}</td>";
            echo "</tr>";
        }
        echo "</table>";
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