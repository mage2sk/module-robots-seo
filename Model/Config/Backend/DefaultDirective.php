<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Panth\RobotsSeo\Service\DirectiveValidator;

class DefaultDirective extends Value
{
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        private readonly DirectiveValidator $validator,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function beforeSave()
    {
        $value = trim((string) $this->getValue());
        if ($value !== '' && !$this->validator->isValidDirective($value)) {
            throw new LocalizedException(__(
                'Default Meta Robots "%1" is not valid. Use comma separated tokens such as index,follow or noindex,nofollow.',
                $value
            ));
        }
        $this->setValue($value);
        return parent::beforeSave();
    }
}
