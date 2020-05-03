<?php

namespace Trixpua\Shipping\Correios\Tracking;


use GuzzleHttp\Client;
use Meng\AsyncSoap\Guzzle\Factory;


/**
 * Class Correios
 * @author Elizandro Echer <https://github.com/Trixpua>
 * @package Trixpua\Shipping
 * @version 2.0.0
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

    /** @var array|string */
    private $trackingNumbers;

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
        $factory = new Factory();
        $client = $factory->create(new Client(),
            'http://webservice.correios.com.br/service/rastro/Rastro.wsdl');
        try {
            $promise = $client->callAsync('buscaEventosLista', $this->buildRequest())->then(function($response) {

                var_dump($response);

                $this->parseResult($response);
            });
            $promise->wait();
        } catch (\Exception $e) {
            $this->result->status = 'ERROR';
            $this->result->errors[] = 'Soap Error: ' . $e->getMessage();
        }
    }

    /**
     * @return \stdClass
     */
    public function getResult(): \stdClass
    {
        return $this->result;
    }

    /**
     * Mount the request that will be sent to the web service
     * @return array
     */
    private function buildRequest(): array
    {
        return [
            'buscaEventosLista' => [
                'usuarioSro' => $this->login,
                'senhaSro' => $this->password,
                'tipoConsulta' => $this->queryType,
                'tipoResultado' => $this->resultType,
                'listaObjetos' => $this->trackingNumbers
            ]
        ];
    }

    /**
     * Parse the response from the webservice and set the result
     * @param \stdClass $response
     */
    private function parseResult(\stdClass $response): void
    {
        $xml = new \SimpleXMLElement($response->return);
        $this->resultReset();

        $this->result->status = 'OK';
        $this->result->version = (string)trim($xml->versao);
        $this->result->amount = (string)trim($xml->qtd);
        $this->result->queryType = (string)trim($xml->tipoPesquisa);
        $this->result->resultType = (string)trim($xml->tipoResultado);
        $this->result->objects = new \stdClass();
        $count = 0;
        foreach ($xml->objeto as $object) {
            $this->result->objects->{$count} = new \stdClass();
            $this->result->objects->{$count}->number = (string)trim($object->numero);
            $this->result->objects->{$count}->initials = (string)trim($object->sigla);
            $this->result->objects->{$count}->name = (string)trim($object->nome);
            $this->result->objects->{$count}->category = (string)trim($object->categoria);

            $this->setTrackingHistories($object->evento, $count);
            $count++;
        }
    }


    /**
     * Set the histories
     * @param \object $histories
     * @param int $count
     */
    private function setTrackingHistories(object $histories, int $count): void
    {
        $this->result->objects->{$count}->histories = new \stdClass();
        $newCount = 0;
        foreach ($histories as $history) {
            $this->result->objects->{$count}->histories->{$newCount} = new \stdClass();
            $this->result->objects->{$count}->histories->{$newCount}->type = (string)trim($history->tipo);
            $this->result->objects->{$count}->histories->{$newCount}->status = (string)trim($history->status);
            $this->result->objects->{$count}->histories->{$newCount}->date = (string)trim($history->data);
            $this->result->objects->{$count}->histories->{$newCount}->hour = (string)trim($history->hora);
            $this->result->objects->{$count}->histories->{$newCount}->description = (string)trim($history->descricao);
            $this->result->objects->{$count}->histories->{$newCount}->detail = (string)trim($history->detalhe);
            $this->result->objects->{$count}->histories->{$newCount}->local = (string)trim($history->local);
            $this->result->objects->{$count}->histories->{$newCount}->code = (string)trim($history->codigo);
            $this->result->objects->{$count}->histories->{$newCount}->city = (string)trim($history->cidade);
            $this->result->objects->{$count}->histories->{$newCount}->state = (string)trim($history->uf);
            $this->result->objects->{$count}->histories->{$newCount}->receiver = $this->setVariableHistories($history->recebedor);
            $this->result->objects->{$count}->histories->{$newCount}->document = $this->setVariableHistories($history->documento);
            $this->result->objects->{$count}->histories->{$newCount}->comment = $this->setVariableHistories($history->comentario);
            $this->result->objects->{$count}->histories->{$newCount}->destiny = $this->setVariableHistories($history->destino);
            $newCount++;
        }
    }

    private function setVariableHistories(string $history): string
    {
        return isset($history) ? (string)trim($history) : '';
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