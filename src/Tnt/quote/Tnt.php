<?php

namespace Trixpua\Shipping\Tnt\Quote;


use GuzzleHttp\Client;
use Meng\AsyncSoap\Guzzle\Factory;
use Trixpua\Shipping\ShippingInfo;


/**
 * Class Tnt
 * @author Elizandro Echer <https://github.com/Trixpua>
 * @package Trixpua\Shipping
 * @version 2.0.0
 */
class Tnt
{

    /** @var string */
    private $senderZipCode;

    /** @var string */
    private $login;

    /** @var string */
    private $password;

    /** @var int|string */
    private $clientDivisionCode;

    /** @var string */
    private $senderTaxId;

    /** @var string */
    private $senderStateRegistrationNumber;

    /** @var string */
    private $senderPersonType;

    /** @var string */
    private $senderTaxSituation;

    /** @var int|float|string */
    private $weight;

    /** @var string|null */
    private $shippingModal;

    /** @var string|null */
    private $shippingIncoterm;

    /** @var string|null */
    private $receiverPersonType;

    /** @var string|null */
    private $receiverTaxSituation;

    /** @var string|null */
    private $receiverTaxId;

    /** @var string|null */
    private $receiverStateRegistrationNumber;

    /** @var ShippingInfo */
    private $shippingInfo;

    /** @var \stdClass */
    private $result;

    /**
     * Tnt constructor.
     * @param string $senderZipCode Define the sender ZIP code
     * @param string $login Define the login registered to access TNT services
     * @param string $password Define the password registered to access TNT services
     * @param int|string $clientDivisionCode Define the client division code provided by TNT
     * @param string $senderTaxSituation Define the sender tax situation ('CI' => 'Contribuinte Incentivado' | 'ON' => 'Órgão Público Não Contribuinte' | 'PN' => 'Produtor Rural Não Contribuinte' | 'MN' => 'ME/EPP/Simples Nacional Não Contribuinte' | 'CN' => 'Cia. Mista Não Contribuinte' | 'OF' => 'Órgão Público - Progr. Fortalecimento Modernização Estadual' | 'CM' => 'Cia. Mista Contribuinte' | 'CO' => 'Contribuinte' | 'ME' => 'ME/EPP/Simples Nacional Contribuinte' | 'NC' => 'Não Contribuinte' | 'OP' => 'Órgão Público Contribuinte' | 'PR' => 'Produtor Rural Contribuinte')
     * @param string $senderTaxId Define the sender tax ID (CPF / CNPJ)
     * @param string $senderStateRegistrationNumber Define the sender state registration number (IE)
     * @param string $senderPersonType OPTIONAL (DEFAULT 'J') - Define the sender person type ('J' => 'Jurídica' | 'F' => 'Física')
     */
    public function __construct(
        string $senderZipCode,
        string $login,
        string $password,
        $clientDivisionCode,
        string $senderTaxSituation,
        string $senderTaxId,
        string $senderStateRegistrationNumber,
        string $senderPersonType = 'J'
    ) {
        $this->result = new \stdClass();
        $this->setSenderZipCode($senderZipCode);
        $this->setLogin($login);
        $this->setPassword($password);
        $this->setClientDivisionCode($clientDivisionCode);
        $this->setSenderTaxSituation($senderTaxSituation);
        $this->setSenderTaxId($senderTaxId);
        $this->setSenderStateRegistrationNumber($senderStateRegistrationNumber);
        $this->setSenderPersonType($senderPersonType);
    }

    /**
     * @param string $senderZipCode Define the sender ZIP code
     */
    public function setSenderZipCode(string $senderZipCode): void
    {
        $this->senderZipCode = preg_replace('/[^0-9]/', '', $senderZipCode);
    }

    /**
     * @param string $login Define the login registered to access TNT services
     */
    public function setLogin(string $login): void
    {
        $this->login = $login;
    }

