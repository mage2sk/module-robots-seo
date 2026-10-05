<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Test\Unit\Controller\Adminhtml\Policy;

use Panth\RobotsSeo\Controller\Adminhtml\Policy\Save;
use Panth\RobotsSeo\Model\ResourceModel\RobotsPolicy as PolicyResource;
use Panth\RobotsSeo\Model\Robots\Policy;
use Panth\RobotsSeo\Service\DirectiveValidator;
use Panth\RobotsSeo\Test\Unit\Controller\Adminhtml\ControllerTestCase;

class SaveTest extends ControllerTestCase
{
    private ?int $modelId = null;
    private array $savedData = [];
    private int $saveCalls = 0;
    private array $loaded = [];

    /**
     * @param int|null $existingId id that load() finds; null = nothing found
     */
    private function controller(
        array $params,
        ?array $post,
        ?int $existingId = null,
        ?\Throwable $saveError = null
    ): Save {
        $this->modelId = null;
        $this->savedData = [];
        $this->saveCalls = 0;
        $this->loaded = [];

        $model = $this->createStub(Policy::class);
        $model->method('getId')->willReturnCallback(fn() => $this->modelId);
        $model->method('setData')->willReturnCallback(function ($data) use ($model) {
            $this->savedData = $data;
            return $model;
        });

        $resource = $this->createStub(PolicyResource::class);
        $resource->method('load')->willReturnCallback(function ($m, $id) use ($existingId, $resource) {
            $this->loaded[] = $id;
            if ($existingId !== null && (int) $id === $existingId) {
                $this->modelId = $existingId;
            }
            return $resource;
        });
        $resource->method('save')->willReturnCallback(function () use ($saveError, $resource) {
            if ($saveError) {
                throw $saveError;
            }
            $this->saveCalls++;
            $this->modelId = $this->modelId ?? 42;
            return $resource;
        });

        return new Save($this->context($params, $post), $model, $resource, new DirectiveValidator());
    }

    private function validPost(array $override = []): array
    {
        return $override + [
            'store_id' => '1',
            'user_agent' => ' Googlebot ',
            'directive' => ' DISALLOW ',
            'path' => ' /private/ ',
            'priority' => '5',
            'is_active' => '1',
        ];
    }

    public function testNewPolicyIsSavedWithNormalisedValues(): void
    {
        $this->controller([], $this->validPost())->execute();

        $this->assertSame(1, $this->saveCalls);
        $this->assertSame([
            'policy_id' => null,
            'store_id' => 1,
            'user_agent' => 'Googlebot',
            'directive' => 'disallow',
            'path' => '/private/',
            'priority' => 5,
            'is_active' => 1,
        ], $this->savedData);
        $this->assertSame(['Robots policy saved.'], $this->success);
        $this->assertSame(['*/*/', []], $this->redirect);
        $this->assertSame([], $this->loaded);
    }

    public function testNestedDataKeyTakesPrecedence(): void
    {
        $post = ['data' => $this->validPost(['user_agent' => 'Bingbot']), 'user_agent' => 'Ignored'];
        $this->controller([], $post)->execute();

        $this->assertSame('Bingbot', $this->savedData['user_agent']);
    }

    public function testRequestParamsUsedWhenPostEmpty(): void
    {
        $this->controller($this->validPost(['user_agent' => '*']), null)->execute();

        $this->assertSame('*', $this->savedData['user_agent']);
        $this->assertSame(1, $this->saveCalls);
    }

    public function testNegativePriorityClampedAndInactiveStored(): void
    {
        $this->controller([], $this->validPost(['priority' => '-3', 'is_active' => '0']))->execute();

        $this->assertSame(0, $this->savedData['priority']);
        $this->assertSame(0, $this->savedData['is_active']);
    }

    public function testExistingPolicyIsUpdatedAndBackToEdit(): void
    {
        $this->controller(['back' => 'edit'], $this->validPost(['policy_id' => '7']), 7)->execute();

        $this->assertSame([7], $this->loaded);
        $this->assertSame(7, $this->savedData['policy_id']);
        $this->assertSame(['*/*/edit', ['policy_id' => 7]], $this->redirect);
    }

    public function testNewPolicyBackToEditUsesNewId(): void
    {
        $this->controller(['back' => 'edit'], $this->validPost())->execute();
        $this->assertSame(['*/*/edit', ['policy_id' => 42]], $this->redirect);
    }

    public function testUnknownPolicyIdIsRejected(): void
    {
        $this->controller([], $this->validPost(['policy_id' => '9']))->execute();

        $this->assertSame(0, $this->saveCalls);
        $this->assertSame(['Policy row not found.'], $this->errors);
        $this->assertSame(['*/*/', []], $this->redirect);
    }

    public function testInvalidUserAgentIsRejected(): void
    {
        $this->controller([], $this->validPost(['user_agent' => "Bot\nDisallow: /"]))->execute();

        $this->assertSame(0, $this->saveCalls);
        $this->assertStringContainsString('User agent must contain only', $this->errors[0]);
    }

    public function testInvalidDirectiveIsRejected(): void
    {
        $this->controller([], $this->validPost(['directive' => 'deny']))->execute();

        $this->assertSame(0, $this->saveCalls);
        $this->assertSame(['Directive must be "allow" or "disallow".'], $this->errors);
    }

    public function testInvalidPathIsRejected(): void
    {
        $this->controller([], $this->validPost(['path' => 'no-slash']))->execute();

        $this->assertSame(0, $this->saveCalls);
        $this->assertStringContainsString('Path must start with "/"', $this->errors[0]);
    }

    public function testStorageFailureIsReported(): void
    {
        $this->controller([], $this->validPost(), null, new \RuntimeException('disk full'))->execute();

        $this->assertSame(['Unable to save policy: disk full'], $this->errors);
        $this->assertSame([], $this->success);
        $this->assertSame(['*/*/', []], $this->redirect);
    }
}
