<?php

namespace Trixpua\Shipping\TamCargo\Quote;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

ini_set('max_execution_time', 0);

/**
 * Class TamCargo
 * @author Elizandro Echer <https://github.com/Trixpua>
 * @package Trixpua\Shipping
 * @version 2.0.6
 */
class TamCargoDestinyAirport
{

    /** @var Client */
    private $client;

    /** @var string */
    private $viewState;

    /** @var string */
    private $airportCode;

    /** @var string */
    private $airportName;

    /** @var string */
    private $zipCode;

    /** @var string */
    private $status;

    /** @var array */
    private $errors;

    /**
     * TamCargoDestinyAirport constructor.
     * @param string $receiverZipCode Define the commodity destiny ZIP code
     */
    public function __construct(string $receiverZipCode)
    {
        $this->client = new Client(['cookies' => true]);
        $this->setZipCode($receiverZipCode);
        $this->getAirport();
    }

    /**
     * @param string $zipCode Define the commodity destiny ZIP code
     */
    public function setZipCode(string $zipCode): void
    {
        $this->zipCode = preg_replace('/[^0-9]/', '', $zipCode);
    }

    /**
     * Get the destiny airport based on ZIP code
     */
    private function getAirport(): ?TamCargoDestinyAirport
    {
        $this->getViewState();
        $this->getAddrInfo();

        try {
            $headers = [
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36",
                "Accept: application/xml, text/xml, */*; q=0.01",
                "Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7",
                "Accept-Encoding: ",
                "Connection: keep-alive",
                "Host: minutaweb.lancargo.com",
                "Origin: https://minutaweb.lancargo.com",
                "Referer: https://minutaweb.lancargo.com/MinutaWEB-3.0/public/client.jsf",
                "X-Requested-With: XMLHttpRequest",
                "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
                "Faces-Request: partial/ajax"
            ];

            $parameters = [
                'javax.faces.partial.ajax' => 'true',
                'javax.faces.source' => 'checkBoxDelivery',
                'javax.faces.partial.execute' => 'checkBoxDelivery',
                'javax.faces.partial.render' => 'destStationInformation',
                'javax.faces.behavior.event' => 'valueChange',
                'javax.faces.partial.event' => 'change',
                'formConteudo' => 'formConteudo',
                'inputcep2' => substr($this->zipCode, 0, 5) . '-' . substr($this->zipCode, 5, 3),
                'inputairorig_input' => '0',
                'checkBoxDelivery_input' => 'on',
                'inputairdest_input' => '0',
                'javax.faces.ViewState' => $this->viewState
            ];

            $promise = $this->client->requestAsync('POST',
                'https://minutaweb.lancargo.com/MinutaWEB-3.0/public/client.jsf', [
                    'form_params' => $parameters,
                    'headers' => $headers,
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_SSLVERSION => 0
                    ]
                ])
                                    ->then(function($response) {
                                        $doc = new \DOMDocument();
                                        $libxml_previous_state = libxml_use_internal_errors(true);
                                        $doc->loadHTML($response->getBody());
                                        libxml_use_internal_errors($libxml_previous_state);
                                        $xpath = new \DOMXpath($doc);
                                        $options = $xpath->query('//*[@id="inputairdest_input"]/option');

                                        $this->setAirport($options);

                                        if (strstr($response->getBody(), 'CEP não atendido para entrega')) {
                                            $this->status = 'ERROR';
                                            $this->errors[] = 'CEP destino inválido ou não atendido para entrega';
                                        }
                                    });
            $promise->wait();
            return $this;
        } catch (RequestException $e) {
            $this->status = 'ERROR';
            $this->errors[] = 'Curl Error: ' . $e->getMessage();
        }
    }

    /**
     * Set airport name and airport code
     * @param \object $options
     */
    private function setAirport(object $options)
    {
        foreach ($options as $opt) {
            if ($opt->getAttribute('selected')) {
                $this->status = 'OK';
                $this->airportCode = $opt->getAttribute('value');
                $this->airportName = $opt->nodeValue;
            }
        }
    }

    /**
     * @return string
     */
    public function getAirportCode(): string
    {
        return $this->airportCode;
    }

    /**
     * @return string
     */
    public function getAirportName(): string
    {
        return $this->airportName;
    }

    /**
     * @return string
     */
    public function getZipCode(): string
    {
        return $this->zipCode;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Define the view state used in requests
     */
    private function getViewState(): void
    {
        try {
            $promise = $this->client->requestAsync('GET',
                'https://minutaweb.lancargo.com/MinutaWEB-3.0/public/client.jsf', [
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_SSLVERSION => 0
                    ]
                ])
                                    ->then(function($response) {
                                        $doc = new \DOMDocument();
                                        $libxml_previous_state = libxml_use_internal_errors(true);
                                        $doc->loadHTML($response->getBody());
                                        libxml_use_internal_errors($libxml_previous_state);
                                        $xpath = new \DOMXpath($doc);
                                        $this->viewState = $xpath->query('//*[@name="javax.faces.ViewState"]')
                                                                    ->item(0)
                                                                    ->getAttribute('value');
                                    });
            $promise->wait();
        } catch (RequestException $e) {
            $this->status = 'ERROR';
            $this->errors[] = 'Curl Error: ' . $e->getMessage();
        }
    }

    /**
     * Get the destiny address based on ZIP code
     */
    private function getAddrInfo(): void
    {
        try {
            $headers = [
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36",
                "Accept: application/xml, text/xml, */*; q=0.01",
                "Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7",
                "Accept-Encoding: ",
                "Connection: keep-alive",
                "Host: minutaweb.lancargo.com",
                "Origin: https://minutaweb.lancargo.com",
                "Referer: https://minutaweb.lancargo.com/MinutaWEB-3.0/public/client.jsf",
                "X-Requested-With: XMLHttpRequest",
                "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
                "Faces-Request: partial/ajax"
            ];

            $parameters = [
                'javax.faces.partial.ajax' => 'true',
                'javax.faces.source' => 'inputcep2',
                'primefaces.resetvalues' => 'true',
                'javax.faces.partial.execute' => 'inputcep2',
                'javax.faces.partial.render' => 'inputcep2 inputcidade2 inputbairro2 inputendereco2 inputcomplemento2 panel-recipient-cep stationIataInformation',
                'javax.faces.behavior.event' => 'blur',
                'javax.faces.partial.event' => 'blur',
                'formConteudo' => 'formConteudo',
                'inputcep2' => substr($this->zipCode, 0, 5) . '-' . substr($this->zipCode, 5, 3),
                'inputairorig_input' => '0',
                'inputairdest_input' => '0',
                'javax.faces.ViewState' => $this->viewState
            ];

            $promise = $this->client->requestAsync('POST',
                'https://minutaweb.lancargo.com/MinutaWEB-3.0/public/client.jsf', [
                    'form_params' => $parameters,
                    'headers' => $headers,
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_SSLVERSION => 0
                    ]
                ]);
            $promise->wait();
        } catch (RequestException $e) {
            $this->status = 'ERROR';
            $this->errors[] = 'Curl Error: ' . $e->getMessage();
        }
    }
}