<?php
namespace Aymakan\Carrier\Model\System\Config;
use Magento\Framework\Data\OptionSourceInterface;

class ShippingCost implements OptionSourceInterface
{
    /**
     * Get shipping options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'aymakan', 'label' => __('Aymakan Cost')],
            ['value' => 'free', 'label' => __('Free Cost')],
            ['value' => 'custom', 'label' => __('Custom Cost')],
        ];
    }
}
