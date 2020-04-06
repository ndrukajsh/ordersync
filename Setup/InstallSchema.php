<?php
/**
 */
namespace Seizera\MageSync\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $tableName = $setup->getTable('category_sync_log');

        if ($setup->getConnection()->isTableExists($tableName) != true) {
            $table = $setup->getConnection()
                ->newTable($tableName)
                ->addColumn(
                    'entity_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'primary'  => true,
                        'nullable' => false
                    ]
                )->addColumn(
                    'sync_time',
                    Table::TYPE_TEXT,
                    255,
                    [
                        'unsigned' => true,
                        'nullable' => false
                    ]
                )->addColumn(
                    'mage_category_id',
                    Table::TYPE_INTEGER,
                    15,
                    [
                        'nullable' => false
                    ]
                )->addColumn(
                    'mage_client_category_id',
                    Table::TYPE_INTEGER,
                    15,
                    [
                        'nullable' => false
                    ]
                )->addColumn(
                    'sync_type',
                    Table::TYPE_TEXT,
                    255,
                    [
                        'unsigned' => true,
                        'nullable' => false
                    ]
                )->setComment('Category Sync Log');
            $setup->getConnection()->createTable($table);
        }

        $tableName = $setup->getTable('product_sync_log');

        if ($setup->getConnection()->isTableExists($tableName) != true) {
            $table = $setup->getConnection()
                ->newTable($tableName)
                ->addColumn(
                    'entity_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'primary'  => true,
                        'nullable' => false
                    ]
                )->addColumn(
                    'sync_time',
                    Table::TYPE_TEXT,
                    255,
                    [
                        'unsigned' => true,
                        'nullable' => false
                    ]
                )->addColumn(
                    'mage_product_id',
                    Table::TYPE_INTEGER,
                    15,
                    [
                        'nullable' => false
                    ]
                )->addColumn(
                    'mage_client_product_id',
                    Table::TYPE_INTEGER,
                    15,
                    [
                        'nullable' => false
                    ]
                )->addColumn(
                    'client_id',
                    Table::TYPE_INTEGER,
                    15,
                    [
                        'nullable' => false
                    ]
                )->addColumn(
                    'sync_type',
                    Table::TYPE_TEXT,
                    255,
                    [
                        'unsigned' => true,
                        'nullable' => false
                    ]
                )->setComment('Category Sync Log');
            $setup->getConnection()->createTable($table);
        }
    }
}
