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
 * Class Save
 * @package KiwiCommerce\CronScheduler\Controller\Adminhtml\Job
 */
class Save extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    public $cacheTypeList;

    /**
     * @var \KiwiCommerce\CronScheduler\Helper\Cronjob
     */
    public $jobHelper = null;

    /**
     * @var \KiwiCommerce\CronScheduler\Model\Job
     */
    public $jobModel;

    /**
     * Class constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \KiwiCommerce\CronScheduler\Helper\Cronjob $jobHelper
     * @param \KiwiCommerce\CronScheduler\Model\Job $jobModel
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \KiwiCommerce\CronScheduler\Helper\Cronjob $jobHelper,
        \KiwiCommerce\CronScheduler\Model\Job $jobModel
    ) {
        $this->cacheTypeList = $cacheTypeList;
        $this->jobHelper = $jobHelper;
        $this->jobModel = $jobModel;
        parent::__construct($context);
    }

    /**
     * Execute action
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        try {
            if ($data) {
                $data = $this->jobHelper->trimArray($data);
                #Cron Expression Array
                $cronExprArray = $this->jobHelper->trimArray(explode(',', $data['schedule']));
                $jobData = $this->jobHelper->getJobData();
                $flagMultipleExpression = false;

                #check is multiple expression
                if (count($cronExprArray) > 1) {
                    $flagMultipleExpression = true;
                    $counter = 1;
                }
                foreach ($cronExprArray as $cronExprKey => $cronExpr) {
                    $cronExistResponse = $this->jobHelper->checkIfCronExists($jobData, $cronExpr, $data);

                    #skip the row if already exist.
                    if ($cronExistResponse) {
                        $error[] = $cronExpr;
                        if ($data['mode'] == "edit" && $cronExprKey == 0) {
                            $error = $this->popElement($data, $error, $cronExpr);
                        }
                        continue;
                    }

                    #check the mode
                    if ($flagMultipleExpression && (($data['mode'] == "edit" && $cronExprKey != 0) || ($data['mode'] == "add"))) {
                        $result = $this->jobHelper->getCronJobName($jobData, $data['code'], $counter);
                        $jobcode = $result['jobcode'];
                        $counter = $result['counter'];
                    } else {
                        $jobcode = $data['code'];
                    }

                    $this->jobModel->saveJob($data, $cronExpr, $jobcode);
                    $sucess[] = $cronExpr;
                }

                $this->cacheTypeList->cleanType('config');
                if (isset($sucess) && !empty($sucess)) {
                    $this->messageManager->addSuccessMessage(__('You saved the cron job for expressions - '.join(',', $sucess)));
                }
                if (isset($error) && !empty($error)) {
                    $this->messageManager->addWarningMessage(__('The cron already exists for expressions - '.join(',', $error)));
                }
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->_redirect('*/*/listing');
        }

        return $this->_redirect('*/*/listing');
    }

    /**
     * Pop last element from array
     * @param $data
     * @param $error
     * @return mixed
     */
    private function popElement($data, $error, $cronExpr)
    {
        if (isset($data['oldexpressionvalue']) && $cronExpr == $data['oldexpressionvalue']) {
            array_pop($error);
        }

        return $error;
    }
}
