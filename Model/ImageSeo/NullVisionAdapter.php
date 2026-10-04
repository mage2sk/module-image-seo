<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Model\ImageSeo;

class NullVisionAdapter implements VisionAdapterInterface
{
    public function describe(string $absoluteImagePath, array $context = []): ?array
    {
        return null;
    }
}
