<?php


namespace Aymakan\Carrier\Model\ResourceModel\CollectionAddress;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Aymakan\Carrier\Model\CollectionAddress::class,
            \Aymakan\Carrier\Model\ResourceModel\CollectionAddress::class
        );
    }
}
