<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Controller\Adminhtml\Policy;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Panth\RobotsSeo\Controller\Adminhtml\AbstractAction;

/**
 * Admin grid page for `panth_seo_robots_policy` — lists user-agent /
 * path / directive rows.
 */
class Index extends AbstractAction
{
    public const ADMIN_RESOURCE = 'Panth_RobotsSeo::policies';

    public function __construct(
        Context $context,
        private readonly PageFactory $pageFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('Panth_RobotsSeo::policies');
        $page->getConfig()->getTitle()->prepend(__('Robots Policies'));
        return $page;
    }
}
