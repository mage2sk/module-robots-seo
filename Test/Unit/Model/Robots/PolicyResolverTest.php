<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Test\Unit\Model\Robots;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\RobotsSeo\Helper\Config;
use Panth\RobotsSeo\Model\Robots\MetaResolver;
use Panth\RobotsSeo\Model\Robots\PolicyResolver;
use Panth\RobotsSeo\Service\DirectiveValidator;
use PHPUnit\Framework\TestCase;

class PolicyResolverTest extends TestCase
{
    private string $lastTable = '';

    /**
     * @param array $tables table name => rows (fetchAll) or list of values (fetchCol); missing = table absent
     */
    private function resolver(
        array $tables = [],
        array $settings = [],
        array $existingFiles = [],
        ?MetaResolver $metaResolver = null,
        bool $storeFails = false
    ): PolicyResolver {
        $settings += [
            'override' => false,
            'custom' => '',
            'crawl_delay' => 0,
            'sitemaps' => [],
            'allowed_bots' => [],
            'index_filename' => 'sitemap.xml',
            'xml_sitemap_enabled' => true,
            'filename_column' => true,
        ];

        $select = $this->createStub(Select::class);
        $select->method('from')->willReturnCallback(function ($table) use ($select) {
            $this->lastTable = (string) $table;
            return $select;
        });
        $select->method('where')->willReturnSelf();
        $select->method('order')->willReturnSelf();

        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('isTableExists')->willReturnCallback(
            static fn($table) => array_key_exists($table, $tables)
        );
        $connection->method('select')->willReturn($select);
        $connection->method('tableColumnExists')->willReturn($settings['filename_column']);
        $connection->method('fetchAll')->willReturnCallback(fn() => $tables[$this->lastTable] ?? []);
        $connection->method('fetchCol')->willReturnCallback(fn() => $tables[$this->lastTable] ?? []);

        $resource = $this->createStub(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);

        $store = $this->createStub(Store::class);
        $store->method('getBaseUrl')->willReturn('https://shop.test/');
        $store->method('getCode')->willReturn('default');
        $storeManager = $this->createStub(StoreManagerInterface::class);
        if ($storeFails) {
            $storeManager->method('getStore')->willThrowException(new \RuntimeException('no store'));
        } else {
            $storeManager->method('getStore')->willReturn($store);
        }

        $config = $this->createStub(Config::class);
        $config->method('isRobotsTxtOverrideEnabled')->willReturn($settings['override']);
        $config->method('getCustomRobotsTxt')->willReturn($settings['custom']);
        $config->method('getCrawlDelay')->willReturn($settings['crawl_delay']);
        $config->method('getSitemapUrls')->willReturn($settings['sitemaps']);
        $config->method('getXmlSitemapIndexFilename')->willReturn($settings['index_filename']);
        $config->method('isXmlSitemapEnabled')->willReturn($settings['xml_sitemap_enabled']);
        $allowed = $settings['allowed_bots'];
        $config->method('isLlmBotAllowed')->willReturnCallback(
            static fn($key) => in_array($key, $allowed, true)
        );

        $pub = $this->createStub(ReadInterface::class);
        $pub->method('isFile')->willReturnCallback(static fn($path) => in_array($path, $existingFiles, true));
        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('getDirectoryRead')->willReturn($pub);

        return new PolicyResolver(
            $resource,
            $storeManager,
            $config,
            $metaResolver ?? $this->createStub(MetaResolver::class),
            new DirectiveValidator(),
            $filesystem
        );
    }

    private function section(string $body, string $userAgent): array
    {
        $lines = explode("\n", $body);
        $start = array_search('User-agent: ' . $userAgent, $lines, true);
        $this->assertNotFalse($start, 'Missing group for ' . $userAgent);
        $out = [];
        for ($i = $start + 1; $i < count($lines) && $lines[$i] !== ''; $i++) {
            $out[] = $lines[$i];
        }
        return $out;
    }

    public function testMetaAndHeaderDelegateToMetaResolver(): void
    {
        $meta = $this->createMock(MetaResolver::class);
        $meta->expects($this->once())->method('resolveWithDirectives')->with('product', 3, 1)
            ->willReturn('index,follow,max-snippet:-1');
        $meta->expects($this->once())->method('resolve')->with('category', 4, 2)->willReturn('noindex,follow');

        $resolver = $this->resolver([], [], [], $meta);
        $this->assertSame('index,follow,max-snippet:-1', $resolver->getMetaRobots('product', 3, 1));
        $this->assertSame('noindex,follow', $resolver->getHeaderRobots('category', 4, 2));
    }

    public function testCustomBodyIsNormalisedWhenOverrideEnabled(): void
    {
        $resolver = $this->resolver([], ['override' => true, 'custom' => "User-agent: *\r\nDisallow: /a\0\rAllow: /"]);
        $this->assertSame("User-agent: *\nDisallow: /a\nAllow: /\n", $resolver->getRobotsTxt(1));
    }

