<?php

namespace Trixpua\Shipping\ExpressoSaoMiguel\Quote;

use GuzzleHttp\Exception\RequestException;
use Trixpua\Shipping\ShippingInfo;

ini_set('max_execution_time', 0);

/**
 * Class TamCargo
 * @author Elizandro Echer <https://github.com/Trixpua>
 * @package Trixpua\Shipping
 * @version 2.0.6
 */
class ExpressoSaoMiguelSetParameters extends ExpressoSaoMiguelAuth
{
    /** @var string */
    protected $viewState;

    /** @var array */
    protected $mainHeader;

    /** @var string */
    protected $senderZipCode;

    /** @var array */
    protected $originCities = [];

    /** @var string */
    protected $originCity;

    /** @var array */
    protected $destinyCities = [];

    /** @var string */
    protected $destinyCity;

    /** @var string */
    protected $payer;

    /** @var string */
    protected $button;

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
            "Sec-Fetch-Dest: empty",
            "Sec-Fetch-Mode: cors",
            "Sec-Fetch-Site: same-origin",
            "X-Requested-With: XMLHttpRequest"
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
        ShippingInfo $shippingInfo
    ): void {
        $this->shippingInfo = $shippingInfo;
    }

    protected function setOriginCity(): void
    {
        try {

            $promise = $this->client->requestAsync('GET',
                "https://viacep.com.br/ws/{$this->senderZipCode}/json/")
                                    ->then(function ($response) {
                                        $zipInfo = json_decode($response->getBody()->getContents());
                                        if (!property_exists($zipInfo, 'localidade')) {
                                            $this->result->status = 'ERROR';
                                            $this->result->errors[] = 'CEP da cidade de origem inválido';
                                            return;
                                        }
                                        $this->originCity = mb_strtoupper($this->strRemoveSpecialChars("{$zipInfo->localidade} - {$zipInfo->uf}"));
                                    });
            $promise->wait();
        } catch (RequestException $e) {
            $this->result->status = 'ERROR';
            $this->result->errors[] = 'Curl Error: ' . $e->getMessage();
        }
    }


    protected function setDestinyCity(): void
    {
        try {

            $promise = $this->client->requestAsync('GET',
                "https://viacep.com.br/ws/{$this->shippingInfo->getReceiverZipCode()}/json/")
                                    ->then(function ($response) {
                                        $zipInfo = json_decode($response->getBody()->getContents());
                                        if (!property_exists($zipInfo, 'localidade')) {
                                            $this->result->status = 'ERROR';
                                            $this->result->errors[] = 'CEP da cidade de destino inválido';
                                            return;
                                        }
                                        $this->destinyCity = mb_strtoupper($this->strRemoveSpecialChars("{$zipInfo->localidade} / {$zipInfo->uf}"));
                                    });
            $promise->wait();
        } catch (RequestException $e) {
            $this->result->status = 'ERROR';
            $this->result->errors[] = 'Curl Error: ' . $e->getMessage();
        }
    }

    /**
     * Remove accentuation and special characters of the string
     * @param string $string
     * @return string
     */
    private function strRemoveSpecialChars(string $string): string
    {
        return html_entity_decode(preg_replace('/&([a-zA-Z])(uml|acute|grave|circ|tilde|cedil|lig);/', '$1', htmlentities($this->strNormalize($string), ENT_COMPAT, "UTF-8")));
//        return $this->strNormalize(preg_replace('/[^\w\s]/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', preg_replace('/[^\pL\pN]/u', ' ', $this->strNormalize($string))))); //don't work properly with iconv glibc only with libiconv

    }

    /**
     * Convert the charset to UTF-8 and decode the HTML entities, and so remove the unnecessary spaces
     * @param string $string
     * @return string
     */
    private function strNormalize(string $string): string
    {
        return trim(preg_replace('/[ ]{2,}/', ' ', preg_replace('/(\n|\r|\t|&nbsp;)/', ' ', html_entity_decode(htmlentities($string, null, 'utf-8')))));
    }
}