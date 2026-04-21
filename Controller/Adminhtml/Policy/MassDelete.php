<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Controller\Adminhtml\Policy;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Ui\Component\MassAction\Filter;
use Panth\RobotsSeo\Controller\Adminhtml\AbstractAction;
use Panth\RobotsSeo\Model\ResourceModel\RobotsPolicy\CollectionFactory;

/**
 * Mass-delete selected robots policy rows from the admin grid. POST-only +
 * FormKey enforced via HttpPostActionInterface.
 */
class MassDelete extends AbstractAction implements HttpPostActionInterface
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
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $count = 0;
            foreach ($collection as $row) {
                $row->delete();
                $count++;
            }
            $this->messageManager->addSuccessMessage(
                __('Deleted %1 robots policy row(s).', $count)
            );
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(
                __('Unable to delete selected rows: %1', $e->getMessage())
            );
        }
        return $resultRedirect->setPath('*/*/');
    }
}
