<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Controller\Adminhtml\Robots;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Panth\RobotsSeo\Controller\Adminhtml\AbstractAction;

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
