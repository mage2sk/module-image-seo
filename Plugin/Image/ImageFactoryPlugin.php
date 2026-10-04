<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Plugin\Image;

use Magento\Catalog\Block\Product\Image as ImageBlock;
use Magento\Catalog\Block\Product\ImageFactory;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Panth\ImageSeo\Model\ImageSeo\ImageTemplateResolver;
use Psr\Log\LoggerInterface;

class ImageFactoryPlugin
{
    public function __construct(
        private readonly ImageTemplateResolver $templateResolver,
        private readonly LoggerInterface $logger
    ) {
    }

    public function afterCreate(
        ImageFactory $subject,
        $result,
        Product $product
    ) {
        if (!$this->templateResolver->isEnabled()) {
            return $result;
        }

        if (!$result instanceof DataObject) {
            return $result;
        }

        try {
            $alt = $this->templateResolver->getAlt($product);
            if ($alt !== '') {
                $result->setData('label', $alt);
            }
        } catch (\Throwable $e) {
            $this->logger->warning(
                '[PanthImageSeo] ImageFactoryPlugin::afterCreate failed',
                ['error' => $e->getMessage(), 'product_id' => $product->getId()]
            );
        }

        return $result;
    }
}
