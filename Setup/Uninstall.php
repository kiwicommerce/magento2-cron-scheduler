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

/**
 * Class Uninstall
 * @package KiwiCommerce\CronScheduler\Setup
 */
class Uninstall implements \Magento\Framework\Setup\UninstallInterface
{
    /**
     * Module uninstall code
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     * @return void
     */
    public function uninstall(
        \Magento\Framework\Setup\SchemaSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context
    ) {
        $uninstaller = $setup;
        $uninstaller->startSetup();

        $uninstaller->getConnection()->dropColumn($uninstaller->getTable('cron_schedule'), 'pid', null);
        $uninstaller->getConnection()->dropColumn($uninstaller->getTable('cron_schedule'), 'memory_usage', null);
        $uninstaller->getConnection()->dropColumn($uninstaller->getTable('cron_schedule'), 'cpu_usage', null);
        $uninstaller->getConnection()->dropColumn($uninstaller->getTable('cron_schedule'), 'system_usage', null);
        $uninstaller->getConnection()->dropColumn($uninstaller->getTable('cron_schedule'), 'is_mail_sent', null);

        $uninstaller->endSetup();
    }
}
