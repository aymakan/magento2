<?php


namespace Aymakan\Carrier\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Store\Model\StoreManagerInterface;

class CollectionAddress extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param string|null $connectionName
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        string $connectionName = null
    ) {
        $this->_storeManager = $storeManager;
        parent::__construct($context, $connectionName);

    }

    protected function _construct()
    {
        $this->_init('aymakan_collection_address', 'address_id');
    }
}
