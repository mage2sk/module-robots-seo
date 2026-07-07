<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class DirectiveAction implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'allow',    'label' => __('Allow')],
            ['value' => 'disallow', 'label' => __('Disallow')],
        ];
    }
}
