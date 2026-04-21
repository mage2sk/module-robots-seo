<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Model\ResourceModel\RobotsPolicy\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

/**
 * Real PHP class (not a virtualType) so the UI listing DataProvider can be
 * resolved at compile time and the grid filters pick up the correct PK.
 *
 * `mainTable` and `resourceModel` are injected from `etc/di.xml`, matching
 * the convention documented in `feedback_grid_collection.md`.
 */
class Collection extends SearchResult
{
    protected function _initSelect(): static
    {
        parent::_initSelect();
        return $this;
    }
}
