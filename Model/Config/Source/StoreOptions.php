<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\System\Store as SystemStore;

class StoreOptions implements OptionSourceInterface
{
    public function __construct(
        private readonly SystemStore $systemStore
    ) {
    }

    public function toOptionArray(): array
    {
        $options = [['value' => 0, 'label' => __('All Store Views')]];
        foreach ($this->systemStore->toOptionArray() as $option) {
            if ((int) ($option['value'] ?? 0) === 0) {
                continue;
            }
            $options[] = $option;
        }
        return $options;
    }
}
