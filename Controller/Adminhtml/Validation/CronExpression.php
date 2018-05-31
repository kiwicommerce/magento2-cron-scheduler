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
 * Class CronExpression
 * @package KiwiCommerce\CronScheduler\Controller\Adminhtml\Validation
 */
class CronExpression extends \Magento\Backend\App\Action
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
            $exprArray = explode(',', $data['expression']);
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $result = true;
            foreach ($exprArray as $expr) {
                if (!empty($expr)) {
                    $e = preg_split('#\s+#', $expr, null, PREG_SPLIT_NO_EMPTY);
                    if (count($e) < 5 || count($e) > 6) {
                        $result = false;
                        break;
                    }

                    if (!preg_match('/^(\*|([0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])|\*\/([0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])) (\*|([0-9]|1[0-9]|2[0-3])|\*\/([0-9]|1[0-9]|2[0-3])) (\*|([1-9]|1[0-9]|2[0-9]|3[0-1])|\*\/([1-9]|1[0-9]|2[0-9]|3[0-1])) (\*|([1-9]|1[0-2])|\*\/([1-9]|1[0-2])) (\*|([0-6])|\*\/([0-6]))$/
', $expr)) {
                        $result = false;
                        break;
                    }
                }
            }

            return $resultJson->setData(['success' => $result]);
        }

        return $this->_redirect('*/*/listing');
    }
}
