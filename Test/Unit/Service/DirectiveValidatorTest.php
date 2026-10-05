<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Test\Unit\Service;

use Panth\RobotsSeo\Service\DirectiveValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DirectiveValidatorTest extends TestCase
{
    private DirectiveValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new DirectiveValidator();
    }

    #[DataProvider('userAgentProvider')]
    public function testUserAgent(string $ua, bool $expected): void
    {
        $this->assertSame($expected, $this->validator->isValidUserAgent($ua));
    }

    public static function userAgentProvider(): array
    {
        return [
            'wildcard' => ['*', true],
            'bot name' => ['Googlebot-Image', true],
            'with version' => ['Mozilla/5.0 compatible+bot_1.2', true],
            'surrounding spaces' => ['  CCBot  ', true],
            'empty' => ['', false],
            'blank' => ['   ', false],
            'newline injection' => ["Bot\nDisallow: /", false],
            'colon' => ['Bot:1', false],
            'too long' => [str_repeat('a', 129), false],
            'max length' => [str_repeat('a', 128), true],
        ];
    }

    #[DataProvider('directiveProvider')]
    public function testDirective(string $directive, bool $expected): void
    {
        $this->assertSame($expected, $this->validator->isValidDirective($directive));
    }

    public static function directiveProvider(): array
    {
        return [
            'basic' => ['index,follow', true],
            'spaces and case' => ['NOINDEX , NoFollow', true],
            'all flags' => ['noarchive,nosnippet,noimageindex,notranslate', true],
            'none' => ['none', true],
            'all' => ['all', true],
            'image preview' => ['max-image-preview:large', true],
            'image preview bad value' => ['max-image-preview:huge', false],
            'image preview missing value' => ['max-image-preview', false],
            'snippet unlimited' => ['max-snippet:-1', true],
            'snippet number' => ['max-snippet: 50', true],
            'snippet not numeric' => ['max-snippet:abc', false],
            'video preview' => ['max-video-preview:0', true],
            'unavailable after' => ['unavailable_after: 2030-01-01', true],
            'unavailable after empty' => ['unavailable_after:', false],
            'unknown token' => ['index,bogus', false],
            'trailing comma' => ['index,follow,', true],
            'empty' => ['', false],
            'html' => ['index,<b>', false],
            'quote' => ["index,'x'", false],
            'ampersand' => ['index&follow', false],
            'backslash' => ['index\\follow', false],
            'control char' => ["index,\x01follow", false],
            'too long' => [str_repeat('index,', 50), false],
        ];
    }

    public function testSanitizeKeepsValidAndTrims(): void
    {
        $this->assertSame('noindex,follow', $this->validator->sanitizeDirective('  noindex,follow '));
    }

    public function testSanitizeFallsBackToDefault(): void
    {
        $this->assertSame(DirectiveValidator::DEFAULT_DIRECTIVE, $this->validator->sanitizeDirective('bogus'));
        $this->assertSame('index,follow', $this->validator->sanitizeDirective(''));
    }

    #[DataProvider('actionProvider')]
    public function testAction(string $action, bool $expected): void
    {
        $this->assertSame($expected, $this->validator->isValidAction($action));
    }

    public static function actionProvider(): array
    {
        return [
            'allow' => ['allow', true],
            'disallow padded' => [' Disallow ', true],
            'deny' => ['deny', false],
            'empty' => ['', false],
        ];
    }

    #[DataProvider('pathProvider')]
    public function testPath(string $path, bool $expected): void
    {
        $this->assertSame($expected, $this->validator->isValidPath($path));
    }

    public static function pathProvider(): array
    {
        return [
            'root' => ['/', true],
            'wildcard' => ['/*?SID=', true],
            'trimmed' => ['  /checkout/ ', true],
            'relative' => ['checkout/', false],
            'empty' => ['', false],
            'newline' => ["/a\nDisallow: /", false],
            'tab' => ["/a\tb", false],
            'too long' => ['/' . str_repeat('a', 1024), false],
            'max length' => ['/' . str_repeat('a', 1023), true],
        ];
    }
}
