<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Service;

use Panth\RobotsSeo\Helper\Config;

/**
 * Matches request paths against the configured noindex pattern list so
 * private / customer-scoped pages never emit an indexable robots directive.
 *
 * Patterns are newline-separated path expressions. Leading slash is optional;
 * `*` is a wildcard (zero or more characters); blank lines and lines starting
 * with `#` are ignored.
 *
 * Ported from Panth_AdvancedSEO — unchanged semantics, new namespace.
 */
class NoindexPathMatcher
{
    /**
     * Fallback default pattern list used when the admin field is blank.
     */
    public const DEFAULT_PATTERNS = [
        '/customer/*',
        '/checkout',
        '/checkout/*',
        '/wishlist',
        '/wishlist/*',
        '/sales/*',
        '/contact',
        '/contact/*',
        '/contacts',
        '/contacts/*',
        '/catalogsearch/*',
        '/multishipping/*',
        '/newsletter/manage',
        '/newsletter/manage/*',
        '/review/customer/*',
        '/captcha',
        '/captcha/*',
        '/sendfriend/*',
        '/paypal/*',
        '/downloadable/customer/*',
        '/vault/*',
        '/giftcard/customer/*',
        '/rewards/*',
        '/oauth/*',
        '/connect/*',
    ];

    /**
     * Cached compiled regex, keyed by store id.
     *
     * @var array<int,string>
     */
    private array $compiled = [];

    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * Return true when the given request path matches any of the configured
     * noindex patterns.
     */
    public function isNoindexPath(string $path, ?int $storeId = null): bool
    {
        $normalized = $this->normalizePath($path);
        if ($normalized === '') {
            return false;
        }
        $regex = $this->regexForStore($storeId);
        if ($regex === '') {
            return false;
        }
        return (bool) preg_match($regex, $normalized);
    }

    /**
     * @return string[]
     */
    public function getPatterns(?int $storeId = null): array
    {
        $raw = trim($this->config->getNoindexPaths($storeId));
        if ($raw === '') {
            return self::DEFAULT_PATTERNS;
        }
        $patterns = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $patterns[] = $line;
        }
        return $patterns === [] ? self::DEFAULT_PATTERNS : $patterns;
    }

    private function regexForStore(?int $storeId): string
    {
        $key = (int) ($storeId ?? 0);
        if (isset($this->compiled[$key])) {
            return $this->compiled[$key];
        }
        $parts = [];
        foreach ($this->getPatterns($storeId) as $pattern) {
            $normalized = $this->normalizePath($pattern);
            if ($normalized === '') {
                continue;
            }
            $escaped = preg_quote($normalized, '#');
            $escaped = str_replace('\*', '.*', $escaped);
            $parts[] = '(?:' . $escaped . ')';
        }
        if ($parts === []) {
            return $this->compiled[$key] = '';
        }
        return $this->compiled[$key] = '#^(?:' . implode('|', $parts) . ')/?$#i';
    }

    private function normalizePath(string $value): string
    {
        if ($value === '') {
            return '';
        }
        $path = parse_url($value, PHP_URL_PATH);
        if ($path === null || $path === false) {
            $path = $value;
        }
        $path = (string) $path;
        $path = '/' . ltrim($path, '/');
        $path = preg_replace('#/+#', '/', $path) ?? $path;
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }
        return $path;
    }
}
