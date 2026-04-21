<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Setup\Patch\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Upgrades existing stores that were previously installed via
 * Panth_AdvancedSEO (frontName `seo`) or an older split where the
 * `robots.txt` url_rewrite row points at a different target_path than
 * this module's current controller.
 *
 * The original {@see InstallRobotsTxtRewrite} patch only inserts when
 * no row exists, so upgrades would keep the stale `seo/robots/index`
 * target and `/robots.txt` would 404. This patch idempotently UPDATEs
 * any surviving legacy rows to the canonical target so upgrading from
 * Panth_AdvancedSEO is seamless.
 */
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
