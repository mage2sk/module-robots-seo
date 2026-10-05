<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Test\Unit\Controller\Adminhtml\Policy;

use Panth\RobotsSeo\Controller\Adminhtml\Policy\Delete;
use Panth\RobotsSeo\Model\ResourceModel\RobotsPolicy as PolicyResource;
use Panth\RobotsSeo\Model\Robots\Policy;
use Panth\RobotsSeo\Test\Unit\Controller\Adminhtml\ControllerTestCase;

class DeleteTest extends ControllerTestCase
{
    private int $deleted = 0;

    private function controller(array $params, ?int $foundId, ?\Throwable $deleteError = null): Delete
    {
        $this->deleted = 0;
        $id = null;

        $model = $this->createStub(Policy::class);
        $model->method('getId')->willReturnCallback(static function () use (&$id) {
            return $id;
        });

        $resource = $this->createStub(PolicyResource::class);
        $resource->method('load')->willReturnCallback(static function () use (&$id, $foundId, $resource) {
            $id = $foundId;
            return $resource;
        });
        $resource->method('delete')->willReturnCallback(function () use ($deleteError, $resource) {
            if ($deleteError) {
                throw $deleteError;
            }
            $this->deleted++;
            return $resource;
        });

        return new Delete($this->context($params), $model, $resource);
    }

    public function testDeletesExistingPolicy(): void
    {
        $this->controller(['policy_id' => '3'], 3)->execute();

        $this->assertSame(1, $this->deleted);
        $this->assertSame(['Robots policy deleted.'], $this->success);
        $this->assertSame(['*/*/', []], $this->redirect);
    }

    public function testMissingIdIsRejected(): void
    {
        $this->controller([], 3)->execute();

        $this->assertSame(0, $this->deleted);
        $this->assertSame(['Invalid policy id.'], $this->errors);
    }

    public function testNegativeIdIsRejected(): void
    {
        $this->controller(['policy_id' => '-1'], 3)->execute();
        $this->assertSame(['Invalid policy id.'], $this->errors);
    }

    public function testUnknownPolicyIsReported(): void
    {
        $this->controller(['policy_id' => '5'], null)->execute();

        $this->assertSame(0, $this->deleted);
        $this->assertSame(['Policy not found.'], $this->errors);
        $this->assertSame(['*/*/', []], $this->redirect);
    }

    public function testDeleteFailureIsReported(): void
    {
        $this->controller(['policy_id' => '5'], 5, new \RuntimeException('locked'))->execute();

        $this->assertSame(['Unable to delete: locked'], $this->errors);
        $this->assertSame([], $this->success);
    }
}
