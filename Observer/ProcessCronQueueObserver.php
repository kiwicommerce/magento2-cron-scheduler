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

// @codingStandardsIgnoreStart
// Get Magento 2 version
$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
$productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
$version = $productMetadata->getVersion(); // Return the magento version
$explodedVersion = explode(".", $version);
$expectedVersion = [
    $version,
    $explodedVersion[0] . "." . $explodedVersion[1],
    $explodedVersion[0]
];

$data = [ "ProcessCronQueueObserver.php" ];
$path = '';
$foundVersion = '2.3';

foreach ($data as $file) {
    $fullFile = $path . str_replace("/", DIRECTORY_SEPARATOR, $file);
    $ext = '.' . pathinfo($fullFile, PATHINFO_EXTENSION);

    foreach ($expectedVersion as $v) {
        $v = join('_', explode('.', $v));
        $newFile = __DIR__ . '/' . str_replace($ext, "_" . $v . $ext, $fullFile);

        if (file_exists($newFile)) {
            $foundVersion = $v;
            break;
        }
    }
}

// Decide which class to be used on version comparison
if ($foundVersion === '2_1') {
    if (!\class_exists(ProcessCronQueueObserver_2_1::class)) {
        require 'ProcessCronQueueObserver_2_1.php';
    }
    class ProcessCronQueueObserverExtended extends ProcessCronQueueObserver_2_1 { }
} else {
    if (!\class_exists(ProcessCronQueueObserverDefault::class)) {
        require 'ProcessCronQueueObserverDefault.php';
    }
    class ProcessCronQueueObserverExtended extends ProcessCronQueueObserverDefault { }
}

class ProcessCronQueueObserver extends ProcessCronQueueObserverExtended {

}
// @codingStandardsIgnoreEnds
