<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Controller\Adminhtml\Robots;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Panth\RobotsSeo\Controller\Adminhtml\AbstractAction;

/**
 * Admin-side preview of the generated robots.txt body. Renders the output of
 * PolicyResolver::getRobotsTxt() inside a read-only textarea so merchants can
 * verify the current state before testing on the storefront.
 */
class Index extends AbstractAction
{
    public const ADMIN_RESOURCE = 'Panth_RobotsSeo::robots';

    public function __construct(
        Context $context,
        private readonly PageFactory $pageFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('Panth_RobotsSeo::robots');
        $page->getConfig()->getTitle()->prepend(__('robots.txt Preview'));
        return $page;
    }
}
