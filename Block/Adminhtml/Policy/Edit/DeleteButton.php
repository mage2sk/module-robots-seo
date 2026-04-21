<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Block\Adminhtml\Policy\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        $id = $this->getPolicyId();
        if ($id === 0) {
            return [];
        }
        $url = $this->getUrl('*/*/delete', ['policy_id' => $id]);
        return [
            'label'      => (string) __('Delete'),
            'class'      => 'delete',
            'on_click'   => sprintf(
                "deleteConfirm('%s', '%s')",
                __('Are you sure you want to delete this row?'),
                $url
            ),
            'sort_order' => 20,
        ];
    }
}
