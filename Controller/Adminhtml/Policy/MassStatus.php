<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Controller\Adminhtml\Policy;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Ui\Component\MassAction\Filter;
use Panth\RobotsSeo\Controller\Adminhtml\AbstractAction;
use Panth\RobotsSeo\Model\ResourceModel\RobotsPolicy\CollectionFactory;

class MassStatus extends AbstractAction implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Panth_RobotsSeo::policies_save';

    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $rawStatus = $this->getRequest()->getParam('status');
        if ($rawStatus === null || $rawStatus === '') {
            $this->messageManager->addErrorMessage(__('Please choose a status to apply.'));
            return $resultRedirect->setPath('*/*/');
        }
        $status = (int) ((bool) $rawStatus);
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $count = 0;
            foreach ($collection as $row) {
                $row->setData('is_active', $status);
                $row->save();
                $count++;
            }
            $this->messageManager->addSuccessMessage(
                __('%1 robots policy row(s) were %2.', $count, $status ? 'enabled' : 'disabled')
            );
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(
                __('Unable to update selected rows: %1', $e->getMessage())
            );
        }
        return $resultRedirect->setPath('*/*/');
    }
}
