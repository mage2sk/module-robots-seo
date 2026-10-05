<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Test\Unit\Plugin\Response;

use Laminas\Http\Header\GenericHeader;
use Magento\Framework\App\Area;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\State as AppState;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\RobotsSeo\Api\RobotsPolicyInterface;
use Panth\RobotsSeo\Helper\Config;
use Panth\RobotsSeo\Model\Robots\MetaResolver;
use Panth\RobotsSeo\Plugin\Response\XRobotsTagPlugin;
use Panth\RobotsSeo\Service\DirectiveMerger;
use Panth\RobotsSeo\Service\DirectiveValidator;
use Panth\RobotsSeo\Service\NoindexPathMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class XRobotsTagPluginTest extends TestCase
{
    private array $headers = [];
    private array $info = [];
    private array $debug = [];

    private function plugin(string $uri, array $settings = []): XRobotsTagPlugin
    {
        $settings += [
            'area' => Area::AREA_FRONTEND,
            'enabled' => true,
            'debug' => false,
            'noindex_search' => true,
            'noindex_paths' => '/checkout/*',
            'header' => 'index,follow',
            'appended' => null,
        ];
        $this->info = [];
        $this->debug = [];

        $appState = $this->createStub(AppState::class);
        $appState->method('getAreaCode')->willReturn($settings['area']);

        $policy = $this->createStub(RobotsPolicyInterface::class);
        if ($settings['header'] instanceof \Throwable) {
            $policy->method('getHeaderRobots')->willThrowException($settings['header']);
        } else {
            $policy->method('getHeaderRobots')->willReturn($settings['header']);
        }

        $config = $this->createStub(Config::class);
        $config->method('isEnabled')->willReturn($settings['enabled']);
        $config->method('isDebug')->willReturn($settings['debug']);
        $config->method('isNoindexSearchResults')->willReturn($settings['noindex_search']);
        $config->method('getNoindexPaths')->willReturn($settings['noindex_paths']);

        $request = $this->createStub(HttpRequest::class);
        $request->method('getRequestUri')->willReturn($uri);

        $store = $this->createStub(Store::class);
        $store->method('getId')->willReturn(1);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $logger = $this->createStub(LoggerInterface::class);
        $logger->method('info')->willReturnCallback(function ($m) {
            $this->info[] = $m;
        });
        $logger->method('debug')->willReturnCallback(function ($m) {
            $this->debug[] = $m;
        });

        $meta = $this->createStub(MetaResolver::class);
        $appended = $settings['appended'];
        $meta->method('appendAdvancedDirectives')->willReturnCallback(
            static fn($base) => $appended ?? $base
        );

        $validator = new DirectiveValidator();
        return new XRobotsTagPlugin(
            $appState,
            $policy,
            $config,
            $request,
            $storeManager,
            $logger,
            $validator,
            $meta,
            new NoindexPathMatcher($config),
            new DirectiveMerger($validator)
        );
    }

    private function response(int $status = 200, $existing = false): HttpResponse
    {
        $this->headers = [];
        $response = $this->createStub(HttpResponse::class);
        $response->method('getStatusCode')->willReturn($status);
        $response->method('getHeader')->willReturn($existing);
        $response->method('setHeader')->willReturnCallback(function ($name, $value) use ($response) {
            $this->headers[$name] = $value;
            return $response;
        });
        return $response;
    }

    public function testSkippedOutsideFrontend(): void
    {
        $response = $this->response(404);
        $this->plugin('/x', ['area' => Area::AREA_ADMINHTML])->beforeSendResponse($response);
        $this->assertSame([], $this->headers);
    }

    public function testSkippedWhenDisabled(): void
    {
        $response = $this->response(404);
        $this->plugin('/x', ['enabled' => false])->beforeSendResponse($response);
        $this->assertSame([], $this->headers);
    }

    public function testSkippedForRobotsTxt(): void
    {
        $response = $this->response();
        $this->plugin('/robots.txt')->beforeSendResponse($response);
        $this->assertSame([], $this->headers);
    }

    #[DataProvider('errorStatusProvider')]
    public function testErrorStatusesAreNoindexed(int $status): void
    {
        $response = $this->response($status);
        $this->plugin('/shoe.html')->beforeSendResponse($response);
        $this->assertSame('noindex, nofollow', $this->headers['X-Robots-Tag']);
    }

    public static function errorStatusProvider(): array
    {
        return ['404' => [404], '410' => [410], '500' => [500], '503' => [503]];
    }

    public function testRedirectStatusUsesNormalResolution(): void
    {
        $response = $this->response(301);
        $this->plugin('/shoe.html')->beforeSendResponse($response);
        $this->assertSame('index,follow', $this->headers['X-Robots-Tag']);
    }

    public function testDocumentsAreNoindexed(): void
    {
        $response = $this->response();
        $this->plugin('/media/a.PDF')->beforeSendResponse($response);
        $this->assertSame('noindex, nofollow', $this->headers['X-Robots-Tag']);
    }

    public function testSearchResultsAreNoindexFollow(): void
    {
        $response = $this->response();
        $this->plugin('/catalogsearch/result/?q=x')->beforeSendResponse($response);
        $this->assertSame('noindex, follow', $this->headers['X-Robots-Tag']);
    }

    public function testSearchResultsUseNormalResolutionWhenOptionOff(): void
    {
        $response = $this->response();
        $this->plugin('/catalogsearch/result/?q=x', ['noindex_search' => false])->beforeSendResponse($response);
        $this->assertSame('index,follow', $this->headers['X-Robots-Tag']);
    }

    public function testPrivatePathsAreNoindexed(): void
    {
        $response = $this->response();
        $this->plugin('/checkout/cart')->beforeSendResponse($response);
        $this->assertSame('noindex, nofollow', $this->headers['X-Robots-Tag']);
    }

    public function testPolicyHeaderWithAdvancedDirectives(): void
    {
        $response = $this->response();
        $this->plugin('/shoe.html', ['appended' => 'index,follow,max-snippet:-1'])->beforeSendResponse($response);
        $this->assertSame('index,follow,max-snippet:-1', $this->headers['X-Robots-Tag']);
    }

    public function testEmptyPolicyHeaderDefaultsToIndexFollow(): void
    {
        $response = $this->response();
        $this->plugin('/shoe.html', ['header' => ''])->beforeSendResponse($response);
        $this->assertSame('index, follow', $this->headers['X-Robots-Tag']);
    }

    public function testExistingHeaderObjectIsMerged(): void
    {
        $response = $this->response(200, new GenericHeader('X-Robots-Tag', 'noindex'));
        $this->plugin('/shoe.html')->beforeSendResponse($response);
        $this->assertSame('noindex,follow', $this->headers['X-Robots-Tag']);
    }

    public function testExistingTraversableHeaderIsMerged(): void
    {
        $iterator = new \ArrayIterator([
            new GenericHeader('X-Robots-Tag', 'nofollow'),
            new GenericHeader('X-Robots-Tag', 'noarchive'),
            'not a header',
        ]);
        $response = $this->response(200, $iterator);
        $this->plugin('/shoe.html')->beforeSendResponse($response);
        $this->assertSame('index,nofollow,noarchive', $this->headers['X-Robots-Tag']);
    }

    public function testInvalidExistingHeaderIsIgnored(): void
    {
        $response = $this->response(200, new GenericHeader('X-Robots-Tag', 'garbage'));
        $this->plugin('/shoe.html', ['header' => 'noindex,follow'])->beforeSendResponse($response);
        $this->assertSame('noindex,follow', $this->headers['X-Robots-Tag']);
    }

    public function testInvalidPolicyHeaderIsSanitised(): void
    {
        $response = $this->response();
        $this->plugin('/shoe.html', ['header' => 'x<y'])->beforeSendResponse($response);
        $this->assertSame('index,follow', $this->headers['X-Robots-Tag']);
    }

    public function testDebugModeLogsTheDecision(): void
    {
        $response = $this->response(404);
        $this->plugin('/gone.html', ['debug' => true])->beforeSendResponse($response);

        $this->assertCount(1, $this->info);
        $this->assertStringContainsString('uri=/gone.html status=404 directive=noindex, nofollow', $this->info[0]);
    }

    public function testNoLogWithoutDebug(): void
    {
        $response = $this->response(404);
        $this->plugin('/gone.html')->beforeSendResponse($response);
        $this->assertSame([], $this->info);
    }

    public function testExceptionsAreSwallowedAndLogged(): void
    {
        $response = $this->response();
        $this->plugin('/shoe.html', ['header' => new \RuntimeException('policy failed')])
            ->beforeSendResponse($response);

        $this->assertSame([], $this->headers);
        $this->assertStringContainsString('policy failed', $this->debug[0]);
    }
}
