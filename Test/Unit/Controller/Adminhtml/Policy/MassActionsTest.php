<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Test\Unit\Controller\Adminhtml\Policy;

use Magento\Ui\Component\MassAction\Filter;
use Panth\RobotsSeo\Controller\Adminhtml\Policy\MassDelete;
use Panth\RobotsSeo\Controller\Adminhtml\Policy\MassStatus;
use Panth\RobotsSeo\Model\ResourceModel\RobotsPolicy\Collection;
use Panth\RobotsSeo\Model\ResourceModel\RobotsPolicy\CollectionFactory;
use Panth\RobotsSeo\Model\Robots\Policy;
use Panth\RobotsSeo\Test\Unit\Controller\Adminhtml\ControllerTestCase;

class MassActionsTest extends ControllerTestCase
{
    private array $events = [];

    private function rows(int $count, ?\Throwable $failOn = null): array
    {
        $rows = [];
        for ($i = 1; $i <= $count; $i++) {
            $row = $this->createStub(Policy::class);
            $row->method('delete')->willReturnCallback(function () use ($row, $i, $failOn) {
                if ($failOn && $i === 2) {
                    throw $failOn;
                }
                $this->events[] = 'delete:' . $i;
                return $row;
            });
            $row->method('setData')->willReturnCallback(function ($key, $value) use ($row, $i) {
                $this->events[] = 'set:' . $i . ':' . $key . '=' . $value;
                return $row;
            });
            $row->method('save')->willReturnCallback(function () use ($row, $i, $failOn) {
                if ($failOn && $i === 2) {
                    throw $failOn;
                }
                $this->events[] = 'save:' . $i;
                return $row;
            });
            $rows[] = $row;
        }
        return $rows;
    }

    private function filter(array $rows): array
    {
        $this->events = [];
        $collection = $this->createStub(Collection::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator($rows));
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        $filter = $this->createStub(Filter::class);
        $filter->method('getCollection')->willReturn($collection);
        return [$filter, $factory];
    }

    public function testMassDeleteRemovesEverySelectedRow(): void
    {
        [$filter, $factory] = $this->filter($this->rows(3));
        (new MassDelete($this->context(), $filter, $factory))->execute();

        $this->assertSame(['delete:1', 'delete:2', 'delete:3'], $this->events);
        $this->assertSame(['Deleted 3 robots policy row(s).'], $this->success);
        $this->assertSame(['*/*/', []], $this->redirect);
    }

    public function testMassDeleteReportsFailure(): void
    {
        [$filter, $factory] = $this->filter($this->rows(3, new \RuntimeException('fk')));
        (new MassDelete($this->context(), $filter, $factory))->execute();

        $this->assertSame(['delete:1'], $this->events);
        $this->assertSame(['Unable to delete selected rows: fk'], $this->errors);
        $this->assertSame([], $this->success);
    }

    public function testMassStatusEnables(): void
    {
        [$filter, $factory] = $this->filter($this->rows(2));
        (new MassStatus($this->context(['status' => '1']), $filter, $factory))->execute();

        $this->assertSame(['set:1:is_active=1', 'save:1', 'set:2:is_active=1', 'save:2'], $this->events);
        $this->assertSame(['2 robots policy row(s) were enabled.'], $this->success);
    }

    public function testMassStatusDisables(): void
    {
        [$filter, $factory] = $this->filter($this->rows(1));
        (new MassStatus($this->context(['status' => '0']), $filter, $factory))->execute();

        $this->assertSame(['set:1:is_active=0', 'save:1'], $this->events);
        $this->assertSame(['1 robots policy row(s) were disabled.'], $this->success);
    }

    public function testMassStatusNormalisesNonBinaryValues(): void
    {
        [$filter, $factory] = $this->filter($this->rows(1));
        (new MassStatus($this->context(['status' => '5']), $filter, $factory))->execute();

        $this->assertSame(['set:1:is_active=1', 'save:1'], $this->events);
    }

    public function testMassStatusReportsFailure(): void
    {
        [$filter, $factory] = $this->filter($this->rows(2, new \RuntimeException('deadlock')));
        (new MassStatus($this->context(['status' => '1']), $filter, $factory))->execute();

        $this->assertSame(['Unable to update selected rows: deadlock'], $this->errors);
        $this->assertSame(['*/*/', []], $this->redirect);
    }

    public function testMassStatusWithoutStatusChangesNothing(): void
    {
        [$filter, $factory] = $this->filter($this->rows(2));
        (new MassStatus($this->context(), $filter, $factory))->execute();

        $this->assertSame([], $this->events);
        $this->assertSame([], $this->success);
        $this->assertSame(['Please choose a status to apply.'], $this->errors);
        $this->assertSame(['*/*/', []], $this->redirect);
    }
}
