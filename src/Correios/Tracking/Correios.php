<?php

namespace Trixpua\Shipping\Correios\Tracking;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

/**
 * Class Correios
 * @author Elizandro Echer <https://github.com/Trixpua>
 * @package Trixpua\Shipping
 * @version 2.0.6
 */
class Correios
{

    /** @var null|string */
    private $login;

    /** @var null|string */
    private $password;

    /** @var null|string */
    private $queryType;

    /** @var null|string */
    private $resultType;

    /** @var string */
    private $trackingNumbers;

    /** @var \stdClass */
    private $result;

    /**
     * Correios constructor.
     * @param null|string $login OPTIONAL (DEFAULT null) - Define the user to access SRO service
     * @param null|string $password OPTIONAL (DEFAULT null) - Define the password to access SRO service
     */
    public function __construct()
    {
        $this->result = new \stdClass();
    }

    /**
     * @param array|string $trackingNumbers Define the tracking numbers to get tracking information can be a single tracking number or a list (Ex.: 'AA458226057BR' | ['PM002309903BR'] | ['PM002309903BR', 'RX622250749CH']) or if the query type is defined to 'F' can be a range (Ex.: ['PM002309903BR', 'PM002309934BR'])
     * @param null|string $queryType OPTIONAL (DEFAULT null) - Define the query type ('L' => 'list of objects. The server will query each individual identifier informed' | 'F' => 'range of objects. The server will do the sequential query from the first to the last informed object')
     * @param null|string $resultType OPTIONAL (DEFAULT null) - Define the result type ('T' => 'all events of the object will be returned' | 'U' => 'Only the last event of the object is returned')
     */
    public function setData($trackingNumbers, ?string $queryType = null, ?string $resultType = null): void
    {
        $this->setTrackingNumbers($trackingNumbers);
        $this->setQueryType($queryType);
        $this->setResultType($resultType);
    }

    /**
     * @param array|string $trackingNumbers Define the tracking numbers to get tracking information can be a single tracking number or a list (Ex.: 'AA458226057BR' | ['PM002309903BR'] | ['PM002309903BR', 'RX622250749CH']) or if the query type is defined to 'F' can be a range (Ex.: ['PM002309903BR', 'PM002309934BR'])
     */
    public function setTrackingNumbers($trackingNumbers): void
    {
        $this->trackingNumbers = is_array($trackingNumbers) ? mb_strtoupper(implode('', $trackingNumbers)) : mb_strtoupper($trackingNumbers);
    }

    /**
     * @param null|string $queryType OPTIONAL (DEFAULT null) - Define the query type ('L' => 'list of objects. The
     * server will query each individual identifier informed' | 'F' => 'range of objects. The server will do the sequential query from the first to the last informed object')
     */
    public function setQueryType(?string $queryType = null): void
    {
        $this->queryType = mb_strtoupper($queryType) === 'F' ? 'F' : 'L';
    }

    /**
     * @param null|string $resultType OPTIONAL (DEFAULT null) - Define the result type ('T' => 'all events of the
     * object will be returned' | 'U' => 'Only the last event of the object is returned')
     */
    public function setResultType(?string $resultType = null): void
    {
        $this->resultType = mb_strtoupper($resultType) === 'U' ? 'U' : 'T';
    }

