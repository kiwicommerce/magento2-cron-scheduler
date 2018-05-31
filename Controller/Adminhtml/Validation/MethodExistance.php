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

namespace KiwiCommerce\CronScheduler\Controller\Adminhtml\Validation;

use Magento\Framework\Controller\ResultFactory;

/**
 * Class MethodExistance
 * @package KiwiCommerce\CronScheduler\Controller\Adminhtml\Validation
 */
class MethodExistance extends \Magento\Backend\App\Action
{
    /**
     * Class constructor
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * Execute action
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $data = $this->getRequest()->getPostValue();
            $classpath = trim($data['classpath']);
            $methodname = trim($data['methodname']);
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $result = false;

            if (!empty($classpath)) {
                if (method_exists($classpath, $methodname)) {
                    $result = true;
                }
            }

            return $resultJson->setData(['success' => $result]);
        }

        return $this->_redirect('*/*/listing');
    }
}
