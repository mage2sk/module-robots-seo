<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Test\Unit\Controller\Adminhtml\Policy;

use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Registry;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\PageFactory;
use Panth\RobotsSeo\Controller\Adminhtml\Policy\Edit;
use Panth\RobotsSeo\Model\ResourceModel\RobotsPolicy as PolicyResource;
use Panth\RobotsSeo\Model\Robots\Policy;
use Panth\RobotsSeo\Test\Unit\Controller\Adminhtml\ControllerTestCase;

class EditTest extends ControllerTestCase
{
    private array $titles = [];
    private array $menus = [];
    private array $registered = [];

    private function controller(array $params, ?int $foundId): array
    {
        $this->titles = $this->menus = $this->registered = [];
        $id = null;

        $model = $this->createStub(Policy::class);
        $model->method('getId')->willReturnCallback(static function () use (&$id) {
            return $id;
        });
        $resource = $this->createStub(PolicyResource::class);
        $resource->method('load')->willReturnCallback(static function () use (&$id, $foundId, $resource) {
            $id = $foundId;
            return $resource;
        });

        $title = $this->createStub(Title::class);
        $title->method('prepend')->willReturnCallback(function ($t) {
            $this->titles[] = (string) $t;
        });
        $pageConfig = $this->createStub(PageConfig::class);
        $pageConfig->method('getTitle')->willReturn($title);
        $page = $this->createStub(Page::class);
        $page->method('getConfig')->willReturn($pageConfig);
        $page->method('setActiveMenu')->willReturnCallback(function ($menu) use ($page) {
            $this->menus[] = $menu;
            return $page;
        });
        $pageFactory = $this->createStub(PageFactory::class);
        $pageFactory->method('create')->willReturn($page);

        $registry = $this->createStub(Registry::class);
        $registry->method('register')->willReturnCallback(function ($key, $value) {
            $this->registered[$key] = $value;
        });

        $controller = new Edit($this->context($params), $pageFactory, $registry, $model, $resource);
        return [$controller, $page, $model];
    }

    public function testNewPolicyPage(): void
    {
        [$controller, $page, $model] = $this->controller([], null);

        $this->assertSame($page, $controller->execute());
        $this->assertSame(['New Robots Policy'], $this->titles);
        $this->assertSame(['Panth_RobotsSeo::policies_menu'], $this->menus);
        $this->assertSame($model, $this->registered['panth_robots_seo_policy']);
    }

    public function testExistingPolicyPage(): void
    {
        [$controller, $page] = $this->controller(['policy_id' => '4'], 4);

        $this->assertSame($page, $controller->execute());
        $this->assertSame(['Edit Robots Policy'], $this->titles);
    }

    public function testUnknownPolicyRedirectsToGrid(): void
    {
        [$controller] = $this->controller(['policy_id' => '4'], null);
        $controller->execute();

        $this->assertSame(['Policy not found.'], $this->errors);
        $this->assertSame(['*/*/', []], $this->redirect);
        $this->assertSame([], $this->registered);
        $this->assertSame([], $this->titles);
    }
}
