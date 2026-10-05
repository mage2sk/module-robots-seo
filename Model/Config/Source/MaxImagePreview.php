<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class MaxImagePreview implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'large',    'label' => __('large (recommended)')],
            ['value' => 'standard', 'label' => __('standard')],
            ['value' => 'none',     'label' => __('none (omit directive)')],
        ];
    }
}
