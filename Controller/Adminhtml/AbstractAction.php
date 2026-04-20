<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

/**
 * Shared base class for all Panth_RobotsSeo admin controllers. Centralises
 * the ACL check via `ADMIN_RESOURCE` so every subclass inherits a matching
 * `_isAllowed()` implementation without needing to duplicate boilerplate.
 */
abstract class AbstractAction extends Action
{
    /**
     * Default ACL resource. Sub-classes SHOULD override this constant with
     * their own, more specific resource string when the action is
     * mutation-capable (save, delete) so the Robots & Config resources can
     * be granted independently.
     */
    public const ADMIN_RESOURCE = 'Panth_RobotsSeo::manage';

    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed(static::ADMIN_RESOURCE);
    }
}
