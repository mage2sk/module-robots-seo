<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Block\Adminhtml\Policy\Edit;

use Magento\Backend\Block\Widget\Context;

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
