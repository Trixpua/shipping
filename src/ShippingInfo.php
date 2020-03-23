<?php

namespace Trixpua\Shipping;

/**
 * Class TamCargo
 * @author Elizandro Echer <https://github.com/Trixpua>
 * @package Trixpua\Shipping
 * @version 2.0.2
 */
class ShippingInfo
{
    /** @var string */
    protected $receiverZipCode;

    /** @var int|float|string */
    protected $weight;

    /** @var int|float|string */
    protected $commodityValue;

    /** @var int|float|string */
    protected $volume;

    /** @var bool */
    protected $quoteByWeight;

    /** @var null|int|float|string */
    protected $additionalCharge;

    /** @var null|int|float|string */
    protected $additionalPercent;

    /** @var int|null|string */
    protected $shipmentDelay;

    /**
     * ShippingInfo constructor.
     * @param string $receiverZipCode Define the commodity destiny ZIP code
     * @param float|int|string $weight Define the total weight of the commodity (decimal must be informed with '.')
     * @param float|int|string $commodityValue Define the total value of the commodity (decimal must be informed with '.')
     * @param float|int|null|string $volume OPTIONAL (DEFAULT null) - Define the total cubic volume of the commodity (width x length x height x quantity) in meters (decimal must be informed with '.')
     * @param bool $quoteByWeight OPTIONAL (DEFAULT false) - Define if the quotation should be made only by weight or the cubic volume weight should be calculated
     * @param float|int|null|string $additionalCharge OPTIONAL (DEFAULT null) - Define a fixed amount to be added to the shipping cost (decimal must be informed with '.')
     * @param float|int|null|string $additionalPercent OPTIONAL (DEFAULT null) - Define a percentage value to be added to the shipping cost (decimal must be informed with '.')
     * @param int|null|string $shipmentDelay OPTIONAL (DEFAULT null) - Define the number of days to be added to the delivery date
     */
    public function __construct(
        string $receiverZipCode,
        $weight,
        $commodityValue,
        $volume = null,
        bool $quoteByWeight = false,
        $additionalPercent = null,
        $additionalCharge = null,
        $shipmentDelay = null
    ) {
        $this->setReceiverZipCode($receiverZipCode);
        $this->setWeight($weight);
        $this->setCommodityValue($commodityValue);
        $this->setVolume($volume);
        $this->setQuoteByWeight($quoteByWeight);
        $this->setAdditionalCharge($additionalCharge);
        $this->setAdditionalPercent($additionalPercent);
        $this->setShipmentDelay($shipmentDelay);
    }

    /**
     * @param string $receiverZipCode Define the commodity destiny ZIP code
     */
    public function setReceiverZipCode(string $receiverZipCode): void
    {
        $this->receiverZipCode = preg_replace('/[^0-9]/', '', $receiverZipCode);
    }

    /**
     * @param float|int|string $weight Define the total weight of the commodity (decimal must be informed with '.')
     */
    public function setWeight($weight): void
    {
        $this->weight = number_format(floatval(preg_replace('/[^0-9.]*/', '', $weight)), 4, '.', '');
    }

    /**
     * @param float|int|string $commodityValue Define the total value of the commodity (decimal must be informed with '.')
     */
    public function setCommodityValue($commodityValue): void
    {
        $this->commodityValue = number_format(floatval(preg_replace('/[^0-9.]*/', '', $commodityValue)), 2, '.', '');
    }

    /**
     * @param float|int|null|string $volume OPTIONAL (DEFAULT null) - Define the total cubic volume of the commodity
     * (width x length x height x quantity) in meters (decimal must be informed with '.')
     */
    public function setVolume($volume = null): void
    {
        $this->volume = number_format(floatval(preg_replace('/[^0-9.]*/', '', $volume)), 4, '.', '');
    }

    /**
     * @param bool $quoteByWeight OPTIONAL (DEFAULT false) - Define if the quotation should be made only by weight or the cubic volume weight should be calculated
     */
    public function setQuoteByWeight(bool $quoteByWeight = false): void
    {
        $this->quoteByWeight = $quoteByWeight;
    }


    /**
     * @param float|int|null|string $additionalCharge OPTIONAL (DEFAULT null) - Define a fixed amount to be added to the shipping cost (decimal must be informed with '.')
     */
    public function setAdditionalCharge($additionalCharge = null): void
    {
        $this->additionalCharge = $additionalCharge ? number_format(floatval(preg_replace('/[^0-9.]*/', '', $additionalCharge)), 2, '.', '') : null;
    }

    /**
     * @param float|int|null|string $additionalPercent OPTIONAL (DEFAULT null) - Define a percentage value to be added to the shipping cost (decimal must be informed with '.')
     */
    public function setAdditionalPercent($additionalPercent = null): void
    {
        $this->additionalPercent = $additionalPercent ? number_format(floatval(preg_replace('/[^0-9.]*/', '', $additionalPercent)), 2, '.', '') : null;
    }

    /**
     * @param int|null|string $shipmentDelay OPTIONAL (DEFAULT null) - Define the number of days to be added to the delivery date
     */
    public function setShipmentDelay($shipmentDelay = null): void
    {
        $this->shipmentDelay = $shipmentDelay ? intval(preg_replace('/[^0-9]/', '', $shipmentDelay)) : null;
    }

    /**
     * @return string
     */
    public function getReceiverZipCode(): string
    {
        return $this->receiverZipCode;
    }

    /**
     * @return float|int|string
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @return float|int|string
     */
    public function getCommodityValue()
    {
        return $this->commodityValue;
    }

    /**
     * @return float|int|string
     */
    public function getVolume()
    {
        return $this->volume;
    }

    /**
     * @return bool
     */
    public function isQuoteByWeight(): bool
    {
        return $this->quoteByWeight;
    }

    /**
     * @return float|int|null|string
     */
    public function getAdditionalPercent()
    {
        return $this->additionalPercent;
    }

    /**
     * @return float|int|null|string
     */
    public function getAdditionalCharge()
    {
        return $this->additionalCharge;
    }

    /**
     * @return int|null|string
     */
    public function getShipmentDelay()
    {
        return $this->shipmentDelay;
    }
}