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

namespace KiwiCommerce\CronScheduler\Controller\Adminhtml\Job;

/**
 * Class MassScheduleNow
 * @package KiwiCommerce\CronScheduler\Controller\Adminhtml\Job
 */
class MassScheduleNow extends \Magento\Backend\App\Action
{
    /**
     * @var \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory
     */
    public $scheduleCollectionFactory = null;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    public $timezone;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    public $dateTime;

    /**
     * @var \KiwiCommerce\CronScheduler\Helper\Schedule
     */
    public $scheduleHelper = null;

    /**
     * @var \KiwiCommerce\CronScheduler\Helper\Cronjob
     */
    public $jobHelper = null;

    /**
     * @var string
     */
    protected $aclResource = "job_massschedule";

    /**
     * Class constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory $scheduleCollectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \KiwiCommerce\CronScheduler\Helper\Schedule $scheduleHelper
     * @param \KiwiCommerce\CronScheduler\Helper\Cronjob $jobHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory $scheduleCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \KiwiCommerce\CronScheduler\Helper\Schedule $scheduleHelper,
        \KiwiCommerce\CronScheduler\Helper\Cronjob $jobHelper
    ) {
        $this->scheduleCollectionFactory = $scheduleCollectionFactory;
        $this->timezone = $timezone;
        $this->dateTime = $dateTime;
        $this->scheduleHelper = $scheduleHelper;
        $this->jobHelper = $jobHelper;

        parent::__construct($context);
    }

    /**
     * Is action allowed?
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('KiwiCommerce_CronScheduler::'.$this->aclResource);
    }

    /**
     * Execute action
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        if (isset($data['selected'])) {
            $jobCodes = $data['selected'];
        } elseif (!isset($data['selected']) && isset($data['excluded'])) {
            $filters = $data['filters'];
            unset($filters['placeholder']);
            $jobCodes = $this->jobHelper->getAllFilterJobCodes($filters);
        }

        if (empty($jobCodes)) {
            $this->messageManager->addErrorMessage(__('Selected jobs can not be scheduled now.'));
            return $this->_redirect('*/*/listing');
        }

        try {
            foreach ($jobCodes as $jobCode) {
                $job_status = $this->jobHelper->isJobActive($jobCode);
                if ($job_status) {
                    $collection = $this->scheduleCollectionFactory->create()->getNewEmptyItem();

                    $magentoVersion = $this->scheduleHelper->getMagentoversion();
                    if (version_compare($magentoVersion, "2.2.0") >= 0) {
                        $createdAt  = strftime('%Y-%m-%d %H:%M:%S', $this->dateTime->gmtTimestamp());
                        $scheduleAt = strftime('%Y-%m-%dT%H:%M:%S', $this->dateTime->gmtTimestamp());
                    } else {
                        $createdAt  = strftime('%Y-%m-%d %H:%M:%S', $this->timezone->scopeTimeStamp());
                        $scheduleAt = strftime('%Y-%m-%dT%H:%M:%S', $this->timezone->scopeTimeStamp());
                    }

                    $collection->setData('job_code', $jobCode);
                    $collection->setData('status', \Magento\Cron\Model\Schedule::STATUS_PENDING);
                    $collection->setData('created_at', $createdAt);
                    $collection->setData('scheduled_at', $this->scheduleHelper->filterTimeInput($scheduleAt));
                    $collection->save();
                    $success[] = $jobCode;
                }
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->_redirect('*/*/listing');
        }

        if (isset($success) && !empty($success)) {
            $this->messageManager->addSuccessMessage(__('You scheduled selected jobs now.'));
        }

        return $this->_redirect('*/*/listing');
    }
}
