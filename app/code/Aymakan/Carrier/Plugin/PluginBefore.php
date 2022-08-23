<?php
/**
 * Created by Altaf Hussain.
 * User: Altaf Hussain
 * Date: 28 January 2020
 * Time: 9:46 AM
 */

namespace Aymakan\Carrier\Plugin;

use Magento\Backend\Block\Widget\Button\Toolbar\Interceptor;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\Order;


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
     * PluginBefore constructor.
     * @param ScopeConfigInterface $storeConfig
     * @param Order $order
     */
    public function __construct(ScopeConfigInterface $storeConfig, Order $order)
    {
        $this->order = $order;
        $this->storeConfig = $storeConfig;
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
    )
    {
        $this->_request = $block->getRequest();
        $this->order->loadByAttribute('entity_id', $this->_request->order_id);
        $canShip = $this->order->canShip();
        if ($this->_request->getFullActionName() == 'sales_order_view'
            AND $this->storeConfig->getValue('carriers/aymakan_carrier/active') == 1
            AND $canShip) {
            $buttonList->add(
                'aymakanButton',
                ['label' => __('Create Aymakan Shipping'), 'class' => 'reset'],
                -1
            );
        }
    }
}