<?php


namespace Aymakan\Carrier\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotSaveException;
use Aymakan\Carrier\Model\ResourceModel\CollectionAddress as ResourceCollectionAddress;
use Aymakan\Carrier\Api\Data\CollectionAddressSearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Aymakan\Carrier\Api\CollectionAddressRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Aymakan\Carrier\Api\Data\CollectionAddressInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Aymakan\Carrier\Model\ResourceModel\CollectionAddress\CollectionFactory as CollectionAddressCollectionFactory;
use Magento\Framework\Reflection\DataObjectProcessor;

class CollectionAddressRepository implements CollectionAddressRepositoryInterface
{

    protected $collectionAddressFactory;

    protected $resource;

    protected $extensionAttributesJoinProcessor;

    private $collectionProcessor;

    protected $dataObjectProcessor;

    protected $dataCollectionAddressFactory;

    private $storeManager;

    protected $collectionAddressCollectionFactory;

    protected $dataObjectHelper;

    protected $searchResultsFactory;

    protected $extensibleDataObjectConverter;

    /**
     * @param ResourceCollectionAddress $resource
     * @param CollectionAddressFactory $collectionAddressFactory
     * @param CollectionAddressInterfaceFactory $dataCollectionAddressFactory
     * @param CollectionAddressCollectionFactory $collectionAddressCollectionFactory
     * @param CollectionAddressSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        ResourceCollectionAddress $resource,
        CollectionAddressFactory $collectionAddressFactory,
        CollectionAddressInterfaceFactory $dataCollectionAddressFactory,
        CollectionAddressCollectionFactory $collectionAddressCollectionFactory,
        CollectionAddressSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->collectionAddressFactory = $collectionAddressFactory;
        $this->collectionAddressCollectionFactory = $collectionAddressCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataCollectionAddressFactory = $dataCollectionAddressFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \Aymakan\Carrier\Api\Data\CollectionAddressInterface $collectionAddress
    ) {
        /* if (empty($collectionAddress->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $collectionAddress->setStoreId($storeId);
        } */

        $collectionAddressData = $this->extensibleDataObjectConverter->toNestedArray(
            $collectionAddress,
            [],
            \Aymakan\Carrier\Api\Data\CollectionAddressInterface::class
        );

        $collectionAddressModel = $this->collectionAddressFactory->create()->setData($collectionAddressData);

        try {
            $this->resource->save($collectionAddressModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the collectionAddress: %1',
                $exception->getMessage()
            ));
        }
        return $collectionAddressModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getById($collectionAddressId)
    {
        $collectionAddress = $this->collectionAddressFactory->create();
        $this->resource->load($collectionAddress, $collectionAddressId);
        if (!$collectionAddress->getId()) {
            throw new NoSuchEntityException(__('Collection Address with id "%1" does not exist.', $collectionAddressId));
        }
        return $collectionAddress->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->collectionAddressCollectionFactory->create();

        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Aymakan\Carrier\Api\Data\CollectionAddressInterface::class
        );

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model->getDataModel();
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        \Aymakan\Carrier\Api\Data\CollectionAddressInterface $collectionAddress
    ) {
        try {
            $collectionAddressModel = $this->collectionAddressFactory->create();
            $this->resource->load($collectionAddressModel, $collectionAddress->getSizechartId());
            $this->resource->delete($collectionAddressModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the CollectionAddress: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($collectionAddressId)
    {
        return $this->delete($this->getById($collectionAddressId));
    }
}
