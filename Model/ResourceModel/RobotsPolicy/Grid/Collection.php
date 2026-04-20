<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Model\ResourceModel\RobotsPolicy\Grid;

use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Panth\RobotsSeo\Model\ResourceModel\RobotsPolicy as PolicyResource;
use Panth\RobotsSeo\Model\Robots\Policy as PolicyModel;
use Psr\Log\LoggerInterface;

/**
 * Grid-specific collection that implements `SearchResultInterface` so the UI
 * component data provider (`Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory`)
 * can treat it as a data source.
 *
 * Mirrors the conventions documented in `feedback_grid_collection.md`:
 * a real class (no virtualType), wired under `di.xml` with the main table and
 * resource model, and registered under the data source name in
 * `CollectionFactory`.
 */
class Collection extends AbstractCollection implements SearchResultInterface
{
    protected $_idFieldName = 'policy_id';

    private ?AggregationInterface $aggregations = null;

    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        EventManagerInterface $eventManager,
        $mainTable,
        $resourceModel,
        $connection = null,
        ?\Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
        $this->_init(PolicyModel::class, PolicyResource::class);
        $this->setMainTable($mainTable);
    }

    public function getAggregations()
    {
        return $this->aggregations;
    }

    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
        return $this;
    }

    public function getSearchCriteria()
    {
        return null;
    }

    public function setSearchCriteria(SearchCriteriaInterface $searchCriteria = null)
    {
        return $this;
    }

    public function getTotalCount()
    {
        return $this->getSize();
    }

    public function setTotalCount($totalCount)
    {
        return $this;
    }

    public function setItems(array $items = null)
    {
        return $this;
    }
}
