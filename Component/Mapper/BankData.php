<?php

namespace RpayRatePay\Component\Mapper;

class BankData
{
    /**
     * @var string
     */
    private $accountHolder;

    /**
     * @var string|null
     */
    private $iban;

    /**
     * @var string|null
     */
    private $bankCode;

    /**
     * @var string|null
     */
    private $accountNumber;

    /**
     * @return string
     */
    public function getAccountHolder()
    {
        return $this->accountHolder;
    }

    /**
     * @return null|string
     */
    public function getIban()
    {
        return $this->iban;
    }

    /**
     * @return null|string
     */
    public function getBankCode()
    {
        return $this->bankCode;
    }

    /**
     * @return null|string
     */
    public function getAccountNumber()
    {
        return $this->accountNumber;
    }

    /**
     * BankData constructor.
     * @param $accountHolder
     * @param $iban
     * @param $bankCode
     * @param $accountNumber
     */
    public function __construct($accountHolder, $iban = null, $bankCode = null, $accountNumber = null)
    {
        $this->accountHolder = $accountHolder;
        $this->iban = $iban;
        $this->bankCode = $bankCode;
        $this->accountNumber = $accountNumber;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $a = [
            'Owner' => $this->getAccountHolder()
        ];

        if ($this->getBankCode() !== null) {
            $a['BankAccountNumber'] = $this->getAccountNumber();
            $a['BankCode'] = $this->getBankCode();
        } else {
            $a['Iban'] = $this->getIban();
        }

        return $a;
    }
}
