<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Plugin\Response;

use Magento\Framework\App\Area;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\State as AppState;
use Magento\Store\Model\StoreManagerInterface;
use Panth\RobotsSeo\Api\RobotsPolicyInterface;
use Panth\RobotsSeo\Helper\Config as RobotsConfig;
use Panth\RobotsSeo\Model\Robots\MetaResolver as RobotsMetaResolver;
use Panth\RobotsSeo\Service\DirectiveValidator;
use Panth\RobotsSeo\Service\NoindexPathMatcher;
use Psr\Log\LoggerInterface;

/**
 * Adds the X-Robots-Tag HTTP header on frontend responses.
 *
 * Reinforces the HTML `<meta name="robots">` directive at the HTTP level.
 * For non-HTML assets (PDFs, images served through Magento controllers),
 * the header is emitted based on a URL pattern check so search engines
 * receive consistent robots directives regardless of content type.
 *
 * SECURITY DEFENCES applied in this order:
 *  - `getAreaCode() === frontend` gate — prevents leakage of admin responses.
 *  - Every directive value is run through DirectiveValidator BEFORE the
 *    header is set, so a tampered DB row containing CRLF or bogus tokens
 *    can never reach `setHeader()`.
 */
class XRobotsTagPlugin
{
    /** @var string[] URL extensions that should carry noindex by default */
    private const NOINDEX_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];

    /** @var int[] HTTP status codes that must never be indexed */
    private const NOINDEX_STATUS_CODES = [404, 410, 500, 503];

    public function __construct(
        private readonly AppState $appState,
        private readonly RobotsPolicyInterface $robotsPolicy,
        private readonly RobotsConfig $config,
        private readonly RequestInterface $request,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger,
        private readonly DirectiveValidator $validator,
        private readonly ?RobotsMetaResolver $robotsMetaResolver = null,
        private readonly ?NoindexPathMatcher $noindexPathMatcher = null
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSendResponse(HttpResponse $subject): void
    {
        try {
            if (!$this->isFrontendArea()) {
                return;
            }

            $storeId = (int) $this->storeManager->getStore()->getId();

            if (!$this->config->isEnabled($storeId)) {
                return;
            }

            // Error responses (404/410/5xx) must never be indexed, regardless of
            // any other configuration. The HTTP header takes precedence over
            // `<meta name="robots">` per Google docs.
            if (in_array((int) $subject->getStatusCode(), self::NOINDEX_STATUS_CODES, true)) {
                $this->setHeaderSafely($subject, 'noindex, nofollow');
                return;
            }

            // Non-HTML document responses (.pdf, .doc, ...) should not be
            // indexed by default.
            $requestUri = (string) $this->request->getRequestUri();
            if ($this->isNoindexAssetUrl($requestUri)) {
                $this->setHeaderSafely($subject, 'noindex, nofollow');
                return;
            }

            // Search results: noindex,follow so crawlers still traverse links.
            if ($this->isSearchResultPath($requestUri) && $this->config->isNoindexSearchResults($storeId)) {
                $this->setHeaderSafely($subject, 'noindex, follow');
                return;
            }

            // Private / customer-scoped paths — always noindex,nofollow.
            if ($this->noindexPathMatcher !== null
                && $this->noindexPathMatcher->isNoindexPath($requestUri, $storeId)) {
                $this->setHeaderSafely($subject, 'noindex, nofollow');
                return;
            }

            // Default HTML response branch.
            $robots = $this->robotsPolicy->getHeaderRobots('', 0, $storeId);
            if ($this->robotsMetaResolver !== null) {
                $robots = $this->robotsMetaResolver->appendAdvancedDirectives($robots, $storeId);
            }
            if ($robots === '') {
                $robots = 'index, follow';
            }
            $this->setHeaderSafely($subject, $robots);
        } catch (\Throwable $e) {
            $this->logger->debug('Panth RobotsSeo XRobotsTagPlugin: ' . $e->getMessage());
        }
    }

    /**
     * Validate the directive value and only then forward it to setHeader().
     * If validation fails the default `index, follow` is used so the header
     * is always present but never malicious.
     */
    private function setHeaderSafely(HttpResponse $subject, string $directive): void
    {
        $safe = $this->validator->sanitizeDirective($directive);
        // Final belt-and-braces check: if the directive still contains any
        // control byte, fall back to the hard default. Should be impossible
        // after sanitizeDirective() but keeps the header free of CR/LF under
        // any circumstances.
        if (preg_match('/[\x00-\x1F\x7F]/', $safe)) {
            $safe = DirectiveValidator::DEFAULT_DIRECTIVE;
        }
        $subject->setHeader('X-Robots-Tag', $safe, true);
    }

    private function isFrontendArea(): bool
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
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        return in_array($extension, self::NOINDEX_EXTENSIONS, true);
    }

    private function isSearchResultPath(string $uri): bool
    {
        $path = (string) (parse_url($uri, PHP_URL_PATH) ?? '');
        if ($path === '') {
            return false;
        }
        return str_starts_with($path, '/catalogsearch/');
    }
}
