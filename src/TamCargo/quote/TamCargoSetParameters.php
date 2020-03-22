<?php

namespace Trixpua\Shipping\TamCargo\Quote;

use GuzzleHttp\Exception\RequestException;
use Trixpua\Shipping\ShippingInfo;

ini_set('max_execution_time', 0);

/**
 * Class TamCargo
 * @author Elizandro Echer <https://github.com/Trixpua>
 * @package Trixpua\Shipping
 * @version 2.0.0
 */
class TamCargoSetParameters extends TamCargoAuth
{

    /** @var array */
    protected $mainHeader;

    /** @var string */
    protected $senderZipCode;

    /** @var string */
    protected $originAirport;

    /** @var string */
    protected $destinyAirport;

    /** @var string */
    protected $payer;

    /** @var string|bool */
    protected $insurance;

    /** @var string|bool */
    protected $collect;

    /** @var string|bool */
    protected $delivery;

    /** @var int|float|string */
    protected $weight;

    /** @var ShippingInfo */
    protected $shippingInfo;

    /**
     * TamCargo constructor.
     * @param string $senderZipCode Define the sender ZIP code
     * @param string $login Define the login registered to access TamCargo services
     * @param string $password Define the password registered to access TamCargo services
     */
    public function __construct(string $senderZipCode, string $login, string $password)
    {
        parent::__construct($login, $password);
        $this->setSenderZipCode($senderZipCode);

        $this->mainHeader = [
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36",
            "Accept: application/xml, text/xml, */*; q=0.01",
            "Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7",
            "Accept-Encoding: ",
            "Connection: keep-alive",
            "Cache-Control: max-age=0",
            "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
            "Host: mycargomanager.appslatam.com",
            "Origin: https://mycargomanager.appslatam.com",
            "Referer: https://mycargomanager.appslatam.com/eBusiness-web-1.0-view/private/CreateQuotation.jsf",
            "Faces-Request: partial/ajax",
            "X-Requested-With: XMLHttpRequest",
            "Upgrade-Insecure-Requests: 1",
        ];
    }

    /**
     * @param string $senderZipCode Define the sender ZIP code
     */
    public function setSenderZipCode(string $senderZipCode): void
    {
        $this->senderZipCode = preg_replace('/[^0-9]/', '', $senderZipCode);
    }


    /**
     * @param ShippingInfo $shippingInfo
     * @param bool|string $insurance OPTIONAL (DEFAULT false) - Define if need insurance service
     * @param bool|string $collect OPTIONAL (DEFAULT false) - Define if need collect service
     * @param bool|string $delivery OPTIONAL (DEFAULT false) - Define if need home delivery service
     */
    public function setData(
        ShippingInfo $shippingInfo,
        $insurance = false,
        $collect = false,
        $delivery = false
    ): void {
        $this->shippingInfo = $shippingInfo;
        $this->setInsurance($insurance);
        $this->setCollect($collect);
        $this->setDelivery($delivery);
    }

    /**
     * @param bool|string $insurance OPTIONAL (DEFAULT false) - Define if need insurance service
     */
    public function setInsurance($insurance = false): void
    {
        $this->insurance = boolval($insurance) || strtoupper($insurance) === 'TAM' ? 'TAM' : 'SIN';
    }

    /**
     * @param bool|string $collect OPTIONAL (DEFAULT false) - Define if need collect service
     */
    public function setCollect($collect = false): void
    {
        $this->collect = !boolval($collect) || strtolower($collect) === 'false' ? 'false' : 'true';
    }

    /**
     * @param bool|string $delivery OPTIONAL (DEFAULT false) - Define if need home delivery service
     */
    public function setDelivery($delivery = false): void
    {
        $this->delivery = !boolval($delivery) || strtolower($delivery === 'false') ? 'false' : 'true';
    }

    /**
     * Search for the payer in the option list and select the first one
     */
    protected function setPayer(): void
    {
        try {
            $promise = $this->client->requestAsync('GET',
                'https://mycargomanager.appslatam.com/eBusiness-web-1.0-view/private/CreateQuotation.jsf',
                [
                    'headers' => $this->mainHeader
                ])->then(function ($response) {
                if (!$this->payer && strstr($response->getBody(), 'Erro ao carregar o formulário')) {
                    $this->result->status = 'ERROR';
                    $this->result->errors[] = 'Erro ao obter dados';
                    return;
                }
                $doc = new \DOMDocument();
                $libxml_previous_state = libxml_use_internal_errors(true);
                $doc->loadHTML($response->getBody());
                libxml_use_internal_errors($libxml_previous_state);
                $xpath = new \DOMXpath($doc);
                if ($xpath->query('//*[@id="form:j_idt123_input"]/option[2]')->item(0)) {
                    $this->payer = $xpath->query('//*[@id="form:j_idt123_input"]/option[2]')
                                         ->item(0)
                                         ->getAttribute('value');
                }

                $this->setOriginAirport();
            });
            $promise->wait();
        } catch (RequestException $e) {
            $this->result->status = 'ERROR';
            $this->result->errors[] = 'Curl Error: ' . $e->getMessage();
        }
    }

