<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public const XML_SECTION = 'panth_robots_seo';

    public const XML_GENERAL_ENABLED              = self::XML_SECTION . '/general/enabled';
    public const XML_GENERAL_DEBUG                = self::XML_SECTION . '/general/debug';
    public const XML_GENERAL_DEFAULT_DIRECTIVE    = self::XML_SECTION . '/general/default_directive';
    public const XML_GENERAL_NOINDEX_FILTERED     = self::XML_SECTION . '/general/noindex_filtered';
    public const XML_GENERAL_NOINDEX_SEARCH       = self::XML_SECTION . '/general/noindex_search_results';
    public const XML_GENERAL_NOINDEX_PATHS        = self::XML_SECTION . '/general/noindex_paths';
    public const XML_GENERAL_IGNORED_QUERY_PARAMS = self::XML_SECTION . '/general/ignored_query_params';
    public const XML_GENERAL_MAX_IMAGE_PREVIEW    = self::XML_SECTION . '/general/max_image_preview';
    public const XML_GENERAL_MAX_SNIPPET          = self::XML_SECTION . '/general/max_snippet';
    public const XML_GENERAL_CRAWL_DELAY          = self::XML_SECTION . '/general/crawl_delay';

    public const XML_LLM_BOT_PREFIX               = self::XML_SECTION . '/llm_bots/';

    public const XML_ROBOTSTXT_OVERRIDE           = self::XML_SECTION . '/robots_txt/override_enabled';
    public const XML_ROBOTSTXT_CUSTOM             = self::XML_SECTION . '/robots_txt/custom_body';
    public const XML_ROBOTSTXT_SITEMAP_URLS       = self::XML_SECTION . '/robots_txt/sitemap_urls';

    public const XML_XML_SITEMAP_INDEX_FILENAME   = 'panth_xml_sitemap/generation/index_filename';
    public const XML_XML_SITEMAP_ENABLED          = 'panth_xml_sitemap/general/enabled';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->flag(self::XML_GENERAL_ENABLED, $storeId);
    }

    public function isDebug(?int $storeId = null): bool
    {
        return $this->flag(self::XML_GENERAL_DEBUG, $storeId);
    }

    public function getDefaultDirective(?int $storeId = null): string
    {
        $raw = (string) ($this->value(self::XML_GENERAL_DEFAULT_DIRECTIVE, $storeId) ?? 'index,follow');
        return $raw !== '' ? $raw : 'index,follow';
    }

    public function isNoindexFiltered(?int $storeId = null): bool
    {
        return $this->flag(self::XML_GENERAL_NOINDEX_FILTERED, $storeId);
    }

    public function isNoindexSearchResults(?int $storeId = null): bool
    {
        return $this->flag(self::XML_GENERAL_NOINDEX_SEARCH, $storeId);
    }

    public function getNoindexPaths(?int $storeId = null): string
    {
        return (string) ($this->value(self::XML_GENERAL_NOINDEX_PATHS, $storeId) ?? '');
    }

    public function getIgnoredQueryParams(?int $storeId = null): array
    {
        $raw = (string) ($this->value(self::XML_GENERAL_IGNORED_QUERY_PARAMS, $storeId) ?? '');
        if (trim($raw) === '') {
            return [];
        }

        $params = preg_split('/[\s,]+/', $raw) ?: [];
        $params = array_filter(array_map(
            static fn ($param) => strtolower(trim((string) $param)),
            $params
        ));

        return array_values(array_unique($params));
    }

    public function getMaxImagePreview(?int $storeId = null): string
    {
        $value = (string) ($this->value(self::XML_GENERAL_MAX_IMAGE_PREVIEW, $storeId) ?? 'large');
        $allowed = ['none', 'standard', 'large'];
        return in_array($value, $allowed, true) ? $value : 'large';
    }

    public function getMaxSnippet(?int $storeId = null): int
    {
        $value = trim((string) ($this->value(self::XML_GENERAL_MAX_SNIPPET, $storeId) ?? ''));
        return $value === '' ? -1 : (int) $value;
    }

    public function getCrawlDelay(?int $storeId = null): int
    {
        return max(0, (int) ($this->value(self::XML_GENERAL_CRAWL_DELAY, $storeId) ?? 0));
    }

    public function isLlmBotAllowed(string $bot, ?int $storeId = null): bool
    {
        return $this->flag(self::XML_LLM_BOT_PREFIX . $bot, $storeId);
    }

    public function isRobotsTxtOverrideEnabled(?int $storeId = null): bool
    {
        return $this->flag(self::XML_ROBOTSTXT_OVERRIDE, $storeId);
    }

    public function getCustomRobotsTxt(?int $storeId = null): string
    {
        return (string) ($this->value(self::XML_ROBOTSTXT_CUSTOM, $storeId) ?? '');
    }

    private function flag(string $path, ?int $storeId): bool
    {
        return (bool) $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getSitemapUrls(?int $storeId = null): array
    {
        $raw = (string) ($this->value(self::XML_ROBOTSTXT_SITEMAP_URLS, $storeId) ?? '');
        if (trim($raw) === '') {
            return [];
        }

        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $line = trim(str_replace(["\0", "\r", "\n"], '', $line));
            if ($line !== '') {
                $out[] = $line;
            }
        }

        return $out;
    }

    public function getXmlSitemapIndexFilename(?int $storeId = null): string
    {
        $name = basename(trim((string) ($this->value(self::XML_XML_SITEMAP_INDEX_FILENAME, $storeId) ?? '')));
        return preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.xml$/', $name) === 1 ? $name : 'sitemap.xml';
    }

    public function isXmlSitemapEnabled(?int $storeId = null): bool
    {
        $value = $this->value(self::XML_XML_SITEMAP_ENABLED, $storeId);
        return $value === null || $value === '' || (bool) (int) $value;
    }

    private function value(string $path, ?int $storeId): ?string
    {
        $v = $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
        return $v === null ? null : (string) $v;
    }
}
