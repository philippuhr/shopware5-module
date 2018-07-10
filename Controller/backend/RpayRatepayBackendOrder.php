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
 * RpayRatepayBackendOrder
 *
 * @category   RatePAY
 * @copyright  Copyright (c) 2013 RatePAY GmbH (http://www.ratepay.com)
 */

use RpayRatePay\Component\Service\Validation;

class Shopware_Controllers_Backend_RpayRatepayBackendOrder extends Shopware_Controllers_Backend_ExtJs
{

    private function getSnippet($namespace, $name, $default)
    {
        $ns = Shopware()->Snippets()->getNamespace($namespace);
        return $ns->get($name, $default);
    }

    public function prevalidateAction()
    {
        $params = $this->Request()->getParams();
        $customerId = $params['customerId'];
        $customer = Shopware()->Models()->find('Shopware\Models\Customer\Customer', $customerId);

        $validations = [];

        if (!Validation::isBirthdayValid($customer)) {
            $validations[] = $this->getSnippet("RatePAY/backend/backend_orders","birthday_not_valid", "Geburtstag nicht gültig.");
        }

        if (!Validation::isTelephoneNumberSet($customer)) {
            $validations[] = $this->getSnippet("RatePAY/backend/backend_orders","telephone_not_set",  "Kunden-Telefonnummer nicht gesetzt.");
        }

        if (count($validations) == 0) {
            $this->view->assign([
                'success' => true,
            ]);
        } else {
            $this->view->assign([
                'success' => false,
                'messages' => $validations
            ]);
        }
    }
}
