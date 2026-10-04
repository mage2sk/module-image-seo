<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Model\ImageSeo;

interface VisionAdapterInterface
{
    public function describe(string $absoluteImagePath, array $context = []): ?array;
}
