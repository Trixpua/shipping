<?php

namespace Trixpua\Shipping\Correios\Quote;


use Trixpua\Shipping\ShippingInfo;


/**
 * Class Correios
 * @author Elizandro Echer <https://github.com/Trixpua>
 * @package Trixpua\Shipping
 * @version 2.0.6
 */
abstract class CorreiosSetParameters
{

    /** @var string */
    protected $senderZipCode;

    /** @var null|string */
    protected $login;

    /** @var null|string */
    protected $password;

    /** @var int|float|string */
    protected $weight;

    /** @var string */
    protected $shippingModal;

    /** @var bool */
    protected $valueDeclare;

    /** @var null|bool|string */
    protected $receiptNotice;

    /** @var null|bool|string */
    protected $receiptOwnHand;

    /** @var int|null|string */
    protected $packageFormat;

    /** @var int|float|string */
    protected $length;

    /** @var int|float|string */
    protected $width;

    /** @var int|float|string */
    protected $height;

    /** @var int|float|string */
    protected $diameter;

    /** @var null|string */
    protected $shippingDate;

    /** @var ShippingInfo */
    protected $shippingInfo;

    /** @var \stdClass */
    protected $result;

    /**
     * Correios constructor.
     * @param string $senderZipCode Define the sender ZIP code
     * @param null|string $login OPTIONAL (DEFAULT null) - Define the company administrative code available in the contract, if you have
     * @param null|string $password OPTIONAL (DEFAULT null) - Define the password to access the service, associated with your administrative code
     */
    public function __construct(string $senderZipCode, ?string $login = null, ?string $password = null)
    {
        $this->result = new \stdClass();
        $this->setSenderZipCode($senderZipCode);
        $this->setLogin($login);
        $this->setPassword($password);
    }


    /**
     * @param string $senderZipCode Define the sender ZIP code
     */
    public function setSenderZipCode(string $senderZipCode): void
    {
        $this->senderZipCode = preg_replace('/[^0-9]/', '', $senderZipCode);
    }

    /**
     * @param null|string $login OPTIONAL (DEFAULT null) - Define the company administrative code available in the
     * contract, if you have
     */
    public function setLogin(?string $login = null): void
    {
        $this->login = $login ?: '';
    }

    /**
     * @param null|string $password OPTIONAL (DEFAULT null) - Define the password to access the service, associated
     * with your administrative code
     */
    public function setPassword(?string $password = null): void
    {
        $this->password = $password ?: '';
    }


    /**
     * @param ShippingInfo $shippingInfo
     * @param string $shippingModal Define the shipping modal, can be multiple separated by commas ('04014' => 'SEDEX à vista' | '04065' => 'SEDEX à vista pagamento na entrega' | '04510' => 'PAC à vista' | '04707' => 'PAC à vista pagamento na entrega' | '40169' => 'SEDEX 12' | '40215' => 'SEDEX 10' | '40290' => 'SEDEX Hoje Varejo'), for clients that have contract check the codes in the contract
     * @param bool $valueDeclare OPTIONAL (DEFAULT false) - Define if the order will be shipped with the additional service declared value ('valor declarado') - (insurance)
     * @param bool|null|string $receiptNotice OPTIONAL (DEFAULT 'N') - Define if the order will be shipped with the additional service receipt notice ('aviso de recebimento')
     * @param bool|null|string $receiptOwnHand OPTIONAL (DEFAULT 'N) - Define if the order will be shipped with the additional service receipt own hand ('mão própria')
     * @param int|null|string $packageFormat OPTIONAL (DEFAULT '1') - Define the package format ('1' => Formato caixa/pacote | '2' => Formato rolo/prisma | '3' => Envelope)
     * @param float|int|null|string $length OPTIONAL (DEFAULT null) - Define the package length
     * @param float|int|null|string $width OPTIONAL (DEFAULT null) - Define the package width
     * @param float|int|null|string $height OPTIONAL (DEFAULT null) - Define the package height
     * @param float|int|null|string $diameter OPTIONAL (DEFAULT null) - Define the package diameter
     * @param null|string $shippingDate OPTIONAL (DEFAULT current date) - Define the date that will be used to calculate delivery forecast. Format: DD/MM/YYYY
     */
    public function setData(
        ShippingInfo $shippingInfo,
        string $shippingModal = '04014, 04510',
        bool $valueDeclare = false,
        $receiptNotice = false,
        $receiptOwnHand = false,
        $packageFormat = null,
        $length = null,
        $width = null,
        $height = null,
        $diameter = null,
        ?string $shippingDate = null

    ): void {
        $this->shippingInfo = $shippingInfo;
        $this->setShippingModal($shippingModal);
        $this->setValueDeclare($valueDeclare);
        $this->setReceiptNotice($receiptNotice);
        $this->setReceiptOwnHand($receiptOwnHand);
        $this->setPackageFormat($packageFormat);
        $this->setLength($length);
        $this->setWidth($width);
        $this->setHeight($height);
        $this->setDiameter($diameter);
        $this->setShippingDate($shippingDate);
    }

