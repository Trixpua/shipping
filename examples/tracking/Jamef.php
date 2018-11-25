<?php

require __DIR__ . '/../../vendor/autoload.php';

use Trixpua\Shipping\Jamef\Tracking\Jamef;

$jamef = new Jamef('00.000.000.0000-00');

$jamef->setData('00.000.000.0000-00', '4633');

$jamef->makeRequest();

$return = $jamef->getResult();

var_dump($return);

if ($return->status === 'OK') {
        echo "<table>";
        echo "<tr><td><b>NF: {$return->nf}</b></td><td><b>CTRC: {$return->ctrc}</b></td></tr>";
        echo "<tr><td><b>Origem: {$return->cliorig} - {$return->munorig} / {$return->uforig}</b></td><td><b>Destino: {$return->clidest} - {$return->mundest} / {$return->ufdest}</b></td></tr>";
        echo "<tr><th><b>Status</b></th><th><b>Atualização</b></th><th><b>Origem</b></th><th><b>Destino</b></th></tr>";
        foreach ($return->histories as $history) {
            echo "<tr>";
            echo "<td>{$history->status}</td>";
            echo "<td>{$history->dtatualiz}</td>";
            echo "<td>{$history->munlocl}  {$history->uflocl}</td>";
            echo "<td>{$history->mundestmanf}  {$history->ufdestmanf}</td>";
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