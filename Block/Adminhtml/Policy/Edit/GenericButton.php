<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Block\Adminhtml\Policy\Edit;

use Magento\Backend\Block\Widget\Context;

/**
 * Shared helpers for the admin form button providers — resolve the
 * editing policy_id and build URLs that stay within our admin namespace.
 */
abstract class GenericButton
{
    public function __construct(
        protected readonly Context $context
    ) {
    }

    public function getPolicyId(): int
    {
        return (int) $this->context->getRequest()->getParam('policy_id');
    }

    protected function getUrl(string $route, array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
