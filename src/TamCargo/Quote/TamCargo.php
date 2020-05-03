<?php

namespace Trixpua\Shipping\TamCargo\Quote;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

ini_set('max_execution_time', 0);

/**
 * Class TamCargo
 * @author Elizandro Echer <https://github.com/Trixpua>
 * @package Trixpua\Shipping
 * @version 2.0.2
 */
class TamCargo extends TamCargoSetParameters
{

    /**
     * Make the request to the web service
     */
    public function makeRequest(): void
    {

        $this->setQuoteWeight();
        $this->login();
        if (!$this->viewStateLogin) {
            $this->makeRequest();
            return;
        }

        $this->setPayer();
        if (!$this->payer) {
            $this->makeRequest();
            return;
        }

        $this->defineOriginAirport();
        $this->defineDestinyAirport();



        if ($this->result->status === 'ERROR') {
            return;
        }
        $this->definePayer();

        $this->getResponse();
    }

    /**
     * Define the origin airport with the result obtained previously
     */
    private function defineOriginAirport(): void
    {
        try {
            $parameters = [
                'javax.faces.partial.ajax' => 'true',
                'javax.faces.source' => 'form:originId',
                'javax.faces.partial.execute' => 'form:originId',
                'javax.faces.behavior.event' => 'blur',
                'javax.faces.partial.event' => 'blur',
                'form' => 'form',
                'form:originId_input' => $this->originAirport,
                'form:j_idt84_input' => 'ALL',
                'form:j_idt98_input' => 'TAM',
                'form:j_idt110' => 'P',
                'form:accordionDC_active' => '0',
                'form:table_dim_scrollState' => '0,0',
                'javax.faces.ViewState' => $this->viewStateLogin,
            ];

            $promise = $this->client->requestAsync('POST',
                'https://mycargomanager.appslatam.com/eBusiness-web-1.0-view/private/CreateQuotation.jsf',
                [
                    'form_params' => $parameters,
                    'headers' => $this->mainHeader
                ])->then(function() {
                $this->defineCollect();
            });
            $promise->wait();
        } catch (RequestException $e) {
            $this->result->status = 'ERROR';
            $this->result->errors[] = 'Curl Error: ' . $e->getMessage();
        }

    }

    /**
     * Define if commodity will be collected in origin ZIP code
     */
    private function defineCollect(): void
    {
        try {
            $parameters = [
                'javax.faces.partial.ajax' => 'true',
                'javax.faces.source' => 'form:j_idt30',
                'javax.faces.partial.execute' => 'form:j_idt30',
                'javax.faces.partial.render' => 'form:collectCepId',
                'javax.faces.behavior.event' => 'valueChange',
                'javax.faces.partial.event' => 'change',
                'form' => 'form',
                'form:originId_input' => $this->originAirport,
                'form:j_idt30' => $this->collect,
                'form:j_idt84_input' => 'ALL',
                'form:j_idt98_input' => 'TAM',
                'form:j_idt110' => 'P',
                'form:accordionDC_active' => '0',
                'form:table_dim_scrollState' => '0,0',
                'javax.faces.ViewState' => $this->viewStateLogin,
            ];

            $promise = $this->client->requestAsync('POST',
                'https://mycargomanager.appslatam.com/eBusiness-web-1.0-view/private/CreateQuotation.jsf',
                [
                    'form_params' => $parameters,
                    'headers' => $this->mainHeader
                ])->then(function() {
                $this->setDestinyAirport();
            });
            $promise->wait();
        } catch (RequestException $e) {
            $this->result->status = 'ERROR';
            $this->result->errors[] = 'Curl Error: ' . $e->getMessage();
        }
    }

