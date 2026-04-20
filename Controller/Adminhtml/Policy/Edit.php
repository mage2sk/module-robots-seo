<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Controller\Adminhtml\Policy;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Panth\RobotsSeo\Controller\Adminhtml\AbstractAction;
use Panth\RobotsSeo\Model\Robots\Policy as PolicyModel;
use Panth\RobotsSeo\Model\ResourceModel\RobotsPolicy as PolicyResource;

/**
 * Edit form for a single `panth_seo_robots_policy` row. The row id is
 * explicitly cast to int on read so crafted query strings cannot confuse
 * `ResourceModel::load()` with non-numeric values.
 */
class Edit extends AbstractAction
{
    public const ADMIN_RESOURCE = 'Panth_RobotsSeo::policies';

    public function __construct(
        Context $context,
        private readonly PageFactory $pageFactory,
        private readonly Registry $registry,
        private readonly PolicyModel $policyModel,
        private readonly PolicyResource $policyResource
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $policyId = (int) $this->getRequest()->getParam('policy_id', 0);
        $model = $this->policyModel;
        if ($policyId > 0) {
            $this->policyResource->load($model, $policyId);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('Policy not found.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }
        $this->registry->register('panth_robots_seo_policy', $model);

        $page = $this->pageFactory->create();
        $page->setActiveMenu('Panth_RobotsSeo::policies');
        $page->getConfig()->getTitle()->prepend(
            $policyId > 0 ? __('Edit Robots Policy') : __('New Robots Policy')
        );
        return $page;
    }
}
