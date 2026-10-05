<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Test\Unit\Ui\Component;

use Magento\Framework\Api\Filter;
use Magento\Framework\Data\Collection;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Framework\View\Element\UiComponentFactory;
use Panth\RobotsSeo\Model\ResourceModel\RobotsPolicy\Collection as PolicyCollection;
use Panth\RobotsSeo\Model\ResourceModel\RobotsPolicy\CollectionFactory;
use Panth\RobotsSeo\Ui\Component\Form\DataProvider\PolicyFormDataProvider;
use Panth\RobotsSeo\Ui\Component\Listing\Column\PolicyActions;
use Panth\RobotsSeo\Ui\Component\Listing\LikeFulltextFilter;
use PHPUnit\Framework\TestCase;

class UiComponentsTest extends TestCase
{
    private function formProvider(array $items): PolicyFormDataProvider
    {
        $collection = $this->createStub(PolicyCollection::class);
        $collection->method('getItems')->willReturn($items);
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        return new PolicyFormDataProvider('form', 'policy_id', 'policy_id', $factory);
    }

    public function testFormDataKeyedById(): void
    {
        $item = new DataObject(['policy_id' => 3, 'user_agent' => 'Googlebot']);
        $item->setId(3);
        $data = $this->formProvider([$item])->getData();

        $this->assertSame([3], array_keys($data));
        $this->assertSame('Googlebot', $data[3]['user_agent']);
    }

    public function testFormDataDefaultsForNewPolicy(): void
    {
        $provider = $this->formProvider([]);
        $data = $provider->getData();

        $this->assertSame([
            'store_id' => 0,
            'user_agent' => '*',
            'directive' => 'allow',
            'path' => '/',
            'priority' => 10,
            'is_active' => 1,
        ], $data['']);
        $this->assertSame($data, $provider->getData());
    }

    private function actions(): PolicyActions
    {
        $processor = $this->createStub(Processor::class);
        $context = $this->createStub(ContextInterface::class);
        $context->method('getProcessor')->willReturn($processor);
        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(
            static fn($route, $params) => $route . '/id/' . $params['policy_id']
        );
        return new PolicyActions(
            $context,
            $this->createStub(UiComponentFactory::class),
            $url,
            [],
            ['name' => 'actions']
        );
    }

    public function testActionsAddedForRowsWithIds(): void
    {
        $source = ['data' => ['items' => [['policy_id' => '5'], ['policy_id' => 0], []]]];
        $items = $this->actions()->prepareDataSource($source)['data']['items'];

        $this->assertSame(PolicyActions::URL_EDIT . '/id/5', $items[0]['actions']['edit']['href']);
        $this->assertSame(PolicyActions::URL_DELETE . '/id/5', $items[0]['actions']['delete']['href']);
        $this->assertTrue($items[0]['actions']['delete']['post']);
        $this->assertSame('Delete robots policy 5', $items[0]['actions']['delete']['confirm']['title']);
        $this->assertArrayNotHasKey('actions', $items[1]);
        $this->assertArrayNotHasKey('actions', $items[2]);
    }

    public function testEmptyDataSourceIsReturnedUnchanged(): void
    {
        $source = ['data' => ['items' => []]];
        $this->assertSame($source, $this->actions()->prepareDataSource($source));
    }

    private function dbCollection(array &$wheres): PolicyCollection
    {
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('quoteIdentifier')->willReturnCallback(static fn($c) => '`' . $c . '`');
        $connection->method('quoteInto')->willReturnCallback(
            static fn($text, $value) => str_replace('?', "'" . $value . "'", $text)
        );
        $select = $this->createStub(Select::class);
        $select->method('where')->willReturnCallback(function ($cond) use (&$wheres, $select) {
            $wheres[] = $cond;
            return $select;
        });
        $collection = $this->createStub(PolicyCollection::class);
        $collection->method('getConnection')->willReturn($connection);
        $collection->method('getSelect')->willReturn($select);
        return $collection;
    }

    public function testLikeFilterSearchesAllStringColumnsWithEscaping(): void
    {
        $wheres = [];
        $filter = new Filter();
        $filter->setValue('  50%_off ');

        (new LikeFulltextFilter(['user_agent', 'path', 42]))->apply($this->dbCollection($wheres), $filter);

        $this->assertSame(
            ["`user_agent` LIKE '%50\\%\\_off%' OR `path` LIKE '%50\\%\\_off%'"],
            $wheres
        );
    }

    public function testLikeFilterIgnoresBlankAndNonScalarValues(): void
    {
        $wheres = [];
        $collection = $this->dbCollection($wheres);
        $like = new LikeFulltextFilter(['path']);

        $blank = new Filter();
        $blank->setValue('   ');
        $like->apply($collection, $blank);

        $array = new Filter();
        $array->setValue(['x']);
        $like->apply($collection, $array);

        $this->assertSame([], $wheres);
    }

    public function testLikeFilterNeedsColumnsAndDbCollection(): void
    {
        $wheres = [];
        $filter = new Filter();
        $filter->setValue('abc');

        (new LikeFulltextFilter([]))->apply($this->dbCollection($wheres), $filter);
        (new LikeFulltextFilter(['path']))->apply($this->createStub(Collection::class), $filter);

        $this->assertSame([], $wheres);
    }

    public function testLikeFilterTruncatesLongInput(): void
    {
        $wheres = [];
        $filter = new Filter();
        $filter->setValue(str_repeat('a', 300));

        (new LikeFulltextFilter(['path']))->apply($this->dbCollection($wheres), $filter);

        $this->assertSame("`path` LIKE '%" . str_repeat('a', 200) . "%'", $wheres[0]);
    }
}
