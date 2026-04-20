<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Source model for the policy `directive` column (allow / disallow).
 */
class DirectiveAction implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'allow',    'label' => __('Allow')],
            ['value' => 'disallow', 'label' => __('Disallow')],
        ];
    }
}
