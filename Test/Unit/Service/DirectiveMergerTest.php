<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Test\Unit\Service;

use Panth\RobotsSeo\Service\DirectiveMerger;
use Panth\RobotsSeo\Service\DirectiveValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DirectiveMergerTest extends TestCase
{
    public static function mergeProvider(): array
    {
        return [
            'explicit nofollow kept' => [
                ['index,follow,max-image-preview:large,max-snippet:-1', 'index,nofollow'],
                'index,nofollow,max-image-preview:large,max-snippet:-1',
            ],
            'magento default uppercase' => [
                ['index,follow,max-snippet:-1', 'INDEX,FOLLOW'],
                'index,follow,max-snippet:-1',
            ],
            'noindex from resolver wins' => [
                ['noindex,follow', 'index,nofollow'],
                'noindex,nofollow',
            ],
            'none expands' => [
                ['index,follow', 'none'],
                'noindex,nofollow',
            ],
            'flags are kept' => [
                ['index,follow', 'noarchive'],
                'index,follow,noarchive',
            ],
            'smaller snippet limit wins' => [
                ['max-snippet:-1', 'max-snippet:50'],
                'max-snippet:50',
            ],
            'smaller image preview wins' => [
                ['max-image-preview:large', 'max-image-preview:standard'],
                'max-image-preview:standard',
            ],
            'invalid value ignored' => [
                ['index,follow', 'index,<script>'],
                'index,follow',
            ],
            'nothing valid' => [
                ['', 'bogus'],
                '',
            ],
            'no arguments' => [
                [],
                '',
            ],
            'all only fills missing values' => [
                ['noindex', 'all'],
                'noindex,follow',
            ],
            'all on its own' => [
                ['all'],
                'index,follow',
            ],
            'unlimited snippet loses to finite limit in either order' => [
                ['max-snippet:20', 'max-snippet:-1'],
                'max-snippet:20',
            ],
            'smaller video preview wins' => [
                ['max-video-preview:30', 'max-video-preview:10'],
                'max-video-preview:10',
            ],
            'larger image preview does not override smaller' => [
                ['max-image-preview:none', 'max-image-preview:large'],
                'max-image-preview:none',
            ],
            'first unavailable_after is kept' => [
                ['unavailable_after:2030-01-01', 'unavailable_after:2031-01-01'],
                'unavailable_after:2030-01-01',
            ],
            'duplicate flags collapse' => [
                ['noarchive,nosnippet', 'NOARCHIVE'],
                'noarchive,nosnippet',
            ],
        ];
    }

    #[DataProvider('mergeProvider')]
    public function testMerge(array $values, string $expected): void
    {
        $merger = new DirectiveMerger(new DirectiveValidator());
        $this->assertSame($expected, $merger->merge(...$values));
    }
}
