<?php

namespace Trixpua\Shipping\Jadlog\Quote;


use GuzzleHttp\Client;
use Meng\AsyncSoap\Guzzle\Factory;
use Trixpua\Shipping\ShippingInfo;

/**
 * Class Jadlog
 * @author Elizandro Echer <https://github.com/Trixpua>
 * @package Trixpua\Shipping
 * @version 1.0.0
 */
class Jadlog
{

    /** @var string */
    private $senderZipCode;

    /** @var string */
    private $password;

    /** @var string */
    private $senderTaxId;

    /** @var int|float|string */
    private $weight;

    /** @var int|string */
    private $shippingModal;

    /** @var string|null */
    private $shippingIncoterm;

    /** @var null|int|float|string */
    private $collectFee;

    /** @var string|null */
    private $deliveryType;

    /** @var string|null */
    private $insurance;

    /** @var ShippingInfo */
    private $shippingInfo;

    /** @var \stdClass */
    private $result;

    /**
     * Jadlog constructor.
     * @param string $senderZipCode Define the sender ZIP code
     * @param string $password Define the password registered to access Jadlog services
     * @param string $senderTaxId Define the sender tax ID (CPF / CNPJ)
     */
    public function __construct(string $senderZipCode, string $password, string $senderTaxId)
    {
        $this->result = new \stdClass();
        $this->setSenderZipCode($senderZipCode);
        $this->setPassword($password);
        $this->setSenderTaxId($senderTaxId);
    }

    /**
     * @param string $senderZipCode Define the sender ZIP code
     */
    public function setSenderZipCode(string $senderZipCode): void
    {
        $this->senderZipCode = preg_replace('/[^0-9]/', '', $senderZipCode);
    }

    /**
     * @param string $password Define the password registered to access Jadlog services
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @param string $senderTaxId Define the sender tax ID (CPF / CNPJ)
     */
    public function setSenderTaxId(string $senderTaxId): void
    {
        $this->senderTaxId = preg_replace('/[^0-9]/', '', $senderTaxId);
    }


    /**
     * @param ShippingInfo $shippingInfo
     * @param int|string $shippingModal Define the shipping modal ('0' => 'EXPRESSO' | '3' => '.PACKAGE' | '4' => 'RODOVIÁRIO' | '5' => 'ECONÔMICO' | '6' => 'DOC' | '7' => 'CORPORATE' | '9' => '.COM' | '10' => 'INTERNACIONAL' | '12' => 'CARGO' | '14' => 'EMERGÊNCIAL')
     * @param string|null $shippingIncoterm OPTIONAL (DEFAULT 'N') - Define the shipping incoterm ('S' => 'frete à pagar (FOB)' ou 'N' => 'frete pago (CIF)')
     * @param null|int|float|string $collectFee - OPTIONAL (DEFAULT null) - Define the collect fee negotiated with Jadlog agent
     * @param null|string $deliveryType - OPTIONAL (DEFAULT 'D') - Define the delivery type ('R' => 'Retira unidade' | 'D' => 'Domicílio')
     * @param null|string $insurance - OPTIONAL (DEFAULT 'N') - Define the insurance type ('A' => 'Apólice própria' | 'N' => '|Normal')
     */
    public function setData(
        ShippingInfo $shippingInfo,
        $shippingModal = '3',
        ?string $shippingIncoterm = null,
        ?string $collectFee = null,
        ?string $deliveryType = null,
        ?string $insurance = null
    ): void {
        $this->shippingInfo = $shippingInfo;
        $this->setShippingModal($shippingModal);
        $this->setShippingIncoterm($shippingIncoterm);
        $this->setCollectFee($collectFee);
        $this->setDeliveryType($deliveryType);
        $this->setInsurance($insurance);
    }

    /**
     * @param int|string $shippingModal Define the shipping modal ('0' => 'EXPRESSO' | '3' => '.PACKAGE' | '4' => 'RODOVIÁRIO' | '5' => 'ECONÔMICO' | '6' => 'DOC' | '7' => 'CORPORATE' | '9' => '.COM' | '10' => 'INTERNACIONAL' | '12' => 'CARGO' | '14' => 'EMERGÊNCIAL')
     */
    public function setShippingModal($shippingModal = '3'): void
    {
        $this->shippingModal = $shippingModal;
    }

    /**
     * @param string|null $shippingIncoterm OPTIONAL (DEFAULT 'N') - Define the shipping incoterm ('S' => 'frete à
     * pagar (FOB)' ou 'N' => 'frete pago (CIF)')
     */
    public function setShippingIncoterm(?string $shippingIncoterm = null): void
    {
        $this->shippingIncoterm = strtoupper($shippingIncoterm) === 'S' ? 'S' : 'N';
    }

