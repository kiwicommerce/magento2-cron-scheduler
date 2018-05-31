<?php
/**
 * KiwiCommerce
 *
 * Do not edit or add to this file if you wish to upgrade to newer versions in the future.
 * If you wish to customise this module for your needs.
 * Please contact us https://kiwicommerce.co.uk/contacts.
 *
 * @category   KiwiCommerce
 * @package    KiwiCommerce_CronScheduler
 * @copyright  Copyright (C) 2018 Kiwi Commerce Ltd (https://kiwicommerce.co.uk/)
 * @license    https://kiwicommerce.co.uk/magento2-extension-license/
 */

namespace KiwiCommerce\CronScheduler\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class InstallSchema
 * @package KiwiCommerce\CronScheduler\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $installer->getConnection()->addColumn($installer->getTable('cron_schedule'), 'pid', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            'length' => '100',
            'nullable' => true,
            'comment' => 'Process id of the cron'
        ]);

        $installer->getConnection()->addColumn($installer->getTable('cron_schedule'), 'memory_usage', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            'length' => '12,2',
            'nullable' => true,
            'comment' => 'Memory Usage of the Cron'
        ]);

        $installer->getConnection()->addColumn($installer->getTable('cron_schedule'), 'cpu_usage', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            'length' => '12,2',
            'nullable' => true,
            'comment' => 'CPU Usage of the Cron'
        ]);

        $installer->getConnection()->addColumn($installer->getTable('cron_schedule'), 'system_usage', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            'length' => '12,2',
            'nullable' => true,
            'comment' => 'System Usage of the Cron'
        ]);

        $installer->getConnection()->addColumn($installer->getTable('cron_schedule'), 'is_mail_sent', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
            'length' => 1,
            'nullable' => true,
            'comment' => 'Is mail sent for Missing Crons?'
        ]);

        $installer->getConnection()->addColumn($installer->getTable('cron_schedule'), 'error_message', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            'length' => 500,
            'nullable' => true,
            'comment' => 'FATAL Error/ Execption Message'
        ]);

        $installer->getConnection()->addColumn($installer->getTable('cron_schedule'), 'error_file', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            'length' => 500,
            'nullable' => true,
            'comment' => 'Error File Name'
        ]);

        $installer->getConnection()->addColumn($installer->getTable('cron_schedule'), 'error_line', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            'length' => 50,
            'nullable' => true,
            'comment' => 'Error Line Number'
        ]);

        $installer->endSetup();
    }
}
