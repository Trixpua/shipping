<?php

namespace Trixpua\Shipping\Jamef\Tracking;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

/**
 * Class Jamef
 * @author Elizandro Echer <https://github.com/Trixpua>
 * @package Trixpua\Shipping\Quote
 * @version 2.0.6
 */
class Jamef
{

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    /** @var string */
    private $token;

    /** @var string */
    private $payerTaxId;

    /** @var string */
    private $receiverTaxId;

    /** @var int|string */
    private $invoiceNumber;

    /** @var int|null|string */
    private $invoiceSeries;

    /** @var int|null|string */
    private $originBranch;

    /** @var null|string */
    private $eBillOfLadingNumber;

    /** @var null|string */
    private $ebillsOfLadingeries;

    /** @var null|string */
    private $initialDate;

    /** @var null|string */
    private $finalDate;

    /** @var Client */
    private $client;

    /** @var \stdClass */
    private $result;

    /**
     * Jamef constructor.
     * @param string $username Define the username
     * @param string $password Define the password
     * @param string $payerTaxId Define the payer tax ID (CPF / CNPJ)
     */
    public function __construct(string $username, string $password, string $payerTaxId)
    {
        $this->client = new Client();
        $this->result = new \stdClass();
        $this->username = $username;
        $this->password = $password;
        $this->setPayerTaxId($payerTaxId);
    }


    /**
     * @param string $payerTaxId Define the sender tax ID (CPF / CNPJ)
     */
    public function setPayerTaxId(string $payerTaxId): void
    {
        $this->payerTaxId = preg_replace('/[^\d]/', '', $payerTaxId);
    }

    public function setData(
        $invoiceNumber = null,
        $invoiceSeries = null,
        $eBillOfLadingNumber = null,
        $ebillsOfLadingeries = null,
        ?string $receiverTaxId = null,
        $originBranch = null,
        $initialDate = null,
        $finalDate = null
    ): void {

        $this->setInvoiceNumber($invoiceNumber);
        $this->setInvoiceSeries($invoiceSeries);
        $this->setEBillOfLadingNumber($eBillOfLadingNumber);
        $this->setEbillsOfLadingeries($ebillsOfLadingeries);
        $this->setReceiverTaxId($receiverTaxId);
        $this->setOriginBranch($originBranch);
        $this->setInitialDate($initialDate);
        $this->setFinalDate($finalDate);
    }

    /**
     * @param string $receiverTaxId Define the receiver tax ID (CPF / CNPJ)
     */
    public function setReceiverTaxId(?string $receiverTaxId = null): void
    {
        $this->receiverTaxId = preg_replace('/[^\d]/', '', $receiverTaxId) ?: null;
    }

    /**
     * @param int|string $invoiceNumber Define the invoice number to get track information
     */
    public function setInvoiceNumber($invoiceNumber): void
    {
        $this->invoiceNumber = $invoiceNumber ? intval(preg_replace('/[^\d]/', '', $invoiceNumber)) : null;
    }

    /**
     * @param int|null|string $invoiceSeries OPTIONAL (DEFAULT null) - Define the invoice series to get track
     * information
     */
    public function setInvoiceSeries($invoiceSeries = null): void
    {
        $this->invoiceSeries = $invoiceSeries ? intval(preg_replace('/[^\d]/', '', $invoiceSeries)) : null;
    }

    /**
     * @param int|null|string $eBillOfLadingNumber Define the eBill of Lading number to get track information
     */
    public function setEBillOfLadingNumber($eBillOfLadingNumber = null): void
    {
        $this->eBillOfLadingNumber = $eBillOfLadingNumber ? str_pad(preg_replace('/[^\d]/', '', $eBillOfLadingNumber), '9', '0',
            STR_PAD_LEFT) : null;
    }

    /**
     * @param int|null|string $ebillsOfLadingeries OPTIONAL (DEFAULT null) - Define the eBill of Lading series to get track
     * information
     */
    public function setEbillsOfLadingeries($ebillsOfLadingeries = null): void
    {
        $this->ebillsOfLadingeries = $ebillsOfLadingeries ? intval(preg_replace('/[^\d]/', '', $ebillsOfLadingeries)) : null;
    }

    /**
     * @param null|string $originBranch OPTIONAL (DEFAULT null) - Define the Jamef origin branch
     */
    public function setOriginBranch(?string $originBranch = null): void
    {
        $this->originBranch = $originBranch ?: null;
    }

    /**
     * @param null|string $initialDate OPTIONAL (DEFAULT null) - Define the initial period - Format: DD/MM/YYYY
     */
    public function setInitialDate(?string $initialDate = null): void
    {
        $this->initialDate = $initialDate ?: null;
    }

    /**
     * @param null|string $finalDate OPTIONAL (DEFAULT null) - Define the final period - Format: DD/MM/YYYY
     */
    public function setFinalDate(?string $finalDate = null): void
    {
        $this->finalDate = $finalDate ?: null;
    }


