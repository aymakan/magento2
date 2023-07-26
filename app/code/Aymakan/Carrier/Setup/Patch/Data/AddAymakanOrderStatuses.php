<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Aymakan\Carrier\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\ResourceModel\Order\Status;

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

    private $statusFactory;

    /**
     * @var Status $statusResourceModel
     */
    private $statusResourceModel;

    /**
     * AddAymakanOrderStates constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param StatusFactory $statusFactory
     * @param Status $statusResourceModel
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        StatusFactory $statusFactory,
        Status $statusResourceModel
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->statusFactory = $statusFactory;
        $this->statusResourceModel = $statusResourceModel;
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

        $states = [
            'ay_awb_created' => 'pending',
            'ay_pickup_from_collection' => 'processing',
            'ay_received_warehouse' => 'processing',
            'ay_out_for_delivery' => 'processing',
            'ay_not_delivered' => 'closed',
            'ay_returned' => 'closed',
            'ay_in_transit' => 'processing',
            'ay_delayed' => 'holded',
            'ay_cancelled' => 'canceled',
            'ay_on_hold' => 'holded',
        ];

        foreach ($statuses as $code => $info) {
            $status = $this->statusFactory->create();
            $status->setData([
                'status' => $code,
                'label' => $info
            ]);
            $this->statusResourceModel->save($status);
            $status->assignState($states[$code], false, true);
        }

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
