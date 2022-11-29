<?php
namespace Aymakan\Carrier\Controller\Adminhtml\Order;

use Aymakan\Carrier\Helper\Api;
use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction;
use Magento\Sales\Model\Convert\Order;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Shipping\Model\ShipmentNotifier;
use Magento\Ui\Component\MassAction\Filter;

class MassShippingCreate extends AbstractMassAction implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session

    const ADMIN_RESOURCE = 'Magento_Sales::cancel';
*/
    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;
    /**
     * @var Api
     */
    private $api;
    private $session;
    private $convertOrder;
    private $shipmentNotifier;
    private $track;
    private $transaction;

    /**
     * @param OrderManagementInterface|null $orderManagement
     */
    public function __construct(
        Context $context,
        Filter $filter,
        Session $session,
        Order $convertOrder,
        ShipmentNotifier $shipmentNotifier,
        Transaction $transaction,
        CollectionFactory $collectionFactory,
        Api $api,
        Track $track,
        OrderManagementInterface $orderManagement = null
    ) {
        parent::__construct($context, $filter);
        $this->collectionFactory = $collectionFactory;
        $this->session = $session;
        $this->convertOrder = $convertOrder;
        $this->shipmentNotifier = $shipmentNotifier;
        $this->api = $api;
        $this->transaction = $transaction;
        $this->track = $track;
        $orderManagement = $orderManagement ?: ObjectManager::getInstance()->get(
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
        foreach ($collection->getItems() as $order) {
            if (!$order->canShip()) {
                $this->messageManager->addErrorMessage(__('Shipment already created for order #%1.', $order->getIncrementId()));
                continue;
            }

            $address = $order->getShippingAddress();

            $post['delivery_name'] = implode(' ', [isset($address['firstname']) ? $address['firstname'] : '', isset($address['lastname']) ? $address['lastname'] : '']);
            $post['delivery_email'] = isset($address['email']) ? $address['email'] : '';
            $post['delivery_address'] = implode(', ', [isset($address['street']) ? $address['street'] : '', isset($address['city']) ? $address['city'] : '']);
            $post['delivery_neighbourhood'] = '';
            $post['delivery_phone'] = isset($address['telephone']) ? $address['telephone'] : '';
            $post['delivery_postcode'] = isset($address['postcode']) ? $address['postcode'] : '';
            $post['delivery_city'] = $address->getCity();
            $post['reference'] = (string) $order->getIncrementId();
            $post['declared_value'] = $order->getGrandTotal();
            $post['is_cod'] = $order->hasInvoices() ? 1 : 0;
            $post['cod_amount'] = $order->hasInvoices() ? $order->getGrandTotal() : '';
            $post['items'] = (int) $order->getTotalItemCount();
            $post['pieces'] = 1;
            $post['requested_by'] = $this->session->getUser()->getUserName();
            $post['submission_date'] = date('Y-m-d H:i:s');
            $post['pickup_date'] = date('Y-m-d H:i:s');
            $post['delivery_date'] = date('Y-m-d H:i:s');

            $results = $this->api->createShipment($post);

            if (isset($results['errors'])) {
                $messages = 'You have errors: ';
                foreach ($results['errors'] as $error) {
                    $messages .= $error[0];
                }
                $this->messageManager->addErrorMessage(__('%2, #%1.', $order->getIncrementId(), $messages));
            }

            if (!isset($results['shipping'])) {
                $this->messageManager->addErrorMessage(__('An unknown error occurred for #%1. Please check Aymakan log file for errors.', $order->getIncrementId()));
                continue;
            }

            $aymakanShipment = $results['shipping'];
            $trackingNumber = $aymakanShipment['tracking_number'];
            $labelUrl = $aymakanShipment['pdf_label'];

            $shipment = $this->convertOrder->toShipment($order);

            foreach ($order->getAllItems() as $item) {
                if (!$item->getQtyToShip() || $item->getIsVirtual()) {
                    continue;
                }
                $qtyShipped = $item->getQtyToShip();
                $shipmentItem = $this->convertOrder->itemToShipmentItem($item)->setQty($qtyShipped);
                $shipment->addItem($shipmentItem);
            }

            $shipment->register();
            $shipment->getOrder()->setIsInProcess(true);

            try {
                $url = '<a target="_blank" href="' . $labelUrl . '" target="_blank">Print Shipping Label</a>';
                $shipment->addComment(__('Shipment Tracking Number: %1 URL: %2 &nbsp; &nbsp; BY: %3', $trackingNumber, $url, $post['requested_by']), false, false);
                $shipment->save();
                $shipment->getOrder()->save();
                $shipment->save();

                $this->track->setShipment($shipment);
                $this->track->setNumber($trackingNumber);
                $this->track->setCarrierCode('custom');
                $this->track->setTitle('Aymakan');
                $this->track->setOrderId($order->getId());
                $this->track->save();
                $shipment->addTrack($this->track)->save();

                $order->addStatusHistoryComment(__('Shipment is created. Tracking Number: %1, Shipping Label: %2', $trackingNumber, $url));

                $this->messageManager->addSuccessMessage(__('Your shipment is created successfully. Tracking Number: %1', $trackingNumber));
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->getComponentRefererUrl());
        return $resultRedirect;
    }
}
