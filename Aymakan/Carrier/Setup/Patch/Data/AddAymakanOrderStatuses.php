<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Aymakan\Carrier\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Class AddAymakanOrderStates
 * @package Magento\Aymakan\Setup\Patch
 */
class AddAymakanOrderStatuses implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * AddAymakanOrderStates constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        /**
         * Prepare database for install
         */
        $this->moduleDataSetup->getConnection()->startSetup();

        $data     = [];

        $statuses = [
            'ay_awb_created' => __('AWB Created at Origin'),
            'ay_pickup_from_collection' => __('Picked from Collection Point'),
            'ay_received_warehouse' => __('Received at Warehouse'),
            'ay_out_for_delivery' => __('Out for Delivery'),
            'ay_not_delivered' => __('Not Delivered'),
            'ay_returned' => __('Returned'),
            'ay_in_transit' => __('In-Transit'),
            'ay_delayed' => __('Delayed'),
            'ay_cancelled' => __('Cancelled'),
            'ay_on_hold' => __('On Hold'),
        ];
        foreach ($statuses as $code => $info) {
            $data[] = ['status' => $code, 'label' => $info];
        }
        $this->moduleDataSetup->getConnection()->insertArray(
            $this->moduleDataSetup->getTable('sales_order_status'),
            ['status', 'label'],
            $data
        );
        /**
         * Prepare database after install
         */
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '2.0.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
