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

class RobotsMetaPlugin
{
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

            if ($requestPath === '/robots.txt') {
                return (string) $result;
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
            if ($resolved !== '') {
                return $resolved;
            }
        } catch (\Throwable $e) {
            $this->logger->debug('Panth RobotsSeo RobotsMetaPlugin: ' . $e->getMessage());
        }

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
