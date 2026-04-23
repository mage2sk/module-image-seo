<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Plugin\Image;

use Magento\Catalog\Block\Product\Image as ImageBlock;
use Magento\Catalog\Block\Product\ImageFactory;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Panth\ImageSeo\Model\ImageSeo\ImageTemplateResolver;
use Psr\Log\LoggerInterface;

/**
 * Plugin on Magento\Catalog\Block\Product\ImageFactory::create.
 *
 * ImageFactory is the real source of the `alt` / `label` value used by
 * product-listing image blocks — category grids, related products,
 * upsells, cross-sells, widgets. It calls a PRIVATE `getLabel()` method
 * internally which reads `$product->getData('image_label')` and falls
 * back to `$product->getName()`. That private call never goes through
 * \Magento\Catalog\Helper\Image::getLabel, so the plugins on the helper
 * (ImageAttributesPlugin, ProductImagePlugin) don't cover this surface.
 *
 * This after-plugin runs once the ImageBlock is constructed, reads the
 * product off the block's data, renders the configured alt template and
 * overwrites the block's `label` so the alt attribute on the rendered
 * <img> tag matches what the gallery / helper paths produce.
 *
 * Only runs when the master feature flag is on; always a no-op when the
 * template renders empty.
 */
class ImageFactoryPlugin
{
    public function __construct(
        private readonly ImageTemplateResolver $templateResolver,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param ImageFactory $subject
     * @param ImageBlock   $result  The block returned by the native create().
     * @param Product      $product The product passed in.
     * @return ImageBlock
     */
    public function afterCreate(
        ImageFactory $subject,
        $result,
        Product $product
    ) {
        if (!$this->templateResolver->isEnabled()) {
            return $result;
        }

        // ImageBlock extends DataObject, which handles label via __call
        // magic (no explicit setLabel method — method_exists returns false).
        // Use DataObject::setData directly to guarantee the write.
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
