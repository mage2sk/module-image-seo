<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Plugin\Image;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Panth\ImageSeo\Model\ImageSeo\ImageTemplateResolver;
use Psr\Log\LoggerInterface;

/**
 * Plugin on Magento\Catalog\Helper\Image::getLabel.
 *
 * When the image alt/title template feature is enabled in config
 * (panth_image_seo/image/image_seo_enabled), replaces the default image label
 * with a template-rendered alt text that can include tokens like
 * {{name}}, {{store}}, {{sku}}, {{category}}.
 *
 * Registered via di.xml with sortOrder="20" so it runs after the simple
 * name-fallback provided by ProductImagePlugin (default sortOrder).
 */
class ImageAttributesPlugin
{
    public function __construct(
        private readonly ImageTemplateResolver $templateResolver,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param ImageHelper $subject
     * @param mixed $result The label string from Magento or the earlier plugin.
     * @return mixed
     */
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

    /**
     * Read the protected _product property from the image helper via a
     * cached ReflectionProperty.
     */
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
