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

namespace KiwiCommerce\CronScheduler\Ui\DataProvider;

/**
 * Class JobProvider
 * @package KiwiCommerce\CronScheduler\Ui\DataProvider
 */
class JobProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var integer
     */
    protected $size = 20;

    /**
     * @var integer
     */
    protected $offset = 1;

    /**
     * @var array
     */
    protected $likeFilters = [];

    /**
     * @var array
     */
    protected $rangeFilters = [];

    /**
     * @var string
     */
    protected $sortField = 'code';

    /**
     * @var string
     */
    protected $sortDir = 'asc';

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    private $directoryList = null;

    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadFactory
     */
    private $directoryRead = null;

    /**
     * @var \KiwiCommerce\CronScheduler\Helper\Cronjob
     */
    public $jobHelper = null;

    /**
     * Class constructor
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param \Magento\Framework\Filesystem\Directory\ReadFactory $directoryRead
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Cron\Model\ConfigInterface $jobHelper
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \Magento\Framework\Filesystem\Directory\ReadFactory $directoryRead,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \KiwiCommerce\CronScheduler\Helper\Cronjob $jobHelper,
        array $meta = [],
        array $data = []
    ) {
        $this->directoryRead = $directoryRead;
        $this->directoryList = $directoryList;
        $this->jobHelper = $jobHelper;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Set the limit of the collection
     * @param int $offset
     * @param int $size
     */
    public function setLimit(
        $offset,
        $size
    ) {
        $this->size = $size;
        $this->offset = $offset;
    }

    /**
     * Get the collection
     * @return array
     */
    public function getData()
    {
        $data = array_values($this->jobHelper->getJobData());

        $totalRecords = count($data);

        #sorting
        $sortField = $this->sortField;
        $sortDir = $this->sortDir;
        usort($data, function ($a, $b) use ($sortField, $sortDir) {
            if ($sortDir == "asc") {
                return $a[$sortField] > $b[$sortField];
            } else {
                return $a[$sortField] < $b[$sortField];
            }
        });

        #filters
        foreach ($this->likeFilters as $column => $value) {
            $data = array_filter($data, function ($item) use ($column, $value) {
                return stripos($item[$column], $value) !== false;
            });
        }

        #pagination
        $data = array_slice($data, ($this->offset - 1) * $this->size, $this->size);

        return [
            'totalRecords' => $totalRecords,
            'items' => $data,
        ];
    }

    /**
     * Add filters to the collection
     * @param \Magento\Framework\Api\Filter $filter
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        if ($filter->getConditionType() == "like") {
            $this->likeFilters[$filter->getField()] = substr($filter->getValue(), 1, -1);
        } elseif ($filter->getConditionType() == "eq") {
            $this->likeFilters[$filter->getField()] = $filter->getValue();
        } elseif ($filter->getConditionType() == "gteq") {
            $this->rangeFilters[$filter->getField()]['from'] = $filter->getValue();
        } elseif ($filter->getConditionType() == "lteq") {
            $this->rangeFilters[$filter->getField()]['to'] = $filter->getValue();
        }
    }

    /**
     * Set the order of the collection
     * @param string $field
     * @param string $direction
     */
    public function addOrder(
        $field,
        $direction
    ) {
        $this->sortField = $field;
        $this->sortDir = strtolower($direction);
    }
}
