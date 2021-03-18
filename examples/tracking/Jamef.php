<?php

require __DIR__ . '/../../vendor/autoload.php';

use Trixpua\Shipping\Jamef\Tracking\Jamef;

$jamef = new Jamef('yourUsername', 'yourPassword', '00.000.000.0000-00');

$jamef->setData('8265');

$jamef->makeRequest();

$return = $jamef->getResult();

var_dump($return);

if ($return->status === 'OK') {
    foreach ($return->billsOfLading as $billOfLading){
        echo "<table>";
        echo "<tr><td><b>Invoice Number: {$billOfLading->invoiceNumber}</b></td><td><b>Bill Of Lading Number: {$billOfLading->eBillOfLadingNumber}</b></td><td><b>Delivery Forecast: {$billOfLading->deliveryForecast}</b></td></tr>";
        echo "<tr><td><b>Delivery Receipt Link: <a href='{$billOfLading->deliveryReceiptLink}' target='_blank'>{$billOfLading->deliveryReceiptLink}</a></b></td></tr>";
        echo "<tr><td><b>Origin: {$billOfLading->originClient} - {$billOfLading->originCity} / {$billOfLading->originState}</b></td><td><b>Destiny: {$billOfLading->destinyClient} - {$billOfLading->destinyCity} / {$billOfLading->destinyState}</b></td></tr>";
        echo "<tr><th><b>Status</b></th><th><b>Update</b></th><th><b>Manifest Number</b></th><th><b>Origin</b></th><th><b>Destiny</b></th></tr>";
        foreach ($billOfLading->events as $event) {
            echo "<tr>";
            echo "<td>{$event->status}</td>";
            echo "<td>{$event->updateDate}</td>";
            echo "<td>{$event->manifestNumber}</td>";
            echo "<td>{$event->originCity}  {$event->originState}</td>";
            echo "<td>{$event->destinyCity}  {$event->destinyState}</td>";
            echo "</tr>";
        }
        echo "</table>";
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