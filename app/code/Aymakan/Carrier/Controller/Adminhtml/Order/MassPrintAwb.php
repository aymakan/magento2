<?php

namespace Aymakan\Carrier\Controller\Adminhtml\Order;

use Aymakan\Carrier\Helper\Api;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;

class MassPrintAwb extends AbstractMassAction implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * const ADMIN_RESOURCE = 'Magento_Sales::cancel';
     */
    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;
    /**
     * @var Api
     */
    private $api;

    /**
     * @param OrderManagementInterface|null $orderManagement
     */
    public function __construct(
        Context                  $context,
        Filter                   $filter,
        CollectionFactory        $collectionFactory,
        ResourceConnection       $connection,
        Api                      $api,
        OrderManagementInterface $orderManagement = null
    ) {
        parent::__construct($context, $filter);
        $this->collectionFactory = $collectionFactory;
        $this->api               = $api;
        $orderManagement         = $orderManagement ?: ObjectManager::getInstance()->get(
            OrderManagementInterface::class
        );
    }

    /**
     * Cancel selected orders
     *
     * @param AbstractCollection $collection
     * @return Redirect
     */
    protected function massAction(AbstractCollection $collection)
    {
        $tracking = [];
        foreach ($collection->getItems() as $order) {
            $track = $order->getTracksCollection()->fetchItem();
            if (!$track) {
                continue;
            }

            $tracking[] = $track->getTrackNumber();
        }

        $bulkAwb = $this->api->createBulkAwb($tracking);

        $resultRedirect = $this->resultRedirectFactory->create();

        if (isset($bulkAwb['bulk_awb_url'])) {
            $path = $bulkAwb['bulk_awb_url'];
        } else {
            $path = $this->getComponentRefererUrl();
        }

        if (isset($bulkAwb['error']) || isset($bulkAwb['errors']) || isset($bulkAwb['message'])) {
            $this->messageManager->addErrorMessage($bulkAwb['message']);
        }

        $resultRedirect->setPath($path);
        return $resultRedirect;
    }
}
