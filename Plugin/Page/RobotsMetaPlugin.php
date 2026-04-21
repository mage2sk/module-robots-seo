<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Plugin\Page;

use Magento\Framework\App\Area;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Store\Model\StoreManagerInterface;
use Panth\RobotsSeo\Helper\Config as RobotsConfig;
use Panth\RobotsSeo\Model\Robots\MetaResolver;
use Panth\RobotsSeo\Service\DirectiveValidator;
use Panth\RobotsSeo\Service\NoindexPathMatcher;
use Psr\Log\LoggerInterface;

/**
 * Rewrites the HTML `<meta name="robots">` tag so the rendered head matches
 * the `X-Robots-Tag` HTTP header emitted by `XRobotsTagPlugin`.
 *
 * Magento core pulls the tag value from `design/search_engine_robots/default_robots`
 * (a free-text config under Stores > Configuration > General > Design) and
 * emits it verbatim. That field has no awareness of:
 *
 *  - Store-level LLM / robots toggles in this module,
 *  - Catalogsearch / layered-nav noindex rules,
 *  - Configured noindex path patterns (customer, checkout, etc.),
 *  - HTTP status code (404/410/503 must not be indexed),
 *  - The `max-image-preview` / `max-snippet` advanced directives.
 *
 * This plugin is an `afterGetRobots` on `PageConfig` so every rendered
 * frontend page — including Hyva pages built via Alpine/Tailwind — gets
 * the correct directive. Admin pages are exempt via the area gate.
 */
class RobotsMetaPlugin
{
    /** @var string[] */
    private const NOINDEX_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];

    public function __construct(
        private readonly AppState $appState,
        private readonly RequestInterface $request,
        private readonly StoreManagerInterface $storeManager,
        private readonly RobotsConfig $config,
        private readonly MetaResolver $metaResolver,
        private readonly NoindexPathMatcher $noindexPathMatcher,
        private readonly DirectiveValidator $validator,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param PageConfig $subject
     * @param string     $result Original robots value from Magento core.
     */
    public function afterGetRobots(PageConfig $subject, $result): string
    {
        try {
            if (!$this->isFrontend()) {
                return (string) $result;
            }

            $storeId = (int) $this->storeManager->getStore()->getId();

            if (!$this->config->isEnabled($storeId)) {
                return (string) $result;
            }

            $requestUri = (string) $this->request->getRequestUri();
            $requestPath = (string) (parse_url($requestUri, PHP_URL_PATH) ?? '');

            // `/robots.txt` is served by our own controller that returns a
            // plain-text body; the `<meta>` path never executes there, but
            // guard anyway.
            if ($requestPath === '/robots.txt') {
                return (string) $result;
            }

            // Non-HTML assets (PDFs, Office docs) must not be indexed even
            // when a page config happens to render over them.
            if ($this->isNoindexAssetUrl($requestUri)) {
                return 'noindex,nofollow';
            }

            // Catalogsearch / layered-nav checks run BEFORE the generic
            // noindex-path matcher so `/catalogsearch/*` emits `noindex,follow`
            // (letting crawlers walk result links) instead of the matcher's
            // blanket `noindex,nofollow`.
            if (str_starts_with($requestPath, '/catalogsearch/')
                && $this->config->isNoindexSearchResults($storeId)) {
                return 'noindex,follow';
            }

            // Private / customer-scoped paths win over any per-entity default.
            if ($this->noindexPathMatcher->isNoindexPath($requestUri, $storeId)) {
                return 'noindex,nofollow';
            }

            // Everything else: delegate to MetaResolver. It honours URL-pattern
            // noindex (layered-nav), per-entity stored overrides, and the
            // default directive. `resolveWithDirectives` appends
            // max-image-preview / max-snippet so meta matches the HTTP header.
            $resolved = $this->metaResolver->resolveWithDirectives('', 0, $storeId);
            if ($resolved !== '') {
                return $resolved;
            }
        } catch (\Throwable $e) {
            $this->logger->debug('Panth RobotsSeo RobotsMetaPlugin: ' . $e->getMessage());
        }

        // Fall back to whatever Magento core returned so we never leave the
        // tag empty.
        return $this->validator->sanitizeDirective((string) $result);
    }

    private function isFrontend(): bool
    {
        try {
            return $this->appState->getAreaCode() === Area::AREA_FRONTEND;
        } catch (\Throwable) {
            return false;
        }
    }

    private function isNoindexAssetUrl(string $uri): bool
    {
        $path = strtolower((string) (parse_url($uri, PHP_URL_PATH) ?? ''));
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        return in_array($ext, self::NOINDEX_EXTENSIONS, true);
    }
}
