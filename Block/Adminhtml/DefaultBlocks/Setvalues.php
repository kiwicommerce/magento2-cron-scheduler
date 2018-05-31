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
namespace KiwiCommerce\CronScheduler\Block\Adminhtml\DefaultBlocks;

/**
 * Class Setvalues
 * @package KiwiCommerce\CronScheduler\Block\Adminhtml\DefaultBlocks
 */
class Setvalues extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\App\DeploymentConfig\Reader
     */
    public $configReader;

    /**
     * Class constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\DeploymentConfig\Reader $configReader
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\DeploymentConfig\Reader $configReader
    ) {
        $this->configReader = $configReader;
        parent::__construct($context);
    }

    /**
     * Get Admin URL
     * @return string
     * @throws \Exception
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getAdminBaseUrl()
    {
        $config = $this->configReader->load();
        $adminSuffix = $config['backend']['frontName'];
        return $this->getBaseUrl() . $adminSuffix . '/';
    }
}
