<?php

namespace Trixpua\Shipping\Jamef\Quote;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Trixpua\Shipping\ShippingInfo;

/**
 * Class Jamef
 * @author Elizandro Echer <https://github.com/Trixpua>
 * @package Trixpua\Shipping\Quote
 * @version 2.0.0
 */
class Jamef
{

    /** @var string */
    private $senderTaxId;

    /** @var string */
    private $originState;

    /** @var string */
    private $originCity;

    /** @var int|string */
    private $quoteBranch;

    /** @var string */
    private $user;

    /** @var int|string */
    private $shippingModal;

    /** @var int|string */
    private $productSegment;

    /** @var null|string */
    private $shippingDate;

    /** @var ShippingInfo */
    private $shippingInfo;

    /** @var \stdClass */
    private $result;

    /**
     * Class to get shipping quotation and delivery time from the web service of the Brazilian shipping company Jamef Transportes
     * @param string $user Define a valid user registered in the Jamef database
     * @param string $senderTaxId Define the sender tax ID (CPF / CNPJ)
     * @param string $originState Define the sender state (UF)
     * @param string $originCity Define the sender city
     * @param int|string $quoteBranch Define the Jamef quotation branch
     */
    public function __construct(
        string $user,
        string $senderTaxId,
        string $originState,
        string $originCity,
        $quoteBranch
    ) {
        $this->result = new \stdClass();
        $this->setSenderTaxId($senderTaxId);
        $this->setOriginState($originState);
        $this->setOriginCity($originCity);
        $this->setQuoteBranch($quoteBranch);
        $this->setUser($user);
    }


    /**
     * @param string $senderTaxId Define the sender tax ID (CPF / CNPJ)
     */
    public function setSenderTaxId(string $senderTaxId): void
    {
        $this->senderTaxId = preg_replace('/[^0-9]/', '', $senderTaxId);
    }

    /**
     * @param string $originState Define the sender state (UF)
     */
    public function setOriginState(string $originState): void
    {
        $this->originState = strtoupper($this->strRemoveSpecialChars($originState));
    }

    /**
     * @param string $originCity Define the sender city
     */
    public function setOriginCity(string $originCity): void
    {
        $this->originCity = strtoupper($this->strRemoveSpecialChars($originCity));
    }

    /**
     * @param int|string $quoteBranch Define the Jamef quotation branch
     */
    public function setQuoteBranch($quoteBranch): void
    {
        $this->quoteBranch = str_pad(preg_replace('/[^0-9]/', '', $quoteBranch), '2', '0', STR_PAD_LEFT);
    }

    /**
     * @param string $user Define a valid user registered in the Jamef database
     */
    public function setUser(string $user): void
    {
        $this->user = $user;
    }

    /**
     * @param ShippingInfo $shippingInfo
     * @param int|string $shippingModal OPTIONAL (DEFAULT '1') - Define the shipping modal ('1' => 'Rodoviário' | '2' => 'Aéreo')
     * @param int|string $productSegment OPTIONAL (DEFAULT '000004') - Define the product segment ('000010' => 'ALIMENTOS INDUSTRIALIZADOS' | '000014' => 'CALCADO' | '000016' => 'CARGAS FRACIONADAS' | '000008' => 'CONFECCOES' | '000004' => 'CONFORME NOTA FISCAL' | '000011' => 'COSMETICOS / MATERIAL CIRURGICO' | '000015' => 'E-COMMERCE' | '000006' => 'JORNAIS / REVISTAS' | '000005' => 'LIVROS' | '000017' => 'MATERIA PRIMA' | '000013' => 'MATERIAL ESCOLAR')
     * @param null|string $shippingDate OPTIONAL (DEFAULT null) - Define the date that will be used to calculate delivery forecast. Format: DD/MM/YYYY
     */
    public function setData(
        ShippingInfo $shippingInfo,
        $shippingModal = null,
        $productSegment = null,
        ?string $shippingDate = null
    ): void {
        $this->shippingInfo = $shippingInfo;
        $this->setShippingModal($shippingModal);
        $this->setProductSegment($productSegment);
        $this->setShippingDate($shippingDate);
    }

    /**
     * @param int|string $shippingModal OPTIONAL (DEFAULT '1') - Define the shipping modal ('1' => 'Rodoviário' | '2'
     * => 'Aéreo')
     */
    public function setShippingModal($shippingModal = null): void
    {
        $this->shippingModal = preg_replace('/[^0-9]/', '', $shippingModal) == '2' ? '2' : '1';
    }

