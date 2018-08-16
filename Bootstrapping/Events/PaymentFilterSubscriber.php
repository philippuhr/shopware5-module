<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 13.06.18
 * Time: 10:53
 */
namespace RpayRatePay\Bootstrapping\Events;

use RatePAY\Service\Util;
use RpayRatePay\Component\Model\ShopwareCustomerWrapper;
use RpayRatePay\Component\Service\ConfigLoader;
use RpayRatePay\Component\Service\ValidationLib as ValidationService;

class PaymentFilterSubscriber implements \Enlight\Event\SubscriberInterface
{
    /**
     * @var
     */
    protected $_object;

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Admin_GetPaymentMeans_DataFilter' => 'filterPayments',
        ];
    }

    /**
     * Filters the shown Payments
     * RatePAY-payments will be hidden, if one of the following requirement is not given
     *  - Delivery Address is not allowed to be not the same as billing address
     *  - The Customer must be over 18 years old
     *  - The Country must be germany or austria
     *  - The Currency must be EUR
     *
     * @param \Enlight_Event_EventArgs $arguments
     * @return array|void
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function filterPayments(\Enlight_Event_EventArgs $arguments)
    {
        $return = $arguments->getReturn();
        $currency = Shopware()->Config()->get('currency');
        $userId = Shopware()->Session()->sUserId;

        if (empty($userId) || empty($currency)) {
            return;
        }

        /** @var Shopware\Models\Customer\Customer $user */
        $user = Shopware()->Models()->find('Shopware\Models\Customer\Customer', Shopware()->Session()->sUserId);
        $wrappedUser = new ShopwareCustomerWrapper($user, Shopware()->Models());

        //get country of order
        if (Shopware()->Session()->checkoutBillingAddressId > 0) { // From Shopware 5.2 find current address information in default billing address
            $addressModel = Shopware()->Models()->getRepository('Shopware\Models\Customer\Address');
            $customerAddressBilling = $addressModel->findOneBy(array('id' => Shopware()->Session()->checkoutBillingAddressId));

            $countryBilling = $customerAddressBilling->getCountry();

            if (Shopware()->Session()->checkoutShippingAddressId > 0 && Shopware()->Session()->checkoutShippingAddressId != Shopware()->Session()->checkoutBillingAddressId) {
                $customerAddressShipping = $addressModel->findOneBy(array('id' => Shopware()->Session()->checkoutShippingAddressId));
                $countryDelivery = $customerAddressShipping->getCountry();
            } else {
                $countryDelivery = $countryBilling;
            }
        } else {

            $countryBilling = $wrappedUser->getBillingCountry();
            $countryDelivery = $wrappedUser->getShippingCountry();

            if (is_null($countryDelivery)) {
                $countryDelivery = $countryBilling;
            }
        }

        //get current shopId
        $shopId = Shopware()->Shop()->getId();

        $backend = false;
        $config = $this->getRatePayPluginConfigByCountry($shopId, $countryBilling, $backend);
        foreach ($config AS $payment => $data) {
            $show[$payment] = $data['status'] == 2 ? true : false;

            $validation = new \Shopware_Plugins_Frontend_RpayRatePay_Component_Validation($user);

            $validation->setAllowedCurrencies($data['currency']);
            $validation->setAllowedCountriesBilling($data['country-code-billing']);
            $validation->setAllowedCountriesDelivery($data['country-code-delivery']);

            if ($validation->isRatepayHidden()) {
                $show[$payment] = false;
                continue;
            }

            if (!$validation->isCurrencyValid($currency)) {
                $show[$payment] = false;
                continue;
            }

            if (!$validation->isBillingCountryValid($countryBilling)) {
                $show[$payment] = false;
                continue;
            }

            if (!$validation->isDeliveryCountryValid($countryDelivery)) {
                $show[$payment] = false;
                continue;
            }

            if (!$validation->isBillingAddressSameLikeShippingAddress()) {
                if (!$data['address']) {
                    $shop[$payment] = false;
                    continue;
                }
            }

            if (Shopware()->Modules()->Basket()) {
                $basket = Shopware()->Modules()->Basket()->sGetAmount();
                $basket = $basket['totalAmount']; //is this always brutto?

                Shopware()->Pluginlogger()->info('BasketAmount: ' . $basket);
                $isB2b = $validation->isCompanyNameSet();

                if (!ValidationService::areAmountsValid($isB2b,$data, $basket)) {
                    $show[$payment] = false;
                    continue;
                }
            }
        }

        $paymentModel = Shopware()->Models()->find('Shopware\Models\Payment\Payment', $user->getPaymentId());
        $setToDefaultPayment = false;

        $payments = array();
        foreach ($return as $payment) {
            if ($payment['name'] === 'rpayratepayinvoice' && !$show['invoice']) {
                Shopware()->Pluginlogger()->info('RatePAY: Filter RatePAY-Invoice');
                $setToDefaultPayment = $paymentModel->getName() === "rpayratepayinvoice" ? : $setToDefaultPayment;
                continue;
            }
            if ($payment['name'] === 'rpayratepaydebit' && !$show['debit']) {
                Shopware()->Pluginlogger()->info('RatePAY: Filter RatePAY-Debit');
                $setToDefaultPayment = $paymentModel->getName() === "rpayratepaydebit" ? : $setToDefaultPayment;
                continue;
            }
            if ($payment['name'] === 'rpayratepayrate' && !$show['installment']) {
                Shopware()->Pluginlogger()->info('RatePAY: Filter RatePAY-Rate');
                $setToDefaultPayment = $paymentModel->getName() === "rpayratepayrate" ? : $setToDefaultPayment;
                continue;
            }
            if ($payment['name'] === 'rpayratepayrate0' && !$show['installment0']) {
                Shopware()->Pluginlogger()->info('RatePAY: Filter RatePAY-Rate0');
                $setToDefaultPayment = $paymentModel->getName() === "rpayratepayrate0" ? : $setToDefaultPayment;
                continue;
            }
            $payments[] = $payment;
        }

        if ($setToDefaultPayment) {
            $user->setPaymentId(Shopware()->Config()->get('paymentdefault'));
            Shopware()->Models()->persist($user);
            Shopware()->Models()->flush();
            Shopware()->Models()->refresh($user);
        }

        return $payments;
    }

    /**
     * Get ratepay plugin config from rpay_ratepay_config table
     *
     * @param $shopId
     * @param $country
     * @return array
     */
    private function getRatePayPluginConfigByCountry($shopId, $country, $backend = false) {

        $configLoader = new ConfigLoader(Shopware()->Db());

        $payments = array("installment", "invoice", "debit", "installment0");
        $paymentConfig = array();

        foreach ($payments AS $payment) {
            $result = $configLoader->getPluginConfigForPaymentType($shopId, $country->getIso(), $payment, $backend);

            if (!empty($result)) {
                $paymentConfig[$payment] = $result;
            }
        }

        return $paymentConfig;
    }


}