    /**
     * Set the origin airport based on the airport code
     */
    protected function setOriginAirport()
    {
        $originAirport = new TamCargoOriginAirport($this->senderZipCode);

        if ($originAirport->getStatus() === 'ERROR') {
            $this->result->status = 'ERROR';
            $this->result->errors[] = array_merge($this->result->errors, $originAirport->getErrors());
            return;
        }
        $originAirportCode = $originAirport->getAirportCode();
        if ($originAirportCode) {
            try {
                $parameters = [
                    'javax.faces.partial.ajax' => 'true',
                    'javax.faces.source' => 'form:originId',
                    'javax.faces.partial.execute' => 'form:originId',
                    'javax.faces.partial.render' => 'form:originId',
                    'form:originId' => 'form:originId',
                    'form:originId_query' => $originAirportCode,
                    'form' => 'form',
                    'form:originId_input' => $originAirportCode,
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
                    ])->then(function ($response) {
                    $this->originAirport = $this->stringBetween($response->getBody(), 'data-item-value="', '"');
                });
                $promise->wait();
            } catch (RequestException $e) {
                $this->result->status = 'ERROR';
                $this->result->errors[] = 'Curl Error: ' . $e->getMessage();
            }
        }

    }


    /**
     * Set the destiny airport based on the airport code
     */
    protected function setDestinyAirport()
    {
        $destinyAirport = new TamCargoDestinyAirport($this->shippingInfo->getReceiverZipCode());

        if ($destinyAirport->getStatus() === 'ERROR') {
            $this->result->status = 'ERROR';
            $this->result->errors[] = array_merge($this->result->errors, $destinyAirport->getErrors());
            return;
        }

        $destinyAirportCode = $destinyAirport->getAirportCode();
        if ($destinyAirportCode) {
            try {
                $parameters = [
                    'javax.faces.partial.ajax' => 'true',
                    'javax.faces.source' => 'form:destinationId',
                    'javax.faces.partial.execute' => 'form:destinationId',
                    'javax.faces.partial.render' => 'form:destinationId',
                    'form:destinationId' => 'form:destinationId',
                    'form:destinationId_query' => $destinyAirportCode,
                    'form' => 'form',
                    'form:originId_input' => $this->originAirport,
                    'form:j_idt30' => $this->collect,
                    'form:collectCepId' => $this->senderZipCode,
                    'form:destinationId_input' => $destinyAirportCode,
                    'form:j_idt84_input' => 'ALL',
                    'form:j_idt98_input' => 'TAM',
                    'form:j_idt110' => 'P',
                    'form:accordionDC_active' => '0',
                    'form:table_dim_scrollState' => '0,0',
                    'javax.faces.ViewState' => $this->viewStateLogin,
                ];

                $promise = $this->client->requestAsync(
                    'POST',
                    'https://mycargomanager.appslatam.com/eBusiness-web-1.0-view/private/CreateQuotation.jsf',
                    [
                        'form_params' => $parameters,
                        'headers' => $this->mainHeader
                    ])->then(function ($response) {
                    $this->destinyAirport = $this->stringBetween($response->getBody(), 'data-item-value="', '"');
                });
                $promise->wait();
            } catch (RequestException $e) {
                $this->result->status = 'ERROR';
                $this->result->errors[] = 'Curl Error: ' . $e->getMessage();
            }
        }

    }

    /**
     * Calculates the cubic weight and defines the weight to be used to quote
     */
    protected function setQuoteWeight(): void
    {
        if ($this->shippingInfo->getVolume() && !$this->shippingInfo->isQuoteByWeight()) {
            $cubedWeight = $this->shippingInfo->getVolume() * 166.5;
            if ($cubedWeight > $this->shippingInfo->getWeight()) {
                $this->weight = number_format($cubedWeight, 4, '.', '');
                return;
            }
        }
        $this->weight = $this->shippingInfo->getWeight();
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