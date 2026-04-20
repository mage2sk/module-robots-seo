<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Model\Robots;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\ScopeInterface;
use Panth\RobotsSeo\Helper\Config;
use Panth\RobotsSeo\Service\DirectiveValidator;

/**
 * Computes the page-level `<meta name="robots">` value.
 *
 * Resolution order (first non-empty WINS):
 *
 *  1) URL pattern policy: filtered layered nav, search results, pagination
 *     (these must beat stored per-entity values so the admin toggles are
 *     never dead switches).
 *  2) `panth_seo_resolved.robots` — populated by the indexer in Panth_AdvancedSEO
 *     when that module is also installed. Reading it here is a soft
 *     cross-module dependency: if AdvancedSEO is absent the table simply
 *     does not exist and the code falls through to step 3.
 *  3) `panth_robots_seo/general/default_directive` system config.
 *
 * Every value that eventually lands in the response goes through the
 * DirectiveValidator whitelist so admin-tampered rows can never inject
 * CRLF or bogus tokens into the header.
 */
class MetaResolver
{
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly RequestInterface $request,
        private readonly Config $config,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly DirectiveValidator $validator
    ) {
    }

    /**
     * Base `<meta name="robots">` value (no max-image-preview / max-snippet).
     */
    public function resolve(string $entityType, int $entityId, int $storeId): string
    {
        if ($this->isNoindexByUrlPattern($storeId)) {
            return 'noindex,follow';
        }

        $stored = $this->fetchStored($entityType, $entityId, $storeId);
        if ($stored !== '') {
            return $this->validator->sanitizeDirective($stored);
        }

        return $this->validator->sanitizeDirective($this->config->getDefaultDirective($storeId));
    }

    /**
     * Append Google-specific robots directives (max-image-preview, max-snippet)
     * to the base robots value when configured. Each directive is still
     * validated via DirectiveValidator so the final string is always safe.
     */
    public function appendAdvancedDirectives(string $baseRobots, int $storeId): string
    {
        $directives = [];

        $maxImagePreview = $this->config->getMaxImagePreview($storeId);
        if ($maxImagePreview !== 'none') {
            $directives[] = 'max-image-preview:' . $maxImagePreview;
        }

        $maxSnippet = $this->config->getMaxSnippet($storeId);
        $directives[] = 'max-snippet:' . $maxSnippet;

        if ($directives === []) {
            return $this->validator->sanitizeDirective($baseRobots);
        }

        $candidate = $baseRobots . ',' . implode(',', $directives);
        return $this->validator->sanitizeDirective($candidate);
    }

    /**
     * Resolve robots value WITH max-* advanced directives appended.
     */
    public function resolveWithDirectives(string $entityType, int $entityId, int $storeId): string
    {
        return $this->appendAdvancedDirectives($this->resolve($entityType, $entityId, $storeId), $storeId);
    }

    /**
     * Fetch the stored `panth_seo_resolved.robots` value when the indexer
     * table exists. Gracefully returns `''` when the table is absent — that
     * is the expected case when Panth_AdvancedSEO is NOT installed, in
     * which case the resolver falls back to the module default.
     */
    private function fetchStored(string $entityType, int $entityId, int $storeId): string
    {
        if ($entityId <= 0) {
            return '';
        }
        $connection = $this->resource->getConnection();
        $resolvedTable = $this->resource->getTableName('panth_seo_resolved');
        if (!$connection->isTableExists($resolvedTable)) {
            return '';
        }
        try {
            $select = $connection->select()
                ->from($resolvedTable, ['robots'])
                ->where('entity_type = ?', $entityType)
                ->where('entity_id = ?', $entityId)
                ->where('store_id = ?', $storeId)
                ->limit(1);
            return (string) $connection->fetchOne($select);
        } catch (\Throwable) {
            return '';
        }
    }

    private function isNoindexByUrlPattern(int $storeId): bool
    {
        $params = (array) $this->request->getParams();
        if ($params === []) {
            return false;
        }

        if ($this->config->isEnabled($storeId) && $this->hasFilterParams($params)
            && $this->config->isNoindexFiltered($storeId)) {
            return true;
        }

        $path = (string) $this->request->getPathInfo();
        if (str_contains($path, 'catalogsearch/result')
            && $this->config->isNoindexSearchResults($storeId)) {
            return true;
        }

        return false;
    }

    /**
     * @param array<string,mixed> $params
     */
    private function hasFilterParams(array $params): bool
    {
        $filterKeys = ['product_list_order', 'product_list_dir', 'product_list_limit', 'product_list_mode'];
        foreach ($filterKeys as $k) {
            if (isset($params[$k])) {
                return true;
            }
        }
        $safe = ['p', 'id', 'category', '___store', '___from_store'];
        foreach ($params as $key => $_) {
            if (!in_array($key, $safe, true) && !in_array($key, $filterKeys, true)) {
                return true;
            }
        }
        return false;
    }
}