    /**
     * @param null|int|float|string $collectFee - OPTIONAL (DEFAULT null) - Define the collect fee negotiated with
     * Jadlog agent
     */
    public function setCollectFee(?string $collectFee = null): void
    {
        $this->collectFee = $collectFee ? number_format(floatval(preg_replace('/[^0-9.]*/', '', $collectFee)), 2, '.',
            '') : '0';
    }

    /**
     * @param null|string $deliveryType - OPTIONAL (DEFAULT 'D') - Define the delivery type ('R' => 'Retira unidade'
     * | 'D' => 'Domicílio')
     */
    public function setDeliveryType(?string $deliveryType = null): void
    {
        $this->deliveryType = strtoupper($deliveryType) === 'R' ? 'R' : 'D';
    }

    /**
     * @param null|string $insurance - OPTIONAL (DEFAULT 'N') - Define the insurance type ('A' => 'Apólice própria' |
     * 'N' => '|Normal')
     */
    public function setInsurance(?string $insurance = null): void
    {
        $this->insurance = strtoupper($insurance) === 'A' ? 'A' : 'N';
    }

    /**
     * Make the request to the web service
     */
    public function makeRequest(): void
    {
        $factory = new Factory();
        $client = $factory->create(new Client(),
            'http://www.jadlog.com.br:8080/JadlogEdiWs/services/ValorFreteBean?wsdl');
        try {
            $this->setQuoteWeight();
            $promise = $client->callAsync('valorar', $this->buildRequest())->then(function($response) {
                $this->parseResult($response);
            });
            $promise->wait();
        } catch (\Exception $e) {
            $this->result->status = 'ERROR';
            $this->result->errors[] = 'Soap Error: ' . $e->getMessage();
        }
    }

    /**
     * Calculates the cubic weight and defines the weight to be used to quote
     */
    private function setQuoteWeight(): void
    {
        $modals = [
            'road' => [
                '3' => '.PACKAGE',
                '4' => 'RODOVIÁRIO',
                '5' => 'ECONÔMICO',
                '6' => 'DOC',
                '14' => 'EMERGÊNCIAL'
            ],
            'air' => [
                '0' => 'EXPRESSO',
                '7' => 'CORPORATE',
                '9' => '.COM',
                '10' => 'INTERNACIONAL',
                '12' => 'CARGO'
            ]
        ];

        if ($this->shippingInfo->getVolume() && !$this->shippingInfo->isQuoteByWeight()) {
            $cubedWeight = array_key_exists($this->shippingModal, $modals['air']) ? $this->shippingInfo->getVolume() * 166.667 : $this->shippingInfo->getVolume() * 300;
            if ($cubedWeight > $this->shippingInfo->getWeight()) {
                $this->weight = number_format($cubedWeight, 4, '.', '');
                return;
            }
        }
        $this->weight = $this->shippingInfo->getWeight();
    }

    /**
     * Mount the request that will be sent to the web service
     * @return array
     */
    private function buildRequest(): array
    {
        return [
            'valorar' => [
                'vModalidade' => $this->shippingModal,
                'Password' => $this->password,
                'vSeguro' => $this->insurance,
                'vVlDec' => $this->shippingInfo->getCommodityValue(),
                'vVlColeta' => $this->collectFee,
                'vCepOrig' => $this->senderZipCode,
                'vCepDest' => $this->shippingInfo->getReceiverZipCode(),
                'vPeso' => $this->weight,
                'vFrap' => $this->shippingIncoterm,
                'vEntrega' => $this->deliveryType,
                'vCnpj' => $this->senderTaxId
            ]
        ];
    }

    /**
     * Parse the response from the webservice and set the result
     * @param \stdClass $response
     */
    private function parseResult(\stdClass $response): void
    {
        $xml = simplexml_load_string($response->valorarReturn);

        if ((string)$xml->Jadlog_Valor_Frete->Mensagem === 'Valor do Frete') {
            $this->result->status = 'OK';
            $this->result->shippingCost = number_format(((number_format(preg_replace('/[^0-9.]*/', '', str_replace(',', '.', str_replace('.', '', $xml->Jadlog_Valor_Frete->Retorno))), 2, '.', '') + $this->shippingInfo->getAdditionalCharge()) / (1 - ($this->shippingInfo->getAdditionalPercent() / 100))), 2, '.', '');
            $this->result->shipCode = $this->shippingModal;
        } else {
            $this->result->status = 'ERROR';
            $this->result->errors[] = (string)$xml->Jadlog_Valor_Frete->Mensagem;
        }
    }

    /**
     * @return \stdClass
     */
    public function getResult(): \stdClass
    {
        return $this->result;
    }


}