<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Test\Unit\Controller\Adminhtml\Policy;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Forward;
use Magento\Framework\Controller\ResultFactory;
use Panth\RobotsSeo\Controller\Adminhtml\Policy\NewAction;
use PHPUnit\Framework\TestCase;

class NewActionTest extends TestCase
{
    public function testForwardsToEdit(): void
    {
        $forward = $this->createMock(Forward::class);
        $forward->expects($this->once())->method('forward')->with('edit')->willReturnSelf();
        $resultFactory = $this->createMock(ResultFactory::class);
        $resultFactory->expects($this->once())->method('create')
            ->with(ResultFactory::TYPE_FORWARD)->willReturn($forward);
        $context = $this->createStub(Context::class);
        $context->method('getResultFactory')->willReturn($resultFactory);

        $this->assertSame($forward, (new NewAction($context))->execute());
    }
}
