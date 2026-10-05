<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class ReseedDefaultRobotsPolicy implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    public function apply(): self
    {
        $this->moduleDataSetup->startSetup();
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('panth_seo_robots_policy');

        if ($connection->isTableExists($table)) {
            $missing = [];
            foreach (InstallDefaultRobotsPolicy::getDefaultRows() as $row) {
                $exists = (int) $connection->fetchOne(
                    $connection->select()
                        ->from($table, 'COUNT(*)')
                        ->where('store_id = ?', (int) $row['store_id'])
                        ->where('user_agent = ?', $row['user_agent'])
                        ->where('directive = ?', $row['directive'])
                        ->where('path = ?', $row['path'])
                );
                if ($exists === 0) {
                    $missing[] = $row;
                }
            }
            if ($missing !== []) {
                $connection->insertMultiple($table, $missing);
            }
        }

        $this->moduleDataSetup->endSetup();
        return $this;
    }

    public static function getDependencies(): array
    {
        return [InstallDefaultRobotsPolicy::class];
    }

    public function getAliases(): array
    {
        return [];
    }
}
