<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Ui\Component\Form\DataProvider;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Panth\RobotsSeo\Model\ResourceModel\RobotsPolicy\CollectionFactory;

/**
 * Supplies the robots-policy edit form with existing row data (edit mode)
 * or sensible defaults (new mode).
 */
class PolicyFormDataProvider extends AbstractDataProvider
{
    private ?array $loadedData = null;

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        if ($this->loadedData !== null) {
            return $this->loadedData;
        }

        $this->loadedData = [];
        foreach ($this->collection->getItems() as $item) {
            $this->loadedData[$item->getId()] = $item->getData();
        }

        if (empty($this->loadedData)) {
            $this->loadedData[''] = [
                'store_id'   => 0,
                'user_agent' => '*',
                'directive'  => 'allow',
                'path'       => '/',
                'priority'   => 10,
                'is_active'  => 1,
            ];
        }

        return $this->loadedData;
    }
}