    /**
     * Define the destiny airport with the result obtained previously
     */
    private function defineDestinyAirport(): void
    {
        try {
            $parameters = [
                'javax.faces.partial.ajax' => 'true',
                'javax.faces.source' => 'form:destinationId',
                'javax.faces.partial.execute' => 'form:destinationId',
                'javax.faces.behavior.event' => 'blur',
                'javax.faces.partial.event' => 'blur',
                'form' => 'form',
                'form:originId_input' => $this->originAirport,
                'form:j_idt30' => $this->collect,
                'form:collectCepId' => $this->senderZipCode,
                'form:destinationId_input' => $this->destinyAirport,
                'form:j_idt84_input' => 'ALL',
                'form:j_idt98_input' => 'TAM',
                'form:j_idt110' => 'P',
                'form:accordionDC_active' => '0',
                'form:table_dim_scrollState' => '0,0',
                'javax.faces.ViewState' => $this->viewStateLogin,
            ];

            $promise = $this->client->requestAsync('POST',
                'https://mycargomanager.appslatam.com/eBusiness-web-1.0-view/private/CreateQuotation.jsf',
                [
                    'form_params' => $parameters,
                    'headers' => $this->mainHeader
                ])->then(function() {
                $this->defineDelivery();
            });
            $promise->wait();
        } catch (RequestException $e) {
            $this->result->status = 'ERROR';
            $this->result->errors[] = 'Curl Error: ' . $e->getMessage();
        }
    }

    /**
     * Define if commodity will be delivered in destiny ZIP code
     */
    private function defineDelivery(): void
    {
        try {
            $parameters = [
                'javax.faces.partial.ajax' => 'true',
                'javax.faces.source' => 'form:j_idt44',
                'javax.faces.partial.execute' => 'form:j_idt44',
                'javax.faces.partial.render' => 'form:deliveryCepId',
                'javax.faces.behavior.event' => 'valueChange',
                'javax.faces.partial.event' => 'change',
                'form' => 'form',
                'form:originId_input' => $this->originAirport,
                'form:j_idt30' => $this->collect,
                'form:collectCepId' => $this->senderZipCode,
                'form:destinationId_input' => $this->destinyAirport,
                'form:j_idt44' => $this->delivery,
                'form:j_idt84_input' => 'ALL',
                'form:j_idt98_input' => 'TAM',
                'form:j_idt110' => 'P',
                'form:accordionDC_active' => '0',
                'form:table_dim_scrollState' => '0,0',
                'javax.faces.ViewState' => $this->viewStateLogin,
            ];

            $promise = $this->client->requestAsync('POST',
                'https://mycargomanager.appslatam.com/eBusiness-web-1.0-view/private/CreateQuotation.jsf',
                [
                    'form_params' => $parameters,
                    'headers' => $this->mainHeader
                ])->then(function() {
                $this->openMeasuresModal();
            });
            $promise->wait();
        } catch (RequestException $e) {
            $this->result->status = 'ERROR';
            $this->result->errors[] = 'Curl Error: ' . $e->getMessage();
        }
    }

    /**
     * Open the measures modal to define the volume and weight
     */
    private function openMeasuresModal()
    {
        try {
            $parameters = [
                'javax.faces.partial.ajax' => 'true',
                'javax.faces.source' => 'form:btnPiecesDetail',
                'javax.faces.partial.execute' => '@all',
                'form:btnPiecesDetail' => 'form:btnPiecesDetail',
                'form' => 'form',
                'form:originId_input' => $this->originAirport,
                'form:j_idt30' => $this->collect,
                'form:collectCepId' => $this->senderZipCode,
                'form:destinationId_input' => $this->destinyAirport,
                'form:j_idt44' => $this->delivery,
                'form:deliveryCepId' => $this->shippingInfo->getReceiverZipCode(),
                'form:j_idt60_input' => '1',
                'form:idPackingType_input' => '21',
                'form:j_idt84_input' => 'ALL',
                'form:j_idt98_input' => 'TAM',
                'form:j_idt110' => 'P',
                'form:accordionDC_active' => '0',
                'form:table_dim_scrollState' => '0,0',
                'javax.faces.ViewState' => $this->viewStateLogin,
            ];

            $promise = $this->client->requestAsync('POST',
                'https://mycargomanager.appslatam.com/eBusiness-web-1.0-view/private/CreateQuotation.jsf',
                [
                    'form_params' => $parameters,
                    'headers' => $this->mainHeader
                ])->then(function() {
                $this->defineWeight();
            });
            $promise->wait();
        } catch (RequestException $e) {
            $this->result->status = 'ERROR';
            $this->result->errors[] = 'Curl Error: ' . $e->getMessage();
        }

    }

