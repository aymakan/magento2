<?php

namespace Aymakan\Carrier\Block\Adminhtml\Order\View;

use Aymakan\Carrier\Helper\Api;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;

class Aymakan extends Generic
{
    protected $collectionAddressFactory;

    /**
     * @var Order
     */
    protected $order;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var EncoderInterface
     */
    protected $jsonEncoder;
    /**
     * @var Api
     */
    private $api;
    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param EncoderInterface $jsonEncoder
     * @param ScopeConfigInterface $scopeConfig
     * @param Order $order
     * @param CacheInterface $cache
     * @param Api $api
     * @param array $data
     */
    public function __construct(
        Context                                                                  $context,
        Registry                                                                 $registry,
        FormFactory                                                              $formFactory,
        EncoderInterface                                                         $jsonEncoder,
        ScopeConfigInterface                                                     $scopeConfig,
        Order                                                                    $order,
        CacheInterface                                                           $cache,
        Api                                                                      $api,
        \Aymakan\Carrier\Model\ResourceModel\CollectionAddress\CollectionFactory $collectionAddressFactory,
        array                                                                    $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->urlBuilder               = $context->getUrlBuilder();
        $this->jsonEncoder              = $jsonEncoder;
        $this->scopeConfig              = $scopeConfig;
        $this->collectionAddressFactory = $collectionAddressFactory->create();
        $this->order                    = $order->load($this->_request->getParam('order_id'));
        $this->setUseContainer(true);
        $this->api   = $api;
        $this->cache = $cache;
    }

