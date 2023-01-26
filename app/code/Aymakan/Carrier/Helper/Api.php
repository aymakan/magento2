<?php
/**
 * File Name: Api.php
 * Created by Altaf Hussain
 * User: Altaf Hussain
 * Description: Aymakan API v2 Integration class
 * Date: 29 January 2020
 * Copyright ©Aymakan, Inc. All rights reserved
 */

namespace Aymakan\Carrier\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Psr\Log\LoggerInterface;

class Api extends AbstractHelper
{
    /**
     * var $endPoint
     */
    private $endPoint = '';

    /**
     * var testingUrl
     */
    protected $testingUrl = 'https://dev-api.aymakan.com.sa/v2';

    /**
     * var liveUrl
     */
    protected $liveUrl = 'https://aymakan.com.sa/api/v2';

    /**
     * var apiKey
     */
    private $apiKey = '';

    private $logger;

    /**
     * Api constructor.
     * @param Context $context
     */
    public function __construct(Context $context, LoggerInterface $logger)
    {
        parent::__construct($context);
        $this->logger = $logger;
        $this->apiKey = $this->scopeConfig->getValue('carriers/aymakan_carrier/api_key');
        $isTesting    = $this->scopeConfig->getValue('carriers/aymakan_carrier/testing');
        if ($isTesting) {
            $this->endPoint = $this->testingUrl;
        } else {
            $this->endPoint = $this->liveUrl;
        }
    }

    /**
     * Get the list of available cities from Ayamakan. It provides both English and Arabic city names.
     * @return array|bool
     */
    public function getCities()
    {
        $url    = $this->endPoint . '/cities';
        $cities = $this->makeCall($url);
        return $cities['cities'];
    }

    /**
     * Creates a shipment
     * @param array $data
     * @return array|bool
     */
    public function createShipment($data)
    {
        $data['collection_name']          = $this->scopeConfig->getValue('carriers/aymakan_carrier/collection_name');
        $data['collection_email']         = $this->scopeConfig->getValue('carriers/aymakan_carrier/collection_email');
        $data['collection_city']          = $this->scopeConfig->getValue('carriers/aymakan_carrier/collection_city');
        $data['collection_address']       = $this->scopeConfig->getValue('carriers/aymakan_carrier/collection_address');
        $data['collection_neighbourhood'] = $this->scopeConfig->getValue('carriers/aymakan_carrier/collection_region');
        $data['collection_postcode']      = "";
        $data['collection_country']       = "SA";
        $data['collection_phone']         = $this->scopeConfig->getValue('carriers/aymakan_carrier/collection_phone');
        $data['collection_description']   = " ";

        $data['cod_amount'] = (isset($data['is_cod']) && $data['is_cod'] !== '0') ? $data['cod_amount'] : 0;

        $url = $this->endPoint . '/shipping/create';
        return $this->makeCall($url, $data, 'POST');
    }

    public function createBulkAwb($trackingNumber)
    {
        if (!is_array($trackingNumber) && !$trackingNumber) {
            return ['message' => __('Invalid Tracking Number Argument (%1)', $trackingNumber)];
        }
        $url = $this->endPoint . '/shipping/bulk_awb/trackings/' . implode(',', $trackingNumber);
        return $this->makeCall($url);
    }

    /**
     * Make a call to Aymakan API
     * @param $url
     * @param null $data
     * @param string $type
     * @return array|bool
     */
    private function makeCall($url, $data = null, string $type = 'GET', $header = [])
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $ch = curl_init();

        if (isset($data) and !empty($data)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $headers = array_merge([
            'Accept: application/json',
            'Authorization: ' . $this->apiKey
        ], $header);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);

        $response = curl_exec($ch);

        curl_close($ch);

        $result = json_decode($response, true);
        if (isset($result) and isset($result['errors'])) {
            return $result;
        }

        if (!isset($result['data'])) {
            $this->log($result);
            return $result;
        }
        return $result['data'];
    }

    protected function log($data)
    {
        $this->logger->error('Error: ' . json_encode($data));
    }

    /**
     * Check if the module is enabled or not.
     * @return bool
     */
    public function isEnabled()
    {
        if ($this->scopeConfig->getValue('carriers/aymakan_carrier/active')) {
            return true;
        }
        return false;
    }

    public function getPricing($cityCollection, $cityDeliver, $weight, $insurance = 0, $declaredValue = 0)
    {
        try {
            $headers[] = 'X-API-KEY: DGD*pwY8Cnmr+a6&5nLDJhKnjt6=ZC';

            $data = [
                'service' => 'delivery',
                'delivery_city' => $cityDeliver,
                'collection_city' => $cityCollection,
                'weight' => $weight,
                'insurance' => (int)$insurance,
                'declared_amount' => (int)$declaredValue
            ];

            $url = $this->endPoint . '/service/price';

            return $this->makeCall($url, $data, 'POST', $headers);

        } catch (\Exception $exception) {
            $this->log($exception->getMessage());
        }
    }
}
