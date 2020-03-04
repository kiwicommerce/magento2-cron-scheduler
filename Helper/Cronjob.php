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
 * Class Cronjob
 * @package KiwiCommerce\CronScheduler\Helper
 */
class Cronjob extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Cron\Model\ConfigInterface
     */
    public $cronConfig = null;

    /**
     * @var \Magento\Cron\Model\Config\Reader\Db
     */
    public $dbReader = null;

    /**
     * @var \Magento\Cron\Model\Config\Reader\Xml
     */
    public $reader = null;

    /**
     * @var String
     */
    public $cronAppendString = '_cron_{$counter}';

    /**
     * Cron job db xml text
     */
    const CRON_DB_XML = 'db_xml';

    /**
     * Cron job db text
     */
    const CRON_DB = 'db';

    /**
     * Cron job xml text
     */
    const CRON_XML = 'xml';

    /**
     * Cron job other
     */
    const CRON_OTHER = 'other';

    /**
     * Class constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Cron\Model\Config\Reader\Db $dbReader
     * @param \Magento\Cron\Model\Config\Reader\Xml $reader
     * @param \Magento\Cron\Model\ConfigInterface $cronConfig
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Cron\Model\Config\Reader\Db $dbReader,
        \Magento\Cron\Model\Config\Reader\Xml $reader,
        \Magento\Cron\Model\ConfigInterface $cronConfig
    ) {
        $this->cronConfig = $cronConfig;
        $this->dbReader = $dbReader;
        $this->reader = $reader;
        parent::__construct($context);
    }

    /**
     * Get list of cron jobs.
     *
     * @return array
     */
    public function getJobData()
    {
        $data = [];
        $configJobs = $this->cronConfig->getJobs();

        foreach ($configJobs as $group => $jobs) {
            foreach ($jobs as $code => $job) {
                $job = $this->setJobData($job);
                $job['code'] = $code;
                $job['group'] = $group;
                $job['jobtype'] = $this->getJobcodeType($code, $group);
                $data[$code] = $job;
            }
        }

        return $data;
    }

    /**
     * Get cron job detail.
     * @param $jobcode
     * @return array
     */
    public function getJobDetail($jobcode)
    {
        $data = [];
        $configJobs = $this->cronConfig->getJobs();

        foreach ($configJobs as $group => $jobs) {
            foreach ($jobs as $code => $job) {
                if ($code == $jobcode) {
                    $job  = $this->setJobData($job);
                    $job['code'] = $code;
                    $job['group'] = $group;
                    $data = $job;
                    break;
                }
            }
        }

        return $data;
    }

    /**
     * Set job data for given job
     * @param $job
     */
    private function setJobData($job)
    {
        if (!isset($job['config_schedule'])) {
            if (isset($job['schedule'])) {
                $job['config_schedule'] = $job['schedule'];
            } else {
                if (isset($job['config_path'])) {
                    $job['config_schedule'] = $this->scopeConfig->getValue(
                        $job['config_path'],
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    );
                } else {
                    $job['config_schedule'] = "";
                }
            }
        }
        if (!isset($job['is_active'])) {
            $job['is_active'] = 1;
        }

        return $job;
    }

    /**
     * Check is job code active
     * @param $jobCode
     * @return bool
     */
    public function isJobActive($jobCode)
    {
        $result = false;
        $jobDetail = $this->getJobDetail($jobCode);
        if (isset($jobDetail['is_active']) && $jobDetail['is_active']==1) {
            $result = true;
        }

        return $result;
    }

    /**
     * Filter Job codes as applied filters
     * @param $filters
     * @return array
     */
    public function getAllFilterJobCodes($filters)
    {
        $data = array_values($this->getJobData());
        $result = [];
        #filters
        foreach ($filters as $column => $value) {
            $data = array_filter($data, function ($item) use ($column, $value) {
                return stripos($item[$column], $value) !== false;
            });
        }

        if (!empty($data)) {
            $result = array_column($data, 'code');
        }

        return $result;
    }

    /**
     * Create unique job code name for multiple expression
     * @param $jobData
     * @param $jobCode
     * @param $counter
     * @return array
     */
    public function getCronJobName($jobData, $jobCode, $counter)
    {
        $data = array_values($jobData);
        $result = [];
        $existingJobCode = array_column($data, 'code');
        $appendJobCode = $this->cronAppendString;

        for ($i = $counter; $i <= $counter+100; $i++) {
            $cronExprString = strtr($appendJobCode, ['{$counter}' => $i]);
            $jobCodeCheck = $jobCode.$cronExprString;

            #check if the same name already in the array or not.
            if (!in_array($jobCodeCheck, $existingJobCode)) {
                $result['jobcode'] = $jobCodeCheck;
                $result['counter'] = $i+1;
                $result['status']  = "success";
                break;
            }
        }

        return $result;
    }

    /**
     * Check cron exists with same instance method and expression
     * @param $jobData
     * @param $cronExpr
     * @param $instance
     * @param $method
     * @return bool
     */
    public function checkIfCronExists($jobData, $cronExpr, $data)
    {
        $instance = $data['instance'];
        $method   = $data['method'];
        $result = false;
        foreach (array_values($jobData) as $job) {
            if ($job['instance']==$instance && $job['method']==$method) {
                if (isset($job['schedule']) && $job['schedule'] == $cronExpr) {
                    $result = true;
                    break;
                }
                if (isset($job['config_schedule']) && $job['config_schedule'] == $cronExpr) {
                    $result = true;
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * trim the given array
     * @param $array
     *
     * @return $array
     */
    public function trimArray($array)
    {
        $result = array_map('trim', $array);
        return $result;
    }

    /**
     * Check is joncode of xml
     * @param $jobCode
     * @param $group
     * @return bool
     */
    public function isXMLJobcode($jobCode, $group)
    {
        $configJobs = $this->reader->read();
        $result = false;

        if (isset($configJobs[$group][$jobCode])) {
            $result = true;
        }
        return $result;
    }

    /**
     * Get job code type(db, xml, db_xml)
     * @param $jobCode
     * @param $group
     * @return string
     */
    private function getJobcodeType($jobCode, $group)
    {
        $xmlJobs = $this->reader->read();
        $dbJobs = $this->dbReader->get();

        $xml = (isset($xmlJobs[$group][$jobCode])) ? true : false;
        $db  = (isset($dbJobs[$group][$jobCode])) ? true : false;
        if ($xml && $db) {
            $result = self::CRON_DB_XML;
        } elseif (!$xml && $db) {
            $result = self::CRON_DB;
        } elseif ($xml && !$db) {
            $result = self::CRON_XML;
        } else {
            $result = self::CRON_OTHER;
        }

        return $result;
    }
}
