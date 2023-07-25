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

use Aymakan\Carrier\Helper\Api;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Rate\Result;

class AymakanShipping extends AbstractCarrier implements \Magento\Shipping\Model\Carrier\CarrierInterface
{
    /**
     * @var string
     */
    protected $_code = 'aymakan_carrier';

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    protected $_rateMethodFactory;

    /**
     * @var \Magento\Shipping\Model\Tracking\ResultFactory
     */
    protected $_trackFactory;

    /**
     * @var \Magento\Shipping\Model\Tracking\Result\StatusFactory
     */
    protected $_trackStatusFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Api
     */
    private $api;

    /**
     * Shipping constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface          $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory  $rateErrorFactory,
        \Psr\Log\LoggerInterface                                    $logger,
        \Magento\Shipping\Model\Rate\ResultFactory                  $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Shipping\Model\Tracking\ResultFactory              $trackFactory,
        \Magento\Shipping\Model\Tracking\Result\StatusFactory       $trackStatusFactory,
        Api                                                         $api,
        array                                                       $data = []
    ) {
        $this->_rateResultFactory  = $rateResultFactory;
        $this->_rateMethodFactory  = $rateMethodFactory;
        $this->_trackFactory       = $trackFactory;
        $this->_trackStatusFactory = $trackStatusFactory;
        $this->api                 = $api;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * get allowed methods
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }

    public function getTrackingInfo($trackings)
    {
        $this->isTesting    = $this->scopeConfig->getValue('carriers/aymakan_carrier/testing');

        $result   = $this->_trackFactory->create();
        $tracking = $this->_trackStatusFactory->create();
        $tracking->setCarrier($this->_code);
        $tracking->setCarrierTitle('Aymakan');
        $tracking->setTracking($trackings);

        if ($this->isTesting) {
            $tracking->setUrl('https://dev.aymakan.com/track/' . $trackings);
        } else {
            $tracking->setUrl('https://aymakan.com/track/' . $trackings);
        }

        $result->append($tracking);

        return $tracking;
    }

    /**
     * @param RateRequest $request
     * @return bool|Result
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        // dd($request->getPackageWeight(), $request->getPackageValue());

        // $request->getPackageValue()

        $result = $this->_rateResultFactory->create();

        $pricing = $this->api->getPricing($this->getConfigData('collection_city'), $request->getDestCity(), 1);

        if (isset($pricing['total_price'])) {
            $method = $this->_rateMethodFactory->create();
            $method->setCarrier($this->_code);
            $method->setCarrierTitle($this->getConfigData('title'));
            $method->setMethod($this->_code);
            $method->setMethodTitle($this->getConfigData('name'));
            $method->setPrice($pricing['total_price']);
            $method->setCost($pricing['total_price']);
            $result->append($method);
        } else {
            $error = $this->_rateErrorFactory->create();
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage($this->getConfigData('title'));
            $error->setErrorMessage($this->getConfigData('specificerrmsg'));
            $result->append($error);
        }

        return $result;
    }
}
