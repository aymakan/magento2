<?php

namespace Aymakan\Carrier\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $table_aymakan_collection_address = $setup->getConnection()->newTable($setup->getTable('aymakan_collection_address'));

        $table_aymakan_collection_address->addColumn(
            'address_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true,'nullable' => false,'primary' => true,'unsigned' => true],
            'Entity ID'
        );

        $table_aymakan_collection_address->addColumn(
            'name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            [],
            'Collection Name'
        );

        $table_aymakan_collection_address->addColumn(
            'email',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            [],
            'Collection Email'
        );

        $table_aymakan_collection_address->addColumn(
            'phone',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            [],
            'Collection Phone'
        );

        $table_aymakan_collection_address->addColumn(
            'address',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            [],
            'Collection Address'
        );

        $table_aymakan_collection_address->addColumn(
            'city',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            [],
            'Collection City'
        );

        $table_aymakan_collection_address->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['default' => '\Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT','nullable' => false],
            'created_at'
        );

        $setup->getConnection()->createTable($table_aymakan_collection_address);
    }
}
