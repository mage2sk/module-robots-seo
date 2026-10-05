<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Setup\Patch\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class RefreshRobotsTxtRewrite implements DataPatchInterface
{
    private const REQUEST_PATH = 'robots.txt';
    private const TARGET_PATH  = 'seo_robots/robots/index';

    public function __construct(
        private readonly ResourceConnection $resource
    ) {
    }

    public function apply(): self
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('url_rewrite');
        if (!$connection->isTableExists($table)) {
            return $this;
        }

        $connection->update(
            $table,
            [
                'target_path' => self::TARGET_PATH,
                'description' => 'Panth_RobotsSeo dynamic robots.txt',
            ],
            [
                'request_path = ?' => self::REQUEST_PATH,
                'target_path <> ?' => self::TARGET_PATH,
            ]
        );

        return $this;
    }

    public static function getDependencies(): array
    {
        return [InstallRobotsTxtRewrite::class];
    }

    public function getAliases(): array
    {
        return [];
    }
}
