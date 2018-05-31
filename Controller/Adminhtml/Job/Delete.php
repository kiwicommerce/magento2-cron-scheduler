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
 * Class Delete
 * @package KiwiCommerce\CronScheduler\Controller\Adminhtml\Job
 */
class Delete extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    public $cacheTypeList;

    /**
     * @var string
     */
    protected $aclResource = "job_deletejob";

    /**
     * @var \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory
     */
    public $scheduleCollectionFactory = null;

    /**
     * @var \KiwiCommerce\CronScheduler\Model\Job
     */
    public $jobModel;

    /**
     * @var \KiwiCommerce\CronScheduler\Helper\Cronjob
     */
    public $jobHelper = null;

    /**
     * Class constructor
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory $scheduleCollectionFactory
     * @param \KiwiCommerce\CronScheduler\Helper\Cronjob $jobHelper
     * @param \KiwiCommerce\CronScheduler\Model\Job $jobModel
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory $scheduleCollectionFactory,
        \KiwiCommerce\CronScheduler\Helper\Cronjob $jobHelper,
        \KiwiCommerce\CronScheduler\Model\Job $jobModel
    ) {
        $this->cacheTypeList = $cacheTypeList;
        $this->jobHelper = $jobHelper;
        $this->scheduleCollectionFactory = $scheduleCollectionFactory;
        $this->jobModel = $jobModel;
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
        $jobcode = $this->getRequest()->getParam('job_code');
        $group = $this->getRequest()->getParam('group');

        if (!empty($jobcode) && !empty($group)) {
            if ($this->jobHelper->isXMLJobcode($jobcode, $group)) {
                $this->messageManager->addErrorMessage(__('The cron job can not be deleted.'));
            } else {
                $collection = $this->scheduleCollectionFactory->create();
                $collection->addFieldToFilter('job_code', $jobcode);

                foreach ($collection as $job) {
                    $job->delete();
                }

                $this->jobModel->deleteJob($group, $jobcode);

                $this->cacheTypeList->cleanType('config');
                $this->messageManager->addSuccessMessage(__('A total of 1 record(s) have been deleted.'));
            }
        }

        return $this->_redirect('*/*/listing');
    }
}
