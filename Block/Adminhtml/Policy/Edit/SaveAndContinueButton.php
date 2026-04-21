<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Block\Adminhtml\Policy\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class SaveAndContinueButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        return [
            'label'          => (string) __('Save & Continue Edit'),
            'class'          => 'save',
            'data_attribute' => [
                'mage-init' => ['button' => ['event' => 'saveAndContinueEdit']],
            ],
            'sort_order'     => 80,
        ];
    }
}
