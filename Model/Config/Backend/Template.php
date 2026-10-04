<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Panth\ImageSeo\Model\ImageSeo\TemplateValidator;

class Template extends Value
{
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        private readonly TemplateValidator $templateValidator,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function beforeSave()
    {
        $value = trim((string) $this->getValue());
        $this->setValue($value);
        $label = (string) ($this->getData('field_config/label') ?: 'Template');
        $this->templateValidator->validate($value, $label);
        return parent::beforeSave();
    }
}
