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

namespace KiwiCommerce\CronScheduler\Setup;

/**
 * Class Recurring
 * @package KiwiCommerce\CronScheduler\Setup
 */
class Recurring implements \Magento\Framework\Setup\InstallSchemaInterface
{
    /**
     * @var null|\Symfony\Component\Console\Output\ConsoleOutput
     */
    public $output = null;

    /**
     * @var string
     */
    public $magentoVersion = "";

    /**
     * Class constructor.
     * @param \Symfony\Component\Console\Output\ConsoleOutput $output
     * @param \Magento\Framework\App\ProductMetadata $productMetaData
     */
    public function __construct(
        \Symfony\Component\Console\Output\ConsoleOutput $output,
        \Magento\Framework\App\ProductMetadata $productMetaData
    ) {
        $this->output = $output;
        $explodedVersion = explode("-", $productMetaData->getVersion());
        $this->magentoVersion = $explodedVersion[0];
    }

    /**
     * Copy files for specific version
     * @param $data
     */
    public function copyFilesForVersion($data)
    {
        $version = $this->magentoVersion;
        $explodedVersion = explode(".", $version);
        $expectedVersion = [
            $version,
            $explodedVersion[0] . "." . $explodedVersion[1],
            $explodedVersion[0]
        ];

        $path = str_replace("Setup" . DIRECTORY_SEPARATOR . "Recurring.php", "", __FILE__);

        foreach ($data as $file) {
            $fullFile = $path . str_replace("/", DIRECTORY_SEPARATOR, $file);
            $ext = pathinfo($fullFile, PATHINFO_EXTENSION);

            foreach ($expectedVersion as $v) {
                $newFile = str_replace("." . $ext, "_" . $v . "." . $ext, $fullFile);
                if (file_exists($newFile)) {
                    copy($newFile, $fullFile);
                    break;
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function install(
        \Magento\Framework\Setup\SchemaSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context
    ) {

        $data = [
            "Observer/ProcessCronQueueObserver.php"
        ];
        $this->copyFilesForVersion($data);
    }
}
