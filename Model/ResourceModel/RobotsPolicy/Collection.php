<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Model\ResourceModel\RobotsPolicy;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Panth\RobotsSeo\Model\ResourceModel\RobotsPolicy as PolicyResource;
use Panth\RobotsSeo\Model\Robots\Policy as PolicyModel;

/**
 * Generic collection for the policy model. A separate grid-specific
 * collection (see `Grid\Collection`) is used for the admin UI component.
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = 'policy_id';

    protected function _construct(): void
    {
        $this->_init(PolicyModel::class, PolicyResource::class);
    }
}