    /**
     * @param string $password Define the password registered to access TNT services
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @param int|string $clientDivisionCode Define the client division code provided by TNT
     */
    public function setClientDivisionCode($clientDivisionCode): void
    {
        $this->clientDivisionCode = preg_replace('/[^0-9]/', '', $clientDivisionCode);
    }

    /**
     * @param string $senderTaxSituation Define the sender tax situation ('CI' =>
     * 'Contribuinte Incentivado' | 'ON' => 'Órgão Público Não Contribuinte' | 'PN' => 'Produtor Rural Não
     * Contribuinte' | 'MN' => 'ME/EPP/Simples Nacional Não Contribuinte' | 'CN' => 'Cia. Mista Não Contribuinte' |
     * 'OF' => 'Órgão Público - Progr. Fortalecimento Modernização Estadual' | 'CM' => 'Cia. Mista Contribuinte' |
     * 'CO' => 'Contribuinte' | 'ME' => 'ME/EPP/Simples Nacional Contribuinte' | 'NC' => 'Não Contribuinte' | 'OP' =>
     * 'Órgão Público Contribuinte' | 'PR' => 'Produtor Rural Contribuinte')
     */
    public function setSenderTaxSituation(string $senderTaxSituation): void
    {
        $this->senderTaxSituation = strtoupper($senderTaxSituation);
    }

    /**
     * @param string $senderTaxId Define the sender tax ID (CPF / CNPJ)
     */
    public function setSenderTaxId(string $senderTaxId): void
    {
        $this->senderTaxId = preg_replace('/[^0-9]/', '', $senderTaxId);
    }

    /**
     * @param string $senderStateRegistrationNumber Define the sender state registration number (IE)
     */
    public function setSenderStateRegistrationNumber(string $senderStateRegistrationNumber): void
    {
        $this->senderStateRegistrationNumber = preg_replace('/[^0-9]/', '', $senderStateRegistrationNumber);
    }

    /**
     * @param string $senderPersonType OPTIONAL (DEFAULT 'J') - Define the sender person type ('J' => 'Jurídica' |
     * 'F' => 'Física')
     */
    public function setSenderPersonType(string $senderPersonType = 'J'): void
    {
        $this->senderPersonType = strtoupper($senderPersonType) === 'F' ? 'F' : 'J';
    }

    /**
     * @param ShippingInfo $shippingInfo
     * @param string|null $shippingModal - OPTIONAL (DEFAULT 'RNC') - Define the shipping modal ('RNC' => 'Rodoviário Nacional Convencional' ou 'ANC' => 'Aéreo Nacional Convencional')
     * @param string|null $shippingIncoterm OPTIONAL (DEFAULT 'C') - Define the shipping incoterm ('C' => 'CIF' ou 'F' => 'FOB')
     * @param string|null $receiverPersonType OPTIONAL (DEFAULT 'F') - Define the receiver person type ('J' => 'Jurídica' | 'F' => 'Física')
     * @param string|null $receiverTaxSituation OPTIONAL (DEFAULT 'NC') - Define the receiver tax situation ('CI' => 'Contribuinte Incentivado' | 'ON' => 'Órgão Público Não Contribuinte' | 'PN' => 'Produtor Rural Não Contribuinte' | 'MN' => 'ME/EPP/Simples Nacional Não Contribuinte' | 'CN' => 'Cia. Mista Não Contribuinte' | 'OF' => 'Órgão Público - Progr. Fortalecimento Modernização Estadual' | 'CM' => 'Cia. Mista Contribuinte' | 'CO' => 'Contribuinte' | 'ME' => 'ME/EPP/Simples Nacional Contribuinte' | 'NC' => 'Não Contribuinte' | 'OP' => 'Órgão Público Contribuinte' | 'PR' => 'Produtor Rural Contribuinte')
     * @param string|null $receiverTaxId OPTIONAL (DEFAULT '0000000000') - Define the receiver tax ID (CPF / CNPJ)
     * @param string|null $receiverStateRegistrationNumber OPTIONAL (DEFAULT null) - Define the receiver state registration number (IE)
     */
    public function setData(
        ShippingInfo $shippingInfo,
        ?string $shippingModal = null,
        ?string $shippingIncoterm = null,
        ?string $receiverPersonType = null,
        ?string $receiverTaxSituation = null,
        ?string $receiverTaxId = null,
        ?string $receiverStateRegistrationNumber = null
    ): void {
        $this->shippingInfo = $shippingInfo;
        $this->setShippingModal($shippingModal);
        $this->setShippingIncoterm($shippingIncoterm);
        $this->setReceiverPersonType($receiverPersonType);
        $this->setReceiverTaxSituation($receiverTaxSituation);
        $this->setReceiverTaxId($receiverTaxId);
        $this->setReceiverStateRegistrationNumber($receiverStateRegistrationNumber);
    }