    /**
     * Form preparation
     *
     * @return void
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        $form = $this->_formFactory->create([
            'data' => [
                'action' => $this->getUrl('aymakan'),
                'id' => 'aymakan_shipping_form',
                'class' => 'admin__scope-old',
                'enctype' => 'multipart/form-data',
                'method' => 'post'
            ]
        ]);

        $form->setUseContainer($this->getUseContainer());
        $form->addField('aymakan_modal_messages', 'note', []);
        $fieldset = $form->addFieldset('aymakan_shipping_form_fieldset_1', [
            'class' => 'fieldset-column',
            'legend' => __('Customer Address Information')
        ]);
        $fieldset->addField(
            '',
            'hidden',
            [
                'name' => 'form_key',
                'value' => $this->getFormKey(),
            ]
        );
        $fieldset->addField(
            'delivery_order_id',
            'hidden',
            [
                'name' => 'id',
                'value' => $this->order->getId(),
            ]
        );
        $fieldset->addField(
            'delivery_country',
            'hidden',
            [
                'name' => 'delivery_country',
                'value' => $this->getAddress()->getCountryId(),
            ]
        );
        $address = $this->getAddress();
        $fieldset->addField(
            'delivery_name',
            'text',
            [
                'class' => 'edited-data validate',
                'label' => __('Name'),
                'title' => __('Name'),
                'required' => true,
                'name' => 'delivery_name',
                'value' => $address->getFirstname() . ' ' . $address->getLastname(),
            ]
        );
        $fieldset->addField(
            'delivery_email validate-email',
            'text',
            [
                'class' => 'edited-data',
                'label' => __('Email'),
                'title' => __('Email'),
                'required' => true,
                'name' => 'delivery_email',
                'value' => $this->order->getCustomerEmail(),
            ]
        );
        $fieldset->addField(
            'delivery_address validate',
            'text',
            [
                'class' => 'edited-data',
                'label' => __('Address'),
                'title' => __('Address'),
                'required' => true,
                'name' => 'delivery_address',
                'value' => $this->getAddress()->getStreetLine(1),
            ]
        );
        $fieldset->addField(
            'delivery_neighbourhood',
            'text',
            [
                'class' => 'edited-data',
                'label' => __('Region'),
                'title' => __('Region'),
                'required' => true,
                'name' => 'delivery_neighbourhood',
                'value' => $this->getAddress()->getRegion(),
            ]
        );
        $fieldset->addField(
            'delivery_phone validate-phone',
            'text',
            [
                'class' => 'edited-data',
                'label' => __('Phone'),
                'title' => __('Phone'),
                'required' => true,
                'name' => 'delivery_phone',
                'value' => $this->getAddress()->getTelephone(),
            ]
        );
        $fieldset->addField(
            'delivery_postcode validate',
            'text',
            [
                'class' => 'edited-data',
                'label' => __('Postcode'),
                'title' => __('Postcode'),
                'name' => 'delivery_postcode',
                'value' => $this->getAddress()->getPostcode(),
            ]
        );
        $fieldset->addField(
            'delivery_city validate',
            'select',
            [
                'class' => 'edited-data',
                'label' => __('City'),
                'title' => __('City'),
                'required' => false,
                'name' => 'delivery_city',
                'value' => $this->order->getShippingAddress()->getCity(),
                'values' => $this->getCities(),
                'note' => 'Aymakan deliver to specific cities only. Each city has its specific namings as listed in Aymakan documentation.'
            ]
        );

        $fieldset->addField(
            'is_collection',
            'select',
            [
                'label' => __('Collection Address'),
                'class' => 'is_collection',
                'name' => 'is_collection',
                'values' => $this->getCollectionAddresses()
            ]
        );
        $fieldset = $form->addFieldset('aymakan_shipping_form_fieldset_2', [
            'class' => 'fieldset-column',
            'legend' => __('New Collection Address Information')
        ]);
        $this->collectionAddressFields($fieldset, $form->getHtmlIdPrefix());

        $fieldset = $form->addFieldset('aymakan_shipping_form_fieldset_3', [
            'class' => 'fieldset-column',
            'legend' => __('Shipping Information')
        ]);
        $fieldset->addField(
            'delivery_reference validate',
            'text',
            [
                'class' => 'edited-data',
                'label' => __('Reference'),
                'title' => __('Reference'),
                'required' => true,
                'name' => 'reference',
                'value' => $this->order->getIncrementId()
            ]
        );
        $fieldset->addField(
            'declared_value validate',
            'text',
            [
                'class' => 'edited-data',
                'label' => __('Order Total'),
                'title' => __('Order Total'),
                'required' => true,
                'name' => 'declared_value',
                'value' => $this->order->getGrandTotal()
            ]
        );
        $paymentMethodCode = $this->order->getPayment()->getMethodInstance()->getCode();
        $fieldset->addField(
            'is_cod validate',
            'select',
            [
                'class' => 'edited-data',
                'label' => __('Is COD?'),
                'title' => __('Is COD?'),
                'required' => false,
                'name' => 'is_cod',
                'value' => ($paymentMethodCode === 'cashondelivery') ? '1' : '0',
                'values' => ['0' => 'No', '1' => 'Yes'],
                'note' => 'If order is COD, then select Yes.'
            ]
        );
        $fieldset->addField(
            'cod_amount validate',
            'text',
            [
                'class' => 'edited-data',
                'label' => __('COD Amount'),
                'title' => __('COD Amount'),
                'required' => false,
                'name' => 'cod_amount',
                'value' => ($paymentMethodCode === 'cashondelivery') ? $this->order->getGrandTotal() : 0,
                'note' => 'If order is COD, then COD amount is the amount Aymakan driver will be collecting from your customer.'
            ]
        );
        $fieldset->addField(
            'deliver_items validate',
            'text',
            [
                'class' => 'edited-data',
                'label' => __('Items'),
                'title' => __('Items'),
                'required' => true,
                'name' => 'items',
                'value' => $this->getItemsCount(),
                'note' => 'Number of items in the shipment.'
            ]
        );
        $fieldset->addField(
            'deliver_pieces validate',
            'text',
            [
                'class' => 'edited-data',
                'label' => __('Pieces'),
                'title' => __('Pieces'),
                'required' => true,
                'name' => 'pieces',
                'note' => 'Pieces in the shipment. For example, for a large orders, the items can be boxed in multiple cartons. The number of boxed cartons should be entered here. ',
            ]
        );




        $this->setForm($form);
    }

    /**
     * @throws LocalizedException
     * @throws \JsonException
     */
    public function collectionAddressFields($fieldset, $htmlIdPrefix)
    {
        $fieldset->addField(
            'collection_name',
            'text',
            [
                'class' => 'edited-data',
                'label' => __('Collection Name'),
                'title' => __('Collection Name'),
                'required' => true,
                'name' => 'collection_name',
               // 'value' => $this->scopeConfig->getValue('carriers/aymakan_carrier/collection_name'),
            ]
        );

        $fieldset->addField(
            'collection_email validate-email',
            'text',
            [
                'class' => 'edited-data',
                'label' => __('Collection Email'),
                'title' => __('Collection Email'),
                'required' => true,
                'name' => 'collection_email',
               // 'value' => $this->scopeConfig->getValue('carriers/aymakan_carrier/collection_email'),
            ]
        );

        $fieldset->addField(
            'collection_phone',
            'text',
            [
                'class' => 'edited-data',
                'label' => __('Collection Phone'),
                'title' => __('Collection Phone'),
                'required' => true,
                'name' => 'collection_phone',
               // 'value' => $this->scopeConfig->getValue('carriers/aymakan_carrier/collection_phone'),
            ]
        );

        $fieldset->addField(
            'collection_address',
            'text',
            [
                'class' => 'edited-data',
                'label' => __('Collection Address'),
                'title' => __('Collection Address'),
                'required' => true,
                'name' => 'collection_address',
                // 'value' => $this->scopeConfig->getValue('carriers/aymakan_carrier/collection_address'),
            ]
        );

        $fieldset->addField(
            'collection_city',
            'select',
            [
                'class' => 'edited-data',
                'label' => __('Collection City'),
                'title' => __('Collection City'),
                'required' => false,
                'name' => 'collection_city',
              //  'value' => $this->scopeConfig->getValue('carriers/aymakan_carrier/collection_city'),
                'values' => $this->getCities(),
            ]
        );
        /*
        /*$this->setChild(
            'form_after',
            $this->getLayout()->createBlock(
                \Magento\Backend\Block\Widget\Form\Element\Dependence::class
            )
                ->addFieldMap("{$htmlIdPrefix}is_collection", 'is_collection')
                ->addFieldMap("{$htmlIdPrefix}collection_name", 'collection_name')
                ->addFieldMap("{$htmlIdPrefix}collection_email", 'collection_email')
                ->addFieldMap("{$htmlIdPrefix}collection_phone", 'collection_phone')
                ->addFieldMap("{$htmlIdPrefix}collection_address", 'collection_address')
                ->addFieldMap("{$htmlIdPrefix}collection_city", 'collection_city')
                ->addFieldDependence('collection_name', 'is_collection', 'new_collection')
                ->addFieldDependence('collection_email', 'is_collection', 'new_collection')
                ->addFieldDependence('collection_phone', 'is_collection', 'new_collection')
                ->addFieldDependence('collection_address', 'is_collection', 'new_collection')
                ->addFieldDependence('collection_city', 'is_collection', 'new_collection')
        );
        */
    }

