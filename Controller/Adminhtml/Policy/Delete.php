<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Controller\Adminhtml\Policy;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Panth\RobotsSeo\Controller\Adminhtml\AbstractAction;
use Panth\RobotsSeo\Model\ResourceModel\RobotsPolicy as PolicyResource;
use Panth\RobotsSeo\Model\Robots\Policy as PolicyModel;

/**
 * Delete a single robots policy row. Requires POST + FormKey (enforced by
 * HttpPostActionInterface) so `/admin/panth_robots_seo/policy/delete?policy_id=5`
 * cannot be called from a crafted GET link.
 */
class Delete extends AbstractAction implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Panth_RobotsSeo::policies_save';

    public function __construct(
        Context $context,
        private readonly PolicyModel $policyModel,
        private readonly PolicyResource $policyResource
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $policyId = (int) $this->getRequest()->getParam('policy_id', 0);
        if ($policyId <= 0) {
            $this->messageManager->addErrorMessage(__('Invalid policy id.'));
            return $resultRedirect->setPath('*/*/');
        }
        try {
            $this->policyResource->load($this->policyModel, $policyId);
            if (!$this->policyModel->getId()) {
                $this->messageManager->addErrorMessage(__('Policy not found.'));
                return $resultRedirect->setPath('*/*/');
            }
            $this->policyResource->delete($this->policyModel);
            $this->messageManager->addSuccessMessage(__('Robots policy deleted.'));
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('Unable to delete: %1', $e->getMessage()));
        }
        return $resultRedirect->setPath('*/*/');
    }
}
