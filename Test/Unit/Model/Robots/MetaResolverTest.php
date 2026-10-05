<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Test\Unit\Model\Robots;

use Laminas\Stdlib\Parameters;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Panth\RobotsSeo\Helper\Config;
use Panth\RobotsSeo\Model\Robots\MetaResolver;
use Panth\RobotsSeo\Service\DirectiveValidator;
use PHPUnit\Framework\TestCase;

class MetaResolverTest extends TestCase
{
    /**
     * @param string|\Throwable|null $stored null = table missing
     */
    private function resolver(
        $stored = null,
        array $query = [],
        string $path = '/item.html',
        array $settings = []
    ): MetaResolver {
        $settings += [
            'enabled' => true,
            'noindex_filtered' => true,
            'noindex_search' => true,
            'default' => 'index,follow',
            'image' => 'large',
            'snippet' => -1,
        ];

        $request = $this->createStub(HttpRequest::class);
        $request->method('getQuery')->willReturn(new Parameters($query));
        $request->method('getPathInfo')->willReturn($path);

        $config = $this->createStub(Config::class);
        $config->method('isEnabled')->willReturn($settings['enabled']);
        $config->method('isNoindexFiltered')->willReturn($settings['noindex_filtered']);
        $config->method('isNoindexSearchResults')->willReturn($settings['noindex_search']);
        $config->method('getDefaultDirective')->willReturn($settings['default']);
        $config->method('getIgnoredQueryParams')->willReturn([]);
        $config->method('getMaxImagePreview')->willReturn($settings['image']);
        $config->method('getMaxSnippet')->willReturn($settings['snippet']);

        $select = $this->createStub(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $select->method('limit')->willReturnSelf();

        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('isTableExists')->willReturn($stored !== null);
        $connection->method('select')->willReturn($select);
        if ($stored instanceof \Throwable) {
            $connection->method('fetchOne')->willThrowException($stored);
        } else {
            $connection->method('fetchOne')->willReturn((string) $stored);
        }

        $resource = $this->createStub(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);

        return new MetaResolver(
            $resource,
            $request,
            $config,
            $this->createStub(ScopeConfigInterface::class),
            new DirectiveValidator()
        );
    }

    public function testStoredDirectiveWinsOverDefault(): void
    {
        $this->assertSame('noindex,nofollow', $this->resolver('noindex,nofollow')->resolve('product', 5, 1));
    }

    public function testInvalidStoredDirectiveIsSanitised(): void
    {
        $this->assertSame('index,follow', $this->resolver('index,<script>')->resolve('product', 5, 1));
    }

    public function testEmptyStoredValueFallsBackToConfiguredDefault(): void
    {
        $resolver = $this->resolver('', [], '/item.html', ['default' => 'index,nofollow']);
        $this->assertSame('index,nofollow', $resolver->resolve('product', 5, 1));
    }

    public function testMissingTableFallsBackToDefault(): void
    {
        $resolver = $this->resolver(null, [], '/item.html', ['default' => 'noindex,follow']);
        $this->assertSame('noindex,follow', $resolver->resolve('category', 5, 1));
    }

    public function testDatabaseErrorFallsBackToDefault(): void
    {
        $this->assertSame('index,follow', $this->resolver(new \RuntimeException('db'))->resolve('product', 5, 1));
    }

    public function testNonPositiveEntityIdSkipsStoredLookup(): void
    {
        $resolver = $this->resolver('noindex,nofollow', [], '/item.html', ['default' => 'index,follow']);
        $this->assertSame('index,follow', $resolver->resolve('', 0, 1));
    }

    public function testInvalidConfiguredDefaultIsSanitised(): void
    {
        $resolver = $this->resolver(null, [], '/item.html', ['default' => 'whatever']);
        $this->assertSame('index,follow', $resolver->resolve('', 0, 1));
    }

    public function testSearchResultPathIsNoindexed(): void
    {
        $resolver = $this->resolver('index,follow', [], '/catalogsearch/result/');
        $this->assertSame('noindex,follow', $resolver->resolve('product', 5, 1));
    }

    public function testSearchResultPathIndexableWhenOptionOff(): void
    {
        $resolver = $this->resolver(null, [], '/catalogsearch/result/', ['noindex_search' => false]);
        $this->assertSame('index,follow', $resolver->resolve('', 0, 1));
    }

    public function testFilterParamsIgnoredWhenModuleDisabled(): void
    {
        $resolver = $this->resolver(null, ['color' => '5'], '/c.html', ['enabled' => false]);
        $this->assertSame('index,follow', $resolver->resolve('category', 3, 1));
    }

    public function testFilterParamsIgnoredWhenNoindexFilteredOff(): void
    {
        $resolver = $this->resolver(null, ['color' => '5'], '/c.html', ['noindex_filtered' => false]);
        $this->assertSame('index,follow', $resolver->resolve('category', 3, 1));
    }

    public function testStoreSwitchParamsAreSafe(): void
    {
        $resolver = $this->resolver(null, ['___store' => 'de', '___from_store' => 'en', 'id' => '3']);
        $this->assertSame('index,follow', $resolver->resolve('category', 3, 1));
    }

    public function testAdvancedDirectivesAreAppended(): void
    {
        $resolver = $this->resolver(null, [], '/x', ['image' => 'large', 'snippet' => -1]);
        $this->assertSame(
            'index,follow,max-image-preview:large,max-snippet:-1',
            $resolver->appendAdvancedDirectives('index,follow', 1)
        );
    }

    public function testImagePreviewNoneIsOmitted(): void
    {
        $resolver = $this->resolver(null, [], '/x', ['image' => 'none', 'snippet' => 120]);
        $this->assertSame('noindex,follow,max-snippet:120', $resolver->appendAdvancedDirectives('noindex,follow', 1));
    }

    public function testInvalidBaseMakesWholeCandidateFallBack(): void
    {
        $resolver = $this->resolver(null, [], '/x');
        $this->assertSame('index,follow', $resolver->appendAdvancedDirectives('bad<', 1));
    }

    public function testResolveWithDirectivesCombinesBoth(): void
    {
        $resolver = $this->resolver('noindex,follow', [], '/x', ['image' => 'standard', 'snippet' => 50]);
        $this->assertSame(
            'noindex,follow,max-image-preview:standard,max-snippet:50',
            $resolver->resolveWithDirectives('product', 7, 1)
        );
    }
}