    /**
     * @param int|string $productSegment OPTIONAL (DEFAULT '000004') - Define the product segment ('000010' => 'ALIMENTOS INDUSTRIALIZADOS' | '000014' => 'CALCADO' | '000016' => 'CARGAS FRACIONADAS' | '000008' => 'CONFECCOES' | '000004' => 'CONFORME NOTA FISCAL' | '000011' => 'COSMETICOS / MATERIAL CIRURGICO' | '000015' => 'E-COMMERCE' | '000006' => 'JORNAIS / REVISTAS' | '000005' => 'LIVROS' | '000017' => 'MATERIA PRIMA' | '000013' => 'MATERIAL ESCOLAR')
     */
    public function setProductSegment($productSegment = null): void
    {
        $this->productSegment = $productSegment ? (str_pad(preg_replace('/[^0-9]/', '', $productSegment), '6', '0', STR_PAD_LEFT)) : '000004';
    }

    /**
     * @param null|string $shippingDate OPTIONAL (DEFAULT current date) - Define the date that will be used to calculate
     * delivery forecast. Format: DD/MM/YYYY
     */
    public function setShippingDate(?string $shippingDate = null): void
    {
        $this->shippingDate = $shippingDate ?: date('d/m/Y');
    }

    /**
     * Make the request to the web service
     */
    public function makeRequest(): void
    {
        $client = new Client();
        try {
            $this->shippingInfo->isQuoteByWeight() ? $this->shippingInfo->setVolume('0') : null;
            var_dump($this->buildRequest());
            $promise = $client->requestAsync('GET', $this->buildRequest())->then(function($response) {
                $this->parseResult($response);
            });
            $promise->wait();
        } catch (RequestException $e) {
            $this->result->status = 'ERROR';
            $this->result->errors[] = 'Curl Error: ' . $e->getMessage();
        }
    }

    /**
     * Get the parsed result from the request
     * @return \stdClass
     */
    public function getResult(): \stdClass
    {
        return $this->result;
    }

    /**
     * Mount the request that will be sent to the web service
     * @return string
     */
    private function buildRequest(): string
    {
        return "https://www.jamef.com.br/frete/rest/v1/{$this->shippingModal}/{$this->senderTaxId}/{$this->originCity}/{$this->originState}/{$this->productSegment}/{$this->shippingInfo->getWeight()}/{$this->shippingInfo->getCommodityValue()}/{$this->shippingInfo->getVolume()}/{$this->shippingInfo->getReceiverZipCode()}/{$this->quoteBranch}/{$this->shippingDate}/{$this->user}";
    }

    /**
     * Parse the response from the webservice and set the result
     * @param Response $response
     * @throws \Exception
     */
    private function parseResult(Response $response): void
    {
        $json = json_decode($response->getBody());
        if ($response->getStatusCode() === 200) {
            $this->result->status = 'OK';
            $this->setShippingCost($json->valor);
            $this->setDeliveryTime($json->previsao_entrega);
            return;
        }
        if ($response->getStatusCode() === 204) {
            $this->result->status = 'ERROR';
            $this->result->errors[] = 'Error trying to get the quotation, possible reasons: Incorrect or missing parameters, ZIP code not covered';
            return;
        }
        $this->result->status = 'ERROR';
        $this->result->errors[] = 'Unknown error';
    }


    /**
     * Set the shipping cost
     * @param float $value
     */
    private function setShippingCost(float $value): void
    {
        if ($value) {
            $this->result->shippingCost = number_format((($value + $this->shippingInfo->getAdditionalCharge()) / (1 - ($this->shippingInfo->getAdditionalPercent() / 100))), 2, '.', '');
        }
    }

    /**
     * Set the delivery time
     * @param string $deliveryForecast
     * @throws \Exception
     */
    private function setDeliveryTime(string $deliveryForecast): void
    {
        if ($deliveryForecast) {
            $shippingDate = new \DateTime(\DateTime::createFromFormat('d/m/Y', $this->shippingDate)->format('d-m-Y'));
            $deliveryDate = new \DateTime(\DateTime::createFromFormat('d/m/Y', $deliveryForecast)->format('d-m-Y'));
            $deliveryDays = $deliveryDate->diff($shippingDate)->format('%a');

            $workingDays = 0;
            for ($i = 0; $i < $deliveryDays; $i++) {
                $shippingDate->modify('+1 day');
                if ((int)$shippingDate->format('w') != 0 && (int)$shippingDate->format('w') != 6) {
                    $workingDays++;
                }
            }
            $this->result->deliveryTime = $workingDays + $this->shippingInfo->getShipmentDelay();
        }
    }

    /**
     * Remove accentuation and special characters of the string
     * @param string $string
     * @return string
     */
    private function strRemoveSpecialChars(string $string): string
    {
        return html_entity_decode(preg_replace('/&([a-zA-Z])(uml|acute|grave|circ|tilde|cedil|lig);/','$1',htmlentities($this->strNormalize($string), ENT_COMPAT, "UTF-8")));
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