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

namespace KiwiCommerce\CronScheduler\Ui\Component\Listing\Column\Group;

/**
 * Class Options
 * @package KiwiCommerce\CronScheduler\Ui\Component\Listing\Column\Group
 */
class Options implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var array
     */
    public $options = null;

    /**
     * @var \Magento\Cron\Model\ConfigInterface
     */
    public $cronConfig = null;

    /**
     * Options constructor.
     * @param \Magento\Cron\Model\ConfigInterface $cronConfig
     */
    public function __construct(
        \Magento\Cron\Model\ConfigInterface $cronConfig
    ) {
    
        $this->cronConfig = $cronConfig;
    }

    /**
     * Get all options available
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $configJobs = $this->cronConfig->getJobs();
            foreach (array_keys($configJobs) as $group) {
                $this->options[] = [
                    "label" => __($group),
                    "value" => $group
                ];
            }
        }

        return $this->options;
    }
}
