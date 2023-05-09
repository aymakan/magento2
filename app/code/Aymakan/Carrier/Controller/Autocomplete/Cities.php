<?php

namespace Aymakan\Carrier\Controller\Autocomplete;

use Aymakan\Carrier\Helper\Api;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Locale\ResolverInterface;

class Cities extends Action
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var JsonFactory
     */
    private $cities;

    protected $localeResolver;

    /**
     * Autocomplete constructor.
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        ResolverInterface $localeResolver,
        JsonFactory $resultJsonFactory,
        Api $api
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->localeResolver = $localeResolver;
        $this->cities = $api->getCities();
        parent::__construct($context);
    }

    /**
     * Execute AJAX autocomplete request
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $term = $this->getRequest()->getParam('term');

        $filteredCities = array_filter($this->cities, function ($city) use ($term) {
            // Filter based on the English city name or the Arabic city name
            return stripos($city['city_en'], $term) !== false || stripos($city['city_ar'], $term) !== false;
        });

        $results = [];
        $localeCode = $this->localeResolver->getLocale();

        foreach ($filteredCities as $city) {
            if ($localeCode === 'ar_SA') {
                $results[] = [
                    'value' => $city['city_en'],
                    'label' => $city['city_ar'],
                ];
            } else {
                $results[] = [
                    'value' => $city['city_en'],
                    'label' => $city['city_en'],
                ];
            }
        }

        // Return the filtered cities as JSON response
        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setBody(json_encode($results));
    }
}
