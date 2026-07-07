<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Model\Robots;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\ScopeInterface;
use Panth\RobotsSeo\Helper\Config;
use Panth\RobotsSeo\Service\DirectiveValidator;

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

    public function resolveWithDirectives(string $entityType, int $entityId, int $storeId): string
    {
        return $this->appendAdvancedDirectives($this->resolve($entityType, $entityId, $storeId), $storeId);
    }

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
        $query = $this->request->getQuery()->toArray();

        if ($query !== [] && $this->config->isEnabled($storeId) && $this->hasFilterParams($query)
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
