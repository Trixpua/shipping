<?php

namespace Trixpua\Shipping\Correios\Quote;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

/**
 * Class Correios
 * @author Elizandro Echer <https://github.com/Trixpua>
 * @package Trixpua\Shipping
 * @version 2.0.2
 */
class Correios extends CorreiosSetParameters
{
    /**
     * Make the request to the web service
     */
    public function makeRequest(): void
    {

        $client = new Client();
        try {
            $this->setQuoteWeight();
            $promise = $client->requestAsync('GET', 'http://201.48.198.97/calculador/CalcPrecoPrazo.aspx?' . http_build_query($this->buildRequest()))->then(function ($response) {
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

    /**
     * Mount the request that will be sent to the web service
     * @return array
     */
    private function buildRequest(): array
    {
        return [
            'nCdEmpresa' => $this->login ? $this->login : '08082650',
            'sDsSenha' => $this->password ? $this->password : '564321',
            'nCdServico' => $this->shippingModal,
            'sCepOrigem' => $this->senderZipCode,
            'sCepDestino' => $this->shippingInfo->getReceiverZipCode(),
            'nVlPeso' => $this->weight,
            'nCdFormato' => $this->packageFormat,
            'nVlComprimento' => $this->setQuoteLength(),
            'nVlAltura' => $this->setQuoteHeight(),
            'nVlLargura' => $this->setQuoteWidth(),
            'nVlDiametro' => $this->setQuoteDiameter(),
            'sCdMaoPropria' => $this->receiptOwnHand,
            'nVlValorDeclarado' => $this->setQuoteValueDeclare(),
            'sCdAvisoRecebimento' => $this->receiptNotice,
            'sDtCalculo' => $this->shippingDate,
            'StrRetorno' => 'xml',
            'op' => 'CalcPrecoPrazoRestricao'
        ];
    }

    /**
     * Calculates the cubic weight and defines the weight to be used to quote
     */
    private function setQuoteWeight(): void
    {
        if ($this->shippingInfo->getVolume() && !$this->shippingInfo->isQuoteByWeight()) {
            $cubedWeight = $this->shippingInfo->getVolume() * 166.667;
            if ($cubedWeight > 5 && $cubedWeight > $this->shippingInfo->getWeight()) {
                $this->weight = number_format($cubedWeight, 4, '.', '');
                return;
            }
        }
        $this->weight = $this->shippingInfo->getWeight();
    }

    /**
     * Check if the minimum declared value was define or set it to the minimum required
     * @return string
     */
    private function setQuoteValueDeclare(): string
    {
        return $this->valueDeclare ? $this->shippingInfo->getCommodityValue() < '18' ? '18.00' : number_format(preg_replace('/[^0-9.]*/', '', $this->shippingInfo->getCommodityValue()), 2, '.', '') : '0';
    }

    /**
     * Check if the minimum length was define or set it to the minimum required
     * @return string
     */
    private function setQuoteLength(): string
    {
        switch ($this->packageFormat) {
            case '2':
                return $this->shippingInfo->isQuoteByWeight() || $this->length < '18' ? '18' : number_format(floatval($this->length), 4, '.', '');
            default:
                return $this->shippingInfo->isQuoteByWeight() || $this->length < '16' ? '16' : number_format(floatval($this->length), 4, '.', '');
        }
    }

    /**
     * Check if the minimum width was define or set it to the minimum required
     * @return string
     */
    private function setQuoteWidth(): string
    {
        switch ($this->packageFormat) {
            case '2':
                return '0';
            default:
                return $this->shippingInfo->isQuoteByWeight() || $this->width < '11' ? '11' : number_format(floatval($this->width), 4, '.', '');
        }
    }

    /**
     * Check if the minimum height was define or set it to the minimum required
     * @return string
     */
    private function setQuoteHeight(): string
    {
        switch ($this->packageFormat) {
            case '1':
                return $this->shippingInfo->isQuoteByWeight() || $this->height < '2' ? '2' : number_format(floatval($this->height), 4, '.', '');
            default:
                return '0';
        }
    }

    /**
     * Check if the minimum diameter was define or set it to the minimum required
     * @return string
     */
    private function setQuoteDiameter(): string
    {
        switch ($this->packageFormat) {
            case '2':
                return $this->shippingInfo->isQuoteByWeight() || $this->diameter < '5' ? '5' : number_format(floatval($this->diameter), 4, '.', '');
            default:
                return '0';
        }
    }

    /**
     * Parse the response from the webservice and set the result
     * @param Response $response
     */
    private function parseResult(Response $response): void
    {
        $result = simplexml_load_string($response->getBody()->getContents());

        $this->resultReset();
        $this->result->status = 'OK';
        $this->result->modals = new \stdClass();

        $key = 0;
        foreach ($result as $row) {
            $this->result->modals->{$key} = new \stdClass();
            $this->setModalStatus($key, $row->Valor, $row->MsgErro);

            $this->result->modals->{$key}->shippingCost = number_format(((number_format(str_replace(',', '.', str_replace('.', '', $row->Valor)), 2, '.', '') + $this->shippingInfo->getAdditionalCharge()) / (1 - ($this->shippingInfo->getAdditionalPercent() / 100))), 2, '.', '');
            $this->result->modals->{$key}->deliveryTime = $row->PrazoEntrega + $this->shippingInfo->getShipmentDelay();
            $this->result->modals->{$key}->shipCode = str_pad($row->Codigo, '5', '0', STR_PAD_LEFT);
            $this->result->modals->{$key}->ownHandValue = number_format(str_replace(',', '.', str_replace('.', '', $row->ValorMaoPropria)), 2, '.', '');
            $this->result->modals->{$key}->receiptNoticeValue = number_format(str_replace(',', '.', str_replace('.', '', $row->ValorAvisoRecebimento)), 2, '.', '');
            $this->result->modals->{$key}->declaredValue = number_format(str_replace(',', '.', str_replace('.', '', $row->ValorValorDeclarado)), 2, '.', '');
            $this->result->modals->{$key}->homeDelivery = $row->EntregaDomiciliar;
            $this->result->modals->{$key}->saturdayDelivery = $row->EntregaSabado;
            $this->result->modals->{$key}->valueWithoutAdditional = number_format(str_replace(',', '.', str_replace('.', '', $row->ValorSemAdicionais)), 2, '.', '');
            $this->result->modals->{$key}->remarks = $row->obsFim;
            $this->result->modals->{$key}->errorCode = $row->Erro;
            $this->result->modals->{$key}->errorMessage = $row->MsgErro;

            $key++;
        }
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


    /**
     * Set status for each modal
     * @param int $key
     * @param string $value
     * @param string $error
     */
    private function setModalStatus(int $key, string $value, string $error): void
    {
        if (floatval(number_format(floatval(str_replace(',', '.', str_replace('.', '', $value))), 2, '.', ''))) {
            $this->result->modals->{$key}->status = 'OK';
        } else {
            $this->result->modals->{$key}->status = 'ERROR';
            $this->result->modals->{$key}->errors[] = $error;
        }
    }
}