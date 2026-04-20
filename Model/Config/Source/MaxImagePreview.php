<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Source model for the `max-image-preview` robots directive.
 */
class MaxImagePreview implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'large',    'label' => __('large (recommended)')],
            ['value' => 'standard', 'label' => __('standard')],
            ['value' => 'none',     'label' => __('none (omit directive)')],
        ];
    }
}
