<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Plugin\Image;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Panth\ImageSeo\Model\ImageSeo\ImageTemplateResolver;
use Psr\Log\LoggerInterface;

class ImageAttributesPlugin
{
    public function __construct(
        private readonly ImageTemplateResolver $templateResolver,
        private readonly LoggerInterface $logger
    ) {
    }

    public function afterGetLabel(ImageHelper $subject, mixed $result): mixed
    {
        if (!$this->templateResolver->isEnabled()) {
            return $result;
        }

        try {
            $product = $this->extractProduct($subject);
            if ($product === null) {
                return $result;
            }

            $alt = $this->templateResolver->getAlt($product);
            if ($alt !== '') {
                return $alt;
            }
        } catch (\Throwable $e) {
            $this->logger->warning(
                '[PanthImageSeo] ImageAttributesPlugin::afterGetLabel failed',
                ['error' => $e->getMessage()]
            );
        }

        return $result;
    }

    private function extractProduct(ImageHelper $subject): ?ProductInterface
    {
        static $property = null;
        if ($property === null) {
            try {
                $property = new \ReflectionProperty(ImageHelper::class, '_product');
                $property->setAccessible(true);
            } catch (\Throwable $e) {
                return null;
            }
        }
        $product = $property->getValue($subject);
        return $product instanceof ProductInterface ? $product : null;
    }
}
