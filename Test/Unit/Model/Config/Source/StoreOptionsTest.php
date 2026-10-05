<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Test\Unit\Model\Config\Source;

use Magento\Store\Model\System\Store as SystemStore;
use Panth\RobotsSeo\Model\Config\Source\StoreOptions;
use PHPUnit\Framework\TestCase;

class StoreOptionsTest extends TestCase
{
    public function testAllStoreViewsComesFirstFollowedByStoreViews(): void
    {
        $systemStore = $this->createStub(SystemStore::class);
        $systemStore->method('toOptionArray')->willReturn([
            ['value' => '1', 'label' => 'Default Store View'],
            ['value' => '2', 'label' => 'Luma Store View'],
        ]);

        $options = (new StoreOptions($systemStore))->toOptionArray();

        $this->assertCount(3, $options);
        $this->assertSame(0, $options[0]['value']);
        $this->assertSame('All Store Views', (string) $options[0]['label']);
        $this->assertSame('1', $options[1]['value']);
        $this->assertSame('Luma Store View', $options[2]['label']);
    }

    public function testAdminStoreOptionIsNotDuplicated(): void
    {
        $systemStore = $this->createStub(SystemStore::class);
        $systemStore->method('toOptionArray')->willReturn([
            ['value' => '0', 'label' => 'Admin'],
            ['value' => '1', 'label' => 'Default Store View'],
        ]);

        $options = (new StoreOptions($systemStore))->toOptionArray();

        $this->assertSame([0, '1'], array_column($options, 'value'));
    }

    public function testNoStoreViewsStillOffersAllStoreViews(): void
    {
        $systemStore = $this->createStub(SystemStore::class);
        $systemStore->method('toOptionArray')->willReturn([]);

        $options = (new StoreOptions($systemStore))->toOptionArray();

        $this->assertSame([0], array_column($options, 'value'));
    }
}
