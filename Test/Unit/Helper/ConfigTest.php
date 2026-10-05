<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Panth\RobotsSeo\Helper\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private function config(array $values = [], array $flags = []): Config
    {
        $scope = $this->createStub(ScopeConfigInterface::class);
        $scope->method('getValue')->willReturnCallback(
            static fn(string $path) => $values[$path] ?? null
        );
        $scope->method('isSetFlag')->willReturnCallback(
            static fn(string $path) => (bool) ($flags[$path] ?? false)
        );
        return new Config($scope);
    }

    public function testFlagsReadTheirOwnPaths(): void
    {
        $config = $this->config([], [
            Config::XML_GENERAL_ENABLED => true,
            Config::XML_GENERAL_NOINDEX_SEARCH => true,
            Config::XML_ROBOTSTXT_OVERRIDE => true,
            Config::XML_LLM_BOT_PREFIX . 'ccbot' => true,
        ]);

        $this->assertTrue($config->isEnabled(1));
        $this->assertFalse($config->isDebug(1));
        $this->assertFalse($config->isNoindexFiltered(1));
        $this->assertTrue($config->isNoindexSearchResults(1));
        $this->assertTrue($config->isRobotsTxtOverrideEnabled(1));
        $this->assertTrue($config->isLlmBotAllowed('ccbot', 1));
        $this->assertFalse($config->isLlmBotAllowed('bytespider', 1));
    }

    public function testDefaultDirectiveFallsBackWhenMissingOrEmpty(): void
    {
        $this->assertSame('index,follow', $this->config()->getDefaultDirective(1));
        $this->assertSame(
            'index,follow',
            $this->config([Config::XML_GENERAL_DEFAULT_DIRECTIVE => ''])->getDefaultDirective(1)
        );
        $this->assertSame(
            'noindex,follow',
            $this->config([Config::XML_GENERAL_DEFAULT_DIRECTIVE => 'noindex,follow'])->getDefaultDirective(1)
        );
    }

    public function testIgnoredQueryParamsAreSplitLowercasedAndDeduplicated(): void
    {
        $config = $this->config([
            Config::XML_GENERAL_IGNORED_QUERY_PARAMS => " UTM_Source, utm_source\ngclid  fbclid,,",
        ]);

        $this->assertSame(['utm_source', 'gclid', 'fbclid'], $config->getIgnoredQueryParams(1));
    }

    public function testIgnoredQueryParamsEmptyWhenBlank(): void
    {
        $this->assertSame([], $this->config()->getIgnoredQueryParams(1));
        $this->assertSame(
            [],
            $this->config([Config::XML_GENERAL_IGNORED_QUERY_PARAMS => "  \n "])->getIgnoredQueryParams(1)
        );
    }

    #[DataProvider('imagePreviewProvider')]
    public function testMaxImagePreviewIsRestrictedToKnownValues(?string $stored, string $expected): void
    {
        $values = $stored === null ? [] : [Config::XML_GENERAL_MAX_IMAGE_PREVIEW => $stored];
        $this->assertSame($expected, $this->config($values)->getMaxImagePreview(1));
    }

    public static function imagePreviewProvider(): array
    {
        return [
            'missing' => [null, 'large'],
            'none' => ['none', 'none'],
            'standard' => ['standard', 'standard'],
            'large' => ['large', 'large'],
            'unknown' => ['huge', 'large'],
            'wrong case' => ['LARGE', 'large'],
        ];
    }

    public function testMaxSnippetDefaultsToUnlimited(): void
    {
        $this->assertSame(-1, $this->config()->getMaxSnippet(1));
        $this->assertSame(160, $this->config([Config::XML_GENERAL_MAX_SNIPPET => '160'])->getMaxSnippet(1));
    }

    public function testCrawlDelayIsNeverNegative(): void
    {
        $this->assertSame(0, $this->config()->getCrawlDelay(1));
        $this->assertSame(0, $this->config([Config::XML_GENERAL_CRAWL_DELAY => '-5'])->getCrawlDelay(1));
        $this->assertSame(10, $this->config([Config::XML_GENERAL_CRAWL_DELAY => '10'])->getCrawlDelay(1));
    }

    public function testStringValuesDefaultToEmpty(): void
    {
        $config = $this->config();
        $this->assertSame('', $config->getNoindexPaths(1));
        $this->assertSame('', $config->getCustomRobotsTxt(1));

        $config = $this->config([
            Config::XML_GENERAL_NOINDEX_PATHS => "/a\n/b",
            Config::XML_ROBOTSTXT_CUSTOM => "User-agent: *\nDisallow:",
        ]);
        $this->assertSame("/a\n/b", $config->getNoindexPaths(1));
        $this->assertSame("User-agent: *\nDisallow:", $config->getCustomRobotsTxt(1));
    }

    public function testSitemapUrlsAreOnePerLineWithoutBlanksOrNullBytes(): void
    {
        $config = $this->config([
            Config::XML_ROBOTSTXT_SITEMAP_URLS => "https://a.test/s.xml\r\n\r\n  /b.xml  \n" . "x\0y",
        ]);

        $this->assertSame(['https://a.test/s.xml', '/b.xml', 'xy'], $config->getSitemapUrls(1));
        $this->assertSame([], $this->config()->getSitemapUrls(1));
    }

    public function testXmlSitemapEnabledDefaultsToYesWhenUnset(): void
    {
        $this->assertTrue($this->config()->isXmlSitemapEnabled(1));
        $this->assertTrue($this->config([Config::XML_XML_SITEMAP_ENABLED => '1'])->isXmlSitemapEnabled(1));
        $this->assertFalse($this->config([Config::XML_XML_SITEMAP_ENABLED => '0'])->isXmlSitemapEnabled(2));
    }

    #[DataProvider('indexFilenameProvider')]
    public function testXmlSitemapIndexFilenameIsSanitised(?string $stored, string $expected): void
    {
        $values = $stored === null ? [] : [Config::XML_XML_SITEMAP_INDEX_FILENAME => $stored];
        $this->assertSame($expected, $this->config($values)->getXmlSitemapIndexFilename(1));
    }

    public static function indexFilenameProvider(): array
    {
        return [
            'missing' => [null, 'sitemap.xml'],
            'custom' => ['index-main.xml', 'index-main.xml'],
            'path stripped' => ['../../etc/map.xml', 'map.xml'],
            'not xml' => ['sitemap.txt', 'sitemap.xml'],
            'leading dot' => ['.hidden.xml', 'sitemap.xml'],
            'spaces' => ['my map.xml', 'sitemap.xml'],
        ];
    }

    public function testBlankMaxSnippetMeansUnlimited(): void
    {
        $this->assertSame(-1, $this->config([Config::XML_GENERAL_MAX_SNIPPET => ''])->getMaxSnippet(1));
        $this->assertSame(-1, $this->config([Config::XML_GENERAL_MAX_SNIPPET => '  '])->getMaxSnippet(1));
        $this->assertSame(0, $this->config([Config::XML_GENERAL_MAX_SNIPPET => '0'])->getMaxSnippet(1));
    }
}
