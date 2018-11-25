<?php

require __DIR__ . '/../../vendor/autoload.php';

use Trixpua\Shipping\Correios\Tracking\Correios;

$correios = new Correios();

$correios->setData(['PM002309903BR', 'PM002309934BR']);
//$correios->setData('PM002309903BR');
//$correios->setData(['PM002309903BR', 'PM002309934BR'], 'F', 'U');

$correios->makeRequest();

$return = $correios->getResult();

var_dump($return);
var_dump($return->objects->{0}->histories);

if ($return->status === 'OK') {
    foreach ($return->objects as $object) {
        echo "<table>";
        echo "<tr><td><b>$object->numero</b></td><td><b>$object->categoria</b></td></tr>";
        foreach ($object->histories as $history) {
            echo "<tr>";
            echo "<td>$history->data $history->hora<br><small>$history->cidade / $history->uf ($history->local)</small></td>";
            echo "<td>$history->descricao";
            if ($history->detalhe) {
                echo "<br><small>$history->detalhe</small>";
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
