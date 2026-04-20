<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Resource model for `panth_seo_robots_policy`. The table name is preserved
 * from Panth_AdvancedSEO so an existing install can be migrated by simply
 * switching which module owns the schema; the data is never touched.
 */
class RobotsPolicy extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('panth_seo_robots_policy', 'policy_id');
    }
}
