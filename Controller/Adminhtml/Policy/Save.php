<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Controller\Adminhtml\Policy;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Panth\RobotsSeo\Controller\Adminhtml\AbstractAction;
use Panth\RobotsSeo\Model\ResourceModel\RobotsPolicy as PolicyResource;
use Panth\RobotsSeo\Model\Robots\Policy as PolicyModel;
use Panth\RobotsSeo\Service\DirectiveValidator;

/**
 * Save a robots policy row. Every input field is re-validated server-side:
 * - `user_agent` must match the printable-ASCII whitelist (no CRLF, no `;`).
 * - `directive` must be `allow` or `disallow`.
 * - `path` must start with `/` and contain no control bytes.
 * - `store_id`, `priority`, `is_active` are cast to int.
 *
 * FormKey + ACL are enforced by the framework (`HttpPostActionInterface`
 * triggers automatic `form_key` verification in the backend action flow).
 */
class Save extends AbstractAction implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Panth_RobotsSeo::policies_save';

    public function __construct(
        Context $context,
        private readonly PolicyModel $policyModel,
        private readonly PolicyResource $policyResource,
        private readonly DirectiveValidator $validator
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $request = $this->getRequest();

        try {
            // ui_component forms submit with `dataScope=data` so fields land
            // as request params AND as `data[<field>]` via getPostValue().
            // Merge both so the controller works regardless of submit mode.
            $post = (array) $request->getPostValue();
            $nested = (array) ($post['data'] ?? []);
            $get = static fn(string $k, $d) => $nested[$k] ?? $post[$k] ?? $request->getParam($k, $d);

            $policyId  = (int) $get('policy_id', 0);
            $storeId   = (int) $get('store_id', 0);
            $userAgent = trim((string) $get('user_agent', ''));
            $directive = strtolower(trim((string) $get('directive', 'allow')));
            $path      = trim((string) $get('path', '/'));
            $priority  = (int) $get('priority', 10);
            $isActive  = (int) (bool) $get('is_active', 1);

            if (!$this->validator->isValidUserAgent($userAgent)) {
                throw new LocalizedException(__('User agent must contain only letters, digits, and . _ - + * / characters (no control chars or line breaks).'));
            }
            if (!$this->validator->isValidAction($directive)) {
                throw new LocalizedException(__('Directive must be "allow" or "disallow".'));
            }
            if (!$this->validator->isValidPath($path)) {
                throw new LocalizedException(__('Path must start with "/" and must not contain control characters.'));
            }

            $model = $this->policyModel;
            if ($policyId > 0) {
                $this->policyResource->load($model, $policyId);
                if (!$model->getId()) {
                    throw new LocalizedException(__('Policy row not found.'));
                }
            }
            $model->setData([
                'policy_id'  => $model->getId(),
                'store_id'   => $storeId,
                'user_agent' => $userAgent,
                'directive'  => $directive,
                'path'       => $path,
                'priority'   => max(0, $priority),
                'is_active'  => $isActive ? 1 : 0,
            ]);
            $this->policyResource->save($model);

            $this->messageManager->addSuccessMessage(__('Robots policy saved.'));
            if ($request->getParam('back') === 'edit') {
                return $resultRedirect->setPath('*/*/edit', ['policy_id' => (int) $model->getId()]);
            }
            return $resultRedirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('Unable to save policy: %1', $e->getMessage()));
        }
        return $resultRedirect->setPath('*/*/');
    }
}