    /**
     * Define the total weight of the commodity
     */
    private function defineWeight(): void
    {
        try {
            $parameters = [
                'javax.faces.partial.ajax' => 'true',
                'javax.faces.source' => 'form:j_idt221',
                'javax.faces.partial.execute' => '@all',
                'javax.faces.partial.render' => 'form:table_dim form:panel_form',
                'form:j_idt221' => 'form:j_idt221',
                'form' => 'form',
                'form:originId_input' => $this->originAirport,
                'form:j_idt30' => $this->collect,
                'form:collectCepId' => $this->senderZipCode,
                'form:destinationId_input' => $this->destinyAirport,
                'form:j_idt44' => $this->delivery,
                'form:deliveryCepId' => $this->shippingInfo->getReceiverZipCode(),
                'form:j_idt60_input' => '1',
                'form:idPackingType_input' => '21',
                'form:j_idt84_input' => 'ALL',
                'form:j_idt98_input' => 'TAM',
                'form:j_idt110' => 'P',
                'form:accordionDC_active' => '0',
                'form:idPackType_input' => '21',
                'form:idMeasures_input' => '100',
                'form:length' => '20',
                'form:width' => '20',
                'form:height' => '10',
                'form:quantity' => '1',
                'form:weight' => strval($this->weight),
                'form:table_dim_scrollState' => '0,0',
                'javax.faces.ViewState' => $this->viewStateLogin,
            ];

            $promise = $this->client->requestAsync('POST',
                'https://mycargomanager.appslatam.com/eBusiness-web-1.0-view/private/CreateQuotation.jsf',
                [
                    'form_params' => $parameters,
                    'headers' => $this->mainHeader
                ])->then(function() {
                $this->addDefinedVolume();
            });
            $promise->wait();
        } catch (RequestException $e) {
            $this->result->status = 'ERROR';
            $this->result->errors[] = 'Curl Error: ' . $e->getMessage();
        }
    }


    /**
     * Add the volume defined previously
     */
    private function addDefinedVolume()
    {
        try {
            $parameters = [
                'javax.faces.partial.ajax' => 'true',
                'javax.faces.source' => 'form:j_idt277',
                'javax.faces.partial.execute' => '@all',
                'form:j_idt277' => 'form:j_idt277',
                'form' => 'form',
                'form:originId_input' => $this->originAirport,
                'form:j_idt30' => $this->collect,
                'form:collectCepId' => $this->senderZipCode,
                'form:destinationId_input' => $this->destinyAirport,
                'form:j_idt44' => $this->delivery,
                'form:deliveryCepId' => $this->shippingInfo->getReceiverZipCode(),
                'form:j_idt60_input' => '1',
                'form:idPackingType_input' => '21',
                'form:j_idt84_input' => 'ALL',
                'form:j_idt98_input' => 'TAM',
                'form:j_idt110' => 'P',
                'form:accordionDC_active' => '0',
                'form:idMeasures_input' => '100',
                'form:table_dim_scrollState' => '0,0',
                'javax.faces.ViewState' => $this->viewStateLogin,
            ];

            $promise = $this->client->requestAsync('POST',
                'https://mycargomanager.appslatam.com/eBusiness-web-1.0-view/private/CreateQuotation.jsf',
                [
                    'form_params' => $parameters,
                    'headers' => $this->mainHeader
                ])->then(function() {
                $this->closeMeasuresModal();
            });
            $promise->wait();
        } catch (RequestException $e) {
            $this->result->status = 'ERROR';
            $this->result->errors[] = 'Curl Error: ' . $e->getMessage();
        }
    }

    /**
     * Close the measures modal and define the volume and weight
     */
    private function closeMeasuresModal()
    {
        try {
            $parameters = [
                'javax.faces.partial.ajax' => 'true',
                'javax.faces.source' => 'form:dlgPieces',
                'javax.faces.partial.execute' => 'form:dlgPieces',
                'javax.faces.behavior.event' => 'close',
                'javax.faces.partial.event' => 'close',
                'form' => 'form',
                'form:originId_input' => $this->originAirport,
                'form:j_idt30' => $this->collect,
                'form:collectCepId' => $this->senderZipCode,
                'form:destinationId_input' => $this->destinyAirport,
                'form:j_idt44' => $this->delivery,
                'form:deliveryCepId' => $this->shippingInfo->getReceiverZipCode(),
                'form:j_idt60_input' => '1',
                'form:idPackingType_input' => '21',
                'form:j_idt84_input' => 'ALL',
                'form:j_idt98_input' => 'TAM',
                'form:j_idt110' => 'P',
                'form:accordionDC_active' => '0',
                'form:idMeasures_input' => '100',
                'form:table_dim_scrollState' => '0,0',
                'javax.faces.ViewState' => $this->viewStateLogin,
            ];

            $promise = $this->client->requestAsync('POST',
                'https://mycargomanager.appslatam.com/eBusiness-web-1.0-view/private/CreateQuotation.jsf',
                [
                    'form_params' => $parameters,
                    'headers' => $this->mainHeader
                ])->then(function() {
                if ($this->insurance === 'SIN') {
                    $this->defineInsurance();
                }
            });
            $promise->wait();
        } catch (RequestException $e) {
            $this->result->status = 'ERROR';
            $this->result->errors[] = 'Curl Error: ' . $e->getMessage();
        }
    }

