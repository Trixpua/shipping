<?php

namespace Trixpua\Shipping\Jamef\Tracking;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

/**
 * Class Jamef
 * @author Elizandro Echer <https://github.com/Trixpua>
 * @package Trixpua\Shipping\Quote
 * @version 2.0.0
 */
class Jamef
{

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    /** @var string */
    private $senderTaxId;

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
    private $eBillOfLadingSeries;

    /** @var null|string */
    private $initialDate;

    /** @var null|string */
    private $finalDate;

    /** @var null|string */
    private $outputFormat;

    /** @var \stdClass */
    private $result;

    /**
     * Jamef constructor.
     * @param string $senderTaxId Define the sender tax ID (CPF / CNPJ)
     */
    public function __construct(string $senderTaxId)
    {
        $this->result = new \stdClass();
        $this->setSenderTaxId($senderTaxId);
    }


    /**
     * @param string $senderTaxId Define the sender tax ID (CPF / CNPJ)
     */
    public function setSenderTaxId(string $senderTaxId): void
    {
        $this->senderTaxId = preg_replace('/[^0-9]/', '', $senderTaxId);
    }

    public function setData(
        string $receiverTaxId,
        $invoiceNumber,
        $invoiceSeries = null,
        $originBranch = null,
        ?string $outputFormat = null
    ): void {
        $this->setReceiverTaxId($receiverTaxId);
        $this->setInvoiceNumber($invoiceNumber);
        $this->setInvoiceSeries($invoiceSeries);
        $this->setOriginBranch($originBranch);
        $this->setOutputFormat($outputFormat);
    }

    /**
     * @param string $receiverTaxId Define the receiver tax ID (CPF / CNPJ)
     */
    public function setReceiverTaxId(string $receiverTaxId): void
    {
        $this->receiverTaxId = preg_replace('/[^0-9]/', '', $receiverTaxId) ?: '0000000000';
    }

    /**
     * @param int|string $invoiceNumber Define the invoice number to get track information
     */
    public function setInvoiceNumber($invoiceNumber): void
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
     * @param int|null|string $originBranch OPTIONAL (DEFAULT null) - Define the Jamef origin branch
     */
    public function setOriginBranch($originBranch = null): void
    {
        $this->originBranch = $originBranch ? str_pad(preg_replace('/[^0-9]/', '', $originBranch), '2', '0',
            STR_PAD_LEFT) : null;
    }

    /**
     * @param null|string $outputFormat ('XML' | 'HTML')
     */
    public function setOutputFormat(?string $outputFormat = null): void
    {
        $this->outputFormat = strtoupper($outputFormat) === 'HTML' ? 'HTML' : 'XML';
    }


    /**
     * Make the request to the web service
     */
    public function makeRequest(): void
    {
        $client = new Client();
        try {
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
        $parameters = [
            'CIC_RESP_PGTO' => $this->senderTaxId,
            'CIC_DEST' => $this->receiverTaxId,
            'NUM_NF' => $this->invoiceNumber,
            'SERIE_NF' => $this->invoiceSeries,
            'SAIDA' => $this->outputFormat,
            'COD_REGN_ORIG' => $this->originBranch
        ];

        $data = http_build_query($parameters);
        return "http://www.jamef.com.br/e-commerce/RastreamentoCargaServlet?$data";
    }

    /**
     * Parse the response from the webservice and set the result
     * @param Response $response
     */
    private function parseResult(Response $response): void
    {
        $xml = simplexml_load_string($response->getBody());

        if ($xml && !isset($xml->ERRO)) {
            $this->result->status = 'OK';
            $this->result->ctrc = (string)trim($xml->CONHECIMENTO->CTRC);
            $this->result->nfe = (string)trim($xml->CONHECIMENTO->NF);
            $this->result->originClient = (string)trim($xml->CONHECIMENTO->CLIORIG);
            $this->result->originCity = (string)trim($xml->CONHECIMENTO->MUNORIG);
            $this->result->originState = (string)trim($xml->CONHECIMENTO->UFORIG);
            $this->result->destinyClient = (string)trim($xml->CONHECIMENTO->CLIDEST);
            $this->result->destinyCity = (string)trim($xml->CONHECIMENTO->MUNDEST);
            $this->result->destinyState = (string)trim($xml->CONHECIMENTO->UFDEST);
            $this->result->imageLink = (string)trim($xml->CONHECIMENTO->LINKIMG);

            $this->setTrackingHistories($xml->HISTORICO->POSICAO);
            return;
        }
        if ($xml && isset($xml->ERRO)) {
            $this->result->status = 'ERROR';
            $this->result->errors[] = (string)trim($xml->ERRO->DESCERRO);
            return;
        }
        $this->result->status = 'ERROR';
        $this->result->errors[] = 'Unknown error';

    }

    /**
     * Set the histories
     * @param \object $histories
     */
    private function setTrackingHistories(object $histories): void
    {
        $this->result->histories = new \stdClass();
        $count = 0;
        foreach ($histories as $history) {
            $this->result->histories->{$count} = new \stdClass();
            $this->result->histories->{$count}->status = (string)trim($history->STATUS);
            $this->result->histories->{$count}->updateDate = (string)trim($history->DTATUALIZ);
            $this->result->histories->{$count}->manifest = (string)trim($history->MANIF);
            $this->result->histories->{$count}->locatedCity = (string)trim($history->MUNLOCL);
            $this->result->histories->{$count}->locatedState = (string)trim($history->UFLOCL);
            $this->result->histories->{$count}->manifestCity = (string)trim($history->MUNDESTMANF);
            $this->result->histories->{$count}->manifestState = (string)trim($history->UFDESTMANF);
            $count++;
        }
    }
}