<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Test\Unit\Block\Adminhtml;

use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\RobotsSeo\Api\RobotsPolicyInterface;
use Panth\RobotsSeo\Block\Adminhtml\Robots;
use PHPUnit\Framework\TestCase;

class RobotsTest extends TestCase
{
    /**
     * Builds the block without the backend Template constructor (which needs the object manager)
     * and injects its own collaborators.
     */
    private function block(RobotsPolicyInterface $policy, StoreManagerInterface $storeManager): Robots
    {
        $block = (new \ReflectionClass(Robots::class))->newInstanceWithoutConstructor();
        \Closure::bind(function () use ($policy, $storeManager) {
            $this->robotsPolicy = $policy;
            $this->storeManager = $storeManager;
        }, $block, Robots::class)();
        return $block;
    }

    private function store(int $id, string $baseUrl): Store
    {
        $store = $this->createStub(Store::class);
        $store->method('getId')->willReturn($id);
        $store->method('getBaseUrl')->willReturn($baseUrl);
        return $store;
    }

    public function testPreviewUsesDefaultStoreView(): void
    {
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getDefaultStoreView')->willReturn($this->store(2, 'https://shop.test/'));
        $policy = $this->createMock(RobotsPolicyInterface::class);
        $policy->expects($this->once())->method('getRobotsTxt')->with(2)->willReturn('body');

        $block = $this->block($policy, $storeManager);

        $this->assertSame('body', $block->getRobotsContent());
        $this->assertSame('https://shop.test/robots.txt', $block->getFrontendUrl());
    }

    public function testFallsBackToCurrentStoreWithoutDefaultView(): void
    {
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getDefaultStoreView')->willReturn(null);
        $storeManager->method('getStore')->willReturn($this->store(5, 'https://b.test'));
        $policy = $this->createMock(RobotsPolicyInterface::class);
        $policy->expects($this->once())->method('getRobotsTxt')->with(5)->willReturn('b');

        $block = $this->block($policy, $storeManager);

        $this->assertSame('b', $block->getRobotsContent());
        $this->assertSame('https://b.test/robots.txt', $block->getFrontendUrl());
    }

    public function testStoreErrorsFallBackToAdminStoreAndRelativeUrl(): void
    {
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getDefaultStoreView')->willThrowException(new \RuntimeException('x'));
        $policy = $this->createMock(RobotsPolicyInterface::class);
        $policy->expects($this->once())->method('getRobotsTxt')->with(0)->willReturn('fallback');

        $block = $this->block($policy, $storeManager);

        $this->assertSame('fallback', $block->getRobotsContent());
        $this->assertSame('/robots.txt', $block->getFrontendUrl());
    }
}
