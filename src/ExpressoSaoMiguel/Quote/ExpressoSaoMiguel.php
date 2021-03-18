<?php

namespace Trixpua\Shipping\ExpressoSaoMiguel\Quote;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

ini_set('max_execution_time', 0);

/**
 * Class ExpressoSaoMiguel
 * @author Elizandro Echer <https://github.com/Trixpua>
 * @package Trixpua\Shipping
 * @version 2.0.6
 */
class ExpressoSaoMiguel extends ExpressoSaoMiguelSetParameters
{

    public function makeRequest(): void
    {
        $this->login();

        $this->setPayerAndCities();
        if (!$this->payer) {
            $this->makeRequest();
            return;
        }
    }


    /**
     * Get for the payer and the cities options
     */
    private function setPayerAndCities(): void
    {
        try {

            $promise = $this->client->requestAsync('GET',
                'https://intranet2.expressosaomiguel.com.br/principal/index.xhtml')
                                    ->then(function ($response) {
                                        $doc = new \DOMDocument();
                                        $libxml_previous_state = libxml_use_internal_errors(true);
                                        $doc->loadHTML($response->getBody());
                                        libxml_use_internal_errors($libxml_previous_state);
                                        $xpath = new \DOMXpath($doc);
                                        $this->viewState = $xpath->query('//*[@name="javax.faces.ViewState"]')
                                                                 ->item(0)
                                                                 ->getAttribute('value');

                                        try {

                                            $headers = [
                                                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36",
                                                "Accept: application/xml, text/xml, */*; q=0.01",
                                                "Accept-Language: pt-BR,pt;q=0.9",
                                                "Accept-Encoding: ",
                                                "Connection: keep-alive",
                                                "Cache-Control: no-cache",
                                                "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
                                                "Host: intranet2.expressosaomiguel.com.br",
                                                "Origin: https://intranet2.expressosaomiguel.com.br",
                                                "Referer: https://intranet2.expressosaomiguel.com.br/principal/index.xhtml",
                                                "Faces-Request: partial/ajax",
                                                "Sec-Fetch-Dest: document",
                                                "Sec-Fetch-Mode: navigate",
                                                "Sec-Fetch-Site: same-origin",
                                                "Sec-Fetch-User: ?1",
                                                "Upgrade-Insecure-Requests: 1",
                                                "X-Requested-With: XMLHttpRequest"
                                            ];

                                            $parameters = [
                                                'menuform' => 'true',
                                                'menuform:menu-tudo' => 'menuform:menu-tudo',
                                                'menuform:menu-tudo_menuid' => '1_0',
                                                'javax.faces.ViewState' => $this->viewState
                                            ];

                                            $promise = $this->client->requestAsync('POST',
                                                'https://intranet2.expressosaomiguel.com.br/principal/index.xhtml', [
                                                    'form_params' => $parameters,
                                                    'headers' => $headers
                                                ])
                                                                    ->then(function ($response) {
                                                                        $doc = new \DOMDocument();
                                                                        $libxml_previous_state = libxml_use_internal_errors(true);
                                                                        $doc->loadHTML($response->getBody());
                                                                        libxml_use_internal_errors($libxml_previous_state);
                                                                        $xpath = new \DOMXpath($doc);
                                                                        $this->viewState = $xpath->query('//*[@name="javax.faces.ViewState"]')
                                                                                                 ->item(0)
                                                                                                 ->getAttribute('value');


                                                                        $this->payer = $xpath->query('//input[@name="form_cotacao:cliente_selecionado"]')
                                                                                             ->item(0)
                                                                                             ->getAttribute('value');

                                                                        $this->button = $xpath->query('//button')
                                                                                             ->item(5)
                                                                                             ->getAttribute('name');


                                                                        $originCitiesOptions = $xpath->query("//select[@name='form_cotacao:cidadeOrigem_input']/option");
                                                                        foreach ($originCitiesOptions as $originCityOption) {
                                                                            if ($originCityOption->getAttribute('value')) {
                                                                                $this->originCities[$originCityOption->getAttribute('value')] = $originCityOption->nodeValue;
                                                                            }
                                                                        }
                                                                        $this->setOriginCity();
                                                                        if (!in_array($this->originCity, $this->originCities)) {
                                                                            $this->result->status = 'ERROR';
                                                                            $this->result->errors[] = 'Cidade de origem não atendida';
                                                                            return;
                                                                        }


                                                                        $destinyCitiesOptions = $xpath->query("//select[@name='form_cotacao:cidadeDestino_input']/option");
                                                                        foreach ($destinyCitiesOptions as $destinyCityOption) {
                                                                            if ($destinyCityOption->getAttribute('value')) {
                                                                                $this->destinyCities[$destinyCityOption->getAttribute('value')] = $destinyCityOption->nodeValue;
                                                                            }
                                                                        }
                                                                        $this->setDestinyCity();
                                                                        if (!in_array($this->destinyCity, $this->destinyCities)) {
                                                                            $this->result->status = 'ERROR';
                                                                            $this->result->errors[] = 'Cidade de destino não atendida';
                                                                            return;
                                                                        }

                                                                        $this->getResponse();
                                                                    });
                                            $promise->wait();
                                        } catch (RequestException $e) {
                                            $this->result->status = 'ERROR';
                                            $this->result->errors[] = 'Curl Error: ' . $e->getMessage();
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
            $parameters = [
                'javax.faces.partial.ajax' => 'true',
                'javax.faces.source' => $this->button,
                'javax.faces.partial.execute' => '@all',
                'javax.faces.partial.render' => 'growlMensagem form_cotacao:pnlCotacao corpo_pagina',
                $this->button => $this->button,
                'form_cotacao' => 'form_cotacao',
                'form_cotacao:cliente_selecionado' => $this->payer,
                'form_cotacao:cidadeOrigem_focus' => '',
                'form_cotacao:cidadeOrigem_input' => array_search($this->originCity, $this->originCities),
                'form_cotacao:cliente_destinatario' => '',
                'form_cotacao:cidadeDestino_focus' => '',
                'form_cotacao:cidadeDestino_input' => array_search($this->destinyCity, $this->destinyCities),
                'form_cotacao:cliente_expedidor' => $this->payer,
                'form_cotacao:cliente_recebedor' => '',
                'form_cotacao:cliente_tomador' => $this->payer,
                'form_cotacao:valor_nota_input' => '',
                'form_cotacao:valor_nota_hinput' => $this->shippingInfo->getCommodityValue(),
                'form_cotacao:qtdd' => '1',
                'form_cotacao:peso_input' => '',
                'form_cotacao:peso_hinput' => $this->shippingInfo->getWeight(),
                'form_cotacao:metroCubicoTotal_input' => '',
                'form_cotacao:metroCubicoTotal_hinput' => $this->shippingInfo->getVolume(),
                'form_cotacao:alturaMedidas_input' => '0,000',
                'form_cotacao:alturaMedidas_hinput' => '0',
                'form_cotacao:larguraMedidas_input' => '0,000',
                'form_cotacao:larguraMedidas_hinput' => '0',
                'form_cotacao:profundidadeMedidas_input' => '0,000',
                'form_cotacao:profundidadeMedidas_hinput' => '0',
                'form_cotacao:volumesMedidas' => '0',
                'form_cotacao:gridMedidas_scrollState' => '0,0',
                'javax.faces.ViewState' => $this->viewState
            ];

            $promise = $this->client->requestAsync('POST',
                'https://intranet2.expressosaomiguel.com.br/principal/index.xhtml',
                [
                    'form_params' => $parameters,
                    'headers' => $this->mainHeader
                ])->then(function ($response) {
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
        $this->result->status = 'OK';
        $doc = new \DOMDocument();
        $libxml_previous_state = libxml_use_internal_errors(true);
        $doc->loadHTML($response->getBody());
        libxml_use_internal_errors($libxml_previous_state);
        $xpath = new \DOMXpath($doc);

        $this->result->shippingType = $xpath->query('.//table[2] //tr[3] //td[4]')->item(0)->nodeValue;
        $this->result->originBranch = $xpath->query('.//table[3] //tr[1] //td[1] //span[2]')->item(0)->nodeValue;
        $this->result->destinyBranch = $xpath->query('.//table[3] //tr[1] //td[2] //span[2]')->item(0)->nodeValue;
        $this->result->senderName = $xpath->query('.//table[3] //tr[3] //td[1] //tr[1] //td[2]')->item(0)->nodeValue;
        $this->result->senderTaxID = $xpath->query('.//table[3] //tr[3] //td[1] //tr[2] //td[2]')->item(0)->nodeValue;
        $this->result->senderCity = $xpath->query('.//table[3] //tr[3] //td[1] //tr[3] //td[2]')->item(0)->nodeValue;

        $this->result->receiverCity = $xpath->query('.//table[3] //tr[3] //td[2] //tr[3] //td[2]')->item(0)->nodeValue;

        $this->result->declaredValue = (float)str_replace(['.',','], ['','.'], preg_replace('/[^0-9,]/', '', $xpath->query('.//table[3] //tr[6] //td[1]')->item(0)->nodeValue));
        $this->result->totalVolumes =  (int)str_replace(['.',','], ['','.'], preg_replace('/[^0-9,]/', '', $xpath->query('.//table[3] //tr[6] //td[2]')->item(0)->nodeValue));
        $this->result->weight =  (float)str_replace(['.',','], ['','.'], preg_replace('/[^0-9,]/', '', $xpath->query('.//table[3] //tr[6] //td[3]')->item(0)->nodeValue));
        $this->result->cubic =  (float)str_replace(['.',','], ['','.'], preg_replace('/[^0-9,]/', '', $xpath->query('.//table[3] //tr[6] //td[4]')->item(0)->nodeValue));
        $this->result->cubicWeight =  (float)str_replace(['.',','], ['','.'], preg_replace('/[^0-9,]/', '', $xpath->query('.//table[3] //tr[6] //td[5]')->item(0)->nodeValue));

        $this->result->parcels = new \stdClass();
        $this->result->parcels->tde =  (float)str_replace(['.',','], ['','.'], preg_replace('/[^0-9,]/', '', $xpath->query('.//table[3] //tr[7] //td[1]')->item(0)->nodeValue));
        $this->result->parcels->tda =  (float)str_replace(['.',','], ['','.'], preg_replace('/[^0-9,]/', '', $xpath->query('.//table[3] //tr[7] //td[2]')->item(0)->nodeValue));
        $this->result->parcels->redispatch =  (float)str_replace(['.',','], ['','.'], preg_replace('/[^0-9,]/', '', $xpath->query('.//table[3] //tr[7] //td[3]')->item(0)->nodeValue));
        $this->result->parcels->cpfFee =  (float)str_replace(['.',','], ['','.'], preg_replace('/[^0-9,]/', '', $xpath->query('.//table[3] //tr[7] //td[4]')->item(0)->nodeValue));

        $this->result->shippingCost = number_format((((float)str_replace(['.',','], ['','.'], preg_replace('/[^0-9,]/', '', $xpath->query('.//table[4] //tr[1] //td[1]')->item(0)->nodeValue))+ $this->shippingInfo->getAdditionalCharge()) / (1 - ($this->shippingInfo->getAdditionalPercent() / 100))), 2, '.', '');

        $this->result->deliveryTime = $this->setDeliveryTime($this->stringBetween($xpath->query('.//table[4] //tr[2] //td[1]')->item(0)->nodeValue, 'Entrega prevista no dia: ', ' '));

    }

    /**
     * Set the delivery time
     * @param string $deliveryForecast
     * @throws \Exception
     */
    private function setDeliveryTime(string $deliveryForecast): int
    {
        if ($deliveryForecast) {
            $shippingDate = new \DateTime(\DateTime::createFromFormat('d/m/Y', date('d/m/Y'))->format('d-m-Y'));
            $deliveryDate = new \DateTime(\DateTime::createFromFormat('d/m/Y', $deliveryForecast)->format('d-m-Y'));
            $deliveryDays = $deliveryDate->diff($shippingDate)->format('%a');

            $workingDays = 0;
            for ($i = 0; $i < $deliveryDays; $i++) {
                $shippingDate->modify('+1 day');
                if ((int)$shippingDate->format('w') != 0 && (int)$shippingDate->format('w') != 6) {
                    $workingDays++;
                }
            }
             return $workingDays + $this->shippingInfo->getShipmentDelay();
        }
    }

    /**
     * @param string $string Full string to search
     * @param string $start Initial part of string
     * @param string $end Final part of string
     * @return string
     */
    private function stringBetween(string $string, string $start, string $end): string
    {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) {
            return '';
        }
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }
}