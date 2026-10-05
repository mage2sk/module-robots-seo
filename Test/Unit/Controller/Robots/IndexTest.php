<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Test\Unit\Controller\Robots;

use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\RobotsSeo\Api\RobotsPolicyInterface;
use Panth\RobotsSeo\Controller\Robots\Index;
use Panth\RobotsSeo\Helper\Config;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    private array $headers = [];
    private ?string $contents = null;
    private array $handles = [];

    private function controller(bool $enabled, ?RobotsPolicyInterface $policy = null): array
    {
        $this->headers = $this->handles = [];
        $this->contents = null;

        $raw = $this->createStub(Raw::class);
        $raw->method('setHeader')->willReturnCallback(function ($name, $value) use ($raw) {
            $this->headers[$name] = $value;
            return $raw;
        });
        $raw->method('setContents')->willReturnCallback(function ($c) use ($raw) {
            $this->contents = $c;
            return $raw;
        });
        $rawFactory = $this->createStub(RawFactory::class);
        $rawFactory->method('create')->willReturn($raw);

        $page = $this->createStub(Page::class);
        $page->method('addHandle')->willReturnCallback(function ($h) use ($page) {
            $this->handles[] = $h;
            return $page;
        });
        $page->method('setHeader')->willReturnCallback(function ($name, $value) use ($page) {
            $this->headers[$name] = $value;
            return $page;
        });
        $pageFactory = $this->createStub(PageFactory::class);
        $pageFactory->method('create')->willReturn($page);

        $store = $this->createStub(Store::class);
        $store->method('getId')->willReturn(3);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $config = $this->createStub(Config::class);
        $config->method('isEnabled')->willReturn($enabled);

        $controller = new Index(
            $rawFactory,
            $policy ?? $this->createStub(RobotsPolicyInterface::class),
            $storeManager,
            $config,
            $pageFactory
        );
        return [$controller, $raw, $page];
    }

    public function testEnabledServesGeneratedBodyAsPlainText(): void
    {
        $policy = $this->createMock(RobotsPolicyInterface::class);
        $policy->expects($this->once())->method('getRobotsTxt')->with(3)->willReturn("User-agent: *\n");

        [$controller, $raw] = $this->controller(true, $policy);

        $this->assertSame($raw, $controller->execute());
        $this->assertSame("User-agent: *\n", $this->contents);
        $this->assertSame('text/plain; charset=utf-8', $this->headers['Content-Type']);
        $this->assertSame('noindex', $this->headers['X-Robots-Tag']);
    }

    public function testDisabledFallsBackToCoreRobotsLayout(): void
    {
        $policy = $this->createMock(RobotsPolicyInterface::class);
        $policy->expects($this->never())->method('getRobotsTxt');

        [$controller, , $page] = $this->controller(false, $policy);

        $this->assertSame($page, $controller->execute());
        $this->assertSame(['robots_index_index'], $this->handles);
        $this->assertSame('text/plain', $this->headers['Content-Type']);
        $this->assertNull($this->contents);
    }
}
