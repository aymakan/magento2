<?php
/**
 * File Name: AymakanShipping.php
 * Created by Altaf Hussain
 * User: Altaf Hussain
 * Description: Aymakan Shipping Class
 * Date: 19 July 2020
 * Copyright ©Aymakan, Inc. All rights reserved
 */

namespace Aymakan\Carrier\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;

class AymakanShipping extends AbstractCarrier implements CarrierInterface
{
    public function getAllowedMethods()
    {
        // TODO: Implement getAllowedMethods() method.
    }

    public function collectRates(RateRequest $request)
    {
        // TODO: Implement collectRates() method.
    }
}