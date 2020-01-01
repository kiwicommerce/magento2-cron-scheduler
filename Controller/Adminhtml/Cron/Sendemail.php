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

namespace KiwiCommerce\CronScheduler\Controller\Adminhtml\Cron;

/**
 * Class Sendemail
 * @package KiwiCommerce\CronScheduler\Controller\Adminhtml\Cron
 */
class Sendemail extends \Magento\Backend\App\Action
{
    /**
     * Recipient email config path
     */
    const XML_PATH_EMAIL_RECIPIENT = 'cronscheduler/general/cronscheduler_admin_email';

    /**
     * Recipient email enable/disable status
     */
    const XML_PATH_EMAIL_ENABLE_STATUS = 'cronscheduler/general/cronscheduler_email_enabled';

    /**
     * @var \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory
     */
    public $scheduleCollectionFactory = null;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    public $transportBuilder = null;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    public $inlineTranslation = null;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig = null;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager = null;

    /**
     * @var \Magento\Framework\Mail\Template\SenderResolverInterface
     */
    public $senderResolver;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    public $dateTime;

    /**
     * Test Email Template Name
     */
    const TEST_EMAIL_TEMPLATE = 'cronscheduler_email_template';

    /**
     * Test Email Template Name
     */
    const IS_MAIL_STATUS = 1;

    /**
     * Class constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory $scheduleCollectionFactory
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Mail\Template\SenderResolverInterface $senderResolver
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\CollectionFactory $scheduleCollectionFactory,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Mail\Template\SenderResolverInterface $senderResolver,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->scheduleCollectionFactory = $scheduleCollectionFactory;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
        $this->dateTime = $dateTime;
        $this->senderResolver = $senderResolver;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Execute action
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|string
     */
    public function execute()
    {
        $emailEnableStatus = $this->scopeConfig->getValue(self::XML_PATH_EMAIL_ENABLE_STATUS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if ($emailEnableStatus) {
            $emailItems['errorMessages'] = $this->getFatalErrorOfJobcode();
            $emailItems['missedJobs']    = $this->getMissedCronJob();

            $receiverEmailConfig = $this->scopeConfig->getValue(self::XML_PATH_EMAIL_RECIPIENT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $receiverEmailIds = explode(',', $receiverEmailConfig);

            if (!empty($receiverEmailIds) && (!empty($emailItems['errorMessages']->getData()) || !empty($emailItems['missedJobs']->getData()))) {
                try {
                    $from = $this->senderResolver->resolve('general');

                    $this->sendEmailStatus($receiverEmailIds, $from, $emailItems);
                    $this->updateMailStatus($emailItems);
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                }
            }
        }
    }

    /**
     * Update is mail status after sending an email
     *
     * @param $emailItems
     */
    private function updateMailStatus($emailItems)
    {
        if (!empty($emailItems['errorMessages'])) {
            foreach ($emailItems['errorMessages'] as $errorMessage) {
                $collection = $this->scheduleCollectionFactory->create();
                $filters = [
                    'schedule_id' => $errorMessage['max_id'],
                    'job_code' => $errorMessage['job_code'],
                    'status' => \Magento\Cron\Model\Schedule::STATUS_ERROR
                ];
                $collection->updateMailStatusByJobCode(['is_mail_sent' => self::IS_MAIL_STATUS], $filters);
            }
        }

        if (!empty($emailItems['missedJobs'])) {
            foreach ($emailItems['missedJobs'] as $missedJob) {
                $collection = $this->scheduleCollectionFactory->create();
                $filters = [
                    'schedule_id' => $missedJob['max_id'],
                    'job_code' => $missedJob['job_code'],
                    'status' => \Magento\Cron\Model\Schedule::STATUS_MISSED
                ];
                $collection->updateMailStatusByJobCode(['is_mail_sent' => self::IS_MAIL_STATUS], $filters);
            }
        }
    }

    /**
     * Get Missed cron jobs count
     *
     * @return \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\Collection
     */
    private function getMissedCronJob()
    {
        $collection = $this->scheduleCollectionFactory->create();
        $collection->getSelect()->where('status = "'.\Magento\Cron\Model\Schedule::STATUS_MISSED.'"')
            ->where('is_mail_sent is NULL')
            ->reset('columns')
            ->columns(['job_code', 'MAX(schedule_id) as max_id', 'COUNT(schedule_id) as totalmissed'])
            ->group(['job_code']);

        return $collection;
    }

    /**
     * Get Each Cron Job Fatal error
     *
     * @return \KiwiCommerce\CronScheduler\Model\ResourceModel\Schedule\Collection
     */
    private function getFatalErrorOfJobcode()
    {
        $collection = $this->scheduleCollectionFactory->create();
        $collection->getSelect()->where('status = "'.\Magento\Cron\Model\Schedule::STATUS_ERROR.'"')
            ->where('error_message is not NULL')
            ->where('is_mail_sent is NULL')
            ->reset('columns')
            ->columns(['job_code', 'error_message','MAX(schedule_id) as max_id'])
            ->group(['job_code']);

        return $collection;
    }

    /**
     * Send Email
     * @param $to
     * @param $from
     * @param $items
     * @return $this
     * @throws \Magento\Framework\Exception\MailException
     */
    private function sendEmailStatus($to, $from, $items)
    {
        $templateOptions = ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId()];
        $templateVars = [
            'store' => $this->storeManager->getStore(),
            'items'=> $items,
        ];

        $this->inlineTranslation->suspend();

        $this->transportBuilder->setTemplateIdentifier(self::TEST_EMAIL_TEMPLATE)
            ->setTemplateOptions($templateOptions)
            ->setTemplateVars($templateVars)
            ->setFrom($from)
            ->addTo($to);

        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();

        $this->inlineTranslation->resume();
        return $this;
    }
}
