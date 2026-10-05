<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Test\Unit\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Panth\RobotsSeo\Model\Config\Backend\DefaultDirective;
use Panth\RobotsSeo\Service\DirectiveValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DefaultDirectiveTest extends TestCase
{
    private function model(string $value): DefaultDirective
    {
        $context = $this->createStub(Context::class);
        $context->method('getEventDispatcher')->willReturn($this->createStub(ManagerInterface::class));
        $model = new DefaultDirective(
            $context,
            $this->createStub(Registry::class),
            $this->createStub(ScopeConfigInterface::class),
            $this->createStub(TypeListInterface::class),
            new DirectiveValidator(),
            $this->createStub(AbstractResource::class)
        );
        $model->setValue($value);
        return $model;
    }

    #[DataProvider('validProvider')]
    public function testValidDirectiveIsTrimmedAndAccepted(string $value, string $expected): void
    {
        $model = $this->model($value);
        $model->beforeSave();
        $this->assertSame($expected, $model->getValue());
    }

    public static function validProvider(): array
    {
        return [
            'default' => ['index,follow', 'index,follow'],
            'spaced' => ['  noindex, nofollow ', 'noindex, nofollow'],
            'advanced' => ['index,follow,max-snippet:-1,max-image-preview:large', 'index,follow,max-snippet:-1,max-image-preview:large'],
            'blank falls back at runtime' => ['   ', ''],
        ];
    }

    #[DataProvider('invalidProvider')]
    public function testInvalidDirectiveIsRejected(string $value): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('is not valid');
        $this->model($value)->beforeSave();
    }

    public static function invalidProvider(): array
    {
        return [
            'typo' => ['noindx,follow'],
            'html' => ['index,<script>'],
            'bad image preview' => ['max-image-preview:huge'],
            'bad snippet' => ['max-snippet:abc'],
        ];
    }
}
