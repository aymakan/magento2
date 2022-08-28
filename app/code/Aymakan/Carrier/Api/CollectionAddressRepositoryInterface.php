<?php


namespace Aymakan\Carrier\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface CollectionAddressRepositoryInterface
{

    /**
     * Save CollectionAddress
     * @param \Aymakan\Carrier\Api\Data\CollectionAddressInterface $collectionAddress
     * @return \Aymakan\Carrier\Api\Data\CollectionAddressInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Aymakan\Carrier\Api\Data\CollectionAddressInterface $collectionAddress
    );

    /**
     * Retrieve CollectionAddress
     * @param string $collectionaddressId
     * @return \Aymakan\Carrier\Api\Data\CollectionAddressInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($collectionaddressId);

    /**
     * Retrieve CollectionAddress matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Aymakan\Carrier\Api\Data\CollectionAddressSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete CollectionAddress
     * @param \Aymakan\Carrier\Api\Data\CollectionAddressInterface $collectionAddress
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Aymakan\Carrier\Api\Data\CollectionAddressInterface $collectionAddress
    );

    /**
     * Delete CollectionAddress by ID
     * @param string $collectionaddressId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($collectionaddressId);
}
