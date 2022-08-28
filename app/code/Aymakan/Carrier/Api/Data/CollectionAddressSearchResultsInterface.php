<?php


namespace Aymakan\Carrier\Api\Data;

interface CollectionAddressSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get CollectionAddress list.
     * @return \Aymakan\Carrier\Api\Data\CollectionAddressInterface[]
     */
    public function getItems();

    /**
     * Set brand list.
     * @param \Aymakan\Carrier\Api\Data\CollectionAddressInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
