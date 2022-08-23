<?php
/**
 * File Name: Index.php
 * Created By: Abdul Shakoor
 * User: Abdul Shakoor
 * Modified By: Altaf Hussain
 * Date: 30 January 2020
 * Copyright ©Aymakan, Inc. All rights reserved.
 */

namespace Aymakan\Carrier\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Sales\Model\Order;
use Magento\Shipping\Model\ShipmentNotifier;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\DB\Transaction;

use Aymakan\Carrier\Helper\Api;

class Index extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Aymaka_Carrier::shipping';
    /**
     * @var Api
     */
    private $api;
    /**
     * @var Order
     */
    private $order;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var \Magento\Sales\Model\Convert\Order
     */
    private $convertOrder;
    /**
     * @var ShipmentNotifier
     */
    private $shipmentNotifier;
    /**
     * @var Transaction
     */
    private $transaction;
    /**
     * @var Order\Shipment\Track
     */
    private $track;
    /**
     * @var ManagerInterface
     */
    private $messages;

    /**
     * @param Context $context
     * @param Order $order
     * @param Session $session
     * @param \Magento\Sales\Model\Convert\Order $convertOrder
     * @param ShipmentNotifier $shipmentNotifier
     * @param Transaction $transaction
     * @param Order\Shipment\Track $track
     * @param ManagerInterface $messages
     * @param Api $api
     */
    public function __construct(
        Context $context,
        Order $order,
        Session $session,
        \Magento\Sales\Model\Convert\Order $convertOrder,
        ShipmentNotifier $shipmentNotifier,
        Transaction $transaction,
        Order\Shipment\Track $track,
        ManagerInterface $messages,
        Api $api
    )
    {
        parent::__construct($context);
        $this->api = $api;
        $this->order = $order;
        $this->session = $session;
        $this->convertOrder = $convertOrder;
        $this->shipmentNotifier = $shipmentNotifier;
        $this->transaction = $transaction;
        $this->track = $track;
        $this->messages = $messages;
    }

    /**
     * Index action
     * @return Redirect
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $post = $this->getRequest()->getPostValue();

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$post)
            return $resultRedirect->setPath('*/*/');

        $this->order->loadByAttribute('entity_id', $post['id']);

        if (!isset($post['delivery_city']) || $post['delivery_city'] == "") {
            $this->messages->addErrorMessage('Please select delivery city.');
            return $this->_redirect($this->getUrl('sales/order/view/order_id/' . $post['id']));
            exit;
        }

        unset($post['form_key']);

        $post['requested_by'] = $this->session->getUser()->getUserName();
        $post['submission_date'] = date('Y-m-d H:i:s');
        $post['pickup_date'] = date('Y-m-d H:i:s');
        $post['delivery_date'] = date('Y-m-d H:i:s');
        $post['reference'] = (string) $this->order->getIncrementId();
        $results = $this->api->createShipment($post);
        if (isset($results['errors'])) {
            $messages = 'You have errors: ';

            foreach ($results['errors'] as $error) {
                $messages .= $error[0];
            }
            $this->messages->addErrorMessage($messages);
            return $this->_redirect($this->getUrl('sales/order/view/order_id/' . $post['id']));
            exit;
        }
        if (!isset($results['shipping']))
        {
            $this->messages->addErrorMessage('An unknown error occurred. Please check Aymakan log file for errors.');
            return $this->_redirect($this->getUrl('sales/order/view/order_id/' . $post['id']));
            exit;
        }
        $aymakanShipment = $results['shipping'];

        $trackingNumber = $aymakanShipment['tracking_number'];
        $labelUrl = $aymakanShipment['pdf_label'];

        $shipment = $this->convertOrder->toShipment($this->order);
        foreach ($this->order->getAllItems() as $item) {
            if (!$item->getQtyToShip() || $item->getIsVirtual())
                continue;

            $qtyShipped = $item->getQtyToShip();
            $shipmentItem = $this->convertOrder->itemToShipmentItem($item)->setQty($qtyShipped);
            $shipment->addItem($shipmentItem);
        }

        $shipment->register();
        $shipment->getOrder()->setIsInProcess(true);

        try {
            $url = '<a target="_blank" href="' . $labelUrl . '" target="_blank">Print Shipping Label</a>';

            $shipment->addComment('Shipment Tracking Number: ' . $trackingNumber . ' URL: ' . $url . ' &nbsp; &nbsp; BY: ' . $post['requested_by'], false, false);
            $shipment->save();
            $shipment->getOrder()->save();
            $shipment->save();

            $this->track->setShipment($shipment);
            $this->track->setNumber($trackingNumber);
            $this->track->setCarrierCode('custom');
            $this->track->setTitle('Aymakan');
            $this->track->setOrderId($post['id']);
            $this->track->save();
            $shipment->addTrack($this->track)->save();

            $this->order->addStatusHistoryComment('Shipment is created. Tracking Number: ' . $trackingNumber . ', Shipping Label: ' . $url);

            $this->messages->addSuccessMessage('Your shipment is created successfully. Tracking Number: ' . $trackingNumber);
            return $this->_redirect($this->getUrl('sales/order/view/order_id/' . $post['id']));
            exit;

        } catch (\Exception $e) {
            $this->messages->addErrorMessage($e->getMessage());
            return $this->_redirect($this->getUrl('sales/order/view/order_id/' . $post['id']));
            exit;
        }

    }
}
