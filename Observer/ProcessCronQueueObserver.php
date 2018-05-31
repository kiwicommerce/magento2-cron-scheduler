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

namespace KiwiCommerce\CronScheduler\Observer;

use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\Event\ObserverInterface;
use \Magento\Cron\Model\Schedule;

/**
 * Class ProcessCronQueueObserver
 * @package KiwiCommerce\CronScheduler\Observer
 */
class ProcessCronQueueObserver extends \Magento\Cron\Observer\ProcessCronQueueObserver
{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Framework\App\State
     */
    private $state;

    /**
     * @var \KiwiCommerce\CronScheduler\Helper\Schedule
     */
    private $scheduleHelper = null;

    /**
     * @var \KiwiCommerce\CronScheduler\Helper\Schedule
     */
    private $jobHelper = null;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Cron\Model\ScheduleFactory $scheduleFactory
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param \Magento\Cron\Model\ConfigInterface $config
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Console\Request $request
     * @param \Magento\Framework\ShellInterface $shell
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Process\PhpExecutableFinderFactory $phpExecutableFinderFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\State $state
     * @param \KiwiCommerce\CronScheduler\Helper\Schedule $scheduleHelper
     * @param \KiwiCommerce\CronScheduler\Helper\Cronjob $jobHelper
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Cron\Model\ScheduleFactory $scheduleFactory,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Cron\Model\ConfigInterface $config,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Console\Request $request,
        \Magento\Framework\ShellInterface $shell,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Process\PhpExecutableFinderFactory $phpExecutableFinderFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\State $state,
        \KiwiCommerce\CronScheduler\Helper\Schedule $scheduleHelper,
        \KiwiCommerce\CronScheduler\Helper\Cronjob $jobHelper
    ) {

        $construct= "__construct"; // in order to bypass the compiler
        parent::$construct($objectManager, $scheduleFactory, $cache, $config, $scopeConfig, $request, $shell, $dateTime, $phpExecutableFinderFactory, $logger, $state);
        $this->logger = $logger;
        $this->state = $state;
        $this->scheduleHelper = $scheduleHelper;
        $this->jobHelper = $jobHelper;
    }

    /**
     * Process cron queue
     * Generate tasks schedule
     * Cleanup tasks schedule
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        #Handle Fatal Error
        $runningSchedule = null;
        register_shutdown_function(function () use (&$runningSchedule) {
            $errorMessage = error_get_last();
            if ($errorMessage) {
                if ($runningSchedule != null) {
                    $s = $runningSchedule;
                    $s->setStatus(\Magento\Cron\Model\Schedule::STATUS_ERROR);
                    $s->setErrorMessage($errorMessage['message']);
                    $s->setErrorFile($errorMessage['file']);
                    $s->setErrorLine($errorMessage['line']);
                    $s->save();
                }
            }
        });

        $pendingJobs = $this->_getPendingSchedules();
        $currentTime = $this->dateTime->gmtTimestamp();
        $jobGroupsRoot = $this->_config->getJobs();

        $phpPath = $this->phpExecutableFinder->find() ?: 'php';

        foreach ($jobGroupsRoot as $groupId => $jobsRoot) {
            $this->_cleanup($groupId);
            $this->_generate($groupId);
            if ($this->_request->getParam('group') !== null
                && $this->_request->getParam('group') !== '\'' . ($groupId) . '\''
                && $this->_request->getParam('group') !== $groupId
            ) {
                continue;
            }
            if (($this->_request->getParam(self::STANDALONE_PROCESS_STARTED) !== '1') && (
                    $this->_scopeConfig->getValue(
                        'system/cron/' . $groupId . '/use_separate_process',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    ) == 1
                )
            ) {
                $this->_shell->execute(
                    $phpPath . ' %s cron:run --group=' . $groupId . ' --' . Cli::INPUT_KEY_BOOTSTRAP . '='
                    . self::STANDALONE_PROCESS_STARTED . '=1',
                    [
                        BP . '/bin/magento'
                    ]
                );
                continue;
            }

            /** @var \Magento\Cron\Model\Schedule $schedule */
            foreach ($pendingJobs as $schedule) {
                $runningSchedule = $schedule;
                $jobConfig = isset($jobsRoot[$schedule->getJobCode()]) ? $jobsRoot[$schedule->getJobCode()] : null;
                if (!$jobConfig) {
                    continue;
                }

                $scheduledTime = strtotime($schedule->getScheduledAt());
                if ($scheduledTime > $currentTime) {
                    continue;
                }

                try {
                    if ($schedule->tryLockJob()) {
                        $this->scheduleHelper->setPid($schedule);
                        $cpu_before = getrusage();
                        $this->_runJob($scheduledTime, $currentTime, $jobConfig, $schedule, $groupId);
                        $cpu_after = getrusage();
                        $this->scheduleHelper->setCpuUsage($cpu_after, $cpu_before, $schedule);
                        $this->scheduleHelper->setMemoryUsage($schedule);
                    }
                } catch (\Exception $e) {
                    $schedule->setMessages($e->getMessage());
                    $schedule->setErrorMessage($e->getMessage());
                    $schedule->setErrorLine($e->getLine());

                    if ($schedule->getStatus() === Schedule::STATUS_ERROR) {
                        $this->logger->critical($e);
                    }
                    if ($schedule->getStatus() === Schedule::STATUS_MISSED
                        && $this->state->getMode() === State::MODE_DEVELOPER
                    ) {
                        $this->logger->info(
                            sprintf(
                                "%s Schedule Id: %s Job Code: %s",
                                $schedule->getMessages(),
                                $schedule->getScheduleId(),
                                $schedule->getJobCode()
                            )
                        );
                    }
                }
                $schedule->save();
            }
        }
    }

    /**
     * @param string $jobCode
     * @param string $cronExpression
     * @param int $timeInterval
     * @param array $exists
     * @return void
     */
    protected function saveSchedule($jobCode, $cronExpression, $timeInterval, $exists)
    {
        $result = $this->jobHelper->isJobActive($jobCode);

        if ($result) {
            parent::saveSchedule($jobCode, $cronExpression, $timeInterval, $exists);
        }
    }
}