    /**
     * Define if will use the insurance service
     */
    private function defineInsurance(): void
    {
        try {
            $parameters = [
                'javax.faces.partial.ajax' => 'true',
                'javax.faces.source' => 'form:j_idt98',
                'javax.faces.partial.execute' => 'form:j_idt98',
                'javax.faces.partial.render' => 'form:declaredValueId',
                'javax.faces.behavior.event' => 'change',
                'javax.faces.partial.event' => 'change',
                'form' => 'form',
                'form:originId_input' => $this->originAirport,
                'form:j_idt30' => $this->collect,
                'form:collectCepId' => $this->senderZipCode,
                'form:destinationId_input' => $this->destinyAirport,
                'form:j_idt44' => $this->delivery,
                'form:deliveryCepId' => $this->shippingInfo->getReceiverZipCode(),
                'form:j_idt60_input' => '1',
                'form:idPackingType_input' => '21',
                'form:j_idt84_input' => 'ALL',
                'form:j_idt98_input' => $this->insurance,
                'form:j_idt110' => 'P',
                'form:accordionDC_active' => '0',
                'form:idMeasures_input' => '100',
                'form:table_dim_scrollState' => '0,0',
                'javax.faces.ViewState' => $this->viewStateLogin,
            ];

            $promise = $this->client->requestAsync('POST',
                'https://mycargomanager.appslatam.com/eBusiness-web-1.0-view/private/CreateQuotation.jsf',
                [
                    'form_params' => $parameters,
                    'headers' => $this->mainHeader
                ]);
            $promise->wait();
        } catch (RequestException $e) {
            $this->result->status = 'ERROR';
            $this->result->errors[] = 'Curl Error: ' . $e->getMessage();
        }
    }

    /**
     * Define the payer and finish the request
     */
    private function definePayer(): void
    {
        try {
            $parameters = [
                'javax.faces.partial.ajax' => 'true',
                'javax.faces.source' => 'form:btnCotizar',
                'javax.faces.partial.execute' => '@all',
                'form:btnCotizar' => 'form:btnCotizar',
                'form' => 'form',
                'form:originId_input' => $this->originAirport,
                'form:j_idt30' => $this->collect,
                'form:collectCepId' => $this->senderZipCode,
                'form:destinationId_input' => $this->destinyAirport,
                'form:j_idt44' => $this->delivery,
                'form:deliveryCepId' => $this->shippingInfo->getReceiverZipCode(),
                'form:j_idt60_input' => '1',
                'form:idPackingType_input' => '21',
                'form:j_idt84_input' => 'ALL',
                'form:j_idt98_input' => $this->insurance,
                'form:declaredValueId' => strval($this->shippingInfo->getCommodityValue()),
                'form:j_idt110' => 'P',
                'form:j_idt123_input' => $this->payer,
                'form:accordionDC_active' => '0',
                'form:idMeasures_input' => '100',
                'form:table_dim_scrollState' => '0,0',
                'javax.faces.ViewState' => $this->viewStateLogin,
            ];

            $promise = $this->client->requestAsync('POST',
                'https://mycargomanager.appslatam.com/eBusiness-web-1.0-view/private/CreateQuotation.jsf',
                [
                    'form_params' => $parameters,
                    'headers' => $this->mainHeader
                ])->then(function($response) {

                if (!$response->getHeader('Set-Cookie')) {
                    $this->makeRequest();
                    return;
                }
            });
            $promise->wait();
        } catch (RequestException $e) {
            $this->result->status = 'ERROR';
            $this->result->errors[] = 'Curl Error: ' . $e->getMessage();
        }
    }

