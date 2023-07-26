<?php

namespace Aymakan\Carrier\Controller\Webhook;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\RemoteServiceUnavailableException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

class Status extends Action implements CsrfAwareActionInterface
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    public function __construct(
        Context $context,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        OrderRepositoryInterface $orderRepository,
        PageFactory $resultPageFactory
    ) {
        $this->_logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->orderRepository = $orderRepository;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Dispatch request
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->getRequest()->isPost() && !$this->scopeConfig->getValue('carriers/aymakan_carrier/active')) {
            return;
        }

        try {
            $data = json_decode($this->getRequest()->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $status = [
                'AY-0001' => 'ay_awb_created',
                'AY-0002' => 'ay_pickup_from_collection',
                'AY-0003' => 'ay_received_warehouse',
                'AY-0004' => 'ay_out_for_delivery',
                'AY-0006' => 'ay_not_delivered',
                'AY-0008' => 'ay_returned',
                'AY-0009' => 'ay_in_transit',
                'AY-00010' => 'ay_delayed',
                'AY-00011' => 'ay_cancelled',
                'AY-0032' => 'pending',
                'AY-0050' => 'ay_on_hold',
                'AY-0005' => 'complete',
            ];

            if (isset($data['status'], $status[$data['status']], $data['reference'])) {
                $orderId = (int)$data['reference'];
                $order = $this->orderRepository->get($orderId);

                $order->setStatus($status[$data['status']]);
                $order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE);

                $message = $data['reason'] ?? __('Order status is updated by Aymakan.');

                $history = $order->addCommentToStatusHistory($message, $order->getStatus());
                $history->setIsCustomerNotified(true);
                $this->orderRepository->save($order);
            }

        } catch (RemoteServiceUnavailableException $e) {
            $this->_logger->critical($e);
            $this->getResponse()->setStatusHeader(503, '1.1', 'Service Unavailable')->sendResponse();
            /** @todo eliminate usage of exit statement */
            // phpcs:ignore Magento2.Security.LanguageConstruct.ExitUsage
            exit;
        } catch (\Magento\Framework\Exception\CouldNotSaveException $e) {
            $this->_logger->critical($e);
            $this->getResponse()->setHttpResponseCode(500);
        } catch (Exception $e) {
            $this->_logger->critical($e);
            $this->getResponse()->setHttpResponseCode(500);
        }
    }
}
