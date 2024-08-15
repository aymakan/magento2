<?php

namespace Aymakan\Carrier\Plugin;

use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Backend\Block\Widget\Button\Toolbar\Interceptor;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Sales\Model\Order;
use Magento\Framework\App\RequestInterface;

class PluginBefore
{
    /**
     * @var Order
     */
    private $order;

    /**
     * @var ScopeConfigInterface
     */
    private $storeConfig;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * PluginBefore constructor.
     * @param ScopeConfigInterface $storeConfig
     * @param Order $order
     * @param RequestInterface $request
     */
    public function __construct(
        ScopeConfigInterface $storeConfig,
        Order $order,
        RequestInterface $request
    ) {
        $this->order = $order;
        $this->storeConfig = $storeConfig;
        $this->request = $request;
    }

    /**
     * @param Interceptor $interceptor
     * @param AbstractBlock $block
     * @param ButtonList $buttonList
     */
    public function beforePushButtons(
        Interceptor $interceptor,
        AbstractBlock $block,
        ButtonList $buttonList
    ) {
        $orderId = $this->request->getParam('order_id');
        $this->order->loadByAttribute('entity_id', $orderId);
        $canShip = $this->order->canShip();
        if ($this->request->getFullActionName() === 'sales_order_view'
            && $this->storeConfig->getValue('carriers/aymakan_carrier/active') == 1
            && $canShip) {
            $buttonList->add(
                'aymakanButton',
                ['label' => __('Create Aymakan Shipping'), 'class' => 'reset'],
                -1
            );
        }
    }
}