    public function testBlankCustomBodyFallsBackToGeneratedFile(): void
    {
        $body = $this->resolver([], ['override' => true, 'custom' => "  \n"])->getRobotsTxt(1);
        $this->assertStringStartsWith("# Generated by Panth_RobotsSeo for store 1\n", $body);
    }

    public function testCustomBodyIgnoredWhenOverrideDisabled(): void
    {
        $body = $this->resolver([], ['override' => false, 'custom' => 'User-agent: x'])->getRobotsTxt(1);
        $this->assertStringNotContainsString('User-agent: x', $body);
    }

    public function testDefaultDisallowsWhenNoWildcardRows(): void
    {
        $body = $this->resolver()->getRobotsTxt(1);
        $rules = $this->section($body, '*');

        $this->assertContains('Disallow: /checkout/', $rules);
        $this->assertContains('Disallow: /customer/', $rules);
        $this->assertContains('Disallow: /*?SID=', $rules);
        $this->assertStringEndsWith("\n", $body);
    }

    public function testPolicyRowsAreGroupedByUserAgentAndValidated(): void
    {
        $rows = [
            ['user_agent' => '', 'directive' => 'disallow', 'path' => '/private/'],
            ['user_agent' => '*', 'directive' => 'allow', 'path' => '/'],
            ['user_agent' => '*', 'directive' => 'disallow', 'path' => 'relative'],
            ['user_agent' => 'Googlebot', 'directive' => 'DISALLOW', 'path' => '/tmp/'],
            ['user_agent' => "Bad\nAgent", 'directive' => 'disallow', 'path' => '/'],
        ];
        $body = $this->resolver(['panth_seo_robots_policy' => $rows])->getRobotsTxt(1);

        $this->assertSame(['Disallow: /private/', 'Allow: /'], $this->section($body, '*'));
        $this->assertSame(['Disallow: /tmp/'], $this->section($body, 'Googlebot'));
        $this->assertStringNotContainsString('Bad', $body);
        $this->assertStringNotContainsString('Disallow: /checkout/', $body);
    }

    public function testCrawlDelayOnlyOnWildcardGroup(): void
    {
        $rows = [['user_agent' => 'Googlebot', 'directive' => 'allow', 'path' => '/']];
        $body = $this->resolver(['panth_seo_robots_policy' => $rows], ['crawl_delay' => 5])->getRobotsTxt(1);

        $this->assertContains('Crawl-delay: 5', $this->section($body, '*'));
        $this->assertNotContains('Crawl-delay: 5', $this->section($body, 'Googlebot'));
    }

    public function testNoCrawlDelayLineWhenZero(): void
    {
        $this->assertStringNotContainsString('Crawl-delay', $this->resolver()->getRobotsTxt(1));
    }

    public function testLlmBotsBlockedUnlessAllowed(): void
    {
        $body = $this->resolver([], ['allowed_bots' => ['ccbot']])->getRobotsTxt(1);

        $this->assertSame(['Allow: /'], $this->section($body, 'CCBot'));
        $this->assertSame(['Disallow: /'], $this->section($body, 'Bytespider'));
        $this->assertSame(['Allow: /'], $this->section($body, 'YouBot'));
    }

    public function testAllowedLlmBotUsesItsOwnPolicyRowsDeduplicated(): void
    {
        $rows = [
            ['user_agent' => 'ccbot', 'directive' => 'disallow', 'path' => '/checkout/'],
            ['user_agent' => 'CCBot', 'directive' => 'disallow', 'path' => '/checkout/'],
            ['user_agent' => 'Bytespider', 'directive' => 'allow', 'path' => '/blog/'],
        ];
        $body = $this->resolver(['panth_seo_robots_policy' => $rows], ['allowed_bots' => ['ccbot']])
            ->getRobotsTxt(1);

        $this->assertSame(['Disallow: /checkout/'], $this->section($body, 'CCBot'));
        $this->assertSame(['Disallow: /'], $this->section($body, 'Bytespider'));
        $this->assertSame(1, substr_count($body, 'User-agent: CCBot'));
    }

    public function testLlmBotPolicyCoversEveryMappedBot(): void
    {
        $policy = $this->resolver([], ['allowed_bots' => ['ccbot']])->getLlmBotPolicy(1);

        $this->assertSame(array_keys(PolicyResolver::LLM_BOT_CONFIG_MAP), array_keys($policy));
        $this->assertTrue($policy['CCBot']);
        $this->assertFalse($policy['Bytespider']);
        $this->assertTrue($policy['PetalBot'], 'Bots without a config key are always allowed');
    }

    public function testConfiguredSitemapUrlsAreResolvedAndDeduplicated(): void
    {
        $body = $this->resolver([], ['sitemaps' => [
            '{{base_url}}/sitemap.xml',
            '/sitemap.xml',
            '/media//nested.xml',
            'ftp://bad.test/s.xml',
            'not-a-url',
        ]])->getRobotsTxt(1);

        $this->assertSame(1, substr_count($body, 'Sitemap: https://shop.test/sitemap.xml'));
        $this->assertStringContainsString('Sitemap: https://shop.test/media/nested.xml', $body);
        $this->assertStringNotContainsString('ftp://', $body);
        $this->assertStringNotContainsString('not-a-url', $body);
        $this->assertStringContainsString("Host: shop.test\n", $body);
    }