    /**
     * @param string|null $shippingModal - OPTIONAL (DEFAULT 'RNC') - Define the shipping modal ('RNC' => 'Rodoviário
     * Nacional Convencional' ou 'ANC' => 'Aéreo Nacional Convencional')
     */
    public function setShippingModal(?string $shippingModal = null): void
    {
        $this->shippingModal = strtoupper($shippingModal) === 'ANC' ? 'ANC' : 'RNC';
    }

    /**
     * @param string|null $shippingIncoterm OPTIONAL (DEFAULT 'C') - Define the shipping incoterm ('C' => 'CIF' ou 'F' => 'FOB')
     */
    public function setShippingIncoterm(?string $shippingIncoterm = null): void
    {
        $this->shippingIncoterm = strtoupper($shippingIncoterm) === 'F' ? 'F' : 'C';
    }

    /**
     * @param string|null $receiverPersonType OPTIONAL (DEFAULT 'F') - Define the receiver person type ('J' => 'Jurídica' |
     * 'F' => 'Física')
     */
    public function setReceiverPersonType(?string $receiverPersonType = null): void
    {
        $this->receiverPersonType = strtoupper($receiverPersonType) === 'J' ? 'J' : 'F';
    }

    /**
     * @param string|null $receiverTaxSituation OPTIONAL (DEFAULT 'NC') - Define the receiver tax situation ('CI' =>
     * 'Contribuinte Incentivado' | 'ON' => 'Órgão Público Não Contribuinte' | 'PN' => 'Produtor Rural Não
     * Contribuinte' | 'MN' => 'ME/EPP/Simples Nacional Não Contribuinte' | 'CN' => 'Cia. Mista Não Contribuinte' |
     * 'OF' => 'Órgão Público - Progr. Fortalecimento Modernização Estadual' | 'CM' => 'Cia. Mista Contribuinte' |
     * 'CO' => 'Contribuinte' | 'ME' => 'ME/EPP/Simples Nacional Contribuinte' | 'NC' => 'Não Contribuinte' | 'OP' =>
     * 'Órgão Público Contribuinte' | 'PR' => 'Produtor Rural Contribuinte')
     */
    public function setReceiverTaxSituation(?string $receiverTaxSituation = null): void
    {
        $this->receiverTaxSituation = strtoupper($receiverTaxSituation) ?: 'NC';
    }

    /**
     * @param string|null $receiverTaxId OPTIONAL (DEFAULT '0000000000') - Define the receiver tax ID (CPF / CNPJ)
     */
    public function setReceiverTaxId(?string $receiverTaxId = null): void
    {
        $this->receiverTaxId = preg_replace('/[^0-9]/', '', $receiverTaxId) ?: '0000000000';
    }

    /**
     * @param string|null $receiverStateRegistrationNumber OPTIONAL (DEFAULT null) - Define the receiver state registration
     * number (IE)
     */
    public function setReceiverStateRegistrationNumber(?string $receiverStateRegistrationNumber = null): void
    {
        $this->receiverStateRegistrationNumber = $receiverStateRegistrationNumber;
    }

