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

namespace KiwiCommerce\CronScheduler\Helper;

/**
 * Class Schedule
 * @package KiwiCommerce\CronScheduler\Helper
 */
class Schedule extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory
     */
    public $scheduleCollectionFactory = null;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    public $messageManager = null;

    /**
     * @var \Magento\Framework\App\ProductMetadata
     */
    public $productMetaData = null;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    public $datetime = null;

    /**
     * Class constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory $scheduleCollectionFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\App\ProductMetadata $productMetaData
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $datetime
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory $scheduleCollectionFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\ProductMetadata $productMetaData,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime
    ) {
        $this->scheduleCollectionFactory = $scheduleCollectionFactory;
        $this->messageManager = $messageManager;
        $this->productMetaData = $productMetaData;
        $this->datetime = $datetime;

        parent::__construct($context);
    }

    /**
     * Store pid in cron table
     *
     * @param $schedule
     */
    public function setPid(&$schedule)
    {
        if (function_exists('getmypid')) {
            $schedule->setPid(getmypid());
        }
    }

    /**
     * Calculate actual CPU usage in time ms
     * @param $ru
     * @param $rus
     * @param $schedule
     */
    public function setCpuUsage($ru, $rus, &$schedule)
    {
        $cpuData = $this->rutime($ru, $rus, 'utime');
        $systemData = $this->rutime($ru, $rus, 'stime');
        $schedule->setCpuUsage($cpuData);
        $schedule->setSystemUsage($systemData);
    }

    /**
     * Get Usage
     *
     * @param $ru
     * @param $rus
     * @param $index
     * @return float|int
     */
    private function rutime($ru, $rus, $index)
    {
        return ($ru["ru_$index.tv_sec"]*1000 + intval($ru["ru_$index.tv_usec"]/1000))
            -  ($rus["ru_$index.tv_sec"]*1000 + intval($rus["ru_$index.tv_usec"]/1000));
    }

    /**
     * Save Memory usage.Convert bytes to megabytes.
     * @param $schedule
     */
    public function setMemoryUsage(&$schedule)
    {
        $memory = (memory_get_peak_usage(false)/1024/1024);

        $schedule->setMemoryUsage($memory);
    }

    /**
     * Generates filtered time input from user to formatted time (YYYY-MM-DD)
     *
     * @param mixed $time
     * @return string
     */
    public function filterTimeInput($time)
    {
        $matches = [];
        preg_match('/(\d+-\d+-\d+)T(\d+:\d+)/', $time, $matches);
        $time = $matches[1] . " " . $matches[2];
        return strftime('%Y-%m-%d %H:%M:00', strtotime($time));
    }

    /**
     * Set last cron status message.
     *
     */
    public function getLastCronStatusMessage()
    {
        $magentoVersion = $this->getMagentoversion();
        if (version_compare($magentoVersion, "2.2.0") >= 0) {
            $currentTime = $this->datetime->date('U');
        } else {
            $currentTime = (int)$this->datetime->date('U') + $this->datetime->getGmtOffset('hours') * 60 * 60;
        }
        $lastCronStatus = strtotime($this->scheduleCollectionFactory->create()->getLastCronStatus());
        if ($lastCronStatus != null) {
            $diff = floor(($currentTime - $lastCronStatus) / 60);
            if ($diff > 5) {
                if ($diff >= 60) {
                    $diff = floor($diff / 60);
                    $this->messageManager->addErrorMessage(__("Last cron execution is older than %1 hour%2", $diff, ($diff > 1) ? "s" : ""));
                } else {
                    $this->messageManager->addErrorMessage(__("Last cron execution is older than %1 minute%2", $diff, ($diff > 1) ? "s" : ""));
                }
            } else {
                $this->messageManager->addSuccessMessage(__("Last cron execution was %1 minute%2 ago", $diff, ($diff > 1) ? "s" : ""));
            }
        } else {
            $this->messageManager->addErrorMessage(__("No cron execution found"));
        }
    }

    /**
     * Get Latest magento Version
     * @return mixed
     */
    public function getMagentoversion()
    {
        $explodedVersion = explode("-", $this->productMetaData->getVersion());
        $magentoversion = $explodedVersion[0];

        return $magentoversion;
    }
}