    public function testXmlSitemapProfilesAreDiscoveredWhenFilesExist(): void
    {
        $tables = ['panth_seo_sitemap_profile' => [
            ['output_path' => ''],
            ['output_path' => 'maps/{store_code}/'],
            ['output_path' => '../escape'],
            ['output_path' => 'missing/'],
        ]];
        $files = ['xmlsitemap/default/sitemap.xml', 'maps/default/sitemap.xml'];
        $body = $this->resolver($tables, [], $files)->getRobotsTxt(1);

        $this->assertStringContainsString('Sitemap: https://shop.test/xmlsitemap/default/sitemap.xml', $body);
        $this->assertStringContainsString('Sitemap: https://shop.test/maps/default/sitemap.xml', $body);
        $this->assertSame(1, substr_count($body, 'xmlsitemap/default/sitemap.xml'));
        $this->assertStringNotContainsString('missing/', $body);
    }

    public function testProfileSitemapFilenameIsUsedPerStore(): void
    {
        $tables = ['panth_seo_sitemap_profile' => [
            ['output_path' => '/', 'sitemap_filename' => 'sitemap_luma.xml'],
            ['output_path' => 'seo/{store_code}', 'sitemap_filename' => '../../env.xml'],
            ['output_path' => '/', 'sitemap_filename' => null],
        ]];
        $files = ['sitemap_luma.xml', 'seo/default/legacy.xml', 'legacy.xml', 'sitemap.xml'];
        $body = $this->resolver($tables, ['index_filename' => 'legacy.xml'], $files)->getRobotsTxt(1);

        $this->assertStringContainsString('Sitemap: https://shop.test/sitemap_luma.xml', $body);
        $this->assertStringContainsString('Sitemap: https://shop.test/seo/default/legacy.xml', $body);
        $this->assertStringContainsString('Sitemap: https://shop.test/legacy.xml', $body);
        $this->assertStringNotContainsString('env.xml', $body);
        $this->assertStringNotContainsString('Sitemap: https://shop.test/sitemap.xml', $body);
    }

    public function testWithoutFilenameColumnTheIndexFilenameSettingIsUsed(): void
    {
        $tables = ['panth_seo_sitemap_profile' => [['output_path' => '/']]];
        $body = $this->resolver($tables, ['filename_column' => false, 'index_filename' => 'map.xml'], ['map.xml'])
            ->getRobotsTxt(1);
        $this->assertStringContainsString('Sitemap: https://shop.test/map.xml', $body);
    }

    public function testDisabledXmlSitemapIsNotListedAndCoreSitemapIsUsed(): void
    {
        $tables = [
            'panth_seo_sitemap_profile' => [['output_path' => '/', 'sitemap_filename' => 'sitemap.xml']],
            'sitemap' => [['sitemap_path' => '/', 'sitemap_filename' => 'core.xml']],
        ];
        $body = $this->resolver($tables, ['xml_sitemap_enabled' => false], ['sitemap.xml', 'core.xml'])
            ->getRobotsTxt(1);
        $this->assertStringNotContainsString('Sitemap: https://shop.test/sitemap.xml', $body);
        $this->assertStringContainsString('Sitemap: https://shop.test/core.xml', $body);
    }

    public function testMagentoSitemapTableIsFallback(): void
    {
        $tables = ['sitemap' => [
            ['sitemap_path' => '/pub/maps/', 'sitemap_filename' => 'core.xml'],
            ['sitemap_path' => '/', 'sitemap_filename' => 'root.xml'],
            ['sitemap_path' => '/', 'sitemap_filename' => 'bad name.xml'],
            ['sitemap_path' => '/../x/', 'sitemap_filename' => 'up.xml'],
        ]];
        $files = ['pub/maps/core.xml', 'root.xml', 'up.xml'];
        $body = $this->resolver($tables, [], $files)->getRobotsTxt(1);

        $this->assertStringContainsString('Sitemap: https://shop.test/pub/maps/core.xml', $body);
        $this->assertStringContainsString('Sitemap: https://shop.test/root.xml', $body);
        $this->assertStringContainsString('Sitemap: https://shop.test/up.xml', $body);
        $this->assertStringNotContainsString('bad name', $body);
    }

    public function testNoSitemapLineWhenNothingExists(): void
    {
        $tables = ['panth_seo_sitemap_profile' => [['output_path' => '']]];
        $this->assertStringNotContainsString('Sitemap:', $this->resolver($tables)->getRobotsTxt(1));
    }

    public function testStoreFailureStillReturnsRules(): void
    {
        $body = $this->resolver([], [], [], null, true)->getRobotsTxt(1);

        $this->assertStringContainsString('User-agent: *', $body);
        $this->assertStringNotContainsString('Host:', $body);
        $this->assertStringNotContainsString('Sitemap:', $body);
    }
}
