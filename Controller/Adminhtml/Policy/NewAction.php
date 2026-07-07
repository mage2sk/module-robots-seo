<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Controller\Adminhtml\Policy;

use Magento\Framework\Controller\ResultFactory;
use Panth\RobotsSeo\Controller\Adminhtml\AbstractAction;

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
