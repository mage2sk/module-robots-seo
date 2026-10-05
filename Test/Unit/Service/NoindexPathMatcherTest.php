<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Test\Unit\Service;

use Panth\RobotsSeo\Helper\Config;
use Panth\RobotsSeo\Service\NoindexPathMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NoindexPathMatcherTest extends TestCase
{
    private function matcher(string $paths = ''): NoindexPathMatcher
    {
        $config = $this->createStub(Config::class);
        $config->method('getNoindexPaths')->willReturn($paths);
        return new NoindexPathMatcher($config);
    }

    public function testDefaultPatternsUsedWhenConfigBlank(): void
    {
        $this->assertSame(NoindexPathMatcher::DEFAULT_PATTERNS, $this->matcher("  \n ")->getPatterns(1));
    }

    public function testCustomPatternsSkipCommentsAndBlanks(): void
    {
        $matcher = $this->matcher("# private\r\n/members/*\n\n  /cart  \n#/old");
        $this->assertSame(['/members/*', '/cart'], $matcher->getPatterns(1));
    }

    public function testOnlyCommentsFallBackToDefaults(): void
    {
        $this->assertSame(NoindexPathMatcher::DEFAULT_PATTERNS, $this->matcher("# a\n# b")->getPatterns(1));
    }

    #[DataProvider('defaultMatchProvider')]
    public function testDefaultMatching(string $uri, bool $expected): void
    {
        $this->assertSame($expected, $this->matcher()->isNoindexPath($uri, 1));
    }

    public static function defaultMatchProvider(): array
    {
        return [
            'customer account' => ['/customer/account/login/', true],
            'checkout exact' => ['/checkout', true],
            'checkout trailing slash' => ['/checkout/', true],
            'checkout subpage' => ['/checkout/cart/', true],
            'query ignored' => ['/checkout?x=1', true],
            'case insensitive' => ['/Customer/Account', true],
            'full url' => ['https://shop.test/wishlist/index/', true],
            'customer root only' => ['/customer', false],
            'product page' => ['/shoe.html', false],
            'prefix lookalike' => ['/checkouts', false],
            'home' => ['/', false],
            'empty' => ['', false],
        ];
    }

    public function testRepeatedSlashesAreCollapsed(): void
    {
        $this->assertTrue($this->matcher()->isNoindexPath('/customer//account///edit', 1));
    }

    public function testCustomWildcardMatching(): void
    {
        $matcher = $this->matcher("/private/*\n/landing-*.html");
        $this->assertTrue($matcher->isNoindexPath('/private/a/b', 1));
        $this->assertTrue($matcher->isNoindexPath('/landing-summer.html', 1));
        $this->assertFalse($matcher->isNoindexPath('/landing.html', 1));
        $this->assertFalse($matcher->isNoindexPath('/customer/account', 1));
    }

    public function testRegexSpecialCharactersAreLiteral(): void
    {
        $matcher = $this->matcher('/a.b(c)');
        $this->assertTrue($matcher->isNoindexPath('/a.b(c)', 1));
        $this->assertFalse($matcher->isNoindexPath('/axb(c)', 1));
    }

    public function testCompiledRegexIsCachedPerStore(): void
    {
        $config = $this->createMock(Config::class);
        $config->expects($this->exactly(2))->method('getNoindexPaths')->willReturn('/x');
        $matcher = new NoindexPathMatcher($config);

        $this->assertTrue($matcher->isNoindexPath('/x', 1));
        $this->assertFalse($matcher->isNoindexPath('/y', 1));
        $this->assertTrue($matcher->isNoindexPath('/x', 2));
    }
}
