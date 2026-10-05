<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Test\Unit\Controller\Router;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Router\ActionList;
use Panth\RobotsSeo\Controller\Robots\Index;
use Panth\RobotsSeo\Controller\Router\RobotsRouter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RobotsRouterTest extends TestCase
{
    private function request(string $path): HttpRequest
    {
        $request = $this->createStub(HttpRequest::class);
        $request->method('getPathInfo')->willReturn($path);
        return $request;
    }

    #[DataProvider('robotsPathProvider')]
    public function testRobotsTxtIsRoutedToTheModuleAction(string $path): void
    {
        $action = $this->createStub(ActionInterface::class);
        $actionList = $this->createMock(ActionList::class);
        $actionList->expects($this->once())->method('get')
            ->with('Panth_RobotsSeo', null, 'robots', 'index')
            ->willReturn(Index::class);
        $factory = $this->createMock(ActionFactory::class);
        $factory->expects($this->once())->method('create')->with(Index::class)->willReturn($action);

        $this->assertSame($action, (new RobotsRouter($factory, $actionList))->match($this->request($path)));
    }

    public static function robotsPathProvider(): array
    {
        return [
            'plain' => ['/robots.txt'],
            'trailing slash' => ['/robots.txt/'],
            'no leading slash' => ['robots.txt'],
        ];
    }

    #[DataProvider('otherPathProvider')]
    public function testOtherPathsAreNotMatched(string $path): void
    {
        $actionList = $this->createMock(ActionList::class);
        $actionList->expects($this->never())->method('get');
        $router = new RobotsRouter($this->createStub(ActionFactory::class), $actionList);

        $this->assertNull($router->match($this->request($path)));
    }

    public static function otherPathProvider(): array
    {
        return [
            'home' => ['/'],
            'nested' => ['/de/robots.txt'],
            'case differs' => ['/ROBOTS.TXT'],
            'similar' => ['/robots.txt.bak'],
        ];
    }

    public function testMissingActionClassReturnsNull(): void
    {
        $actionList = $this->createStub(ActionList::class);
        $actionList->method('get')->willReturn(null);
        $factory = $this->createMock(ActionFactory::class);
        $factory->expects($this->never())->method('create');

        $this->assertNull((new RobotsRouter($factory, $actionList))->match($this->request('/robots.txt')));
    }
}
