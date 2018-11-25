<?php

namespace Trixpua\Shipping\TamCargo\Quote;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

ini_set('max_execution_time', 0);

/**
 * Class tamcargo
 * @author Elizandro Echer <https://github.com/Trixpua>
 * @package Trixpua\Shipping
 * @version 1.0.0
 */
abstract class TamCargoLogin
{

    /** @var Client */
    protected $client;

    /** @var string */
    protected $urlLogin;

    /** @var string */
    protected $viewStateLogin;

    /** @var string */
    private $login;

    /** @var string */
    private $password;

    /** @var \stdClass */
    protected $result;

    /**
     * tamcargo constructor.
     * @param string $login Define the login registered to access tamcargo services
     * @param string $password Define the password registered to access tamcargo services
     */
    protected function __construct(string $login, string $password)
    {
        $this->result = new \stdClass();
        $this->setLogin($login);
        $this->setPassword($password);
    }

    /**
     * @param string $login Define the login registered to access tamcargo services
     */
    public function setLogin(string $login): void
    {
        $this->login = $login;
    }

    /**
     * @param string $password Define the password registered to access tamcargo services
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * Make the login in My Cargo Manager service and set the required parameters needed to proceed
     */
    protected function login(): void
    {
        $this->client = new Client(['cookies' => true]);
        try {
            $promise = $this->client->requestAsync('GET',
                'https://secure.lancargo.com/cas/login?TARGET=https%3A%2F%2Fsecure.lancargo.com%2FeBusiness-web-1.0-view%2Fprivate%2FCreateQuotation.jsf%3Flanguage%3Dpt%26company%3DLA')
                                    ->then(function($response) {
                                        $doc = new \DOMDocument();
                                        $libxml_previous_state = libxml_use_internal_errors(true);
                                        $doc->loadHTML($response->getBody());
                                        libxml_use_internal_errors($libxml_previous_state);
                                        $xpath = new \DOMXpath($doc);
                                        $lt = $xpath->query('//input[@name="lt"]')
                                                    ->item(0)
                                                    ->getAttribute('value');
                                        $execution = $xpath->query('//input[@name="execution"]')
                                                            ->item(0)
                                                            ->getAttribute('value');
                                        $this->setViewState($lt, $execution);
                                    });
            $promise->wait();
        } catch (RequestException $e) {
            $this->result->status = 'ERROR';
            $this->result->errors[] = 'Curl Error: ' . $e->getMessage();
        }
    }

    /**
     * Set view state to use and next request
     * @param string $lt
     * @param string $execution
     */
    private function setViewState(string $lt, string $execution)
    {
        try {
            $this->urlLogin = "https://secure.lancargo.com/cas/login;jsessionid={$this->client->getConfig('cookies')->toArray()[0]['Value']}?TARGET=https%3A%2F%2Fsecure.lancargo.com%2FeBusiness-web-1.0-view%2Fprivate%2FCreateQuotation.jsf%3Flanguage%3Dpt%26company%3DLA";
            $headers = [
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36",
                "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",
                "Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7",
                "Accept-Encoding: ",
                "Connection: keep-alive",
                "Cache-Control: max-age=0",
                "Content-Type: application/x-www-form-urlencoded",
                "Host: secure.lancargo.com",
                "Origin: https://secure.lancargo.com",
                "Referer: https://secure.lancargo.com/cas/login?TARGET=https%3A%2F%2Fsecure.lancargo.com%2FeBusiness-web-1.0-view%2Fprivate%2FCreateQuotation.jsf%3Flanguage%3Dpt%26company%3DLA",
                "Upgrade-Insecure-Requests: 1",
            ];

            $parameters = [
                'username' => $this->login,
                'password' => $this->password,
                'lt' => $lt,
                'execution' => $execution,
                '_eventId' => 'submit',
                'submit' => 'ENTRAR',
            ];
            $promise = $this->client->requestAsync('POST',
                $this->urlLogin, [
                    'form_params' => $parameters,
                    'headers' => $headers
                ])
                                    ->then(function($response) {
                                        $doc = new \DOMDocument();
                                        $libxml_previous_state = libxml_use_internal_errors(true);
                                        $doc->loadHTML($response->getBody());
                                        libxml_use_internal_errors($libxml_previous_state);
                                        $xpath = new \DOMXpath($doc);
                                        $this->viewStateLogin = $xpath->query('//*[@name="javax.faces.ViewState"]')
                                                                        ->item(0)
                                                                        ->getAttribute('value');
                                        $this->result->status = null;
                                        $this->result->errors = [];
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

}