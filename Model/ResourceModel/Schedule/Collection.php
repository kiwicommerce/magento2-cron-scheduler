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
namespace KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule;

/**
 * Class Collection
 * @package KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule
 */
class Collection extends \Magento\Cron\Model\ResourceModel\Schedule\Collection
{
    /**
     * @var string
     */
    protected $_idFieldName = "schedule_id";

    /**
     * Update mail status for given filter.
     * @param $data
     * @param $filter
     */
    public function updateMailStatusByJobCode($data, $filter)
    {
        $connection = $this->getConnection();

        $connection->update(
            $this->getMainTable(),
            $data,
            [
                'schedule_id <= ? ' => (int)$filter['schedule_id'],
                'job_code = ?' => $filter['job_code'],
                'status = ?' => $filter['status'],
                'error_message IS NOT NULL',
                'is_mail_sent IS NULL'
            ]
        );
    }

    /**
     * Get the last Cron Status
     * @return string | null
     */
    public function getLastCronStatus()
    {
        $this->getSelect()->reset('columns')
            ->columns(['executed_at'])
            ->where('executed_at is not null and job_code ="kiwicommerce_cronscheduler_status"')
            ->order('finished_at desc');

        $last = $this->getFirstItem();
        if ($last) {
            return $last->getExecutedAt();
        } else {
            return null;
        }
    }

    /**
     * Get Schedule task status
     * @return $this
     */
    public function getScheduleTaskStatuses()
    {
        $this->getSelect()->reset('columns')
            ->columns('DISTINCT(status) as status')
            ->order('status ASC');

        return $this;
    }
}
