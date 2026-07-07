<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Panth\RobotsSeo\Api\RobotsPolicyInterface;

class Robots extends Template
{
    protected $_template = 'Panth_RobotsSeo::robots.phtml';

    public function __construct(
        Context $context,
        private readonly RobotsPolicyInterface $robotsPolicy,
        private readonly StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getRobotsContent(): string
    {
        try {
            $storeId = (int) $this->storeManager->getStore()->getId();
        } catch (\Throwable) {
            $storeId = 0;
        }
        return $this->robotsPolicy->getRobotsTxt($storeId);
    }

    public function getFrontendUrl(): string
    {
        try {
            return rtrim((string) $this->storeManager->getStore()->getBaseUrl(), '/') . '/robots.txt';
        } catch (\Throwable) {
            return '/robots.txt';
        }
    }
}
