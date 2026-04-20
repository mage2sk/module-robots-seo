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
            $policyId  = (int) $request->getParam('policy_id', 0);
            $storeId   = (int) $request->getParam('store_id', 0);
            $userAgent = trim((string) $request->getParam('user_agent', ''));
            $directive = strtolower(trim((string) $request->getParam('directive', 'allow')));
            $path      = trim((string) $request->getParam('path', '/'));
            $priority  = (int) $request->getParam('priority', 10);
            $isActive  = (int) (bool) $request->getParam('is_active', 1);

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
