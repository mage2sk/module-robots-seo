<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Test\Unit\Block\Adminhtml\Policy\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\UrlInterface;
use Panth\RobotsSeo\Block\Adminhtml\Policy\Edit\BackButton;
use Panth\RobotsSeo\Block\Adminhtml\Policy\Edit\DeleteButton;
use Panth\RobotsSeo\Block\Adminhtml\Policy\Edit\SaveAndContinueButton;
use Panth\RobotsSeo\Block\Adminhtml\Policy\Edit\SaveButton;
use PHPUnit\Framework\TestCase;

class ButtonsTest extends TestCase
{
    private function context($policyId): Context
    {
        $request = $this->createStub(HttpRequest::class);
        $request->method('getParam')->willReturnCallback(
            static fn($key) => $key === 'policy_id' ? $policyId : null
        );
        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(
            static fn($route, $params = []) => 'https://admin.test/' . $route
                . ($params ? '?' . http_build_query($params) : '')
        );
        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getUrlBuilder')->willReturn($url);
        $context->method('getEscaper')->willReturn(new \Magento\Framework\Escaper());
        return $context;
    }

    public function testPolicyIdIsReadAsInteger(): void
    {
        $this->assertSame(12, (new BackButton($this->context('12')))->getPolicyId());
        $this->assertSame(0, (new BackButton($this->context(null)))->getPolicyId());
    }

    public function testDeleteButtonHiddenForNewPolicy(): void
    {
        $this->assertSame([], (new DeleteButton($this->context(null)))->getButtonData());
    }

    public function testDeleteButtonPostsToDeleteUrl(): void
    {
        $data = (new DeleteButton($this->context('7')))->getButtonData();

        $this->assertSame('Delete', $data['label']);
        $this->assertSame('delete', $data['class']);
        $this->assertStringContainsString("'https://admin.test/*/*/delete?policy_id=7'", $data['on_click']);
        $this->assertStringStartsWith('deleteConfirm(', $data['on_click']);
    }

    public function testBackButtonLinksToGrid(): void
    {
        $data = (new BackButton($this->context(null)))->getButtonData();
        $this->assertSame("location.href = 'https://admin.test/*/*/';", $data['on_click']);
    }

    public function testSaveButtonsTriggerFormEvents(): void
    {
        $save = (new SaveButton($this->context(null)))->getButtonData();
        $continue = (new SaveAndContinueButton($this->context(null)))->getButtonData();

        $this->assertSame('save', $save['data_attribute']['mage-init']['button']['event']);
        $this->assertSame('saveAndContinueEdit', $continue['data_attribute']['mage-init']['button']['event']);
        $this->assertGreaterThan($continue['sort_order'], $save['sort_order']);
    }

    private function withApostropheTranslation(callable $fn): mixed
    {
        $previous = \Magento\Framework\Phrase::getRenderer();
        \Magento\Framework\Phrase::setRenderer(new class implements \Magento\Framework\Phrase\RendererInterface {
            public function render(array $source, array $arguments)
            {
                return "It's " . end($source);
            }
        });
        try {
            return $fn();
        } finally {
            \Magento\Framework\Phrase::setRenderer($previous);
        }
    }

    public function testTranslatedConfirmTextIsJsEscaped(): void
    {
        $data = $this->withApostropheTranslation(
            fn() => (new DeleteButton($this->context('7')))->getButtonData()
        );

        $this->assertStringNotContainsString("It's", $data['on_click']);
        $this->assertStringContainsString('It\\u0027s', $data['on_click']);
    }
}