    /**
     * Get widget options
     *
     * @return string
     */
    public function getWidgetOptions()
    {
        return $this->jsonEncoder->encode(
            [
                'saveVideoUrl' => $this->getUrl('catalog/product_gallery/upload'),
                'saveRemoteVideoUrl' => $this->getUrl('product_video/product_gallery/retrieveImage'),
                'htmlId' => $this->getHtmlId(),
            ]
        );
    }

    /**
     * @return \Magento\Sales\Api\Data\OrderAddressInterface|Order\Address|null
     */
    public function getAddress()
    {
        $shippingAddress = $this->order->getShippingAddress();
        return (!isset($shippingAddress)) ? $this->order->getBillingAddress() : $shippingAddress;
    }

    public function getItemsCount()
    {
        return (int)$this->order->getTotalQtyOrdered();
    }

    /**
     * @return array
     */
    public function getCollectionAddresses()
    {
        $addresses = $this->collectionAddressFactory->getData();

        $options = [
            'default_collection' => __('Default Collection Address'),
            'new_collection' => __('Add New Collection Address'),
        ];

        if (count($addresses) > 0) {
            foreach ($addresses as $address) {
                if (!isset($address['address_id'], $address['name'])) {
                    continue;
                }
                $options[] = ['value' => $address['address_id'], 'label' => '--'.$address['name']];
            }
        }

        return $options;
    }

    /**
     * @return array
     * @throws \JsonException
     */
    public function getCities()
    {
        $orderLocale = $this->scopeConfig->getValue('general/locale/code', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->order->getStore()->getStoreId());

        //  if ($this->scopeConfig->getValue('carriers/aymakan_carrier/city_ar'))
        if ($orderLocale == 'ar_SA') {
            $citiesKey = 'city_ar';
        } else {
            $citiesKey = 'city_en';
        }

        $fromCache = $this->cache->load($citiesKey);
        if (!$fromCache) {
            $cities  = $this->api->getCities();
            $options = [];

            if (count($cities) > 0) {
                foreach ($cities as $city) {
                    $options[$city[$citiesKey]] = addslashes($city[$citiesKey]);
                }
            }

            $this->cache->save(json_encode($options, JSON_THROW_ON_ERROR), $citiesKey);
            $fromCache = $this->cache->load($citiesKey);
        }

        return json_decode($fromCache, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
