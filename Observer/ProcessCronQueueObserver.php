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

use Magento\Cron\Model\Schedule;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\Profiler\Driver\Standard\Stat;

/**
 * Class ProcessCronQueueObserver
 * @package KiwiCommerce\CronScheduler\Observer
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class ProcessCronQueueObserver extends \Magento\Cron\Observer\ProcessCronQueueObserver
{
    /**
     * @var array
     */
    private $invalid = [];
    /**
     * @var \KiwiCommerce\CronScheduler\Helper\Cronjob
     */
    private $jobHelper = null;
    /**
     * @var \Magento\Framework\Lock\LockManagerInterface
     */
    private $lockManager;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var \KiwiCommerce\CronScheduler\Helper\Schedule
     */
    private $scheduleHelper = null;
    /**
     * @var Stat
     */
    private $statProfiler;
    /**
     * @var \Magento\Framework\App\State
     */
    private $state;

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
     * @param \Magento\Framework\Profiler\Driver\Standard\StatFactory $statFactory
     * @param \Magento\Framework\Lock\LockManagerInterface $lockManager
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Cron\Model\DeadlockRetrierInterface $retrier
     * @param \KiwiCommerce\CronScheduler\Helper\Schedule $scheduleHelper
     * @param \KiwiCommerce\CronScheduler\Helper\Cronjob $jobHelper
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
        \Magento\Framework\Profiler\Driver\Standard\StatFactory $statFactory,
        \Magento\Framework\Lock\LockManagerInterface $lockManager,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Cron\Model\DeadlockRetrierInterface $retrier,
        \KiwiCommerce\CronScheduler\Helper\Schedule $scheduleHelper,
        \KiwiCommerce\CronScheduler\Helper\Cronjob $jobHelper
    ) {
        parent::__construct(
            $objectManager, $scheduleFactory, $cache, $config, $scopeConfig,
            $request, $shell, $dateTime, $phpExecutableFinderFactory, $logger,
            $state, $statFactory, $lockManager, $eventManager, $retrier);
        $this->logger = $logger;
        $this->state = $state;
        $this->statProfiler = $statFactory->create();
        $this->lockManager = $lockManager;
        $this->scheduleHelper = $scheduleHelper;
        $this->jobHelper = $jobHelper;
    }

    /**
     * Execute job by calling specific class::method
     *
     * @param int $scheduledTime
     * @param int $currentTime
     * @param string[] $jobConfig
     * @param Schedule $schedule
     * @param string $groupId
     * @return void
     * @throws \Exception
     */
    protected function _runJob($scheduledTime, $currentTime, $jobConfig, $schedule, $groupId)
    {
        $jobCode = $schedule->getJobCode();
        $scheduleLifetime = $this->getCronGroupConfigurationValue($groupId, self::XML_PATH_SCHEDULE_LIFETIME);
        $scheduleLifetime = $scheduleLifetime * self::SECONDS_IN_MINUTE;
        if ($scheduledTime < $currentTime - $scheduleLifetime) {
            $schedule->setStatus(Schedule::STATUS_MISSED);
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new \Exception(sprintf('Cron Job %s is missed at %s', $jobCode, $schedule->getScheduledAt()));
        }

        if (!isset($jobConfig['instance'], $jobConfig['method'])) {
            $schedule->setStatus(Schedule::STATUS_ERROR);
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new \Exception(sprintf('No callbacks found for cron job %s', $jobCode));
        }
        $model = $this->_objectManager->create($jobConfig['instance']);
        $callback = [$model, $jobConfig['method']];
        if (!is_callable($callback)) {
            $schedule->setStatus(Schedule::STATUS_ERROR);
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new \Exception(
                sprintf('Invalid callback: %s::%s can\'t be called', $jobConfig['instance'], $jobConfig['method'])
            );
        }

        $schedule->setExecutedAt(strftime('%Y-%m-%d %H:%M:%S', $this->dateTime->gmtTimestamp()))->save();

        $this->startProfiling();
        try {
            $this->logger->info(sprintf('Cron Job %s is run', $jobCode));
            //phpcs:ignore Magento2.Functions.DiscouragedFunction
            call_user_func_array($callback, [$schedule]);
        } catch (\Throwable $e) {
            $schedule->setStatus(Schedule::STATUS_ERROR);
            $this->logger->error(
                sprintf(
                    'Cron Job %s has an error: %s. Statistics: %s',
                    $jobCode,
                    $e->getMessage(),
                    $this->getProfilingStat()
                )
            );
            if (!$e instanceof \Exception) {
                $e = new \RuntimeException(
                    'Error when running a cron job',
                    0,
                    $e
                );
            }
            throw $e;
        } finally {
            $this->stopProfiling();
        }

        $schedule->setStatus(
            Schedule::STATUS_SUCCESS
        )->setFinishedAt(
            strftime(
                '%Y-%m-%d %H:%M:%S',
                $this->dateTime->gmtTimestamp()
            )
        );

        $this->logger->info(
            sprintf(
                'Cron Job %s is successfully finished. Statistics: %s',
                $jobCode,
                $this->getProfilingStat()
            )
        );
    }

    /**
     * Clean up scheduled jobs that are disabled in the configuration.
     *
     * This can happen when you turn off a cron job in the config and flush the cache.
     *
     * @param string $groupId
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function cleanupDisabledJobs($groupId)
    {
        $jobs = $this->_config->getJobs();
        $jobsToCleanup = [];
        foreach ($jobs[$groupId] as $jobCode => $jobConfig) {
            if (!$this->getCronExpression($jobConfig)) {
                /** @var \Magento\Cron\Model\ResourceModel\Schedule $scheduleResource */
                $jobsToCleanup[] = $jobCode;
            }
        }

        if (count($jobsToCleanup) > 0) {
            $scheduleResource = $this->_scheduleFactory->create()->getResource();
            $count = $scheduleResource->getConnection()->delete(
                $scheduleResource->getMainTable(),
                [
                    'status = ?' => Schedule::STATUS_PENDING,
                    'job_code in (?)' => $jobsToCleanup,
                ]
            );

            $this->logger->info(sprintf('%d cron jobs were cleaned', $count));
        }
    }

    /**
     * Clean expired jobs
     *
     * @param string $groupId
     * @param int $currentTime
     * @return ProcessCronQueueObserverDefault
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function cleanupJobs($groupId, $currentTime)
    {
        // check if history cleanup is needed
        $lastCleanup = (int)$this->_cache->load(self::CACHE_KEY_LAST_HISTORY_CLEANUP_AT . $groupId);
        $historyCleanUp = (int)$this->getCronGroupConfigurationValue($groupId, self::XML_PATH_HISTORY_CLEANUP_EVERY);
        if ($lastCleanup > $this->dateTime->gmtTimestamp() - $historyCleanUp * self::SECONDS_IN_MINUTE) {
            return $this;
        }
        // save time history cleanup was ran with no expiration
        $this->_cache->save(
            $this->dateTime->gmtTimestamp(),
            self::CACHE_KEY_LAST_HISTORY_CLEANUP_AT . $groupId,
            ['crontab'],
            null
        );

        $this->cleanupDisabledJobs($groupId);

        $historySuccess = (int)$this->getCronGroupConfigurationValue($groupId, self::XML_PATH_HISTORY_SUCCESS);
        $historyFailure = (int)$this->getCronGroupConfigurationValue($groupId, self::XML_PATH_HISTORY_FAILURE);
        $historyLifetimes = [
            Schedule::STATUS_SUCCESS => $historySuccess * self::SECONDS_IN_MINUTE,
            Schedule::STATUS_MISSED => $historyFailure * self::SECONDS_IN_MINUTE,
            Schedule::STATUS_ERROR => $historyFailure * self::SECONDS_IN_MINUTE,
            Schedule::STATUS_PENDING => max($historyFailure, $historySuccess) * self::SECONDS_IN_MINUTE,
        ];

        $jobs = $this->_config->getJobs()[$groupId];
        $scheduleResource = $this->_scheduleFactory->create()->getResource();
        $connection = $scheduleResource->getConnection();
        $count = 0;
        foreach ($historyLifetimes as $status => $time) {
            $count += $connection->delete(
                $scheduleResource->getMainTable(),
                [
                    'status = ?' => $status,
                    'job_code in (?)' => array_keys($jobs),
                    'created_at < ?' => $connection->formatDate($currentTime - $time)
                ]
            );
        }

        if ($count) {
            $this->logger->info(sprintf('%d cron jobs were cleaned', $count));
        }
    }

    /**
     * Clean up scheduled jobs that do not match their cron expression anymore.
     *
     * This can happen when you change the cron expression and flush the cache.
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function cleanupScheduleMismatches()
    {
        /** @var \Magento\Cron\Model\ResourceModel\Schedule $scheduleResource */
        $scheduleResource = $this->_scheduleFactory->create()->getResource();
        foreach ($this->invalid as $jobCode => $scheduledAtList) {
            $scheduleResource->getConnection()->delete(
                $scheduleResource->getMainTable(),
                [
                    'status = ?' => Schedule::STATUS_PENDING,
                    'job_code = ?' => $jobCode,
                    'scheduled_at in (?)' => $scheduledAtList,
                ]
            );
        }
        return $this;
    }

    /**
     * Process cron queue
     * Generate tasks schedule
     * Cleanup tasks schedule
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
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

        $currentTime = $this->dateTime->gmtTimestamp();
        $jobGroupsRoot = $this->_config->getJobs();
        // sort jobs groups to start from used in separated process
        uksort(
            $jobGroupsRoot,
            function ($a, $b) {
                return $this->getCronGroupConfigurationValue($b, 'use_separate_process')
                    - $this->getCronGroupConfigurationValue($a, 'use_separate_process');
            }
        );

        $phpPath = $this->phpExecutableFinder->find() ?: 'php';

        foreach ($jobGroupsRoot as $groupId => $jobsRoot) {
            if (!$this->isGroupInFilter($groupId)) {
                continue;
            }
            if ($this->_request->getParam(self::STANDALONE_PROCESS_STARTED) !== '1'
                && $this->getCronGroupConfigurationValue($groupId, 'use_separate_process') == 1
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

            $this->lockGroup(
                $groupId,
                function ($groupId) use ($currentTime, $jobsRoot) {
                    $this->cleanupJobs($groupId, $currentTime);
                    $this->generateSchedules($groupId);
                    $this->processPendingJobs($groupId, $jobsRoot, $currentTime);
                }
            );
        }
    }

    /**
     * Generate cron schedule
     *
     * @param string $groupId
     * @return $this
     */
    private function generateSchedules($groupId)
    {
        /**
         * check if schedule generation is needed
         */
        $lastRun = (int)$this->_cache->load(self::CACHE_KEY_LAST_SCHEDULE_GENERATE_AT . $groupId);
        $rawSchedulePeriod = (int)$this->getCronGroupConfigurationValue(
            $groupId,
            self::XML_PATH_SCHEDULE_GENERATE_EVERY
        );
        $schedulePeriod = $rawSchedulePeriod * self::SECONDS_IN_MINUTE;
        if ($lastRun > $this->dateTime->gmtTimestamp() - $schedulePeriod) {
            return $this;
        }

        /**
         * save time schedules generation was ran with no expiration
         */
        $this->_cache->save(
            $this->dateTime->gmtTimestamp(),
            self::CACHE_KEY_LAST_SCHEDULE_GENERATE_AT . $groupId,
            ['crontab'],
            null
        );

        $schedules = $this->getNonExitedSchedules($groupId);
        $exists = [];
        /** @var Schedule $schedule */
        foreach ($schedules as $schedule) {
            $exists[$schedule->getJobCode() . '/' . $schedule->getScheduledAt()] = 1;
        }
        /**
         * generate global crontab jobs
         */
        $jobs = $this->_config->getJobs();
        $this->invalid = [];
        $this->_generateJobs($jobs[$groupId], $exists, $groupId);
        $this->cleanupScheduleMismatches();
        return $this;
    }

    /**
     * Get cron expression of cron job.
     *
     * @param array $jobConfig
     * @return null|string
     */
    private function getCronExpression($jobConfig)
    {
        $cronExpression = null;
        if (isset($jobConfig['config_path'])) {
            $cronExpression = $this->getConfigSchedule($jobConfig) ?: null;
        }

        if (!$cronExpression) {
            if (isset($jobConfig['schedule'])) {
                $cronExpression = $jobConfig['schedule'];
            }
        }
        return $cronExpression;
    }

    /**
     * Get CronGroup Configuration Value.
     *
     * @param string $groupId
     * @param string $path
     * @return int
     */
    private function getCronGroupConfigurationValue($groupId, $path)
    {
        return $this->_scopeConfig->getValue(
            'system/cron/' . $groupId . '/' . $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Return job collection from database with status 'pending', 'running' or 'success'
     *
     * @param string $groupId
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    private function getNonExitedSchedules($groupId)
    {
        $jobs = $this->_config->getJobs();
        $pendingJobs = $this->_scheduleFactory->create()->getCollection();
        $pendingJobs->addFieldToFilter(
            'status',
            [
                'in' => [
                    Schedule::STATUS_PENDING,
                    Schedule::STATUS_RUNNING,
                    Schedule::STATUS_SUCCESS
                ]
            ]
        );
        $pendingJobs->addFieldToFilter('job_code', ['in' => array_keys($jobs[$groupId])]);
        return $pendingJobs;
    }

    /**
     * Return job collection from data base with status 'pending'.
     *
     * @param string $groupId
     * @return \Magento\Cron\Model\ResourceModel\Schedule\Collection
     */
    private function getPendingSchedules($groupId)
    {
        $jobs = $this->_config->getJobs();
        $pendingJobs = $this->_scheduleFactory->create()->getCollection();
        $pendingJobs->addFieldToFilter('status', Schedule::STATUS_PENDING);
        $pendingJobs->addFieldToFilter('job_code', ['in' => array_keys($jobs[$groupId])]);
        return $pendingJobs;
    }

    /**
     * Retrieves statistics in the JSON format
     *
     * @return string
     */
    private function getProfilingStat()
    {
        $stat = $this->statProfiler->get('job');
        unset($stat[Stat::START]);
        return json_encode($stat);
    }

    /**
     * Is Group In Filter.
     *
     * @param string $groupId
     * @return bool
     */
    private function isGroupInFilter($groupId): bool
    {
        return !($this->_request->getParam('group') !== null
            && trim($this->_request->getParam('group'), "'") !== $groupId);
    }

    /**
     * Lock group
     *
     * It should be taken by standalone (child) process, not by the parent process.
     *
     * @param int $groupId
     * @param callable $callback
     *
     * @return void
     */
    private function lockGroup($groupId, callable $callback)
    {

        if (!$this->lockManager->lock(self::LOCK_PREFIX . $groupId, self::LOCK_TIMEOUT)) {
            $this->logger->warning(
                sprintf(
                    "Could not acquire lock for cron group: %s, skipping run",
                    $groupId
                )
            );
            return;
        }
        try {
            $callback($groupId);
        } finally {
            $this->lockManager->unlock(self::LOCK_PREFIX . $groupId);
        }
    }

    /**
     * Process error messages.
     *
     * @param Schedule $schedule
     * @param \Exception $exception
     * @return void
     */
    private function processError(\Magento\Cron\Model\Schedule $schedule, \Exception $exception)
    {
        $schedule->setMessages($exception->getMessage());
        if ($schedule->getStatus() === Schedule::STATUS_ERROR) {
            $this->logger->critical($exception);
        }
        if ($schedule->getStatus() === Schedule::STATUS_MISSED
            && $this->state->getMode() === State::MODE_DEVELOPER
        ) {
            $this->logger->info($schedule->getMessages());
        }
    }

    /**
     * Process pending jobs.
     *
     * @param string $groupId
     * @param array $jobsRoot
     * @param int $currentTime
     * @throws \Throwable
     */
    private function processPendingJobs($groupId, $jobsRoot, $currentTime)
    {
        $procesedJobs = [];
        $pendingJobs = $this->getPendingSchedules($groupId);
        /** @var \Magento\Cron\Model\Schedule $schedule */
        foreach ($pendingJobs as $schedule) {
            if (isset($procesedJobs[$schedule->getJobCode()])) {
                // process only on job per run
                continue;
            }
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
                $this->processError($schedule, $e);
                $schedule->setErrorMessage($e->getMessage());
                $schedule->setErrorLine($e->getLine());
            }
            if ($schedule->getStatus() === Schedule::STATUS_SUCCESS) {
                $procesedJobs[$schedule->getJobCode()] = true;
            }
            $schedule->save();
        }
    }

    /**
     * Save a schedule of cron job.
     *
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

    /**
     * Starts profiling
     *
     * @return void
     */
    private function startProfiling()
    {
        $this->statProfiler->clear();
        $this->statProfiler->start('job', microtime(true), memory_get_usage(true), memory_get_usage());
    }

    /**
     * Stops profiling
     *
     * @return void
     */
    private function stopProfiling()
    {
        $this->statProfiler->stop('job', microtime(true), memory_get_usage(true), memory_get_usage());
    }
}
