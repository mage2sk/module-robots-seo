<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Controller\Robots;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Panth\RobotsSeo\Api\RobotsPolicyInterface;
use Panth\RobotsSeo\Helper\Config;

class Index implements HttpGetActionInterface
{
    public function __construct(
        private readonly RawFactory $rawFactory,
        private readonly RobotsPolicyInterface $robotsPolicy,
        private readonly StoreManagerInterface $storeManager,
        private readonly Config $config,
        private readonly PageFactory $pageFactory
    ) {
    }

    public function execute(): ResponseInterface|ResultInterface
    {
        $storeId = (int) $this->storeManager->getStore()->getId();

        if (!$this->config->isEnabled($storeId)) {
            $page = $this->pageFactory->create(true);
            $page->addHandle('robots_index_index');
            $page->setHeader('Content-Type', 'text/plain');
            return $page;
        }

        $body = $this->robotsPolicy->getRobotsTxt($storeId);
        $result = $this->rawFactory->create();
        $result->setHeader('Content-Type', 'text/plain; charset=utf-8', true);

        $result->setHeader('X-Robots-Tag', 'noindex', true);
        $result->setContents($body);
        return $result;
    }
}
