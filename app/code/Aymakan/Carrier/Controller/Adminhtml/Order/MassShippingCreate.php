<?php
namespace Aymakan\Carrier\Controller\Adminhtml\Order;

use Aymakan\Carrier\Helper\Api;
use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction;
use Magento\Sales\Model\Convert\Order;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Shipping\Model\CarrierFactory;
use Magento\Ui\Component\MassAction\Filter;

class MassShippingCreate extends AbstractMassAction implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
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
     * @var Session
     */
    private $session;

    /**
     * @var Order
     */
    private $convertOrder;

    /**
     * @var CarrierFactory
     */
    private $carrierFactory;

    /**
     * @var \Magento\Sales\Model\Order\Shipment\TrackFactory
     */
    protected $trackFactory;

    /**
     * @param OrderManagementInterface|null $orderManagement
     */
    public function __construct(
        Context $context,
        TrackFactory $trackFactory,
        Filter $filter,
        Session $session,
        Order $convertOrder,
        CollectionFactory $collectionFactory,
        Api $api,
        CarrierFactory $carrierFactory,
        OrderManagementInterface $orderManagement = null
    ) {
        parent::__construct($context, $filter);
        $this->collectionFactory = $collectionFactory;
        $this->trackFactory = $trackFactory;
        $this->session = $session;
        $this->convertOrder = $convertOrder;
        $this->api = $api;
        $this->carrierFactory =  $carrierFactory;
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
            $getTracks = $order->getTracksCollection()->fetchItem();
            if ($getTracks) {
                $this->messageManager->addErrorMessage(__('Shipment already created for order #%1.', $order->getIncrementId()));
                continue;
            }

            $address = $order->getShippingAddress();

            $paymentMethod = $order->getPayment()->getMethod();
            $isCod = in_array($paymentMethod, ['cashondelivery', 'cod']) ? 1 : 0;

            $post['delivery_name'] = implode(' ', [isset($address['firstname']) ? $address['firstname'] : '', isset($address['lastname']) ? $address['lastname'] : '']);
            $post['delivery_email'] = isset($address['email']) ? $address['email'] : '';
            $post['delivery_address'] = implode(', ', [isset($address['street']) ? $address['street'] : '', isset($address['city']) ? $address['city'] : '']);
            $post['delivery_neighbourhood'] = '';
            $post['delivery_phone'] = isset($address['telephone']) ? $address['telephone'] : '';
            $post['delivery_postcode'] = isset($address['postcode']) ? $address['postcode'] : '';
            $post['delivery_city'] = $address->getCity();
            $post['delivery_country'] = 'SA';
            $post['reference'] = (string) $order->getIncrementId();
            $post['declared_value'] = $order->getGrandTotal();
            $post['is_cod'] = $isCod;
            $post['cod_amount'] = $isCod ? $order->getGrandTotal() : '';
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
                /* $this->messageManager->addErrorMessage(__('An unknown error occurred for #%1. Please check Aymakan log file for errors.', $order->getIncrementId()));*/
                continue;
            }

            try {
                $this->addShipment($order, $results);

                $trackingNumber = $results['shipping']['tracking_number'];
                $pdfLabel = $results['shipping']['pdf_label'];

                $url = '<a target="_blank" href="' . $pdfLabel . '">Print Shipping Label1</a>';
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

    /**
     * @param Shipment $shipment
     * @param array $trackingNumbers
     * @param string $carrierCode
     * @param string $carrierTitle
     *
     * @return void
     */
    private function addShipment($order, $results)
    {
        $aymakanShipment = $results['shipping'];
        $trackingNumber = $aymakanShipment['tracking_number'];
        $labelUrl = $aymakanShipment['pdf_label'];
        $requestedBy = $this->session->getUser()->getUserName();

        $convertOrder = $this->convertOrder;

        $shipment = $convertOrder->toShipment($order);

        foreach ($order->getAllItems() as $item) {
            if (!$item->getQtyToShip() || $item->getIsVirtual()) {
                continue;
            }
            $qtyShipped = $item->getQtyToShip();
            $shipmentItem = $convertOrder->itemToShipmentItem($item)->setQty($qtyShipped);
            $shipment->addItem($shipmentItem);
        }

        $shipment->register();
        $shipment->getOrder()->setIsInProcess(true);

        $url = '<a target="_blank" href="' . $labelUrl . '">Print Shipping Label</a>';
        $shipment->addComment(__('Shipment Tracking Number: %1 URL: %2 &nbsp; &nbsp; BY: %3', $trackingNumber, $url, $requestedBy), false, false);

        $shipment->addTrack(
            $this->trackFactory->create()
                ->setNumber($trackingNumber)
                ->setCarrierCode('aymakan_carrier')
                ->setTitle('Aymakan Tracking')
        );

        $carrierInstance = $this->carrierFactory->create('aymakan_carrier');
        if ($carrierInstance) {
            $carrierInstance->getTrackingInfo($trackingNumber);
        }

        $shipment->getOrder()->save();
        $shipment->save();
    }
}
