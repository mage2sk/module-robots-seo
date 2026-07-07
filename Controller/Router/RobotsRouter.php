<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Controller\Router;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Router\ActionList;
use Magento\Framework\App\RouterInterface;

class RobotsRouter implements RouterInterface
{
    public function __construct(
        private readonly ActionFactory $actionFactory,
        private readonly ActionList $actionList
    ) {
    }

    public function match(RequestInterface $request): ?ActionInterface
    {
        $identifier = trim((string) $request->getPathInfo(), '/');
        if ($identifier !== 'robots.txt') {
            return null;
        }

        $actionClass = $this->actionList->get('Panth_RobotsSeo', null, 'robots', 'index');
        if ($actionClass === null) {
            return null;
        }
        return $this->actionFactory->create($actionClass);
    }
}
