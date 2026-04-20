<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Controller\Adminhtml\Policy;

use Magento\Framework\Controller\ResultFactory;
use Panth\RobotsSeo\Controller\Adminhtml\AbstractAction;

/**
 * Redirect the Robots Policy "Add New" button to the edit form with no id.
 * Using a dedicated action avoids leaking the internal edit-form path to the
 * toolbar configuration.
 */
class NewAction extends AbstractAction
{
    public const ADMIN_RESOURCE = 'Panth_RobotsSeo::policies_save';

    public function execute()
    {
        $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
        $resultForward->forward('edit');
        return $resultForward;
    }
}
