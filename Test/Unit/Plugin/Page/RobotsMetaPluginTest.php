<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Test\Unit\Plugin\Page;

use Magento\Framework\App\Area;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\RobotsSeo\Helper\Config;
use Panth\RobotsSeo\Model\Robots\MetaResolver;
use Panth\RobotsSeo\Plugin\Page\RobotsMetaPlugin;
use Panth\RobotsSeo\Service\DirectiveMerger;
use Panth\RobotsSeo\Service\DirectiveValidator;
use Panth\RobotsSeo\Service\NoindexPathMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class RobotsMetaPluginTest extends TestCase
{
    private array $headers = [];
    private array $logs = [];

    private function plugin(
        string $uri,
        array $settings = []
    ): RobotsMetaPlugin {
        $settings += [
            'area' => Area::AREA_FRONTEND,
            'enabled' => true,
            'noindex_search' => true,
            'noindex_paths' => '/customer/*',
            'resolved' => 'index,follow,max-image-preview:large,max-snippet:-1',
            'store_error' => false,
            'status' => 200,
        ];
        $this->headers = [];
        $this->logs = [];

        $appState = $this->createStub(AppState::class);
        if ($settings['area'] === null) {
            $appState->method('getAreaCode')->willThrowException(new LocalizedException(__('no area')));
        } else {
            $appState->method('getAreaCode')->willReturn($settings['area']);
        }

        $request = $this->createStub(HttpRequest::class);
        $request->method('getRequestUri')->willReturn($uri);

        $store = $this->createStub(Store::class);
        $store->method('getId')->willReturn(1);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        if ($settings['store_error']) {
            $storeManager->method('getStore')->willThrowException(new \RuntimeException('store down'));
        } else {
            $storeManager->method('getStore')->willReturn($store);
        }

        $config = $this->createStub(Config::class);
        $config->method('isEnabled')->willReturn($settings['enabled']);
        $config->method('isNoindexSearchResults')->willReturn($settings['noindex_search']);
        $config->method('getNoindexPaths')->willReturn($settings['noindex_paths']);

        $meta = $this->createStub(MetaResolver::class);
        $meta->method('resolveWithDirectives')->willReturn($settings['resolved']);

        $logger = $this->createStub(LoggerInterface::class);
        $logger->method('debug')->willReturnCallback(function ($message) {
            $this->logs[] = $message;
        });

        $response = $this->createStub(HttpResponse::class);
        $response->method('getStatusCode')->willReturn($settings['status']);
        $response->method('setHeader')->willReturnCallback(function ($name, $value) use ($response) {
            $this->headers[$name] = $value;
            return $response;
        });

        $validator = new DirectiveValidator();
        return new RobotsMetaPlugin(
            $appState,
            $request,
            $storeManager,
            $config,
            $meta,
            new NoindexPathMatcher($config),
            $validator,
            $logger,
            new DirectiveMerger($validator),
            $response
        );
    }

    private function apply(RobotsMetaPlugin $plugin, $incoming): string
    {
        return $plugin->afterGetRobots($this->createStub(PageConfig::class), $incoming);
    }

    public function testNonFrontendAreaReturnsIncomingUntouched(): void
    {
        $plugin = $this->plugin('/customer/account', ['area' => Area::AREA_ADMINHTML]);
        $this->assertSame('INDEX,FOLLOW', $this->apply($plugin, 'INDEX,FOLLOW'));
        $this->assertSame([], $this->headers);
    }

    public function testMissingAreaIsTreatedAsNonFrontend(): void
    {
        $plugin = $this->plugin('/customer/account', ['area' => null]);
        $this->assertSame('INDEX,FOLLOW', $this->apply($plugin, 'INDEX,FOLLOW'));
    }

    public function testDisabledModuleReturnsIncoming(): void
    {
        $plugin = $this->plugin('/customer/account', ['enabled' => false]);
        $this->assertSame('INDEX,FOLLOW', $this->apply($plugin, 'INDEX,FOLLOW'));
        $this->assertSame([], $this->headers);
    }

    public function testRobotsTxtRequestIsLeftAlone(): void
    {
        $this->assertSame('INDEX,FOLLOW', $this->apply($this->plugin('/robots.txt'), 'INDEX,FOLLOW'));
    }

    #[DataProvider('documentProvider')]
    public function testDocumentDownloadsAreNoindexNofollow(string $uri): void
    {
        $this->assertSame('noindex,nofollow', $this->apply($this->plugin($uri), 'INDEX,FOLLOW'));
        $this->assertSame('noindex,nofollow', $this->headers['X-Robots-Tag']);
    }

    #[DataProvider('errorStatusProvider')]
    public function testErrorStatusPagesAreNoindexNofollow(int $status): void
    {
        $plugin = $this->plugin('/no-such-page', ['status' => $status]);
        $this->assertSame('noindex,nofollow', $this->apply($plugin, 'INDEX,FOLLOW'));
        $this->assertSame('noindex,nofollow', $this->headers['X-Robots-Tag']);
    }

    public static function errorStatusProvider(): array
    {
        return [
            'not found' => [404],
            'gone' => [410],
            'server error' => [500],
            'maintenance' => [503],
        ];
    }

    public function testRegularStatusKeepsResolvedDirective(): void
    {
        $plugin = $this->plugin('/no-such-page', ['status' => 301]);
        $this->assertSame(
            'index,follow,max-image-preview:large,max-snippet:-1',
            $this->apply($plugin, 'INDEX,FOLLOW')
        );
    }

    public static function documentProvider(): array
    {
        return [
            'pdf' => ['/media/manual.pdf'],
            'upper case docx' => ['/media/Spec.DOCX?v=2'],
            'xlsx' => ['/files/prices.xlsx'],
        ];
    }

    public function testSearchResultsAreNoindexFollow(): void
    {
        $this->assertSame('noindex,follow', $this->apply($this->plugin('/catalogsearch/result/?q=a'), 'INDEX,FOLLOW'));
    }

    public function testSearchResultsFallThroughWhenOptionOff(): void
    {
        $plugin = $this->plugin('/catalogsearch/result/?q=a', ['noindex_search' => false, 'noindex_paths' => '/x']);
        $this->assertSame('index,follow,max-image-preview:large,max-snippet:-1', $this->apply($plugin, 'INDEX,FOLLOW'));
    }

    public function testConfiguredPrivatePathsAreNoindexNofollow(): void
    {
        $this->assertSame('noindex,nofollow', $this->apply($this->plugin('/customer/account/'), 'INDEX,FOLLOW'));
    }

    public function testResolvedValueMergedWithIncoming(): void
    {
        $result = $this->apply($this->plugin('/shoe.html'), 'INDEX,NOFOLLOW');

        $this->assertSame('index,nofollow,max-image-preview:large,max-snippet:-1', $result);
        $this->assertSame($result, $this->headers['X-Robots-Tag']);
    }

    public function testResolverNoindexBeatsIncomingIndex(): void
    {
        $plugin = $this->plugin('/shoe.html', ['resolved' => 'noindex,follow']);
        $this->assertSame('noindex,follow', $this->apply($plugin, 'INDEX,FOLLOW'));
    }

    public function testInvalidEverythingFallsBackToDefault(): void
    {
        $plugin = $this->plugin('/shoe.html', ['resolved' => 'junk']);
        $this->assertSame('index,follow', $this->apply($plugin, 'also junk'));
    }

    public function testExceptionIsLoggedAndIncomingSanitised(): void
    {
        $plugin = $this->plugin('/shoe.html', ['store_error' => true]);

        $this->assertSame('noindex,follow', $this->apply($plugin, 'noindex,follow'));
        $this->assertSame('index,follow', $this->apply($plugin, '<bad>'));
        $this->assertStringContainsString('store down', $this->logs[0]);
    }

    public function testNullIncomingIsHandled(): void
    {
        $plugin = $this->plugin('/shoe.html', ['enabled' => false]);
        $this->assertSame('', $this->apply($plugin, null));
    }
}
