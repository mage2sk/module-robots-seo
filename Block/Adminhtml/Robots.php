<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Panth\RobotsSeo\Api\RobotsPolicyInterface;

/**
 * Backend preview block for the rendered robots.txt body. Pulls the current
 * body straight from PolicyResolver so the admin sees the EXACT same output
 * that the frontend controller would emit for the current store scope.
 */
class Robots extends Template
{
    /** @var string */
    protected $_template = 'Panth_RobotsSeo::robots.phtml';

    public function __construct(
        Context $context,
        private readonly RobotsPolicyInterface $robotsPolicy,
        private readonly StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Return the rendered robots.txt body for the current store scope.
     * The result is pre-validated by PolicyResolver so echoing it through
     * `escapeHtml()` in the template is enough to prevent any stored XSS
     * from a tampered DB row.
     */
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
