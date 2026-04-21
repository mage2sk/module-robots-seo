<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Renders a per-row Actions dropdown (Edit / Delete) on the policy grid.
 */
class PolicyActions extends Column
{
    public const URL_EDIT   = 'panth_robots_seo/policy/edit';
    public const URL_DELETE = 'panth_robots_seo/policy/delete';

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        $name = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            $id = (int) ($item['policy_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $item[$name]['edit'] = [
                'href'  => $this->urlBuilder->getUrl(self::URL_EDIT, ['policy_id' => $id]),
                'label' => (string) __('Edit'),
            ];
            $item[$name]['delete'] = [
                'href'    => $this->urlBuilder->getUrl(self::URL_DELETE, ['policy_id' => $id]),
                'label'   => (string) __('Delete'),
                'confirm' => [
                    'title'   => (string) __('Delete robots policy %1', $id),
                    'message' => (string) __('Are you sure you want to delete this row? This cannot be undone.'),
                ],
                'post' => true,
            ];
        }

        return $dataSource;
    }
}
