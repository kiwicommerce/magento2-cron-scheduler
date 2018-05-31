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

namespace KiwiCommerce\CronScheduler\Controller\Adminhtml\Cron;

/**
 * Class LongJobChecker
 * @package KiwiCommerce\CronScheduler\Controller\Adminhtml\Cron
 */
class LongJobChecker extends \Magento\Backend\App\Action
{
    /**
     * @var \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory
     */
    public $scheduleCollectionFactory = null;

    /**
     * @var string
     */
    private $timePeriod = '- 3 hour';

    /**
     * Constant for killed status.
     */
    const STATUS_KILLED = 'killed';

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    public $dateTime;

    /**
     * Class constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory $scheduleCollectionFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory $scheduleCollectionFactory
    ) {
        $this->dateTime = $dateTime;
        $this->scheduleCollectionFactory = $scheduleCollectionFactory;
        parent::__construct($context);
    }

    /**
     * Execute action
     */
    public function execute()
    {
        $collection = $this->scheduleCollectionFactory->create();
        $time = strftime('%Y-%m-%d %H:%M:%S', $this->dateTime->gmtTimestamp($this->timePeriod));

        $jobs = $collection->addFieldToFilter('status', \Magento\Cron\Model\Schedule::STATUS_RUNNING)
            ->addFieldToFilter(
                'finished_at',
                ['null' => true]
            )
            ->addFieldToFilter(
                'executed_at',
                ['lt' => $time]
            )
            ->addFieldToSelect(['schedule_id','pid'])
            ->load();

        foreach ($jobs as $job) {
            $pid = $job->getPid();

            $finished_at = strftime('%Y-%m-%d %H:%M:%S', $this->dateTime->gmtTimestamp());
            if (function_exists('posix_getsid') && posix_getsid($pid) === false) {
                $job->setData('status', \Magento\Cron\Model\Schedule::STATUS_ERROR);
                $job->setData('messages', __('Execution stopped due to some error.'));
                $job->setData('finished_at', $finished_at);
            } else {
                posix_kill($pid, 9);
                $job->setData('status', self::STATUS_KILLED);
                $job->setData('messages', __('It is killed as running for longer period.'));
                $job->setData('finished_at', $finished_at);
            }
            $job->save();
        }
    }
}
