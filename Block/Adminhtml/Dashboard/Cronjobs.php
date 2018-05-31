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

namespace KiwiCommerce\CronScheduler\Block\Adminhtml\Dashboard;

/**
 * Class Cronjobs
 * @package KiwiCommerce\CronScheduler\Block\Adminhtml\Dashboard
 */
class Cronjobs extends \Magento\Backend\Block\Template
{
    /**
     * @var \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory
     */
    public $scheduleCollectionFactory = null;

    /**
     * Dashboard enable/disable status
     */
    const XML_PATH_DASHBOARD_ENABLE_STATUS = 'cronscheduler/general/cronscheduler_dashboard_enabled';

    /**
     * Display total records on dashboard
     */
    const TOTAL_RECORDS_ON_DASHBOARD = 5;

    /**
     * Class constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory $scheduleCollectionFactory
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory $scheduleCollectionFactory
    ) {
        $this->scheduleCollectionFactory = $scheduleCollectionFactory;
        parent::__construct($context);
    }

    /**
     * Get Top running jobs
     *
     * @return \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\Collection
     */
    public function getTopRunningJobs()
    {
        $collection = $this->scheduleCollectionFactory->create();

        $collection->addFieldToFilter('status', \Magento\Cron\Model\Schedule::STATUS_SUCCESS)
            ->addExpressionFieldToSelect(
                'timediff',
                'TIME_TO_SEC(TIMEDIFF(`finished_at`, `executed_at`))',
                []
            )
            ->setOrder('TIME_TO_SEC(TIMEDIFF(`finished_at`, `executed_at`))', 'DESC')
            ->setPageSize(self::TOTAL_RECORDS_ON_DASHBOARD)
            ->load();

        return $collection;
    }

    /**
     * Check store configuration value for dashboard
     * @return mixed
     */
    public function isDashboardActive()
    {
        $dashboardEnableStatus = $this->_scopeConfig->getValue(self::XML_PATH_DASHBOARD_ENABLE_STATUS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        return $dashboardEnableStatus;
    }
}
