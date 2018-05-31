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
 * Class MassStatusDisable
 * @package KiwiCommerce\CronScheduler\Controller\Adminhtml\Job
 */
class MassStatusDisable extends \Magento\Backend\App\Action
{
    /**
     * @var \KiwiCommerce\CronScheduler\Helper\Schedule
     */
    public $jobHelper = null;

    /**
     * @var \KiwiCommerce\CronScheduler\Model\Job
     */
    public $jobModel;

    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    public $cacheTypeList;

    /**
     * @var string
     */
    protected $aclResource = "job_massstatuschange";

    /**
     * Cron job disable status
     */
    const CRON_JOB_DISABLE_STATUS = 0;

    /**
     * Class constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \KiwiCommerce\CronScheduler\Model\Job $jobModel
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \KiwiCommerce\CronScheduler\Helper\Cronjob $jobHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \KiwiCommerce\CronScheduler\Model\Job $jobModel,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \KiwiCommerce\CronScheduler\Helper\Cronjob $jobHelper
    ) {
        $this->jobHelper = $jobHelper;
        $this->jobModel = $jobModel;
        $this->cacheTypeList = $cacheTypeList;
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
            $this->messageManager->addErrorMessage(__('Selected jobs can not be disabled.'));
            return $this->_redirect('*/*/listing');
        }

        try {
            foreach ($jobCodes as $jobCode) {
                $data = $this->jobHelper->getJobDetail($jobCode);
                $this->jobModel->changeJobStatus($data, self::CRON_JOB_DISABLE_STATUS);
            }
            $this->cacheTypeList->cleanType('config');
            $this->messageManager->addSuccessMessage(__('You disabled selected jobs.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->_redirect('*/*/listing');
        }
        return $this->_redirect('*/*/listing');
    }
}
