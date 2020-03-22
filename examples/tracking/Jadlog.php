<?php

require __DIR__ . '/../../vendor/autoload.php';

use Trixpua\Shipping\Jadlog\Tracking\Jadlog;

$jadlog = new Jadlog('yourPassword', '00.000.000.0000-00');


//$Jadlog->setData('10080155188760');
//$Jadlog->setData('10087040452963');
//$Jadlog->setData('10089640397546');
$jadlog->setData('18109001907268');

$jadlog->makeRequest();

$return = $jadlog->getResult();

var_dump($return);


if ($return->status === 'OK') {
    echo "<table>";
    echo "<tr><td><b>Tracking Number: {$return->number}</b></td><td><b>CTe: {$return->cteNumber}</b></td><td><b>Last Status: {$return->lastStatus}</b></td></tr>";
    echo "<tr><td><b>Delivery Time: {$return->deliveryTime}</b></td><td><b>Receiver: {$return->receiver}</b></td><td><b>Document: {$return->document}</b></td></tr>";
    echo "<tr><th><b>History Time</b></th><th><b>Description</b></th><th><b>Remarks</b></th></tr>";
    foreach ($return->histories as $history) {
        echo "<tr>";
        echo "<td>{$history->historyTime}</td>";
        echo "<td>{$history->description}</td>";
        echo "<td>{$history->remarks}</td>";
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