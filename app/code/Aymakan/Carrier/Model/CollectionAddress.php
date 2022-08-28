<?php

namespace Aymakan\Carrier\Model;

use Aymakan\Carrier\Api\Data\CollectionAddressInterface;
use Aymakan\Carrier\Api\Data\CollectionAddressInterfaceFactory;
use Aymakan\Carrier\Model\ResourceModel\CollectionAddress\Collection;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;

class CollectionAddress extends AbstractModel
{
    protected $collectionAddressDataFactory;

    protected $dataObjectHelper;

    protected $_eventPrefix = 'aymakan_collection_Address_collectionAddress';

    /**
     * @param Context $context
     * @param Registry $registry
     * @param CollectionAddressInterfaceFactory $collectionAddressDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param ResourceModel\CollectionAddress $resource
     * @param Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context  $context,
        Registry       $registry,
        CollectionAddressInterfaceFactory $collectionAddressDataFactory,
        DataObjectHelper                  $dataObjectHelper,
        ResourceModel\CollectionAddress   $resource,
        Collection                        $resourceCollection,
        array                             $data = []
    ) {
        $this->collectionAddressDataFactory = $collectionAddressDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve collectionAddress model with collectionAddress data
     * @return CollectionAddressInterface
     */
    public function getDataModel()
    {
        $collectionAddressData = $this->getData();

        $collectionAddressDataObject = $this->collectionAddressDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $collectionAddressDataObject,
            $collectionAddressData,
            CollectionAddressInterface::class
        );

        return $collectionAddressDataObject;
    }
}