    /**
     * Make the request to the web service
     */
    public function makeRequest(): void
    {
        $header = [
            "Content-type" => "application/xml",
            "Accept" => "application/json"
        ];

        $client = new Client();

        try {
            $promise = $client->requestAsync('POST', 'http://webservice.correios.com.br/service/rest/rastro/rastroMobile',
                [
                    'body' => $this->buildSoapEnvelope(),
                    'headers' => $header
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
     * @return \stdClass
     */
    public function getResult(): \stdClass
    {
        return $this->result;
    }

    private function buildSoapEnvelope(): string
    {
        return "<rastroObjeto>
                    <usuario>MobileXect</usuario>
                    <senha>DRW0#9F$@0</senha>
                    <tipo>$this->queryType</tipo>
                    <resultado>$this->resultType</resultado>
                    <objetos>$this->trackingNumbers</objetos>
                    <lingua>101</lingua>
                    <token>QTXFMvu_Z-6XYezP3VbDsKBgSeljSqlysM9x</token>
                </rastroObjeto>";
    }

    /**
     * Parse the response from the webservice and set the result
     * @param Response $response
     */
    private function parseResult(Response $response): void
    {
        $return = $response->getBody()->getContents();

        $json = json_decode($return);

        $this->resultReset();

        $this->result->status = 'OK';
        $this->result->version = $json->versao;
        $this->result->quantity = $json->quantidade;
        $this->result->queryType = $json->pesquisa;
        $this->result->resultType = $json->resultado;

        $count = 0;
        foreach ($json->objeto as $object) {

            $this->result->objects[$count] = new \stdClass();

            if(mb_strpos($object->categoria, 'ERRO: ') !== false){
                $this->result->objects[$count]->status = 'ERROR';
                $this->result->objects[$count]->number = $object->numero;
                $this->result->objects[$count]->error = $object->categoria;
                continue;
            }

            $this->result->objects[$count]->status = 'OK';
            $this->result->objects[$count]->number = $object->numero;
            $this->result->objects[$count]->initials = $object->sigla;
            $this->result->objects[$count]->name = $object->nome;
            $this->result->objects[$count]->category = $object->categoria;

            $this->result->objects[$count]->events = $this->parseTrackingEvents($object->evento);
            $count++;
        }
    }

    /**
     * Parse the tracking events
     * @param \object $events
     */
    private function parseTrackingEvents(array $events): array
    {
        $parsedEvents = [];
        $count = 0;
        foreach ($events as $event) {
            $parsedEvents[$count] = new \stdClass();
            $parsedEvents[$count]->type = $event->tipo;
            $parsedEvents[$count]->status = $event->status;
            $parsedEvents[$count]->date = $event->data;
            $parsedEvents[$count]->hour = $event->hora;
            $parsedEvents[$count]->create = $event->criacao;
            $parsedEvents[$count]->description = $event->descricao;
            $parsedEvents[$count]->detail = $event->detalhe;

            $parsedEvents[$count]->destinyZip = $event->cepDestino;
            $parsedEvents[$count]->custodyTime = $event->prazoGuarda;
            $parsedEvents[$count]->workingDays = $event->diasUteis;
            $parsedEvents[$count]->shippingDate = $event->dataPostagem;

            $parsedEvents[$count]->originBranch = $this->parseBranch($event->unidade);

            if(property_exists($event, 'recebedor')){
                $parsedEvents[$count]->receiver = $this->parseReceiverInfo($event->recebedor);
            }

            if(property_exists($event, 'destino')) {
                $parsedEvents[$count]->destinyBranch = $this->parseDestiniesBranches($event->destino);
            }

            if(property_exists($event, 'detalheOEC')) {
                $parsedEvents[$count]->objectsDeliveredToPostmanDetail = $this->parseObjectsDeliveredToPostman($event->detalheOEC);
            }

            $count++;
        }
        return $parsedEvents;
    }

    private function parseReceiverInfo($receiverInfo): \stdClass
    {
        $parsedReceiverInfo = new \stdClass();
        $parsedReceiverInfo->name = $receiverInfo->nome;
        $parsedReceiverInfo->document = $receiverInfo->documento;
        $parsedReceiverInfo->comment = $receiverInfo->comentario;

        return $parsedReceiverInfo;
    }

    private function parseObjectsDeliveredToPostman($objectsDeliveredToPostman): \stdClass
    {
        $parsedObjectsDeliveredToPostman = new \stdClass();
        $parsedObjectsDeliveredToPostman->postman = $objectsDeliveredToPostman->carteiro;
        $parsedObjectsDeliveredToPostman->district = $objectsDeliveredToPostman->distrito;
        $parsedObjectsDeliveredToPostman->list = $objectsDeliveredToPostman->lista;
        $parsedObjectsDeliveredToPostman->branch = $objectsDeliveredToPostman->unidade;
        $parsedObjectsDeliveredToPostman->latitude = $objectsDeliveredToPostman->latitude;
        $parsedObjectsDeliveredToPostman->longitude = $objectsDeliveredToPostman->longitude;

        return $parsedObjectsDeliveredToPostman;
    }

    private function parseBranch($branch): \stdClass
    {
        $parsedBranch = new \stdClass();
        $parsedBranch->local = $branch->local;
        $parsedBranch->code = $branch->codigo;
        $parsedBranch->city = $branch->cidade;
        $parsedBranch->state = $branch->uf;
        $parsedBranch->sto = $branch->sto;
        $parsedBranch->type = $branch->tipounidade;
        $parsedBranch->address = $this->parseAddress($branch->endereco);

        return $parsedBranch;
    }

    private function parseDestiniesBranches($destinies): array
    {
        $parsedDestiniesBranches = [];
        foreach ($destinies as $key => $destiny) {
            $parsedDestiniesBranches[$key] = $this->parseBranch($destiny);
        }

        return $parsedDestiniesBranches;

    }

    private function parseAddress($address): \stdClass
    {
        $parsedAddress = new \stdClass();
        $parsedAddress->code = $address->codigo;
        $parsedAddress->zip = $address->cep;
        $parsedAddress->street = $address->logradouro;
        $parsedAddress->complement = $address->complemento;
        $parsedAddress->number = $address->numero;
        $parsedAddress->city = $address->localidade;
        $parsedAddress->state = $address->uf;
        $parsedAddress->district = $address->bairro;
        $parsedAddress->latitude = $address->latitude;
        $parsedAddress->longitude = $address->longitude;
        $parsedAddress->cellphone = $address->celular;

        return $parsedAddress;
    }

    /**
     * Reset the result if not empty
     */
    private function resultReset(): void
    {
        foreach ($this->result as $item => $value) {
            unset($this->result->{$item});
        }
    }
}