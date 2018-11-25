<?php

namespace Trixpua\Shipping\Correios\Quote;


use GuzzleHttp\Client;
use Meng\AsyncSoap\Guzzle\Factory;

/**
 * Class correios
 * @author Elizandro Echer <https://github.com/Trixpua>
 * @package Trixpua\Shipping
 * @version 1.0.0
 */
class Correios extends CorreiosSetParameters
{
    /**
     * Make the request to the web service
     */
    public function makeRequest(): void
    {
        $factory = new Factory();
        $client = $factory->create(new Client(), 'http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx?WSDL');
        try {
            $this->setQuoteWeight();
            $promise = $client->callAsync('CalcPrecoPrazoData', $this->buildRequest())->then(function($response) {
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
            'CalcPrecoPrazoData' => [
                'nCdEmpresa' => $this->login,
                'sDsSenha' => $this->password,
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
                'sDtCalculo' => $this->shippingDate
            ]
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
     * @param \stdClass $response
     */
    private function parseResult(\stdClass $response): void
    {
        $this->resultReset();
        $this->result->status = 'OK';
        $this->result->modals = new \stdClass();
        if (is_array($response->CalcPrecoPrazoDataResult->Servicos->cServico)) {
            foreach ($response->CalcPrecoPrazoDataResult->Servicos->cServico as $key => $row) {
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
            }
        } else {
            $this->resultReset();
            $this->result->status = 'OK';
            $this->result->modals = new \stdClass();
            $this->result->modals->{0} = new \stdClass();
            $this->setModalStatus(0, $response->CalcPrecoPrazoDataResult->Servicos->cServico->Valor, $response->CalcPrecoPrazoDataResult->Servicos->cServico->MsgErro);

            $this->result->modals->{0}->shippingCost = number_format(((number_format(str_replace(',', '.', str_replace('.', '', $response->CalcPrecoPrazoDataResult->Servicos->cServico->Valor)), 2, '.', '') + $this->shippingInfo->getAdditionalCharge()) / (1 - ($this->shippingInfo->getAdditionalPercent() / 100))), 2, '.', '');
            $this->result->modals->{0}->deliveryTime = $response->CalcPrecoPrazoDataResult->Servicos->cServico->PrazoEntrega + $this->shippingInfo->getShipmentDelay();
            $this->result->modals->{0}->shipCode = str_pad($response->CalcPrecoPrazoDataResult->Servicos->cServico->Codigo, '5', '0', STR_PAD_LEFT);
            $this->result->modals->{0}->ownHandValue = number_format(str_replace(',', '.', str_replace('.', '', $response->CalcPrecoPrazoDataResult->Servicos->cServico->ValorMaoPropria)), 2, '.', '');
            $this->result->modals->{0}->receiptNoticeValue = number_format(str_replace(',', '.', str_replace('.', '', $response->CalcPrecoPrazoDataResult->Servicos->cServico->ValorAvisoRecebimento)), 2, '.', '');
            $this->result->modals->{0}->declaredValue = number_format(str_replace(',', '.', str_replace('.', '', $response->CalcPrecoPrazoDataResult->Servicos->cServico->ValorValorDeclarado)), 2, '.', '');
            $this->result->modals->{0}->homeDelivery = $response->CalcPrecoPrazoDataResult->Servicos->cServico->EntregaDomiciliar;
            $this->result->modals->{0}->saturdayDelivery = $response->CalcPrecoPrazoDataResult->Servicos->cServico->EntregaSabado;
            $this->result->modals->{0}->valueWithoutAdditional = number_format(str_replace(',', '.', str_replace('.', '', $response->CalcPrecoPrazoDataResult->Servicos->cServico->ValorSemAdicionais)), 2, '.', '');
            $this->result->modals->{0}->remarks = $response->CalcPrecoPrazoDataResult->Servicos->cServico->obsFim;
            $this->result->modals->{0}->errorCode = $response->CalcPrecoPrazoDataResult->Servicos->cServico->Erro;
            $this->result->modals->{0}->errorMessage = $response->CalcPrecoPrazoDataResult->Servicos->cServico->MsgErro;
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