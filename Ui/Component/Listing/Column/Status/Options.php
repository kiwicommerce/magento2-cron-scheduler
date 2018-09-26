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

namespace KiwiCommerce\CronScheduler\Ui\Component\Listing\Column\Status;

/**
 * Class Options
 * @package KiwiCommerce\CronScheduler\Ui\Component\Listing\Column\Status
 */
class Options implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var array
     */
    public $options = null;

    /**
     * @var \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory
     */
    public $scheduleCollectionFactory = null;

    /**
     * Options constructor.
     * @param \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory $scheduleCollectionFactory
     */
    public function __construct(
        \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory $scheduleCollectionFactory
    ) {
    
        $this->scheduleCollectionFactory = $scheduleCollectionFactory->create();
    }

    /**
     * Get all options available
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $this->options = [];
            $scheduleTaskStatuses = $this->scheduleCollectionFactory->getScheduleTaskStatuses();

            foreach ($scheduleTaskStatuses as $scheduleTaskStatus) {
                $status = $scheduleTaskStatus->getStatus();
                $this->options[] = [
                    "label" => __(strtoupper($status)),
                    "value" => $status
                ];
            }
        }

        return $this->options;
    }
}
