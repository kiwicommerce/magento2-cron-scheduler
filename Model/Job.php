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

namespace KiwiCommerce\CronScheduler\Model;

use \Magento\Framework\Model\AbstractModel;

/**
 * Class Job
 * @package KiwiCommerce\CronScheduler\Model
 */
class Job extends AbstractModel
{
    /**
     * @var string
     */
    public $cronExprTemplate = 'crontab/{$group}/jobs/{$jobcode}/schedule/cron_expr';

    /**
     * @var string
     */
    public $cronModelTemplate = 'crontab/{$group}/jobs/{$jobcode}/run/model';

    /**
     * @var string
     */
    public $cronStatusTemplate = 'crontab/{$group}/jobs/{$jobcode}/is_active';

    /**
     * @var string
     */
    public $scope = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT;

    /**
     * @var \Magento\Framework\App\Config\ConfigResource\ConfigInterface
     */
    public $configInterface = null;

    /**
     * class constructor
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configInterface
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configInterface,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->configInterface = $configInterface;
    }


    /**
     * Save cron job for given data and expression
     * @param $data
     * @param $cronExpr
     * @param $jobCode
     */
    public function saveJob($data, $cronExpr, $jobCode)
    {
        #Cron Expression
        $vars = [
            '{$group}' => $data['group'],
            '{$jobcode}' => $jobCode
        ];

        $cronExprString   = strtr($this->cronExprTemplate, $vars);
        $cronModelString  = strtr($this->cronModelTemplate, $vars);
        $cronStatusString = strtr($this->cronStatusTemplate, $vars);
        $cronModelValue   = $data['instance'] . "::" . $data['method'];
        $cronStatusValue  = $data['is_active'];

        $this->configInterface
            ->saveConfig($cronExprString, $cronExpr, $this->scope, 0);
        $this->configInterface
            ->saveConfig($cronModelString, $cronModelValue, $this->scope, 0);
        $this->configInterface
            ->saveConfig($cronStatusString, $cronStatusValue, $this->scope, 0);
    }

    /**
     * Delete the job
     *
     * @param $group
     * @param $jobCode
     */
    public function deleteJob($group, $jobCode)
    {
        $vars = [
            '{$group}' => $group,
            '{$jobcode}' => $jobCode
        ];
        $cronExprString = strtr($this->cronExprTemplate, $vars);
        $cronModelString = strtr($this->cronModelTemplate, $vars);
        $cronStatusString = strtr($this->cronStatusTemplate, $vars);

        $this->configInterface
            ->deleteConfig($cronExprString, $this->scope, 0);
        $this->configInterface
            ->deleteConfig($cronModelString, $this->scope, 0);
        $this->configInterface
            ->deleteConfig($cronStatusString, $this->scope, 0);
    }

    /**
     * Change job Status
     *
     * @param $jobData
     * @param $status
     */
    public function changeJobStatus($jobData, $status)
    {
        $vars = [
            '{$group}' => $jobData['group'],
            '{$jobcode}' => $jobData['code']
        ];
        $cronStatusString = strtr($this->cronStatusTemplate, $vars);
        $cronStatusValue  = $status;

        $this->configInterface
            ->saveConfig($cronStatusString, $cronStatusValue, $this->scope, 0);
    }
}