    /**
     * Make the request to the web service
     */
    public function makeRequest(): void
    {
        if (!$this->token) {
            $this->login();
        }

        try {
            $promise = $this->client->requestAsync('POST', 'https://api.jamef.com.br/rastreamento/ver',
                [
                    'headers' =>
                        [
                            'Authorization' => 'Bearer ' . $this->token,
                        ],
                    'json' => $this->buildRequest()
                ]
            )->then(function ($response) {
                $this->parseResult($response);
            });
            $promise->wait();
        } catch (RequestException $e) {
            $error = json_decode($e->getResponse()->getBody());
            if($error->message){
                $this->result->errors[] = utf8_decode($error->message->message);
            }
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
     */
    private function login(): void
    {
        try {
            $promise = $this->client->requestAsync('POST', 'https://api.jamef.com.br/login',
                ['json' => ['username' => $this->username, 'password' => $this->password]]
            )->then(function ($response) {
                $json = json_decode($response->getBody());
                $this->token = $json->access_token;
            });
            $promise->wait();
        } catch (RequestException $e) {
            $this->result->status = 'ERROR';
            $this->result->errors[] = 'Curl Error: ' . $e->getMessage();
        }
    }

    /**
     * Mount the request that will be sent to the web service
     * @return array
     */
    private function buildRequest(): array
    {
        return [
            "documentoResponsavelPagamento" => $this->payerTaxId,
            "numeroNotaFiscal" => $this->invoiceNumber,
            "numeroSerieNotaFiscal" => $this->invoiceSeries,
            "conhecimentoTransporteEletronico" => $this->eBillOfLadingNumber,
            "numeroSerieConhecimentoTransporteEletronico" => $this->ebillsOfLadingeries,
            "documentoDestinatario" => $this->receiverTaxId,
            "codigoFilialOrigem" => $this->originBranch,
            "dataInicial" => $this->initialDate,
            "dataFinal" => $this->finalDate
        ];
    }

    /**
     * Parse the response from the webservice and set the result
     * @param Response $response
     */
    private function parseResult(Response $response): void
    {
        $json = json_decode($response->getBody());

        if (isset($json->errorMessage)) {
            $this->result->status = 'ERROR';
            $this->result->errors[] = $json->errorMessage;
            return;
        }

        $this->result->status = 'OK';

        if (isset($this->result->errors)) {
            unset($this->result->errors);
        }

        foreach ($json->conhecimentos as $key => $billOfLading) {
            $this->result->billsOfLading[$key] = new \stdClass();
            $this->result->billsOfLading[$key]->status = 'OK';
            $this->result->billsOfLading[$key]->originClient = $billOfLading->nomeClienteOrigem;
            $this->result->billsOfLading[$key]->originCity = $billOfLading->municipioOrigem;
            $this->result->billsOfLading[$key]->originState = $billOfLading->ufOrigem;
            $this->result->billsOfLading[$key]->destinyClient = $billOfLading->nomeClienteDestino;
            $this->result->billsOfLading[$key]->destinyCity = $billOfLading->municipioDestino;
            $this->result->billsOfLading[$key]->destinyState = $billOfLading->ufDestino;
            $this->result->billsOfLading[$key]->invoiceNumber = $billOfLading->numeroNotaFiscal;
            $this->result->billsOfLading[$key]->eBillOfLadingNumber = $billOfLading->conhecimentoTransporteEletronico;
            $this->result->billsOfLading[$key]->deliveryForecast = (\DateTime::createFromFormat('d/m/y', $billOfLading->dataPrevisaoEntrega))->format('d/m/Y');
            $this->result->billsOfLading[$key]->shippingCost = $billOfLading->valorFrete;
            $this->result->billsOfLading[$key]->deliveryReceiptLink = $billOfLading->linkImagemComprovanteEntrega;

            $this->result->billsOfLading[$key]->events = $this->parseTrackingEvents($billOfLading->historico);
        }

    }

    /**
     * Parse the tracking events
     * @param array $events
     */
    private function parseTrackingEvents(array $events): array
    {
        $parsedEvents = [];
        $count = 0;
        foreach ($events as $event) {
            $parsedEvents[$count] = new \stdClass();
            $parsedEvents[$count]->status = $event->statusRastreamento;
            $parsedEvents[$count]->updateDate = (\DateTime::createFromFormat('d/m/y H:i', $event->dataAtualizacao))->format('d/m/Y H:i:s');
            $parsedEvents[$count]->manifestNumber = $event->numeroManifesto;
            $parsedEvents[$count]->originCity = $event->municipioOrigem;
            $parsedEvents[$count]->originState = $event->ufOrigem;
            $parsedEvents[$count]->destinyCity = $event->municipioDestino;
            $parsedEvents[$count]->destinyState = $event->ufDestino;
            $count++;
        }
        return $parsedEvents;
    }
}