    /**
     * Get the response from request
     */
    private function getResponse(): void
    {
        try {
            $headers = [
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36",
                "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",
                "Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7",
                "Accept-Encoding: ",
                "Connection: keep-alive",
                "Cache-Control: no-cache",
                "Pragma: no-cache",
                "Host: mycargomanager.appslatam.com",
                "Referer: https://mycargomanager.appslatam.com/eBusiness-web-1.0-view/private/CreateQuotation.jsf",
                "Upgrade-Insecure-Requests: 1",
            ];

            $promise = $this->client->requestAsync('POST',
                'https://mycargomanager.appslatam.com/eBusiness-web-1.0-view/private/DisplayQuotation.jsf',
                [
                    'headers' => $headers
                ])->then(function($response) {

                $this->parseResult($response);
            });
            $promise->wait();
        } catch (RequestException $e) {
            $this->result->status = 'ERROR';
            $this->result->errors[] = 'Curl Error: ' . $e->getMessage();
        }
    }

    /**
     * Parse the response from the webservice and set the result
     * @param Response $response
     */
    private function parseResult(Response $response): void
    {
        $this->resultReset();
        $this->result->status = 'OK';
        $doc = new \DOMDocument();
        $libxml_previous_state = libxml_use_internal_errors(true);
        $doc->loadHTML($response->getBody());
        libxml_use_internal_errors($libxml_previous_state);
        $xpath = new \DOMXpath($doc);

        $redispatchTime = '';
        if ($xpath->query('//*[@id="form:j_idt83"]')->item(0)) {
            $redispatchTime = intval($xpath->query('//*[@id="form:j_idt83"]')->item(0)->getAttribute('value'));
        }

        $deliveryTime = ['CONVENCIONAL' => '3', 'PROXIMO DIA' => '2', 'PROXIMO VOO' => '1', 'PREPAGO' => '2'];
        $this->result->modals = new \stdClass();
        $trs = $xpath->query('//*[@id="form:quotationTableId_data"]/tr');
        foreach ($trs as $modkey => $tr) {
            $this->result->modals->{$modkey} = new \stdClass();
            $this->result->modals->{$modkey}->status = '';
            $this->result->modals->{$modkey}->modal = $tr->childNodes->item(0)->nodeValue;
            $this->result->modals->{$modkey}->shippingCost = number_format((($tr->childNodes->item(7)->nodeValue + $this->shippingInfo->getAdditionalCharge()) / (1 - ($this->shippingInfo->getAdditionalPercent() / 100))), 2, '.', '');
            $this->result->modals->{$modkey}->deliveryTime = $deliveryTime[$tr->childNodes->item(0)->nodeValue] + $redispatchTime + $this->shippingInfo->getShipmentDelay();
            $this->result->modals->{$modkey}->originAirport = $this->originAirport;
            $this->result->modals->{$modkey}->destinyAirport = $this->destinyAirport;
            $this->result->modals->{$modkey}->redispatch = $redispatchTime ? true : false;
            $this->result->modals->{$modkey}->parcels = new \stdClass();
            $this->result->modals->{$modkey}->parcels->shipping = $tr->childNodes->item(1)->nodeValue;
            $this->result->modals->{$modkey}->parcels->rate = $tr->childNodes->item(2)->nodeValue;
            $this->result->modals->{$modkey}->parcels->collect = $tr->childNodes->item(3)->nodeValue;
            $this->result->modals->{$modkey}->parcels->delivery = $tr->childNodes->item(4)->nodeValue;
            $this->result->modals->{$modkey}->parcels->others = $tr->childNodes->item(5)->nodeValue;
            $this->result->modals->{$modkey}->parcels->taxes = $tr->childNodes->item(6)->nodeValue;
        }
        $this->setModalStatus($this->result->modals);

    }

    /**
     * Reset the result if not empty
     */
    private function resultReset(): void
    {
        foreach ($this->result as $item => $value) {
            unset($this->result->{$item});
        }
    }

    /**
     * Set status for each modal
     * @param \stdClass $modals
     */
    private function setModalStatus(\stdClass $modals): void
    {
        foreach ($modals as $key => $modal) {
            if (floatval($modal->parcels->shipping)) {
                $this->result->modals->{$key}->status = 'OK';
            } else {
                $this->result->modals->{$key}->status = 'ERROR';
                $this->result->modals->{$key}->errors[] = 'Não foi possível obter valor para esta modalidade de envio!';
            }
        }
    }
}