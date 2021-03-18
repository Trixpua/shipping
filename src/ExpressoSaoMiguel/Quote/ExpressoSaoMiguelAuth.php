<?php

namespace Trixpua\Shipping\ExpressoSaoMiguel\Quote;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

ini_set('max_execution_time', 0);

/**
 * Class ExpressoSaoMiguelAuth
 * @author Elizandro Echer <https://github.com/Trixpua>
 * @package Trixpua\Shipping
 * @version 2.0.6
 */
abstract class ExpressoSaoMiguelAuth
{

    /** @var Client */
    protected $client;

    /** @var string */
    protected $viewStateLogin;

    /** @var string */
    private $login;

    /** @var string */
    private $password;

    /** @var \stdClass */
    protected $result;

    /**
     * ExpressoSaoMiguelAuth constructor.
     * @param string $login Define the login registered to access TamCargo services
     * @param string $password Define the password registered to access TamCargo services
     */
    protected function __construct(string $login, string $password)
    {
        $this->result = new \stdClass();
        $this->setLogin($login);
        $this->setPassword($password);
    }

    /**
     * @param string $login Define the login registered to access TamCargo services
     */
    public function setLogin(string $login): void
    {
        $this->login = $login;
    }

    /**
     * @param string $password Define the password registered to access TamCargo services
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * Make the login in Expresso Sao Miguel service and set the required parameters needed to proceed
     */
    protected function login(): void
    {
        $this->client = new Client(['cookies' => true]);
        try {
            $this->getViewState();

            $headers = [
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36",
                "Accept: application/xml, text/xml, */*; q=0.01",
                "Accept-Language: pt-BR,pt;q=0.9",
                "Accept-Encoding: ",
                "Connection: keep-alive",
                "Cache-Control: no-cache",
                "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
                "Host: intranet2.expressosaomiguel.com.br",
                "Origin: https://intranet2.expressosaomiguel.com.br",
                "Referer: https://intranet2.expressosaomiguel.com.br/",
                "Faces-Request: partial/ajax",
                "Sec-Fetch-Dest: empty",
                "Sec-Fetch-Mode: cors",
                "Sec-Fetch-Site: same-origin",
                "X-Requested-With: XMLHttpRequest"
            ];

            $parameters = [
                'javax.faces.partial.ajax' => 'true',
                'javax.faces.source' => 'formLogin:j_idt25',
                'javax.faces.partial.execute' => '@all',
                'javax.faces.partial.render' => 'growlLogin',
                'formLogin:j_idt25' => 'formLogin:j_idt25',
                'formLogin' => 'formLogin',
                'formLogin:username' => $this->login,
                'formLogin:password' => $this->password,
                'javax.faces.ViewState' => $this->viewStateLogin
            ];

            $promise = $this->client->requestAsync('POST',
                'https://intranet2.expressosaomiguel.com.br/index.xhtml', [
                    'form_params' => $parameters,
                    'headers' => $headers
                ])
                                    ->then(function($response) {
                                        $doc = new \DOMDocument();
                                        $libxml_previous_state = libxml_use_internal_errors(true);
                                        $doc->loadHTML($response->getBody());
                                        libxml_use_internal_errors($libxml_previous_state);
                                        $xpath = new \DOMXpath($doc);



                                        $this->result->status = null;
                                        $this->result->errors = [];

                                    });
            $promise->wait();
        } catch (RequestException $e) {
            $this->result->status = 'ERROR';
            $this->result->errors[] = 'Curl Error: ' . $e->getMessage();
        }
    }


    private function getViewState()
    {
        try {

            $promise = $this->client->requestAsync('GET',
                'https://intranet2.expressosaomiguel.com.br/')
                                    ->then(function($response) {
                                        $doc = new \DOMDocument();
                                        $libxml_previous_state = libxml_use_internal_errors(true);
                                        $doc->loadHTML($response->getBody());
                                        libxml_use_internal_errors($libxml_previous_state);
                                        $xpath = new \DOMXpath($doc);
                                        $this->viewStateLogin = $xpath->query('//*[@name="javax.faces.ViewState"]')
                                                                        ->item(0)
                                                                        ->getAttribute('value');
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