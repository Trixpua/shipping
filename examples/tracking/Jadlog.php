<?php

require __DIR__ . '/../../vendor/autoload.php';

use Trixpua\Shipping\Jadlog\Tracking\Jadlog;

$jadlog = new Jadlog('yourPassword', '00.000.000.0000-00');

//$jadlog->setData('10080155188760');
//$jadlog->setData('10087040452963');
//$jadlog->setData('10089640397546');
$jadlog->setData('18109001907268');

$jadlog->makeRequest();

$return = $jadlog->getResult();

var_dump($return);


if ($return->status === 'OK') {
    echo "<table>";
    echo "<tr><td><b>Documento: {$return->Numero}</b></td><td><b>CTe: {$return->Cte}</b></td><td><b>Última atualização: {$return->lastStatus}</b></td></tr>";
    echo "<tr><td><b>Data Entrega: {$return->DataHoraEntrega}</b></td><td><b>Recebedor: {$return->Recebedor}</b></td><td><b>Documento: {$return->Documento}</b></td></tr>";
    echo "<tr><th><b>Data Evento</b></th><th><b>Descricao</b></th><th><b>Observacao</b></th></tr>";
    foreach ($return->histories as $history) {
        echo "<tr>";
        echo "<td>{$history->DataHoraEvento}</td>";
        echo "<td>{$history->Descricao}</td>";
        echo "<td>{$history->Observacao}</td>";
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