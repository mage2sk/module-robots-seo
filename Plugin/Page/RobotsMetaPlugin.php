<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Plugin\Page;

use Magento\Framework\App\Area;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\State as AppState;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Store\Model\StoreManagerInterface;
use Panth\RobotsSeo\Helper\Config as RobotsConfig;
use Panth\RobotsSeo\Model\Robots\MetaResolver;
use Panth\RobotsSeo\Service\DirectiveMerger;
use Panth\RobotsSeo\Service\DirectiveValidator;
use Panth\RobotsSeo\Service\NoindexPathMatcher;
use Psr\Log\LoggerInterface;

class RobotsMetaPlugin
{
    private const NOINDEX_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];

    private const NOINDEX_STATUS_CODES = [404, 410, 500, 503];

    public function __construct(
        private readonly AppState $appState,
        private readonly RequestInterface $request,
        private readonly StoreManagerInterface $storeManager,
        private readonly RobotsConfig $config,
        private readonly MetaResolver $metaResolver,
        private readonly NoindexPathMatcher $noindexPathMatcher,
        private readonly DirectiveValidator $validator,
        private readonly LoggerInterface $logger,
        private readonly DirectiveMerger $merger,
        private readonly HttpResponse $response
    ) {
    }

    public function afterGetRobots(PageConfig $subject, $result): string
    {
        $incoming = (string) $result;
        try {
            if (!$this->isFrontend()) {
                return $incoming;
            }

            $storeId = (int) $this->storeManager->getStore()->getId();

            if (!$this->config->isEnabled($storeId)) {
                return $incoming;
            }

            $requestUri = (string) $this->request->getRequestUri();
            $requestPath = (string) (parse_url($requestUri, PHP_URL_PATH) ?? '');

            if ($requestPath === '/robots.txt') {
                return $incoming;
            }

            $robots = $this->resolve($incoming, $requestUri, $requestPath, $storeId);
            $this->response->setHeader('X-Robots-Tag', $robots, true);
            return $robots;
        } catch (\Throwable $e) {
            $this->logger->debug('Panth RobotsSeo RobotsMetaPlugin: ' . $e->getMessage());
        }

        return $this->validator->sanitizeDirective($incoming);
    }

    private function resolve(string $incoming, string $requestUri, string $requestPath, int $storeId): string
    {
        if (in_array((int) $this->response->getStatusCode(), self::NOINDEX_STATUS_CODES, true)) {
            return 'noindex,nofollow';
        }

        if ($this->isNoindexAssetUrl($requestUri)) {
            return 'noindex,nofollow';
        }

        if (str_starts_with($requestPath, '/catalogsearch/')
            && $this->config->isNoindexSearchResults($storeId)) {
            return 'noindex,follow';
        }

        if ($this->noindexPathMatcher->isNoindexPath($requestUri, $storeId)) {
            return 'noindex,nofollow';
        }

        $resolved = $this->metaResolver->resolveWithDirectives('', 0, $storeId);
        $merged = $this->merger->merge($resolved, $incoming);
        if ($merged !== '') {
            return $merged;
        }

        return $this->validator->sanitizeDirective($incoming);
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
