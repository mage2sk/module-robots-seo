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

class XRobotsTagPlugin
{
    private const NOINDEX_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];

    private const NOINDEX_STATUS_CODES = [404, 410, 500, 503];

    public function __construct(
        private readonly AppState $appState,
        private readonly RobotsPolicyInterface $robotsPolicy,
        private readonly RobotsConfig $config,
        private readonly RequestInterface $request,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger,
        private readonly DirectiveValidator $validator,
        private readonly RobotsMetaResolver $robotsMetaResolver,
        private readonly NoindexPathMatcher $noindexPathMatcher
    ) {
    }

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

            $requestUri = (string) $this->request->getRequestUri();

            $requestPath = (string) (parse_url($requestUri, PHP_URL_PATH) ?? '');
            if ($requestPath === '/robots.txt') {
                return;
            }

            if (in_array((int) $subject->getStatusCode(), self::NOINDEX_STATUS_CODES, true)) {
                $this->setHeaderSafely($subject, 'noindex, nofollow');
                return;
            }

            if ($this->isNoindexAssetUrl($requestUri)) {
                $this->setHeaderSafely($subject, 'noindex, nofollow');
                return;
            }

            if ($this->isSearchResultPath($requestUri) && $this->config->isNoindexSearchResults($storeId)) {
                $this->setHeaderSafely($subject, 'noindex, follow');
                return;
            }

            if ($this->noindexPathMatcher->isNoindexPath($requestUri, $storeId)) {
                $this->setHeaderSafely($subject, 'noindex, nofollow');
                return;
            }

            $robots = $this->robotsPolicy->getHeaderRobots('', 0, $storeId);
            $robots = $this->robotsMetaResolver->appendAdvancedDirectives($robots, $storeId);
            if ($robots === '') {
                $robots = 'index, follow';
            }
            $this->setHeaderSafely($subject, $robots);
        } catch (\Throwable $e) {
            $this->logger->debug('Panth RobotsSeo XRobotsTagPlugin: ' . $e->getMessage());
        }
    }

    private function setHeaderSafely(HttpResponse $subject, string $directive): void
    {
        $safe = $this->validator->sanitizeDirective($directive);

        if (preg_match('/[\x00-\x1F\x7F]/', $safe)) {
            $safe = DirectiveValidator::DEFAULT_DIRECTIVE;
        }
        $subject->setHeader('X-Robots-Tag', $safe, true);

        try {
            $storeId = (int) $this->storeManager->getStore()->getId();
            if ($this->config->isDebug($storeId)) {
                $this->logger->info(sprintf(
                    'X-Robots-Tag set uri=%s status=%d directive=%s',
                    (string) $this->request->getRequestUri(),
                    (int) $subject->getStatusCode(),
                    $safe
                ));
            }
        } catch (\Throwable) {
        }
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
