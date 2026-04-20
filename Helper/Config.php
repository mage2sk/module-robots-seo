<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Single entry point for every robots-related system.xml value. Anything that
 * comes out of this helper is strictly typed and normalised so callers never
 * have to re-validate raw `ScopeConfig` values.
 */
class Config
{
    /** Root section for all robots-related system config. */
    public const XML_SECTION = 'panth_robots_seo';

    public const XML_GENERAL_ENABLED              = self::XML_SECTION . '/general/enabled';
    public const XML_GENERAL_DEBUG                = self::XML_SECTION . '/general/debug';
    public const XML_GENERAL_DEFAULT_DIRECTIVE    = self::XML_SECTION . '/general/default_directive';
    public const XML_GENERAL_NOINDEX_FILTERED     = self::XML_SECTION . '/general/noindex_filtered';
    public const XML_GENERAL_NOINDEX_SEARCH       = self::XML_SECTION . '/general/noindex_search_results';
    public const XML_GENERAL_NOINDEX_PATHS        = self::XML_SECTION . '/general/noindex_paths';
    public const XML_GENERAL_MAX_IMAGE_PREVIEW    = self::XML_SECTION . '/general/max_image_preview';
    public const XML_GENERAL_MAX_SNIPPET          = self::XML_SECTION . '/general/max_snippet';
    public const XML_GENERAL_CRAWL_DELAY          = self::XML_SECTION . '/general/crawl_delay';

    public const XML_LLM_BOT_PREFIX               = self::XML_SECTION . '/llm_bots/';

    public const XML_ROBOTSTXT_OVERRIDE           = self::XML_SECTION . '/robots_txt/override_enabled';
    public const XML_ROBOTSTXT_CUSTOM             = self::XML_SECTION . '/robots_txt/custom_body';

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

    /**
     * max-image-preview directive value: "none", "standard", or "large".
     */
    public function getMaxImagePreview(?int $storeId = null): string
    {
        $value = (string) ($this->value(self::XML_GENERAL_MAX_IMAGE_PREVIEW, $storeId) ?? 'large');
        $allowed = ['none', 'standard', 'large'];
        return in_array($value, $allowed, true) ? $value : 'large';
    }

    /**
     * max-snippet directive value: -1 (unlimited) or a positive character count.
     */
    public function getMaxSnippet(?int $storeId = null): int
    {
        return (int) ($this->value(self::XML_GENERAL_MAX_SNIPPET, $storeId) ?? -1);
    }

    /**
     * Crawl-delay directive for robots.txt (seconds). 0 means omit the directive.
     */
    public function getCrawlDelay(?int $storeId = null): int
    {
        return max(0, (int) ($this->value(self::XML_GENERAL_CRAWL_DELAY, $storeId) ?? 0));
    }

    public function isLlmBotAllowed(string $bot, ?int $storeId = null): bool
    {
        // `$bot` comes from a hard-coded whitelist in PolicyResolver::LLM_BOT_CONFIG_MAP
        // (not from user input) so direct concatenation is safe here.
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

    private function value(string $path, ?int $storeId): ?string
    {
        $v = $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
        return $v === null ? null : (string) $v;
    }
}
