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
class CorreiosOld
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
    private $trackingNumber;

    /** @var \stdClass */
    private $result;

    /**
     * Correios constructor.
     * @param null|string $login OPTIONAL (DEFAULT null) - Define the user to access SRO service
     * @param null|string $password OPTIONAL (DEFAULT null) - Define the password to access SRO service
     */
    public function __construct(?string $login = null, ?string $password = null)
    {
        $this->result = new \stdClass();
        $this->setLogin($login);
        $this->setPassword($password);
    }


    /**
     * @param null|string $login OPTIONAL (DEFAULT null) - Define the user to access SRO service
     */
    public function setLogin(?string $login = null): void
    {
        $this->login = $login ?: 'ECT';
    }

    /**
     * @param null|string $password OPTIONAL (DEFAULT null) - Define the password to access SRO service
     */
    public function setPassword(?string $password = null): void
    {
        $this->password = $password ?: 'SRO';
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
        $this->trackingNumbers = is_array($trackingNumbers) ? array_map('strtoupper',
            $trackingNumbers) : strtoupper($trackingNumbers);
    }

    /**
     * @param null|string $queryType OPTIONAL (DEFAULT null) - Define the query type ('L' => 'list of objects. The
     * server will query each individual identifier informed' | 'F' => 'range of objects. The server will do the sequential query from the first to the last informed object')
     */
    public function setQueryType(?string $queryType = null): void
    {
        $this->queryType = strtoupper($queryType) === 'F' ? 'F' : 'L';
    }

    /**
     * @param null|string $resultType OPTIONAL (DEFAULT null) - Define the result type ('T' => 'all events of the
     * object will be returned' | 'U' => 'Only the last event of the object is returned')
     */
    public function setResultType(?string $resultType = null): void
    {
        $this->resultType = strtoupper($resultType) === 'U' ? 'U' : 'T';
    }
    /**
     * Make the request to the web service
     */
    public function makeRequest(): void
    {
        $header = array(
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "SOAPAction: buscaEventosLista",
        );

        $client = new Client();
        try {
            $promise = $client->requestAsync('POST', 'http://webservice.correios.com.br:80/service/rastro',
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
        return "<?xml version='1.0' encoding='utf-8'?>
<soapenv:Envelope xmlns:soapenv='http://schemas.xmlsoap.org/soap/envelope/' xmlns:res='http://resource.webservice.correios.com.br/'>
   <soapenv:Header/>
   <soapenv:Body>
      <res:buscaEventosLista>
         <usuario>$this->login</usuario>
         <senha>$this->password</senha>
         <tipo>L</tipo>
         <resultado>$this->resultType</resultado>
         <lingua>101</lingua>
         <objetos>$this->trackingNumber</objetos>
      </res:buscaEventosLista>
   </soapenv:Body>
</soapenv:Envelope>";
    }

    /**
     * Parse the response from the webservice and set the result
     * @param Response $response
     */
    private function parseResult(Response $response): void
    {
        var_dump($response->getBody()->getContents());

        $parsedResponse = self::stringBetween($response->getBody()->getContents(), 'mlns:ns2="http://resource.webservice.correios.com.br/">', '</ns2:buscaEventosListaResponse>');

        $xml = new \SimpleXMLElement($parsedResponse);

        var_dump($xml);

        $this->resultReset();

        $this->result->status = 'OK';
        $this->result->version = (string)trim($xml->versao);
        $this->result->quantity = (string)trim($xml->qtd);
        $this->result->queryType = (string)trim($xml->tipoPesquisa);
        $this->result->resultType = (string)trim($xml->tipoResultado);

        $count = 0;
        foreach ($xml->objeto as $object) {

            $this->result->object[$count] = new \stdClass();

            $this->result->object[$count]->number = (string)trim($object->numero);
            $this->result->object[$count]->initials = (string)trim($object->sigla);
            $this->result->object[$count]->name = (string)trim($object->nome);
            $this->result->object[$count]->category = (string)trim($object->categoria);
            $this->result->object[$count]->error = (string)trim($object->erro);

            $this->result->object[$count]->events = $this->parseTrackingEvents($object->evento);
            $count++;
        }
    }

    /**
     * Parse the tracking events
     * @param \object $events
     */
    private function parseTrackingEvents(object $events): array
    {
        $parsedEvents = [];
        $count = 0;
        foreach ($events as $event) {
            $parsedEvents[$count] = new \stdClass();
            $parsedEvents[$count]->type = (string)trim($event->tipo);
            $parsedEvents[$count]->status = (string)trim($event->status);
            $parsedEvents[$count]->date = (string)trim($event->data);
            $parsedEvents[$count]->hour = (string)trim($event->hora);
            $parsedEvents[$count]->description = (string)trim($event->descricao);
            $parsedEvents[$count]->detail = (string)trim($event->detalhe);
            $parsedEvents[$count]->local = (string)trim($event->local);
            $parsedEvents[$count]->code = (string)trim($event->codigo);
            $parsedEvents[$count]->city = (string)trim($event->cidade);
            $parsedEvents[$count]->state = (string)trim($event->uf);
            $parsedEvents[$count]->sto = (string)trim($event->sto);
            $parsedEvents[$count]->amazoncode = (string)trim($event->amazoncode);
            $parsedEvents[$count]->amazontimezone = (string)trim($event->amazontimezone);
            $parsedEvents[$count]->receiver = $this->parseVariableEvents($event->recebedor);
            $parsedEvents[$count]->document = $this->parseVariableEvents($event->documento);
            $parsedEvents[$count]->comment = $this->parseVariableEvents($event->comentario);
            $parsedEvents[$count]->destiny = $this->parseDestinies($event->destino);
            $parsedEvents[$count]->address = $this->parseAddress($event->endereco);

            $count++;
        }
        return $parsedEvents;
    }

    private function parseVariableEvents(string $event): string
    {
        return isset($event) ? (string)trim($event) : '';
    }

    private function parseDestinies($destinies): array
    {
        $parsedDestinies = [];
        $count = 0;
        foreach ($destinies as $destiny) {
            $parsedDestinies[$count] = new \stdClass();
            $parsedDestinies[$count]->local = (string)trim($destiny->local);
            $parsedDestinies[$count]->code = (string)trim($destiny->codigo);
            $parsedDestinies[$count]->city = (string)trim($destiny->cidade);
            $parsedDestinies[$count]->district = (string)trim($destiny->bairro);
            $parsedDestinies[$count]->state = (string)trim($destiny->uf);
            $count++;
        }

        return $parsedDestinies;
    }

    private function parseAddress($address): \stdClass
    {
        $parsedAddress = new \stdClass();
        $parsedAddress->code = (string)trim($address->codigo);
        $parsedAddress->zip = (string)trim($address->cep);
        $parsedAddress->street = (string)trim($address->logradouro);
        $parsedAddress->complement = (string)trim($address->complemento);
        $parsedAddress->number = (string)trim($address->numero);
        $parsedAddress->city = (string)trim($address->localidade);
        $parsedAddress->state = (string)trim($address->uf);
        $parsedAddress->district = (string)trim($address->bairro);
        $parsedAddress->latitude = (string)trim($address->latitude);
        $parsedAddress->longitude = (string)trim($address->longitude);
        $parsedAddress->cellphone = (string)trim($address->celular);

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

    private static function stringBetween($string, $start, $end)
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