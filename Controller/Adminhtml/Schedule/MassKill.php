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
namespace KiwiCommerce\CronScheduler\Controller\Adminhtml\Schedule;

/**
 * Class MassKill
 * @package KiwiCommerce\CronScheduler\Controller\Adminhtml\Schedule
 */
class MassKill extends \Magento\Backend\App\Action
{
    /**
     * Constant for status killed
     */
    const STATUS_KILLED = 'killed';

    /**
     * @var \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory
     */
    public $scheduleCollectionFactory = null;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    public $dateTime;

    /**
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    protected $filter;

    /**
     * @var string
     */
    protected $aclResource = "schedule_masskill";

    /**
     * Class constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory $scheduleCollectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory $scheduleCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Ui\Component\MassAction\Filter $filter
    ) {
        $this->scheduleCollectionFactory = $scheduleCollectionFactory;
        $this->dateTime = $dateTime;
        $this->filter = $filter;
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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        if (!isset($data['selected']) && isset($data['excluded'])) {
            $collection = $this->filter->getCollection($this->scheduleCollectionFactory->create());
            $ids = $collection->getAllIds();
        } else {
            $collection = $this->scheduleCollectionFactory->create();
            $ids = $data['selected'];
        }

        if (empty($ids)) {
            $this->messageManager->addErrorMessage(__('Selected jobs can not be killed.'));
            return $this->_redirect('*/*/listing');
        }

        try {
            $collection->addFieldToFilter('status', \Magento\Cron\Model\Schedule::STATUS_RUNNING)
                ->addFieldToFilter(
                    'finished_at',
                    ['null' => true]
                )
                ->addFieldToFilter(
                    'schedule_id',
                    ['in' => $ids]
                )
                ->addFieldToSelect(['schedule_id','pid'])
                ->load();

            $killedJobData = $collection->getData();
            $killedScheduleIds = $errorScheduleIds = [];
            $runningJobs = array_column($killedJobData, 'schedule_id');
            $errorScheduleIds = array_diff($ids, $runningJobs);

            foreach ($collection as $dbrunningjobs) {
                $pid = $dbrunningjobs->getPid();
                $scheduleId = $dbrunningjobs->getScheduleId();

                if (function_exists('posix_getsid') && posix_getsid($pid) === false) {
                    $errorScheduleIds[] = $scheduleId;
                } else {
                    $finished_at = strftime('%Y-%m-%d %H:%M:%S', $this->dateTime->gmtTimestamp());
                    $dbrunningjobs->setData('status', self::STATUS_KILLED);
                    $dbrunningjobs->setData('messages', __('It is killed by admin.'));
                    $dbrunningjobs->setData('finished_at', $finished_at);
                    $dbrunningjobs->save();

                    posix_kill($pid, 9);
                    $killedScheduleIds[] = $scheduleId;
                }
            }
            if (!empty($killedScheduleIds)) {
                $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been killed - ' . join(',', $killedScheduleIds), count($killedScheduleIds)));
            }
            if (!empty($errorScheduleIds)) {
                $this->messageManager->addErrorMessage(__('A total of %1 record(s) can not be killed - ' . join(',', $errorScheduleIds), count($errorScheduleIds)));
            }

            return $this->_redirect('*/*/listing');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->_redirect('*/*/listing');
        }
    }
}