    /**
     * @param string $shippingModal Define the shipping modal, can be multiple separated by commas ('04014' => 'SEDEX
     * à vista' | '04065' => 'SEDEX à vista pagamento na entrega' | '04510' => 'PAC à vista' | '04707' => 'PAC à
     * vista pagamento na entrega' | '40169' => 'SEDEX 12' | '40215' => 'SEDEX 10' | '40290' => 'SEDEX Hoje Varejo'),
     * for clients that have contract check the codes in the contract
     */
    public function setShippingModal(string $shippingModal = '04014, 04510'): void
    {
        $this->shippingModal = $shippingModal;
    }

    /**
     * @param bool $valueDeclare OPTIONAL (DEFAULT false) - Define if the order will be shipped with the additional service declared value
     * ('valor declarado') - (insurance)
     */
    public function setValueDeclare(bool $valueDeclare = false): void
    {
        $this->valueDeclare = $valueDeclare;
    }

    /**
     * @param bool|null|string $receiptNotice OPTIONAL (DEFAULT 'N') - Define if the order will be shipped with the
     * additional service receipt notice ('aviso de recebimento')
     */
    public function setReceiptNotice($receiptNotice = false): void
    {
        $this->receiptNotice = strtoupper($receiptNotice) === 'S' ? 'S' : 'N';
    }

    /**
     * @param bool|null|string $receiptOwnHand OPTIONAL (DEFAULT 'N) - Define if the order will be shipped with the
     * additional service receipt own hand ('mão própria')
     */
    public function setReceiptOwnHand($receiptOwnHand = false): void
    {
        $this->receiptOwnHand = strtoupper($receiptOwnHand) === 'S' ? 'S' : 'N';
    }

    /**
     * @param int|null|string $packageFormat OPTIONAL (DEFAULT '1') - Define the package format ('1' => Formato
     * caixa/pacote | '2' => Formato rolo/prisma | '3' => Envelope)
     */
    public function setPackageFormat($packageFormat = null): void
    {
        $this->packageFormat = $packageFormat == 2 || $packageFormat == 3 ? $packageFormat : '1';
    }

    /**
     * @param float|int|null|string $length OPTIONAL (DEFAULT null) - Define the package length
     */
    public function setLength($length = null): void
    {
        $this->length = $length;
    }

    /**
     * @param float|int|null|string $width OPTIONAL (DEFAULT null) - Define the package width
     */
    public function setWidth($width = null): void
    {
        $this->width = $width;
    }

    /**
     * @param float|int|null|string $height OPTIONAL (DEFAULT null) - Define the package height
     */
    public function setHeight($height = null): void
    {
        $this->height = $height;
    }

    /**
     * @param float|int|null|string $diameter OPTIONAL (DEFAULT null) - Define the package diameter
     */
    public function setDiameter($diameter = null): void
    {
        $this->diameter = $diameter;
    }

    /**
     * @param null|string $shippingDate OPTIONAL (DEFAULT current date) - Define the date that will be used to calculate
     * delivery forecast. Format: DD/MM/YYYY
     */
    public function setShippingDate(?string $shippingDate = null): void
    {
        $this->shippingDate = $shippingDate ?: date('d/m/Y');
    }

}