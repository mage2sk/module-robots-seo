<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Test\Unit\Model\Config\Source;

use Panth\RobotsSeo\Model\Config\Source\DirectiveAction;
use Panth\RobotsSeo\Model\Config\Source\MaxImagePreview;
use Panth\RobotsSeo\Service\DirectiveValidator;
use PHPUnit\Framework\TestCase;

class SourceModelsTest extends TestCase
{
    public function testDirectiveActionValuesAreAcceptedByTheValidator(): void
    {
        $validator = new DirectiveValidator();
        $values = array_column((new DirectiveAction())->toOptionArray(), 'value');

        $this->assertSame(['allow', 'disallow'], $values);
        foreach ($values as $value) {
            $this->assertTrue($validator->isValidAction($value));
        }
    }

    public function testImagePreviewValuesMatchTheAllowedSet(): void
    {
        $options = (new MaxImagePreview())->toOptionArray();
        $values = array_column($options, 'value');

        $this->assertSame(['large', 'standard', 'none'], $values);
        $this->assertEqualsCanonicalizing(DirectiveValidator::VALID_IMAGE_PREVIEW, $values);
        $this->assertSame('large (recommended)', (string) $options[0]['label']);
    }
}
