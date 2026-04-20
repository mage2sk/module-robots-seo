<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Model\Robots;

use Magento\Framework\Model\AbstractModel;
use Panth\RobotsSeo\Model\ResourceModel\RobotsPolicy as RobotsPolicyResource;

/**
 * AR model for a single row in `panth_seo_robots_policy`.
 */
class Policy extends AbstractModel
{
    /**
     * Primary key column.
     *
     * @var string
     */
    protected $_idFieldName = 'policy_id';

    protected function _construct(): void
    {
        $this->_init(RobotsPolicyResource::class);
    }
}
