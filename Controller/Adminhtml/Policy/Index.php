<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Controller\Adminhtml\Policy;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;
use Panth\RobotsSeo\Controller\Adminhtml\AbstractAction;

class Index extends AbstractAction implements HttpGetActionInterface
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
        $page->setActiveMenu('Panth_RobotsSeo::policies_menu');
        $page->getConfig()->getTitle()->prepend(__('Robots Policies'));
        return $page;
    }
}
