<?php

namespace Trixpua\Shipping\Tnt\Tracking;


use GuzzleHttp\Client;
use Meng\AsyncSoap\Guzzle\Factory;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\StreamFactory;


/**
 * Class Tnt
 * @author Elizandro Echer <https://github.com/Trixpua>
 * @package Trixpua\Shipping
 * @version 2.0.6
 */
class Tnt
{
    /** @var string */
    private $login;

    /** @var string */
    private $senderTaxId;

    /** @var int|null|string */
    private $invoiceNumber;

    /** @var int|null|string */
    private $invoiceSeries;

    /** @var int|null|string */
    private $orderNumber;

    /** @var \stdClass */
    private $result;

    /**
     * Tnt constructor.
     * @param string $login Define the login registered to access TNT services
     * @param string $senderTaxId Define the sender tax ID (CPF / CNPJ)
     */
    public function __construct(string $login, string $senderTaxId)
    {
        $this->result = new \stdClass();
        $this->setLogin($login);
        $this->setSenderTaxId($senderTaxId);
    }

    /**
     * @param string $login Define the login registered to access TNT services
     */
    public function setLogin(string $login): void
    {
        $this->login = $login;
    }

    /**
     * @param string $senderTaxId Define the sender tax ID (CPF / CNPJ)
     */
    public function setSenderTaxId(string $senderTaxId): void
    {
        $this->senderTaxId = preg_replace('/[^0-9]/', '', $senderTaxId);
    }

    /**
     * Needed to inform the invoice number or order number
     * @param int|null|string $invoiceNumber OPTIONAL (DEFAULT null) - Define the invoice number to get track
     * @param int|null|string $invoiceSeries OPTIONAL (DEFAULT null) - Define the invoice series to get track
     * @param int|null|string $orderNumber OPTIONAL (DEFAULT null) - Define the order number to get track information
     */
    public function setData($invoiceNumber = null, $invoiceSeries = null, $orderNumber = null): void
    {
        $this->setInvoiceNumber($invoiceNumber);
        $this->setInvoiceSeries($invoiceSeries);
        $this->setOrderNumber($orderNumber);
    }

    /**
     * @param int|null|string $invoiceNumber OPTIONAL (DEFAULT null) - Define the invoice number to get track
     * information
     */
    public function setInvoiceNumber($invoiceNumber = null): void
    {
        $this->invoiceNumber = $invoiceNumber ? intval(preg_replace('/[^0-9]/', '', $invoiceNumber)) : null;
    }

    /**
     * @param int|null|string $invoiceSeries OPTIONAL (DEFAULT null) - Define the invoice series to get track
     * information
     */
    public function setInvoiceSeries($invoiceSeries = null): void
    {
        $this->invoiceSeries = $invoiceSeries ? intval(preg_replace('/[^0-9]/', '', $invoiceSeries)) : null;
    }

    /**
     * @param int|null|string $orderNumber OPTIONAL (DEFAULT null) - Define the order number to get track information
     */
    public function setOrderNumber($orderNumber = null): void
    {
        $this->orderNumber = $orderNumber ? intval(preg_replace('/[^0-9]/', '', $orderNumber)) : null;
    }

    /**
     * Make the request to the web service
     */
    public function makeRequest(): void
    {
        $factory = new Factory();
        $client = $factory->create(new Client(), new StreamFactory(), new RequestFactory(), 'https://ws.tntbrasil.com.br/tntws/Localizacao?wsdl');

        var_dump($this->buildRequest());
        try {
            $promise = $client->callAsync('localizaMercadoria', $this->buildRequest())->then(function ($response) {
                $this->parseResult($response);
            });
            $promise->wait();
        } catch (\Exception $e) {
            $this->result->status = 'ERROR';
            $this->result->errors[] = 'Soap Error: ' . $e->getMessage();
        }
    }

    /**
     * Mount the request that will be sent to the web service
     * @return array
     */
    private function buildRequest(): array
    {
        return [
            'localizaMercadoria' => [
                'in0' => [
                    'cnpj' => new \SoapVar($this->senderTaxId, XSD_STRING, 'string', null, 'cnpj', 'http://model.localizacao.mercurio.com'),
                    'nf' => $this->invoiceNumber ? new \SoapVar($this->invoiceNumber, XSD_INT, 'int', null, 'nf', 'http://model.localizacao.mercurio.com'): new \SoapVar('?', XSD_STRING, 'string', null, 'nf', 'http://model.localizacao.mercurio.com'),
                    'nfSerie' => $this->invoiceSeries ? new \SoapVar($this->invoiceSeries, XSD_INT, 'string', null, 'nfSerie', 'http://model.localizacao.mercurio.com') : $this->invoiceNumber ? new \SoapVar('1', XSD_STRING, 'string', null, 'nfSerie', 'http://model.localizacao.mercurio.com') : new \SoapVar('?', XSD_STRING, 'string', null, 'pedido', 'http://model.localizacao.mercurio.com'),
                    'pedido' => $this->orderNumber ? new \SoapVar($this->orderNumber, XSD_INT, 'int', null, 'pedido', 'http://model.localizacao.mercurio.com') : new \SoapVar('?', XSD_STRING, 'string', null, 'pedido', 'http://model.localizacao.mercurio.com'),
                    'usuario' => new \SoapVar($this->login, XSD_STRING, 'string', null, 'usuario', 'http://model.localizacao.mercurio.com'),
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
        if (property_exists($response->out->erros, "string") === false) {
            $this->result->status = 'OK';
            $this->result->payerTaxId = $response->out->cnpjDevedor;
            $this->result->ctrc = $response->out->conhecimento;
            $this->result->nfe = $response->out->notaFiscal;
            $this->result->order = $response->out->pedido;
            $this->result->weight = $response->out->peso;
            $this->result->volumeAmount = $response->out->qtdVolumes;
            $this->result->deliveryForecast = $response->out->previsaoEntrega;
            $this->result->location = $response->out->localizacao;
            $this->result->deliveryTime = $response->out->dataEntrega;
            $this->result->notDeliveryReason = $response->out->motivoNaoEntrega;
            return;
        }
        if (is_array($response->out->erros->string)) {
            $this->result->status = 'ERROR';
            foreach ($response->out->erros->string as $error) {
                $this->result->errors[] = $error;
            }
        } else {
            $this->result->status = 'ERROR';
            $this->result->errors[] = $response->out->erros->string;
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