    /**
     * Make the request to the web service
     */
    public function makeRequest(): void
    {

        $factory = new Factory();
        $client = $factory->create(new Client(), 'http://200.248.69.12/tntws/CalculoFrete?wsdl');

        try {
            $this->setQuoteWeight();
            $promise = $client->callAsync('calculaFrete', $this->buildRequest())->then(function ($response) {
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
        if ($this->shippingInfo->getVolume() && !$this->shippingInfo->isQuoteByWeight()) {
            $cubedWeight = $this->shippingModal === 'ANC' ? $this->shippingInfo->getVolume() * 166.667 : $this->shippingInfo->getVolume() * 300;
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
            'calculaFrete' => [
                'in0' => [
                    'login' => new \SoapVar($this->login, XSD_STRING, 'string', null, 'login', 'http://model.vendas.lms.mercurio.com'),
                    'senha' => new \SoapVar($this->password, XSD_STRING, 'string', null, 'senha', 'http://model.vendas.lms.mercurio.com'),
                    'tpPessoaRemetente' => new \SoapVar($this->senderPersonType, XSD_STRING, 'string', null, 'tpPessoaRemetente', 'http://model.vendas.lms.mercurio.com'),
                    'tpSituacaoTributariaRemetente' => new \SoapVar($this->senderTaxSituation, XSD_STRING, 'string', null, 'tpSituacaoTributariaRemetente', 'http://model.vendas.lms.mercurio.com'),
                    'cdDivisaoCliente' => new \SoapVar($this->clientDivisionCode, XSD_STRING, 'string', null, 'cdDivisaoCliente', 'http://model.vendas.lms.mercurio.com'),
                    'nrIdentifClienteRem' => new \SoapVar($this->senderTaxId, XSD_STRING, 'string', null, 'nrIdentifClienteRem', 'http://model.vendas.lms.mercurio.com'),
                    'nrInscricaoEstadualRemetente' => new \SoapVar($this->senderStateRegistrationNumber, XSD_STRING, 'string', null, 'nrInscricaoEstadualRemetente', 'http://model.vendas.lms.mercurio.com'),
                    'cepOrigem' => new \SoapVar($this->senderZipCode, XSD_STRING, 'string', null, 'cepOrigem', 'http://model.vendas.lms.mercurio.com'),
                    'tpFrete' => new \SoapVar($this->shippingIncoterm, XSD_STRING, 'string', null, 'tpFrete', 'http://model.vendas.lms.mercurio.com'),
                    'tpServico' => new \SoapVar($this->shippingModal, XSD_STRING, 'string', null, 'tpServico', 'http://model.vendas.lms.mercurio.com'),
                    'tpPessoaDestinatario' => new \SoapVar($this->receiverPersonType, XSD_STRING, 'string', null, 'tpPessoaDestinatario', 'http://model.vendas.lms.mercurio.com'),
                    'tpSituacaoTributariaDestinatario' => new \SoapVar($this->receiverTaxSituation, XSD_STRING, 'string', null, 'tpSituacaoTributariaDestinatario', 'http://model.vendas.lms.mercurio.com'),
                    'nrIdentifClienteDest' => new \SoapVar($this->receiverTaxId, XSD_STRING, 'string', null, 'nrIdentifClienteDest', 'http://model.vendas.lms.mercurio.com'),
                    'nrInscricaoEstadualDestinatario' => new \SoapVar($this->receiverStateRegistrationNumber, XSD_STRING, 'string', null, 'nrInscricaoEstadualDestinatario', 'http://model.vendas.lms.mercurio.com'),
                    'cepDestino' => new \SoapVar($this->shippingInfo->getReceiverZipCode(), XSD_STRING, 'string', null, 'cepDestino', 'http://model.vendas.lms.mercurio.com'),
                    'psReal' => new \SoapVar($this->weight, XSD_STRING, 'string', null, 'psReal', 'http://model.vendas.lms.mercurio.com'),
                    'vlMercadoria' => new \SoapVar($this->shippingInfo->getCommodityValue(), XSD_STRING, 'string', null, 'vlMercadoria', 'http://model.vendas.lms.mercurio.com')
                ]
            ]
        ];
    }

    /**
     * Parse the response from the webservice and set the result
     * @param \stdClass $response
     */
    private function parseResult(\stdClass $response): void
    {
        if (!property_exists($response->out, 'errorList')) {
            $this->result->status = 'OK';
            $this->result->shippingCost = number_format((($response->out->vlTotalFrete + $this->shippingInfo->getAdditionalCharge()) / (1 - ($this->shippingInfo->getAdditionalPercent() / 100))), 2, '.', '');

            $this->result->deliveryTime = $response->out->prazoEntrega + $this->shippingInfo->getShipmentDelay();

            $this->result->senderName = property_exists($response->out, 'nmRemetente') ? $response->out->nmRemetente : '';
            $this->result->originCity = property_exists($response->out, 'nmMunicipioOrigem') ? $response->out->nmMunicipioOrigem : '';
            $this->result->originBranchPhone = property_exists($response->out, 'nrDDDFilialOrigem') && property_exists($response->out, 'nrTelefoneFilialOrigem') ? $response->out->nrDDDFilialOrigem . ' ' .
                $response->out->nrTelefoneFilialOrigem : '';
            $this->result->receiverName = property_exists($response->out, 'nmDestinatario') ? $response->out->nmDestinatario : '';
            $this->result->destinyCity = property_exists($response->out, 'nmMunicipioDestino') ? $response->out->nmMunicipioDestino : '';
            $this->result->destinyBranchPhone = property_exists($response->out, 'nrDDDFilialDestino') && property_exists($response->out, 'nrTelefoneFilialDestino') ? $response->out->nrDDDFilialDestino . ' ' .
                $response->out->nrTelefoneFilialDestino : '';
            $this->result->discountValue = property_exists($response->out, 'vlDesconto') ? $response->out->vlDesconto : '';
            $this->result->icmsStValue = property_exists($response->out, 'vlICMSubstituicaoTributaria') ? $response->out->vlICMSubstituicaoTributaria : '';
            $this->result->taxesValue = property_exists($response->out, 'vlImposto') ? $response->out->vlImposto : '';
            $this->result->ctrcTotalvalue = property_exists($response->out, 'vlTotalCtrc') ? $response->out->vlTotalCtrc : '';
            $this->result->serviceTotalvalue = property_exists($response->out, 'vlTotalServico') ? $response->out->vlTotalServico : '';
            $this->setParcels($response->out->parcelas);
            $this->setAdditionalServices($response->out->servicosAdicionais);

        } else {
            if (is_array($response->out->errorList)) {
                $this->result->status = 'ERROR';
                foreach ($response->out->errorList as $error) {
                    $this->result->errors[] = $error;
                }
            } else {
                $this->result->status = 'ERROR';
                $this->result->errors[] = $response->out->errorList;
            }

        }
    }

    /**
     * Set the parcels
     * @param $parcels
     */
    private function setParcels(object $parcels): void
    {
        $this->result->parcels = new \stdClass();
        foreach ($parcels->ParcelasFreteWebService as $key => $parcel) {
            $this->result->parcels->{$key} = new \stdClass();
            $this->result->parcels->{$key}->description = $parcel->dsParcela;
            $this->result->parcels->{$key}->value = $parcel->vlParcela;
        }
    }

    /**
     * Set the additional services
     * @param $additionalServices
     */
    private function setAdditionalServices(object $additionalServices): void
    {
        $this->result->additionalServices = new \stdClass();
        foreach ($additionalServices->ServicoAdicionalWebService as $key => $additionalService) {
            $this->result->additionalServices->{$key} = new \stdClass();
            $this->result->additionalServices->{$key}->service = property_exists($additionalService, 'nmServico') ? $additionalService->nmServico : '';
            $this->result->additionalServices->{$key}->description = property_exists($additionalService, 'dsComplemento') ? $additionalService->dsComplemento : '';
            $this->result->additionalServices->{$key}->currency = property_exists($additionalService, 'sgMoeda') ? $additionalService->sgMoeda : '';
            $this->result->additionalServices->{$key}->value = property_exists($additionalService, 'vlServico') ? $additionalService->vlServico : '';
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