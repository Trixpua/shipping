<?php

namespace Trixpua\Shipping\Jadlog\Tracking;


use GuzzleHttp\Client;
use Meng\AsyncSoap\Guzzle\Factory;


/**
 * Class Jadlog
 * @author Elizandro Echer <https://github.com/Trixpua>
 * @package Trixpua\Shipping
 * @version 2.0.2
 */
class Jadlog
{

    /** @var string */
    private $password;

    /** @var string */
    private $senderTaxId;

    /** @var int|string */
    private $documentNumber;

    /** @var \stdClass */
    private $result;

    /**
     * Jadlog constructor.
     * @param string $password Define the password registered to access Jadlog services
     * @param string $senderTaxId Define the sender tax ID (CPF / CNPJ)
     */
    public function __construct(string $password, string $senderTaxId)
    {
        $this->result = new \stdClass();
        $this->setPassword($password);
        $this->setSenderTaxId($senderTaxId);
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
     * @param int|string $documentNumber Define the tracking numbers to get tracking information
     */
    public function setData($documentNumber): void
    {
        $this->setDocumentNumber($documentNumber);
    }

    /**
     * @param int|string $documentNumber Define the tracking numbers to get tracking information
     */
    public function setDocumentNumber($documentNumber): void
    {
        $this->documentNumber = preg_replace('/[^0-9]/', '', $documentNumber);
    }


    /**
     * Make the request to the web service
     */
    public function makeRequest(): void
    {
        $factory = new Factory();
        $client = $factory->create(new Client(),
            'http://201.63.135.178:8080/JadlogEdiWs/services/TrackingBean?wsdl');
        try {
            $promise = $client->callAsync('consultar', $this->buildRequest())->then(function($response) {
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
            'consultar' => [
                'CodCliente' => $this->senderTaxId,
                'Password' => $this->password,
                'NDs' => $this->documentNumber
            ]
        ];
    }

    /**
     * Parse the response from the webservice and set the result
     * @param \stdClass $response
     */
    private function parseResult(\stdClass $response): void
    {
        $xml = simplexml_load_string($response->consultarReturn);

        if (!$xml->Jadlog_Tracking_Consultar->Mensagem) {
            $this->result->status = 'OK';
            $this->result->number = (string)trim($xml->Jadlog_Tracking_Consultar->ND->Numero);
            $this->result->lastStatus = (string)trim($xml->Jadlog_Tracking_Consultar->ND->Status);
            $this->result->deliveryTime = (string)trim($xml->Jadlog_Tracking_Consultar->ND->DataHoraEntrega);
            $this->result->receiver = (string)trim($xml->Jadlog_Tracking_Consultar->ND->Recebedor);
            $this->result->document = (string)trim($xml->Jadlog_Tracking_Consultar->ND->Documento);
            $this->result->nfeKey = (string)trim($xml->Jadlog_Tracking_Consultar->ND->ChaveAcesso);
            $this->result->cteNumber = (string)trim($xml->Jadlog_Tracking_Consultar->ND->Cte);
            $this->result->cteSeries = (string)trim($xml->Jadlog_Tracking_Consultar->ND->Serie);
            $this->result->issuanceDate = (string)trim($xml->Jadlog_Tracking_Consultar->ND->DataEmissao);
            $this->result->value = (string)trim($xml->Jadlog_Tracking_Consultar->ND->Valor);

            $count = count($xml->Jadlog_Tracking_Consultar->ND->Evento);
            foreach ($xml->Jadlog_Tracking_Consultar->ND->Evento as $history) {
                $count--;
                $this->result->histories[$count] = new \stdClass();
                $this->result->histories[$count]->code = (string)trim($history->Codigo);
                $this->result->histories[$count]->historyTime = (string)trim($history->DataHoraEvento);
                $this->result->histories[$count]->description = (string)trim($history->Descricao);
                $this->result->histories[$count]->remarks = (string)trim($history->Observacao);
            }
            ksort($this->result->histories);
            $this->result->histories = $this->ArrayToObject($this->result->histories);
        } else {
            $this->result->status = 'ERROR';
            $this->result->errors[] = (string)trim($xml->Jadlog_Tracking_Consultar->Mensagem);
        }
    }

    /**
     * @return \stdClass
     */
    public function getResult(): \stdClass
    {
        return $this->result;
    }

    private function arrayToObject($array)
    {
        return is_array($array) ? (object)array_map(__METHOD__, $array) : $array;
    }


}