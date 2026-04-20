<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Controller\Robots;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\RobotsSeo\Api\RobotsPolicyInterface;

/**
 * Serves a dynamic robots.txt for the current store from PolicyResolver.
 *
 * Wired via `etc/frontend/routes.xml` (frontName `panth_robots_seo`) AND via a
 * URL rewrite that maps `/robots.txt` -> `seo_robots/robots/index`. The
 * Magento_Robots core router is disabled in `etc/frontend/di.xml` so our
 * rewrite actually takes precedence.
 */
class Index implements HttpGetActionInterface
{
    public function __construct(
        private readonly RawFactory $rawFactory,
        private readonly RobotsPolicyInterface $robotsPolicy,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function execute(): ResponseInterface|ResultInterface
    {
        $storeId = (int) $this->storeManager->getStore()->getId();
        $body = $this->robotsPolicy->getRobotsTxt($storeId);
        $result = $this->rawFactory->create();
        $result->setHeader('Content-Type', 'text/plain; charset=utf-8', true);
        // The robots.txt file itself should not be indexed.
        $result->setHeader('X-Robots-Tag', 'noindex', true);
        $result->setContents($body);
        return $result;
    }
}
