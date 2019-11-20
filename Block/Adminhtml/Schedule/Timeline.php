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

namespace KiwiCommerce\CronScheduler\Block\Adminhtml\Schedule;

/**
 * Class Timeline
 * @package KiwiCommerce\CronScheduler\Block\Adminhtml\Schedule
 */
class Timeline extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    public $datetime = null;

    /**
     * @var \KiwiCommerce\CronScheduler\Helper\Schedule
     */
    public $scheduleHelper = null;

    /**
     * @var \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory
     */
    public $collectionFactory = null;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    public $timezone;

    /**
     * @var \Magento\Framework\App\ProductMetadata
     */
    private $productMetaData;

    /**
     * Class constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $datetime
     * @param \KiwiCommerce\CronScheduler\Helper\Schedule $scheduleHelper
     * @param \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory $collectionFactory
     * @param \Magento\Framework\App\ProductMetadata $productMetaData
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        \KiwiCommerce\CronScheduler\Helper\Schedule $scheduleHelper,
        \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory $collectionFactory,
        \Magento\Framework\App\ProductMetadata $productMetaData,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        array $data = []
    ) {
        $this->datetime = $datetime;
        $this->scheduleHelper = $scheduleHelper;
        $this->collectionFactory = $collectionFactory;
        $this->productMetaData = $productMetaData; // todo need to this un-used variable
        parent::__construct($context, $data);
        $this->timezone = $timezone;
    }

    /**
     * Get the data to construct the timeline
     *
     * @return array
     * @throws \Exception
     */
    public function getCronJobData()
    {
        $data = [];
        $schedules = $this->collectionFactory->create();
        $schedules->getSelect()->order('job_code');

        foreach ($schedules as $schedule) {
            $start = $this->timezone->date($schedule->getData('executed_at'))->format('Y-m-d H:i:s');
            $end = $this->timezone->date($schedule->getData('finished_at'))->format('Y-m-d H:i:s');
            $status = $schedule->getStatus();

            if ($start == null) {
                $start = $end = $schedule->getData('scheduled_at');
            }

            if ($status == \Magento\Cron\Model\Schedule::STATUS_RUNNING) {
                $end = $this->timezone->date()->format('Y-m-d H:i:s');
            }

            if ($status == \Magento\Cron\Model\Schedule::STATUS_ERROR && $end == null) {
                $end = $start;
            }
            $level   = $this->getStatusLevel($status);
            $tooltip = $this->getToolTip($schedule, $level, $status, $start, $end);

            $data[] = [
                $schedule->getJobCode(),
                $status,
                $tooltip,
                $this->getNewDateForJs($start),
                $this->getNewDateForJs($end),
                $schedule->getScheduleId()
            ];
        }

        return $data;
    }

    /**
     * Generate js date format for given date
     * @param $date
     * @return string
     */
    private function getNewDateForJs($date)
    {
        return "new Date(" . $this->datetime->date('Y,', $date) . ($this->datetime->date('m', $date) - 1) . $this->datetime->date(',d,H,i,s,0', $date) . ")";
    }

    /**
     * Get Status Level
     * @param $status
     * @return string
     */
    private function getStatusLevel($status)
    {
        switch ($status) {
            case \Magento\Cron\Model\Schedule::STATUS_ERROR:
            case \Magento\Cron\Model\Schedule::STATUS_MISSED:
                $level = 'major';
                break;
            case \Magento\Cron\Model\Schedule::STATUS_RUNNING:
                $level = 'running';
                break;
            case \Magento\Cron\Model\Schedule::STATUS_PENDING:
                $level = 'minor';
                break;
            case \Magento\Cron\Model\Schedule::STATUS_SUCCESS:
                $level = 'notice';
                break;
            default:
                $level = 'critical';
        }

        return $level;
    }

    /**
     * Get tooltip text for each cron job
     * @param $schedule
     * @param $level
     * @param $status
     * @param $start
     * @param $end
     * @return string
     */
    private function getToolTip($schedule, $level, $status, $start, $end)
    {
        $tooltip = "<table class=>"
            . "<tr><td colspan='2'>"
            . $schedule->getJobCode()
            . "</td></tr>"
            . "<tr><td>"
            . "Id"
            . "</td><td>"
            . $schedule->getId() . "</td></tr>"
            . "<tr><td>"
            . "Status"
            . "</td><td>"
            . "<span class='grid-severity-" . $level . "'>" . $status . "</span>"
            . "</td></tr>"
            . "<tr><td>"
            . "Created at"
            . "</td><td>"
            . $this->timezone->date($schedule->getData('created_at'))->format('Y-m-d H:i:s')
            . "</td></tr>"
            . "<tr><td>"
            . "Scheduled at"
            . "</td><td>"
            . $this->timezone->date($schedule->getData('scheduled_at'))->format('Y-m-d H:i:s')
            . "</td></tr>"
            . "<tr><td>"
            . "Executed at"
            . "</td><td>"
            . ($start != null ? $start : "")
            . "</td></tr>"
            . "<tr><td>"
            . "Finished at"
            . "</td><td>"
            . ($end != null ? $end : "")
            . "</td></tr>";

        if ($status== "success") {
            $timeFirst  = strtotime($start);
            $timeSecond = strtotime($end);
            $differenceInSeconds = $timeSecond - $timeFirst;

            $tooltip .= "<tr><td>"
                . "CPU Usage"
                . "</td><td>"
                . $schedule->getCpuUsage()
                . "</td></tr>"
                . "<tr><td>"
                . "System Usage"
                . "</td><td>"
                . $schedule->getSystemUsage()
                . "</td></tr>"
                . "<tr><td>"
                . "Memory Usage"
                . "</td><td>"
                . $schedule->getMemoryUsage()
                . "</td></tr>"
                . "<tr><td>"
                . "Total Executed Time"
                . "</td><td>"
                . $differenceInSeconds
                . "</td></tr>";
        }
        $tooltip .= "</table>";

        return $tooltip;
    }

    /**
     * Get the current date for javascript
     * @return string
     */
    public function getDateWithJs()
    {
        $current = $this->datetime->date('U') + $this->datetime->getGmtOffSet('seconds');
        return "new Date(" . $this->datetime->date("Y,", $current) . ($this->datetime->date("m", $current) - 1) . $this->datetime->date(",d,H,i,s", $current) . ")";
    }
}
