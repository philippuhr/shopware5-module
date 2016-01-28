<?php

    /**
     * This program is free software; you can redistribute it and/or modify it under the terms of
     * the GNU General Public License as published by the Free Software Foundation; either
     * version 3 of the License, or (at your option) any later version.
     *
     * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
     * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
     * See the GNU General Public License for more details.
     *
     * You should have received a copy of the GNU General Public License along with this program;
     * if not, see <http://www.gnu.org/licenses/>.
     *
     * validation
     *
     * @category   RatePAY
     * @copyright  Copyright (c) 2013 RatePAY GmbH (http://www.ratepay.com)
     */
    class Shopware_Plugins_Frontend_RpayRatePay_Component_Validation
    {

        /**
         * An Instance of the Shopware-CustomerModel
         *
         * @var Shopware\Models\Customer\Customer
         */
        private $_user;

        /**
         * An Instance of the Shopware-PaymentModel
         *
         * @var Shopware\Models\Payment\Payment
         */
        private $_payment;

        /**
         * Constructor
         *
         * Saves the CustomerModel and initiate the Class
         */
        public function __construct()
        {
            $this->_user = Shopware()->Models()->find('Shopware\Models\Customer\Customer', Shopware()->Session()->sUserId);
            $this->_payment = Shopware()->Models()->find('Shopware\Models\Payment\Payment', $this->_user->getPaymentId());
        }

        /**
         * Checks if the choosen payment is a RatePAY-payment
         *
         * @return boolean
         */
        public function isRatePAYPayment()
        {
            return in_array($this->_payment->getName(), array("rpayratepayinvoice", "rpayratepayrate", "rpayratepaydebit"));
        }

        /**
         * Checks the Customers Age for RatePAY payments
         *
         * @return boolean
         */
        public function isAgeValid()
        {
            $today = new DateTime("now");
            $birthday = $this->_user->getBilling()->getBirthday();
            $return = false;
            if (!is_null($birthday)) {
                if( $birthday->diff($today)->y >= 18 && $birthday->diff($today)->y <= 120 )
                {
                    $return = true;
                }
            }

            return $return;
        }

        /**
         * Checks the format of the birthday-value
         *
         * @return boolean
         */
        public function isBirthdayValid()
        {
            $birthday = $this->_user->getBilling()->getBirthday();
            $return = false;
            if (!is_null($birthday)) {
                if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $birthday->format('Y-m-d')) !== 0)
                {

                    $return = true;
                }
            }

            return $return;
        }

        /**
         * Checks if the telephoneNumber is Set
         *
         * @return boolean
         */
        public function isTelephoneNumberSet()
        {
            $phone = $this->_user->getBilling()->getPhone();

            return !empty($phone);
        }

        /**
         * Checks if the VatId is set
         *
         * @return boolean
         */
        public function isUSTSet()
        {
            $ust = $this->_user->getBilling()->getVatId();

            return !empty($ust);
        }

        /**
         * Checks if the CompanyName is set
         *
         * @return boolean
         */
        public function isCompanyNameSet()
        {
            $companyName = $this->_user->getBilling()->getCompany();

            return !empty($companyName);
        }

        /**
         * Compares the Data of billing- and shippingaddress.
         *
         * @return boolean
         */
        public function isBillingAddressSameLikeShippingAddress()
        {
            $billingAddress = $this->_user->getBilling();
            $shippingAddress = $this->_user->getShipping();
            $classFunctions = array(
                'getCity',
                'getCompany',
                'getCountryId',
                'getDepartment',
                'getFirstname',
                'getLastName',
                'getSalutation',
                'getStateId',
                'getStreet',
                'getZipCode'
            );
            $return = true;
            if (!is_null($shippingAddress)) {
                foreach ($classFunctions as $function) {
                    if (call_user_func(array($billingAddress, $function)) !== call_user_func(array($shippingAddress, $function))) {
                        Shopware()->Pluginlogger()->info('areAddressesEqual-> The value of ' . $function . " differs.");
                        $return = false;
                    }
                }
            }

            return $return;
        }

        /**
         * Compares the Country of billing- and shippingaddress.
         *
         * @return boolean
         */
        public function isCountryEqual()
        {
            $billingAddress = $this->_user->getBilling();
            $shippingAddress = $this->_user->getShipping();
            $return = true;
            if (!is_null($shippingAddress)) {
                if ($billingAddress->getCountryId() != $shippingAddress->getCountryId()) {
                    $return = false;
                }
            }

            return $return;
        }

        /**
         * Checks if the country is germany or austria
         *
         * @return boolean
         */
        public function isCountryValid()
        {
            $country = Shopware()->Models()->find('Shopware\Models\Country\Country', $this->_user->getBilling()->getCountryId());

            return ($country->getIso() === "DE") || ($country->getIso() === 'AT');
        }

        /**
         * Checks if payment methods are hidden by session. Methods will be hide just in live/production mode
         *
         * @return bool
         */
        public function isRatepayHidden() {
            $config = Shopware()->Plugins()->Frontend()->RpayRatePay()->Config();
            $country = Shopware()->Models()->find('Shopware\Models\Country\Country', $this->_user->getBilling()->getCountryId())->getIso();

            if('DE' === $country || 'AT' === $country) {
                $sandbox = $config->get('RatePaySandbox' . $country);
            }

            if (true === Shopware()->Session()->RatePAY['hidePayment'] && false === $sandbox) {
                return true;
            } else {
                return false;
            }
        }

        public function getPayment()
        {
            return $this->_payment;
        }

        public function getUser()
        {
            return $this->_user;
        }

    }
